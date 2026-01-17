/**
 * Database Configuration
 * 
 * Manages SQLite database connections using better-sqlite3
 * Four separate databases for isolation:
 * - company.db: Core employee, roles, departments
 * - hr.db: HR-sensitive data (compensation, reviews)
 * - dataforge.db: Custom user tables
 * - audit.db: Audit logs and notifications
 */

import Database from 'better-sqlite3';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Database connections
let databases = {
  company: null,
  hr: null,
  dataforge: null,
  audit: null
};

/**
 * Get the data directory path
 */
function getDataDir() {
  const dataDir = process.env.DB_DIR || path.join(__dirname, '../../data');
  
  // Create directory if it doesn't exist
  if (!fs.existsSync(dataDir)) {
    fs.mkdirSync(dataDir, { recursive: true });
  }
  
  return dataDir;
}

/**
 * Initialize a single database with its schema
 */
function initDatabase(name, schemaFile) {
  const dataDir = getDataDir();
  const dbPath = path.join(dataDir, `${name}.db`);
  const schemaPath = path.join(__dirname, '../database', schemaFile);
  
  console.log(`[DB] Initializing ${name}.db at ${dbPath}`);
  
  // Create database connection
  const db = new Database(dbPath);
  
  // Enable WAL mode for better concurrency
  db.pragma('journal_mode = WAL');
  
  // Enable foreign keys
  db.pragma('foreign_keys = ON');
  
  // Read and execute schema if database is new
  const tableCheck = db.prepare("SELECT name FROM sqlite_master WHERE type='table' LIMIT 1").get();
  
  if (!tableCheck) {
    console.log(`[DB] Running schema for ${name}.db...`);
    const schema = fs.readFileSync(schemaPath, 'utf-8');
    db.exec(schema);
    console.log(`[DB] Schema applied for ${name}.db`);
  } else {
    console.log(`[DB] ${name}.db already initialized`);
  }
  
  return db;
}

/**
 * Initialize all databases
 */
export async function initializeDatabases() {
  try {
    databases.company = initDatabase('company', 'schema-company.sql');
    databases.hr = initDatabase('hr', 'schema-hr.sql');
    databases.dataforge = initDatabase('dataforge', 'schema-dataforge.sql');
    databases.audit = initDatabase('audit', 'schema-audit.sql');
    
    // Run seed data if roles table is empty
    const roleCount = databases.company.prepare('SELECT COUNT(*) as count FROM roles').get();
    if (roleCount.count === 0) {
      console.log('[DB] Seeding initial data...');
      await seedInitialData();
      console.log('[DB] Initial data seeded');
    }
    
    return true;
  } catch (error) {
    console.error('[DB] Failed to initialize databases:', error);
    throw error;
  }
}

/**
 * Seed initial data (roles, permissions, etc.)
 */
async function seedInitialData() {
  const db = databases.company;
  
  // Insert roles
  const insertRole = db.prepare(`
    INSERT INTO roles (name, display_name, level, description, is_system)
    VALUES (?, ?, ?, ?, 1)
  `);
  
  const roles = [
    ['employee', 'Employee', 20, 'Standard employee with self-service access'],
    ['manager', 'Manager', 40, 'Team lead with direct report management'],
    ['hr_staff', 'HR Staff', 50, 'HR team member with limited HR access'],
    ['hr_admin', 'HR Administrator', 70, 'Full HR access including compensation'],
    ['admin', 'Administrator', 80, 'IT/System administrator'],
    ['super_admin', 'Super Administrator', 90, 'Full system access except billing'],
    ['owner', 'Owner', 100, 'Company owner with full access']
  ];
  
  for (const role of roles) {
    insertRole.run(...role);
  }
  
  // Insert basic permissions
  const insertPerm = db.prepare(`
    INSERT INTO permissions (name, category, description)
    VALUES (?, ?, ?)
  `);
  
  const permissions = [
    // Employee self-service
    ['profile.view.own', 'profile', 'View own profile'],
    ['profile.edit.own', 'profile', 'Edit own profile'],
    ['devices.view.own', 'vpn', 'View own VPN devices'],
    ['devices.manage.own', 'vpn', 'Add/remove own VPN devices'],
    ['timeoff.view.own', 'timeoff', 'View own time-off requests'],
    ['timeoff.request', 'timeoff', 'Submit time-off requests'],
    ['directory.view', 'directory', 'View company directory'],
    ['announcements.view', 'announcements', 'View announcements'],
    ['tasks.view.own', 'tasks', 'View own tasks'],
    ['dataforge.tables.view.own', 'dataforge', 'View own DataForge tables'],
    ['dataforge.tables.create', 'dataforge', 'Create DataForge tables'],
    
    // Manager
    ['team.view', 'team', 'View direct reports'],
    ['timeoff.approve.team', 'timeoff', 'Approve team time-off'],
    ['tasks.assign.team', 'tasks', 'Assign tasks to team'],
    
    // HR
    ['employees.view.all', 'hr', 'View all employees'],
    ['employees.edit.all', 'hr', 'Edit employee information'],
    ['employees.create', 'hr', 'Create new employees'],
    ['timeoff.view.all', 'timeoff', 'View all time-off requests'],
    ['timeoff.approve.all', 'timeoff', 'Approve any time-off request'],
    ['documents.view.all', 'documents', 'View all employee documents'],
    
    // HR Admin
    ['compensation.view', 'compensation', 'View compensation data'],
    ['compensation.edit', 'compensation', 'Edit compensation data'],
    
    // Admin
    ['users.view.all', 'admin', 'View all users'],
    ['users.edit.all', 'admin', 'Edit user accounts'],
    ['users.invite', 'admin', 'Send employee invitations'],
    ['vpn.view.all', 'vpn', 'View all VPN connections'],
    ['vpn.manage.all', 'vpn', 'Manage all VPN devices'],
    ['audit.view', 'admin', 'View audit logs'],
    ['settings.view', 'admin', 'View system settings'],
    ['settings.edit', 'admin', 'Edit system settings'],
    
    // Owner
    ['billing.view', 'owner', 'View billing information'],
    ['branding.manage', 'owner', 'Manage company branding'],
    ['company.settings', 'owner', 'Manage company settings']
  ];
  
  for (const perm of permissions) {
    insertPerm.run(...perm);
  }
  
  // Seed time-off types in HR database
  const hrDb = databases.hr;
  const insertTimeOffType = hrDb.prepare(`
    INSERT INTO time_off_types (name, code, description, color, default_days_per_year, requires_approval)
    VALUES (?, ?, ?, ?, ?, ?)
  `);
  
  const timeOffTypes = [
    ['Vacation', 'PTO', 'Paid time off for vacation', '#10B981', 20, 1],
    ['Sick Leave', 'SICK', 'Paid sick leave', '#EF4444', 10, 0],
    ['Personal Day', 'PERS', 'Personal days', '#8B5CF6', 3, 1],
    ['Bereavement', 'BRV', 'Bereavement leave', '#6B7280', 5, 0],
    ['Unpaid Leave', 'UNPD', 'Unpaid leave of absence', '#9CA3AF', 0, 1]
  ];
  
  for (const type of timeOffTypes) {
    insertTimeOffType.run(...type);
  }
  
  // Insert default company settings
  const insertSetting = db.prepare(`
    INSERT INTO company_settings (key, value, type, category)
    VALUES (?, ?, ?, ?)
  `);
  
  const settings = [
    ['company_name', 'My Company', 'string', 'branding'],
    ['primary_color', '#3B82F6', 'string', 'branding'],
    ['timezone', 'America/New_York', 'string', 'general'],
    ['date_format', 'MM/DD/YYYY', 'string', 'general']
  ];
  
  for (const setting of settings) {
    insertSetting.run(...setting);
  }
}

/**
 * Get a specific database connection
 */
export function getDb(name) {
  if (!databases[name]) {
    throw new Error(`Database '${name}' not initialized`);
  }
  return databases[name];
}

/**
 * Get all database connections
 */
export function getAllDbs() {
  return databases;
}

/**
 * Close all database connections
 */
export function closeAllDbs() {
  for (const [name, db] of Object.entries(databases)) {
    if (db) {
      console.log(`[DB] Closing ${name}.db`);
      db.close();
      databases[name] = null;
    }
  }
}

export default {
  initializeDatabases,
  getDb,
  getAllDbs,
  closeAllDbs
};
