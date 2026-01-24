<?php
/**
 * TrueVault VPN - Edit Table
 * Part 13 - Redirects to designer.php
 */

$tableId = isset($_GET['id']) ? intval($_GET['id']) : 0;
header('Location: designer.php?id=' . $tableId);
exit;
?>
