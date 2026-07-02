from odoo import models, fields, api
import logging

_logger = logging.getLogger(__name__)


class HrPayslipInherit(models.Model):
    _inherit = 'hr.payslip'

    overtime_bonus_ids = fields.One2many(
        'hr.overtime.bonus',
        'payslip_id',
        string='Overtime Bonuses',
        help='Overtime bonus records linked to this payslip',
    )
    total_overtime_bonus = fields.Monetary(
        string='Total Overtime Bonus',
        compute='_compute_total_overtime_bonus',
        store=True,
        currency_field='currency_id',
        help='Sum of all overtime bonus amounts linked to this payslip',
    )
    overtime_bonus_count = fields.Integer(
        string='Overtime Entries',
        compute='_compute_total_overtime_bonus',
        store=True,
    )

    @api.depends('overtime_bonus_ids', 'overtime_bonus_ids.bonus_amount')
    def _compute_total_overtime_bonus(self):
        """Compute total overtime bonus from linked overtime records."""
        for payslip in self:
            bonuses = payslip.overtime_bonus_ids
            payslip.total_overtime_bonus = sum(bonuses.mapped('bonus_amount'))
            payslip.overtime_bonus_count = len(bonuses)

    def compute_sheet(self):
        """
        Override compute_sheet to link confirmed overtime bonuses
        for the payslip period before computing salary lines.
        """
        for payslip in self:
            if payslip.employee_id and payslip.date_from and payslip.date_to:
                # Find confirmed overtime bonuses for this employee in the payslip period
                overtime_bonuses = self.env['hr.overtime.bonus'].search([
                    ('employee_id', '=', payslip.employee_id.id),
                    ('date', '>=', payslip.date_from),
                    ('date', '<=', payslip.date_to),
                    ('state', '=', 'confirmed'),
                    ('payslip_id', '=', False),
                ])
                if overtime_bonuses:
                    overtime_bonuses.write({'payslip_id': payslip.id})
                    _logger.info(
                        'Payslip %s: Linked %d overtime bonus record(s) for %s',
                        payslip.number or payslip.id,
                        len(overtime_bonuses),
                        payslip.employee_id.name,
                    )
        return super().compute_sheet()

    def action_payslip_done(self):
        """Override to mark linked overtime bonuses as paid when payslip is validated."""
        res = super().action_payslip_done()
        for payslip in self:
            overtime_bonuses = payslip.overtime_bonus_ids.filtered(
                lambda r: r.state == 'confirmed'
            )
            if overtime_bonuses:
                overtime_bonuses.write({'state': 'paid'})
                _logger.info(
                    'Payslip %s: Marked %d overtime bonus record(s) as paid',
                    payslip.number or payslip.id,
                    len(overtime_bonuses),
                )
        return res
