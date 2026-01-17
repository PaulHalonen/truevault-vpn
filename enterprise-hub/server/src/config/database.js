/**
 * Database Configuration - sql.js wrapper
 * 
 * Uses sql.js (pure JavaScript SQLite) with file persistence
 * This works on Windows without requiring Visual Studio build tools
 */

import initSqlJs from 'sql.js';
import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Database instances
const databases = {};
let SQL = null;

// Database paths
const DB_DIR = join(__dirname, '../../data');
const DB_PATHS = {
  company: join(DB_DIR, 'company.db'),
  hr: join(DB_DIR, 'hr.db'),
  dataforge: join(DB_DIR, 'dataforge.db'),
  audit: join(DB_DIR, 'audit.db')
};

// Schema paths
const SCHEMA_DIR = join(__dirname, '../../database');

/**
 * Initialize sql.js and all databases
 */
export async function initializeDatabases() {
  // Initialize sql.js
  SQL = await initSqlJs();
  
  // Ensure data directory exists
  if (!existsSync(DB_DIR)) {
    mkdirSync(DB_DIR, { recursive: true });
    console.log(`[DB] Created data directory: ${DB_DIR}`);
  }
  
  // Initialize each database
  for (const [name, path] of Object.entries(DB_PATHS)) {
    databases[name] = await initializeDatabase(name, path);
  }
  
  // Seed initial data if needed
  await seedInitialData();
  
  console.log('[DB] All databases initialized');
}

/**
 * Initialize a single database
 */
async function initializeDatabase(name, dbPath) {
  let db;
  
  // Load existing database or create new
  if (existsSync(dbPath)) {
    const fileBuffer = readFileSync(dbPath);
    db = new SQL.Database(fileBuffer);
    console.log(`[DB] Loaded existing database: ${name}`);
  } else {
    db = new SQL.Database();
    console.log(`[DB] Created new database: ${name}`);
    
    // Apply schema
    const schemaPath = join(SCHEMA_DIR, `schema-${name}.sql`);
    if (existsSync(schemaPath)) {
      const schema = readFileSync(schemaPath, 'utf-8');
      db.run(schema);
      console.log(`[DB] Applied schema for: ${name}`);
    }
    
    // Save to file
    saveDatabase(name, db);
  }
  
  // Enable foreign keys
  db.run('PRAGMA foreign_keys = ON');
  
  // Wrap with helper methods
  return createDbWrapper(name, db);
}

/**
 * Create a wrapper around sql.js database with better-sqlite3-like API
 */
function createDbWrapper(name, db) {
  const wrapper = {
    name,
    _db: db,
    
    /**
     * Prepare a statement (returns object with run, get, all methods)
     */
    prepare(sql) {
      return {
        run(...params) {
          db.run(sql, params);
          saveDatabase(name, db);
          return {
            changes: db.getRowsModified(),
            lastInsertRowid: getLastInsertRowId(db)
          };
        },
        
        get(...params) {
          const stmt = db.prepare(sql);
          stmt.bind(params);
          if (stmt.step()) {
            const result = stmt.getAsObject();
            stmt.free();
            return result;
          }
          stmt.free();
          return undefined;
        },
        
        all(...params) {
          const results = [];
          const stmt = db.prepare(sql);
          stmt.bind(params);
          while (stmt.step()) {
            results.push(stmt.getAsObject());
          }
          stmt.free();
          return results;
        }
      };
    },
    
    /**
     * Execute raw SQL
     */
    exec(sql) {
      db.exec(sql);
      saveDatabase(name, db);
    },
    
    /**
     * Run SQL with parameters
     */
    run(sql, ...params) {
      db.run(sql, params.flat());
      saveDatabase(name, db);
      return {
        changes: db.getRowsModified(),
        lastInsertRowid: getLastInsertRowId(db)
      };
    }
  };
  
  return wrapper;
}

/**
 * Get last insert row ID
 */
function getLastInsertRowId(db) {
  const result = db.exec('SELECT last_insert_rowid() as id');
  if (result.length > 0 && result[0].values.length > 0) {
    return result[0].values[0][0];
  }
  return 0;
}

/**
 * Save database to file
 */
function saveDatabase(name, db) {
  const data = db.export();
  const buffer = Buffer.from(data);
  writeFileSync(DB_PATHS[name], buffer);
}

/**
 * Get a specific database
 */
export function getDb(name) {
  if (!databases[name]) {
    throw new Error(`Database '${name}' not initialized`);
  }
  return databases[name];
}

/**
 * Get all databases
 */
export function getAllDbs() {
  return databases;
}

/**
 * Close all databases
 */
export function closeAllDbs() {
  for (const [name, db] of Object.entries(databases)) {
    if (db._db) {
      // Save before closing
      saveDatabase(name, db._db);
      db._db.close();
    }
  }
  console.log('[DB] All databases closed');
}

/**
 * Seed initial data if databases are empty
 */
async function seedInitialData() {
  const companyDb = databases.company;
  const hrDb = databases.hr;
  const dataforgeDb = databases.dataforge;
  
  // Check if roles exist
  const roleCount = companyDb.prepare('SELECT COUNT(*) as count FROM roles').get();
  
  if (roleCount.count === 0) {
    console.log('[DB] Seeding initial data...');
    
    // Read and execute seed data
    const seedPath = join(SCHEMA_DIR, 'seed-data.sql');
    if (existsSync(seedPath)) {
      const seedSql = readFileSync(seedPath, 'utf-8');
      
      // Split by semicolon and execute each statement
      const statements = seedSql
        .split(';')
        .map(s => s.trim())
        .filter(s => s.length > 0 && !s.startsWith('--'));
      
      for (const stmt of statements) {
        try {
          // Determine which database based on table name
          if (stmt.includes('time_off_types') || stmt.includes('document_categories') || stmt.includes('onboarding_templates')) {
            hrDb._db.run(stmt);
          } else if (stmt.includes('df_templates')) {
            dataforgeDb._db.run(stmt);
          } else {
            companyDb._db.run(stmt);
          }
        } catch (err) {
          // Ignore errors for individual statements (some might fail due to order)
          console.log(`[DB] Seed statement skipped: ${err.message.substring(0, 50)}...`);
        }
      }
      
      // Save all databases
      saveDatabase('company', companyDb._db);
      saveDatabase('hr', hrDb._db);
      saveDatabase('dataforge', dataforgeDb._db);
      
      console.log('[DB] Initial data seeded');
    }
  }
}

export default {
  initializeDatabases,
  getDb,
  getAllDbs,
  closeAllDbs
};
