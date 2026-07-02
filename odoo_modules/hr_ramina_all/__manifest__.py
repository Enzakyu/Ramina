{
    'name': 'Ramina HR All-in-One',
    'version': '19.0.1.0.0',
    'category': 'Human Resources',
    'summary': 'Unified HR management suite for Ramina with attendance, payroll, leave, and overtime',
    'description': """
        Ramina HR All-in-One
        ====================
        Complete HR management solution for Ramina including:
        - Employee management
        - Attendance tracking (9 AM - 5 PM schedule)
        - Leave management (Annual, Sick, Personal)
        - Payroll with automatic overtime calculation
        - Security groups and access control
        - Unified menu structure

        Company: Ramina
        Currency: IDR (Indonesian Rupiah)
        Work Schedule: Monday - Friday, 9:00 AM - 5:00 PM
        Timezone: Asia/Jakarta (WIB)
    """,
    'author': 'Ramina',
    'license': 'LGPL-3',
    'depends': [
        'hr',
        'hr_attendance',
        'hr_holidays',
        'hr_payroll',
        'hr_contract',
        'hr_work_entry',
        'hr_custom_payroll',
    ],
    'data': [
        'security/hr_security_groups.xml',
        'security/ir.model.access.csv',
        'data/hr_default_config.xml',
        'views/hr_menu_views.xml',
    ],
    'installable': True,
    'application': True,
    'auto_install': False,
}
