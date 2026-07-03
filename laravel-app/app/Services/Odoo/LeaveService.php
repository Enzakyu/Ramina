<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;
use Illuminate\Support\Facades\Log;

/**
 * Manages leave / time-off operations against Odoo.
 *
 * Uses the hr.leave and hr.leave.type models.
 * Leave states in Odoo: draft → confirm → validate1 → validate (or refuse).
 */
class LeaveService
{
    protected OdooService $odoo;

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Get all available leave types.
     *
     * @return array List of leave-type records.
     *
     * @throws OdooException
     */
    public function getLeaveTypes(): array
    {
        return $this->odoo->searchRead(
            model: 'hr.leave.type',
            domain: [],
            fields: [
                'id',
                'name',
                'max_leaves',
                'leaves_taken',
                'virtual_remaining_leaves',
                'requires_allocation',
            ],
            options: [
                'order' => 'name asc',
            ],
        );
    }

    /**
     * Submit a new leave request.
     *
     * @param int    $employeeId  Odoo hr.employee ID.
     * @param int    $leaveTypeId Odoo hr.leave.type ID.
     * @param string $dateFrom    Start datetime (Y-m-d H:i:s or Y-m-d).
     * @param string $dateTo      End datetime (Y-m-d H:i:s or Y-m-d).
     * @param string $description Reason / note for the leave.
     * @return int The ID of the created hr.leave record.
     *
     * @throws OdooException
     */
    public function requestLeave(
        int $employeeId,
        int $leaveTypeId,
        string $dateFrom,
        string $dateTo,
        string $description = ''
    ): int {
        // Normalise to full datetime if only a date was provided.
        $dateFrom = $this->normaliseDateStart($dateFrom);
        $dateTo   = $this->normaliseDateEnd($dateTo);

        $values = [
            'employee_id'       => $employeeId,
            'holiday_status_id' => $leaveTypeId,
            'date_from'         => $dateFrom,
            'date_to'           => $dateTo,
            'name'              => $description,
            'request_date_from' => explode(' ', $dateFrom)[0],
            'request_date_to'   => explode(' ', $dateTo)[0],
        ];

        $id = $this->odoo->create('hr.leave', $values);

        Log::info('Leave request created.', [
            'leave_id'    => $id,
            'employee_id' => $employeeId,
            'type_id'     => $leaveTypeId,
            'from'        => $dateFrom,
            'to'          => $dateTo,
        ]);

        return $id;
    }

    /**
     * Get leave requests for a specific employee.
     *
     * @param int         $employeeId Odoo hr.employee ID.
     * @param string|null $state      Optional state filter (draft, confirm, validate1, validate, refuse, cancel).
     * @return array List of leave records.
     *
     * @throws OdooException
     */
    public function getLeaveRequests(int $employeeId, ?string $state = null): array
    {
        $domain = [
            ['employee_id', '=', $employeeId],
        ];

        if ($state !== null) {
            $domain[] = ['state', '=', $state];
        }

        return $this->odoo->searchRead(
            model: 'hr.leave',
            domain: $domain,
            fields: [
                'id',
                'employee_id',
                'holiday_status_id',
                'date_from',
                'date_to',
                'number_of_days',
                'state',
                'name',
            ],
            options: [
                'order' => 'date_from desc',
            ],
        );
    }

    /**
     * Get all pending leave requests awaiting first-level confirmation.
     *
     * State 'confirm' means the employee has submitted and it awaits
     * manager / HR approval.
     *
     * @return array List of pending leave records.
     *
     * @throws OdooException
     */
    public function getPendingLeaves(): array
    {
        return $this->odoo->searchRead(
            model: 'hr.leave',
            domain: [
                ['state', '=', 'confirm'],
            ],
            fields: [
                'id',
                'employee_id',
                'holiday_status_id',
                'date_from',
                'date_to',
                'number_of_days',
                'state',
                'name',
            ],
            options: [
                'order' => 'date_from asc',
            ],
        );
    }

    /**
     * Approve a leave request.
     *
     * Calls the action_approve method on hr.leave which transitions the
     * state from 'confirm' → 'validate1' or 'validate' depending on the
     * leave type's approval configuration.
     *
     * @param int $id The hr.leave record ID.
     * @return mixed
     *
     * @throws OdooException
     */
    public function approveLeave(int $id): mixed
    {
        Log::info('Approving leave request.', ['leave_id' => $id]);

        $result = $this->odoo->callMethod(
            model: 'hr.leave',
            method: 'action_approve',
            args: [[$id]],
        );

        Log::info('Leave request approved.', ['leave_id' => $id]);

        return $result;
    }

    /**
     * Reject / refuse a leave request.
     *
     * Calls action_refuse on the record and then writes the refusal reason.
     *
     * @param int    $id     The hr.leave record ID.
     * @param string $reason Human-readable reason for refusal.
     * @return mixed
     *
     * @throws OdooException
     */
    public function rejectLeave(int $id, string $reason = ''): mixed
    {
        Log::info('Rejecting leave request.', ['leave_id' => $id, 'reason' => $reason]);

        $result = $this->odoo->callMethod(
            model: 'hr.leave',
            method: 'action_refuse',
            args: [[$id]],
        );

        // Write the refusal reason if provided.
        if ($reason !== '') {
            $this->odoo->write('hr.leave', [$id], [
                'report_note' => $reason,
            ]);
        }

        Log::info('Leave request rejected.', ['leave_id' => $id]);

        return $result;
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Normalise a date string to a start-of-day datetime if only a date.
     */
    protected function normaliseDateStart(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 01:00:00'; // 09:00 WIB = 01:00 UTC (company hours start)
        }

        return $value;
    }

    /**
     * Normalise a date string to an end-of-day datetime if only a date.
     */
    protected function normaliseDateEnd(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 09:00:00'; // 17:00 WIB = 09:00 UTC (company hours end)
        }

        return $value;
    }
}
