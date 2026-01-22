<?php
/**
 * Admin Logout
 */

define('TRUEVAULT_INIT', true);
require_once __DIR__ . '/../configs/config.php';

session_start();
session_destroy();

header('Location: /admin/login.php');
exit;
