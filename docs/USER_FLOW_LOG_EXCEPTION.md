# Kiểm tra log và exception theo luồng người dùng

Thời điểm kiểm tra: 2026-06-05.

Mục tiêu: giả lập người dùng truy cập, đăng nhập và dùng một số chức năng chính để xem hệ thống sinh log nào và có exception nào không.

## Kịch bản đã chạy

### 1. Admin đăng nhập và dùng chức năng quản trị

Tài khoản:

- Username: `admin`
- Password: `Admin@123`

Luồng kiểm tra:

```text
POST /public/login.php
GET  /public/index.php
GET  /public/students.php?q=SV001
GET  /public/student_detail.php?id=1
GET  /public/users.php
GET  /public/logs.php
GET  /public/student_detail.php?id=999999
```

Kết quả HTTP:

- Đăng nhập admin: `302 -> index.php`
- Vào dashboard: `200`
- Tìm sinh viên `SV001`: `200`
- Xem chi tiết sinh viên `id=1`: `200`
- Xem quản lý tài khoản: `200`
- Xem nhật ký ứng dụng: `200`
- Xem sinh viên không tồn tại `id=999999`: `302 -> students.php`

### 2. Sinh viên đăng nhập và dùng chức năng cá nhân

Tài khoản:

- Username: `user01`
- Password: `User@123`

Luồng kiểm tra:

```text
POST /public/login.php
GET  /public/profile.php
GET  /public/students.php
```

Kết quả HTTP:

- Đăng nhập sinh viên: `302 -> profile.php`
- Xem hồ sơ cá nhân: `200`
- Truy cập trang admin `students.php`: `302 -> access_denied.php`

## Log phát sinh trong `logs/app.log`

## Log nằm ở đâu?

Hệ thống hiện có 3 nơi cần biết khi kiểm tra log:

- Log ứng dụng:
  `logs/app.log`
  Dùng để xem các thao tác do code gọi `write_log()`, ví dụ dashboard, xem chi tiết sinh viên, users, logs.

- Trang xem log trong web:
  `public/logs.php`
  Admin mở trên trình duyệt để xem 20 dòng log ứng dụng mới nhất.

- Log lỗi Apache/PHP:
  `C:\xampp\apache\logs\error.log`
  Dùng để xem exception, fatal error, HTTP `500`, lỗi PHP hoặc lỗi file không tồn tại.

Khi chạy web local, nếu muốn xem thao tác người dùng thì mở:

```text
logs/app.log
```

Hoặc vào trình duyệt bằng tài khoản admin:

```text
http://localhost/web-demo-student/public/logs.php
```

Nếu trang bị trắng hoặc HTTP `500`, kiểm tra file:

```text
C:\xampp\apache\logs\error.log
```

Sau khi chạy kịch bản trên, hệ thống ghi thêm 4 dòng log:

```text
[2026-06-05 09:57:38] INFO DASHBOARD_VIEW username=admin role=admin ip=::1 method=GET uri=/web-demo-student/public/index.php message="Dashboard viewed" context=[]
[2026-06-05 09:57:38] INFO STUDENT_VIEW username=admin role=admin ip=::1 method=GET uri=/web-demo-student/public/student_detail.php?id=1 message="Student detail viewed" context={"student_id":1}
[2026-06-05 09:57:38] INFO USER_LIST_VIEW username=admin role=admin ip=::1 method=GET uri=/web-demo-student/public/users.php message="User list viewed" context=[]
[2026-06-05 09:57:38] INFO LOG_VIEW username=admin role=admin ip=::1 method=GET uri=/web-demo-student/public/logs.php message="Application log viewed" context={"keyword":""}
```

Các chức năng hiện có ghi log:

- Dashboard admin: `DASHBOARD_VIEW`
- Xem chi tiết sinh viên: `STUDENT_VIEW`
- Cập nhật điểm sinh viên: `STUDENT_GRADES_UPDATED`
- Thêm/sửa sinh viên: `STUDENT_CREATED`, `STUDENT_UPDATED`
- Thêm/sửa/xóa/khóa tài khoản: `USER_CREATED`, `USER_UPDATED`, `USER_DELETED`, `USER_STATUS_UPDATED`
- Xem danh sách tài khoản: `USER_LIST_VIEW`
- Đổi mật khẩu: `PASSWORD_CHANGED`
- Xem nhật ký ứng dụng: `LOG_VIEW`

Các chức năng hiện không ghi log do đã rút gọn cho web local:

- Đăng nhập: chỉ tạo session và cookie `last_username`
- Đăng xuất: chỉ hủy session
- Xem danh sách sinh viên: chỉ render danh sách/tìm kiếm nhanh
- Xem/cập nhật hồ sơ cá nhân: chỉ cập nhật dữ liệu và cookie `theme`
- Truy cập sai quyền: redirect sang `access_denied.php`, không ghi log

## Exception kiểm tra được

### Exception cũ đã phát hiện và sửa

Trước khi sửa, khi admin tìm sinh viên bằng:

```text
GET /public/students.php?q=SV001
```

Apache/PHP từng ghi lỗi:

```text
PDOException: SQLSTATE[HY093]: Invalid parameter number
app/student.php:92
```

Nguyên nhân:

```sql
student_code LIKE :q OR full_name LIKE :q OR email LIKE :q
```

PDO native prepare không ổn định khi dùng lại cùng một named placeholder nhiều lần. Đã sửa thành 3 placeholder riêng:

```sql
student_code LIKE :student_code
OR full_name LIKE :full_name
OR email LIKE :email
```

Sau khi sửa, kiểm tra lại:

```text
GET /public/students.php?q=SV001 -> 200
```

### Exception mới sau kịch bản hiện tại

Không phát sinh HTTP `500` mới trong kịch bản đã chạy.

Apache error log không có lỗi mới sau thời điểm kiểm tra. Các lỗi còn thấy trong `C:\xampp\apache\logs\error.log` là lỗi cũ trước khi sửa, gồm:

- Lỗi logout cũ do gọi `setcookie()` sai tham số.
- Lỗi truy cập file cũ không còn tồn tại như `student_add.php`, `change_password.php`, `api_students.php`.
- Lỗi tìm sinh viên cũ `SQLSTATE[HY093]`, đã sửa.

## Kết luận

Luồng người dùng chính hiện chạy được:

- Admin đăng nhập, xem dashboard, tìm sinh viên, xem chi tiết, xem users/logs.
- Sinh viên đăng nhập, xem hồ sơ cá nhân.
- Sinh viên bị chặn khi truy cập chức năng admin.
- Không còn exception mới trong các luồng đã mô phỏng.

Nếu muốn log đầy đủ hơn, có thể thêm lại log cho đăng nhập, đăng xuất, xem danh sách sinh viên, cập nhật hồ sơ và truy cập sai quyền.
