# Big Apple Restaurant QR Ordering

โครงการเว็บสั่งอาหารด้วย QR Code สำหรับร้าน Big Apple (อาหารยุโรป + ไทย) พัฒนาบนสแต็ก XAMPP / PHP + MySQL พร้อมระบบหลังบ้านสำหรับจัดการออเดอร์และเมนู

## ฟีเจอร์หลัก
- ลูกค้าสแกน QR → เลือกเมนู 5 หมวดหมู่ (อาหารไทย, Breakfast, Lunch, Dinner, Beverage)
- จัดตะกร้า, ระบุโต๊ะ, เลือกชำระ (เงินสด / PromptPay / จ่ายทีหลัง)
- รับหมายเลขออเดอร์และติดตามสถานะผ่านหน้าเว็บ
- หลังบ้านสำหรับพนักงาน: ดูออเดอร์ใหม่, อัพเดทสถานะ/การชำระ, จัดการเมนู, รายงานยอดขาย
- รองรับแจ้งเตือนผ่าน Telegram Bot (เปิดใช้ได้เมื่อกรอก TOKEN + CHAT ID)

## โครงสร้างโฟลเดอร์
```
assets/           ไฟล์ CSS / JS / รูปตัวอย่าง
includes/         ฟังก์ชันส่วนกลาง (database, auth, cart, order, notification)
public/           หน้าลูกค้า (index, cart, checkout, order status)
admin/            หน้าหลังบ้าน (login, dashboard, orders, menu, sales)
sql/bigapple_schema.sql  สคริปต์สร้างฐานข้อมูลและข้อมูลตัวอย่าง
```

## การตั้งค่าเบื้องต้น
1. เปิด XAMPP และเริ่ม Apache + MySQL
2. นำไฟล์โครงการไปไว้ที่ `htdocs/bigapple`
3. เข้าสู่ phpMyAdmin แล้วรันไฟล์ `sql/bigapple_schema.sql`
4. ตั้งค่าไฟล์ `includes/config.php`
   - กำหนด HOST/USER/PASSWORD หากแตกต่างจากค่าเริ่มต้น
   - ใส่ `PROMPTPAY_ACCOUNT` สำหรับสร้าง QR ชำระเงิน
   - หากต้องการแจ้งเตือน Telegram ให้ใส่ `TELEGRAM_BOT_TOKEN` และ `TELEGRAM_CHAT_ID`
5. เปิดใช้งานหน้าเว็บ
   - ลูกค้า: `http://localhost/bigapple/public/index.php`
   - หลังบ้าน: `http://localhost/bigapple/admin/index.php`

## บัญชีตัวอย่าง (Admin)
- Username: `admin`
- Password: `12345`

## การสร้าง QR Code สำหรับแต่ละโต๊ะ
1. ใช้ลิงก์ `http://localhost/bigapple/public/index.php?table=T1` (เพิ่มพารามิเตอร์ตามโต๊ะ)
2. ใช้เครื่องมือสร้าง QR (เช่น qrcode-monkey.com) แล้วพิมพ์ติดที่โต๊ะ
3. สามารถกรอก `qr_code_url` ลงในตาราง `restaurant_tables` เพื่อเก็บอ้างอิงได้

## โลจิสติกส์การใช้งาน
- **Phase 1 (สัปดาห์ 1):** ติดตั้ง XAMPP, import ฐานข้อมูล, ตรวจสอบ config
- **Phase 2 (สัปดาห์ 2):** เพิ่มเมนูจริงผ่านหน้า Admin และอัพโหลดรูป
- **Phase 3 (สัปดาห์ 3-4):** ทดสอบออเดอร์จริง, ผูก PromptPay, เปิดแจ้งเตือน Telegram, ทำคู่มือพนักงาน
- **Phase 4 (สัปดาห์ 5):** ตรวจสอบแดชบอร์ด, สรุปยอดขาย, ปรับเมนู/ราคา, เตรียม QR สำหรับหน้างาน
- **Phase 5 (สัปดาห์ 6):** ทดสอบ UAT, อัพโหลดขึ้นโฮสติ้ง, ตรวจสอบ SSL, Soft launch กับลูกค้า

## การต่อยอดในอนาคต
- ระบบสะสมแต้มและคูปอง (Phase 2)
- ระบบจองโต๊ะล่วงหน้า + แจ้งเตือน
- Web Push Notification / SMS สำรอง
- รองรับหลายสาขาและรายงานขั้นสูง
- พัฒนา Mobile App หรือ PWA

## เคล็ดลับ
- ก่อนใช้งานจริง ให้เปลี่ยนรหัสผ่าน Admin และเพิ่มบัญชี Staff เพิ่มเติมในตาราง `users`
- ตั้ง Cron Job สำรองฐานข้อมูล หรือใช้ phpMyAdmin export รายสัปดาห์
- หากอัพโหลดขึ้น Shared Hosting ให้สร้างไฟล์ `.htaccess` เพื่อกำหนดโฟลเดอร์ public เป็น web root

