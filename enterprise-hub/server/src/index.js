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
import { Server as SocketIO } from 'socket.io';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

// Import database
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

// Initialize Express
const app = express();
const httpServer = createServer(app);

// Initialize Socket.IO
const io = new SocketIO(httpServer, {
  cors: {
    origin: process.env.CORS_ORIGIN || '*',
    methods: ['GET', 'POST']
  }
});

// Make io available to routes
app.set('io', io);

// ============================================================================
// MIDDLEWARE
// ============================================================================

// Security headers
app.use(helmet({
  contentSecurityPolicy: false // Disable for development
}));

// CORS
app.use(cors({
  origin: process.env.CORS_ORIGIN || '*',
  credentials: true
}));

// Request logging
if (process.env.NODE_ENV !== 'test') {
  app.use(morgan('dev'));
}

// Body parsing
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// ============================================================================
// ROUTES
// ============================================================================

// Health check (no auth required)
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

// Public routes (no auth required)
app.use('/api/auth', authRoutes);

// Protected routes (auth required)
app.use('/api/employees', authMiddleware, employeeRoutes);
app.use('/api/timeoff', authMiddleware, timeoffRoutes);
app.use('/api/vpn', authMiddleware, vpnRoutes);
app.use('/api/admin', authMiddleware, adminRoutes);
app.use('/api/dataforge', authMiddleware, dataforgeRoutes);

// 404 handler
app.use('/api/*', (req, res) => {
  res.status(404).json({ error: 'Endpoint not found' });
});

// Global error handler (must be last)
app.use(errorHandler);

// ============================================================================
// SOCKET.IO HANDLERS
// ============================================================================

io.on('connection', (socket) => {
  console.log('[Socket] Client connected:', socket.id);
  
  // Join company room (all employees)
  socket.on('join:company', () => {
    socket.join('company');
    console.log('[Socket] Joined company room:', socket.id);
  });
  
  // Join table room (for DataForge real-time updates)
  socket.on('join:table', (tableId) => {
    socket.join(`table:${tableId}`);
    console.log('[Socket] Joined table room:', tableId);
  });
  
  // Leave table room
  socket.on('leave:table', (tableId) => {
    socket.leave(`table:${tableId}`);
  });
  
  // Handle disconnect
  socket.on('disconnect', () => {
    console.log('[Socket] Client disconnected:', socket.id);
  });
});

// ============================================================================
// SERVER STARTUP
// ============================================================================

const PORT = process.env.PORT || 3001;

async function startServer() {
  try {
    // Initialize databases
    console.log('[Server] Initializing databases...');
    await initializeDatabases();
    console.log('[Server] Databases initialized');
    
    // Start HTTP server
    httpServer.listen(PORT, () => {
      console.log(`
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║   TrueVault Enterprise Hub Server                          ║
║                                                            ║
║   API:        http://localhost:${PORT}/api                   ║
║   Health:     http://localhost:${PORT}/api/health            ║
║   WebSocket:  ws://localhost:${PORT}                         ║
║                                                            ║
║   Environment: ${(process.env.NODE_ENV || 'development').padEnd(40)}║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
      `);
    });
  } catch (error) {
    console.error('[Server] Failed to start:', error);
    process.exit(1);
  }
}

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('[Server] SIGTERM received, shutting down...');
  httpServer.close(() => {
    console.log('[Server] HTTP server closed');
  });
  await closeAllDbs();
  process.exit(0);
});

process.on('SIGINT', async () => {
  console.log('[Server] SIGINT received, shutting down...');
  httpServer.close(() => {
    console.log('[Server] HTTP server closed');
  });
  await closeAllDbs();
  process.exit(0);
});

// Start the server
startServer();

export { app, io };
