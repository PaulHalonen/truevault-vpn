-- Database Builder Schema (builder.db)
-- Created: January 19, 2026

-- Table 1: custom_tables (stores user-created tables)
CREATE TABLE IF NOT EXISTS custom_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_name TEXT NOT NULL UNIQUE,        -- Technical name (e.g., 'customers')
    display_name TEXT NOT NULL,             -- Display name (e.g., 'Customers')
    description TEXT,
    icon TEXT DEFAULT '📊',                 -- Emoji icon
    created_by INTEGER,                     -- User ID who created this
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    is_active INTEGER DEFAULT 1
);

-- Table 2: custom_fields (stores fields for each custom table)
CREATE TABLE IF NOT EXISTS custom_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    field_name TEXT NOT NULL,               -- Technical name (e.g., 'email')
    display_name TEXT NOT NULL,             -- Display name (e.g., 'Email Address')
    field_type TEXT NOT NULL,               -- text, email, number, date, etc.
    field_order INTEGER DEFAULT 0,          -- Display order
    is_required INTEGER DEFAULT 0,
    default_value TEXT,
    validation_rules TEXT,                  -- JSON: {min: 0, max: 100, pattern: "..."}
    options TEXT,                           -- JSON: for dropdown/radio ["Option 1", "Option 2"]
    help_text TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);

-- Table 3: table_relationships (stores relationships between tables)
CREATE TABLE IF NOT EXISTS table_relationships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_table_id INTEGER NOT NULL,
    to_table_id INTEGER NOT NULL,
    relationship_type TEXT NOT NULL,        -- one_to_one, one_to_many, many_to_many
    from_field TEXT,                        -- Foreign key field in from_table
    to_field TEXT,                          -- Primary key field in to_table
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (to_table_id) REFERENCES custom_tables(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_custom_fields_table ON custom_fields(table_id);
CREATE INDEX IF NOT EXISTS idx_custom_fields_order ON custom_fields(field_order);
CREATE INDEX IF NOT EXISTS idx_relationships_from ON table_relationships(from_table_id);
CREATE INDEX IF NOT EXISTS idx_relationships_to ON table_relationships(to_table_id);

-- Sample data for demonstration
INSERT OR IGNORE INTO custom_tables (id, table_name, display_name, description, icon) VALUES
(1, 'customers', 'Customers', 'Customer contact information', '👥'),
(2, 'products', 'Products', 'Product catalog', '📦'),
(3, 'orders', 'Orders', 'Customer orders', '🛒');

INSERT OR IGNORE INTO custom_fields (table_id, field_name, display_name, field_type, field_order, is_required) VALUES
-- Customers table fields
(1, 'name', 'Full Name', 'text', 1, 1),
(1, 'email', 'Email Address', 'email', 2, 1),
(1, 'phone', 'Phone Number', 'phone', 3, 0),
(1, 'created_date', 'Customer Since', 'date', 4, 0),
-- Products table fields
(2, 'product_name', 'Product Name', 'text', 1, 1),
(2, 'price', 'Price', 'currency', 2, 1),
(2, 'stock', 'Stock Quantity', 'number', 3, 0),
-- Orders table fields
(3, 'order_date', 'Order Date', 'date', 1, 1),
(3, 'customer_id', 'Customer', 'number', 2, 1),
(3, 'total_amount', 'Total Amount', 'currency', 3, 1);

INSERT OR IGNORE INTO table_relationships (from_table_id, to_table_id, relationship_type) VALUES
(3, 1, 'many_to_one');  -- Orders -> Customers
