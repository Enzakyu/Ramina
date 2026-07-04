from odoo import models, fields

class HrPayrollAdjustment(models.Model):
    _name = 'hr.payroll.adjustment'
    _description = 'Adhoc Payroll Adjustment'
    _inherit = ['mail.thread', 'mail.activity.mixin']
    _order = 'date desc, employee_id'

    employee_id = fields.Many2one('hr.employee', string='Employee', required=True, tracking=True)
    date = fields.Date(string='Date', required=True, default=fields.Date.context_today)
    adjustment_type = fields.Selection([
        ('allowance', 'Allowance (Bonus/THR)'),
        ('deduction', 'Deduction (Penalty)')
    ], string='Type', required=True, tracking=True)
    amount = fields.Monetary(string='Amount', required=True, currency_field='currency_id')
    description = fields.Char(string='Description', required=True)
    state = fields.Selection([
        ('draft', 'Draft'),
        ('approved', 'Approved'),
        ('applied', 'Applied to Payslip')
    ], string='Status', default='draft', tracking=True)
    
    currency_id = fields.Many2one('res.currency', related='employee_id.company_id.currency_id', readonly=True)
    
    def action_approve(self):
        self.write({'state': 'approved'})
