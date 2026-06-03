# Hồ sơ sinh viên

Ứng dụng PHP thuần quản lý hồ sơ sinh viên chạy trên XAMPP. Hệ thống dùng PHP
session, phân quyền, PDO prepared statements, CSRF token, cookie giao diện và
application log.

## Công nghệ

- PHP thuần, không dùng framework, Composer hoặc npm.
- HTML, CSS và JavaScript nhỏ gọn cho thanh cuộn log.
- Apache và MySQL/MariaDB trong XAMPP.
- PDO và prepared statements.
- `password_hash()` và `password_verify()`.

## Chạy ứng dụng

Import database:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root < sql\init.sql"
```

Mở trình duyệt:

```text
http://localhost/web-demo-student/public/login.php
```

## Tài khoản demo

| Tên đăng nhập | Mật khẩu | Quyền |
| --- | --- | --- |
| `admin` | `Admin@123` | Quản trị viên |
| `user01` | `User@123` | Sinh viên `SV001` |

## Phân quyền

| Chức năng | Quản trị viên | Sinh viên |
| --- | --- | --- |
| Tổng quan hệ thống | Có | Không |
| Quản lý sinh viên | Có | Không |
| Cập nhật điểm 3 môn | Có | Không |
| Quản lý tài khoản | Có | Không |
| Xem nhật ký ứng dụng | Có | Không |
| Xem và cập nhật hồ sơ cá nhân | Có | Có |
| Xem điểm cá nhân | Không áp dụng | Có |
| Đổi mật khẩu | Có | Có |

## Cấu trúc chính

```text
app/                 Model, xác thực, helper và logger
config/database.php  Kết nối PDO
public/              Các trang PHP xử lý form và render HTML
public/assets/        CSS giao diện
sql/init.sql         Toàn bộ schema và dữ liệu mẫu
logs/app.log         Nhật ký ứng dụng
```

Các form submit trực tiếp bằng `POST` tới trang PHP. Sau khi xử lý thành công,
hệ thống redirect về trang danh sách hoặc hồ sơ tương ứng.

## Tài liệu workflow

- `docs/WORKFLOW.md`: workflow đăng nhập, xem sinh viên và đổi mật khẩu.
