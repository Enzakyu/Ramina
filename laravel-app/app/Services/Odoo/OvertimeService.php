<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Manages overtime operations against Odoo's hr.attendance.overtime model.
 *
 * Work hours: 09:00 – 17:00 WIB (UTC+7).
 * Currency: IDR.
 */
class OvertimeService
{
    protected OdooService $odoo;

    /**
     * Default fields for hr.attendance.overtime records.
     */
    protected array $defaultFields = [
        'id',
        'employee_id',
        'date',
        'duration',
        'adjustment',
    ];

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get overtime records for an employee with optional date filtering.
     *
     * @param int         $employeeId Odoo hr.employee ID.
     * @param string|null $dateFrom   Optional start date (Y-m-d).
     * @param string|null $dateTo     Optional end date (Y-m-d).
     * @return array List of overtime records.
     *
     * @throws OdooException
     */
    public function getOvertimeRecords(int $employeeId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $domain = [
            ['employee_id', '=', $employeeId],
        ];

        if ($dateFrom !== null) {
            $domain[] = ['date', '>=', $dateFrom];
        }
        if ($dateTo !== null) {
            $domain[] = ['date', '<=', $dateTo];
        }

        return $this->odoo->searchRead(
            model: 'hr.attendance.overtime',
            domain: $domain,
            fields: $this->defaultFields,
            options: [
                'order' => 'date desc',
            ],
        );
    }

    /**
     * Get an overtime summary for an employee for the current month.
     *
     * Returns the total overtime hours as well as the individual records.
     *
     * @param int $employeeId Odoo hr.employee ID.
     * @return array{total_hours: float, records: array}
     *
     * @throws OdooException
     */
    public function getOvertimeSummary(int $employeeId): array
    {
        $now       = Carbon::now('Asia/Jakarta');
        $dateFrom  = $now->copy()->startOfMonth()->format('Y-m-d');
        $dateTo    = $now->copy()->endOfMonth()->format('Y-m-d');

        $records = $this->getOvertimeRecords($employeeId, $dateFrom, $dateTo);

        $totalHours = 0.0;
        foreach ($records as $record) {
            $duration = (float) ($record['duration'] ?? 0);
            $adjustment = (float) ($record['adjustment'] ?? 0);
            $totalHours += $duration + $adjustment;
        }

        return [
            'total_hours' => round($totalHours, 2),
            'month'       => $now->format('F'),
            'year'        => (int) $now->format('Y'),
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'records'     => $records,
        ];
    }

    /**
     * Get all overtime records pending validation (admin view).
     *
     * Searches for records that are not yet validated. The Odoo
     * hr.attendance.overtime model may use a boolean field or duration
     * checks. We look for records with adjustment = 0 as a proxy for
     * "not yet reviewed", or fall back to recent unconfirmed records.
     *
     * @return array List of pending overtime records.
     *
     * @throws OdooException
     */
    public function getAllPendingOvertime(): array
    {
        // Try fetching records that may need validation.
        // Odoo 19's hr.attendance.overtime uses duration > 0 with
        // adjustment = 0 indicating un-reviewed overtime.
        $records = $this->odoo->searchRead(
            model: 'hr.attendance.overtime',
            domain: [
                ['duration', '>', 0],
                ['adjustment', '=', 0],
            ],
            fields: $this->defaultFields,
            options: [
                'order' => 'date desc',
                'limit' => 200,
            ],
        );

        return $records;
    }

    /**
     * Confirm / validate an overtime record.
     *
     * Since hr.attendance.overtime may not have a dedicated workflow
     * action in all Odoo configurations, this method attempts to call
     * action_validate first. If that method does not exist, it falls
     * back to writing the adjustment field to match duration (marking
     * it as approved).
     *
     * @param int $id The hr.attendance.overtime record ID.
     * @return bool True on success.
     *
     * @throws OdooException
     */
    public function confirmOvertime(int $id): bool
    {
        Log::info('Confirming overtime record.', ['overtime_id' => $id]);

        try {
            // Attempt the standard workflow action if available.
            $this->odoo->callMethod(
                model: 'hr.attendance.overtime',
                method: 'action_validate',
                args: [[$id]],
            );

            Log::info('Overtime confirmed via action_validate.', ['overtime_id' => $id]);

            return true;
        } catch (OdooException $e) {
            // If the method doesn't exist, fall back to a manual write.
            if (
                str_contains($e->getMessage(), 'action_validate')
                || str_contains($e->getMessage(), 'AttributeError')
                || str_contains($e->getMessage(), 'not found')
            ) {
                Log::info('action_validate not available, falling back to manual approval.', [
                    'overtime_id' => $id,
                ]);

                return $this->confirmOvertimeManual($id);
            }

            throw $e;
        }
    }

    /**
     * Manually approve overtime by copying duration into adjustment.
     *
     * This marks the record as reviewed by aligning adjustment with the
     * actual computed overtime duration.
     *
     * @param int $id The hr.attendance.overtime record ID.
     * @return bool
     *
     * @throws OdooException
     */
    protected function confirmOvertimeManual(int $id): bool
    {
        // Read the current duration.
        $records = $this->odoo->searchRead(
            model: 'hr.attendance.overtime',
            domain: [['id', '=', $id]],
            fields: ['id', 'duration'],
        );

        if (empty($records)) {
            throw new OdooException(
                message: "Overtime record with ID {$id} not found.",
                code: 404,
                odooErrorType: 'record_not_found',
            );
        }

        $duration = (float) ($records[0]['duration'] ?? 0);

        $result = $this->odoo->write('hr.attendance.overtime', [$id], [
            'adjustment' => $duration,
        ]);

        Log::info('Overtime manually confirmed.', [
            'overtime_id' => $id,
            'duration'    => $duration,
        ]);

        return $result;
    }
}
