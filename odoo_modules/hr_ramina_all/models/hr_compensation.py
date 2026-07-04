from odoo import models, fields, api

class HrCompensation(models.Model):
    _name = 'hr.compensation'
    _description = 'Employee Compensation & Reimbursement'
    _inherit = ['mail.thread', 'mail.activity.mixin']

    employee_id = fields.Many2one(
        'hr.employee', 
        string='Employee', 
        required=True, 
        tracking=True,
    )
    date = fields.Date(
        string='Date', 
        default=fields.Date.context_today, 
        required=True,
    )
    amount = fields.Monetary(
        string='Amount', 
        required=True,
        currency_field='currency_id',
    )
    description = fields.Text(
        string='Description', 
        required=True,
    )
    state = fields.Selection([
        ('draft', 'Draft'),
        ('submitted', 'Submitted'),
        ('approved', 'Approved'),
        ('rejected', 'Rejected'),
        ('paid', 'Paid')
    ], string='Status', default='draft', tracking=True)
    
    currency_id = fields.Many2one(
        'res.currency', 
        related='employee_id.company_id.currency_id', 
        readonly=True
    )
    
    def action_submit(self):
        self.write({'state': 'submitted'})
        
    def action_approve(self):
        self.write({'state': 'approved'})
        
    def action_reject(self):
        self.write({'state': 'rejected'})
        
    def action_paid(self):
        self.write({'state': 'paid'})
