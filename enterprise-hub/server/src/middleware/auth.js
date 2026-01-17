/**
 * Authentication Middleware
 * 
 * Verifies JWT tokens and attaches user to request
 */

import jwt from 'jsonwebtoken';
import { getDb } from '../config/database.js';

const JWT_SECRET = process.env.JWT_SECRET || 'development-secret-change-in-production';

/**
 * Main authentication middleware
 * Verifies JWT token from Authorization header
 */
export function authMiddleware(req, res, next) {
  try {
    // Get token from header
    const authHeader = req.headers.authorization;
    
    if (!authHeader) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'No authorization header provided'
      });
    }
    
    // Check Bearer format
    const parts = authHeader.split(' ');
    if (parts.length !== 2 || parts[0] !== 'Bearer') {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Invalid authorization header format. Use: Bearer <token>'
      });
    }
    
    const token = parts[1];
    
    // Verify token
    let decoded;
    try {
      decoded = jwt.verify(token, JWT_SECRET);
    } catch (err) {
      if (err.name === 'TokenExpiredError') {
        return res.status(401).json({
          error: 'Unauthorized',
          message: 'Token has expired',
          code: 'TOKEN_EXPIRED'
        });
      }
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Invalid token'
      });
    }
    
    // Get user from database
    const db = getDb('company');
    const employee = db.prepare(`
      SELECT 
        e.id,
        e.email,
        e.first_name,
        e.last_name,
        e.employee_number,
        e.role_id,
        e.department_id,
        e.manager_id,
        e.status,
        e.is_active,
        r.name as role_name,
        r.level as role_level,
        r.display_name as role_display_name
      FROM employees e
      JOIN roles r ON e.role_id = r.id
      WHERE e.id = ? AND e.is_active = 1
    `).get(decoded.employeeId);
    
    if (!employee) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'User not found or inactive'
      });
    }
    
    // Get user permissions
    const permissions = db.prepare(`
      SELECT p.name
      FROM permissions p
      JOIN role_permissions rp ON p.id = rp.permission_id
      WHERE rp.role_id = ?
    `).all(employee.role_id).map(p => p.name);
    
    // Attach user to request
    req.user = {
      id: employee.id,
      email: employee.email,
      firstName: employee.first_name,
      lastName: employee.last_name,
      employeeNumber: employee.employee_number,
      roleId: employee.role_id,
      roleName: employee.role_name,
      roleLevel: employee.role_level,
      roleDisplayName: employee.role_display_name,
      departmentId: employee.department_id,
      managerId: employee.manager_id,
      permissions: permissions
    };
    
    // Update last activity in session (optional - for session tracking)
    if (decoded.sessionId) {
      db.prepare(`
        UPDATE sessions 
        SET last_used_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND is_active = 1
      `).run(decoded.sessionId);
    }
    
    next();
    
  } catch (error) {
    console.error('[Auth] Middleware error:', error);
    return res.status(500).json({
      error: 'Internal Server Error',
      message: 'Authentication failed'
    });
  }
}

/**
 * Check if user has a specific permission
 */
export function hasPermission(permission) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Not authenticated'
      });
    }
    
    if (!req.user.permissions.includes(permission)) {
      return res.status(403).json({
        error: 'Forbidden',
        message: `Missing required permission: ${permission}`
      });
    }
    
    next();
  };
}

/**
 * Check if user has any of the specified permissions
 */
export function hasAnyPermission(permissions) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Not authenticated'
      });
    }
    
    const hasAny = permissions.some(p => req.user.permissions.includes(p));
    
    if (!hasAny) {
      return res.status(403).json({
        error: 'Forbidden',
        message: `Missing required permissions. Need one of: ${permissions.join(', ')}`
      });
    }
    
    next();
  };
}

/**
 * Check if user has all of the specified permissions
 */
export function hasAllPermissions(permissions) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Not authenticated'
      });
    }
    
    const hasAll = permissions.every(p => req.user.permissions.includes(p));
    
    if (!hasAll) {
      return res.status(403).json({
        error: 'Forbidden',
        message: `Missing required permissions: ${permissions.join(', ')}`
      });
    }
    
    next();
  };
}

/**
 * Check if user has minimum role level
 */
export function requireRoleLevel(minLevel) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({
        error: 'Unauthorized',
        message: 'Not authenticated'
      });
    }
    
    if (req.user.roleLevel < minLevel) {
      return res.status(403).json({
        error: 'Forbidden',
        message: 'Insufficient role level'
      });
    }
    
    next();
  };
}

/**
 * Check if user is owner (role level 100)
 */
export function requireOwner(req, res, next) {
  if (!req.user || req.user.roleLevel < 100) {
    return res.status(403).json({
      error: 'Forbidden',
      message: 'Owner access required'
    });
  }
  next();
}

/**
 * Check if user is admin or higher (role level 80+)
 */
export function requireAdmin(req, res, next) {
  if (!req.user || req.user.roleLevel < 80) {
    return res.status(403).json({
      error: 'Forbidden',
      message: 'Admin access required'
    });
  }
  next();
}

/**
 * Check if user is HR or higher (role level 50+)
 */
export function requireHR(req, res, next) {
  if (!req.user || req.user.roleLevel < 50) {
    return res.status(403).json({
      error: 'Forbidden',
      message: 'HR access required'
    });
  }
  next();
}

/**
 * Check if user is manager or higher (role level 40+)
 */
export function requireManager(req, res, next) {
  if (!req.user || req.user.roleLevel < 40) {
    return res.status(403).json({
      error: 'Forbidden',
      message: 'Manager access required'
    });
  }
  next();
}

export default {
  authMiddleware,
  hasPermission,
  hasAnyPermission,
  hasAllPermissions,
  requireRoleLevel,
  requireOwner,
  requireAdmin,
  requireHR,
  requireManager
};
