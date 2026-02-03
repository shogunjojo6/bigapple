function doGet(e) {
  setupDatabase();
  return HtmlService.createTemplateFromFile('index')
      .evaluate()
      .addMetaTag('viewport', 'width=device-width, initial-scale=1')
      .setTitle('Library Management System');
}

function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename)
      .getContent();
}

function setupDatabase() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheets = [
    { name: 'ADMIN', headers: ['admin_id', 'username', 'password'] },
    { name: 'MEMBER', headers: ['member_id', 'name', 'address', 'phone', 'email', 'password', 'registration_date', 'member_type'] },
    { name: 'BOOK', headers: ['book_id', 'title', 'author', 'publisher', 'publication_year', 'isbn', 'quantity'] },
    { name: 'BORROW', headers: ['borrow_id', 'member_id', 'book_id', 'borrow_date', 'due_date', 'return_date'] }
  ];

  sheets.forEach(sheetInfo => {
    let sheet = ss.getSheetByName(sheetInfo.name);
    if (!sheet) {
      sheet = ss.insertSheet(sheetInfo.name);
      sheet.appendRow(sheetInfo.headers);
    }
  });
}

function readData(sheetName) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  if (!sheet) return [];
  const data = sheet.getDataRange().getDisplayValues();
  const headers = data.shift();
  return data.map(row => {
    let obj = {};
    headers.forEach((header, index) => {
      obj[header] = row[index];
    });
    return obj;
  });
}

function createData(sheetName, data) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
  const row = headers.map(header => data[header] || '');
  sheet.appendRow(row);
  return 'Success';
}

function updateData(sheetName, idColumn, idValue, data) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  const values = sheet.getDataRange().getValues();
  const headers = values[0];
  const colIndex = headers.indexOf(idColumn);

  if (colIndex === -1) return 'ID Column not found';

  for (let i = 1; i < values.length; i++) {
    if (values[i][colIndex] == idValue) {
      const row = headers.map(header => data[header] !== undefined ? data[header] : values[i][headers.indexOf(header)]);
      sheet.getRange(i + 1, 1, 1, row.length).setValues([row]);
      return 'Success';
    }
  }
  return 'Not Found';
}

function deleteData(sheetName, idColumn, idValue) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  const values = sheet.getDataRange().getValues();
  const headers = values[0];
  const colIndex = headers.indexOf(idColumn);

  if (colIndex === -1) return 'ID Column not found';

  for (let i = 1; i < values.length; i++) {
    if (values[i][colIndex] == idValue) {
      sheet.deleteRow(i + 1);
      return 'Success';
    }
  }
  return 'Not Found';
}

function searchData(sheetName, query) {
  const data = readData(sheetName);
  if (!query) return data;
  query = query.toString().toLowerCase();
  return data.filter(item => {
    return Object.values(item).some(val =>
      String(val).toLowerCase().includes(query)
    );
  });
}

function borrowBook(memberId, bookId) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const bookSheet = ss.getSheetByName('BOOK');
  const bookData = bookSheet.getDataRange().getValues();
  const bookHeaders = bookData[0];
  const idIndex = bookHeaders.indexOf('book_id');
  const qtyIndex = bookHeaders.indexOf('quantity');

  let bookRowIndex = -1;
  let currentQty = 0;

  for (let i = 1; i < bookData.length; i++) {
    if (bookData[i][idIndex] == bookId) {
      bookRowIndex = i + 1;
      currentQty = parseInt(bookData[i][qtyIndex]);
      break;
    }
  }

  if (bookRowIndex === -1) return 'Book not found';
  if (currentQty <= 0) return 'Book not available';

  bookSheet.getRange(bookRowIndex, qtyIndex + 1).setValue(currentQty - 1);

  const borrowSheet = ss.getSheetByName('BORROW');
  const borrowId = 'BR' + new Date().getTime();
  const borrowDate = new Date();
  const dueDate = new Date();
  dueDate.setDate(borrowDate.getDate() + 7);

  borrowSheet.appendRow([
    borrowId,
    memberId,
    bookId,
    borrowDate.toISOString().split('T')[0],
    dueDate.toISOString().split('T')[0],
    ''
  ]);

  return 'Success';
}

function returnBook(borrowId) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const borrowSheet = ss.getSheetByName('BORROW');
  const borrowData = borrowSheet.getDataRange().getValues();
  const borrowHeaders = borrowData[0];
  const bIdIndex = borrowHeaders.indexOf('borrow_id');
  const bookIdIndex = borrowHeaders.indexOf('book_id');
  const returnDateIndex = borrowHeaders.indexOf('return_date');

  let borrowRowIndex = -1;
  let bookId = '';

  for (let i = 1; i < borrowData.length; i++) {
    if (borrowData[i][bIdIndex] == borrowId) {
      if (borrowData[i][returnDateIndex] !== '') return 'Already returned';
      borrowRowIndex = i + 1;
      bookId = borrowData[i][bookIdIndex];
      break;
    }
  }

  if (borrowRowIndex === -1) return 'Borrow record not found';

  borrowSheet.getRange(borrowRowIndex, returnDateIndex + 1).setValue(new Date().toISOString().split('T')[0]);

  const bookSheet = ss.getSheetByName('BOOK');
  const bookData = bookSheet.getDataRange().getValues();
  const bookHeaders = bookData[0];
  const bkIdIndex = bookHeaders.indexOf('book_id');
  const qtyIndex = bookHeaders.indexOf('quantity');

  for (let i = 1; i < bookData.length; i++) {
    if (bookData[i][bkIdIndex] == bookId) {
      const currentQty = parseInt(bookData[i][qtyIndex]);
      bookSheet.getRange(i + 1, qtyIndex + 1).setValue(currentQty + 1);
      break;
    }
  }

  return 'Success';
}

function getStats() {
    const ss = SpreadsheetApp.getActiveSpreadsheet();
    const books = ss.getSheetByName('BOOK') ? ss.getSheetByName('BOOK').getLastRow() - 1 : 0;
    const members = ss.getSheetByName('MEMBER') ? ss.getSheetByName('MEMBER').getLastRow() - 1 : 0;
    const borrows = ss.getSheetByName('BORROW') ? ss.getSheetByName('BORROW').getLastRow() - 1 : 0;
    return {
        books: Math.max(0, books),
        members: Math.max(0, members),
        borrows: Math.max(0, borrows)
    };
}
