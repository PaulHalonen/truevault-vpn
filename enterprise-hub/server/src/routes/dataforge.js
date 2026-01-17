/**
 * DataForge Routes
 * 
 * Custom database builder - create tables, fields, and records
 */

import { Router } from 'express';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { hasPermission } from '../middleware/auth.js';

const router = Router();

// ============================================================================
// LIST MY TABLES
// ============================================================================

/**
 * GET /api/dataforge/tables
 * List user's tables and shared tables
 */
router.get('/tables', asyncHandler(async (req, res) => {
  const db = getDb('dataforge');
  
  // Get tables user created or has access to
  const tables = db.prepare(`
    SELECT DISTINCT
      t.id,
      t.name,
      t.slug,
      t.description,
      t.icon,
      t.color,
      t.record_count,
      t.created_by,
      t.created_at,
      CASE WHEN t.created_by = ? THEN 'owner' 
           ELSE COALESCE(p.permission_level, 'view') END as access_level
    FROM df_tables t
    LEFT JOIN df_table_permissions p ON t.id = p.table_id 
      AND (p.employee_id = ? OR p.role_id = ?)
    WHERE t.is_active = 1 
      AND (t.created_by = ? OR p.id IS NOT NULL)
    ORDER BY t.name
  `).all(req.user.id, req.user.id, req.user.roleId, req.user.id);
  
  res.json(tables);
}));

// ============================================================================
// GET SINGLE TABLE
// ============================================================================

/**
 * GET /api/dataforge/tables/:slug
 * Get table details with fields
 */
router.get('/tables/:slug', asyncHandler(async (req, res) => {
  const { slug } = req.params;
  const db = getDb('dataforge');
  
  const table = db.prepare(`
    SELECT * FROM df_tables WHERE slug = ? AND is_active = 1
  `).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  // Check access
  const hasAccess = checkTableAccess(db, table.id, req.user);
  if (!hasAccess) {
    throw Errors.forbidden('You do not have access to this table');
  }
  
  // Get fields
  const fields = db.prepare(`
    SELECT * FROM df_fields 
    WHERE table_id = ? 
    ORDER BY display_order, id
  `).all(table.id);
  
  res.json({
    ...table,
    fields
  });
}));

// ============================================================================
// CREATE TABLE
// ============================================================================

/**
 * POST /api/dataforge/tables
 * Create a new table
 */
router.post('/tables', hasPermission('dataforge.tables.create'), asyncHandler(async (req, res) => {
  const db = getDb('dataforge');
  const auditDb = getDb('audit');
  
  const { name, description, icon = '📋', color = '#3B82F6', fields = [] } = req.body;
  
  if (!name) {
    throw Errors.badRequest('Table name is required');
  }
  
  // Generate slug
  const slug = name.toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_|_$/g, '') + '_' + Date.now().toString(36);
  
  // Create table
  const result = db.prepare(`
    INSERT INTO df_tables (name, slug, description, icon, color, created_by)
    VALUES (?, ?, ?, ?, ?, ?)
  `).run(name, slug, description, icon, color, req.user.id);
  
  const tableId = result.lastInsertRowid;
  
  // Add default fields if no fields provided
  const defaultFields = fields.length > 0 ? fields : [
    { name: 'Name', slug: 'name', field_type: 'text', is_required: true },
    { name: 'Notes', slug: 'notes', field_type: 'textarea' }
  ];
  
  // Insert fields
  const insertField = db.prepare(`
    INSERT INTO df_fields (
      table_id, name, slug, field_type, description,
      display_order, is_required, is_visible, options
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
  `);
  
  defaultFields.forEach((field, index) => {
    const fieldSlug = field.slug || field.name.toLowerCase().replace(/[^a-z0-9]+/g, '_');
    insertField.run(
      tableId,
      field.name,
      fieldSlug,
      field.field_type || 'text',
      field.description || null,
      index,
      field.is_required ? 1 : 0,
      field.options ? JSON.stringify(field.options) : null
    );
  });
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, ip_address
    )
    VALUES (?, ?, ?, 'create', 'dataforge_table', ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    tableId,
    name,
    req.ip
  );
  
  res.status(201).json({
    success: true,
    table: {
      id: tableId,
      name,
      slug
    }
  });
}));

// ============================================================================
// UPDATE TABLE
// ============================================================================

/**
 * PATCH /api/dataforge/tables/:slug
 * Update table settings
 */
router.patch('/tables/:slug', asyncHandler(async (req, res) => {
  const { slug } = req.params;
  const db = getDb('dataforge');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ?`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  // Check ownership
  if (table.created_by !== req.user.id) {
    const permission = getTablePermission(db, table.id, req.user);
    if (permission !== 'full') {
      throw Errors.forbidden('You do not have permission to edit this table');
    }
  }
  
  const { name, description, icon, color } = req.body;
  
  db.prepare(`
    UPDATE df_tables 
    SET name = COALESCE(?, name),
        description = COALESCE(?, description),
        icon = COALESCE(?, icon),
        color = COALESCE(?, color),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(name, description, icon, color, table.id);
  
  res.json({ success: true });
}));

// ============================================================================
// DELETE TABLE
// ============================================================================

/**
 * DELETE /api/dataforge/tables/:slug
 * Delete a table (soft delete)
 */
router.delete('/tables/:slug', asyncHandler(async (req, res) => {
  const { slug } = req.params;
  const db = getDb('dataforge');
  const auditDb = getDb('audit');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ?`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  // Only owner can delete
  if (table.created_by !== req.user.id) {
    throw Errors.forbidden('Only the table owner can delete it');
  }
  
  // Soft delete
  db.prepare(`UPDATE df_tables SET is_active = 0 WHERE id = ?`).run(table.id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, ip_address
    )
    VALUES (?, ?, ?, 'delete', 'dataforge_table', ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    table.id,
    table.name,
    req.ip
  );
  
  res.json({ success: true });
}));

// ============================================================================
// RECORDS - LIST
// ============================================================================

/**
 * GET /api/dataforge/tables/:slug/records
 * List records in a table
 */
router.get('/tables/:slug/records', asyncHandler(async (req, res) => {
  const { slug } = req.params;
  const { page = 1, limit = 50, sort, order = 'asc', search } = req.query;
  const db = getDb('dataforge');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ? AND is_active = 1`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  // Check access
  if (!checkTableAccess(db, table.id, req.user)) {
    throw Errors.forbidden('You do not have access to this table');
  }
  
  // Get records
  let query = `SELECT * FROM df_records WHERE table_id = ?`;
  const params = [table.id];
  
  // Search (searches in JSON data)
  if (search) {
    query += ` AND data LIKE ?`;
    params.push(`%${search}%`);
  }
  
  // Sort
  if (sort) {
    query += ` ORDER BY json_extract(data, '$.${sort}') ${order === 'desc' ? 'DESC' : 'ASC'}`;
  } else {
    query += ` ORDER BY created_at DESC`;
  }
  
  // Pagination
  const offset = (parseInt(page) - 1) * parseInt(limit);
  query += ` LIMIT ? OFFSET ?`;
  params.push(parseInt(limit), offset);
  
  const records = db.prepare(query).all(...params);
  
  // Parse JSON data
  const parsedRecords = records.map(r => ({
    id: r.id,
    ...JSON.parse(r.data),
    _createdAt: r.created_at,
    _updatedAt: r.updated_at,
    _createdBy: r.created_by
  }));
  
  // Get total count
  const { total } = db.prepare(`SELECT COUNT(*) as total FROM df_records WHERE table_id = ?`).get(table.id);
  
  res.json({
    records: parsedRecords,
    pagination: {
      page: parseInt(page),
      limit: parseInt(limit),
      total,
      pages: Math.ceil(total / parseInt(limit))
    }
  });
}));

// ============================================================================
// RECORDS - CREATE
// ============================================================================

/**
 * POST /api/dataforge/tables/:slug/records
 * Create a new record
 */
router.post('/tables/:slug/records', asyncHandler(async (req, res) => {
  const { slug } = req.params;
  const db = getDb('dataforge');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ? AND is_active = 1`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  // Check permission (need edit access)
  const permission = getTablePermission(db, table.id, req.user);
  if (!permission || permission === 'view') {
    throw Errors.forbidden('You do not have permission to add records');
  }
  
  // Get fields for validation
  const fields = db.prepare(`SELECT * FROM df_fields WHERE table_id = ?`).all(table.id);
  
  // Validate required fields
  for (const field of fields) {
    if (field.is_required && !req.body[field.slug]) {
      throw Errors.badRequest(`${field.name} is required`);
    }
  }
  
  // Insert record
  const result = db.prepare(`
    INSERT INTO df_records (table_id, data, created_by, updated_by)
    VALUES (?, ?, ?, ?)
  `).run(table.id, JSON.stringify(req.body), req.user.id, req.user.id);
  
  // Update record count
  db.prepare(`UPDATE df_tables SET record_count = record_count + 1 WHERE id = ?`).run(table.id);
  
  // Broadcast update via WebSocket
  const io = req.app.get('io');
  if (io) {
    io.to(`table:${table.id}`).emit('record:created', {
      tableId: table.id,
      recordId: result.lastInsertRowid
    });
  }
  
  res.status(201).json({
    success: true,
    record: {
      id: result.lastInsertRowid,
      ...req.body
    }
  });
}));

// ============================================================================
// RECORDS - UPDATE
// ============================================================================

/**
 * PATCH /api/dataforge/tables/:slug/records/:id
 * Update a record
 */
router.patch('/tables/:slug/records/:id', asyncHandler(async (req, res) => {
  const { slug, id } = req.params;
  const db = getDb('dataforge');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ? AND is_active = 1`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  const permission = getTablePermission(db, table.id, req.user);
  if (!permission || permission === 'view') {
    throw Errors.forbidden('You do not have permission to edit records');
  }
  
  const record = db.prepare(`SELECT * FROM df_records WHERE id = ? AND table_id = ?`).get(id, table.id);
  
  if (!record) {
    throw Errors.notFound('Record not found');
  }
  
  // Merge existing data with updates
  const existingData = JSON.parse(record.data);
  const updatedData = { ...existingData, ...req.body };
  
  db.prepare(`
    UPDATE df_records 
    SET data = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
    WHERE id = ?
  `).run(JSON.stringify(updatedData), req.user.id, id);
  
  // Broadcast update
  const io = req.app.get('io');
  if (io) {
    io.to(`table:${table.id}`).emit('record:updated', {
      tableId: table.id,
      recordId: parseInt(id)
    });
  }
  
  res.json({ success: true, record: { id: parseInt(id), ...updatedData } });
}));

// ============================================================================
// RECORDS - DELETE
// ============================================================================

/**
 * DELETE /api/dataforge/tables/:slug/records/:id
 * Delete a record
 */
router.delete('/tables/:slug/records/:id', asyncHandler(async (req, res) => {
  const { slug, id } = req.params;
  const db = getDb('dataforge');
  
  const table = db.prepare(`SELECT * FROM df_tables WHERE slug = ? AND is_active = 1`).get(slug);
  
  if (!table) {
    throw Errors.notFound('Table not found');
  }
  
  const permission = getTablePermission(db, table.id, req.user);
  if (permission !== 'full' && table.created_by !== req.user.id) {
    throw Errors.forbidden('You do not have permission to delete records');
  }
  
  db.prepare(`DELETE FROM df_records WHERE id = ? AND table_id = ?`).run(id, table.id);
  
  // Update record count
  db.prepare(`UPDATE df_tables SET record_count = record_count - 1 WHERE id = ? AND record_count > 0`).run(table.id);
  
  res.json({ success: true });
}));

// ============================================================================
// TEMPLATES
// ============================================================================

/**
 * GET /api/dataforge/templates
 * List available templates
 */
router.get('/templates', asyncHandler(async (req, res) => {
  const db = getDb('dataforge');
  const { category } = req.query;
  
  let query = `SELECT * FROM df_templates WHERE is_active = 1`;
  const params = [];
  
  if (category) {
    query += ` AND category = ?`;
    params.push(category);
  }
  
  query += ` ORDER BY is_featured DESC, use_count DESC, name`;
  
  const templates = db.prepare(query).all(...params);
  
  // Get unique categories
  const categories = db.prepare(`
    SELECT DISTINCT category FROM df_templates WHERE is_active = 1 ORDER BY category
  `).all().map(c => c.category);
  
  res.json({ templates, categories });
}));

/**
 * POST /api/dataforge/templates/:id/use
 * Create a table from template
 */
router.post('/templates/:id/use', hasPermission('dataforge.tables.create'), asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { name } = req.body;
  const db = getDb('dataforge');
  
  const template = db.prepare(`SELECT * FROM df_templates WHERE id = ?`).get(id);
  
  if (!template) {
    throw Errors.notFound('Template not found');
  }
  
  const tableName = name || template.name;
  const slug = tableName.toLowerCase().replace(/[^a-z0-9]+/g, '_') + '_' + Date.now().toString(36);
  
  // Create table
  const result = db.prepare(`
    INSERT INTO df_tables (name, slug, description, icon, color, template_id, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `).run(tableName, slug, template.description, template.icon, template.color, template.id, req.user.id);
  
  const tableId = result.lastInsertRowid;
  
  // Create fields from template
  const fieldsDefinition = JSON.parse(template.fields_definition);
  const insertField = db.prepare(`
    INSERT INTO df_fields (
      table_id, name, slug, field_type, description,
      display_order, is_required, options, related_table_id
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  `);
  
  fieldsDefinition.forEach((field, index) => {
    insertField.run(
      tableId,
      field.name,
      field.slug,
      field.field_type,
      field.description,
      index,
      field.is_required ? 1 : 0,
      field.options ? JSON.stringify(field.options) : null,
      field.related_table_id
    );
  });
  
  // Increment template use count
  db.prepare(`UPDATE df_templates SET use_count = use_count + 1 WHERE id = ?`).run(id);
  
  res.status(201).json({
    success: true,
    table: {
      id: tableId,
      name: tableName,
      slug
    }
  });
}));

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function checkTableAccess(db, tableId, user) {
  // Owner always has access
  const table = db.prepare(`SELECT created_by FROM df_tables WHERE id = ?`).get(tableId);
  if (table && table.created_by === user.id) return true;
  
  // Check explicit permissions
  const permission = db.prepare(`
    SELECT permission_level FROM df_table_permissions
    WHERE table_id = ? AND (employee_id = ? OR role_id = ?)
    LIMIT 1
  `).get(tableId, user.id, user.roleId);
  
  return !!permission;
}

function getTablePermission(db, tableId, user) {
  // Owner has full access
  const table = db.prepare(`SELECT created_by FROM df_tables WHERE id = ?`).get(tableId);
  if (table && table.created_by === user.id) return 'full';
  
  // Check explicit permissions
  const permission = db.prepare(`
    SELECT permission_level FROM df_table_permissions
    WHERE table_id = ? AND (employee_id = ? OR role_id = ?)
    ORDER BY 
      CASE permission_level 
        WHEN 'full' THEN 1 
        WHEN 'edit' THEN 2 
        WHEN 'view' THEN 3 
      END
    LIMIT 1
  `).get(tableId, user.id, user.roleId);
  
  return permission?.permission_level || null;
}

export default router;
