# Local Development — PHP Built-in Server

This project includes simple PHP endpoints under `api/auth` for register, login, logout, and status.

Quick start (Windows / PowerShell):

1. Start the built-in PHP server in the project root:

```powershell
php -S 127.0.0.1:8000 -t .
```

2. Register a user (PowerShell example):

```powershell
curl -X POST "http://127.0.0.1:8000/api/auth/register.php" `
  -H "Content-Type: application/json" `
  -d '{"username":"testuser","email":"test@example.com","password":"testpass","first_name":"Test","last_name":"User"}'
```

3. Login (PowerShell example):

```powershell
curl -X POST "http://127.0.0.1:8000/api/auth/login.php" `
  -H "Content-Type: application/json" `
  -d '{"email":"test@example.com","password":"testpass"}'
```

Notes:
- The endpoints expect JSON bodies. `register.php` passes the parsed JSON to `Auth::register($data)`. `login.php` expects `{ "email": ..., "password": ... }`.
- On successful registration the service will create a session cookie (`session_token`) and return `{"success":true, "user_id": <id>}`.
- If you prefer Bash (Linux/macOS or Git Bash on Windows), use single-quoted JSON and the same `curl` commands.

Simple test script is provided at `scripts/test_endpoints.sh`.
