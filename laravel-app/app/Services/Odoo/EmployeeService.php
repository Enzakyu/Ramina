<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use Illuminate\Support\Facades\Log;

/**
 * Manages employee CRUD operations against Odoo's hr.employee model.
 */
class EmployeeService
{
    protected OdooService $odoo;

    /**
     * Default fields returned when reading employee records.
     */
    protected array $defaultFields = [
        'id',
        'name',
        'job_id',
        'department_id',
        'work_email',
        'work_phone',
        'employee_type',
        'parent_id',
        'coach_id',
        'company_id',
        'identification_id',
        'barcode',
        'pin',
        'user_id',
        'image_128',
        'basic_salary',
    ];

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get a single employee record by ID.
     *
     * @param int        $id     Odoo hr.employee record ID.
     * @param array|null $fields Override default field list.
     * @return array Employee record data.
     *
     * @throws OdooException If the employee is not found.
     */
    public function getEmployee(int $id, ?array $fields = null): array
    {
        $records = $this->odoo->searchRead(
            model: 'hr.employee',
            domain: [['id', '=', $id]],
            fields: $fields ?? $this->defaultFields,
        );

        if (empty($records)) {
            throw new OdooException(
                message: "Employee with ID {$id} not found.",
                code: 404,
                odooErrorType: 'record_not_found',
            );
        }

        return $records[0];
    }

    /**
     * Find the employee record linked to an Odoo res.users ID.
     *
     * Typically used after authentication to map the logged-in user
     * to their employee profile.
     *
     * @param int $uid Odoo res.users ID.
     * @return array|null Employee record or null if none linked.
     *
     * @throws OdooException
     */
    public function getEmployeeByUserId(int $uid): ?array
    {
        $records = $this->odoo->searchRead(
            model: 'hr.employee',
            domain: [['user_id', '=', $uid]],
            fields: $this->defaultFields,
            options: [
                'limit' => 1,
            ],
        );

        return $records[0] ?? null;
    }

    /**
     * Get a paginated list of employees.
     *
     * @param array $domain Additional domain filters.
     * @param int   $limit  Maximum records to return.
     * @param int   $offset Pagination offset.
     * @return array List of employee records.
     *
     * @throws OdooException
     */
    public function getAllEmployees(array $domain = [], int $limit = 80, int $offset = 0): array
    {
        return $this->odoo->searchRead(
            model: 'hr.employee',
            domain: $domain,
            fields: $this->defaultFields,
            options: [
                'limit'  => $limit,
                'offset' => $offset,
                'order'  => 'name asc',
            ],
        );
    }

    /**
     * Get all departments.
     */
    public function getDepartments(): array
    {
        return $this->odoo->searchRead('hr.department', [], ['id', 'name']);
    }

    /**
     * Get all job positions.
     */
    public function getJobs(): array
    {
        return $this->odoo->searchRead('hr.job', [], ['id', 'name']);
    }

    /**
     * Create a new employee record.
     *
     * @param array $data Field values for the new employee. Example keys:
     *                    name, job_id, department_id, work_email, work_phone,
     *                    employee_type, parent_id, identification_id, password, etc.
     * @return int The ID of the created hr.employee record.
     *
     * @throws OdooException
     */
    public function createEmployee(array $data): int
    {
        // Ensure mandatory field.
        if (empty($data['name'])) {
            throw new OdooException(
                message: 'Employee name is required.',
                code: 422,
                odooErrorType: 'validation_error',
            );
        }

        // Auto-create Odoo user if email is provided
        if (!empty($data['work_email'])) {
            try {
                // Check if user already exists
                $existingUsers = $this->odoo->searchRead('res.users', [['login', '=', $data['work_email']]], ['id']);
                
                if (!empty($existingUsers)) {
                    $data['user_id'] = $existingUsers[0]['id'];
                } else {
                    $password = $data['password'] ?? 'password123';
                    // Create new user
                    $userId = $this->odoo->create('res.users', [
                        'name' => $data['name'],
                        'login' => $data['work_email'],
                        'password' => $password,
                    ]);
                    $data['user_id'] = $userId;
                }
            } catch (\Exception $e) {
                Log::error('Failed to auto-create res.users for employee.', ['error' => $e->getMessage()]);
                // Proceed to create employee without linking if user creation fails
            }
        }

        unset($data['password']);

        // Odoo requires 'false' to unset many2one fields, not 'null'
        foreach (['job_id', 'department_id', 'parent_id', 'coach_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = false;
            }
        }

        $id = $this->odoo->create('hr.employee', $data);

        Log::info('Employee created.', ['employee_id' => $id, 'name' => $data['name']]);

        return $id;
    }

    /**
     * Update an existing employee record.
     *
     * @param int   $id   Odoo hr.employee record ID.
     * @param array $data Field values to update.
     * @return bool True on success.
     *
     * @throws OdooException
     */
    public function updateEmployee(int $id, array $data): bool
    {
        if (empty($data)) {
            return true; // Nothing to update.
        }

        // Odoo requires 'false' to unset many2one fields, not 'null'
        foreach (['job_id', 'department_id', 'parent_id', 'coach_id'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = false;
            }
        }

        $result = $this->odoo->write('hr.employee', [$id], $data);

        Log::info('Employee updated.', [
            'employee_id' => $id,
            'fields'      => array_keys($data),
        ]);

        return $result;
    }
}
