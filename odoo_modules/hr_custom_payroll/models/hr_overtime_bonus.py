from odoo import models, fields, api, _
from odoo.exceptions import ValidationError
from datetime import datetime, timedelta, time
import logging

_logger = logging.getLogger(__name__)


class HrOvertimeBonus(models.Model):
    _name = 'hr.overtime.bonus'
    _description = 'Employee Overtime Bonus'
    _order = 'date desc, employee_id'
    _rec_name = 'employee_id'

    employee_id = fields.Many2one(
        'hr.employee',
        string='Employee',
        required=True,
        ondelete='cascade',
        index=True,
    )
    date = fields.Date(
        string='Date',
        required=True,
        default=fields.Date.context_today,
        index=True,
    )
    check_in = fields.Datetime(
        string='Check In',
    )
    check_out = fields.Datetime(
        string='Check Out',
    )
    worked_hours = fields.Float(
        string='Worked Hours',
        digits=(16, 2),
    )
    standard_hours = fields.Float(
        string='Standard Hours',
        default=8.0,
        help='Standard working hours per day (default: 8 hours for 9 AM - 5 PM schedule)',
    )
    overtime_hours = fields.Float(
        string='Overtime Hours',
        compute='_compute_overtime_hours',
        store=True,
        digits=(16, 2),
        help='Hours worked beyond the standard working hours',
    )
    overtime_rate = fields.Float(
        string='Overtime Rate Multiplier',
        default=1.5,
        help='Multiplier applied to hourly wage for overtime pay (default: 1.5x)',
    )
    state = fields.Selection(
        selection=[
            ('draft', 'Draft'),
            ('confirmed', 'Confirmed'),
            ('paid', 'Paid'),
        ],
        string='Status',
        default='draft',
        required=True,
        tracking=True,
        index=True,
    )
    attendance_id = fields.Many2one(
        'hr.attendance',
        string='Attendance Record',
        ondelete='set null',
        help='Linked attendance record that generated this overtime',
    )
    payslip_id = fields.Many2one(
        'hr.payslip',
        string='Payslip',
        ondelete='set null',
        help='Payslip that includes this overtime bonus',
    )
    company_id = fields.Many2one(
        'res.company',
        string='Company',
        related='employee_id.company_id',
        store=True,
        readonly=True,
    )
    currency_id = fields.Many2one(
        'res.currency',
        string='Currency',
        related='employee_id.company_id.currency_id',
        store=True,
        readonly=True,
    )
    bonus_amount = fields.Monetary(
        string='Bonus Amount',
        compute='_compute_bonus_amount',
        store=True,
        currency_field='currency_id',
        help='Calculated overtime bonus: overtime_hours × overtime_rate × hourly_wage',
    )

    _sql_constraints = [
        (
            'unique_employee_date_attendance',
            'UNIQUE(employee_id, date, attendance_id)',
            'An overtime record already exists for this employee, date, and attendance!'
        ),
    ]

    # -------------------------------------------------------------------------
    # Compute Methods
    # -------------------------------------------------------------------------

    @api.depends('worked_hours', 'standard_hours')
    def _compute_overtime_hours(self):
        """Calculate overtime as hours worked beyond the standard hours."""
        for rec in self:
            worked = rec.worked_hours or 0.0
            standard = rec.standard_hours or 8.0
            rec.overtime_hours = max(0.0, worked - standard)

    @api.depends('overtime_hours', 'overtime_rate', 'employee_id', 'employee_id.contract_id',
                 'employee_id.contract_id.wage')
    def _compute_bonus_amount(self):
        """
        Compute bonus amount based on:
            bonus = overtime_hours × overtime_rate × hourly_wage
        Hourly wage is derived from the employee's current contract:
            hourly_wage = monthly_wage / 173 (average monthly working hours)
        """
        for rec in self:
            hourly_wage = 0.0
            contract = rec.employee_id.contract_id
            if contract and contract.wage:
                # 173 hours = average monthly working hours (52 weeks × 40 hrs / 12 months)
                hourly_wage = contract.wage / 173.0
            rec.bonus_amount = rec.overtime_hours * rec.overtime_rate * hourly_wage

    # -------------------------------------------------------------------------
    # Action Methods
    # -------------------------------------------------------------------------

    def action_confirm(self):
        """Confirm draft overtime records."""
        records_to_confirm = self.filtered(lambda r: r.state == 'draft')
        if not records_to_confirm:
            return
        records_to_confirm.write({'state': 'confirmed'})
        return True

    def action_set_draft(self):
        """Reset confirmed overtime records back to draft."""
        records_to_draft = self.filtered(lambda r: r.state == 'confirmed')
        if not records_to_draft:
            return
        records_to_draft.write({'state': 'draft'})
        return True

    # -------------------------------------------------------------------------
    # Cron Methods
    # -------------------------------------------------------------------------

    @api.model
    def _cron_compute_overtime(self):
        """
        Daily cron job: scans yesterday's attendance records and creates
        overtime bonus records for employees who:
        - Checked out after 17:00 (5 PM), OR
        - Worked more than 8 hours total

        Only creates records if overtime hours > 0 (worked_hours > standard 8.0).
        Skips if an overtime record already exists for the same employee + attendance.
        """
        yesterday = fields.Date.context_today(self) - timedelta(days=1)
        day_start = datetime.combine(yesterday, time(0, 0, 0))
        day_end = datetime.combine(yesterday + timedelta(days=1), time(0, 0, 0))

        _logger.info(
            'Overtime Cron: Scanning attendance records for %s', yesterday
        )

        # Fetch all completed attendance records for yesterday
        attendances = self.env['hr.attendance'].search([
            ('check_out', '!=', False),
            ('check_out', '>=', day_start),
            ('check_out', '<', day_end),
        ])

        created_count = 0
        for att in attendances:
            # Skip if overtime record already exists for this attendance
            existing = self.search([
                ('employee_id', '=', att.employee_id.id),
                ('attendance_id', '=', att.id),
            ], limit=1)
            if existing:
                continue

            # Determine if this qualifies as overtime
            worked = att.worked_hours or 0.0
            if worked <= 8.0:
                # Also check if check_out time is past 17:00 in employee's timezone
                check_out_local = fields.Datetime.context_timestamp(
                    att.employee_id, att.check_out
                )
                if check_out_local.hour < 17:
                    continue
                # Even if checked out after 17:00, only create if actual overtime exists
                if worked <= 8.0:
                    continue

            # Create overtime bonus record
            self.create({
                'employee_id': att.employee_id.id,
                'date': yesterday,
                'check_in': att.check_in,
                'check_out': att.check_out,
                'worked_hours': worked,
                'standard_hours': 8.0,
                'attendance_id': att.id,
                'state': 'draft',
            })
            created_count += 1

        _logger.info(
            'Overtime Cron: Created %d overtime record(s) for %s',
            created_count, yesterday
        )
        return True
