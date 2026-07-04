<?php

namespace App\Services\Odoo;

use App\Exceptions\OdooException;

class HRFeaturesService
{
    protected OdooService $odoo;

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    // ---------------------------------------------------------
    // Announcements
    // ---------------------------------------------------------
    public function getAnnouncements(): array
    {
        return $this->odoo->searchRead('hr.announcement', [['active', '=', true]], ['id', 'title', 'content', 'date', 'department_id'], ['order' => 'date desc, id desc']);
    }

    public function createAnnouncement(array $data): int
    {
        return $this->odoo->create('hr.announcement', $data);
    }

    public function deleteAnnouncement(int $id): bool
    {
        return $this->odoo->unlink('hr.announcement', [$id]);
    }

    // ---------------------------------------------------------
    // Compensations (Reimbursements)
    // ---------------------------------------------------------
    public function getCompensations(int $employeeId = null): array
    {
        $domain = [];
        if ($employeeId) {
            $domain[] = ['employee_id', '=', $employeeId];
        }
        return $this->odoo->searchRead('hr.compensation', $domain, ['id', 'employee_id', 'date', 'amount', 'description', 'state'], ['order' => 'date desc, id desc']);
    }

    public function requestCompensation(int $employeeId, float $amount, string $description): int
    {
        return $this->odoo->create('hr.compensation', [
            'employee_id' => $employeeId,
            'amount' => $amount,
            'description' => $description,
            'state' => 'submitted'
        ]);
    }

    public function updateCompensationState(int $id, string $state): bool
    {
        return $this->odoo->write('hr.compensation', [$id], ['state' => $state]);
    }

    // ---------------------------------------------------------
    // Payroll Adjustments (Adhoc)
    // ---------------------------------------------------------
    public function getAdjustments(): array
    {
        return $this->odoo->searchRead('hr.payroll.adjustment', [], ['id', 'employee_id', 'date', 'adjustment_type', 'amount', 'description', 'state'], ['order' => 'date desc, id desc']);
    }

    public function createAdjustment(array $data): int
    {
        return $this->odoo->create('hr.payroll.adjustment', $data);
    }

    public function updateAdjustmentState(int $id, string $state): bool
    {
        return $this->odoo->write('hr.payroll.adjustment', [$id], ['state' => $state]);
    }

    // ---------------------------------------------------------
    // Performance Reviews (KPI)
    // ---------------------------------------------------------
    public function getPerformanceReviews(int $employeeId = null): array
    {
        $domain = [];
        if ($employeeId) {
            $domain[] = ['employee_id', '=', $employeeId];
        }
        return $this->odoo->searchRead('hr.performance.review', $domain, ['id', 'employee_id', 'date', 'kpi_score', 'feedback', 'state'], ['order' => 'date desc, id desc']);
    }

    public function createPerformanceReview(array $data): int
    {
        $data['state'] = 'submitted';
        return $this->odoo->create('hr.performance.review', $data);
    }
}
