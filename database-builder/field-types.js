// Field Types Configuration
// 15 supported field types for database builder

const FIELD_TYPES = {
    text: {
        label: 'Text',
        icon: '📝',
        description: 'Single line text input',
        sqlType: 'TEXT',
        defaultValidation: { maxLength: 255 }
    },
    textarea: {
        label: 'Long Text',
        icon: '📄',
        description: 'Multi-line text area',
        sqlType: 'TEXT',
        defaultValidation: { maxLength: 10000 }
    },
    email: {
        label: 'Email',
        icon: '📧',
        description: 'Email address with validation',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$' }
    },
    phone: {
        label: 'Phone',
        icon: '📞',
        description: 'Phone number',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^[\\d\\s\\-\\+\\(\\)]+$' }
    },
    url: {
        label: 'URL',
        icon: '🔗',
        description: 'Website address',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^https?://' }
    },
    number: {
        label: 'Number',
        icon: '🔢',
        description: 'Integer or decimal number',
        sqlType: 'REAL',
        defaultValidation: { min: null, max: null }
    },
    currency: {
        label: 'Currency',
        icon: '💰',
        description: 'Money amount with 2 decimal places',
        sqlType: 'REAL',
        defaultValidation: { min: 0, decimals: 2 }
    },
    date: {
        label: 'Date',
        icon: '📅',
        description: 'Date picker (YYYY-MM-DD)',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^\\d{4}-\\d{2}-\\d{2}$' }
    },
    datetime: {
        label: 'Date & Time',
        icon: '🕐',
        description: 'Date and time picker',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^\\d{4}-\\d{2}-\\d{2}\\s\\d{2}:\\d{2}' }
    },
    checkbox: {
        label: 'Checkbox',
        icon: '☑️',
        description: 'True/False toggle',
        sqlType: 'INTEGER',
        defaultValidation: {}
    },
    select: {
        label: 'Dropdown',
        icon: '📋',
        description: 'Select from predefined options',
        sqlType: 'TEXT',
        defaultValidation: {},
        requiresOptions: true
    },
    radio: {
        label: 'Radio Buttons',
        icon: '🔘',
        description: 'Choose one option',
        sqlType: 'TEXT',
        defaultValidation: {},
        requiresOptions: true
    },
    rating: {
        label: 'Rating',
        icon: '⭐',
        description: 'Star rating (1-5)',
        sqlType: 'REAL',
        defaultValidation: { min: 1, max: 5 }
    },
    file: {
        label: 'File Upload',
        icon: '📎',
        description: 'File attachment',
        sqlType: 'TEXT',
        defaultValidation: { maxSize: 10485760 } // 10MB
    },
    color: {
        label: 'Color Picker',
        icon: '🎨',
        description: 'Color selection (#RRGGBB)',
        sqlType: 'TEXT',
        defaultValidation: { pattern: '^#[0-9A-Fa-f]{6}$' }
    }
};

// Export for PHP usage
export default FIELD_TYPES;
