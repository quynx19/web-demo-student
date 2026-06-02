# REST API

The web interface uses these REST endpoints through `public/assets/app.js`.
Authentication uses the existing PHP session cookie. Mutating requests also
require the `X-CSRF-Token` header.

## Endpoints

| Method | URL | Permission | Purpose |
| --- | --- | --- | --- |
| `GET` | `/api/session` | Public | Current session and CSRF token |
| `POST` | `/api/session` | Public | Log in |
| `DELETE` | `/api/session` | Logged in | Log out |
| `GET` | `/api/dashboard` | Logged in | Dashboard statistics |
| `GET` | `/api/students` | Logged in | Paginated student list |
| `GET` | `/api/students/{id}` | Logged in | Student detail |
| `POST` | `/api/students` | Admin | Create student |
| `PUT`, `PATCH` | `/api/students/{id}` | Admin | Update student |
| `DELETE` | `/api/students/{id}` | Admin | Delete student |
| `GET` | `/api/users` | Admin | User list |
| `GET` | `/api/users/{id}` | Admin | User detail |
| `POST` | `/api/users` | Admin | Create user |
| `PUT`, `PATCH` | `/api/users/{id}` | Admin | Update user or lock status |
| `DELETE` | `/api/users/{id}` | Admin | Delete user |
| `GET` | `/api/profile` | Logged in | Current profile |
| `PATCH` | `/api/profile` | Logged in | Update profile and theme |
| `PUT` | `/api/profile/password` | Logged in | Change password |
| `GET` | `/api/logs` | Admin | Paginated application log |

All URLs are relative to:

```text
http://localhost/web-demo-student/public
```

Apache rewrite rules are defined in `public/api/.htaccess`. Direct `.php`
fallback URLs remain available, for example:

```text
/api/students.php?id=1
/api/users.php?id=1
```

The legacy read endpoint `/api_students.php` is kept for compatibility.

## Student Filters

`GET /api/students` supports:

| Parameter | Description |
| --- | --- |
| `q` | Search student code, name, or email |
| `major` | Filter by exact major |
| `year` | Filter by year from `1` to `6` |
| `page` | Page number, default `1` |
| `per_page` | Results per page, default `20`, maximum `100` |

## Status Codes

| Status | Meaning |
| --- | --- |
| `200` | Successful read or update |
| `201` | Resource created |
| `204` | Successful delete or logout |
| `400` | Invalid ID, JSON, or request target |
| `401` | Login required or invalid credentials |
| `403` | Permission denied or invalid CSRF token |
| `404` | Resource not found |
| `409` | Unique field conflict |
| `415` | JSON content type required |
| `422` | Validation error |

## PowerShell Example

```powershell
$session = curl.exe -s -c .api-cookie.txt http://localhost/web-demo-student/public/api/session | ConvertFrom-Json
$csrf = $session.csrf_token

$login = @{ username = 'admin'; password = 'Admin@123' } | ConvertTo-Json -Compress
$login | curl.exe -b .api-cookie.txt -c .api-cookie.txt -H "Content-Type: application/json" -H "X-CSRF-Token: $csrf" --data-binary '@-' -X POST http://localhost/web-demo-student/public/api/session

curl.exe -b .api-cookie.txt http://localhost/web-demo-student/public/api/students

$student = @{ student_code = 'SV100'; full_name = 'Nguyen Van A'; email = 'a@example.com'; year = 3 } | ConvertTo-Json -Compress
$student | curl.exe -b .api-cookie.txt -H "Content-Type: application/json" -H "X-CSRF-Token: $csrf" --data-binary '@-' -X POST http://localhost/web-demo-student/public/api/students
```
