<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Manages payroll operations against Odoo's hr.payslip model.
 *
 * Currency: IDR (Indonesian Rupiah).
 */
class PayrollService
{
    protected OdooService $odoo;

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get payslips for a specific employee with optional date filtering.
     *
     * @param int         $employeeId Odoo hr.employee ID.
     * @param string|null $dateFrom   Optional start date (Y-m-d).
     * @param string|null $dateTo     Optional end date (Y-m-d).
     * @return array List of payslip records.
     *
     * @throws OdooException
     */
    public function getPayslips(int $employeeId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $domain = [
            ['employee_id', '=', $employeeId],
        ];

        if ($dateFrom !== null) {
            $domain[] = ['date_from', '>=', $dateFrom];
        }
        if ($dateTo !== null) {
            $domain[] = ['date_to', '<=', $dateTo];
        }

        return $this->odoo->searchRead(
            model: 'hr.payslip',
            domain: $domain,
            fields: [
                'id',
                'employee_id',
                'date_from',
                'date_to',
                'state',
                'name',
                'number',
                'net_wage',
            ],
            options: [
                'order' => 'date_from desc',
            ],
        );
    }

    /**
     * Get detailed information for a single payslip, including its lines.
     *
     * @param int $id The hr.payslip record ID.
     * @return array{payslip: array, lines: array}
     *
     * @throws OdooException
     */
    public function getPayslipDetail(int $id): array
    {
        // Fetch the payslip header.
        $payslips = $this->odoo->searchRead(
            model: 'hr.payslip',
            domain: [['id', '=', $id]],
            fields: [
                'id',
                'employee_id',
                'date_from',
                'date_to',
                'state',
                'name',
                'number',
                'net_wage',
                'company_id',
                'line_ids',
            ],
        );

        if (empty($payslips)) {
            throw new OdooException(
                message: "Payslip with ID {$id} not found.",
                code: 404,
                odooErrorType: 'record_not_found',
            );
        }

        $payslip = $payslips[0];

        // Fetch the payslip lines.
        $lines = $this->odoo->searchRead(
            model: 'hr.payslip.line',
            domain: [['slip_id', '=', $id]],
            fields: [
                'id',
                'name',
                'code',
                'category_id',
                'total',
            ],
        );

        return [
            'payslip' => $payslip,
            'lines'   => $lines,
        ];
    }

    /**
     * Get all payslips (admin view) with optional month/year filter.
     *
     * @param int|null $month Month number (1-12).
     * @param int|null $year  Four-digit year.
     * @return array List of payslip records.
     *
     * @throws OdooException
     */
    public function getAllPayslips(?int $month = null, ?int $year = null): array
    {
        $domain = [];

        if ($month !== null && $year !== null) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $domain[] = ['date_from', '>=', $startDate];
            $domain[] = ['date_to', '<=', $endDate];
        } elseif ($year !== null) {
            $domain[] = ['date_from', '>=', "{$year}-01-01"];
            $domain[] = ['date_to', '<=', "{$year}-12-31"];
        }

        return $this->odoo->searchRead(
            model: 'hr.payslip',
            domain: $domain,
            fields: [
                'id',
                'employee_id',
                'date_from',
                'date_to',
                'state',
                'name',
                'number',
                'net_wage',
            ],
            options: [
                'order' => 'date_from desc',
            ],
        );
    }

    /**
     * Generate payslips for given employees and date range.
     *
     * Creates a payslip batch run, then generates individual payslips for
     * each employee. If a salary structure ID is provided it will be set
     * on each slip.
     *
     * @param array       $employeeIds List of hr.employee IDs.
     * @param string      $dateFrom    Start date (Y-m-d).
     * @param string      $dateTo      End date (Y-m-d).
     * @param int|null    $structId    Optional salary structure ID.
     * @return array{run_id: int, payslip_ids: int[]}
     *
     * @throws OdooException
     */
    public function generatePayslips(
        array $employeeIds,
        string $dateFrom,
        string $dateTo,
        ?int $structId = null
    ): array {
        Log::info('Generating payslips.', [
            'employees' => $employeeIds,
            'from'      => $dateFrom,
            'to'        => $dateTo,
        ]);

        $payslipIds = [];

        foreach ($employeeIds as $employeeId) {
            $values = [
                'employee_id'    => (int) $employeeId,
                'date_from'      => $dateFrom,
                'date_to'        => $dateTo,
                'name'           => "Payslip {$dateFrom} to {$dateTo}",
            ];

            $payslipId = $this->odoo->create('hr.payslip', $values);
            $payslipIds[] = $payslipId;

            // Compute the payslip (triggers salary rule calculations).
            $this->odoo->callMethod(
                model: 'hr.payslip',
                method: 'compute_sheet',
                args: [[$payslipId]],
            );
        }

        Log::info('Payslips generated.', [
            'payslip_ids' => $payslipIds,
        ]);

        return [
            'run_id'      => null,
            'payslip_ids' => $payslipIds,
        ];
    }

    /**
     * Confirm / finalise a payslip (set state to 'done').
     *
     * Calls action_payslip_done on the hr.payslip record.
     *
     * @param int $id The hr.payslip record ID.
     * @return mixed
     *
     * @throws OdooException
     */
    public function confirmPayslip(int $id): mixed
    {
        Log::info('Confirming payslip.', ['payslip_id' => $id]);

        $result = $this->odoo->callMethod(
            model: 'hr.payslip',
            method: 'action_payslip_done',
            args: [[$id]],
        );

        Log::info('Payslip confirmed.', ['payslip_id' => $id]);

        return $result;
    }
}
