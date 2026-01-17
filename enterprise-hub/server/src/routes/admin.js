/**
 * Admin Routes
 * 
 * System administration endpoints
 */

import { Router } from 'express';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { requireAdmin, hasPermission } from '../middleware/auth.js';

const router = Router();

// All admin routes require admin level
router.use(requireAdmin);

// ============================================================================
// DASHBOARD STATS
// ============================================================================

/**
 * GET /api/admin/stats
 * Get admin dashboard statistics
 */
router.get('/stats', asyncHandler(async (req, res) => {
  const companyDb = getDb('company');
  const auditDb = getDb('audit');
  
  // Employee stats
  const employeeStats = companyDb.prepare(`
    SELECT 
      COUNT(*) as total,
      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
      SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM employees
  `).get();
  
  // VPN stats
  const vpnStats = companyDb.prepare(`
    SELECT 
      COUNT(*) as totalDevices,
      SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as activeDevices
    FROM vpn_devices
  `).get();
  
  // Session stats
  const sessionStats = companyDb.prepare(`
    SELECT COUNT(*) as activeSessions
    FROM sessions
    WHERE is_active = 1 AND expires_at > datetime('now')
  `).get();
  
  // Recent activity
  const recentActivity = auditDb.prepare(`
    SELECT action, COUNT(*) as count
    FROM audit_logs
    WHERE created_at > datetime('now', '-24 hours')
    GROUP BY action
  `).all();
  
  // Login attempts (last 24h)
  const loginStats = auditDb.prepare(`
    SELECT 
      SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful,
      SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed
    FROM login_attempts
    WHERE created_at > datetime('now', '-24 hours')
  `).get();
  
  res.json({
    employees: employeeStats,
    vpn: vpnStats,
    sessions: sessionStats,
    activity: recentActivity,
    logins: loginStats
  });
}));

// ============================================================================
// USER MANAGEMENT
// ============================================================================

/**
 * GET /api/admin/users
 * List all users with detailed info
 */
router.get('/users', hasPermission('users.view.all'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const { search, role, status, page = 1, limit = 50 } = req.query;
  
  let query = `
    SELECT 
      e.id,
      e.email,
      e.first_name,
      e.last_name,
      e.employee_number,
      e.status,
      e.is_active,
      e.last_login_at,
      e.created_at,
      r.name as role_name,
      r.display_name as role_display_name,
      r.level as role_level,
      d.name as department_name
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE 1=1
  `;
  
  const params = [];
  
  if (search) {
    query += ` AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.email LIKE ?)`;
    const term = `%${search}%`;
    params.push(term, term, term);
  }
  
  if (role) {
    query += ` AND e.role_id = ?`;
    params.push(role);
  }
  
  if (status) {
    query += ` AND e.status = ?`;
    params.push(status);
  }
  
  const offset = (parseInt(page) - 1) * parseInt(limit);
  query += ` ORDER BY e.last_name, e.first_name LIMIT ? OFFSET ?`;
  params.push(parseInt(limit), offset);
  
  const users = db.prepare(query).all(...params);
  
  // Get total count
  const { total } = db.prepare(`SELECT COUNT(*) as total FROM employees`).get();
  
  res.json({
    users,
    pagination: {
      page: parseInt(page),
      limit: parseInt(limit),
      total,
      pages: Math.ceil(total / parseInt(limit))
    }
  });
}));

/**
 * PATCH /api/admin/users/:id/role
 * Change user's role
 */
router.patch('/users/:id/role', hasPermission('users.roles.assign'), asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { roleId } = req.body;
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Can't change own role
  if (parseInt(id) === req.user.id) {
    throw Errors.badRequest('You cannot change your own role');
  }
  
  // Get target user
  const targetUser = db.prepare(`SELECT * FROM employees WHERE id = ?`).get(id);
  if (!targetUser) {
    throw Errors.notFound('User not found');
  }
  
  // Get new role
  const newRole = db.prepare(`SELECT * FROM roles WHERE id = ?`).get(roleId);
  if (!newRole) {
    throw Errors.notFound('Role not found');
  }
  
  // Can't assign role higher than own level
  if (newRole.level > req.user.roleLevel) {
    throw Errors.forbidden('Cannot assign a role higher than your own');
  }
  
  // Update role
  db.prepare(`UPDATE employees SET role_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?`).run(roleId, id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'role_change', 'employee', ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    `Changed role to ${newRole.display_name}`,
    req.ip
  );
  
  res.json({ success: true, newRole: newRole.display_name });
}));

// ============================================================================
// AUDIT LOGS
// ============================================================================

/**
 * GET /api/admin/audit-logs
 * View audit logs
 */
router.get('/audit-logs', hasPermission('audit.view'), asyncHandler(async (req, res) => {
  const auditDb = getDb('audit');
  const { action, userId, startDate, endDate, page = 1, limit = 100 } = req.query;
  
  let query = `
    SELECT *
    FROM audit_logs
    WHERE 1=1
  `;
  
  const params = [];
  
  if (action) {
    query += ` AND action = ?`;
    params.push(action);
  }
  
  if (userId) {
    query += ` AND employee_id = ?`;
    params.push(userId);
  }
  
  if (startDate) {
    query += ` AND created_at >= ?`;
    params.push(startDate);
  }
  
  if (endDate) {
    query += ` AND created_at <= ?`;
    params.push(endDate);
  }
  
  const offset = (parseInt(page) - 1) * parseInt(limit);
  query += ` ORDER BY created_at DESC LIMIT ? OFFSET ?`;
  params.push(parseInt(limit), offset);
  
  const logs = auditDb.prepare(query).all(...params);
  
  res.json(logs);
}));

// ============================================================================
// INVITATIONS
// ============================================================================

/**
 * POST /api/admin/invitations
 * Send employee invitation
 */
router.post('/invitations', hasPermission('users.invite'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const { email, roleId, departmentId, positionId } = req.body;
  
  if (!email || !roleId) {
    throw Errors.badRequest('Email and role are required');
  }
  
  // Check email not already registered
  const existing = db.prepare(`SELECT id FROM employees WHERE LOWER(email) = LOWER(?)`).get(email);
  if (existing) {
    throw Errors.conflict('An employee with this email already exists');
  }
  
  // Check no pending invitation
  const pendingInvite = db.prepare(`
    SELECT id FROM invitations 
    WHERE LOWER(email) = LOWER(?) AND status = 'pending'
  `).get(email);
  if (pendingInvite) {
    throw Errors.conflict('A pending invitation already exists for this email');
  }
  
  // Generate invite code
  const crypto = await import('crypto');
  const inviteCode = crypto.randomBytes(16).toString('hex');
  
  // Create invitation
  const result = db.prepare(`
    INSERT INTO invitations (
      email, role_id, department_id, position_id, 
      invite_code, invited_by, expires_at
    )
    VALUES (?, ?, ?, ?, ?, ?, datetime('now', '+7 days'))
  `).run(email, roleId, departmentId, positionId, inviteCode, req.user.id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'invite', 'invitation', ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    result.lastInsertRowid,
    `Invited ${email}`,
    req.ip
  );
  
  // TODO: Send invitation email
  console.log(`[Admin] Invitation sent to ${email}, code: ${inviteCode}`);
  
  res.status(201).json({
    success: true,
    invitation: {
      id: result.lastInsertRowid,
      email,
      inviteCode: process.env.NODE_ENV === 'development' ? inviteCode : undefined
    }
  });
}));

/**
 * GET /api/admin/invitations
 * List all invitations
 */
router.get('/invitations', hasPermission('users.invite'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const invitations = db.prepare(`
    SELECT 
      i.*,
      r.display_name as role_name,
      d.name as department_name,
      e.first_name || ' ' || e.last_name as invited_by_name
    FROM invitations i
    JOIN roles r ON i.role_id = r.id
    LEFT JOIN departments d ON i.department_id = d.id
    JOIN employees e ON i.invited_by = e.id
    ORDER BY i.created_at DESC
  `).all();
  
  res.json(invitations);
}));

// ============================================================================
// ROLES & PERMISSIONS
// ============================================================================

/**
 * GET /api/admin/roles
 * List all roles
 */
router.get('/roles', asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const roles = db.prepare(`
    SELECT r.*, 
      (SELECT COUNT(*) FROM employees WHERE role_id = r.id) as user_count
    FROM roles r
    ORDER BY r.level DESC
  `).all();
  
  res.json(roles);
}));

/**
 * GET /api/admin/permissions
 * List all permissions
 */
router.get('/permissions', asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const permissions = db.prepare(`
    SELECT * FROM permissions ORDER BY category, name
  `).all();
  
  // Group by category
  const grouped = permissions.reduce((acc, p) => {
    if (!acc[p.category]) acc[p.category] = [];
    acc[p.category].push(p);
    return acc;
  }, {});
  
  res.json(grouped);
}));

// ============================================================================
// DEPARTMENTS
// ============================================================================

/**
 * GET /api/admin/departments
 * List all departments
 */
router.get('/departments', asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const departments = db.prepare(`
    SELECT d.*,
      m.first_name || ' ' || m.last_name as manager_name,
      (SELECT COUNT(*) FROM employees WHERE department_id = d.id) as employee_count
    FROM departments d
    LEFT JOIN employees m ON d.manager_id = m.id
    ORDER BY d.name
  `).all();
  
  res.json(departments);
}));

/**
 * POST /api/admin/departments
 * Create department
 */
router.post('/departments', hasPermission('settings.edit'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const { name, code, description, managerId, parentId } = req.body;
  
  if (!name) {
    throw Errors.badRequest('Department name is required');
  }
  
  const result = db.prepare(`
    INSERT INTO departments (name, code, description, manager_id, parent_id)
    VALUES (?, ?, ?, ?, ?)
  `).run(name, code, description, managerId, parentId);
  
  res.status(201).json({
    success: true,
    department: { id: result.lastInsertRowid, name }
  });
}));

// ============================================================================
// SETTINGS
// ============================================================================

/**
 * GET /api/admin/settings
 * Get all settings
 */
router.get('/settings', hasPermission('settings.view'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const settings = db.prepare(`SELECT * FROM company_settings`).all();
  
  // Group by category
  const grouped = settings.reduce((acc, s) => {
    if (!acc[s.category]) acc[s.category] = {};
    acc[s.category][s.key] = s.value;
    return acc;
  }, {});
  
  res.json(grouped);
}));

/**
 * PATCH /api/admin/settings
 * Update settings
 */
router.patch('/settings', hasPermission('settings.edit'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const updates = req.body;
  
  const updateSetting = db.prepare(`
    UPDATE company_settings 
    SET value = ?, updated_at = CURRENT_TIMESTAMP, updated_by = ?
    WHERE key = ?
  `);
  
  for (const [key, value] of Object.entries(updates)) {
    updateSetting.run(value, req.user.id, key);
  }
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, description, ip_address
    )
    VALUES (?, ?, ?, 'update', 'settings', 'Settings updated', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    req.ip
  );
  
  res.json({ success: true });
}));

// ============================================================================
// ANNOUNCEMENTS
// ============================================================================

/**
 * POST /api/admin/announcements
 * Create announcement
 */
router.post('/announcements', hasPermission('announcements.manage'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const { title, content, type = 'info', isPinned = false, expiresAt } = req.body;
  
  if (!title || !content) {
    throw Errors.badRequest('Title and content are required');
  }
  
  const result = db.prepare(`
    INSERT INTO announcements (title, content, type, is_pinned, published_at, expires_at, created_by)
    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
  `).run(title, content, type, isPinned ? 1 : 0, expiresAt, req.user.id);
  
  // Broadcast via WebSocket
  const io = req.app.get('io');
  if (io) {
    io.to('company').emit('announcement', { 
      id: result.lastInsertRowid, 
      title, 
      type 
    });
  }
  
  res.status(201).json({
    success: true,
    announcement: { id: result.lastInsertRowid, title }
  });
}));

export default router;
