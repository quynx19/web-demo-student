# Hồ sơ sinh viên

Ứng dụng PHP thuần demo hệ thống quản lý hồ sơ sinh viên chạy trên XAMPP. Project dùng để thực hành cấu trúc ứng dụng web, đăng nhập bằng session, phân quyền, cookie, API JSON, ghi nhật ký ứng dụng và quy trình nâng cấp mã nguồn.

## Công nghệ sử dụng

- PHP thuần, không dùng framework, Composer hoặc npm.
- HTML, CSS và JavaScript cơ bản, không dùng CDN.
- Apache và MySQL/MariaDB trong XAMPP.
- PDO và prepared statements cho truy vấn SQL.
- `password_hash()` và `password_verify()` cho mật khẩu.
- PHP session cho đăng nhập.
- Cookie cho tùy chọn giao diện.
- Application log tại `logs/app.log`.

## Tài khoản demo

Tài khoản demo chỉ ghi trong README, không hiển thị trên trang đăng nhập.

| Tên đăng nhập | Mật khẩu | Quyền |
| --- | --- | --- |
| `admin` | `Admin@123` | Quản trị viên |
| `user01` | `User@123` | Người dùng thường |

## Chạy ứng dụng

Đặt project tại:

```text
C:\xampp\htdocs\web-demo-student
```

Start Apache và MySQL trong XAMPP, sau đó import database:

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < sql\init.sql"
```

Mở trình duyệt:

```text
http://localhost/web-demo-student/public/login.php
```

## Kiểm tra API

Endpoint session công khai:

```text
http://localhost/web-demo-student/public/api/session
```

Sau khi đăng nhập, có thể kiểm tra danh sách sinh viên:

```text
http://localhost/web-demo-student/public/api/students?q=an&year=3
```

Danh sách endpoint và ví dụ gọi API: [`docs/API.md`](docs/API.md).

## Phân quyền

| Chức năng | Quản trị viên | Người dùng |
| --- | --- | --- |
| Xem Tổng quan | Có | Có |
| Xem danh sách sinh viên | Có | Có |
| Tìm kiếm, lọc sinh viên | Có | Có |
| Xem chi tiết sinh viên | Có | Có |
| Thêm, sửa, xóa sinh viên | Có | Không |
| Quản lý tài khoản | Có | Không |
| Xem nhật ký ứng dụng | Có | Không |
| Cập nhật hồ sơ cá nhân | Có | Có |
| Đổi mật khẩu | Có | Có |
| Gọi API sinh viên | Có | Có |

Nếu người dùng thường truy cập trực tiếp trang quản trị như `student_add.php`, hệ thống chuyển đến `access_denied.php` và ghi log `ACCESS_DENIED`.

## Test logout

1. Đăng nhập bằng `admin`.
2. Bấm `Đăng xuất`.
3. Kiểm tra trình duyệt quay về `login.php`.
4. Truy cập lại `students.php`.
5. Kết quả mong đợi: hệ thống chuyển về `login.php`.
6. Mở `logs/app.log` và kiểm tra event `LOGOUT`.

## Demo phân quyền

1. Đăng nhập `admin / Admin@123`.
2. Kiểm tra admin thấy menu: Tổng quan, Sinh viên, Thêm sinh viên, Quản lý tài khoản, Nhật ký ứng dụng, Hồ sơ cá nhân, Đổi mật khẩu, Đăng xuất.
3. Thêm, sửa, xóa sinh viên.
4. Xem nhật ký ứng dụng.
5. Đăng xuất.
6. Đăng nhập `user01 / User@123`.
7. Kiểm tra user không thấy menu quản trị.
8. Kiểm tra user chỉ thấy nút `Chi tiết` trong danh sách sinh viên.
9. Truy cập trực tiếp `student_add.php` bằng user01 để sinh log `ACCESS_DENIED`.

## Xem log

- Application log: `C:\xampp\htdocs\web-demo-student\logs\app.log`
- Apache access log: `C:\xampp\apache\logs\access.log`
- Apache error log: `C:\xampp\apache\logs\error.log`

Các event thường dùng khi demo:

- `LOGIN_SUCCESS`
- `LOGIN_FAILED`
- `LOGOUT`
- `ACCESS_DENIED`
- `API_DASHBOARD_VIEW`
- `API_STUDENTS_LIST`
- `API_STUDENT_CREATED`
- `API_STUDENT_UPDATED`
- `API_STUDENT_DELETED`
- `API_USER_CREATED`
- `API_USER_UPDATED`
- `API_PASSWORD_CHANGED`
- `EXCEPTION`

## Lệnh Git sau nâng cấp

```powershell
git status
git add .
git commit -m "Convert web app to API-first REST architecture"
git push
```

## REST API

Giao diện web hoạt động theo kiến trúc API-first: các trang PHP chỉ render khung
HTML, còn dữ liệu và thao tác nghiệp vụ được thực hiện qua REST API bằng
`public/assets/app.js`.

Tài liệu endpoint cho session, dashboard, sinh viên, tài khoản, hồ sơ cá nhân,
đổi mật khẩu và log: [`docs/API.md`](docs/API.md).
