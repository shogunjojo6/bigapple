# คู่มือการติดตั้งและอัปเดตระบบ (Setup & Update Guide) v4 (Full Admin System)

เนื่องจากมีการอัปเกรดระบบครั้งใหญ่เพื่อรองรับการจัดการ Admin และ รถยนต์ผ่านหน้าเว็บได้โดยตรง **ระบบจะต้องการโครงสร้าง Sheet ใหม่** ดังนี้ครับ

**ข้อแนะนำ:** วิธีที่ง่ายที่สุดคือ **ลบ Sheet `Admins` และ `Calendar Vehicles` เดิมทิ้ง (ถ้ามี)** แล้วรันระบบ 1 ครั้ง ระบบจะสร้าง Sheet และโครงสร้างคอลัมน์ที่ถูกต้องให้โดยอัตโนมัติครับ

แต่หากต้องการสร้างเองด้วยมือ ให้ทำตามโครงสร้างนี้ครับ:

## 1. การเปลี่ยนแปลงใน Google Sheets (Database)

### 1.1 Sheet ชื่อ: `Admins`
ใช้สำหรับเก็บข้อมูลผู้ดูแลระบบ และการล็อกอิน
*   **จำนวนคอลัมน์:** 10 คอลัมน์
*   **แถวที่ 1 (Header):**
    `AdminId`, `Username`, `PasswordHash`, `FullName`, `Email`, `Role`, `AvatarFileId`, `IsActive`, `CreatedAt`, `UpdatedAt`

### 1.2 Sheet ชื่อ: `Calendar Vehicles`
ใช้สำหรับเก็บข้อมูลรถ ปฏิทิน รูปภาพ และสถานะการเปิด/ปิดใช้งาน
*   **จำนวนคอลัมน์:** 8 คอลัมน์
*   **แถวที่ 1 (Header):**
    `ItemId`, `DisplayName`, `ImageFileId`, `CalendarId`, `ButtonLabel`, `IsActive`, `CreatedAt`, `UpdatedAt`

### 1.3 Sheet ชื่อ: `Bookings` (อันเดิม)
ใช้เก็บข้อมูลการจอง
*   **เพิ่มเติม:** ระบบจะเพิ่มคอลัมน์ใหม่ต่อท้ายโดยอัตโนมัติ (DriveOption, HasLicense, DriverName) ไม่ต้องลบข้อมูลเก่าครับ

---

## 2. การตั้งค่าโฟลเดอร์รูปภาพ (Google Drive)
ระบบจะทำการสร้างโฟลเดอร์ชื่อ `VehicleSys_Assets` ใน Google Drive ของเจ้าของ Script ให้โดยอัตโนมัติ เมื่อมีการอัปโหลดรูปภาพครั้งแรก

## 3. วิธีการนำไปใช้งาน (Deployment)
1.  นำไฟล์โค้ดทั้งหมด (`Code.gs`, `index.html`, `css.html`, `js.html`, `admin_dashboard.html`) ไปอัปเดตใน Apps Script Editor
2.  ตรวจสอบว่าได้เพิ่มไฟล์ `admin_dashboard.html` เข้าไปในโปรเจกต์แล้ว
3.  กด **Deploy** > **New Deployment** > **Deploy**
4.  **Admin Login เริ่มต้น:**
    *   Username: `admin`
    *   Password: `1234`
