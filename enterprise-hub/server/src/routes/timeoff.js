/**
 * Time-Off Routes
 * 
 * Manage time-off requests, balances, and approvals
 */

import { Router } from 'express';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { hasPermission, hasAnyPermission } from '../middleware/auth.js';

const router = Router();

// ============================================================================
// GET TIME-OFF TYPES
// ============================================================================

/**
 * GET /api/timeoff/types
 * List all time-off types
 */
router.get('/types', asyncHandler(async (req, res) => {
  const hrDb = getDb('hr');
  
  const types = hrDb.prepare(`
    SELECT id, name, code, description, color, default_days_per_year,
           requires_approval, can_carry_over, max_carry_over_days
    FROM time_off_types
    WHERE is_active = 1
    ORDER BY name
  `).all();
  
  res.json(types);
}));

// ============================================================================
// GET MY BALANCES
// ============================================================================

/**
 * GET /api/timeoff/balances
 * Get current user's time-off balances
 */
router.get('/balances', asyncHandler(async (req, res) => {
  const hrDb = getDb('hr');
  const currentYear = new Date().getFullYear();
  
  // Get balances for current year
  const balances = hrDb.prepare(`
    SELECT 
      b.id,
      b.time_off_type_id,
      t.name as type_name,
      t.code as type_code,
      t.color,
      b.entitled_days,
      b.used_days,
      b.pending_days,
      b.carried_over_days,
      b.adjusted_days,
      (b.entitled_days + b.carried_over_days + b.adjusted_days - b.used_days - b.pending_days) as available_days
    FROM time_off_balances b
    JOIN time_off_types t ON b.time_off_type_id = t.id
    WHERE b.employee_id = ? AND b.year = ?
    ORDER BY t.name
  `).all(req.user.id, currentYear);
  
  // If no balances exist, create default ones
  if (balances.length === 0) {
    const types = hrDb.prepare(`
      SELECT id, name, code, color, default_days_per_year
      FROM time_off_types WHERE is_active = 1
    `).all();
    
    const insertBalance = hrDb.prepare(`
      INSERT INTO time_off_balances (employee_id, time_off_type_id, year, entitled_days)
      VALUES (?, ?, ?, ?)
    `);
    
    for (const type of types) {
      insertBalance.run(req.user.id, type.id, currentYear, type.default_days_per_year);
    }
    
    // Fetch the newly created balances
    return res.json(hrDb.prepare(`
      SELECT 
        b.id,
        b.time_off_type_id,
        t.name as type_name,
        t.code as type_code,
        t.color,
        b.entitled_days,
        b.used_days,
        b.pending_days,
        b.carried_over_days,
        b.adjusted_days,
        (b.entitled_days + b.carried_over_days + b.adjusted_days - b.used_days - b.pending_days) as available_days
      FROM time_off_balances b
      JOIN time_off_types t ON b.time_off_type_id = t.id
      WHERE b.employee_id = ? AND b.year = ?
      ORDER BY t.name
    `).all(req.user.id, currentYear));
  }
  
  res.json(balances);
}));

// ============================================================================
// GET MY REQUESTS
// ============================================================================

/**
 * GET /api/timeoff/requests
 * Get current user's time-off requests
 */
router.get('/requests', asyncHandler(async (req, res) => {
  const hrDb = getDb('hr');
  const { status, year } = req.query;
  
  let query = `
    SELECT 
      r.id,
      r.time_off_type_id,
      t.name as type_name,
      t.code as type_code,
      t.color,
      r.start_date,
      r.end_date,
      r.total_days,
      r.half_day_start,
      r.half_day_end,
      r.reason,
      r.status,
      r.reviewed_by,
      r.reviewed_at,
      r.review_notes,
      r.created_at
    FROM time_off_requests r
    JOIN time_off_types t ON r.time_off_type_id = t.id
    WHERE r.employee_id = ?
  `;
  
  const params = [req.user.id];
  
  if (status) {
    query += ` AND r.status = ?`;
    params.push(status);
  }
  
  if (year) {
    query += ` AND strftime('%Y', r.start_date) = ?`;
    params.push(year.toString());
  }
  
  query += ` ORDER BY r.start_date DESC`;
  
  const requests = hrDb.prepare(query).all(...params);
  
  res.json(requests);
}));

// ============================================================================
// CREATE TIME-OFF REQUEST
// ============================================================================

/**
 * POST /api/timeoff/requests
 * Submit a new time-off request
 */
router.post('/requests', asyncHandler(async (req, res) => {
  const hrDb = getDb('hr');
  const companyDb = getDb('company');
  const auditDb = getDb('audit');
  
  const {
    typeId,
    startDate,
    endDate,
    halfDayStart = false,
    halfDayEnd = false,
    reason
  } = req.body;
  
  // Validate required fields
  if (!typeId || !startDate || !endDate) {
    throw Errors.badRequest('Type, start date, and end date are required');
  }
  
  // Validate dates
  const start = new Date(startDate);
  const end = new Date(endDate);
  
  if (start > end) {
    throw Errors.badRequest('End date must be after start date');
  }
  
  if (start < new Date()) {
    throw Errors.badRequest('Cannot request time off in the past');
  }
  
  // Calculate total days (simple calculation - weekdays only would need more logic)
  let totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
  if (halfDayStart) totalDays -= 0.5;
  if (halfDayEnd) totalDays -= 0.5;
  
  // Check time-off type exists
  const type = hrDb.prepare(`SELECT * FROM time_off_types WHERE id = ? AND is_active = 1`).get(typeId);
  if (!type) {
    throw Errors.notFound('Time-off type not found');
  }
  
  // Check balance
  const currentYear = new Date().getFullYear();
  const balance = hrDb.prepare(`
    SELECT * FROM time_off_balances 
    WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
  `).get(req.user.id, typeId, currentYear);
  
  if (balance) {
    const available = balance.entitled_days + balance.carried_over_days + 
                      balance.adjusted_days - balance.used_days - balance.pending_days;
    if (totalDays > available) {
      throw Errors.badRequest(`Insufficient balance. Available: ${available} days, Requested: ${totalDays} days`);
    }
  }
  
  // Check for overlapping requests
  const overlap = hrDb.prepare(`
    SELECT id FROM time_off_requests
    WHERE employee_id = ? 
      AND status != 'denied'
      AND status != 'cancelled'
      AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
  `).get(req.user.id, endDate, startDate, startDate, endDate);
  
  if (overlap) {
    throw Errors.conflict('You already have a time-off request for these dates');
  }
  
  // Create request
  const result = hrDb.prepare(`
    INSERT INTO time_off_requests (
      employee_id, time_off_type_id, start_date, end_date,
      total_days, half_day_start, half_day_end, reason, status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    req.user.id, typeId, startDate, endDate,
    totalDays, halfDayStart ? 1 : 0, halfDayEnd ? 1 : 0, reason,
    type.requires_approval ? 'pending' : 'approved'
  );
  
  // Update pending balance
  if (type.requires_approval) {
    hrDb.prepare(`
      UPDATE time_off_balances 
      SET pending_days = pending_days + ?, updated_at = CURRENT_TIMESTAMP
      WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
    `).run(totalDays, req.user.id, typeId, currentYear);
  } else {
    // Auto-approved - update used days directly
    hrDb.prepare(`
      UPDATE time_off_balances 
      SET used_days = used_days + ?, updated_at = CURRENT_TIMESTAMP
      WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
    `).run(totalDays, req.user.id, typeId, currentYear);
  }
  
  // Get manager to notify
  const employee = companyDb.prepare(`
    SELECT manager_id FROM employees WHERE id = ?
  `).get(req.user.id);
  
  // Create notification for manager
  if (employee.manager_id && type.requires_approval) {
    auditDb.prepare(`
      INSERT INTO notifications (
        employee_id, title, message, type, priority,
        action_url, resource_type, resource_id
      )
      VALUES (?, ?, ?, 'action', 'normal', ?, 'timeoff_request', ?)
    `).run(
      employee.manager_id,
      'Time-Off Request Pending',
      `${req.user.firstName} ${req.user.lastName} requested ${totalDays} days of ${type.name}`,
      `/manager/timeoff/${result.lastInsertRowid}`,
      result.lastInsertRowid
    );
  }
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'create', 'timeoff_request', ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    result.lastInsertRowid,
    `Requested ${totalDays} days of ${type.name} (${startDate} to ${endDate})`,
    req.ip
  );
  
  res.status(201).json({
    success: true,
    request: {
      id: result.lastInsertRowid,
      typeId,
      typeName: type.name,
      startDate,
      endDate,
      totalDays,
      status: type.requires_approval ? 'pending' : 'approved'
    }
  });
}));

// ============================================================================
// CANCEL TIME-OFF REQUEST
// ============================================================================

/**
 * POST /api/timeoff/requests/:id/cancel
 * Cancel a pending time-off request
 */
router.post('/requests/:id/cancel', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { reason } = req.body;
  const hrDb = getDb('hr');
  const auditDb = getDb('audit');
  
  // Get request
  const request = hrDb.prepare(`
    SELECT * FROM time_off_requests WHERE id = ?
  `).get(id);
  
  if (!request) {
    throw Errors.notFound('Time-off request not found');
  }
  
  // Check ownership
  if (request.employee_id !== req.user.id) {
    throw Errors.forbidden('You can only cancel your own requests');
  }
  
  // Can only cancel pending requests
  if (request.status !== 'pending') {
    throw Errors.badRequest('Only pending requests can be cancelled');
  }
  
  // Update request
  hrDb.prepare(`
    UPDATE time_off_requests 
    SET status = 'cancelled', 
        cancelled_at = CURRENT_TIMESTAMP,
        cancellation_reason = ?,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(reason, id);
  
  // Restore pending balance
  const currentYear = new Date(request.start_date).getFullYear();
  hrDb.prepare(`
    UPDATE time_off_balances 
    SET pending_days = pending_days - ?, updated_at = CURRENT_TIMESTAMP
    WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
  `).run(request.total_days, req.user.id, request.time_off_type_id, currentYear);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, description, ip_address
    )
    VALUES (?, ?, ?, 'cancel', 'timeoff_request', ?, 'Time-off request cancelled', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    req.ip
  );
  
  res.json({ success: true, message: 'Request cancelled' });
}));

// ============================================================================
// APPROVE/DENY TIME-OFF REQUEST (Manager/HR)
// ============================================================================

/**
 * POST /api/timeoff/requests/:id/review
 * Approve or deny a time-off request
 */
router.post('/requests/:id/review', 
  hasAnyPermission(['timeoff.approve.team', 'timeoff.approve.all']),
  asyncHandler(async (req, res) => {
    const { id } = req.params;
    const { action, notes } = req.body;
    const hrDb = getDb('hr');
    const companyDb = getDb('company');
    const auditDb = getDb('audit');
    
    if (!['approve', 'deny'].includes(action)) {
      throw Errors.badRequest('Action must be "approve" or "deny"');
    }
    
    // Get request with employee info
    const request = hrDb.prepare(`SELECT * FROM time_off_requests WHERE id = ?`).get(id);
    
    if (!request) {
      throw Errors.notFound('Time-off request not found');
    }
    
    if (request.status !== 'pending') {
      throw Errors.badRequest('Request has already been reviewed');
    }
    
    // Check permission - team approval only for direct reports
    const canApproveAll = req.user.permissions.includes('timeoff.approve.all');
    
    if (!canApproveAll) {
      const employee = companyDb.prepare(`
        SELECT manager_id FROM employees WHERE id = ?
      `).get(request.employee_id);
      
      if (employee.manager_id !== req.user.id) {
        throw Errors.forbidden('You can only approve requests from your direct reports');
      }
    }
    
    const newStatus = action === 'approve' ? 'approved' : 'denied';
    
    // Update request
    hrDb.prepare(`
      UPDATE time_off_requests 
      SET status = ?,
          reviewed_by = ?,
          reviewed_at = CURRENT_TIMESTAMP,
          review_notes = ?,
          updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `).run(newStatus, req.user.id, notes, id);
    
    // Update balances
    const currentYear = new Date(request.start_date).getFullYear();
    
    if (action === 'approve') {
      // Move from pending to used
      hrDb.prepare(`
        UPDATE time_off_balances 
        SET pending_days = pending_days - ?,
            used_days = used_days + ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
      `).run(request.total_days, request.total_days, request.employee_id, request.time_off_type_id, currentYear);
    } else {
      // Return pending days
      hrDb.prepare(`
        UPDATE time_off_balances 
        SET pending_days = pending_days - ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE employee_id = ? AND time_off_type_id = ? AND year = ?
      `).run(request.total_days, request.employee_id, request.time_off_type_id, currentYear);
    }
    
    // Notify employee
    auditDb.prepare(`
      INSERT INTO notifications (
        employee_id, title, message, type, priority,
        action_url, resource_type, resource_id
      )
      VALUES (?, ?, ?, ?, ?, ?, 'timeoff_request', ?)
    `).run(
      request.employee_id,
      action === 'approve' ? 'Time-Off Approved! ✓' : 'Time-Off Denied',
      action === 'approve' 
        ? `Your ${request.total_days} day request has been approved`
        : `Your time-off request was denied${notes ? ': ' + notes : ''}`,
      'info',
      action === 'approve' ? 'normal' : 'high',
      `/my/timeoff`,
      id
    );
    
    // Audit log
    auditDb.prepare(`
      INSERT INTO audit_logs (
        employee_id, employee_email, employee_name, action,
        resource_type, resource_id, description, ip_address
      )
      VALUES (?, ?, ?, ?, 'timeoff_request', ?, ?, ?)
    `).run(
      req.user.id,
      req.user.email,
      `${req.user.firstName} ${req.user.lastName}`,
      action,
      id,
      `Time-off request ${action}d`,
      req.ip
    );
    
    res.json({ success: true, status: newStatus });
  })
);

// ============================================================================
// GET TEAM TIME-OFF (Manager)
// ============================================================================

/**
 * GET /api/timeoff/team
 * Get time-off requests for manager's team
 */
router.get('/team', 
  hasAnyPermission(['timeoff.approve.team', 'timeoff.view.all']),
  asyncHandler(async (req, res) => {
    const hrDb = getDb('hr');
    const companyDb = getDb('company');
    const { status } = req.query;
    
    // Get direct reports (or all if HR)
    const canViewAll = req.user.permissions.includes('timeoff.view.all');
    
    let employeeIds;
    if (canViewAll) {
      employeeIds = companyDb.prepare(`SELECT id FROM employees WHERE is_active = 1`).all().map(e => e.id);
    } else {
      employeeIds = companyDb.prepare(`
        SELECT id FROM employees WHERE manager_id = ? AND is_active = 1
      `).all(req.user.id).map(e => e.id);
    }
    
    if (employeeIds.length === 0) {
      return res.json([]);
    }
    
    let query = `
      SELECT 
        r.*,
        t.name as type_name,
        t.code as type_code,
        t.color
      FROM time_off_requests r
      JOIN time_off_types t ON r.time_off_type_id = t.id
      WHERE r.employee_id IN (${employeeIds.map(() => '?').join(',')})
    `;
    
    const params = [...employeeIds];
    
    if (status) {
      query += ` AND r.status = ?`;
      params.push(status);
    }
    
    query += ` ORDER BY r.created_at DESC`;
    
    const requests = hrDb.prepare(query).all(...params);
    
    // Add employee names
    const enrichedRequests = requests.map(r => {
      const emp = companyDb.prepare(`
        SELECT first_name, last_name, avatar_url FROM employees WHERE id = ?
      `).get(r.employee_id);
      return {
        ...r,
        employeeName: emp ? `${emp.first_name} ${emp.last_name}` : 'Unknown',
        employeeAvatar: emp?.avatar_url
      };
    });
    
    res.json(enrichedRequests);
  })
);

// ============================================================================
// GET TEAM CALENDAR
// ============================================================================

/**
 * GET /api/timeoff/calendar
 * Get time-off calendar for team view
 */
router.get('/calendar', asyncHandler(async (req, res) => {
  const hrDb = getDb('hr');
  const companyDb = getDb('company');
  const { start, end } = req.query;
  
  if (!start || !end) {
    throw Errors.badRequest('Start and end dates are required');
  }
  
  // Get approved time-off in date range
  const requests = hrDb.prepare(`
    SELECT 
      r.id,
      r.employee_id,
      r.start_date,
      r.end_date,
      r.total_days,
      t.name as type_name,
      t.color
    FROM time_off_requests r
    JOIN time_off_types t ON r.time_off_type_id = t.id
    WHERE r.status = 'approved'
      AND r.start_date <= ?
      AND r.end_date >= ?
  `).all(end, start);
  
  // Add employee names
  const enrichedRequests = requests.map(r => {
    const emp = companyDb.prepare(`
      SELECT first_name, last_name FROM employees WHERE id = ?
    `).get(r.employee_id);
    return {
      ...r,
      employeeName: emp ? `${emp.first_name} ${emp.last_name}` : 'Unknown'
    };
  });
  
  res.json(enrichedRequests);
}));

export default router;
