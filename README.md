# Camagru v2

A simple image gallery and user management application built with pure PHP (no frameworks).

## 💡 Overview

* **Backend:** pure PHP (PDO), no third-party libraries
* **Web-server:** Nginx + PHP-FPM
* **Database:** PostgreSQL
* **Frontend:** Vanilla HTML / CSS / JS, supports file uploads, webcam photo capture (PNG with alpha channel), and full user flows: registration, email confirmation, login, profile update, password recovery.
* **Dockerized:** three containers — `php`, `nginx`, `db`

## 🚀 Quick Start

1. Clone the repository:

   ```bash
   git clone git@github.com:aseptimu/camagru.git
   cd camagru
   ```
2. Create the `.env` file from the example:

   ```bash
   cp .env.example .env
   # Set values for:
   #   DB_USER, DB_PASSWORD
   #   EMAIL_FROM, EMAIL_FROM_NAME, EMAIL_REPLY_TO
   #   SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS
   ```
3. Start the containers:

   ```bash
   docker-compose up --build -d
   ```
4. Open in your browser:

   ```
   http://localhost/
   ```

## 📁 Project Structure

```
camagru/
├── backend/
│   ├── public/           # index.php, uploads/
│   └── src/              # controllers, services, repositories
├── frontend/             # index.html, css/, js/
├── nginx/                # default.conf for Nginx
├── migrations/           # SQL scripts for DB initialization
├── .env.example
├── docker-compose.yml
└── README.md               # this file
```

## ⚙️ API

### Image Endpoints

* **GET /api/images**

    * Retrieve a list of all images (JSON array of `{ id, filename, original_name, created_at }`)
* **POST /api/images/upload**

    * Upload a file or webcam snapshot (`multipart/form-data`, field `image`)

### User & Auth Endpoints

* **POST /api/register**

    * Register a new user (`username`, `email`, `password`)
* **GET /api/confirm?token=...**

    * Confirm email using token from registration
* **POST /api/login**

    * Authenticate a user (`username`, `password`)
* **POST /api/logout**

    * Invalidate the current session
* **GET /api/status**

    * Check auth status and retrieve current user info
* **POST /api/recover**

    * Send password recovery link to email (`email`)
* **POST /api/reset**

    * Reset password using token (`token`, `password`, `confirmPassword`)
* **GET /api/profile**

    * Fetch logged-in user profile (`id`, `username`, `email`)
* **POST /api/updateProfile**

    * Update user fields (`username?`, `email?`, `password?`, `confirmPassword?`)

## 📷 Webcam Capture

1. Click **Use Webcam** to allow camera access
2. Click **Capture** to take a PNG snapshot and upload it to the server

## 🔒 Security & User Flows

* Passwords are hashed before storage (bcrypt)
* Email confirmation is required before login
* CSRF protection via same-origin credentials on fetch
* Session-based authentication stored in secure, HTTP-only cookies

---

*Camagru v2 — now with full user management flows.*
