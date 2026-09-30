# 🎧 Smart IT Helpdesk System

ระบบจัดการแจ้งซ่อมและสนับสนุนงานไอที (IT Helpdesk System) พัฒนาด้วย **PHP 8.2** แบบ **PSR-4 MVC Architecture** รองรับการทำงานร่วมกันระหว่างผู้ใช้งานทั่วไป (User), ช่างเทคนิค (Technician) และผู้ดูแลระบบ (Admin) พร้อมระบบแจ้งเตือนและกราฟสรุปสถิติแบบ Realtime

---

## ✨ Features (คุณสมบัติหลัก)

- **Architecture & Security:**
  - โครงสร้างระบบแบบ MVC รองรับมาตรฐาน PSR-4 Autoloading
  - การบริหารจัดการฐานข้อมูลด้วย **PDO (Prepared Statements)** ป้องกัน SQL Injection
  - ระบบยืนยันตัวตน (Authentication) และกำหนดสิทธิ์การใช้งาน (RBAC: User, Technician, Admin)
  - ซ่อนการตั้งค่าความลับด้วย **Environment Variables (`.env`)**

- **Ticket Management:**
  - สร้าง ติดตามสถานะ และจัดการรายการแจ้งซ่อม
  - ระบบอัปโหลดไฟล์แนบประกอบการแจ้งซ่อม/ความคิดเห็น
  - Event-driven Notification แจ้งเตือนเมื่อมีการเปลี่ยนสถานะ Ticket ด้วย Observer Pattern

- **Analytics & Reporting:**
  - **Admin Dashboard & Analytics** แสดงสถิติภาพรวมด้วย **Chart.js**
  - แสดงสัดส่วนตามสถานะ (Status), หมวดหมู่ปัญหา (Category) และระดับความสำคัญ (Priority)

---

## 🛠️ Tech Stack (เทคโนโลยีที่ใช้)

- **Backend:** PHP 8.2, PDO
- **Frontend:** Bootstrap 5, FontAwesome 6, Chart.js
- **Dependencies & Tools:** Composer, PHPMailer, vlucas/phpdotenv
- **Database:** MySQL / MariaDB

---

## 🚀 Installation & Setup (ขั้นตอนการติดตั้ง)

### 1. Clone Repository
```bash
git clone [https://github.com/PHODSATHON021/Smart-IT-Helpdesk.git](https://github.com/PHODSATHON021/Smart-IT-Helpdesk.git)
cd Smart-IT-Helpdesk