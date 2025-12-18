# คู่มือการติดตั้งและอัปเดตระบบ (Setup & Update Guide) v3

เนื่องจากมีการอัปเกรดระบบเพิ่มเติม (Vehicle CRUD) **จำเป็นต้องเพิ่ม Sheet ใหม่ 2 แผ่น (หรือเปลี่ยนชื่อแผ่นเดิม)** ใน Google Spreadsheet เดิมของคุณครับ

## 1. การเปลี่ยนแปลงใน Google Sheets (Database)
กรุณาเปิดไฟล์ Google Sheet เดิมแล้วกด **+ (เพิ่มแผ่นงาน)** เพื่อสร้าง Sheet ใหม่ และตั้งชื่อให้ตรงเป๊ะดังนี้:

### 1.1 Sheet ชื่อ: `Admins`
ใช้สำหรับเก็บข้อมูลผู้ดูแลระบบ (Username/Password)
*   **แถวที่ 1 (Header):** `Username`, `Password`, `Name`, `ProfileImage`
*   **ข้อมูลตัวอย่าง (แถวที่ 2):**
    *   Username: `admin`
    *   Password: `1234`
    *   Name: `Admin IT`
    *   ProfileImage: `https://via.placeholder.com/150` (ใส่ลิงก์รูปภาพ หรือเว้นว่างได้)

### 1.2 Sheet ชื่อ: `Calendar Vehicles`
**(สำคัญ: เปลี่ยนจากเดิมที่ใช้ชื่อ `Vehicles`)**
ใช้สำหรับเก็บข้อมูลรถและปฏิทิน รวมถึงลิงก์ปุ่มดูปฏิทิน
*   **แถวที่ 1 (Header):** `Namecar`, `Calendar ID`, `Url Calendar`, `Car Image`
*   **คำอธิบายคอลัมน์:**
    *   `Namecar`: ชื่อรถ (เช่น [รถ 6 ล้อ][ISUZU]...)
    *   `Calendar ID`: ID ของ Google Calendar สำหรับรถคันนั้น
    *   `Url Calendar`: ลิงก์สำหรับให้พนักงานกดดูปฏิทิน (เช่น https://calendar.google.com/...)
    *   `Car Image`: ลิงก์รูปภาพรถ หรือ ID ของไฟล์ใน Drive (ระบบจัดการให้อัตโนมัติเมื่ออัปโหลด)

### 1.3 Sheet ชื่อ: `Bookings` (อันเดิม)
*   ไม่ต้องลบหรือแก้ไขคอลัมน์ใดๆ ข้อมูลเก่าใช้ต่อได้เลย

---

## 2. การตั้งค่าใน Code (Configuration)
ในไฟล์ `Code.gs` เวอร์ชันใหม่ ระบบจะอ่านข้อมูลจาก Sheet `Admins` และ `Calendar Vehicles` โดยอัตโนมัติ

## 3. วิธีการนำไปใช้งาน (Deployment)
1.  นำไฟล์โค้ดทั้ง 4 ไฟล์ (`Code.gs`, `index.html`, `css.html`, `js.html`) ไปอัปเดตใน Apps Script Editor
2.  กด **Deploy** > **New Deployment** > **Deploy**
3.  นำ URL Web App ไปใช้งานได้เลยครับ
