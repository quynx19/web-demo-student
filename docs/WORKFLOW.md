# Workflow chức năng

Tài liệu này mô tả luồng xử lý chính của các chức năng: đăng nhập, xem/thêm sinh viên và đổi mật khẩu.

## 1. Đăng nhập

File chính:

- `public/login.php`: hiển thị form và nhận POST đăng nhập.
- `app/auth.php`: kiểm tra tài khoản, tạo session và phân quyền.
- `app/user.php`: đọc thông tin tài khoản từ database.
- `app/logger.php`: ghi log đăng nhập.

```mermaid
flowchart TD
    A["Người dùng mở login.php"] --> B{"Đã đăng nhập?"}
    B -- "Có" --> C["Chuyển đến landing_page()"]
    B -- "Không" --> D["Hiển thị form đăng nhập kèm CSRF token"]
    D --> E["Người dùng nhập username/password và submit POST"]
    E --> F{"CSRF hợp lệ?"}
    F -- "Không" --> G["Hiển thị lỗi phiên làm việc"]
    F -- "Có" --> H["login_user() kiểm tra users trong database"]
    H --> I{"Tài khoản hợp lệ và active?"}
    I -- "Không" --> J["Hiển thị lỗi sai tài khoản hoặc mật khẩu"]
    I -- "Có" --> K["Lưu user_id, username, role vào session"]
    K --> L["Ghi log LOGIN_SUCCESS"]
    L --> M{"Vai trò"}
    M -- "admin" --> N["Chuyển đến index.php"]
    M -- "user" --> O["Chuyển đến profile.php"]
```

Ghi chú:

- Nếu tài khoản đã đăng nhập mà mở lại `login.php`, hệ thống tự chuyển đến trang phù hợp theo quyền.
- Admin vào `index.php`; sinh viên vào `profile.php`.
- Mật khẩu được kiểm tra bằng `password_verify()`, không so sánh mật khẩu dạng plain text.

## 2. Xem sinh viên

File chính:

- `public/students.php`: danh sách, tìm kiếm, lọc và xóa sinh viên.
- `public/student_detail.php`: xem chi tiết sinh viên và điểm 3 môn.
- `app/student.php`: truy vấn, lọc, thống kê và CRUD sinh viên.
- `app/grade.php`: lấy và cập nhật điểm sinh viên.
- `app/auth.php`: kiểm tra quyền admin.

```mermaid
flowchart TD
    A["Admin mở students.php"] --> B["require_role('admin')"]
    B --> C{"Có quyền admin?"}
    C -- "Không" --> D["Chuyển đến access_denied.php"]
    C -- "Có" --> E["Đọc filter từ GET: q, major, year, page"]
    E --> F["normalize_student_filters() và validate_student_filters()"]
    F --> G["count_students() tính tổng bản ghi"]
    G --> H["list_students() lấy 20 sinh viên mỗi trang"]
    H --> I["Render bảng sinh viên"]
    I --> J{"Người dùng chọn thao tác"}
    J -- "Chi tiết" --> K["Mở student_detail.php?id=..."]
    J -- "Sửa" --> L["Mở student_form.php?id=..."]
    J -- "Xóa" --> M["POST delete kèm CSRF token"]
    M --> N{"CSRF hợp lệ?"}
    N -- "Không" --> O["Báo lỗi phiên làm việc"]
    N -- "Có" --> P["delete_student()"]
    P --> Q["Ghi log STUDENT_DELETED"]
    Q --> R["Redirect về students.php"]
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
- Mỗi lần xem danh sách sinh viên có ghi log `STUDENT_LIST_VIEW`.
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
