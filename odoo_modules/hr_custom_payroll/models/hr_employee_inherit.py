from odoo import models, fields, api
from datetime import date


class HrEmployeeInherit(models.Model):
    _inherit = 'hr.employee'

    current_month_overtime = fields.Float(
        string='Current Month Overtime (Hours)',
        compute='_compute_overtime_stats',
        help='Total overtime hours for the current month',
    )
    total_overtime_unpaid = fields.Float(
        string='Total Unpaid Overtime (Hours)',
        compute='_compute_overtime_stats',
        help='Total overtime hours in draft or confirmed status (not yet paid)',
    )

    @api.depends_context('uid')
    def _compute_overtime_stats(self):
        """
        Compute overtime statistics for each employee:
        - current_month_overtime: total overtime hours this calendar month
        - total_overtime_unpaid: total overtime hours not yet paid (draft + confirmed)
        """
        OvertimeBonus = self.env['hr.overtime.bonus']
        today = date.today()
        first_of_month = today.replace(day=1)

        for employee in self:
            # Current month overtime hours
            current_month_records = OvertimeBonus.search([
                ('employee_id', '=', employee.id),
                ('date', '>=', first_of_month),
                ('date', '<=', today),
            ])
            employee.current_month_overtime = sum(
                current_month_records.mapped('overtime_hours')
            )

            # Total unpaid overtime hours (draft + confirmed)
            unpaid_records = OvertimeBonus.search([
                ('employee_id', '=', employee.id),
                ('state', 'in', ['draft', 'confirmed']),
            ])
            employee.total_overtime_unpaid = sum(
                unpaid_records.mapped('overtime_hours')
            )
