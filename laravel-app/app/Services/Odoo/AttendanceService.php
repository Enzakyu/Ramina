<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Handles all HR attendance operations against Odoo.
 *
 * Uses the hr.attendance model for history/status and the hr.employee
 * model's `attendance_manual` method for clock-in / clock-out toggling.
 */
class AttendanceService
{
    protected OdooService $odoo;

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get the current attendance status for an employee.
     *
     * Fetches the most recent hr.attendance record. If its check_out is
     * empty the employee is currently checked in.
     *
     * @param int $employeeId Odoo hr.employee ID.
     * @return array{checked_in: bool, last_check_in: string|null, last_check_out: string|null, attendance_id: int|null}
     *
     * @throws OdooException
     */
    public function getAttendanceStatus(int $employeeId): array
    {
        $records = $this->odoo->searchRead(
            model: 'hr.attendance',
            domain: [
                ['employee_id', '=', $employeeId],
            ],
            fields: ['id', 'employee_id', 'check_in', 'check_out', 'worked_hours'],
            options: [
                'order' => 'check_in desc',
                'limit' => 1,
            ],
        );

        if (empty($records)) {
            return [
                'checked_in'     => false,
                'last_check_in'  => null,
                'last_check_out' => null,
                'attendance_id'  => null,
            ];
        }

        $latest = $records[0];
        $checkedIn = empty($latest['check_out']) || $latest['check_out'] === false;

        return [
            'checked_in'     => $checkedIn,
            'last_check_in'  => $latest['check_in'] ?? null,
            'last_check_out' => ($latest['check_out'] !== false) ? ($latest['check_out'] ?? null) : null,
            'attendance_id'  => $latest['id'] ?? null,
        ];
    }

    /**
     * Toggle attendance (check-in or check-out) using Odoo's built-in method.
     *
     * Calls hr.employee → attendance_manual which creates or closes an
     * hr.attendance record automatically.
     *
     * @param int $employeeId Odoo hr.employee ID.
     * @return mixed Odoo's response from attendance_manual (typically the
     *               action dict or attendance record data).
     *
     * @throws OdooException
     */
    public function toggleAttendance(int $employeeId): mixed
    {
        Log::info('Toggling attendance.', ['employee_id' => $employeeId]);

        $result = $this->odoo->callMethod(
            model: 'hr.employee',
            method: 'attendance_manual',
            args: [[$employeeId], false],
        );

        Log::info('Attendance toggled.', [
            'employee_id' => $employeeId,
            'result'      => is_array($result) ? array_keys($result) : $result,
        ]);

        return $result;
    }

    /**
     * Get attendance history for an employee within a date range.
     *
     * @param int    $employeeId Odoo hr.employee ID.
     * @param string $from       Start datetime (Y-m-d H:i:s or Y-m-d).
     * @param string $to         End datetime (Y-m-d H:i:s or Y-m-d).
     * @return array List of attendance records.
     *
     * @throws OdooException
     */
    public function getAttendanceHistory(int $employeeId, string $from, string $to): array
    {
        // Normalise dates to full datetime strings if only date was given.
        $dateFrom = $this->normaliseDateTime($from, '00:00:00');
        $dateTo   = $this->normaliseDateTime($to, '23:59:59');

        $domain = [
            ['employee_id', '=', $employeeId],
            ['check_in', '>=', $dateFrom],
            ['check_in', '<=', $dateTo],
        ];

        return $this->odoo->searchRead(
            model: 'hr.attendance',
            domain: $domain,
            fields: ['id', 'employee_id', 'check_in', 'check_out', 'worked_hours'],
            options: [
                'order' => 'check_in desc',
            ],
        );
    }

    /**
     * Get all attendance records for today (useful for admin dashboard).
     *
     * Returns every attendance record whose check_in is on or after
     * today at 00:00:00 UTC.
     *
     * @return array List of attendance records with employee name.
     *
     * @throws OdooException
     */
    public function getAllAttendanceToday(): array
    {
        $todayStart = Carbon::today('UTC')->format('Y-m-d H:i:s');

        $domain = [
            ['check_in', '>=', $todayStart],
        ];

        return $this->odoo->searchRead(
            model: 'hr.attendance',
            domain: $domain,
            fields: ['id', 'employee_id', 'check_in', 'check_out', 'worked_hours'],
            options: [
                'order' => 'check_in desc',
            ],
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Ensure a date/datetime string is a full datetime.
     *
     * @param string $value       Input date(time) string.
     * @param string $defaultTime Time part to append if only a date is supplied.
     * @return string Full datetime string Y-m-d H:i:s.
     */
    protected function normaliseDateTime(string $value, string $defaultTime): string
    {
        $value = trim($value);

        // Already has a time component.
        if (preg_match('/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/', $value)) {
            return $value;
        }

        return $value . ' ' . $defaultTime;
    }
}
