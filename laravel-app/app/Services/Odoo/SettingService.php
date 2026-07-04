<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;

class SettingService
{
    protected OdooService $odoo;

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get a configuration parameter from Odoo.
     */
    public function getParam(string $key, $default = null)
    {
        $records = $this->odoo->searchRead(
            model: 'ir.config_parameter',
            domain: [['key', '=', $key]],
            fields: ['value'],
        );

        return $records[0]['value'] ?? $default;
    }

    /**
     * Set a configuration parameter in Odoo.
     */
    public function setParam(string $key, $value): void
    {
        $records = $this->odoo->searchRead(
            model: 'ir.config_parameter',
            domain: [['key', '=', $key]],
            fields: ['id'],
        );

        if (empty($records)) {
            $this->odoo->create('ir.config_parameter', [
                'key' => $key,
                'value' => (string) $value,
            ]);
        } else {
            $this->odoo->write('ir.config_parameter', [$records[0]['id']], [
                'value' => (string) $value,
            ]);
        }
    }

    /**
     * Get all payroll settings.
     */
    public function getPayrollSettings(): array
    {
        return [
            'standard_hours' => $this->getParam('ramina.standard_hours', '8.0'),
            'overtime_rate'  => $this->getParam('ramina.overtime_rate', '1.5'),
        ];
    }

    /**
     * Update all payroll settings.
     */
    public function updatePayrollSettings(array $data): void
    {
        if (isset($data['standard_hours'])) {
            $this->setParam('ramina.standard_hours', $data['standard_hours']);
        }
        if (isset($data['overtime_rate'])) {
            $this->setParam('ramina.overtime_rate', $data['overtime_rate']);
        }
    }
}
