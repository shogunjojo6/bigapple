# Meeting Room Booking System Setup Guide

Follow these steps to set up your Meeting Room Booking System.

## 1. Google Sheet Setup

1.  **Create a new Google Sheet.**
2.  **Rename the first sheet to `Rooms` and the second sheet to `Bookings`.**
3.  **Set up the `Rooms` sheet with the following columns:**
    *   `Name`
    *   `Capacity`
    *   `Facilities`
    *   `Photo URL` (use placeholder URLs if you don't have images)
    *   `Calendar ID`
4.  **Set up the `Bookings` sheet with the following columns:**
    *   `Timestamp`
    *   `Room Name`
    *   `Meeting Title`
    *   `Booker Name`
    *   `Booker Email`
    *   `Start Time`
    *   `End Time`
    *   `Event ID`

## 2. Google Calendar Setup

1.  **Create a separate Google Calendar for each meeting room.**
2.  **For each calendar, go to `Settings and sharing` and find the `Calendar ID` under the `Integrate calendar` section.**
3.  **Make sure the calendars are publicly accessible or shared with the appropriate users.**

## 3. Google Apps Script Configuration

1.  **Open the `Code.gs` file in the Google Apps Script editor.**
2.  **Replace the placeholder value for `SHEET_ID` with your actual Google Sheet ID.**
3.  **Save the changes.**
4.  **Make sure to add the Calendar ID for each room in the `Rooms` sheet, as the script will read the Calendar ID from there.**

## 4. Deployment

1.  **In the Google Apps Script editor, click on `Deploy` > `New deployment`.**
2.  **Select `Web app` as the deployment type.**
3.  **In the `Who has access` dropdown, select `Anyone` or `Anyone within your organization`.**
4.  **Click `Deploy`.**
5.  **Copy the web app URL and open it in your browser.**

Your Meeting Room Booking System is now ready to use!
