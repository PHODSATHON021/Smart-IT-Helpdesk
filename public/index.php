<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Core\Database;
use App\Core\EventDispatcher;
use App\Repositories\TicketRepository;
use App\Services\TicketStatusService;
use App\Services\AuthService;
use App\Notifications\EmailNotificationService;
use App\Observers\TicketObserver;
use App\Controllers\TicketController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$db = Database::getInstance()->getConnection();
$auth = new AuthService($db);

// ตรวจสอบการเข้าสู่ระบบ
$auth->requireLogin();

// เตรียม dependencies ตาม Observer Pattern
$ticketRepo = new TicketRepository();
$events = new EventDispatcher();
$emailService = new EmailNotificationService();
$observer = new TicketObserver($emailService);

// ผูก Event Observer
$events->listen('ticket.status_changed', function ($data) use ($observer) {
    $observer->handleStatusChanged($data['ticket'], $data['from'], $data['to'], $data['email']);
});

$statusService = new TicketStatusService($ticketRepo, $events);
$controller = new TicketController($ticketRepo, $statusService, $auth);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
        $newId = $controller->create($_POST);
        if ($newId) {
            $message = "สร้าง Ticket #{$newId} เรียบร้อยแล้ว!";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $controller->updateStatus((int)$_POST['ticket_id'], $_POST['status']);
        $message = "อัปเดตสถานะ Ticket เรียบร้อยแล้ว!";
    }
}

// เรียกดึงข้อมูลผ่าน Controller
$data = $controller->index();
$currentUser = $data['currentUser'];
$tickets = $data['tickets'];
$categories = $data['categories'];

// โหลดหน้า View
require_once __DIR__ . '/../views/tickets/index.php';