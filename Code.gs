// ---------------- Configuration ----------------
const ADMIN_EMAILS = [
  '67319010023@tatc.ac.th', // สำหรับรับอีเมลแจ้งเตือน
];
// Web App URL for admin notification link (Leave empty to auto-detect current URL)
const ADMIN_NOTIFY_LINK = 'https://script.google.com/macros/s/AKfycbzGwSyu1CmexJYIlu0TK-HJ9Rg7YwdHG9XvchbwV4vhD3M90FkFhHGACaAFPPLrdy8c/exec';
const ADMIN_CHECK_LINK_OVERRIDE = '';

const SHEET_NAME = 'Bookings';
const SPREADSHEET_ID = '1SYK7LTcyiZpP4udPdTeqGWeHCy5ze4b6KamEJDa9G-E';

const SHEET_ADMINS = 'Admins';
const SHEET_VEHICLES = 'Calendar Vehicles';

// ---------------- Web App Serving ----------------
function doGet() {
  try {
    const template = HtmlService.createTemplateFromFile('index');
    return template.evaluate()
        .setTitle('Vehicle Booking System')
        .setSandboxMode(HtmlService.SandboxMode.IFRAME)
        .addMetaTag('viewport', 'width=device-width, initial-scale=1');
  } catch (e) {
    return ContentService.createTextOutput("System Error: " + e.message);
  }
}

function include(filename) {
  try {
    return HtmlService.createHtmlOutputFromFile(filename).getContent();
  } catch (e) {
    return "<script>console.error('Error loading " + filename + ": " + e.message + "'); alert('Error loading component: " + filename + "');</script>";
  }
}

function getCheckLink_() {
  const autoUrl = ScriptApp.getService().getUrl();
  return ADMIN_CHECK_LINK_OVERRIDE || autoUrl || ADMIN_NOTIFY_LINK || 'https://example.com';
}

function preservePhone_(v){ const s=String(v||'').trim(); return s && /^\d+$/.test(s) ? "'" + s : s; }

// ---------------- Admin Management ----------------

// Helper: Hash Password (SHA-256)
function hashPassword_(raw) {
  if (!raw) return '';
  const digest = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, raw);
  let hex = '';
  for (let i = 0; i < digest.length; i++) {
    let byte = digest[i]; // Use 'let' because we modify it
    if (byte < 0) byte += 256;
    const bStr = byte.toString(16);
    hex += (bStr.length === 1 ? '0' : '') + bStr;
  }
  return hex;
}

function getAdminsSheet_() {
  // Defensive sheet opening
  let ss;
  try {
    ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  } catch(e) {
    throw new Error("Cannot open Spreadsheet. Please check ID and Permissions.");
  }

  let sheet = ss.getSheetByName(SHEET_ADMINS);
  if (!sheet) {
     sheet = ss.insertSheet(SHEET_ADMINS);
     // 1.AdminId 2.Username 3.PasswordHash 4.FullName 5.Email 6.Role 7.AvatarFileId 8.IsActive 9.CreatedAt 10.UpdatedAt
     sheet.appendRow([
       'AdminId', 'Username', 'PasswordHash', 'FullName', 'Email', 'Role', 'AvatarFileId', 'IsActive', 'CreatedAt', 'UpdatedAt'
     ]);
     // Default admin
     const now = new Date();
     sheet.appendRow([
       '1', 'admin', hashPassword_('1234'), 'System Admin', '', 'SuperAdmin', '', true, now, now
     ]);
  }
  return sheet;
}

function adminLogin(username, password) {
  try {
    const sheet = getAdminsSheet_();
    const inputHash = hashPassword_(password);
    const data = sheet.getDataRange().getValues();

    for (let i = 1; i < data.length; i++) {
      const storedUser = String(data[i][1]).trim();
      const storedPass = String(data[i][2]).trim();
      const isActive   = data[i][7];

      const inputUserNormal = String(username).trim();

      if (storedUser.toLowerCase() === inputUserNormal.toLowerCase()) {
        if (storedPass.toLowerCase() !== inputHash.toLowerCase()) return { success: false, message: 'Password incorrect.' };

        const isActiveBool = (isActive === true) || (String(isActive).toLowerCase() === 'true') || (isActive === 1);
        if (isActiveBool) {
           return {
             success: true,
             user: {
               id: data[i][0],
               username: storedUser,
               name: data[i][3],
               email: data[i][4],
               role: data[i][5],
               image: data[i][6]
             }
           };
        } else {
           return { success: false, message: 'Account is inactive.' };
        }
      }
    }
    return { success: false, message: 'Username not found.' };
  } catch (e) {
    return { success: false, message: 'Login Error: ' + e.message };
  }
}

function isAuthenticated_(auth) {
  if (!auth || !auth.username || !auth.password) return false;
  const res = adminLogin(auth.username, auth.password);
  return res.success;
}

// Get all admins for management list
function getAllAdmins(auth) {
  if (!isAuthenticated_(auth)) return { success: false, message: 'Unauthorized' };
  try {
    const sheet = getAdminsSheet_();
    const data = sheet.getDataRange().getValues();
    const admins = [];
    for(let i=1; i<data.length; i++){
      admins.push({
        id: data[i][0],
        username: data[i][1],
        fullName: data[i][3],
        email: data[i][4],
        role: data[i][5],
        avatar: data[i][6],
        isActive: data[i][7],
        updatedAt: data[i][9]
      });
    }
    return { success: true, admins };
  } catch(e) { return { success: false, message: e.message }; }
}

// Create or Update Admin
function saveAdmin(data, auth) {
  if (!isAuthenticated_(auth)) return { success: false, message: 'Unauthorized' };
  try {
    const sheet = getAdminsSheet_();
    const rows = sheet.getDataRange().getValues();
    const now = new Date();

    // If ID exists, UPDATE
    if (data.id) {
      for(let i=1; i<rows.length; i++) {
        if(String(rows[i][0]) === String(data.id)) {
          // Update fields
          const range = sheet.getRange(i+1, 1, 1, 10);
          const row = rows[i];

          if(data.password) row[2] = hashPassword_(data.password); // Hash new password
          if(data.fullName) row[3] = data.fullName;
          if(data.email !== undefined) row[4] = data.email;
          if(data.role) row[5] = data.role;
          if(data.avatar) row[6] = data.avatar;
          if(data.isActive !== undefined) row[7] = data.isActive;
          row[9] = now; // UpdatedAt

          range.setValues([row]);
          return { success: true, message: 'Admin updated' };
        }
      }
      return { success: false, message: 'Admin ID not found' };
    }

    // Create NEW
    // Check username duplicate
    for(let i=1; i<rows.length; i++) {
      if(String(rows[i][1]).toLowerCase() === String(data.username).toLowerCase()) {
        return { success: false, message: 'Username already exists' };
      }
    }

    const newId = new Date().getTime().toString(); // Simple ID
    const passHash = hashPassword_(data.password || '1234'); // Default or provided
    sheet.appendRow([
      newId,
      data.username,
      passHash,
      data.fullName || '',
      data.email || '',
      data.role || 'Admin',
      data.avatar || '',
      true, // Active by default
      now,
      now
    ]);
    return { success: true, message: 'Admin created' };

  } catch(e) { return { success: false, message: e.message }; }
}


// ---------------- Vehicle Management ----------------

function getVehiclesSheet_() {
  let ss;
  try {
    ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  } catch(e) {
    throw new Error("Cannot open Spreadsheet: " + e.message);
  }

  let sheet = ss.getSheetByName(SHEET_VEHICLES);
  if (!sheet) {
     sheet = ss.insertSheet(SHEET_VEHICLES);
     // 1.ItemId 2.DisplayName 3.ImageFileId 4.CalendarId 5.ButtonLabel 6.IsActive 7.CreatedAt 8.UpdatedAt
     sheet.appendRow(['ItemId', 'DisplayName', 'ImageFileId', 'CalendarId', 'ButtonLabel', 'IsActive', 'CreatedAt', 'UpdatedAt']);
  }
  return sheet;
}

function getAllVehicles(auth) {
  const isAdmin = auth && auth.username;

  try {
    const sheet = getVehiclesSheet_();
    const data = sheet.getDataRange().getValues();
    const vehicles = [];
    for (let i = 1; i < data.length; i++) {
      // Robust Active Check
      const val = data[i][5];
      const isActive = (val === true || String(val).toLowerCase() === 'true' || val === 1);

      if (!isAdmin && !isActive) continue; // Hide inactive from public

      vehicles.push({
        id: data[i][0],
        name: data[i][1],
        image: data[i][2],
        calendarId: data[i][3],
        buttonLabel: data[i][4],
        isActive: isActive,
        updatedAt: data[i][7]
      });
    }
    return { success: true, vehicles };
  } catch (e) {
    return { success: false, message: "DB Error: " + e.message };
  }
}

// Public wrapper for frontend
function getPublicVehicles() {
  return getAllVehicles(null); // No auth -> only active
}

function saveVehicle(data, auth) {
  if (!isAuthenticated_(auth)) return { success: false, message: 'Unauthorized' };

  try {
    const sheet = getVehiclesSheet_();
    const rows = sheet.getDataRange().getValues();
    const now = new Date();

    // EDIT
    if (data.id) {
       for (let i = 1; i < rows.length; i++) {
          if (String(rows[i][0]) === String(data.id)) {
             const row = rows[i];
             if(data.name) row[1] = data.name;
             if(data.image) row[2] = data.image;
             if(data.calendarId) row[3] = data.calendarId;
             if(data.buttonLabel) row[4] = data.buttonLabel;
             if(data.isActive !== undefined) row[5] = data.isActive;
             row[7] = now;
             sheet.getRange(i+1, 1, 1, 8).setValues([row]);
             return { success: true };
          }
       }
       return { success: false, message: 'Vehicle not found.' };
    }

    // ADD
    const newId = 'V' + new Date().getTime();
    sheet.appendRow([
      newId,
      data.name,
      data.image || '',
      data.calendarId || '',
      data.buttonLabel || 'View Calendar',
      true, // Active
      now,
      now
    ]);
    return { success: true };
  } catch (e) { return { success: false, message: e.message }; }
}

function deleteVehicle(id, auth) {
  if (!isAuthenticated_(auth)) return { success: false, message: 'Unauthorized' };
  try {
    const sheet = getVehiclesSheet_();
    const rows = sheet.getDataRange().getValues();
    for(let i=1; i<rows.length; i++) {
       if(String(rows[i][0]) === String(id)) {
          sheet.deleteRow(i+1);
          return { success: true };
       }
    }
    return { success: false, message: 'Not found' };
  } catch (e) { return { success: false, message: e.message }; }
}

// Helper to get Cal ID map dynamically
function getVehicleMap_() {
  // Return { "CarName": "CalID" }
  const res = getAllVehicles({username:'system'}); // Get all even if inactive, for admin safety/lookup
  if (!res.success) return {};
  const map = {};
  res.vehicles.forEach(v => {
    if (v.name) map[v.name] = v.calendarId;
  });
  return map;
}

function uploadFile(data, mimeType, filename) {
  try {
    const folderName = "VehicleSys_Assets";
    const folders = DriveApp.getFoldersByName(folderName);
    let folder;
    if (folders.hasNext()) {
      folder = folders.next();
    } else {
      folder = DriveApp.createFolder(folderName);
    }

    const blob = Utilities.newBlob(Utilities.base64Decode(data), mimeType, filename);
    const file = folder.createFile(blob);
    file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);

    return { success: true, fileId: file.getId(), url: `https://drive.google.com/uc?export=view&id=${file.getId()}` };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ---------------- Submit ----------------
function submitBooking(formData) {
  try {
    const sheet = getSheet_();
    const data = sanitizeForm_(formData);
    const validationError = validateForm_(data);
    if (validationError) return { success: false, message: validationError };

    // Verify car name exists in Active Vehicles
    const publicVehicles = getPublicVehicles();
    if (data.car !== 'อื่นๆ ให้ระบุ') {
       const isValid = publicVehicles.success && publicVehicles.vehicles.some(v => v.name === data.car);
       if (!isValid) return { success: false, message: 'รถที่เลือกไม่ถูกต้องหรือถูกปิดการใช้งานแล้ว' };
    }

    if (hasPendingOverlap_(sheet, data)) return { success: false, message: 'เวลาซ้ำกับคำขอค้างอยู่ กรุณาเลือกเวลาใหม่' };
    if (hasCalendarOverlap_(data)) return { success: false, message: 'เวลาซ้ำกับปฏิทิน กรุณาเลือกเวลาใหม่' };

    const endTimeToStore = data.returnTime || data.endTime || data.startTime;
    const now = new Date();

    const oContact = preservePhone_(data.originContact);
    const d1Contact = preservePhone_(data.dest1Contact);
    const d2Contact = preservePhone_(data.dest2Contact);

    sheet.appendRow([
      now,
      data.name, data.department, data.workTypes, data.vehicleTypes, data.car,
      data.date, data.startTime, endTimeToStore,
      data.originPlace, data.originAddress, oContact, data.originReason, data.originMap,
      data.dest1Place, data.dest1Address, d1Contact, data.dest1Reason, data.dest1Map,
      data.dest2Place, data.dest2Address, d2Contact, data.dest2Reason, data.dest2Map,
      data.extraDetails, 'Pending', '', '', data.email, '', '', data.returnDate, data.returnTime,
      data.driveOption, data.hasLicense, data.driverName
    ]);

    try { notifyAdmins_(data, now); } catch (e) { console.error('Notify admin failed:', e); }
    return { success: true, message: 'บันทึกคำขอจองเรียบร้อย' };
  } catch (err) {
    console.error(err);
    return { success: false, message: 'Error submitting booking: ' + err.message };
  }
}

// ---------------- Read ----------------
function getBookings() {
  let resp = { ok: false, bookings: [], isAdmin: false, email: '', error: '' };
  try {
    const sheet = getSheet_();
    const values = sheet.getDataRange().getValues() || [];
    const rows = values.slice(1);
    const tz = Session.getScriptTimeZone();
    const email = (Session.getActiveUser() && Session.getActiveUser().getEmail()) || '';
    const isAdmin = ADMIN_EMAILS.includes(email);

    const bookings = rows.map((r, idx) => {
      const rowNumber = idx + 2;
      return {
        rowNumber,
        timestamp: r[0] ? Utilities.formatDate(new Date(r[0]), tz, 'yyyy-MM-dd HH:mm') : '',
        name: r[1] || '', department: r[2] || '',
        workTypes: r[3] || '', vehicleTypes: r[4] || '', car: r[5] || '',
        date: normalizeDate_(r[6], tz), startTime: normalizeTime_(r[7]), endTime: normalizeTime_(r[8]),
        originPlace: r[9] || '', originAddress: r[10] || '', originContact: r[11] || '',
        originReason: r[12] || '', originMap: r[13] || '',
        dest1Place: r[14] || '', dest1Address: r[15] || '', dest1Contact: r[16] || '',
        dest1Reason: r[17] || '', dest1Map: r[18] || '',
        dest2Place: r[19] || '', dest2Address: r[20] || '', dest2Contact: r[21] || '',
        dest2Reason: r[22] || '', dest2Map: r[23] || '',
        extraDetails: r[24] || '', status: r[25] || 'Pending',
        approver: r[26] || '', approvedAt: r[27] ? Utilities.formatDate(new Date(r[27]), tz, 'yyyy-MM-dd HH:mm') : '',
        requesterEmail: r[28] || '', rejectionReason: r[29] || '', eventId: r[30] || '',
        returnDate: normalizeDate_(r[31], tz), returnTime: normalizeTime_(r[32]),
        driveOption: r[33] || '', hasLicense: r[34] || false, driverName: r[35] || ''
      };
    }).reverse();

    resp = { ok: true, bookings, isAdmin, email, error: '' };
  } catch (err) {
    console.error(err); resp.error = err.message || String(err);
  }
  return resp;
}

// ---------------- Stats ----------------
function getStats(startStr, endStr) {
  try {
    const sheet = getSheet_();
    const values = sheet.getDataRange().getValues() || [];
    const rows = values.slice(1);
    const start = startStr ? new Date(`${startStr}T00:00:00`) : null;
    const end = endStr ? new Date(`${endStr}T23:59:59`) : null;

    const filtered = rows.filter(r => {
      const dStart = new Date(r[6]);
      const dEnd = r[31] ? new Date(r[31]) : dStart;
      if (start && dEnd < start) return false;
      if (end && dStart > end) return false;
      return true;
    });

    const total = filtered.length;
    const byCar = {};
    const byWork = {};
    filtered.forEach(r => {
      const car = r[5] || 'ไม่ระบุ';
      byCar[car] = (byCar[car] || 0) + 1;
      const wt = r[3] || 'ไม่ระบุ';
      byWork[wt] = (byWork[wt] || 0) + 1;
    });

    return { success: true, total, byCar, byWork, start: startStr || '', end: endStr || '' };
  } catch (err) {
    console.error(err);
    return { success: false, message: err.message || String(err) };
  }
}

// ---------------- Summary PDF ----------------
function generateSummaryPdf(startStr, endStr) {
  try {
    const stats = getStats(startStr, endStr);
    if (!stats.success) return { success:false, message: stats.message || 'สร้างสรุปไม่สำเร็จ' };

    const html = buildSummaryPdfHtml_(stats);
    const pdfBlob = HtmlService.createHtmlOutput(html).getBlob().getAs('application/pdf');
    const filename = `summary_${startStr||'all'}_${endStr||'all'}.pdf`;
    const base64 = Utilities.base64Encode(pdfBlob.getBytes());
    return { success:true, filename, data: base64 };
  } catch (err) {
    console.error(err);
    return { success:false, message: err.message || String(err) };
  }
}

function buildSummaryPdfHtml_(s){
  const colors = ['#1e4fd7','#0ea5e9','#10b981','#ff8c42','#8b5cf6','#ef4444','#14b8a6','#f59e0b','#22c55e'];
  const donutCar = buildDonutSvg_(s.byCar || {}, colors);
  const donutWork = buildDonutSvg_(s.byWork || {}, colors);

  return `
  <html><head><style>
    @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
    body{font-family:'Kanit',sans-serif;padding:24px;color:#0f172a;}
    h1{margin:0 0 12px;}
    .block{border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:12px;}
    .title{font-weight:700;margin-bottom:6px;color:#475569;}
    .flex{display:flex;gap:12px;align-items:center;}
    .legend{font-size:12px;}
    .legend div{margin:2px 0;}
    .dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:6px;}
  </style></head><body>
    <h1>สรุปการใช้งานรถ</h1>
    <div class="block"><div class="title">ช่วงวัน</div><div>${escape_(s.start||'-')} ถึง ${escape_(s.end||'-')}</div></div>
    <div class="block"><div class="title">จำนวนรวม</div><div>รายการทั้งหมด: <b>${escape_(s.total)}</b></div></div>
    <div class="block"><div class="title">จองตามรถ</div>${donutCar}</div>
    <div class="block"><div class="title">จองตามประเภทงาน</div>${donutWork}</div>
  </body></html>`;
}

function buildDonutSvg_(obj, colors){
  const entries = Object.entries(obj||{});
  const total = entries.reduce((s,[,v])=>s+v,0);
  const cx=120, cy=120, rOuter=90, rInner=55;
  if(total === 0){
    return `<div class="flex"><svg width="240" height="240" viewBox="0 0 240 240">
      <circle cx="${cx}" cy="${cy}" r="${rOuter}" fill="#e2e8f0"></circle>
      <circle cx="${cx}" cy="${cy}" r="${rInner}" fill="#fff"></circle>
    </svg><div class="legend">ไม่มีข้อมูล</div></div>`;
  }

  let current = -Math.PI/2;
  const parts = entries.map(([label,value], idx)=>{
    const frac = value/total;
    let angle = frac * 2*Math.PI;
    if (idx === entries.length-1) {
      angle = 2*Math.PI - (current + Math.PI/2);
    }
    const start = current;
    const end = current + angle;
    current = end;

    const largeArc = angle > Math.PI ? 1 : 0;
    const x1 = cx + rOuter * Math.cos(start);
    const y1 = cy + rOuter * Math.sin(start);
    const x2 = cx + rOuter * Math.cos(end);
    const y2 = cy + rOuter * Math.sin(end);
    const x3 = cx + rInner * Math.cos(end);
    const y3 = cy + rInner * Math.sin(end);
    const x4 = cx + rInner * Math.cos(start);
    const y4 = cy + rInner * Math.sin(start);

    const pathData = [
      `M ${x1} ${y1}`,
      `A ${rOuter} ${rOuter} 0 ${largeArc} 1 ${x2} ${y2}`,
      `L ${x3} ${y3}`,
      `A ${rInner} ${rInner} 0 ${largeArc} 0 ${x4} ${y4}`,
      'Z'
    ].join(' ');

    return {
      pathData,
      color: colors[idx % colors.length],
      label,
      value,
      pct: (frac*100).toFixed(1)
    };
  });

  const paths = parts.map(p=>`<path d="${p.pathData}" fill="${p.color}" stroke="#fff" stroke-width="1"></path>`).join('');
  const legend = parts.map(p=>`<div><span class="dot" style="background:${p.color}"></span>${escape_(p.label)}: ${p.value} (${p.pct}%)</div>`).join('');
  return `<div class="flex"><svg width="240" height="240" viewBox="0 0 240 240">
    ${paths}
    <circle cx="${cx}" cy="${cy}" r="${rInner}" fill="#fff"></circle>
  </svg><div class="legend">${legend}</div></div>`;
}

// ---------------- Update status ----------------
function updateBookingStatus(rowNumber, newStatus, reason, adminUser, auth) {
  try {
    if (!isAuthenticated_(auth)) {
       return { success: false, message: 'Unauthorized: Invalid credentials.' };
    }
    const adminName = (adminUser && adminUser.name) ? adminUser.name : (auth.username || 'Admin');

    if (!['Approved', 'Rejected'].includes(newStatus)) return { success: false, message: 'Invalid status.' };

    const sheet = getSheet_();
    const lastRow = sheet.getLastRow();
    if (!rowNumber || rowNumber < 2 || rowNumber > lastRow) return { success: false, message: 'Row out of range.' };

    const row = sheet.getRange(rowNumber, 1, 1, 33).getValues()[0];
    const requesterEmail = row[28] || '';
    const booking = rowToBooking_(row);

    if (newStatus === 'Approved') {
      const shouldCreateEvent = booking.car && booking.car !== 'อื่นๆ ให้ระบุ';
      if (shouldCreateEvent) {
        const start = parseDateTime_(booking.date, booking.startTime);
        const end = parseDateTime_(booking.returnDate || booking.date, booking.returnTime || booking.endTime);
        if (!start || !end) return { success: false, message: 'Invalid date/time for this booking.' };
        if (hasCalendarOverlap_(booking)) return { success: false, message: 'เวลาซ้ำกับปฏิทิน กรุณาเลือกเวลาใหม่' };

        // Dynamic Calendar Lookup
        const calMap = getVehicleMap_();
        const calId = calMap[booking.car];

        if (!calId) return { success: false, message: `ยังไม่ได้ตั้งค่า Calendar ID สำหรับรถ ${booking.car}` };
        const calendar = CalendarApp.getCalendarById(calId);
        if (!calendar) return { success: false, message: `ไม่พบปฏิทินของรถ ${booking.car}` };

        const title = `${booking.car} | ${booking.name}`;
        const description = buildEventDescription_(booking);
        const ev = calendar.createEvent(title, start, end, { description, location: booking.dest1Place || booking.originPlace || '' });
        const eventId = ev.getId();
        const approvedAt = new Date();
        sheet.getRange(rowNumber, 26, 1, 6).setValues([['Approved', adminName, approvedAt, row[28], '', eventId]]);
      } else {
        const approvedAt = new Date();
        sheet.getRange(rowNumber, 26, 1, 6).setValues([['Approved', adminName, approvedAt, row[28], '', '']]);
      }

      if (requesterEmail) {
        try {
          const pdfRes = generateBookingPdf(rowNumber);
          if (pdfRes && pdfRes.success) sendUserResultMail_(requesterEmail, booking, 'Approved', '', pdfRes);
        } catch (e) { console.error('Send approve mail failed:', e); }
      }
      return { success: true, message: 'Approved (สร้าง event เฉพาะรถที่ไม่ใช่ “อื่นๆ ให้ระบุ”).' };
    }

    const approvedAt = new Date();
    removeEventForBooking_(booking);
    sheet.getRange(rowNumber, 26, 1, 6).setValues([[newStatus, adminName, approvedAt, row[28], reason || '', '']]);
    if (requesterEmail) {
      try {
        const pdfRes = generateBookingPdf(rowNumber);
        sendUserResultMail_(requesterEmail, booking, 'Rejected', reason || '', pdfRes && pdfRes.success ? pdfRes : null);
      } catch (e) { console.error('Send reject mail failed:', e); }
    }
    return { success: true, message: 'Booking rejected.' };
  } catch (err) {
    console.error(err);
    return { success: false, message: 'Error updating status: ' + err.message };
  }
}

// ---------------- Calendar fetch/remove ----------------
function getCalendarBookings(car, startDateStr, endDateStr) {
  try {
    const calMap = getVehicleMap_();
    const calId = calMap[car];
    if (!calId) return { success: false, events: [], message: `ยังไม่ได้ตั้งค่า Calendar ID สำหรับรถ ${car}` };
    const calendar = CalendarApp.getCalendarById(calId);
    if (!calendar) return { success: false, events: [], message: `ไม่พบปฏิทินของรถ ${car}` };

    const tz = Session.getScriptTimeZone();
    const startDate = startDateStr ? new Date(`${startDateStr}T00:00:00`) : new Date();
    const endBase = endDateStr ? new Date(`${endDateStr}T23:59:59`) : new Date(new Date().getTime() + 30 * 24 * 60 * 60 * 1000);
    const events = calendar.getEvents(startDate, endBase).map(ev => ({
      id: ev.getId(), title: ev.getTitle(),
      start: Utilities.formatDate(ev.getStartTime(), tz, 'yyyy-MM-dd HH:mm'),
      end: Utilities.formatDate(ev.getEndTime(), tz, 'yyyy-MM-dd HH:mm'),
      description: ev.getDescription() || '', location: ev.getLocation() || '',
    }));
    events.sort((a, b) => new Date(a.start) - new Date(b.start));
    return { success: true, events, message: '' };
  } catch (err) {
    console.error(err);
    return { success: false, events: [], message: 'Error reading calendar: ' + err.message };
  }
}

function removeEventForBooking_(booking) {
  const calMap = getVehicleMap_();
  const calId = calMap[booking.car];
  if (!calId) return;
  const cal = CalendarApp.getCalendarById(calId);
  if (!cal) return;

  if (booking.eventId) {
    try {
      const ev = cal.getEventById(booking.eventId);
      if (ev) { ev.deleteEvent(); return; }
    } catch (e) { /* fallback */ }
  }

  const start = parseDateTime_(booking.date, booking.startTime);
  const end = parseDateTime_(booking.returnDate || booking.date, booking.returnTime || booking.endTime);
  if (!start || !end) return;
  const events = cal.getEvents(start, end);
  for (const ev of events) {
    const title = ev.getTitle() || '';
    if (title.includes(booking.car) && title.includes(booking.name)) {
      ev.deleteEvent();
      break;
    }
  }
}

// ---------------- Booking PDF ----------------
function generateBookingPdf(rowNumber) {
  try {
    const sheet = getSheet_();
    const lastRow = sheet.getLastRow();
    if (!rowNumber || rowNumber < 2 || rowNumber > lastRow) return { success: false, message: 'Row out of range.' };
    const row = sheet.getRange(rowNumber, 1, 1, 33).getValues()[0];
    const status = row[25] || 'Pending';
    if (status !== 'Approved' && status !== 'Rejected') return { success: false, message: 'Only approved/rejected bookings can export PDF.' };

    const tz = Session.getScriptTimeZone();
    const data = rowToBooking_(row);
    data.timestamp = data.timestamp ? Utilities.formatDate(new Date(data.timestamp), tz, 'yyyy-MM-dd HH:mm') : '';
    data.approvedAt = data.approvedAt ? Utilities.formatDate(new Date(data.approvedAt), tz, 'yyyy-MM-dd HH:mm') : '';
    const qr = buildQrImages_(data);
    const html = buildPdfHtml_(data, qr);
    const pdfBlob = HtmlService.createHtmlOutput(html).getBlob().getAs('application/pdf');
    const filename = `booking_${data.car}_${data.date}_${(data.startTime||'').replace(':','')}.pdf`;
    const base64 = Utilities.base64Encode(pdfBlob.getBytes());
    return { success: true, filename, data: base64, blob: pdfBlob };
  } catch (err) {
    console.error(err);
    return { success: false, message: 'Error generating PDF: ' + err.message };
  }
}

// ---------------- Helpers ----------------
function rowToBooking_(r){
  return {
    timestamp: r[0], name: r[1] || '', department: r[2] || '', workTypes: r[3] || '',
    vehicleTypes: r[4] || '', car: r[5] || '', date: normalizeDate_(r[6], Session.getScriptTimeZone()),
    startTime: normalizeTime_(r[7]), endTime: normalizeTime_(r[8]),
    originPlace: r[9] || '', originAddress: r[10] || '', originContact: r[11] || '',
    originReason: r[12] || '', originMap: r[13] || '',
    dest1Place: r[14] || '', dest1Address: r[15] || '', dest1Contact: r[16] || '',
    dest1Reason: r[17] || '', dest1Map: r[18] || '',
    dest2Place: r[19] || '', dest2Address: r[20] || '', dest2Contact: r[21] || '',
    dest2Reason: r[22] || '', dest2Map: r[23] || '',
    extraDetails: r[24] || '', status: r[25] || 'Pending',
    approver: r[26] || '', approvedAt: r[27],
    requesterEmail: r[28] || '', rejectionReason: r[29] || '', eventId: r[30] || '',
    returnDate: normalizeDate_(r[31], Session.getScriptTimeZone()),
    returnTime: normalizeTime_(r[32]),
    driveOption: r[33] || '', hasLicense: r[34] || false, driverName: r[35] || ''
  };
}

function getSheet_() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  const sheet = ss.getSheetByName(SHEET_NAME) || ss.insertSheet(SHEET_NAME);
  ensureHeader_(sheet);
  return sheet;
}

function ensureHeader_(sheet) {
  const header = [
    'Timestamp','RequesterName','Department','WorkTypes','VehicleTypes','Car','Date','StartTime','EndTime',
    'OriginPlace','OriginAddress','OriginContact','OriginReason','OriginMap',
    'Dest1Place','Dest1Address','Dest1Contact','Dest1Reason','Dest1Map',
    'Dest2Place','Dest2Address','Dest2Contact','Dest2Reason','Dest2Map',
    'ExtraDetails','Status','Approver','ApprovedAt','RequesterEmail','RejectionReason','EventId',
    'ReturnDate','ReturnTime', 'DriveOption', 'HasLicense', 'DriverName'
  ];
  const firstRow = sheet.getRange(1, 1, 1, header.length).getValues()[0];
  const needsHeader = firstRow.some((cell, idx) => cell !== header[idx]);
  if (needsHeader) sheet.getRange(1, 1, 1, header.length).setValues([header]);
}

function sanitizeForm_(formData) {
  const safe = v => (v === null || v === undefined) ? '' : String(v).trim();
  return {
    name: safe(formData.name),
    department: safe(formData.department),
    workTypes: safe(formData.workTypes),
    vehicleTypes: safe(formData.vehicleTypes),
    car: safe(formData.car),
    date: safe(formData.date),
    startTime: safe(formData.startTime),
    endTime: safe(formData.returnTime || formData.endTime),
    originPlace: safe(formData.originPlace),
    originAddress: safe(formData.originAddress),
    originContact: safe(formData.originContact),
    originReason: safe(formData.originReason),
    originMap: safe(formData.originMap),
    dest1Place: safe(formData.dest1Place),
    dest1Address: safe(formData.dest1Address),
    dest1Contact: safe(formData.dest1Contact),
    dest1Reason: safe(formData.dest1Reason),
    dest1Map: safe(formData.dest1Map),
    dest2Place: safe(formData.dest2Place),
    dest2Address: safe(formData.dest2Address),
    dest2Contact: safe(formData.dest2Contact),
    dest2Reason: safe(formData.dest2Reason),
    dest2Map: safe(formData.dest2Map),
    extraDetails: safe(formData.extraDetails),
    email: safe(formData.email),
    returnDate: safe(formData.returnDate),
    returnTime: safe(formData.returnTime),
    driveOption: safe(formData.driveOption),
    hasLicense: formData.hasLicense === true || formData.hasLicense === 'true',
    driverName: safe(formData.driverName)
  };
}

function validateForm_(data) {
  const required = ['name','department','car','date','startTime','originPlace','originAddress','originContact','email','returnDate','returnTime'];
  for (const key of required) if (!data[key]) return `Missing field: ${key}`;
  if (!/@qtc-energy\.com$/i.test(data.email)) return 'กรุณากรอกอีเมล @qtc-energy.com';
  const start = parseDateTime_(data.date, data.startTime);
  const end = parseDateTime_(data.returnDate, data.returnTime);
  if (!start || !end) return 'Invalid date or time.';
  if (start >= end) return 'End time must be after start time.';
  return '';
}

function hasPendingOverlap_(sheet, data) {
  const values = sheet.getDataRange().getValues();
  const rows = values.slice(1);
  const newStart = parseDateTime_(data.date, data.startTime);
  const newEnd = parseDateTime_(data.returnDate, data.returnTime);

  for (const r of rows) {
    const existingCar = r[5];
    const status = r[25] || 'Pending';
    if (existingCar !== data.car) continue;
    if (status === 'Approved' || status === 'Rejected') continue;

    const existingDate = normalizeDate_(r[6], Session.getScriptTimeZone());
    const existingReturnDate = normalizeDate_(r[31], Session.getScriptTimeZone()) || existingDate;
    const existStart = parseDateTime_(existingDate, normalizeTime_(r[7]));
    const existEnd = parseDateTime_(existingReturnDate, normalizeTime_(r[32]) || normalizeTime_(r[8]));
    if (!existStart || !existEnd) continue;

    const overlap = !(newEnd <= existStart || newStart >= existEnd);
    if (overlap) return true;
  }
  return false;
}

function hasCalendarOverlap_(data) {
  if (data.car === 'อื่นๆ ให้ระบุ') return false;
  const calMap = getVehicleMap_();
  const calId = calMap[data.car];
  if (!calId) return false;
  const calendar = CalendarApp.getCalendarById(calId);
  if (!calendar) return false;

  const newStart = parseDateTime_(data.date, data.startTime);
  const newEnd = parseDateTime_(data.returnDate, data.returnTime);
  if (!newStart || !newEnd) return false;

  const events = calendar.getEvents(
    new Date(newStart.getTime() - 12 * 60 * 60 * 1000),
    new Date(newEnd.getTime() + 12 * 60 * 60 * 1000)
  );

  for (const ev of events) {
    const eStart = ev.getStartTime();
    const eEnd = ev.getEndTime();
    if (!(newEnd <= eStart || newStart >= eEnd)) return true;
  }
  return false;
}

function parseDateTime_(dateVal, timeVal) {
  if (!dateVal || !timeVal) return null;
  const tz = Session.getScriptTimeZone();
  const dateStr = dateVal instanceof Date ? Utilities.formatDate(dateVal, tz, 'yyyy-MM-dd') : String(dateVal).trim();
  const timeStr = timeVal instanceof Date ? Utilities.formatDate(timeVal, tz, 'HH:mm') : String(timeVal).trim();
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr) || !/^\d{1,2}:\d{2}$/.test(timeStr)) return null;
  const iso = `${dateStr}T${timeStr.padStart(5, '0')}:00`;
  const d = new Date(iso);
  return isNaN(d.getTime()) ? null : d;
}

function normalizeDate_(val, tz) {
  if (!val) return '';
  try {
    const d = new Date(val);
    if (isNaN(d.getTime())) return String(val);
    return Utilities.formatDate(d, tz, 'yyyy-MM-dd');
  } catch (e) { return String(val); }
}

function normalizeTime_(val) {
  if (!val) return '';
  const str = String(val).trim();
  if (/^\d{1,2}:\d{2}/.test(str)) {
    const [h, m] = str.split(':');
    return `${String(h).padStart(2, '0')}:${m.substring(0, 2)}`;
  }
  try {
    const d = new Date(val);
    if (isNaN(d.getTime())) return str;
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
  } catch (e) { return str; }
}

function buildEventDescription_(b){
  return [
    `Requester: ${b.name}`,
    `Department: ${b.department}`,
    `WorkTypes: ${b.workTypes}`,
    `VehicleTypes: ${b.vehicleTypes}`,
    `Origin: ${b.originPlace} | ${b.originAddress} | ${b.originContact}`,
    `Origin Reason: ${b.originReason}`,
    `Dest1: ${b.dest1Place} | ${b.dest1Address} | ${b.dest1Contact}`,
    `Dest1 Reason: ${b.dest1Reason}`,
    b.dest2Place ? `Dest2: ${b.dest2Place} | ${b.dest2Address} | ${b.dest2Contact}` : '',
    b.dest2Reason ? `Dest2 Reason: ${b.dest2Reason}` : '',
    `Extra: ${b.extraDetails}`,
    `Date: ${b.date} ${b.startTime} → ${b.returnDate || b.date} ${b.returnTime || b.endTime}`,
    `Driving: ${b.driveOption} ${b.driverName ? '('+b.driverName+')' : ''}`
  ].filter(Boolean).join('\n');
}

function buildQrImages_(d){
  const make = (url) => {
    if (!url) return '';
    try {
      const blob = Charts.newQrCode().setText(url).setSize(220).build().getBlob();
      const b64 = Utilities.base64Encode(blob.getBytes());
      return `data:image/png;base64,${b64}`;
    } catch(err) {
      try {
        const resp = UrlFetchApp.fetch(`https://chart.googleapis.com/chart?chs=220x220&cht=qr&chl=${encodeURIComponent(url)}`);
        const b64 = Utilities.base64Encode(resp.getContent());
        return `data:image/png;base64,${b64}`;
      } catch(e){ return ''; }
    }
  };
  return { origin: make(d.originMap), dest1: make(d.dest1Map), dest2: make(d.dest2Map) };
}

function buildPdfHtml_(d, qr) {
  const qrBlock = (label, img, url) => {
    if (!img && !url) return '';
    const imgTag = img ? `<img src="${img}" style="width:70px;height:70px;border:1px solid #ddd;padding:2px;display:block;margin-top:2px;">` : '';
    const urlText = url ? `<div style="font-size:9px;color:#555;word-break:break-all;margin-top:2px;line-height:1.1;">${escape_(url)}</div>` : '';
    return `<td style="vertical-align:top;width:33%;padding:2px;"><strong>${escape_(label)}</strong><br>${imgTag}${urlText}</td>`;
  };

  const statusColor = d.status === 'Approved' ? '#198754' : (d.status === 'Rejected' ? '#dc3545' : '#ffc107');

  return `
  <html><head><style>
    @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;700&display=swap');
    body { font-family:'Kanit', sans-serif; padding: 25px; color: #333; font-size: 12px; line-height: 1.3; }
    .header { border-bottom: 2px solid #4e73df; padding-bottom: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { margin: 0; color: #4e73df; font-size: 20px; }
    .meta { font-size: 11px; color: #666; text-align: right; }
    .box { border: 1px solid #ccc; border-radius: 4px; padding: 10px; margin-bottom: 10px; background-color: #fff; }
    .box-title { font-weight: 700; color: #4e73df; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 4px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 2px 6px; }
    .label { font-weight: bold; color: #555; width: 130px; }
    .value { color: #000; }
    .status-stamp {
      border: 2px solid ${statusColor};
      color: ${statusColor};
      font-weight: bold;
      font-size: 16px;
      padding: 4px 12px;
      border-radius: 6px;
      text-transform: uppercase;
      display: inline-block;
    }
    .qr-table td { border: none; text-align: center; }
  </style></head><body>
    <div class="header">
      <div>
        <h1>ใบขออนุญาตใช้รถยนต์</h1>
        <div style="font-size:14px;color:#555;">บริษัท คิวทีซี เอนเนอร์ยี่ จำกัด (มหาชน)</div>
      </div>
      <div class="meta">
        <div>เลขที่รายการ: #${d.timestamp ? new Date(d.timestamp).getTime().toString().substr(-6) : '-'}</div>
        <div class="status-stamp">${escape_(d.status)}</div>
      </div>
    </div>

    <div class="box">
      <div class="box-title">ข้อมูลผู้ขอใช้รถ</div>
      <table>
        <tr>
          <td class="label">ชื่อผู้จอง:</td><td class="value">${escape_(d.name)}</td>
          <td class="label">แผนก:</td><td class="value">${escape_(d.department)}</td>
        </tr>
        <tr>
          <td class="label">อีเมล:</td><td class="value">${escape_(d.requesterEmail)}</td>
          <td class="label">วันที่ทำรายการ:</td><td class="value">${escape_(d.timestamp)}</td>
        </tr>
      </table>
    </div>

    <div class="box">
      <div class="box-title">รายละเอียดการเดินทาง</div>
      <table>
        <tr>
          <td class="label">ประเภทงาน:</td><td class="value">${escape_(d.workTypes)}</td>
        </tr>
        <tr>
          <td class="label">การขับขี่:</td><td class="value">${d.driveOption === 'SELF' ? 'ขับเอง' : 'ต้องการคนขับ'} ${d.driverName ? '(คนขับ: '+escape_(d.driverName)+')' : ''}</td>
        </tr>
        <tr>
          <td class="label">รถที่ใช้:</td><td class="value" colspan="3">${escape_(d.car)} (${escape_(d.vehicleTypes)})</td>
        </tr>
        <tr>
          <td class="label">วันที่เริ่ม:</td><td class="value">${escape_(d.date)} เวลา ${escape_(d.startTime)}</td>
          <td class="label">วันที่คืน:</td><td class="value">${escape_(d.returnDate || d.date)} เวลา ${escape_(d.returnTime || d.endTime)}</td>
        </tr>
      </table>
    </div>

    <div class="box">
      <div class="box-title">สถานที่ (Route)</div>
      <table>
        <tr><td colspan="2" style="border-bottom:1px dashed #eee; padding-top:10px;"><strong>1. ต้นทาง (Origin)</strong></td></tr>
        <tr><td class="label">สถานที่:</td><td class="value">${escape_(d.originPlace)}</td></tr>
        <tr><td class="label">ที่อยู่:</td><td class="value">${escape_(d.originAddress)}</td></tr>
        <tr><td class="label">ผู้ติดต่อ:</td><td class="value">${escape_(d.originContact)}</td></tr>

        <tr><td colspan="2" style="border-bottom:1px dashed #eee; padding-top:10px;"><strong>2. ปลายทาง (Destination 1)</strong></td></tr>
        <tr><td class="label">สถานที่:</td><td class="value">${escape_(d.dest1Place)}</td></tr>
        <tr><td class="label">ที่อยู่:</td><td class="value">${escape_(d.dest1Address)}</td></tr>
        <tr><td class="label">ผู้ติดต่อ:</td><td class="value">${escape_(d.dest1Contact)}</td></tr>

        ${d.dest2Place ? `
        <tr><td colspan="2" style="border-bottom:1px dashed #eee; padding-top:10px;"><strong>3. ปลายทาง (Destination 2)</strong></td></tr>
        <tr><td class="label">สถานที่:</td><td class="value">${escape_(d.dest2Place)}</td></tr>
        <tr><td class="label">ที่อยู่:</td><td class="value">${escape_(d.dest2Address)}</td></tr>
        <tr><td class="label">ผู้ติดต่อ:</td><td class="value">${escape_(d.dest2Contact)}</td></tr>` : ''}
      </table>
      <div style="margin-top:15px;">
        <table class="qr-table">
          <tr>
            ${qrBlock('Map: ต้นทาง', qr.origin, d.originMap)}
            ${qrBlock('Map: ปลายทาง 1', qr.dest1, d.dest1Map)}
            ${d.dest2Place ? qrBlock('Map: ปลายทาง 2', qr.dest2, d.dest2Map) : ''}
          </tr>
        </table>
      </div>
    </div>

    <div class="box">
      <div class="box-title">การอนุมัติ</div>
      <table>
        <tr>
          <td class="label">ผู้อนุมัติ:</td><td class="value">${escape_(d.approver || '-')}</td>
          <td class="label">เวลาอนุมัติ:</td><td class="value">${escape_(d.approvedAt || '-')}</td>
        </tr>
        <tr>
          <td class="label">หมายเหตุ:</td><td class="value">${escape_(d.extraDetails || '-')}</td>
        </tr>
        ${d.status === 'Rejected' ? `<tr><td class="label" style="color:red;">เหตุผลที่ปฏิเสธ:</td><td class="value" style="color:red;">${escape_(d.rejectionReason)}</td></tr>` : ''}
      </table>
    </div>
  </body></html>`;
}

// ---------------- Email templates ----------------
function notifyAdmins_(data, now) {
  if (!data) return;
  const link = getCheckLink_();
  const subj = `มีคำขอจองรถใหม่จาก ${data.name}`;
  const btn = `<div style="margin:12px 0 4px 0;"><a href="${link}" style="padding:10px 14px;background:#1e4fd7;color:#fff;border-radius:6px;text-decoration:none;display:inline-block;">ตรวจสอบ</a></div>`;
  const html = [
    `มีคำขอใหม่จาก ${escape_(data.name)} (${escape_(data.department)})<br>`,
    `รถ: ${escape_(data.car)}<br>`,
    `วันที่เริ่ม: ${escape_(data.date)} ${escape_(data.startTime)}<br>`,
    `วันที่คืน: ${escape_(data.returnDate)} ${escape_(data.returnTime)}<br>`,
    `ปลายทาง: ${escape_(data.dest1Place)}<br>`,
    `การขับขี่: ${data.driveOption} ${data.driverName ? '('+data.driverName+')' : ''}<br>`,
    btn
  ].join('');
  MailApp.sendEmail({ to: ADMIN_EMAILS.join(','), subject: subj, htmlBody: html });
}

function sendUserResultMail_(to, booking, status, reason, pdfRes) {
  if (!booking || !to) return;
  const link = getCheckLink_();
  const btn = `<div style="margin:12px 0 4px 0;"><a href="${link}" style="padding:10px 14px;background:#1e4fd7;color:#fff;border-radius:6px;text-decoration:none;display:inline-block;">ตรวจสอบ</a></div>`;
  const subj = `ผลการจองรถ: ${status}`;
  const html = [
    `สวัสดี ${escape_(booking.name)},<br>`,
    `สถานะคำขอ: <b>${escape_(status)}</b><br>`,
    reason ? `เหตุผล: ${escape_(reason)}<br>` : '',
    `รถ: ${escape_(booking.car)}<br>`,
    `วันที่เริ่ม: ${escape_(booking.date)} เวลา ${escape_(booking.startTime)}<br>`,
    `วันที่คืน: ${escape_(booking.returnDate || booking.date)} เวลา ${escape_(booking.returnTime || booking.endTime)}<br>`,
    `ปลายทาง: ${escape_(booking.dest1Place)}<br>`,
    btn
  ].join('');
  const opts = { htmlBody: html };
  if (pdfRes && pdfRes.success) {
    opts.attachments = [Utilities.newBlob(Utilities.base64Decode(pdfRes.data), 'application/pdf', pdfRes.filename)];
  }
  MailApp.sendEmail(to, subj, '', opts);
}

function escape_(s){return String(s||'').replace(/[&<>"']/g,c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));}