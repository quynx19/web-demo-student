# Workflow chức năng

Tài liệu này mô tả luồng xử lý chính của các chức năng: đăng nhập, xem/thêm sinh viên và đổi mật khẩu.

## 1. Đăng nhập

File chính:

- `public/login.php`: hiển thị form và nhận POST đăng nhập.
- `app/auth.php`: kiểm tra tài khoản, tạo session và phân quyền.
- `app/user.php`: đọc thông tin tài khoản từ database.

```mermaid
flowchart TD
    A["Người dùng mở login.php"] --> B{"Đã đăng nhập?"}
    B -- "Có" --> C["Chuyển đến landing_page()"]
    B -- "Không" --> D["Hiển thị form đăng nhập"]
    D --> E["Người dùng nhập username/password và submit POST"]
    E --> F["login_user() kiểm tra users trong database"]
    F --> G{"Tài khoản hợp lệ và active?"}
    G -- "Không" --> H["Hiển thị lỗi sai tài khoản hoặc mật khẩu"]
    G -- "Có" --> I["Lưu user_id, username, role vào session"]
    I --> J["Lưu cookie last_username"]
    J --> K{"Vai trò"}
    K -- "admin" --> L["Chuyển đến index.php"]
    K -- "user" --> M["Chuyển đến profile.php"]
```

Ghi chú:

- Nếu tài khoản đã đăng nhập mà mở lại `login.php`, hệ thống tự chuyển đến trang phù hợp theo quyền.
- Admin vào `index.php`; sinh viên vào `profile.php`.
- Mật khẩu được kiểm tra bằng `password_verify()`, không so sánh mật khẩu dạng plain text.

## 2. Xem sinh viên

File chính:

- `public/students.php`: danh sách, tìm kiếm nhanh và xóa sinh viên.
- `public/student_detail.php`: xem chi tiết sinh viên và điểm 3 môn.
- `app/student.php`: truy vấn, tìm kiếm, thống kê và CRUD sinh viên.
- `app/grade.php`: lấy và cập nhật điểm sinh viên.
- `app/auth.php`: kiểm tra quyền admin.

```mermaid
flowchart TD
    A["Admin mở students.php"] --> B["require_role('admin')"]
    B --> C{"Có quyền admin?"}
    C -- "Không" --> D["Chuyển đến access_denied.php"]
    C -- "Có" --> E["Đọc từ khóa q từ GET"]
    E --> F["list_students(q) tìm theo mã, họ tên hoặc email"]
    F --> G["Render bảng sinh viên"]
    G --> H{"Người dùng chọn thao tác"}
    H -- "Chi tiết" --> I["Mở student_detail.php?id=..."]
    H -- "Sửa" --> J["Mở student_form.php?id=..."]
    H -- "Xóa" --> K["POST delete"]
    K --> L["delete_student()"]
    L --> M["Redirect về students.php"]
```

Luồng xem chi tiết:

```mermaid
flowchart TD
    A["Admin bấm Chi tiết"] --> B["student_detail.php nhận id sinh viên"]
    B --> C["get_student(id)"]
    C --> D{"Tìm thấy sinh viên?"}
    D -- "Không" --> E["Flash lỗi và quay về students.php"]
    D -- "Có" --> F["list_student_grades(id)"]
    F --> G["Render hồ sơ sinh viên và điểm 3 môn"]
```

Ghi chú:

- Chức năng xem danh sách sinh viên chỉ dành cho admin.
- Sinh viên thường không vào được `students.php`; nếu truy cập trực tiếp sẽ bị chuyển sang `access_denied.php`.
- Danh sách sinh viên đã bỏ lọc ngành/năm và phân trang; chỉ giữ ô tìm kiếm nhanh.
- Khi admin thêm sinh viên mới ở `student_form.php`, hệ thống tạo luôn tài khoản `user` liên kết với sinh viên đó.
- Nếu xóa sinh viên, tài khoản sinh viên liên kết cũng bị xóa để không còn tài khoản không gắn với hồ sơ sinh viên.
- Trang nhật ký ứng dụng chỉ hiển thị 20 dòng log mới nhất sau khi lọc.

Luồng thêm sinh viên kèm tài khoản:

```mermaid
flowchart TD
    A["Admin mở student_form.php"] --> B["Nhập thông tin sinh viên"]
    B --> C["Nhập tài khoản đăng nhập: username, mật khẩu ban đầu, trạng thái"]
    C --> D["Submit POST kèm CSRF token"]
    D --> E{"CSRF và dữ liệu hợp lệ?"}
    E -- "Không" --> F["Hiển thị lỗi trên form"]
    E -- "Có" --> G["Bắt đầu transaction database"]
    G --> H["create_student() tạo hồ sơ sinh viên"]
    H --> I["initialize_student_grades() tạo điểm 3 môn"]
    I --> J["create_user() tạo tài khoản role user gắn student_id"]
    J --> K["Commit transaction"]
    K --> L["Ghi log STUDENT_CREATED và USER_CREATED"]
    L --> M["Redirect về students.php"]
```

## 3. Đổi mật khẩu

File chính:

- `public/password.php`: hiển thị form và xử lý đổi mật khẩu.
- `app/auth.php`: yêu cầu người dùng phải đăng nhập.
- `app/user.php`: lấy tài khoản và cập nhật password hash.
- `app/logger.php`: ghi log đổi mật khẩu.

```mermaid
flowchart TD
    A["Người dùng mở password.php"] --> B["require_login()"]
    B --> C{"Đã đăng nhập?"}
    C -- "Không" --> D["Chuyển đến login.php"]
    C -- "Có" --> E["Lấy user hiện tại bằng get_user_by_id()"]
    E --> F["Hiển thị form đổi mật khẩu kèm CSRF token"]
    F --> G["Người dùng nhập mật khẩu hiện tại, mật khẩu mới, nhập lại mật khẩu mới"]
    G --> H["Submit POST password.php"]
    H --> I{"CSRF hợp lệ?"}
    I -- "Không" --> J["Hiển thị lỗi phiên làm việc"]
    I -- "Có" --> K{"Mật khẩu hiện tại đúng?"}
    K -- "Không" --> L["Hiển thị lỗi mật khẩu hiện tại không đúng"]
    K -- "Có" --> M{"Mật khẩu mới >= 6 ký tự?"}
    M -- "Không" --> N["Hiển thị lỗi độ dài mật khẩu"]
    M -- "Có" --> O{"Nhập lại mật khẩu khớp?"}
    O -- "Không" --> P["Hiển thị lỗi không khớp"]
    O -- "Có" --> Q["change_user_password() lưu password_hash mới"]
    Q --> R["Ghi log PASSWORD_CHANGED"]
    R --> S["Flash thành công"]
    S --> T["Redirect về profile.php"]
```

Ghi chú:

- Đổi mật khẩu đã được tách khỏi cập nhật hồ sơ.
- `profile.php` chỉ còn cập nhật thông tin cá nhân và xem điểm.
- `password.php` chỉ xử lý mật khẩu, giúp luồng rõ hơn và dễ kiểm tra hơn.
