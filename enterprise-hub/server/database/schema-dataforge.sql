-- ============================================================================
-- DATAFORGE DATABASE SCHEMA (dataforge.db)
-- TrueVault Enterprise Business Hub
-- Custom database builder: tables, fields, records, templates
-- ============================================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- ============================================================================
-- DF_TABLES TABLE
-- User-created custom tables/trackers
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    icon TEXT DEFAULT '📋',
    color TEXT DEFAULT '#3B82F6',
    template_id INTEGER,
    
    -- Settings
    allow_attachments BOOLEAN DEFAULT 1,
    allow_comments BOOLEAN DEFAULT 0,
    show_row_numbers BOOLEAN DEFAULT 1,
    default_sort_field TEXT,
    default_sort_direction TEXT DEFAULT 'asc',
    
    -- Metadata
    record_count INTEGER DEFAULT 0,
    is_system BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DF_FIELDS TABLE
-- Field definitions for custom tables
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    field_type TEXT NOT NULL,
    description TEXT,
    
    -- Display
    display_order INTEGER DEFAULT 0,
    width INTEGER,
    is_visible BOOLEAN DEFAULT 1,
    is_visible_in_grid BOOLEAN DEFAULT 1,
    
    -- Validation
    is_required BOOLEAN DEFAULT 0,
    is_unique BOOLEAN DEFAULT 0,
    default_value TEXT,
    min_value TEXT,
    max_value TEXT,
    regex_pattern TEXT,
    
    -- Field-type specific options (JSON)
    options TEXT,
    
    -- Relationship fields
    related_table_id INTEGER,
    related_display_field TEXT,
    
    -- Formula fields
    formula TEXT,
    
    -- System
    is_system BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(table_id, slug),
    FOREIGN KEY (table_id) REFERENCES df_tables(id) ON DELETE CASCADE,
    FOREIGN KEY (related_table_id) REFERENCES df_tables(id) ON DELETE SET NULL
);

-- ============================================================================
-- DF_RECORDS TABLE
-- Actual data records in custom tables
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    
    -- Metadata
    created_by INTEGER,
    updated_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (table_id) REFERENCES df_tables(id) ON DELETE CASCADE
);

-- ============================================================================
-- DF_TABLE_PERMISSIONS TABLE
-- Who can access which tables
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_table_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    employee_id INTEGER,
    role_id INTEGER,
    permission_level TEXT NOT NULL DEFAULT 'view',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,
    
    FOREIGN KEY (table_id) REFERENCES df_tables(id) ON DELETE CASCADE,
    CHECK (employee_id IS NOT NULL OR role_id IS NOT NULL)
);

-- ============================================================================
-- DF_TEMPLATES TABLE
-- Pre-built table templates
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    category TEXT NOT NULL,
    icon TEXT DEFAULT '📋',
    color TEXT DEFAULT '#3B82F6',
    
    -- Template definition (JSON)
    fields_definition TEXT NOT NULL,
    sample_data TEXT,
    
    -- Related templates (for packs)
    pack_id INTEGER,
    pack_order INTEGER,
    
    -- Stats
    use_count INTEGER DEFAULT 0,
    
    -- System
    is_featured BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DF_TEMPLATE_PACKS TABLE
-- Groups of related templates
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_template_packs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    icon TEXT DEFAULT '📦',
    
    -- Stats
    use_count INTEGER DEFAULT 0,
    
    is_featured BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DF_VIEWS TABLE
-- Saved views/filters for tables
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    
    -- View configuration (JSON)
    filters TEXT,
    sort_field TEXT,
    sort_direction TEXT DEFAULT 'asc',
    visible_fields TEXT,
    group_by TEXT,
    
    -- Sharing
    is_default BOOLEAN DEFAULT 0,
    is_shared BOOLEAN DEFAULT 0,
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (table_id) REFERENCES df_tables(id) ON DELETE CASCADE
);

-- ============================================================================
-- DF_AUTOMATIONS TABLE
-- Table automations/triggers
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_automations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    table_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    
    -- Trigger
    trigger_type TEXT NOT NULL,
    trigger_config TEXT,
    
    -- Conditions (JSON)
    conditions TEXT,
    
    -- Actions (JSON)
    actions TEXT NOT NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT 1,
    last_run_at DATETIME,
    run_count INTEGER DEFAULT 0,
    
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (table_id) REFERENCES df_tables(id) ON DELETE CASCADE
);

-- ============================================================================
-- DF_ATTACHMENTS TABLE
-- File attachments for records
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    record_id INTEGER NOT NULL,
    field_id INTEGER,
    
    file_name TEXT NOT NULL,
    file_path TEXT NOT NULL,
    file_size INTEGER,
    mime_type TEXT,
    
    uploaded_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (record_id) REFERENCES df_records(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES df_fields(id) ON DELETE SET NULL
);

-- ============================================================================
-- DF_COMMENTS TABLE
-- Comments on records
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    record_id INTEGER NOT NULL,
    parent_id INTEGER,
    
    content TEXT NOT NULL,
    
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (record_id) REFERENCES df_records(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES df_comments(id) ON DELETE CASCADE
);

-- ============================================================================
-- DF_DASHBOARDS TABLE
-- Custom dashboards
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_dashboards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    
    -- Layout (JSON)
    layout TEXT,
    
    -- Sharing
    is_default BOOLEAN DEFAULT 0,
    is_shared BOOLEAN DEFAULT 0,
    
    created_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- DF_WIDGETS TABLE
-- Dashboard widgets
-- ============================================================================
CREATE TABLE IF NOT EXISTS df_widgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    dashboard_id INTEGER NOT NULL,
    
    widget_type TEXT NOT NULL,
    title TEXT,
    
    -- Configuration (JSON)
    config TEXT NOT NULL,
    
    -- Position
    position_x INTEGER DEFAULT 0,
    position_y INTEGER DEFAULT 0,
    width INTEGER DEFAULT 1,
    height INTEGER DEFAULT 1,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (dashboard_id) REFERENCES df_dashboards(id) ON DELETE CASCADE
);

-- ============================================================================
-- INDEXES
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_df_tables_slug ON df_tables(slug);
CREATE INDEX IF NOT EXISTS idx_df_tables_created_by ON df_tables(created_by);
CREATE INDEX IF NOT EXISTS idx_df_fields_table ON df_fields(table_id);
CREATE INDEX IF NOT EXISTS idx_df_fields_type ON df_fields(field_type);
CREATE INDEX IF NOT EXISTS idx_df_records_table ON df_records(table_id);
CREATE INDEX IF NOT EXISTS idx_df_records_created ON df_records(created_at);
CREATE INDEX IF NOT EXISTS idx_df_permissions_table ON df_table_permissions(table_id);
CREATE INDEX IF NOT EXISTS idx_df_permissions_employee ON df_table_permissions(employee_id);
CREATE INDEX IF NOT EXISTS idx_df_templates_category ON df_templates(category);
CREATE INDEX IF NOT EXISTS idx_df_templates_pack ON df_templates(pack_id);
CREATE INDEX IF NOT EXISTS idx_df_views_table ON df_views(table_id);
CREATE INDEX IF NOT EXISTS idx_df_automations_table ON df_automations(table_id);
CREATE INDEX IF NOT EXISTS idx_df_attachments_record ON df_attachments(record_id);
CREATE INDEX IF NOT EXISTS idx_df_comments_record ON df_comments(record_id);
CREATE INDEX IF NOT EXISTS idx_df_widgets_dashboard ON df_widgets(dashboard_id);

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
