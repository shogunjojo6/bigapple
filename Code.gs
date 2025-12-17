// The holy grail of Google Apps Script Back-end.
const SHEET_ID = "YOUR_GOOGLE_SHEET_ID"; // Replace with your Google Sheet ID

const sheet = SpreadsheetApp.openById(SHEET_ID);
const roomsSheet = sheet.getSheetByName("Rooms");
const bookingsSheet = sheet.getSheetByName("Bookings");

/**
 * Serves the HTML of the web app.
 */
function doGet() {
  return HtmlService.createTemplateFromFile('index').evaluate();
}

/**
 * Includes the content of another file in the HTML template.
 * This is a common pattern in GAS web apps to separate HTML, CSS, and JS.
 */
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

/**
 * @description Books a meeting room.
 * @param {object} bookingDetails - The details of the booking.
 * @returns {object} A confirmation or error message.
 */
function bookRoom(bookingDetails) {
  try {
    const {
      roomName,
      title,
      name,
      email,
      start,
      end
    } = bookingDetails;
    const room = getRoomByName(roomName);

    if (!room) {
      throw new Error("Room not found.");
    }

    // Check for availability in Google Calendar
    const calendar = CalendarApp.getCalendarById(room.calendarId);
    const events = calendar.getEvents(new Date(start), new Date(end));

    if (events.length > 0) {
      return {
        success: false,
        message: "Room is already booked for the selected time."
      };
    }

    // Create event in Google Calendar
    const event = calendar.createEvent(title, new Date(start), new Date(end), {
      description: `Booked by: ${name} (${email})`,
      guests: email,
      sendInvites: true
    });

    // Log booking in Google Sheet
    bookingsSheet.appendRow([
      new Date(),
      roomName,
      title,
      name,
      email,
      new Date(start),
      new Date(end),
      event.getId()
    ]);

    return {
      success: true,
      message: "Booking successful! An invitation has been sent to your email."
    };
  } catch (error) {
    return {
      success: false,
      message: error.message
    };
  }
}

/**
 * @description Gets the list of rooms from the Google Sheet.
 * @returns {Array} An array of room objects.
 */
function getRooms() {
  const rooms = roomsSheet.getDataRange().getValues();
  // Remove header row
  rooms.shift();
  return rooms.map(row => ({
    name: row[0],
    capacity: row[1],
    facilities: row[2],
    photoUrl: row[3],
    calendarId: row[4]
  }));
}

/**
 * @description Gets a room by its name.
 * @param {string} roomName - The name of the room.
 * @returns {object} The room object or null if not found.
 */
function getRoomByName(roomName) {
  const rooms = getRooms();
  return rooms.find(room => room.name === roomName);
}

/**
 * @description Gets the bookings for a user by email.
 * @param {string} email - The email of the user.
 * @returns {Array} An array of booking objects.
 */
function getMyBookings(email) {
  const bookings = bookingsSheet.getDataRange().getValues();
  bookings.shift(); // Remove header row
  const userBookings = bookings.filter(row => row[4] === email);

  return userBookings.map(row => ({
    timestamp: row[0],
    roomName: row[1],
    title: row[2],
    name: row[3],
    email: row[4],
    start: row[5],
    end: row[6],
    eventId: row[7]
  }));
}
