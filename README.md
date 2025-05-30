# Camagru v1

A simple image gallery application built with pure PHP (no frameworks), supporting file uploads and webcam capture.

## 💡 Overview

- **Backend:** pure PHP (PDO), no third-party libraries
- **Web-server:** Nginx + PHP-FPM
- **Database:** PostgreSQL
- **Frontend:** Vanilla HTML / CSS / JS, supports file uploads and webcam photo capture (PNG with alpha channel)
- **Dockerized:** three containers — `php`, `nginx`, `db`

## 🚀 Quick Start

1. Clone the repository:
```bash
git clone git@github.com:aseptimu/camagru.git
cd camagru
```
2. Create the `.env` file from the example:
```bash
cp .env.example .env

# Set values for: DB_USER, DB_PASSWORD
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
└── README.txt
```

## ⚙️ API

- **GET /images** — retrieve a list of all images (JSON)
- **POST /images/upload** — upload a file or webcam snapshot (`multipart/form-data`, field `image`)

## 📷 Webcam Capture

1. Click “Use Webcam” to allow camera access
2. Click “Capture” to take a PNG snapshot and upload it to the server


