# Áp dụng Stitch cho giao diện ứng viên — 02/11/2026

Nguồn: `stitch_techjob_ai_wordpress_platform/`, skill ui-ux-pro-max. Áp dụng trực tiếp theo yêu cầu chọn mẫu Stitch.

- Thêm `assets/stitch-public.css`, chỉ tải ở giao diện public/ứng viên; giữ CSS cũ và thay đổi chưa commit hiện có.
- Trang chủ/tìm việc: hero hai dòng, nền tím nhạt, tìm kiếm xanh lá, từ khóa nhanh có liên kết thật, bộ lọc trái, thẻ việc làm rộng, trạng thái rỗng và CTA luyện tập.
- Tài khoản: thẻ thông tin đầu trang, avatar chữ cái lấy từ tên thật, nút cập nhật CV/luyện tập, tab và thống kê theo dữ liệu hiện tại.
- Chi tiết việc làm: nhóm thông tin trên nền nhạt, nội dung trái và form ứng tuyển phải; responsive về một cột.
- Khung AI rộng hơn, vẫn dùng widget, REST API và nhãn demo hiện tại. Không thêm video/voice/matching giả.
- Đồng bộ công ty, đăng nhập/đăng ký và footer bằng hệ thống màu/chữ mới.
- Không thay route, tên trường, nonce, nội dung đồng ý, xử lý upload hay quyền truy cập. Không seed/khôi phục dữ liệu, không sửa giao diện Admin.

## Kiểm tra đã thực hiện
- PHP lint: includes/view.php và templates/jobs.php, job.php, candidate.php, companies.php đạt.
- `tests/policy.php`: PASS 10 policy checks.
- `node --check assets/techjob.js`: đạt.
- `git diff --check`: đạt (có thông báo chuẩn hóa CRLF/LF).
- Browser Docker 8081: xem trang chủ desktop 1440px; 375px không tràn ngang, menu mở/đóng, widget mở/đóng, form đăng nhập; 768px trang chủ tiếng Anh không tràn ngang.
- Ảnh: `docs/progress/stitch-public-desktop.png`.

## Giới hạn kiểm chứng
Database hiện không có việc làm/công ty/ứng viên. Chưa kiểm thử trực quan màn chi tiết việc làm có dữ liệu, tài khoản ứng viên đã đăng nhập hoặc phiên Gemini. Không chạy bộ integration/HTTP phụ thuộc seed. Chưa kiểm tra mọi kích thước, zoom lớn hay mọi thao tác bàn phím. Font Google có fallback hệ thống khi offline.
