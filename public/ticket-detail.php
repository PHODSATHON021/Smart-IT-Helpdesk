<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Repositories\TicketRepository;
use App\Repositories\CommentRepository;
use App\Services\AuthService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$db = Database::getInstance()->getConnection();
$auth = new AuthService($db);
$auth->requireLogin();
$currentUser = $auth->user();

$ticketId = (int)($_GET['id'] ?? 0);
if (!$ticketId) {
    header('Location: index.php');
    exit;
}

$ticketRepo = new TicketRepository();
$commentRepo = new CommentRepository();

// ดึงข้อมูล Ticket
$stmt = $db->prepare("
    SELECT t.*, c.name AS category_name, u.name AS user_name, u.email AS user_email
    FROM tickets t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.id = :id
");
$stmt->execute(['id' => $ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: index.php');
    exit;
}

if ($currentUser['role'] === 'user' && $ticket['user_id'] != $currentUser['id']) {
    header('Location: index.php');
    exit;
}

// บันทึกความคิดเห็นพร้อมรูปภาพ
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $commentText = trim($_POST['comment']);
    $imagePath = null;

    // จัดการการอัปโหลดไฟล์รูปภาพ
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['attachment']['tmp_name'];
        $fileName = $_FILES['attachment']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/uploads/';
            
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $imagePath = 'uploads/' . $newFileName;
            }
        }
    }

    if (!empty($commentText) || $imagePath !== null) {
        $commentRepo->create([
            'ticket_id'  => $ticketId,
            'user_id'    => $currentUser['id'],
            'comment'    => $commentText,
            'image_path' => $imagePath
        ]);
        $message = "ส่งความคิดเห็นเรียบร้อยแล้ว!";
    }
}

$comments = $commentRepo->getByTicketId($ticketId);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียด Ticket #<?= $ticket['id'] ?> - Smart Helpdesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .comment-box { border-left: 4px solid #0d6efd; background-color: #ffffff; }
        .comment-box.admin { border-left-color: #198754; background-color: #f4fbf7; }
        .img-attachment { max-width: 100%; max-height: 300px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-arrow-left me-2"></i>กลับหน้าหลัก</a>
            <span class="text-white">Ticket #<?= $ticket['id'] ?></span>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="fa-solid fa-check-circle me-1"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- ฝั่งซ้าย: รายละเอียดปัญหา -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ticket['title']) ?></h3>
                            <span class="badge bg-primary fs-6"><?= htmlspecialchars($ticket['status']) ?></span>
                        </div>
                        <div class="text-muted small mb-3">
                            <span><i class="fa-regular fa-user me-1"></i><?= htmlspecialchars($ticket['user_name'] ?? '') ?></span> |
                            <span><i class="fa-regular fa-folder me-1"></i><?= htmlspecialchars($ticket['category_name'] ?? '') ?></span> |
                            <span><i class="fa-regular fa-clock me-1"></i><?= htmlspecialchars($ticket['created_at'] ?? '') ?></span>
                        </div>
                        <hr>
                        <p class="card-text text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($ticket['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม') ?></p>
                    </div>
                </div>

                <!-- ความคิดเห็น / การตอบกลับ -->
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-comments me-2"></i>การตอบกลับ / บันทึกงาน</h5>
                
                <?php if (empty($comments)): ?>
                    <div class="alert alert-light border text-center text-muted py-3">ยังไม่มีความคิดเห็นใน Ticket นี้</div>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <?php 
                            $userRole = $c['user_role'] ?? $c['role'] ?? 'user';
                            $isAdminOrTech = in_array(strtolower($userRole), ['admin', 'technician']);
                            $commentText = $c['body'] ?? $c['comment'] ?? $c['message'] ?? '';
                        ?>
                        <div class="card shadow-sm mb-3 comment-box <?= $isAdminOrTech ? 'admin' : '' ?>">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong>
                                        <?= htmlspecialchars($c['user_name'] ?? 'ผู้ใช้งาน') ?> 
                                        <span class="badge bg-secondary ms-1" style="font-size: 0.7rem;"><?= strtoupper(htmlspecialchars($userRole)) ?></span>
                                    </strong>
                                    <small class="text-muted"><?= htmlspecialchars($c['created_at'] ?? '') ?></small>
                                </div>
                                <?php if (!empty($commentText)): ?>
                                    <p class="mb-2 text-dark"><?= nl2br(htmlspecialchars($commentText)) ?></p>
                                <?php endif; ?>

                                <!-- แสดงผลรูปภาพแนบ -->
                                <?php if (!empty($c['image_path'])): ?>
                                    <div class="mt-2">
                                        <a href="<?= htmlspecialchars($c['image_path']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($c['image_path']) ?>" class="img-attachment border shadow-sm" alt="แนบรูปภาพ">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- ฟอร์มพิมพ์ตอบกลับ + แนบไฟล์ -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-body p-3">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <textarea name="comment" class="form-control" rows="3" placeholder="พิมพ์ข้อความตอบกลับ หรือบันทึกโน้ต..."></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="mb-0">
                                    <label for="attachment" class="btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-paperclip me-1"></i>แนบรูปภาพ
                                    </label>
                                    <input type="file" name="attachment" id="attachment" class="d-none" accept="image/*" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                                    <span id="file-name" class="small text-muted ms-2"></span>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> ส่งข้อความ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ฝั่งขวา: สรุปสถานะ -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0">ข้อมูลสรุป</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>ผู้แจ้ง:</strong> <?= htmlspecialchars($ticket['user_name'] ?? '') ?></p>
                        <p class="mb-2"><strong>อีเมล:</strong> <?= htmlspecialchars($ticket['user_email'] ?? '') ?></p>
                        <p class="mb-2"><strong>ระดับความสำคัญ:</strong> <?= htmlspecialchars($ticket['priority'] ?? '') ?></p>
                        <p class="mb-0"><strong>หมวดหมู่:</strong> <?= htmlspecialchars($ticket['category_name'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>