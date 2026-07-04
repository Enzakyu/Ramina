from odoo import models, fields, api, _
from odoo.exceptions import UserError

class HrPayslip(models.Model):
    _name = 'hr.payslip'
    _description = 'Payslip'
    _order = 'date_to desc, employee_id'

    name = fields.Char(string='Payslip Name', required=True)
    number = fields.Char(string='Reference', readonly=True, copy=False)
    employee_id = fields.Many2one('hr.employee', string='Employee', required=True)
    date_from = fields.Date(string='Date From', required=True, default=fields.Date.context_today)
    date_to = fields.Date(string='Date To', required=True, default=fields.Date.context_today)
    state = fields.Selection([
        ('draft', 'Draft'),
        ('verify', 'Waiting'),
        ('done', 'Done'),
        ('cancel', 'Rejected'),
    ], string='Status', index=True, readonly=True, copy=False, default='draft')
    
    line_ids = fields.One2many('hr.payslip.line', 'slip_id', string='Payslip Lines', readonly=False)
    company_id = fields.Many2one('res.company', string='Company', default=lambda self: self.env.company)
    net_wage = fields.Monetary(string='Net Wage', compute='_compute_net_wage', store=True, currency_field='currency_id')
    currency_id = fields.Many2one('res.currency', related='company_id.currency_id', readonly=True)
    
    overtime_bonus_ids = fields.One2many('hr.overtime.bonus', 'payslip_id', string='Overtime Bonuses')

    @api.onchange('employee_id', 'date_from', 'date_to')
    def _onchange_employee(self):
        if (not self.employee_id) or (not self.date_from) or (not self.date_to):
            return
        self.name = _('Salary Slip of %s for %s - %s') % (
            self.employee_id.name,
            self.date_from.strftime('%B %Y'),
            self.date_to.strftime('%B %Y')
        )

    @api.depends('line_ids.total')
    def _compute_net_wage(self):
        for slip in self:
            net_line = slip.line_ids.filtered(lambda l: l.code == 'NET')
            slip.net_wage = sum(net_line.mapped('total')) if net_line else 0.0

    def compute_sheet(self):
        for payslip in self:
            payslip.line_ids.unlink()
            lines = []
            
            # 1. Basic Salary
            basic_amount = payslip.employee_id.basic_salary or 0.0
            
            lines.append((0, 0, {
                'name': _('Basic Salary'),
                'code': 'BASIC',
                'category_id': self.env.ref('hr_custom_payroll.BASIC', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.BASIC', raise_if_not_found=False) else False,
                'total': basic_amount,
            }))

            # 2. Daily Settlements (Overtime, Tardiness, Attendance Allowance)
            settlements = self.env['hr.overtime.bonus'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('date', '>=', payslip.date_from),
                ('date', '<=', payslip.date_to),
                ('state', '=', 'confirmed')
            ])
            
            payslip.overtime_bonus_ids = settlements
            overtime_amount = sum(settlements.mapped('bonus_amount'))
            tardiness_amount = sum(settlements.mapped('tardiness_penalty'))
            attendance_amount = sum(settlements.mapped('attendance_allowance'))

            if overtime_amount > 0:
                lines.append((0, 0, {
                    'name': _('Overtime Bonus'),
                    'code': 'OVERTIME',
                    'category_id': self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False) else False,
                    'total': overtime_amount,
                }))
                
            if tardiness_amount > 0:
                lines.append((0, 0, {
                    'name': _('Tardiness Penalty'),
                    'code': 'TARDY',
                    'category_id': self.env.ref('hr_custom_payroll.DED', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.DED', raise_if_not_found=False) else False,
                    'total': -tardiness_amount,
                }))
                
            if attendance_amount > 0:
                lines.append((0, 0, {
                    'name': _('Attendance Allowance'),
                    'code': 'ATT_ALW',
                    'category_id': self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False) else False,
                    'total': attendance_amount,
                }))

            # 3. Compensations (Reimbursements)
            compensations = self.env['hr.compensation'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('date', '>=', payslip.date_from),
                ('date', '<=', payslip.date_to),
                ('state', '=', 'approved')
            ])
            compensation_amount = sum(compensations.mapped('amount'))
            if compensation_amount > 0:
                lines.append((0, 0, {
                    'name': _('Reimbursements'),
                    'code': 'COMP',
                    'category_id': self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False) else False,
                    'total': compensation_amount,
                }))
                
            # 4. Ad-Hoc Adjustments
            adjustments = self.env['hr.payroll.adjustment'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('date', '>=', payslip.date_from),
                ('date', '<=', payslip.date_to),
                ('state', '=', 'approved')
            ])
            
            adj_allowance = sum(adjustments.filtered(lambda r: r.adjustment_type == 'allowance').mapped('amount'))
            adj_deduction = sum(adjustments.filtered(lambda r: r.adjustment_type == 'deduction').mapped('amount'))
            
            if adj_allowance > 0:
                lines.append((0, 0, {
                    'name': _('Other Allowances (THR/Bonus)'),
                    'code': 'ADJ_ALW',
                    'category_id': self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False) else False,
                    'total': adj_allowance,
                }))
            if adj_deduction > 0:
                lines.append((0, 0, {
                    'name': _('Other Deductions'),
                    'code': 'ADJ_DED',
                    'category_id': self.env.ref('hr_custom_payroll.DED', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.DED', raise_if_not_found=False) else False,
                    'total': -adj_deduction,
                }))

            # 5. Performance Reviews (KPI Multiplier - e.g. score / 100 * basic_salary * 10%)
            reviews = self.env['hr.performance.review'].search([
                ('employee_id', '=', payslip.employee_id.id),
                ('date', '>=', payslip.date_from),
                ('date', '<=', payslip.date_to),
                ('state', '=', 'submitted')
            ], limit=1)
            
            kpi_bonus = 0.0
            if reviews:
                # Example policy: up to 10% of basic salary based on score
                kpi_bonus = basic_amount * 0.10 * (reviews.kpi_score / 100.0)
                if kpi_bonus > 0:
                    lines.append((0, 0, {
                        'name': _('Performance Bonus (KPI: %s)') % reviews.kpi_score,
                        'code': 'KPI',
                        'category_id': self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.ALW', raise_if_not_found=False) else False,
                        'total': kpi_bonus,
                    }))

            # 6. Net Salary
            net_amount = basic_amount + overtime_amount - tardiness_amount + attendance_amount + compensation_amount + adj_allowance - adj_deduction + kpi_bonus
            lines.append((0, 0, {
                'name': _('Net Salary'),
                'code': 'NET',
                'category_id': self.env.ref('hr_custom_payroll.NET', raise_if_not_found=False).id if self.env.ref('hr_custom_payroll.NET', raise_if_not_found=False) else False,
                'total': net_amount,
            }))

            payslip.write({'line_ids': lines, 'state': 'verify'})
            
            # Mark compensations and adjustments as applied/paid when done
            # (In reality this happens on action_payslip_done, but we can set up relations if needed.
            # For simplicity, we just calculate them).
            
    def action_payslip_done(self):
        for slip in self:
            if slip.state == 'cancel':
                raise UserError(_("You can't validate a cancelled payslip."))
            slip.write({'state': 'done'})
            slip.overtime_bonus_ids.write({'state': 'paid'})
            if not slip.number:
                slip.number = self.env['ir.sequence'].next_by_code('hr.payslip') or _('New')

    def action_payslip_cancel(self):
        self.write({'state': 'cancel'})

    def action_payslip_draft(self):
        self.write({'state': 'draft'})


class HrPayslipLine(models.Model):
    _name = 'hr.payslip.line'
    _description = 'Payslip Line'
    _order = 'slip_id, id'

    name = fields.Char(required=True)
    code = fields.Char(required=True)
    slip_id = fields.Many2one('hr.payslip', string='Payslip', required=True, ondelete='cascade')
    total = fields.Float(string='Total', digits=(16, 2))
    category_id = fields.Many2one('hr.salary.rule.category', string='Category')


class HrSalaryRuleCategory(models.Model):
    _name = 'hr.salary.rule.category'
    _description = 'Salary Rule Category'

    name = fields.Char(required=True)
    code = fields.Char(required=True)
