-- ============================================================================
-- SEED DATA
-- TrueVault Enterprise Business Hub
-- Default roles, permissions, time-off types, templates
-- ============================================================================

-- ============================================================================
-- ROLES
-- ============================================================================
INSERT INTO roles (name, display_name, level, description, is_system) VALUES
('employee', 'Employee', 20, 'Standard employee with self-service access', 1),
('manager', 'Manager', 40, 'Team lead with direct report management', 1),
('hr_staff', 'HR Staff', 50, 'HR team member with limited HR access', 1),
('hr_admin', 'HR Administrator', 70, 'Full HR access including compensation', 1),
('admin', 'Administrator', 80, 'IT/System administrator', 1),
('super_admin', 'Super Administrator', 90, 'Full system access except billing', 1),
('owner', 'Owner', 100, 'Company owner with full access', 1);

-- ============================================================================
-- PERMISSIONS
-- ============================================================================

-- Employee Self-Service
INSERT INTO permissions (name, category, description) VALUES
('profile.view.own', 'profile', 'View own profile'),
('profile.edit.own', 'profile', 'Edit own profile'),
('profile.avatar.own', 'profile', 'Update own avatar'),
('devices.view.own', 'vpn', 'View own VPN devices'),
('devices.manage.own', 'vpn', 'Add/remove own VPN devices'),
('timeoff.view.own', 'timeoff', 'View own time-off requests'),
('timeoff.request', 'timeoff', 'Submit time-off requests'),
('timeoff.cancel.own', 'timeoff', 'Cancel own pending requests'),
('documents.view.own', 'documents', 'View own documents'),
('documents.upload.own', 'documents', 'Upload own documents'),
('directory.view', 'directory', 'View company directory'),
('announcements.view', 'announcements', 'View announcements'),
('tasks.view.own', 'tasks', 'View own tasks'),
('tasks.manage.own', 'tasks', 'Manage own tasks'),
('notifications.view.own', 'notifications', 'View own notifications');

-- Manager Permissions
INSERT INTO permissions (name, category, description) VALUES
('team.view', 'team', 'View direct reports'),
('team.profile.view', 'team', 'View team member profiles'),
('timeoff.approve.team', 'timeoff', 'Approve team time-off requests'),
('timeoff.view.team', 'timeoff', 'View team time-off calendar'),
('tasks.assign.team', 'tasks', 'Assign tasks to team members'),
('tasks.view.team', 'tasks', 'View team tasks');

-- HR Permissions
INSERT INTO permissions (name, category, description) VALUES
('employees.view.all', 'hr', 'View all employees'),
('employees.edit.all', 'hr', 'Edit employee information'),
('employees.create', 'hr', 'Create new employees'),
('employees.deactivate', 'hr', 'Deactivate employees'),
('departments.manage', 'hr', 'Manage departments'),
('positions.manage', 'hr', 'Manage positions'),
('timeoff.view.all', 'timeoff', 'View all time-off requests'),
('timeoff.approve.all', 'timeoff', 'Approve any time-off request'),
('timeoff.types.manage', 'timeoff', 'Manage time-off types'),
('timeoff.balances.adjust', 'timeoff', 'Adjust time-off balances'),
('documents.view.all', 'documents', 'View all employee documents'),
('documents.manage.all', 'documents', 'Manage all documents'),
('reviews.manage', 'hr', 'Manage performance reviews'),
('onboarding.manage', 'hr', 'Manage onboarding'),
('hr.notes.manage', 'hr', 'Manage HR notes');

-- HR Admin Only
INSERT INTO permissions (name, category, description) VALUES
('compensation.view', 'compensation', 'View compensation data'),
('compensation.edit', 'compensation', 'Edit compensation data'),
('employees.terminate', 'hr', 'Process terminations');

-- Admin Permissions
INSERT INTO permissions (name, category, description) VALUES
('users.view.all', 'admin', 'View all users'),
('users.edit.all', 'admin', 'Edit user accounts'),
('users.roles.assign', 'admin', 'Assign roles to users'),
('users.sessions.manage', 'admin', 'Manage user sessions'),
('users.invite', 'admin', 'Send employee invitations'),
('vpn.view.all', 'vpn', 'View all VPN connections'),
('vpn.manage.all', 'vpn', 'Manage all VPN devices'),
('audit.view', 'admin', 'View audit logs'),
('settings.view', 'admin', 'View system settings'),
('settings.edit', 'admin', 'Edit system settings'),
('announcements.manage', 'announcements', 'Create/edit announcements'),
('backups.view', 'admin', 'View backups'),
('backups.create', 'admin', 'Create backups');

-- Super Admin Permissions
INSERT INTO permissions (name, category, description) VALUES
('sso.configure', 'admin', 'Configure SSO providers'),
('backups.restore', 'admin', 'Restore from backups'),
('roles.manage', 'admin', 'Manage custom roles');

-- Owner Permissions
INSERT INTO permissions (name, category, description) VALUES
('billing.view', 'owner', 'View billing information'),
('billing.manage', 'owner', 'Manage billing'),
('branding.manage', 'owner', 'Manage company branding'),
('company.settings', 'owner', 'Manage company settings'),
('ownership.transfer', 'owner', 'Transfer ownership');

-- DataForge Permissions
INSERT INTO permissions (name, category, description) VALUES
('dataforge.tables.create', 'dataforge', 'Create DataForge tables'),
('dataforge.tables.view.own', 'dataforge', 'View own DataForge tables'),
('dataforge.tables.view.shared', 'dataforge', 'View shared DataForge tables'),
('dataforge.tables.manage.own', 'dataforge', 'Manage own DataForge tables'),
('dataforge.templates.use', 'dataforge', 'Use DataForge templates'),
('dataforge.automations.manage', 'dataforge', 'Manage DataForge automations'),
('dataforge.dashboards.create', 'dataforge', 'Create DataForge dashboards');

-- ============================================================================
-- ROLE-PERMISSION MAPPINGS
-- ============================================================================

-- Employee permissions (level 20)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'employee' AND p.name IN (
    'profile.view.own', 'profile.edit.own', 'profile.avatar.own',
    'devices.view.own', 'devices.manage.own',
    'timeoff.view.own', 'timeoff.request', 'timeoff.cancel.own',
    'documents.view.own', 'documents.upload.own',
    'directory.view', 'announcements.view',
    'tasks.view.own', 'tasks.manage.own', 'notifications.view.own',
    'dataforge.tables.create', 'dataforge.tables.view.own', 
    'dataforge.tables.view.shared', 'dataforge.tables.manage.own',
    'dataforge.templates.use', 'dataforge.dashboards.create'
);

-- Manager permissions (level 40) = Employee + team management
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'manager' AND p.name IN (
    'profile.view.own', 'profile.edit.own', 'profile.avatar.own',
    'devices.view.own', 'devices.manage.own',
    'timeoff.view.own', 'timeoff.request', 'timeoff.cancel.own',
    'documents.view.own', 'documents.upload.own',
    'directory.view', 'announcements.view',
    'tasks.view.own', 'tasks.manage.own', 'notifications.view.own',
    'dataforge.tables.create', 'dataforge.tables.view.own', 
    'dataforge.tables.view.shared', 'dataforge.tables.manage.own',
    'dataforge.templates.use', 'dataforge.dashboards.create',
    'team.view', 'team.profile.view',
    'timeoff.approve.team', 'timeoff.view.team',
    'tasks.assign.team', 'tasks.view.team'
);

-- HR Staff permissions (level 50)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'hr_staff' AND p.name IN (
    'profile.view.own', 'profile.edit.own', 'profile.avatar.own',
    'devices.view.own', 'devices.manage.own',
    'timeoff.view.own', 'timeoff.request', 'timeoff.cancel.own',
    'documents.view.own', 'documents.upload.own',
    'directory.view', 'announcements.view',
    'tasks.view.own', 'tasks.manage.own', 'notifications.view.own',
    'dataforge.tables.create', 'dataforge.tables.view.own', 
    'dataforge.tables.view.shared', 'dataforge.tables.manage.own',
    'dataforge.templates.use', 'dataforge.dashboards.create',
    'employees.view.all', 'employees.edit.all', 'employees.create',
    'departments.manage', 'positions.manage',
    'timeoff.view.all', 'timeoff.approve.all', 'timeoff.types.manage', 'timeoff.balances.adjust',
    'documents.view.all', 'documents.manage.all',
    'reviews.manage', 'onboarding.manage', 'hr.notes.manage'
);

-- HR Admin permissions (level 70) = HR Staff + compensation + terminate
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'hr_admin' AND p.name IN (
    'profile.view.own', 'profile.edit.own', 'profile.avatar.own',
    'devices.view.own', 'devices.manage.own',
    'timeoff.view.own', 'timeoff.request', 'timeoff.cancel.own',
    'documents.view.own', 'documents.upload.own',
    'directory.view', 'announcements.view',
    'tasks.view.own', 'tasks.manage.own', 'notifications.view.own',
    'dataforge.tables.create', 'dataforge.tables.view.own', 
    'dataforge.tables.view.shared', 'dataforge.tables.manage.own',
    'dataforge.templates.use', 'dataforge.dashboards.create', 'dataforge.automations.manage',
    'employees.view.all', 'employees.edit.all', 'employees.create', 'employees.deactivate', 'employees.terminate',
    'departments.manage', 'positions.manage',
    'timeoff.view.all', 'timeoff.approve.all', 'timeoff.types.manage', 'timeoff.balances.adjust',
    'documents.view.all', 'documents.manage.all',
    'reviews.manage', 'onboarding.manage', 'hr.notes.manage',
    'compensation.view', 'compensation.edit',
    'team.view', 'team.profile.view', 'timeoff.view.team'
);

-- Admin permissions (level 80)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'admin' AND p.name IN (
    'profile.view.own', 'profile.edit.own', 'profile.avatar.own',
    'devices.view.own', 'devices.manage.own',
    'timeoff.view.own', 'timeoff.request', 'timeoff.cancel.own',
    'documents.view.own', 'documents.upload.own',
    'directory.view', 'announcements.view', 'announcements.manage',
    'tasks.view.own', 'tasks.manage.own', 'notifications.view.own',
    'dataforge.tables.create', 'dataforge.tables.view.own', 
    'dataforge.tables.view.shared', 'dataforge.tables.manage.own',
    'dataforge.templates.use', 'dataforge.dashboards.create', 'dataforge.automations.manage',
    'users.view.all', 'users.edit.all', 'users.roles.assign', 'users.sessions.manage', 'users.invite',
    'vpn.view.all', 'vpn.manage.all',
    'audit.view', 'settings.view', 'settings.edit',
    'backups.view', 'backups.create'
);

-- Super Admin permissions (level 90) = Admin + SSO + restore + roles
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'super_admin' AND p.name NOT IN (
    'billing.view', 'billing.manage', 'branding.manage', 'company.settings', 'ownership.transfer'
);

-- Owner permissions (level 100) = ALL permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'owner';

-- ============================================================================
-- TIME-OFF TYPES (for hr.db)
-- Run this against hr.db
-- ============================================================================
INSERT INTO time_off_types (name, code, description, color, default_days_per_year, accrual_type, requires_approval, can_carry_over, max_carry_over_days) VALUES
('Vacation', 'PTO', 'Paid time off for vacation', '#10B981', 20, 'annual', 1, 1, 5),
('Sick Leave', 'SICK', 'Paid sick leave', '#EF4444', 10, 'annual', 0, 0, 0),
('Personal Day', 'PERS', 'Personal days', '#8B5CF6', 3, 'annual', 1, 0, 0),
('Bereavement', 'BRV', 'Bereavement leave', '#6B7280', 5, 'as_needed', 0, 0, 0),
('Jury Duty', 'JURY', 'Jury duty leave', '#6B7280', 0, 'as_needed', 0, 0, 0),
('Parental Leave', 'PAR', 'Parental/maternity/paternity leave', '#EC4899', 0, 'as_needed', 1, 0, 0),
('Unpaid Leave', 'UNPD', 'Unpaid leave of absence', '#9CA3AF', 0, 'as_needed', 1, 0, 0);

-- ============================================================================
-- DOCUMENT CATEGORIES (for hr.db)
-- ============================================================================
INSERT INTO document_categories (name, description, requires_expiration, is_confidential_by_default) VALUES
('Personal ID', 'Government-issued identification', 1, 1),
('Certificates', 'Professional certifications', 1, 0),
('Contracts', 'Employment contracts', 0, 1),
('Performance', 'Performance reviews and feedback', 0, 1),
('Training', 'Training completion certificates', 1, 0),
('Medical', 'Medical documentation', 1, 1),
('Tax Documents', 'W-2, W-4, and tax forms', 0, 1),
('Other', 'Other documents', 0, 0);

-- ============================================================================
-- DEFAULT COMPANY SETTINGS (for company.db)
-- ============================================================================
INSERT INTO company_settings (key, value, type, category) VALUES
('company_name', 'My Company', 'string', 'branding'),
('company_logo_url', NULL, 'string', 'branding'),
('primary_color', '#3B82F6', 'string', 'branding'),
('secondary_color', '#10B981', 'string', 'branding'),
('timezone', 'America/New_York', 'string', 'general'),
('date_format', 'MM/DD/YYYY', 'string', 'general'),
('time_format', '12h', 'string', 'general'),
('fiscal_year_start', '01-01', 'string', 'general'),
('work_week_start', 'monday', 'string', 'general'),
('sso_google_enabled', 'false', 'boolean', 'sso'),
('sso_google_client_id', NULL, 'string', 'sso'),
('sso_microsoft_enabled', 'false', 'boolean', 'sso'),
('sso_microsoft_client_id', NULL, 'string', 'sso'),
('vpn_server_endpoint', NULL, 'string', 'vpn'),
('vpn_server_public_key', NULL, 'string', 'vpn'),
('vpn_network_cidr', '10.0.0.0/24', 'string', 'vpn'),
('auto_approve_timeoff_days', '0', 'number', 'timeoff'),
('require_timeoff_reason', 'false', 'boolean', 'timeoff');

-- ============================================================================
-- DEFAULT ONBOARDING TEMPLATE (for hr.db)
-- ============================================================================
INSERT INTO onboarding_templates (name, description, is_default, is_active) VALUES
('Standard Onboarding', 'Default onboarding checklist for new employees', 1, 1);

INSERT INTO onboarding_template_items (template_id, title, description, category, due_days, assigned_to_role, sort_order, is_required) VALUES
(1, 'Complete personal information', 'Fill out all required profile fields', 'paperwork', 0, 'employee', 1, 1),
(1, 'Upload identification documents', 'Upload government-issued ID', 'paperwork', 1, 'employee', 2, 1),
(1, 'Review employee handbook', 'Read and acknowledge the employee handbook', 'policies', 1, 'employee', 3, 1),
(1, 'Setup VPN access', 'Install WireGuard and configure VPN', 'it_setup', 0, 'employee', 4, 1),
(1, 'Complete security training', 'Complete mandatory security awareness training', 'training', 7, 'employee', 5, 1),
(1, 'Meet with manager', 'Schedule and complete introductory meeting with manager', 'meetings', 1, 'employee', 6, 1),
(1, 'Setup workstation', 'Ensure employee has necessary equipment', 'it_setup', 0, 'admin', 7, 1),
(1, 'Create user accounts', 'Create email and system accounts', 'it_setup', 0, 'admin', 8, 1),
(1, 'Add to relevant systems', 'Grant access to required applications', 'it_setup', 1, 'admin', 9, 1),
(1, '30-day check-in', 'Schedule 30-day performance check-in', 'meetings', 30, 'hr_staff', 10, 0);

-- ============================================================================
-- END OF SEED DATA
-- ============================================================================
