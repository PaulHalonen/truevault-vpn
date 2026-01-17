/**
 * Employee Routes
 * 
 * CRUD operations for employees
 * Different access levels based on role
 */

import { Router } from 'express';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { hasPermission, hasAnyPermission } from '../middleware/auth.js';

const router = Router();

// ============================================================================
// LIST EMPLOYEES (Directory)
// ============================================================================

/**
 * GET /api/employees
 * List employees - filtered by permissions
 */
router.get('/', asyncHandler(async (req, res) => {
  const db = getDb('company');
  const { search, department, status, role, page = 1, limit = 50 } = req.query;
  
  let query = `
    SELECT 
      e.id,
      e.email,
      e.first_name,
      e.last_name,
      e.preferred_name,
      e.avatar_url,
      e.phone,
      e.employee_number,
      e.department_id,
      e.position_id,
      e.manager_id,
      e.hire_date,
      e.employment_type,
      e.work_location,
      e.status,
      r.display_name as role_name,
      d.name as department_name,
      p.title as position_title
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.is_active = 1
  `;
  
  const params = [];
  
  // Only HR+ can see all employees, others see limited fields
  const canSeeAll = req.user.permissions.includes('employees.view.all');
  
  // Manager sees their team
  if (!canSeeAll && req.user.roleLevel >= 40) {
    query += ` AND (e.manager_id = ? OR e.id = ?)`;
    params.push(req.user.id, req.user.id);
  }
  
  // Search filter
  if (search) {
    query += ` AND (
      e.first_name LIKE ? OR 
      e.last_name LIKE ? OR 
      e.email LIKE ? OR
      e.employee_number LIKE ?
    )`;
    const searchTerm = `%${search}%`;
    params.push(searchTerm, searchTerm, searchTerm, searchTerm);
  }
  
  // Department filter
  if (department) {
    query += ` AND e.department_id = ?`;
    params.push(department);
  }
  
  // Status filter (HR+ only)
  if (status && canSeeAll) {
    query += ` AND e.status = ?`;
    params.push(status);
  }
  
  // Role filter (HR+ only)
  if (role && canSeeAll) {
    query += ` AND e.role_id = ?`;
    params.push(role);
  }
  
  // Pagination
  const offset = (parseInt(page) - 1) * parseInt(limit);
  query += ` ORDER BY e.last_name, e.first_name LIMIT ? OFFSET ?`;
  params.push(parseInt(limit), offset);
  
  const employees = db.prepare(query).all(...params);
  
  // Get total count
  let countQuery = `SELECT COUNT(*) as total FROM employees e WHERE e.is_active = 1`;
  const countParams = [];
  
  if (!canSeeAll && req.user.roleLevel >= 40) {
    countQuery += ` AND (e.manager_id = ? OR e.id = ?)`;
    countParams.push(req.user.id, req.user.id);
  }
  
  const { total } = db.prepare(countQuery).get(...countParams);
  
  res.json({
    employees,
    pagination: {
      page: parseInt(page),
      limit: parseInt(limit),
      total,
      pages: Math.ceil(total / parseInt(limit))
    }
  });
}));

// ============================================================================
// GET SINGLE EMPLOYEE
// ============================================================================

/**
 * GET /api/employees/:id
 * Get employee by ID
 */
router.get('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  
  // Check permissions
  const isOwnProfile = parseInt(id) === req.user.id;
  const canViewAll = req.user.permissions.includes('employees.view.all');
  const isManager = req.user.roleLevel >= 40;
  
  // Get employee
  const employee = db.prepare(`
    SELECT 
      e.*,
      r.name as role_name,
      r.display_name as role_display_name,
      r.level as role_level,
      d.name as department_name,
      p.title as position_title,
      m.first_name || ' ' || m.last_name as manager_name
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    LEFT JOIN employees m ON e.manager_id = m.id
    WHERE e.id = ?
  `).get(id);
  
  if (!employee) {
    throw Errors.notFound('Employee not found');
  }
  
  // Check access
  if (!isOwnProfile && !canViewAll) {
    // Manager can only see their direct reports
    if (isManager && employee.manager_id !== req.user.id) {
      throw Errors.forbidden('You can only view your direct reports');
    }
    if (!isManager) {
      throw Errors.forbidden('You do not have permission to view this employee');
    }
  }
  
  // Build response based on permissions
  const response = {
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
    manager: employee.manager_id ? {
      id: employee.manager_id,
      name: employee.manager_name
    } : null,
    hireDate: employee.hire_date,
    employmentType: employee.employment_type,
    workLocation: employee.work_location,
    status: employee.status
  };
  
  // Include sensitive fields only for HR+ or own profile
  if (isOwnProfile || canViewAll) {
    response.personalEmail = employee.personal_email;
    response.dateOfBirth = employee.date_of_birth;
    
    // Get emergency contacts
    const emergencyContacts = db.prepare(`
      SELECT id, name, relationship, phone, email, is_primary
      FROM employee_emergency_contacts
      WHERE employee_id = ?
      ORDER BY is_primary DESC
    `).all(id);
    
    response.emergencyContacts = emergencyContacts;
  }
  
  res.json(response);
}));

// ============================================================================
// UPDATE EMPLOYEE (Self)
// ============================================================================

/**
 * PATCH /api/employees/:id
 * Update employee - self-service or HR
 */
router.patch('/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const isOwnProfile = parseInt(id) === req.user.id;
  const canEditAll = req.user.permissions.includes('employees.edit.all');
  
  if (!isOwnProfile && !canEditAll) {
    throw Errors.forbidden('You do not have permission to edit this employee');
  }
  
  // Fields that employees can edit themselves
  const selfEditableFields = [
    'preferred_name', 'phone', 'personal_email', 'avatar_url'
  ];
  
  // Fields that HR can edit
  const hrEditableFields = [
    ...selfEditableFields,
    'first_name', 'last_name', 'email', 'employee_number',
    'department_id', 'position_id', 'manager_id',
    'hire_date', 'employment_type', 'work_location', 'status'
  ];
  
  const allowedFields = canEditAll ? hrEditableFields : selfEditableFields;
  
  // Build update query
  const updates = [];
  const values = [];
  
  for (const [key, value] of Object.entries(req.body)) {
    // Convert camelCase to snake_case
    const dbKey = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
    
    if (allowedFields.includes(dbKey)) {
      updates.push(`${dbKey} = ?`);
      values.push(value);
    }
  }
  
  if (updates.length === 0) {
    throw Errors.badRequest('No valid fields to update');
  }
  
  // Get old values for audit
  const oldEmployee = db.prepare(`SELECT * FROM employees WHERE id = ?`).get(id);
  
  // Update employee
  updates.push('updated_at = CURRENT_TIMESTAMP');
  values.push(id);
  
  db.prepare(`
    UPDATE employees SET ${updates.join(', ')} WHERE id = ?
  `).run(...values);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, old_values, new_values, ip_address
    )
    VALUES (?, ?, ?, 'update', 'employee', ?, ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    JSON.stringify(oldEmployee),
    JSON.stringify(req.body),
    req.ip
  );
  
  // Return updated employee
  const updated = db.prepare(`
    SELECT 
      e.*,
      r.display_name as role_display_name,
      d.name as department_name,
      p.title as position_title
    FROM employees e
    JOIN roles r ON e.role_id = r.id
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN positions p ON e.position_id = p.id
    WHERE e.id = ?
  `).get(id);
  
  res.json({
    success: true,
    employee: {
      id: updated.id,
      email: updated.email,
      firstName: updated.first_name,
      lastName: updated.last_name,
      preferredName: updated.preferred_name,
      phone: updated.phone,
      avatarUrl: updated.avatar_url,
      status: updated.status
    }
  });
}));

// ============================================================================
// CREATE EMPLOYEE (HR Only)
// ============================================================================

/**
 * POST /api/employees
 * Create new employee - HR only
 */
router.post('/', hasPermission('employees.create'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const {
    email,
    firstName,
    lastName,
    roleId,
    departmentId,
    positionId,
    managerId,
    hireDate,
    employmentType = 'full_time',
    workLocation = 'office'
  } = req.body;
  
  // Validate required fields
  if (!email || !firstName || !lastName || !roleId) {
    throw Errors.badRequest('Email, first name, last name, and role are required');
  }
  
  // Check for duplicate email
  const existing = db.prepare(`SELECT id FROM employees WHERE LOWER(email) = LOWER(?)`).get(email);
  if (existing) {
    throw Errors.conflict('An employee with this email already exists');
  }
  
  // Generate employee number
  const lastEmployee = db.prepare(`
    SELECT employee_number FROM employees 
    WHERE employee_number LIKE 'EMP%' 
    ORDER BY id DESC LIMIT 1
  `).get();
  
  let employeeNumber = 'EMP001';
  if (lastEmployee) {
    const num = parseInt(lastEmployee.employee_number.replace('EMP', '')) + 1;
    employeeNumber = `EMP${num.toString().padStart(3, '0')}`;
  }
  
  // Generate temporary password
  const tempPassword = Math.random().toString(36).slice(-8);
  const bcrypt = await import('bcrypt');
  const passwordHash = await bcrypt.hash(tempPassword, 12);
  
  // Insert employee
  const result = db.prepare(`
    INSERT INTO employees (
      email, first_name, last_name, employee_number, password_hash,
      role_id, department_id, position_id, manager_id,
      hire_date, employment_type, work_location,
      must_change_password, status, is_active, created_by
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'active', 1, ?)
  `).run(
    email, firstName, lastName, employeeNumber, passwordHash,
    roleId, departmentId || null, positionId || null, managerId || null,
    hireDate || null, employmentType, workLocation,
    req.user.id
  );
  
  const newEmployeeId = result.lastInsertRowid;
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, description, ip_address
    )
    VALUES (?, ?, ?, 'create', 'employee', ?, ?, 'New employee created', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    newEmployeeId,
    `${firstName} ${lastName}`,
    req.ip
  );
  
  // TODO: Send welcome email with temporary password
  console.log(`[Employee] Created ${email} with temp password: ${tempPassword}`);
  
  res.status(201).json({
    success: true,
    employee: {
      id: newEmployeeId,
      email,
      firstName,
      lastName,
      employeeNumber
    },
    // Only include temp password in development
    ...(process.env.NODE_ENV === 'development' && { tempPassword })
  });
}));

// ============================================================================
// DEACTIVATE EMPLOYEE (HR Only)
// ============================================================================

/**
 * POST /api/employees/:id/deactivate
 * Deactivate an employee
 */
router.post('/:id/deactivate', hasPermission('employees.deactivate'), asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { reason } = req.body;
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  // Can't deactivate yourself
  if (parseInt(id) === req.user.id) {
    throw Errors.badRequest('You cannot deactivate your own account');
  }
  
  // Get employee
  const employee = db.prepare(`SELECT * FROM employees WHERE id = ?`).get(id);
  if (!employee) {
    throw Errors.notFound('Employee not found');
  }
  
  // Deactivate
  db.prepare(`
    UPDATE employees 
    SET is_active = 0, status = 'inactive', updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(id);
  
  // Revoke all sessions
  db.prepare(`UPDATE sessions SET is_active = 0 WHERE employee_id = ?`).run(id);
  
  // Revoke all VPN devices
  db.prepare(`UPDATE vpn_devices SET is_active = 0 WHERE employee_id = ?`).run(id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, description, ip_address
    )
    VALUES (?, ?, ?, 'deactivate', 'employee', ?, ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    `${employee.first_name} ${employee.last_name}`,
    reason || 'Employee deactivated',
    req.ip
  );
  
  res.json({ success: true, message: 'Employee deactivated' });
}));

// ============================================================================
// EMERGENCY CONTACTS
// ============================================================================

/**
 * POST /api/employees/:id/emergency-contacts
 * Add emergency contact
 */
router.post('/:id/emergency-contacts', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  
  // Can only add to own profile or with HR permission
  const isOwnProfile = parseInt(id) === req.user.id;
  const canEditAll = req.user.permissions.includes('employees.edit.all');
  
  if (!isOwnProfile && !canEditAll) {
    throw Errors.forbidden('You cannot add emergency contacts for this employee');
  }
  
  const { name, relationship, phone, email, isPrimary } = req.body;
  
  if (!name || !phone) {
    throw Errors.badRequest('Name and phone are required');
  }
  
  // If setting as primary, unset others
  if (isPrimary) {
    db.prepare(`
      UPDATE employee_emergency_contacts SET is_primary = 0 WHERE employee_id = ?
    `).run(id);
  }
  
  const result = db.prepare(`
    INSERT INTO employee_emergency_contacts (employee_id, name, relationship, phone, email, is_primary)
    VALUES (?, ?, ?, ?, ?, ?)
  `).run(id, name, relationship, phone, email, isPrimary ? 1 : 0);
  
  res.status(201).json({
    success: true,
    contact: {
      id: result.lastInsertRowid,
      name,
      relationship,
      phone,
      email,
      isPrimary: isPrimary || false
    }
  });
}));

/**
 * DELETE /api/employees/:id/emergency-contacts/:contactId
 * Delete emergency contact
 */
router.delete('/:id/emergency-contacts/:contactId', asyncHandler(async (req, res) => {
  const { id, contactId } = req.params;
  const db = getDb('company');
  
  const isOwnProfile = parseInt(id) === req.user.id;
  const canEditAll = req.user.permissions.includes('employees.edit.all');
  
  if (!isOwnProfile && !canEditAll) {
    throw Errors.forbidden('You cannot delete emergency contacts for this employee');
  }
  
  db.prepare(`
    DELETE FROM employee_emergency_contacts WHERE id = ? AND employee_id = ?
  `).run(contactId, id);
  
  res.json({ success: true });
}));

export default router;
