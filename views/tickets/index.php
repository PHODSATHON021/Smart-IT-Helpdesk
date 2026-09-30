<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Helpdesk Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .navbar-custom { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .badge-status-Open { background-color: #e0f2fe; color: #0369a1; }
        .badge-status-In_Progress { background-color: #fef3c7; color: #b45309; }
        .badge-status-Resolved { background-color: #d1fae5; color: #047857; }
        .badge-status-Closed { background-color: #f3f4f6; color: #4b5563; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="fa-solid fa-headset fs-4"></i> Smart IT Helpdesk
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white fw-medium me-2"><?= htmlspecialchars($currentUser['name'] ?? '') ?> (<?= strtoupper(htmlspecialchars($currentUser['role'] ?? '')) ?>)</span>
                <a href="logout.php" class="btn btn-sm btn-light text-danger rounded-pill fw-medium px-3">ออกจากระบบ</a>
            </div>
        </div>
        
    </nav>

    <div class="container pb-5">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Form สร้าง Ticket -->
            <div class="col-lg-4">
                <div class="card p-4">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>แจ้งเรื่องใหม่</h5>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="create_ticket">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">หัวข้อปัญหา</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">หมวดหมู่</label>
                            <select name="category_id" class="form-select" required>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">ความสำคัญ</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-medium rounded-3">ส่งข้อมูลแจ้งซ่อม</button>
                    </form>
                </div>
            </div>

            <!-- Table แสดงรายการ -->
            <div class="col-lg-8">
                <div class="card overflow-hidden p-4">
                    <h5 class="fw-bold mb-3">รายการ Ticket ทั้งหมด</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>หัวข้อ</th>
                                    <th>ความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th class="text-end">การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tickets)): ?>
                                    <?php foreach ($tickets as $t): ?>
                                        <tr>
                                            <td>#<?= $t['id'] ?></td>
                                            <td>
                                                <a href="ticket-detail.php?id=<?= $t['id'] ?>" class="text-decoration-none fw-medium text-dark">
                                                    <?= htmlspecialchars($t['title']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= htmlspecialchars($t['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-status-<?= str_replace(' ', '_', $t['status']) ?> px-2.5 py-1.5 rounded-pill">
                                                    <?= htmlspecialchars($t['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end align-items-center gap-2">
                                                    <a href="ticket-detail.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                                                        <i class="fa-solid fa-eye me-1"></i>ดูรายละเอียด
                                                    </a>
                                                    <?php if (in_array($currentUser['role'] ?? '', ['admin', 'technician'])): ?>
                                                        <form action="index.php" method="POST" class="d-inline-block m-0">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                                <option value="Open" <?= $t['status'] === 'Open' ? 'selected' : '' ?>>Open</option>
                                                                <option value="In Progress" <?= $t['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                                <option value="Resolved" <?= $t['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                <option value="Closed" <?= $t['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
                                                            </select>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลรายการแจ้งซ่อม</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>