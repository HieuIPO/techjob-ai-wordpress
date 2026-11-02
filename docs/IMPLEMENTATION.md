# TechJob AI — triển khai

## Quyết định
Giữ giao diện local đã chụp (xanh lá phía ứng viên, dashboard xanh dương), dùng luồng GitHub ATS commit `56d6080f85d2f233bca468da6d31e81c0617d7e7` làm tham chiếu. Chỉ có ứng viên và Admin; không có HR. Dùng dữ liệu mẫu mới. Không sửa repo ATS cũ.

Theme `techjob-ai` giữ giao diện; plugin `techjob-ai-core` chứa dữ liệu và nghiệp vụ. WordPress users/cookie/capabilities cho đăng nhập. Việc làm/công ty dùng CPT; ứng tuyển, CV, lịch, thông báo và nhật ký dùng bảng riêng. CV ngoài webroot. Tất cả thời gian DB UTC, hiển thị Asia/Ho_Chi_Minh.

## Kiểm chứng bảo mật
Rà soát quyền chủ sở hữu, nonce/CSRF, SQL có tham số, escape HTML, upload theo nội dung, hạn mức AI, khóa không ra trình duyệt; test tích hợp và test phân quyền. Chạy SecureCoder nếu dịch vụ có sẵn, ghi rõ giới hạn nếu không; audit trong `docs/walkthrough.md`.

## Các đợt
- [x] WordPress portable, dữ liệu và tài khoản mẫu; Compose đã tạo, Docker runtime còn bị chặn bởi engine trên máy.
- [x] Giao diện tuyển dụng, ứng viên, CV, lưu việc và ứng tuyển.
- [x] Dashboard, CRUD tin/công ty/danh mục, hồ sơ, lịch, thông báo và tài khoản.
- [x] Việt/Anh và phỏng vấn demo; adapter Gemini đã viết, chưa có khóa để kiểm chứng thật.
- [x] Kiểm thử portable, đóng gói, backup/restore; cần kiểm chứng Docker khi engine hoạt động.

## Môi trường thực tế
Docker Desktop lỗi socket dockerInference. WordPress 7.1.4 đang chạy bằng PHP 8.2.12 và MariaDB portable 11.4.13 trong runtime. Cấu hình Compose đã qua kiểm tra cú pháp; container chưa chạy được. Xem README và docs/TEST-RESULTS.md.
