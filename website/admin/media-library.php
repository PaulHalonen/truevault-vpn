<?php
/**
 * TrueVault VPN - Media Library (Simplified)
 * Basic file upload and management
 */
define('TRUEVAULT_INIT', true);
session_start();

require_once __DIR__ . '/../configs/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

if (!Auth::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();
$themesConn = $db->getConnection('themes');

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $file = $_FILES['file'];
    $filename = time() . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $stmt = $themesConn->prepare("
            INSERT INTO media_library (filename, filepath, file_type, file_size)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $file['name'],
            '/uploads/' . $filename,
            $file['type'],
            $file['size']
        ]);
        $success = true;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $themesConn->prepare("SELECT filepath FROM media_library WHERE id = ?");
    $stmt->execute([$id]);
    $media = $stmt->fetch();
    
    if ($media) {
        @unlink(__DIR__ . '/..' . $media['filepath']);
        $stmt = $themesConn->prepare("DELETE FROM media_library WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get all media
$stmt = $themesConn->query("SELECT * FROM media_library ORDER BY uploaded_at DESC");
$media = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Media Library - TrueVault Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 28px; color: #333; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .upload-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .upload-box h2 { margin-bottom: 15px; color: #333; }
        .file-input {
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-right: 10px;
        }
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .media-item {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .media-preview {
            width: 100%;
            height: 150px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            overflow: hidden;
        }
        .media-preview img { max-width: 100%; max-height: 100%; }
        .media-name {
            font-size: 13px;
            color: #374151;
            margin-bottom: 8px;
            word-break: break-all;
        }
        .media-actions {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            flex: 1;
        }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .success-msg {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Media Library</h1>
            <a href="/admin/" class="btn btn-primary">← Back</a>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-msg">✓ File uploaded successfully!</div>
        <?php endif; ?>

        <div class="upload-box">
            <h2>Upload New File</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="file" class="file-input" required>
                <button type="submit" class="btn btn-success">Upload</button>
            </form>
        </div>

        <div class="media-grid">
            <?php foreach ($media as $item): ?>
            <div class="media-item">
                <div class="media-preview">
                    <?php if (strpos($item['file_type'], 'image') !== false): ?>
                        <img src="<?= htmlspecialchars($item['filepath']) ?>" alt="">
                    <?php else: ?>
                        <span style="font-size: 48px;">📄</span>
                    <?php endif; ?>
                </div>
                <div class="media-name"><?= htmlspecialchars($item['filename']) ?></div>
                <div class="media-actions">
                    <button class="btn btn-sm btn-success" onclick="copyUrl('<?= htmlspecialchars($item['filepath']) ?>')">
                        Copy URL
                    </button>
                    <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">
                        Delete
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function copyUrl(url) {
            navigator.clipboard.writeText(url);
            alert('URL copied: ' + url);
        }
    </script>
</body>
</html>
