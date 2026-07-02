<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Odoo Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the Odoo ERP system via XML-RPC/JSON-RPC.
    | These values are used by the OdooService to authenticate and communicate
    | with the Odoo backend for HR data synchronization.
    |
    */

    'url' => env('ODOO_URL', 'http://localhost:8069'),

    'db' => env('ODOO_DB', 'ramina'),

    'username' => env('ODOO_USERNAME', 'admin'),

    'api_key' => env('ODOO_API_KEY', ''),

    'timeout' => env('ODOO_TIMEOUT', 30),

];
