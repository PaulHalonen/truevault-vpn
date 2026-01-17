/**
 * Error Handler Middleware
 * 
 * Centralized error handling for the API
 */

/**
 * Custom API Error class
 */
export class ApiError extends Error {
  constructor(statusCode, message, details = null) {
    super(message);
    this.statusCode = statusCode;
    this.details = details;
    this.isOperational = true;
    
    Error.captureStackTrace(this, this.constructor);
  }
}

/**
 * Common error factory methods
 */
export const Errors = {
  badRequest: (message = 'Bad Request', details = null) => 
    new ApiError(400, message, details),
    
  unauthorized: (message = 'Unauthorized') => 
    new ApiError(401, message),
    
  forbidden: (message = 'Forbidden') => 
    new ApiError(403, message),
    
  notFound: (resource = 'Resource') => 
    new ApiError(404, `${resource} not found`),
    
  conflict: (message = 'Conflict') => 
    new ApiError(409, message),
    
  validation: (details) => 
    new ApiError(422, 'Validation failed', details),
    
  internal: (message = 'Internal Server Error') => 
    new ApiError(500, message)
};

/**
 * Global error handler middleware
 */
export function errorHandler(err, req, res, next) {
  // Log the error
  console.error('[Error]', {
    message: err.message,
    stack: process.env.NODE_ENV === 'development' ? err.stack : undefined,
    path: req.path,
    method: req.method,
    userId: req.user?.id
  });
  
  // Handle known API errors
  if (err instanceof ApiError) {
    return res.status(err.statusCode).json({
      error: err.message,
      details: err.details,
      ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
    });
  }
  
  // Handle Zod validation errors
  if (err.name === 'ZodError') {
    return res.status(422).json({
      error: 'Validation failed',
      details: err.errors.map(e => ({
        field: e.path.join('.'),
        message: e.message
      }))
    });
  }
  
  // Handle JWT errors
  if (err.name === 'JsonWebTokenError') {
    return res.status(401).json({
      error: 'Invalid token'
    });
  }
  
  if (err.name === 'TokenExpiredError') {
    return res.status(401).json({
      error: 'Token expired',
      code: 'TOKEN_EXPIRED'
    });
  }
  
  // Handle SQLite errors
  if (err.code === 'SQLITE_CONSTRAINT') {
    return res.status(409).json({
      error: 'Database constraint violation',
      message: err.message.includes('UNIQUE') 
        ? 'A record with this value already exists'
        : 'Database constraint error'
    });
  }
  
  // Handle multer file upload errors
  if (err.code === 'LIMIT_FILE_SIZE') {
    return res.status(400).json({
      error: 'File too large'
    });
  }
  
  // Default to 500 Internal Server Error
  return res.status(500).json({
    error: 'Internal Server Error',
    ...(process.env.NODE_ENV === 'development' && { 
      message: err.message,
      stack: err.stack 
    })
  });
}

/**
 * Async handler wrapper to catch errors in async routes
 */
export function asyncHandler(fn) {
  return (req, res, next) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
}

export default {
  ApiError,
  Errors,
  errorHandler,
  asyncHandler
};
