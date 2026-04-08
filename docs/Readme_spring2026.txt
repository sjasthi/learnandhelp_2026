learnandhelp
Learn and Help — Learn programming and help different causes.

Deployment Instructions
Prerequisites

XAMPP installed on your machine (Apache + MySQL)
PHP 8.0 or higher (included with XAMPP 8.x)
cURL extension enabled in PHP (required for LLM/Claude mode)

Steps to Deploy

Download the Repository
Clone or download this repo as a ZIP file.
Extract the Contents
Extract to: xampp/htdocs/learnandhelp_2026
Start XAMPP
Launch the XAMPP Control Panel and start Apache and MySQL.
Create the Database
Open phpMyAdmin and create a new database called learnandhelp_db.
Import the SQL File
Import the SQL file located at:
xampp/htdocs/learnandhelp_2026/sql/learnandhelp_db.sql
into the learnandhelp_db database.
Launch the App
Open your browser and go to: http://localhost/learnandhelp_2026


Accessing the App
Regular User
Create an account directly through the app's registration page (found below the password field on the Login page).
Admin Access

Create a regular user account through the app.
Open phpMyAdmin and navigate to learnandhelp_db → users table.
Find the account you created.
Change the role field from user to admin.
Log in — you now have admin access.
An Administration option will appear in the navigation bar.


Enable cURL (Required for LLM Mode)
If you plan to use the Claude AI chatbot mode, cURL must be enabled in PHP:

1. Open the XAMPP Control Panel and click Config next to Apache → select php.ini.
2. Search for ;extension=curl and remove the semicolon so it reads: extension=curl
3. Save the file and restart Apache in the XAMPP Control Panel.


Optional: Configure Email (Progress Reports)
The admin progress report feature sends emails via Gmail SMTP using PHPMailer.
To enable email sending:

1. Open: xampp/htdocs/learnandhelp_2026/sendemail.php
2. Update the following constants with a Gmail account and its App Password:
   - MAIL_USERNAME — your Gmail address (e.g. yourname@gmail.com)
   - MAIL_PASSWORD — a 16-character Gmail App Password (not your regular password)
     Generate one at: https://myaccount.google.com/apppasswords
   - MAIL_FROM — same Gmail address
   - MAIL_FROM_NAME — display name (e.g. "Learn and Help")
3. In phpMyAdmin → preferences table, set Email_Mode to:
   - DEV  — all emails go to admin only (safe for testing)
   - PROD — emails go to actual sponsors and students
Note: Email is optional. All other features work without it.


Optional: Enable LLM (Claude AI) Mode
The chatbot supports an optional AI mode powered by Anthropic's Claude. To enable it:

1. Obtain an Anthropic API key from https://console.anthropic.com (requires adding credits).
2. Open phpMyAdmin and navigate to learnandhelp_db → preferences table.
3. Find the row where Preference_Name = 'ANTHROPIC_API_KEY' and set its Value to your API key.
4. Log in as admin and go to the Chat page. An admin panel will appear at the top.
5. Click "LLM (Claude)" to switch from rule-based mode to AI mode.
Note: Without a valid API key, the chatbot runs in rule-based mode by default (no API key required).


Troubleshooting

Apache won't start — Port 80 is likely in use. Common causes: Skype, IIS, or another web server.
  Fix: In XAMPP Control Panel → Apache → Config → httpd.conf, change "Listen 80" to "Listen 8080",
  then access the app at http://localhost:8080/learnandhelp_2026

MySQL won't start — Port 3306 is in use (another MySQL instance running).
  Fix: Stop the conflicting service via Windows Task Manager → Services, or change MySQL port in my.ini.

Blank page or PHP errors — Confirm PHP version is 8.0+. In XAMPP Control Panel → Apache → Logs → check error.log.

LLM mode not working — Confirm cURL is enabled (see above) and the ANTHROPIC_API_KEY preference is set correctly in the database.

Email not sending — Confirm Gmail App Password is correct and 2-Step Verification is enabled on the Gmail account.


Recently Updated Features (FP Stabilization):

Board Members — Manage the list of board members displayed on the site. Admins can add new board members, edit existing member details (including photo, title, and bio), and delete members from the list.

Patron — Manage patrons (supporters/sponsors) of the organization. Admins can add a new patron and view a full table of all patrons at the bottom of the page, with full CRUD (Create, Read, Update, Delete) functionality.

Assets — Track physical and organizational assets required to run the site and program, such as computers, chargers, and laptops. Admins can log, view, edit, and delete asset records.

Assets Report — View a complete report table of all assets, including detailed information and the current status of each asset.

Expenses — Record and manage any expenses incurred for the site or program. Admins can add new expenses, edit existing records, and delete entries. All expense details are tracked and stored for reference.

Chatbot (FP9) — Rule-based chatbot integrated into the main site. Answers questions about schools, enrollment, classes, board members, events, and the book library using live database queries. Supports an optional LLM mode powered by Anthropic Claude (see Optional section above).

Chatbot Eval — View a detailed accuracy report on chatbot performance. Includes overall accuracy score, category breakdown, confusion matrix, rule-based vs. LLM comparison, accuracy trend chart, and model parameter investigation.

Chat Log — View a log of all chatbot interactions. Each entry records the question asked, handler used, response time, and user rating (thumbs up/down). Note: user identities are not recorded. Admins can filter by handler and export to CSV.
