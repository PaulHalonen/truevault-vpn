/**
 * VPN Routes
 * 
 * Manage WireGuard VPN devices and configurations
 */

import { Router } from 'express';
import crypto from 'crypto';
import { getDb } from '../config/database.js';
import { asyncHandler, Errors } from '../middleware/errorHandler.js';
import { hasPermission } from '../middleware/auth.js';

const router = Router();

// ============================================================================
// GET MY DEVICES
// ============================================================================

/**
 * GET /api/vpn/devices
 * Get current user's VPN devices
 */
router.get('/devices', asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  const devices = db.prepare(`
    SELECT 
      id,
      device_name,
      device_type,
      assigned_ip,
      is_active,
      last_handshake,
      created_at
    FROM vpn_devices
    WHERE employee_id = ?
    ORDER BY created_at DESC
  `).all(req.user.id);
  
  res.json(devices);
}));

// ============================================================================
// GET SINGLE DEVICE
// ============================================================================

/**
 * GET /api/vpn/devices/:id
 * Get device details
 */
router.get('/devices/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  
  const device = db.prepare(`
    SELECT * FROM vpn_devices WHERE id = ?
  `).get(id);
  
  if (!device) {
    throw Errors.notFound('Device not found');
  }
  
  // Check ownership or admin
  const canViewAll = req.user.permissions.includes('vpn.view.all');
  if (device.employee_id !== req.user.id && !canViewAll) {
    throw Errors.forbidden('You do not have access to this device');
  }
  
  res.json({
    id: device.id,
    deviceName: device.device_name,
    deviceType: device.device_type,
    assignedIp: device.assigned_ip,
    publicKey: device.public_key,
    isActive: device.is_active === 1,
    lastHandshake: device.last_handshake,
    createdAt: device.created_at
  });
}));

// ============================================================================
// CREATE DEVICE
// ============================================================================

/**
 * POST /api/vpn/devices
 * Add a new VPN device
 */
router.post('/devices', asyncHandler(async (req, res) => {
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const { deviceName, deviceType = 'other', publicKey } = req.body;
  
  if (!deviceName) {
    throw Errors.badRequest('Device name is required');
  }
  
  // Check device limit (max 5 devices per user)
  const deviceCount = db.prepare(`
    SELECT COUNT(*) as count FROM vpn_devices 
    WHERE employee_id = ? AND is_active = 1
  `).get(req.user.id);
  
  if (deviceCount.count >= 5) {
    throw Errors.badRequest('Maximum 5 devices allowed. Please remove a device first.');
  }
  
  // Generate keys if not provided (client-side generation preferred)
  let clientPublicKey = publicKey;
  let clientPrivateKey = null;
  
  if (!publicKey) {
    // Generate WireGuard key pair using crypto
    // Note: In production, use actual WireGuard key generation
    const privateKeyBytes = crypto.randomBytes(32);
    clientPrivateKey = privateKeyBytes.toString('base64');
    
    // Derive public key (simplified - in production use proper curve25519)
    const publicKeyHash = crypto.createHash('sha256').update(privateKeyBytes).digest();
    clientPublicKey = publicKeyHash.toString('base64');
  }
  
  // Get next available IP
  const lastDevice = db.prepare(`
    SELECT assigned_ip FROM vpn_devices 
    ORDER BY id DESC LIMIT 1
  `).get();
  
  let nextIp = '10.0.0.2'; // First client IP
  if (lastDevice) {
    const parts = lastDevice.assigned_ip.split('.');
    const lastOctet = parseInt(parts[3]) + 1;
    if (lastOctet > 254) {
      throw Errors.badRequest('No more IP addresses available');
    }
    nextIp = `${parts[0]}.${parts[1]}.${parts[2]}.${lastOctet}`;
  }
  
  // Insert device
  const result = db.prepare(`
    INSERT INTO vpn_devices (
      employee_id, device_name, device_type, public_key, 
      private_key_encrypted, assigned_ip, is_active
    )
    VALUES (?, ?, ?, ?, ?, ?, 1)
  `).run(
    req.user.id, 
    deviceName, 
    deviceType, 
    clientPublicKey,
    clientPrivateKey, // Would be encrypted in production
    nextIp
  );
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, description, ip_address
    )
    VALUES (?, ?, ?, 'create', 'vpn_device', ?, ?, 'VPN device added', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    result.lastInsertRowid,
    deviceName,
    req.ip
  );
  
  // Get server config from settings
  const serverEndpoint = db.prepare(`
    SELECT value FROM company_settings WHERE key = 'vpn_server_endpoint'
  `).get();
  
  const serverPublicKey = db.prepare(`
    SELECT value FROM company_settings WHERE key = 'vpn_server_public_key'
  `).get();
  
  res.status(201).json({
    success: true,
    device: {
      id: result.lastInsertRowid,
      deviceName,
      deviceType,
      assignedIp: nextIp,
      publicKey: clientPublicKey
    },
    // Include private key only if we generated it (for client config)
    ...(clientPrivateKey && {
      config: {
        privateKey: clientPrivateKey,
        address: `${nextIp}/32`,
        dns: '1.1.1.1, 8.8.8.8',
        serverEndpoint: serverEndpoint?.value || 'vpn.example.com:51820',
        serverPublicKey: serverPublicKey?.value || 'SERVER_PUBLIC_KEY',
        allowedIps: '0.0.0.0/0'
      }
    })
  });
}));

// ============================================================================
// GENERATE CONFIG / QR CODE
// ============================================================================

/**
 * GET /api/vpn/devices/:id/config
 * Get WireGuard config for device
 */
router.get('/devices/:id/config', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { format = 'conf' } = req.query;
  const db = getDb('company');
  
  const device = db.prepare(`
    SELECT * FROM vpn_devices WHERE id = ?
  `).get(id);
  
  if (!device) {
    throw Errors.notFound('Device not found');
  }
  
  // Check ownership
  if (device.employee_id !== req.user.id) {
    const canManageAll = req.user.permissions.includes('vpn.manage.all');
    if (!canManageAll) {
      throw Errors.forbidden('You do not have access to this device');
    }
  }
  
  // Get server settings
  const serverEndpoint = db.prepare(`
    SELECT value FROM company_settings WHERE key = 'vpn_server_endpoint'
  `).get();
  
  const serverPublicKey = db.prepare(`
    SELECT value FROM company_settings WHERE key = 'vpn_server_public_key'
  `).get();
  
  // Build config
  const config = `[Interface]
PrivateKey = ${device.private_key_encrypted || 'YOUR_PRIVATE_KEY'}
Address = ${device.assigned_ip}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = ${serverPublicKey?.value || 'SERVER_PUBLIC_KEY'}
Endpoint = ${serverEndpoint?.value || 'vpn.example.com:51820'}
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25`;

  if (format === 'qr') {
    // Return QR code data (would use qrcode library)
    const QRCode = await import('qrcode');
    const qrDataUrl = await QRCode.toDataURL(config);
    
    res.json({
      qrCode: qrDataUrl,
      deviceName: device.device_name
    });
  } else {
    // Return as downloadable file
    res.setHeader('Content-Type', 'text/plain');
    res.setHeader('Content-Disposition', `attachment; filename="${device.device_name}.conf"`);
    res.send(config);
  }
}));

// ============================================================================
// DELETE DEVICE
// ============================================================================

/**
 * DELETE /api/vpn/devices/:id
 * Remove a VPN device
 */
router.delete('/devices/:id', asyncHandler(async (req, res) => {
  const { id } = req.params;
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const device = db.prepare(`
    SELECT * FROM vpn_devices WHERE id = ?
  `).get(id);
  
  if (!device) {
    throw Errors.notFound('Device not found');
  }
  
  // Check ownership or admin
  const canManageAll = req.user.permissions.includes('vpn.manage.all');
  if (device.employee_id !== req.user.id && !canManageAll) {
    throw Errors.forbidden('You do not have permission to delete this device');
  }
  
  // Soft delete (deactivate)
  db.prepare(`
    UPDATE vpn_devices SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?
  `).run(id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, description, ip_address
    )
    VALUES (?, ?, ?, 'delete', 'vpn_device', ?, ?, 'VPN device removed', ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    device.device_name,
    req.ip
  );
  
  // TODO: Remove peer from WireGuard server
  
  res.json({ success: true, message: 'Device removed' });
}));

// ============================================================================
// ADMIN: GET ALL DEVICES
// ============================================================================

/**
 * GET /api/vpn/admin/devices
 * Get all VPN devices (admin only)
 */
router.get('/admin/devices', hasPermission('vpn.view.all'), asyncHandler(async (req, res) => {
  const db = getDb('company');
  const { status } = req.query;
  
  let query = `
    SELECT 
      d.*,
      e.first_name || ' ' || e.last_name as employee_name,
      e.email as employee_email
    FROM vpn_devices d
    JOIN employees e ON d.employee_id = e.id
  `;
  
  if (status === 'active') {
    query += ` WHERE d.is_active = 1`;
  } else if (status === 'inactive') {
    query += ` WHERE d.is_active = 0`;
  }
  
  query += ` ORDER BY d.created_at DESC`;
  
  const devices = db.prepare(query).all();
  
  res.json(devices);
}));

// ============================================================================
// ADMIN: REVOKE DEVICE
// ============================================================================

/**
 * POST /api/vpn/admin/devices/:id/revoke
 * Revoke a VPN device (admin only)
 */
router.post('/admin/devices/:id/revoke', hasPermission('vpn.manage.all'), asyncHandler(async (req, res) => {
  const { id } = req.params;
  const { reason } = req.body;
  const db = getDb('company');
  const auditDb = getDb('audit');
  
  const device = db.prepare(`SELECT * FROM vpn_devices WHERE id = ?`).get(id);
  
  if (!device) {
    throw Errors.notFound('Device not found');
  }
  
  // Deactivate
  db.prepare(`
    UPDATE vpn_devices SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?
  `).run(id);
  
  // Audit log
  auditDb.prepare(`
    INSERT INTO audit_logs (
      employee_id, employee_email, employee_name, action,
      resource_type, resource_id, resource_name, description, ip_address
    )
    VALUES (?, ?, ?, 'revoke', 'vpn_device', ?, ?, ?, ?)
  `).run(
    req.user.id,
    req.user.email,
    `${req.user.firstName} ${req.user.lastName}`,
    id,
    device.device_name,
    reason || 'Device revoked by admin',
    req.ip
  );
  
  // TODO: Remove peer from WireGuard server immediately
  
  res.json({ success: true, message: 'Device revoked' });
}));

// ============================================================================
// VPN STATUS
// ============================================================================

/**
 * GET /api/vpn/status
 * Get VPN server status
 */
router.get('/status', asyncHandler(async (req, res) => {
  const db = getDb('company');
  
  // Get server settings
  const settings = db.prepare(`
    SELECT key, value FROM company_settings 
    WHERE key IN ('vpn_server_endpoint', 'vpn_network_cidr')
  `).all();
  
  const settingsMap = Object.fromEntries(settings.map(s => [s.key, s.value]));
  
  // Get active device count
  const { activeDevices } = db.prepare(`
    SELECT COUNT(*) as activeDevices FROM vpn_devices WHERE is_active = 1
  `).get();
  
  // Get my device count
  const { myDevices } = db.prepare(`
    SELECT COUNT(*) as myDevices FROM vpn_devices 
    WHERE employee_id = ? AND is_active = 1
  `).get(req.user.id);
  
  res.json({
    serverEndpoint: settingsMap.vpn_server_endpoint || null,
    networkCidr: settingsMap.vpn_network_cidr || '10.0.0.0/24',
    totalActiveDevices: activeDevices,
    myActiveDevices: myDevices,
    maxDevicesPerUser: 5
  });
}));

export default router;
