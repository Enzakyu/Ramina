from odoo import models, fields, api

class HrPerformanceReview(models.Model):
    _name = 'hr.performance.review'
    _description = 'Employee Performance Review (KPI)'
    _inherit = ['mail.thread', 'mail.activity.mixin']
    _order = 'date desc, employee_id'

    employee_id = fields.Many2one('hr.employee', string='Employee', required=True, tracking=True)
    date = fields.Date(string='Review Date', required=True, default=fields.Date.context_today)
    kpi_score = fields.Float(string='KPI Score (0-100)', required=True, tracking=True)
    feedback = fields.Text(string='Feedback')
    state = fields.Selection([
        ('draft', 'Draft'),
        ('submitted', 'Submitted')
    ], string='Status', default='draft', tracking=True)

    @api.constrains('kpi_score')
    def _check_kpi_score(self):
        for record in self:
            if record.kpi_score < 0 or record.kpi_score > 100:
                raise models.ValidationError("KPI Score must be between 0 and 100.")
                
    def action_submit(self):
        self.write({'state': 'submitted'})
