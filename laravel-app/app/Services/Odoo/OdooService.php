<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Core Odoo JSON-RPC client.
 *
 * Handles authentication, session management, and all standard
 * ORM operations (search_read, create, write, unlink) as well as
 * arbitrary method calls via execute_kw.
 */
class OdooService
{
    protected Client $client;
    protected CookieJar $cookieJar;
    protected string $url;
    protected string $db;
    protected string $username;
    protected string $apiKey;
    protected int $timeout;
    protected ?string $sessionId = null;
    protected ?int $uid = null;
    protected int $requestId = 0;

    /**
     * @param string $url      Base URL of the Odoo instance (e.g. https://odoo.ramina.co.id).
     * @param string $db       Database name.
     * @param string $username Login username / email.
     * @param string $apiKey   API key or password used for execute_kw auth.
     * @param int    $timeout  HTTP timeout in seconds.
     */
    public function __construct(
        string $url,
        string $db,
        string $username,
        string $apiKey,
        int $timeout = 30
    ) {
        $this->url      = rtrim($url, '/');
        $this->db       = $db;
        $this->username = $username;
        $this->apiKey   = $apiKey;
        $this->timeout  = $timeout;
        $this->cookieJar = new CookieJar();

        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout'  => $this->timeout,
            'cookies'  => $this->cookieJar,
            'verify'   => false,
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Authentication & Session
    // ─────────────────────────────────────────────────────────────

    /**
     * Authenticate against Odoo via /web/session/authenticate.
     *
     * @param string $db       Database name.
     * @param string $login    Username / email.
     * @param string $password User password or API key.
     * @return array{uid: int, session_id: string}
     *
     * @throws OdooException On authentication failure or transport error.
     */
    public function authenticate(string $db, string $login, string $password): array
    {
        $params = [
            'db'       => $db,
            'login'    => $login,
            'password' => $password,
        ];

        $response = $this->jsonRpc('/web/session/authenticate', $params);

        // Odoo returns uid = false when credentials are invalid.
        $uid = $response['uid'] ?? false;

        if ($uid === false || $uid === null) {
            throw new OdooException(
                message: 'Authentication failed: invalid credentials.',
                code: 401,
                errorData: ['response' => $response],
                odooErrorType: 'access_denied',
            );
        }

        $this->uid = (int) $uid;

        // Extract session_id from the cookie jar or the response body.
        $this->sessionId = $this->extractSessionId($response);

        Log::info('Odoo authentication successful.', [
            'uid'        => $this->uid,
            'session_id' => $this->sessionId,
            'db'         => $db,
        ]);

        return [
            'uid'        => $this->uid,
            'session_id' => $this->sessionId,
        ];
    }

    /**
     * Restore a previously stored session without re-authenticating.
     *
     * @param string $sessionId The session_id value.
     * @param int    $uid       The Odoo user ID.
     */
    public function setSession(string $sessionId, int $uid): void
    {
        $this->sessionId = $sessionId;
        $this->uid       = $uid;

        // Parse the host from the URL so we can set the cookie correctly.
        $parsedUrl = parse_url($this->url);
        $domain    = $parsedUrl['host'] ?? 'localhost';

        $cookie = new SetCookie([
            'Name'     => 'session_id',
            'Value'    => $sessionId,
            'Domain'   => $domain,
            'Path'     => '/',
            'Secure'   => str_starts_with($this->url, 'https'),
            'HttpOnly' => true,
        ]);

        $this->cookieJar->setCookie($cookie);
    }

    // ─────────────────────────────────────────────────────────────
    //  Core ORM wrappers
    // ─────────────────────────────────────────────────────────────

    /**
     * Call execute_kw on an Odoo model via JSON-RPC.
     *
     * @param string $model  Odoo model name (e.g. 'hr.employee').
     * @param string $method ORM method name (e.g. 'search_read', 'create').
     * @param array  $args   Positional arguments.
     * @param array  $kwargs Keyword arguments.
     * @return mixed Result from Odoo.
     *
     * @throws OdooException
     */
    public function execute_kw(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        $uid = $this->getUid();

        if ($uid === null) {
            throw new OdooException(
                message: 'Not authenticated. Call authenticate() first or provide a valid UID.',
                code: 403,
                odooErrorType: 'not_authenticated',
            );
        }

        $params = [
            'service' => 'object',
            'method'  => 'execute_kw',
            'args'    => [
                $this->db,
                $uid,
                $this->apiKey,
                $model,
                $method,
                $args,
                (object) $kwargs, // Cast to object so empty arrays become {} in JSON.
            ],
        ];

        return $this->jsonRpc('/jsonrpc', $params);
    }

    /**
     * search_read shortcut.
     *
     * @param string $model   Odoo model name.
     * @param array  $domain  Odoo domain filter (list of tuples).
     * @param array  $fields  Field names to return.
     * @param array  $options Additional kwargs: limit, offset, order.
     * @return array List of record dictionaries.
     *
     * @throws OdooException
     */
    public function searchRead(
        string $model,
        array $domain = [],
        array $fields = [],
        array $options = []
    ): array {
        $kwargs = [];

        if (!empty($fields)) {
            $kwargs['fields'] = $fields;
        }
        if (isset($options['limit'])) {
            $kwargs['limit'] = (int) $options['limit'];
        }
        if (isset($options['offset'])) {
            $kwargs['offset'] = (int) $options['offset'];
        }
        if (isset($options['order'])) {
            $kwargs['order'] = $options['order'];
        }

        return $this->execute_kw($model, 'search_read', [$domain], $kwargs);
    }

    /**
     * Create a new record.
     *
     * @param string $model  Odoo model name.
     * @param array  $values Field values for the new record.
     * @return int The ID of the created record.
     *
     * @throws OdooException
     */
    public function create(string $model, array $values): int
    {
        $result = $this->execute_kw($model, 'create', [$values]);

        return (int) $result;
    }

    /**
     * Update existing records.
     *
     * @param string    $model  Odoo model name.
     * @param array|int $ids    Record ID(s) to update.
     * @param array     $values Field values to set.
     * @return bool True on success.
     *
     * @throws OdooException
     */
    public function write(string $model, array|int $ids, array $values): bool
    {
        $ids = is_int($ids) ? [$ids] : $ids;

        $result = $this->execute_kw($model, 'write', [$ids, $values]);

        return (bool) $result;
    }

    /**
     * Delete records.
     *
     * @param string    $model Odoo model name.
     * @param array|int $ids   Record ID(s) to delete.
     * @return bool True on success.
     *
     * @throws OdooException
     */
    public function unlink(string $model, array|int $ids): bool
    {
        $ids = is_int($ids) ? [$ids] : $ids;

        $result = $this->execute_kw($model, 'unlink', [$ids]);

        return (bool) $result;
    }

    /**
     * Call an arbitrary method on a model.
     *
     * Semantically identical to execute_kw but intended for custom / action
     * methods (e.g. attendance_manual, action_approve).
     *
     * @param string $model  Odoo model name.
     * @param string $method Method name.
     * @param array  $args   Positional arguments.
     * @param array  $kwargs Keyword arguments.
     * @return mixed
     *
     * @throws OdooException
     */
    public function callMethod(string $model, string $method, array $args = [], array $kwargs = []): mixed
    {
        return $this->execute_kw($model, $method, $args, $kwargs);
    }

    // ─────────────────────────────────────────────────────────────
    //  Accessors
    // ─────────────────────────────────────────────────────────────

    /**
     * Get the authenticated Odoo user ID.
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    /**
     * Get the current session ID.
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    // ─────────────────────────────────────────────────────────────
    //  Internal transport
    // ─────────────────────────────────────────────────────────────

    /**
     * Send a JSON-RPC 2.0 request to Odoo.
     *
     * @param string $endpoint Relative URL path (e.g. /jsonrpc).
     * @param array  $params   Params payload for the JSON-RPC request.
     * @return mixed The "result" field from the JSON-RPC response.
     *
     * @throws OdooException On JSON-RPC error or transport failure.
     */
    protected function jsonRpc(string $endpoint, array $params): mixed
    {
        $this->requestId++;

        $payload = [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'id'      => $this->requestId,
            'params'  => $params,
        ];

        Log::debug('Odoo JSON-RPC request.', [
            'endpoint'   => $endpoint,
            'request_id' => $this->requestId,
            'model'      => $params['args'][3] ?? ($params['model'] ?? null),
            'method'     => $params['args'][4] ?? ($params['method'] ?? null),
        ]);

        try {
            $httpResponse = $this->client->post($endpoint, [
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            Log::error('Odoo transport error.', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            throw OdooException::fromTransportError($e);
        }

        $body = (string) $httpResponse->getBody();
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Odoo response JSON parse failure.', [
                'endpoint' => $endpoint,
                'body'     => mb_substr($body, 0, 500),
            ]);

            throw new OdooException(
                message: 'Failed to parse Odoo JSON-RPC response: ' . json_last_error_msg(),
                code: 500,
                errorData: ['raw_body' => mb_substr($body, 0, 2000)],
                odooErrorType: 'json_parse_error',
            );
        }

        // JSON-RPC level error.
        if (isset($decoded['error'])) {
            $exception = OdooException::fromResponse($decoded);

            Log::warning('Odoo JSON-RPC error.', [
                'endpoint'   => $endpoint,
                'error_type' => $exception->getOdooErrorType(),
                'message'    => $exception->getMessage(),
            ]);

            throw $exception;
        }

        // Some endpoints (e.g. /web/session/authenticate) return result directly.
        if (!array_key_exists('result', $decoded)) {
            Log::error('Odoo response missing "result" key.', [
                'endpoint' => $endpoint,
                'keys'     => array_keys($decoded),
            ]);

            throw new OdooException(
                message: 'Unexpected Odoo response: missing "result" key.',
                code: 500,
                errorData: ['response_keys' => array_keys($decoded)],
                odooErrorType: 'unexpected_response',
            );
        }

        return $decoded['result'];
    }

    /**
     * Extract the session_id from the cookie jar or the response body.
     *
     * @param array $response The decoded /web/session/authenticate result.
     * @return string|null
     */
    protected function extractSessionId(array $response): ?string
    {
        // 1. Try to read from the response body (Odoo sometimes returns it).
        if (!empty($response['session_id'])) {
            return (string) $response['session_id'];
        }

        // 2. Fallback: read from the cookie jar.
        $parsedUrl = parse_url($this->url);
        $domain    = $parsedUrl['host'] ?? 'localhost';

        /** @var SetCookie $cookie */
        foreach ($this->cookieJar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'session_id') {
                return $cookie['Value'] ?? null;
            }
        }

        return null;
    }
}
