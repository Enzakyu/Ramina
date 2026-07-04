from odoo import models, fields

class HrAnnouncement(models.Model):
    _name = 'hr.announcement'
    _description = 'Company Announcement'
    _order = 'date desc, id desc'

    title = fields.Char(string='Title', required=True)
    content = fields.Html(string='Content', required=True)
    date = fields.Date(string='Date Published', default=fields.Date.context_today, required=True)
    active = fields.Boolean(string='Active', default=True)
    
    # Optional: Tie to specific departments, but leaving blank means company-wide
    department_id = fields.Many2one('hr.department', string='Target Department (Optional)')
