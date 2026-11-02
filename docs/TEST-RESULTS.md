# Kết quả kiểm chứng — 02/11/2026

## Môi trường đã chạy

Windows, WordPress 7.1.4, PHP 8.2.12 từ XAMPP, MariaDB portable 11.4.13 trên `127.0.0.1:3308`, website tại `http://localhost:8080`. MariaDB tải từ archive chính thức và đã đối chiếu SHA256. Dữ liệu/runtime riêng trong dự án.

## Tự động

Lệnh: `./scripts/test.ps1 -Local`.

- PHP syntax: các file source, script và test đều qua.
- Policy: **12 checks** — hạn nộp, tin nháp, lương giao nhau/thỏa thuận, đối soát phỏng vấn, PDF giả, DOCX và ZIP giả.
- WordPress integration: **40 checks** — vai trò, redirect an toàn, quyền CV, CRUD tin, template chi tiết, consent/chống nộp trùng, snapshot hồ sơ, lịch/kết quả/hủy/đặt lại, demo đủ 5 câu, giới hạn lượt và quyền phiên.
- HTTP: **19 checks** — homepage/CSS/Anh, đăng ký/đăng nhập, lưu profile, chặn URL admin/CV/hồ sơ người khác, nonce sai, PDF giả/hợp lệ, tải CV, ứng tuyển/chống trùng và REST demo.
- `node --check` qua cho JavaScript ứng dụng.
- `docker compose config --quiet` qua. Không có kết quả container runtime do lỗi Docker Desktop.

Test tạo user/hồ sơ riêng và dọn dữ liệu của chính test; không cần mật khẩu demo cố định. Các kiểm tra HTTP áp dụng cho bản portable có runtime đã cài trên máy này; test policy/integration dùng được qua Docker.

## Trình duyệt

Đã quan sát trang chủ, đăng nhập hai vai trò, dashboard Admin, sửa/lưu tin, dashboard ứng viên, chi tiết việc làm, filter Java trả 3 tin, Việt/Anh và widget demo nhận câu trả lời rồi chuyển câu hỏi tiếp theo. Đã xem giao diện hẹp qua viewport override và desktop; không thấy tràn ngang ở trang chủ hẹp. Việc nộp form consent/upload được kiểm chứng bằng HTTP suite; công cụ trình duyệt không bật được checkbox native trong lần kiểm tra trước.

Đã bổ sung và kiểm tra bốn vùng soạn thảo WordPress cho mô tả/yêu cầu Việt–Anh; lưu tin thành công qua trình soạn thảo, không có lỗi JavaScript ở lần kiểm tra này. Ảnh bàn giao: `docs/progress/techjob-public.png` và `docs/progress/techjob-admin.png`.

Chưa coi đây là kiểm thử bao phủ mọi trang ở mọi breakpoint hoặc kiểm thử tải. Không có kết quả Gemini thật khi chưa có khóa API.

## Sao lưu/phục hồi

`./scripts/backup.ps1 -Local` tạo SQL, CV và cấu hình local trong `backups/`. `scripts/verify-backup-local.php` phục hồi dump vào database tạm riêng, kiểm tra **24 tin, 8 ứng viên, 8 tệp CV**, rồi xóa database tạm. Không ghi đè database đang chạy.

## Các lỗi đã bắt và sửa

- Redirect trống sau login làm trang POST rỗng: chọn dashboard làm URL mặc định.
- Router local không phục vụ CSS/JS qua junction: giới hạn và phục vụ assets của plugin theo MIME.
- Template chi tiết thiếu biến job: truyền đối tượng job rõ ràng.
- Ngày không tồn tại bị PHP tự chuẩn hóa: đối chiếu lại chuỗi ngày nhập.
- Khung giờ đã hủy vẫn bị unique key chặn: unique cho lịch hoạt động, giữ lịch đã hủy.
- WordPress `wp_die` đổi status từ 403 thành 500: truyền `response` rõ ràng.
- Từ chối hồ sơ người khác sau khi đã xuất HTML: kiểm tra quyền trước khi render.

## Còn cần kiểm chứng bên ngoài

1. Docker Desktop hoạt động trở lại: chạy mới Compose và thực thi bộ test trong container.
2. Gemini API key hợp lệ: kiểm tra kết nối, phiên đủ 5 câu, quota/timeout trên nhà cung cấp thật.
3. Trước khi lên hosting: runtime PHP được cập nhật, HTTPS, email nếu cần, backup thực tế và kiểm thử tải.
