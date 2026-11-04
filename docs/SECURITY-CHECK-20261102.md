# Kiểm tra quyền, phiên, đầu vào và CV — 02/11/2026

Phương pháp: đọc handler/hook WordPress và chạy yêu cầu HTTP với tài khoản thử tự tạo. Không dùng tài khoản ứng viên thật; dữ liệu HTTP thử được dọn trong finally.

## Kết quả

- Ứng viên truy cập trang Admin: 403.
- Ứng viên gửi action save_settings với nonce hợp lệ của chính phiên đó: 403. Nonce không thay thế phân quyền.
- Nonce sai cho cập nhật hồ sơ: 403.
- Truy cập hồ sơ ứng tuyển/CV người khác hoặc CV không tồn tại: 403, không trả tệp.
- Chủ sở hữu tải CV: nhận dữ liệu PDF; đường dẫn public uploads không trả CV riêng tư.
- Email/điện thoại không hợp lệ: bị từ chối; email lưu trong database không thay đổi.
- Khóa tài khoản và destroy_all phiên thử: cookie cũ không mở được trang bảo vệ; mật khẩu đúng vẫn không đăng nhập được.
- Mở khóa: mật khẩu sai bị từ chối, mật khẩu đúng đăng nhập được.
- Kiểm tra tích hợp: ứng viên không tạo được việc làm, không dùng CV người khác, không dùng phiên AI người khác; khách không gọi AI được.

## Lỗi tái hiện và sửa

Tải CV mới ngay khi ứng tuyển thất bại dù CV hợp lệ. tj_upload_cv ghi tệp, sau đó ghi audit làm thay đổi wpdb::insert_id, rồi trả nhầm ID audit. Quyền sở hữu CV ngăn tạo hồ sơ với ID sai. Đã giữ file_id trước khi ghi audit và trả đúng ID đó. Kiểm thử HTTP mới thất bại trước bản sửa và đạt sau bản sửa, đồng thời đối chiếu applications.file_id với bản ghi files mới.

## Xác minh

- Portable: 12 policy + 40 integration + 29 HTTP đạt (81 tổng).
- Docker: 12 policy + 40 integration đạt (52 tổng); HTTP mở rộng chạy trên portable, không khẳng định đã chạy trên Docker.
- PHP lint hai tệp thay đổi và git diff --check đạt.

## Giới hạn

Đây là kiểm tra có mục tiêu, không phải xác nhận không còn lỗ hổng. Chưa thử toàn bộ payload sai kiểu/đầu vào cực lớn, session hết hạn theo thời gian, HTTPS/cookie Secure trên hosting, malware bên trong tài liệu, tải đồng thời hay scanner độc lập. Môi trường HTTP localhost không đại diện cho cấu hình HTTPS triển khai thật.
