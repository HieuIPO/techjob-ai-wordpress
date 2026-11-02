# Ánh xạ ATS → WordPress

## Nguồn tham chiếu

- GitHub: https://github.com/HieuIPO/ats-mini-mvc, commit `56d6080f85d2f233bca468da6d31e81c0617d7e7`.
- Giao diện: `home.css` và `admin.css` của ATS local đã chụp ở `docs/reference/screenshots/`. Giữ lại bản CSS này trong plugin, thêm lớp điều chỉnh ở `techjob.css`.
- Bản local và nhánh GitHub khác nhau; không coi ảnh local là ảnh nhánh GitHub. Không thay đổi mã/dữ liệu dự án cũ.

| ATS MVC | TechJob AI |
|---|---|
| `_Layout.cshtml`, Home/Jobs | Layout public và template jobs/job bằng PHP |
| `_AdminLayout.cshtml`, sidebar, dashboard | `/quan-tri/` với CSS admin cũ, chỉ số từ database |
| AccountController, session, role | WordPress users, cookie, nonce, role `tj_candidate`; Admin dùng `manage_options` |
| JobsController | CPT `tj_job`, bảng meta và taxonomy |
| Department/JobPosition | Taxonomy nhóm nghề/vị trí; thêm kỹ năng, địa điểm |
| Công ty hiển thị cố định | CPT `tj_company`, nội dung song ngữ và liên kết công việc |
| CandidateController | `/tai-khoan/` và các trang profile, CV, đã lưu |
| ApplicationsController | Bảng `tj_applications`, `tj_history`, tải CV có kiểm tra quyền |
| InterviewsController | Bảng `tj_interviews`, đối soát kết quả và `.ics` |
| Notifications/AuditLog | Bảng `tj_notifications` và `tj_audit` |
| Chatbot/ngôn ngữ chưa có | Gemini/demo và giao diện/nội dung mẫu Việt–Anh |

## Thay đổi nghiệp vụ có chủ đích

- Bỏ HR và luồng duyệt tin giữa HR/Admin. Admin dùng Nháp/Đang tuyển/Đã đóng.
- Bỏ tra cứu hồ sơ chỉ bằng email/điện thoại công khai; ứng viên đăng nhập để xem hồ sơ của mình.
- Bỏ số liệu dashboard giả thay thế khi database trống; hiển thị số liệu thực, kể cả 0.
- CV PDF/DOC/DOCX ≤5 MB nằm ngoài webroot, không dùng URL uploads công khai.
- Lương lưu số VND/tháng và cờ thỏa thuận để lọc chính xác.
- Trạng thái lưu mã `submitted`, `reviewing`, `interview`, `passed`, `rejected`; không dùng nhãn Việt làm khóa logic.
- Khi hủy lịch, giữ bản ghi; `active_slot=NULL` giải phóng khung giờ nhưng giữ lịch sử. Unique index chặn hai lịch hoạt động cùng hồ sơ/thời gian.
- Hai bản dịch dùng cùng ID việc làm và hồ sơ, không tạo bản ứng tuyển riêng theo ngôn ngữ.

## Giao diện kỹ thuật

Trang PHP render phía máy chủ. Các form POST tới `wp-admin/admin-post.php` với `action=tj_action`, trường nghiệp vụ `tj_action` và nonce theo từng thao tác. Thành công/lỗi dùng POST–redirect–GET. Bộ lọc nằm trong query string.

REST namespace `/wp-json/techjob/v1` hiện triển khai:

| Endpoint | Quyền | Input chính |
|---|---|---|
| `POST /ai/start` | Ứng viên/Admin | skill, level, language, mode |
| `POST /ai/answer` | Chủ phiên | id, step, answer |
| `POST /ai/test` | Admin | Không có khóa trong body |

REST xác thực bằng WordPress cookie và `X-WP-Nonce`. Đầu ra phiên gồm id, mode, message, step, done. Lỗi không chứa khóa API hoặc response thô của Gemini. Các nghiệp vụ tuyển dụng còn lại dùng form POST, chưa xuất bộ REST CRUD tổng quát.

## Dữ liệu và khả năng cài đặt

CPT/metadata dùng bảng WordPress. Các bảng `wp_tj_*` dùng InnoDB, có unique key chống nộp trùng, lưu trữ CV, lịch sử, thông báo, nhật ký, phiên AI và rate limit. Prefix lấy theo `$wpdb->prefix`. Schema version 2 tự nâng cấp khi plugin khởi tạo; không xóa dữ liệu khi tắt plugin.

Không chuyển database SQL Server hay password hash C#; mọi tài khoản demo được tạo mới, mật khẩu ngẫu nhiên. Phần runtime portable tại 8080 là bản dự phòng. Compose đã cài mới và kiểm thử thực tế tại 8081; hai bản có database/CV/tài khoản riêng.
