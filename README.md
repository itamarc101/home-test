# home-test

## Project Additions & Features

This project has been extended with several modern features for authentication, messaging, and file handling. Below is a summary of the main additions and how they work.

---

### 1. **Login & OTP Authentication (React Frontend)**
- The login flow is built with React.
- Users enter their username and receive a One-Time Password (OTP) via email.
- OTPs are sent using the Brevo (Sendinblue) API (not SMTP).
- OTPs are valid for 10 minutes, with rate limiting (max 4/hour, 10/day).
- After successful OTP verification, the username and token are stored in localStorage.

---

### 2. **API & Backend**
- All authentication and chat operations are handled via a PHP API (`api.php`).
- The API supports:
  - OTP generation and validation
  - Chat and message retrieval
  - Sending text, image, and PDF messages
  - Deleting messages for both sides
- The API uses PDO for secure database access.

---

### 3. **Brevo Email Integration**
- OTP emails are sent using the official Brevo API via PHP cURL.
- The Brevo API key and sender address are stored in the `config` table for security.
- No SMTP is used; all email delivery is via the Brevo REST API.

---

### 4. **Sending Images & PDFs**
- Users can send images and PDF files in chat.
- **Desktop:** Supports file selection and drag & drop.
- **Mobile:** Supports camera upload for images.
- Uploaded files are stored in the `uploads/` directory and referenced in messages.
- PDF messages are displayed as clickable links with an icon.

---

### 5. **Deleting Messages**
- Users can delete their own messages for both sides.
- Click on your own message to open a popup with a "Delete for everyone" option.
- Deleted messages are marked as "revoked" and display "הודעה זו נמחקה" ("This message was deleted").

---

### 6. **Logout**
- Users can log out via a button in the sidebar.
- Logging out clears localStorage and redirects to the login page.

---

### 7. **Database Setup & Fixes**
- The `contacts` table is structured to ensure every user/contact pair exists, so chat lists always show the correct name and avatar.
- SQL scripts and helper queries are provided to auto-populate missing contacts.
- The `config` table stores all environment and system settings (API keys, email, page sizes, etc.).

---

### 8. **Profile Picture Handling**
- Each user/contact can have a profile picture (`./profile_pics/{username}.jpg`).
- If a profile picture is missing, a default avatar is shown.
- The chat list and chat window always display the correct contact name and avatar, provided the `contacts` table is populated correctly.

---

## **Setup Instructions**

1. The folder path should be `C:\xampp\htdocs\home-test`
2. Run `xampp-control.exe`
3. Activate **Apache** and **MySQL**
4. Import the provided `mysql.sql`
5. Open terminal, navigate to `C:\xampp\htdocs\home-test\login`, and run 
```
npm install
npm start 
```
6. In your browser, go to http://localhost:3000/ to access the login page.
9. After logging in, you will be redirected to the main chat application at http://localhost/home-test/index.php