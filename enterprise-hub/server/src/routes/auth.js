/**
 * Authentication Routes
 * 
 * Handles login, logout, password reset, and SSO
 */

import { Router } from 'express';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import crypto from 'crypto';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { authMiddleware } from '../middleware/auth.js';

const router = Router();

const JWT_SECRET = process.env.JWT_SECRET || 'development-secret-change-in-production';
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d';
const SALT_ROUNDS = 12;

// ============================================================================
// LOGIN
// ============================================================================

/**
 * POST /api/auth/login
 * Login with email and password
 */
router.post('/login', asyncHandler(async (req, res) => {
  const { email, password } = req.body;
  
  // Validate input
  if (!email || !password) {
    throw Errors.badRequest('Email and password are required');
  }
  
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Find employee by email
  const employee = db.prepare(`
    SELECT 
      e.*,
      r.name as role_name,
      r.level as role_level,
      r.display_name as role_display_name
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    WHERE LOWER(e.email) = LOWER(?)
  `).get(email);
  
  // Log login attempt
  const logAttempt = (success, reason = null) => {
    auditDb.prepare(`
      INSERT INTO login_attempts (email, ip_address, user_agent, success, failure_reason)
      VALUES (?, ?, ?, ?, ?)
    `).run(
      email,
      req.ip || req.connection.remoteAddress,
      req.get('User-Agent'),
      success ? 1 : 0,
      reason
    );
  };
  
  // Check if employee exists
  if (!employee) {
    logAttempt(false, 'User not found');
    throw Errors.unauthorized('Invalid email or password');
  }
  
  // Check if employee is active
  if (!employee.is_active || employee.status !== 'active') {
    logAttempt(false, 'Account inactive');
    throw Errors.unauthorized('Account is inactive. Please contact your administrator.');
  }
  
  // Verify password
  const validPassword = await bcrypt.compare(password, employee.password_hash);
  if (!validPassword) {
    logAttempt(false, 'Invalid password');
    throw Errors.unauthorized('Invalid email or password');
  }
  
  // Create session
  const sessionToken = crypto.randomBytes(32).toString('hex');
  const sessionTokenHash = crypto.createHash('sha256').update(sessionToken).digest('hex');
  
  const sessionResult = db.prepare(`
    INSERT INTO sessions (
      employee_id, token_hash, device_name, device_type, 
      ip_address, user_agent, expires_at
    )
    VALUES (?, ?, ?, ?, ?, ?, datetime('now', '+7 days'))
  `).run(
    employee.id,
    sessionTokenHash,
    req.body.deviceName || 'Unknown Device',
    req.body.deviceType || 'web',
    req.ip || req.connection.remoteAddress,
    req.get('User-Agent')
  );
  
  // Generate JWT
  const token = jwt.sign(
    {
      employeeId: employee.id,
      email: employee.email,
      sessionId: sessionResult.lastInsertRowid
    },
    JWT_SECRET,
    { expiresIn: JWT_EXPIRES_IN }
  );
  
  // Update last login
  db.prepare(`
    UPDATE employees SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?
  `).run(employee.id);
  
  // Log successful login
  logAttempt(true);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action, 
      resource_type, description, ip_address, user_agent
    )
    VALUES (?, ?, ?, 'login', 'session', 'User logged in', ?, ?)
  `).run(
    employee.id,
    employee.email,
    `${employee.first_name} ${employee.last_name}`,
    req.ip,
    req.get('User-Agent')
  );
  
  // Get permissions
  const permissions = db.prepare(`
    SELECT p.name
    FROM permissions p
    JOIN role_permissions rp ON p.id = rp.permission_id
    WHERE rp.role_id = ?
  `).all(employee.role_id).map(p => p.name);
  
  // Return user data and token
  res.json({
    success: true,
    token,
    user: {
      id: employee.id,
      email: employee.email,
      firstName: employee.first_name,
      lastName: employee.last_name,
      avatarUrl: employee.avatar_url,
      employeeNumber: employee.employee_number,
      role: {
        id: employee.role_id,
        name: employee.role_name,
        displayName: employee.role_display_name,
        level: employee.role_level
      },
      departmentId: employee.department_id,
      mustChangePassword: employee.must_change_password === 1,
      permissions
    }
  });
}));

// ============================================================================
// LOGOUT
// ============================================================================

/**
 * POST /api/auth/logout
 * Logout and invalidate session
 */
router.post('/logout', authMiddleware, asyncHandler(async (req, res) => {
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Get token from header to find session
  const authHeader = req.headers.authorization;
  const token = authHeader.split(' ')[1];
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    
    // Deactivate session
    if (decoded.sessionId) {
      db.prepare(`
        UPDATE sessions SET is_active = 0 WHERE id = ?
      `).run(decoded.sessionId);
    }
  } catch (e) {
    // Token might be invalid, but we still want to log out
  }
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, description, ip_address
    )
    VALUES (?, ?, ?, 'logout', 'session', 'User logged out', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    req.ip
  );
  
  res.json({ success: true, message: 'Logged out successfully' });
}));

// ============================================================================
// GET CURRENT USER
// ============================================================================

/**
 * GET /api/auth/me
 * Get current authenticated user
 */
router.get('/me', authMiddleware, asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  // Get fresh user data
  const employee = db.prepare(`
    SELECT 
      e.id,
      e.email,
      e.first_name,
      e.last_name,
      e.preferred_name,
      e.avatar_url,
      e.phone,
      e.employee_number,
      e.role_id,
      e.department_id,
      e.position_id,
      e.manager_id,
      e.hire_date,
      e.employment_type,
      e.work_location,
      e.status,
      e.must_change_password,
      r.name as role_name,
      r.level as role_level,
      r.display_name as role_display_name,
      d.name as department_name,
      p.title as position_title
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.id = ?
  `).get(req.user.id);
  
  if (!employee) {
    throw Errors.notFound('User not found');
  }
  
  res.json({
    id: employee.id,
    email: employee.email,
    firstName: employee.first_name,
    lastName: employee.last_name,
    preferredName: employee.preferred_name,
    avatarUrl: employee.avatar_url,
    phone: employee.phone,
    employeeNumber: employee.employee_number,
    role: {
      id: employee.role_id,
      name: employee.role_name,
      displayName: employee.role_display_name,
      level: employee.role_level
    },
    department: employee.department_id ? {
      id: employee.department_id,
      name: employee.department_name
    } : null,
    position: employee.position_id ? {
      id: employee.position_id,
      title: employee.position_title
    } : null,
    managerId: employee.manager_id,
    hireDate: employee.hire_date,
    employmentType: employee.employment_type,
    workLocation: employee.work_location,
    status: employee.status,
    mustChangePassword: employee.must_change_password === 1,
    permissions: req.user.permissions
  });
}));

// ============================================================================
// CHANGE PASSWORD
// ============================================================================

/**
 * POST /api/auth/password/change
 * Change password for authenticated user
 */
router.post('/password/change', authMiddleware, asyncHandler(async (req, res) => {
  const { currentPassword, newPassword } = req.body;
  
  if (!currentPassword || !newPassword) {
    throw Errors.badRequest('Current password and new password are required');
  }
  
  if (newPassword.length < 8) {
    throw Errors.badRequest('New password must be at least 8 characters');
  }
  
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Get current password hash
  const employee = db.prepare(`
    SELECT password_hash FROM employees WHERE id = ?
  `).get(req.user.id);
  
  // Verify current password
  const validPassword = await bcrypt.compare(currentPassword, employee.password_hash);
  if (!validPassword) {
    throw Errors.unauthorized('Current password is incorrect');
  }
  
  // Hash new password
  const newPasswordHash = await bcrypt.hash(newPassword, SALT_ROUNDS);
  
  // Update password
  db.prepare(`
    UPDATE employees 
    SET password_hash = ?, 
        must_change_password = 0,
        password_changed_at = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(newPasswordHash, req.user.id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'password_change', 'employee', ?, 'Password changed', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    req.user.id,
    req.ip
  );
  
  res.json({ success: true, message: 'Password changed successfully' });
}));

// ============================================================================
// REQUEST PASSWORD RESET
// ============================================================================

/**
 * POST /api/auth/password/reset-request
 * Request a password reset email
 */
router.post('/password/reset-request', asyncHandler(async (req, res) => {
  const { email } = req.body;
  
  if (!email) {
    throw Errors.badRequest('Email is required');
  }
  
  const db = getDb('company');
  
  // Find employee
  const employee = db.prepare(`
    SELECT id, email, first_name, last_name 
    FROM employees 
    WHERE LOWER(email) = LOWER(?) AND is_active = 1
  `).get(email);
  
  // Always return success to prevent email enumeration
  if (!employee) {
    return res.json({ 
      success: true, 
      message: 'If an account exists with this email, a reset link has been sent' 
    });
  }
  
  // Generate reset token
  const resetToken = crypto.randomBytes(32).toString('hex');
  const resetTokenHash = crypto.createHash('sha256').update(resetToken).digest('hex');
  
  // Store reset token (expires in 1 hour)
  db.prepare(`
    INSERT INTO password_resets (employee_id, token_hash, expires_at)
    VALUES (?, ?, datetime('now', '+1 hour'))
  `).run(employee.id, resetTokenHash);
  
  // TODO: Send email with reset link
  // For now, log the token (in production, this would be sent via email)
  console.log(`[Auth] Password reset token for ${email}: ${resetToken}`);
  
  res.json({ 
    success: true, 
    message: 'If an account exists with this email, a reset link has been sent',
    // Only include token in development for testing
    ...(process.env.NODE_ENV === 'development' && { resetToken })
  });
}));

// ============================================================================
// RESET PASSWORD WITH TOKEN
// ============================================================================

/**
 * POST /api/auth/password/reset
 * Reset password using token
 */
router.post('/password/reset', asyncHandler(async (req, res) => {
  const { token, newPassword } = req.body;
  
  if (!token || !newPassword) {
    throw Errors.badRequest('Token and new password are required');
  }
  
  if (newPassword.length < 8) {
    throw Errors.badRequest('Password must be at least 8 characters');
  }
  
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Hash the provided token
  const tokenHash = crypto.createHash('sha256').update(token).digest('hex');
  
  // Find valid reset request
  const resetRequest = db.prepare(`
    SELECT pr.*, e.email, e.first_name, e.last_name
    FROM password_resets pr
    JOIN employees e ON pr.employee_id = e.id
    WHERE pr.token_hash = ? 
      AND pr.expires_at > datetime('now')
      AND pr.used_at IS NULL
  `).get(tokenHash);
  
  if (!resetRequest) {
    throw Errors.badRequest('Invalid or expired reset token');
  }
  
  // Hash new password
  const passwordHash = await bcrypt.hash(newPassword, SALT_ROUNDS);
  
  // Update password
  db.prepare(`
    UPDATE employees 
    SET password_hash = ?,
        must_change_password = 0,
        password_changed_at = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(passwordHash, resetRequest.employee_id);
  
  // Mark token as used
  db.prepare(`
    UPDATE password_resets SET used_at = CURRENT_TIMESTAMP WHERE id = ?
  `).run(resetRequest.id);
  
  // Invalidate all existing sessions
  db.prepare(`
    UPDATE sessions SET is_active = 0 WHERE employee_id = ?
  `).run(resetRequest.employee_id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'password_reset', 'employee', ?, 'Password reset via email', ?)
  `).run(
    resetRequest.employee_id,
    resetRequest.email,
    `${resetRequest.first_name} ${resetRequest.last_name}`,
    resetRequest.employee_id,
    req.ip
  );
  
  res.json({ success: true, message: 'Password has been reset. Please log in with your new password.' });
}));

// ============================================================================
// REFRESH TOKEN
// ============================================================================

/**
 * POST /api/auth/refresh
 * Refresh JWT token
 */
router.post('/refresh', authMiddleware, asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  // Check if user still active
  const employee = db.prepare(`
    SELECT id, email, is_active, status FROM employees WHERE id = ?
  `).get(req.user.id);
  
  if (!employee || !employee.is_active || employee.status !== 'active') {
    throw Errors.unauthorized('Account is no longer active');
  }
  
  // Generate new token
  const token = jwt.sign(
    {
      employeeId: req.user.id,
      email: req.user.email
    },
    JWT_SECRET,
    { expiresIn: JWT_EXPIRES_IN }
  );
  
  res.json({ success: true, token });
}));

// ============================================================================
// GET SESSIONS
// ============================================================================

/**
 * GET /api/auth/sessions
 * Get all active sessions for current user
 */
router.get('/sessions', authMiddleware, asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const sessions = db.prepare(`
    SELECT 
      id,
      device_name,
      device_type,
      ip_address,
      created_at,
      last_used_at
    FROM sessions
    WHERE employee_id = ? AND is_active = 1
    ORDER BY last_used_at DESC
  `).all(req.user.id);
  
  res.json(sessions);
}));

// ============================================================================
// REVOKE SESSION
// ============================================================================

/**
 * DELETE /api/auth/sessions/:id
 * Revoke a specific session
 */
router.delete('/sessions/:id', authMiddleware, asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  
  // Verify session belongs to user
  const session = db.prepare(`
    SELECT id FROM sessions WHERE id = ? AND employee_id = ?
  `).get(id, req.user.id);
  
  if (!session) {
    throw Errors.notFound('Session not found');
  }
  
  // Deactivate session
  db.prepare(`
    UPDATE sessions SET is_active = 0 WHERE id = ?
  `).run(id);
  
  res.json({ success: true, message: 'Session revoked' });
}));

export default router;
