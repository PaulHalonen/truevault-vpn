/**
 * TrueVault Enterprise Hub - Server Entry Point
 * 
 * Express server with Socket.io for real-time updates
 */

import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import { createServer } from 'http';
import { Server as SocketServer } from 'socket.io';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

// Import database initialization
import { initializeDatabases, closeAllDbs } from './config/database.js';

// Import middleware
import { authMiddleware } from './middleware/auth.js';
import { errorHandler } from './middleware/errorHandler.js';

// Import routes
import authRoutes from './routes/auth.js';
import employeeRoutes from './routes/employees.js';
import timeoffRoutes from './routes/timeoff.js';
import vpnRoutes from './routes/vpn.js';
import adminRoutes from './routes/admin.js';
import dataforgeRoutes from './routes/dataforge.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// ============================================================================
// APP CONFIGURATION
// ============================================================================

const app = express();
const httpServer = createServer(app);

const PORT = process.env.PORT || 3001;
const CORS_ORIGIN = process.env.CORS_ORIGIN || 'http://localhost:5173';

// ============================================================================
// MIDDLEWARE
// ============================================================================

// Security headers
app.use(helmet({
  crossOriginResourcePolicy: { policy: 'cross-origin' }
}));

// CORS
app.use(cors({
  origin: CORS_ORIGIN.split(','),
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));

// Body parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Logging
if (process.env.NODE_ENV !== 'test') {
  app.use(morgan('dev'));
}

// ============================================================================
// SOCKET.IO
// ============================================================================

const io = new SocketServer(httpServer, {
  cors: {
    origin: CORS_ORIGIN.split(','),
    methods: ['GET', 'POST'],
    credentials: true
  }
});

// Make io available to routes
app.set('io', io);

// Socket.io connection handling
io.on('connection', (socket) => {
  console.log(`[Socket] Client connected: ${socket.id}`);
  
  // Join company room for broadcasts
  socket.on('join:company', () => {
    socket.join('company');
    console.log(`[Socket] ${socket.id} joined company room`);
  });
  
  // Join specific table room for DataForge updates
  socket.on('join:table', (tableId) => {
    socket.join(`table:${tableId}`);
    console.log(`[Socket] ${socket.id} joined table:${tableId}`);
  });
  
  socket.on('leave:table', (tableId) => {
    socket.leave(`table:${tableId}`);
  });
  
  socket.on('disconnect', () => {
    console.log(`[Socket] Client disconnected: ${socket.id}`);
  });
});

// ============================================================================
// HEALTH CHECK (No auth required)
// ============================================================================

app.get('/api/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    version: process.env.npm_package_version || '1.0.0',
    uptime: process.uptime()
  });
});

// ============================================================================
// PUBLIC ROUTES (No auth required)
// ============================================================================

// Auth routes (login, register, password reset)
app.use('/api/auth', authRoutes);

// ============================================================================
// PROTECTED ROUTES (Auth required)
// ============================================================================

// Apply auth middleware to all routes below this line
app.use('/api', authMiddleware);

// Employee routes
app.use('/api/employees', employeeRoutes);

// Time-off routes
app.use('/api/timeoff', timeoffRoutes);

// VPN routes
app.use('/api/vpn', vpnRoutes);

// Admin routes (additional role check inside)
app.use('/api/admin', adminRoutes);

// DataForge routes
app.use('/api/dataforge', dataforgeRoutes);

// ============================================================================
// 404 HANDLER
// ============================================================================

app.use('/api/*', (req, res) => {
  res.status(404).json({
    error: 'Not Found',
    message: `Route ${req.method} ${req.originalUrl} not found`
  });
});

// ============================================================================
// ERROR HANDLER (Must be last)
// ============================================================================

app.use(errorHandler);

// ============================================================================
// SERVER STARTUP
// ============================================================================

async function startServer() {
  try {
    // Initialize databases
    console.log('[Server] Initializing databases...');
    await initializeDatabases();
    console.log('[Server] Databases initialized');
    
    // Start HTTP server
    httpServer.listen(PORT, () => {
      console.log(`
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║   TrueVault Enterprise Hub Server                            ║
║                                                              ║
║   🌐 API:      http://localhost:${PORT}/api                    ║
║   🔌 Socket:   http://localhost:${PORT}                        ║
║   📊 Health:   http://localhost:${PORT}/api/health             ║
║                                                              ║
║   Mode: ${(process.env.NODE_ENV || 'development').padEnd(52)}║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
      `);
    });
    
  } catch (error) {
    console.error('[Server] Failed to start:', error);
    process.exit(1);
  }
}

// ============================================================================
// GRACEFUL SHUTDOWN
// ============================================================================

function gracefulShutdown(signal) {
  console.log(`\n[Server] ${signal} received. Shutting down gracefully...`);
  
  httpServer.close(() => {
    console.log('[Server] HTTP server closed');
    
    closeAllDbs();
    console.log('[Server] Database connections closed');
    
    console.log('[Server] Goodbye! 👋');
    process.exit(0);
  });
  
  // Force shutdown after 10 seconds
  setTimeout(() => {
    console.error('[Server] Forced shutdown after timeout');
    process.exit(1);
  }, 10000);
}

process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT', () => gracefulShutdown('SIGINT'));

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
  console.error('[Server] Uncaught Exception:', error);
  gracefulShutdown('uncaughtException');
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('[Server] Unhandled Rejection at:', promise, 'reason:', reason);
});

// Start the server
startServer();

export { app, io };
