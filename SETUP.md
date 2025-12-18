# คู่มือการติดตั้งและอัปเดตระบบ (Setup & Update Guide) v2

เนื่องจากมีการอัปเกรดระบบครั้งใหญ่ (Admin Login, Dynamic Vehicles) **จำเป็นต้องเพิ่ม Sheet ใหม่ 2 แผ่น** ใน Google Spreadsheet เดิมของคุณครับ

## 1. การเปลี่ยนแปลงใน Google Sheets (Database)
กรุณาเปิดไฟล์ Google Sheet เดิมแล้วกด **+ (เพิ่มแผ่นงาน)** เพื่อสร้าง Sheet ใหม่ 2 แผ่น และตั้งชื่อให้ตรงเป๊ะดังนี้:

### 1.1 Sheet ชื่อ: `Admins`
ใช้สำหรับเก็บข้อมูลผู้ดูแลระบบ (Username/Password)
*   **แถวที่ 1 (Header):** `Username`, `Password`, `Name`, `ProfileImage`
*   **ข้อมูลตัวอย่าง (แถวที่ 2):**
    *   Username: `admin`
    *   Password: `1234`
    *   Name: `Admin IT`
    *   ProfileImage: `https://via.placeholder.com/150` (ใส่ลิงก์รูปภาพ หรือเว้นว่างได้)

### 1.2 Sheet ชื่อ: `Vehicles`
ใช้สำหรับเก็บข้อมูลรถและปฏิทิน (แทนการเขียนโค้ด)
*   **แถวที่ 1 (Header):** `VehicleName`, `CalendarID`, `ImageURL`
*   **ข้อมูลตัวอย่าง (ให้ Copy ค่าเดิมไปใส่):**
    *   VehicleName: `[รถ 6 ล้อ][ISUZU][83-0506 ระยอง]`
    *   CalendarID: `0bd835150784...group.calendar.google.com`
    *   ImageURL: (ใส่ลิงก์รูปรูปรถถ้ามี)

(ทำซ้ำให้ครบทุกคันที่มีอยู่เดิม)

### 1.3 Sheet ชื่อ: `Bookings` (อันเดิม)
*   ไม่ต้องลบหรือแก้ไขคอลัมน์ใดๆ ข้อมูลเก่าใช้ต่อได้เลย

---

## 2. การตั้งค่าใน Code (Configuration)
ในไฟล์ `Code.gs` เวอร์ชันใหม่ ระบบจะอ่านข้อมูลจาก Sheet `Admins` และ `Vehicles` โดยอัตโนมัติ
*   **ADMIN_PASS เดิม**: ถูกยกเลิก (เปลี่ยนไปใช้ Username/Password ใน Sheet `Admins` แทน)
*   **CALENDAR_BY_CAR เดิม**: ถูกยกเลิก (เปลี่ยนไปอ่านจาก Sheet `Vehicles` แทน)

## 3. สิทธิ์การเข้าถึง (Permissions)
*   ใช้สิทธิ์เดิม (Sheets, Calendar, Mail, UrlFetch) ไม่ต้องกด Allow ใหม่หากเคยกดไปแล้ว

## 4. วิธีการนำไปใช้งาน (Deployment)
1.  นำไฟล์โค้ดทั้ง 4 ไฟล์ (`Code.gs`, `index.html`, `css.html`, `js.html`) ไปอัปเดตใน Apps Script Editor
2.  กด **Deploy** > **New Deployment** > **Deploy**
3.  นำ URL Web App ไปใช้งานได้เลยครับ
