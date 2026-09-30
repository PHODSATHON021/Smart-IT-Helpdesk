<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Services\AuthService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$db = Database::getInstance()->getConnection();
$auth = new AuthService($db);

$error = '';

if ($auth->check()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($auth->login($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง!';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Smart Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 100%; max-width: 400px; border-radius: 15px; }
    </style>
</head>
<body>

<div class="card card-login shadow-lg border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="fa-solid fa-headset text-primary display-4 mb-2"></i>
            <h4 class="fw-bold text-dark">Smart Helpdesk</h4>
            <p class="text-muted small">เข้าสู่ระบบเพื่อจัดการการแจ้งซ่อม</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 text-center small"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
    <div class="mb-3">
        <label class="form-label">อีเมล</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
            <input type="email" id="emailInput" name="email" class="form-control" placeholder="user@example.com" required>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label">รหัสผ่าน</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" id="passwordInput" name="password" class="form-control" placeholder="••••••••" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold"><i class="fa-solid fa-right-to-bracket me-1"></i> เข้าสู่ระบบ</button>
</form>

<div class="mt-4 p-3 bg-light rounded text-muted small">
    <strong>ข้อมูลเข้าสู่ระบบสำหรับทดสอบ (คลิกเพื่อกรอกอัตโนมัติ):</strong><br>
    <div class="mt-2 d-grid gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary text-start" onclick="fillLogin('user@example.com', 'password123')">
            👤 <b>User:</b> user@example.com
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary text-start" onclick="fillLogin('tech@company.com', 'password123')">
            🛠️ <b>Technician:</b> tech@company.com
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary text-start" onclick="fillLogin('it-support@company.com', 'password123')">
            🛡️ <b>Admin:</b> it-support@company.com
        </button>
    </div>
</div>

<script>
function fillLogin(email, password) {
    document.getElementById('emailInput').value = email;
    document.getElementById('passwordInput').value = password;
}
</script>
    </div>
</div>

</body>
</html>