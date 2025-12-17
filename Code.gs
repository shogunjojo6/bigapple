// ---------------- Configuration ----------------
const ADMIN_EMAILS = [
  '67319010023@tatc.ac.th', // สำหรับรับอีเมลแจ้งเตือน
];
// Web App URL for admin notification link (Leave empty to auto-detect current URL)
const ADMIN_NOTIFY_LINK = 'https://script.google.com/macros/s/AKfycbzGwSyu1CmexJYIlu0TK-HJ9Rg7YwdHG9XvchbwV4vhD3M90FkFhHGACaAFPPLrdy8c/exec';
const ADMIN_CHECK_LINK_OVERRIDE = '';
const ADMIN_PASS = 'L@sasa4321';

const SHEET_NAME = 'Bookings';
const SPREADSHEET_ID = '1SYK7LTcyiZpP4udPdTeqGWeHCy5ze4b6KamEJDa9G-E';

const CALENDAR_BY_CAR = {
  '[รถ 6 ล้อ][ISUZU][83-0506 ระยอง]': '0bd83515078480892cb8fed5941057c025a6399e7f8409f60a5698a6f9ccfac8@group.calendar.google.com',
  '[รถกระบะ][ISUZU][3ฒษ2096 กทม.]': 'a977fbd1f3f410b34bd0b0b6ab7eba8ef4982127fd0699e4edf7c9599926fb4a@group.calendar.google.com',
  '[รถกระบะ][TOYOTA Hilux Champ][4ฒก9851 กทม.]': 'cd589b9d1ab0be2099149f4fe5944ac419b6af13e46b0db149651397441bb52f@group.calendar.google.com',
  '[รถยนต์][TOYOTA YARISS][3ขข8315 กทม.]': 'ef0622aa35981268e8d3ba4ec6afaddf0e90900a3a76f9d456d2f869bc057f41@group.calendar.google.com',
};

// ---------------- Web App Serving ----------------
function doGet() {
  const template = HtmlService.createTemplateFromFile('index');
  return template.evaluate()
      .setTitle('Vehicle Booking System')
      .setSandboxMode(HtmlService.SandboxMode.IFRAME)
      .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

function getCheckLink_() {
  const autoUrl = ScriptApp.getService().getUrl();
  return ADMIN_CHECK_LINK_OVERRIDE || autoUrl || ADMIN_NOTIFY_LINK || 'https://example.com';
}

function preservePhone_(v){ const s=String(v||'').trim(); return s && /^\d+$/.test(s) ? "'" + s : s; }
function adminLogin(pass){ return { success: pass === ADMIN_PASS }; }

// ---------------- Submit ----------------
function submitBooking(formData) {
  try {
    const sheet = getSheet_();
    const data = sanitizeForm_(formData);
    const validationError = validateForm_(data);
    if (validationError) return { success: false, message: validationError };

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
      data.extraDetails, 'Pending', '', '', data.email, '', '', data.returnDate, data.returnTime
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
        returnDate: normalizeDate_(r[31], tz), returnTime: normalizeTime_(r[32])
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
      // แยกประเภทงาน ถ้ามีหลายอย่าง (เผื่ออนาคต) แต่นี่นับเป็น string เดียว
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

// HTML สำหรับ Summary PDF (ฟอนต์ Kanit)
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

// วาดโดนัทแบบ wedge เดียวต่อหมวด (ปิดวงสนิท) สำหรับ PDF
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
      angle = 2*Math.PI - (current + Math.PI/2); // ชิ้นสุดท้ายปิดวงตรงเป๊ะ
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
function updateBookingStatus(rowNumber, newStatus, reason, adminPass) {
  try {
    const email = (Session.getActiveUser() && Session.getActiveUser().getEmail()) || '';
    const isAdmin = (adminPass === ADMIN_PASS) || ADMIN_EMAILS.includes(email);
    if (!isAdmin) return { success: false, message: 'Not authorized.' };
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

        const calId = CALENDAR_BY_CAR[booking.car];
        if (!calId) return { success: false, message: `ยังไม่ได้ตั้งค่า Calendar ID สำหรับรถ ${booking.car}` };
        const calendar = CalendarApp.getCalendarById(calId);
        if (!calendar) return { success: false, message: `ไม่พบปฏิทินของรถ ${booking.car}` };

        const title = `${booking.car} | ${booking.name}`;
        const description = buildEventDescription_(booking);
        const ev = calendar.createEvent(title, start, end, { description, location: booking.dest1Place || booking.originPlace || '' });
        const eventId = ev.getId();
        const approvedAt = new Date();
        sheet.getRange(rowNumber, 26, 1, 6).setValues([['Approved', email, approvedAt, row[28], '', eventId]]);
      } else {
        const approvedAt = new Date();
        sheet.getRange(rowNumber, 26, 1, 6).setValues([['Approved', email, approvedAt, row[28], '', '']]);
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
    sheet.getRange(rowNumber, 26, 1, 6).setValues([[newStatus, email, approvedAt, row[28], reason || '', '']]);
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

// ---------------- Update Details ----------------
function updateBookingDetails(rowNumber, formData, adminPass) {
  try {
    const email = (Session.getActiveUser() && Session.getActiveUser().getEmail()) || '';
    const isAdmin = (adminPass === ADMIN_PASS) || ADMIN_EMAILS.includes(email);
    if (!isAdmin) return { success: false, message: 'Not authorized.' };

    const sheet = getSheet_();
    const lastRow = sheet.getLastRow();
    if (!rowNumber || rowNumber < 2 || rowNumber > lastRow) return { success: false, message: 'Row out of range.' };

    const range = sheet.getRange(rowNumber, 1, 1, 33);
    const row = range.getValues()[0];
    const currentStatus = row[25] || 'Pending';
    const oldBooking = rowToBooking_(row);

    // Remove old event if it exists (so we can recreate it with new details if approved)
    if (currentStatus === 'Approved') {
      removeEventForBooking_(oldBooking);
    }

    // Sanitize & Prepare new data
    const data = sanitizeForm_(formData);
    const endTimeToStore = data.returnTime || data.endTime || data.startTime;
    const oContact = preservePhone_(data.originContact);
    const d1Contact = preservePhone_(data.dest1Contact);
    const d2Contact = preservePhone_(data.dest2Contact);

    // Update Sheet (Columns 2-25 and 32-33). Column 1 (Timestamp) and 26-31 (Status stuff) are kept/handled separately
    // Mapping matches submitBooking logic:
    // Col 2: Name, 3: Dept, 4: WorkTypes, 5: VehicleTypes, 6: Car
    // Col 7: Date, 8: StartTime, 9: EndTime
    // Col 10-14: Origin ...
    // Col 15-19: Dest1 ...
    // Col 20-24: Dest2 ...
    // Col 25: Extra
    // Col 29: Email (Index 28)
    // Col 32: ReturnDate (Index 31)
    // Col 33: ReturnTime (Index 32)

    // We update in chunks or one by one. Chunks is better.
    // Range 1: Col 2-6 (Indexes 1-5)
    sheet.getRange(rowNumber, 2, 1, 5).setValues([[data.name, data.department, data.workTypes, data.vehicleTypes, data.car]]);

    // Range 2: Col 7-9 (Indexes 6-8)
    sheet.getRange(rowNumber, 7, 1, 3).setValues([[data.date, data.startTime, endTimeToStore]]);

    // Range 3: Col 10-25 (Indexes 9-24)
    sheet.getRange(rowNumber, 10, 1, 16).setValues([[
      data.originPlace, data.originAddress, oContact, data.originReason, data.originMap,
      data.dest1Place, data.dest1Address, d1Contact, data.dest1Reason, data.dest1Map,
      data.dest2Place, data.dest2Address, d2Contact, data.dest2Reason, data.dest2Map,
      data.extraDetails
    ]]);

    // Range 4: Col 29 (Index 28) - Email
    sheet.getRange(rowNumber, 29, 1, 1).setValue(data.email);

    // Range 5: Col 32-33 (Indexes 31-32) - Return Date/Time
    sheet.getRange(rowNumber, 32, 1, 2).setValues([[data.returnDate, data.returnTime]]);

    // If status was Approved, we need to Re-Approve to create the event and PDF
    // But updateBookingStatus sends email. We might not want to spam.
    // However, data changed, so maybe we SHOULD update the event.
    // Let's call updateBookingStatus only to recreate event.
    // To prevent re-sending 'Approved' email, we might need a flag, but for simplicity/robustness:
    // If we just leave it, the Event is gone.
    // So we MUST recreate the event.

    if (currentStatus === 'Approved') {
       // Call internal logic to recreate event without full status flow or just call updateBookingStatus
       // Calling updateBookingStatus will update 'ApprovedAt' and send email. This is acceptable for a "Change" notification.
       return updateBookingStatus(rowNumber, 'Approved', 'Updated booking details', adminPass);
    }

    // If Pending or Rejected, just return success
    return { success: true, message: 'Booking details updated.' };

  } catch(err) {
    console.error(err);
    return { success: false, message: 'Error editing booking: ' + err.message };
  }
}

// ---------------- Calendar fetch/remove ----------------
function getCalendarBookings(car, startDateStr, endDateStr) {
  try {
    const calId = CALENDAR_BY_CAR[car];
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
  const calId = CALENDAR_BY_CAR[booking.car];
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
    'ReturnDate','ReturnTime'
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
  const calId = CALENDAR_BY_CAR[data.car];
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
    `Date: ${b.date} ${b.startTime} → ${b.returnDate || b.date} ${b.returnTime || b.endTime}`
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
    const imgTag = img ? `<img src="${img}" style="width:140px;height:140px;border:1px solid #e2e8f0;border-radius:8px;display:block;margin-bottom:6px;">` : '';
    const urlText = url ? `<div style="font-size:12px;color:#334155;word-break:break-all;">${escape_(url)}</div>` : '';
    return `<div style="margin-top:6px;">${escape_(label)}<br>${imgTag}${urlText}</div>`;
  };
  const reasonBlock = d.status === 'Rejected' ? `<div><b>เหตุผลการ Reject:</b> ${escape_(d.rejectionReason || '')}</div>` : '';
  return `
  <html><head><style>
    @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap');
    body { font-family:'Kanit',sans-serif; padding:20px; color:#0f172a; }
    h1 { margin:0 0 12px; }
    .section { border:1px solid #e2e8f0; border-radius:10px; padding:12px; margin-bottom:12px; }
    .row { display:flex; gap:8px; margin-bottom:6px; }
    .col { flex:1; }
    .label { font-weight:700; color:#475569; }
    .badge { display:inline-block; padding:6px 10px; border-radius:20px; background:#ecfdf3; color:#166534; font-weight:700; }
  </style></head><body>
    <h1>ใบแจ้งขอใช้รถ</h1>
    <div class="section">
      <div class="row"><div class="col"><span class="label">วันที่ส่งคำขอ:</span> ${escape_(d.timestamp||'')}</div><div class="col"><span class="label">สถานะ:</span> <span class="badge">${escape_(d.status)}</span></div></div>
      <div class="row"><div class="col"><span class="label">ผู้ขอ:</span> ${escape_(d.name)}</div><div class="col"><span class="label">อีเมล:</span> ${escape_(d.requesterEmail)}</div><div class="col"><span class="label">แผนก:</span> ${escape_(d.department)}</div></div>
      <div class="row"><div class="col"><span class="label">ประเภทงาน:</span> ${escape_(d.workTypes)}</div></div>
      <div class="row"><div class="col"><span class="label">ประเภทรถ:</span> ${escape_(d.vehicleTypes)}</div><div class="col"><span class="label">รถ/คัน:</span> ${escape_(d.car)}</div></div>
      <div class="row"><div class="col"><span class="label">วันที่ใช้:</span> ${escape_(d.date)} เวลา ${escape_(d.startTime)}</div><div class="col"><span class="label">วันที่คืน:</span> ${escape_(d.returnDate || d.date)} เวลา ${escape_(d.returnTime || d.endTime)}</div></div>
    </div>
    <div class="section">
      <div class="label">สถานที่รับ (ต้นทาง)</div>
      <div class="row"><div class="col"><span class="label">สถานที่:</span> ${escape_(d.originPlace)}</div><div class="col"><span class="label">เหตุผล:</span> ${escape_(d.originReason)}</div></div>
      <div class="row"><div class="col"><span class="label">ที่อยู่:</span> ${escape_(d.originAddress)}</div><div class="col"><span class="label">ผู้ติดต่อ/เบอร์:</span> ${escape_(d.originContact)}</div></div>
      ${qrBlock('แผนที่ต้นทาง', qr.origin, d.originMap)}
    </div>
    <div class="section">
      <div class="label">สถานที่ส่ง (ปลายทาง 1)</div>
      <div class="row"><div class="col"><span class="label">สถานที่:</span> ${escape_(d.dest1Place)}</div><div class="col"><span class="label">เหตุผล:</span> ${escape_(d.dest1Reason)}</div></div>
      <div class="row"><div class="col"><span class="label">ที่อยู่:</span> ${escape_(d.dest1Address)}</div><div class="col"><span class="label">ผู้ติดต่อ/เบอร์:</span> ${escape_(d.dest1Contact)}</div></div>
      ${qrBlock('แผนที่ปลายทาง 1', qr.dest1, d.dest1Map)}
    </div>
    ${d.dest2Place ? `
    <div class="section">
      <div class="label">สถานที่ส่ง (ปลายทาง 2)</div>
      <div class="row"><div class="col"><span class="label">สถานที่:</span> ${escape_(d.dest2Place)}</div><div class="col"><span class="label">เหตุผล:</span> ${escape_(d.dest2Reason)}</div></div>
      <div class="row"><div class="col"><span class="label">ที่อยู่:</span> ${escape_(d.dest2Address)}</div><div class="col"><span class="label">ผู้ติดต่อ/เบอร์:</span> ${escape_(d.dest2Contact)}</div></div>
      ${qrBlock('แผนที่ปลายทาง 2', qr.dest2, d.dest2Map)}
    </div>` : ''}
    <div class="section"><div class="label">รายละเอียดเพิ่มเติม</div><div>${escape_(d.extraDetails || '-')}</div></div>
    <div class="section">
      <div class="row"><div class="col"><span class="label">ผู้อนุมัติ:</span> ${escape_(d.approver)}</div><div class="col"><span class="label">เวลาอนุมัติ:</span> ${escape_(d.approvedAt||'-')}</div></div>
      ${reasonBlock}
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
