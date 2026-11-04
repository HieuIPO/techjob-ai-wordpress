# TechJob AI — WordPress

Website tuyển dụng IT và luyện phỏng vấn, chuyển từ giao diện/luồng ATS Mini MVC. Hai vai trò: **Ứng viên** và **Admin**. Khách chưa đăng nhập chỉ được xem nội dung công khai.

## Mở bản đang chạy trên máy này

- Website Docker: http://localhost:8081/
- Đăng nhập Docker: http://localhost:8081/dang-nhap/
- Quản trị nghiệp vụ Docker: http://localhost:8081/quan-tri/
- Tài khoản và mật khẩu Docker: `runtime/demo-accounts.txt` (không đưa vào Git).

Docker Desktop hiện hoạt động bình thường. Bản Docker đã cài và kiểm tra trên máy: WordPress 7.1.4, PHP 8.3.36, MariaDB 11.4.13, Docker Engine 29.6.2. Cổng 8081 được chọn để giữ nguyên bản **PHP/MariaDB portable tại 8080** và dữ liệu cũ của bản này. Hai bản có database, CV và tài khoản demo riêng; không tự đồng bộ dữ liệu.

```powershell
docker compose up -d wordpress
./scripts/test.ps1
docker compose stop
```

Phương án portable dự phòng ở 8080 dùng tài khoản trong `runtime/private/demo-accounts.txt`:

```powershell
# Chạy lại sau khi tắt máy
./scripts/start-local.ps1

# Dừng sạch web và database
./scripts/stop-local.ps1

# Kiểm tra mã PHP, policy, nghiệp vụ và HTTP
./scripts/test.ps1 -Local

# Sao lưu và kiểm chứng phục hồi vào database tạm
./scripts/backup.ps1 -Local
& 'C:\xampp\php\php.exe' scripts/verify-backup-local.php
```

Nếu PHP ở nơi khác, truyền `-Php 'đường-dẫn-php.exe'` vào các script PowerShell hỗ trợ tham số này. Bản portable đã chuẩn bị trên máy này; để cài ở máy mới, dùng Docker bên dưới.

## Cài mới bằng Docker

Cần Docker Desktop với Linux engine đang hoạt động. Chạy tại thư mục dự án bằng PowerShell 7:

```powershell
./scripts/setup.ps1 -Port 8081
./scripts/test.ps1
docker compose stop
docker compose up -d wordpress
```

Setup tự tạo mật khẩu database, cài WordPress, kích hoạt theme/plugin, tạo tài khoản và dữ liệu demo. Chạy lại không tạo trùng dữ liệu. Mật khẩu tài khoản được sao chép vào `runtime/demo-accounts.txt` ở host; bản trong container nằm ngoài webroot.

Docker chỉ công khai cổng web trên `127.0.0.1`; database không xuất cổng. Không chạy bản portable và Docker cùng một cổng. `setup.ps1 -Port ...` cập nhật cổng trong `.env`; khi bỏ qua tham số, script dùng cổng đã lưu.

Đã chạy cài mới, chạy lại setup không tạo trùng dữ liệu, khởi động lại container và kiểm tra dữ liệu còn nguyên. Trong Docker đã qua 12 policy checks và 40 integration checks; các trang chính trả HTTP 200.

## Các chức năng đã chuyển

- Giao diện xanh lá phía công khai/ứng viên và xanh dương phía quản trị từ CSS ATS local.
- Tìm kiếm từ khóa/công ty; lọc kỹ năng, nhóm nghề, lương, địa điểm, loại hình; phân trang, tin nổi bật và công ty.
- Đăng ký/đăng nhập, profile, CV riêng tư, lưu việc, ứng tuyển, lịch sử trạng thái và thông báo.
- Admin: dashboard dữ liệu thực, CRUD tin/công ty/danh mục, xem hồ sơ/CV, ghi chú nội bộ, cập nhật trạng thái, khóa/mở ứng viên.
- Lịch phỏng vấn: tạo/sửa/hủy, kết quả và đối soát trạng thái, tải lịch `.ics`.
- Giao diện Việt/Anh và dữ liệu mẫu hai ngôn ngữ.
- Widget 5 câu phỏng vấn, demo ghi nhãn và tích hợp Gemini phía máy chủ.

Bộ dữ liệu mẫu tùy chọn cho môi trường cài mới: 6 công ty hư cấu, 24 việc làm IT, 8 ứng viên cùng CV giả lập, 16 hồ sơ và lịch mẫu. Không nhập tài khoản/CV cá nhân từ ATS cũ.

**Trạng thái hiện tại (04/11/2026):** dữ liệu mẫu và tài khoản ứng viên đã được dọn ở cả Docker lẫn portable theo yêu cầu của chủ dự án. Không chạy seed hoặc khôi phục backup để làm dữ liệu xuất hiện lại. Xem [gói bàn giao](docs/HANDOFF-20261104.md) để biết chức năng, khoảng trống, thay đổi chưa commit và giới hạn kiểm chứng.

## Gemini

Đặt khóa trong `.env` trên máy chủ, **không gửi khóa qua chat**:

```dotenv
GEMINI_API_KEY=YOUR_KEY
GEMINI_MODEL=gemini-flash-latest
```

Portable: dừng rồi chạy lại `stop-local.ps1` / `start-local.ps1` để nạp biến môi trường. Docker: `docker compose up -d --force-recreate wordpress`. Vào **Quản trị → Cấu hình hệ thống → Kiểm tra kết nối Gemini**. Có thể đổi model và hướng dẫn bot tại đây; khóa không hiển thị trong trang quản trị.

Không có khóa sẽ chạy bộ câu hỏi demo, ghi rõ đó là hướng dẫn cố định, không phải AI chấm bài. Mỗi tài khoản giới hạn 10 yêu cầu/phút và 100/ngày. Phiên hết hạn sau 24 giờ. Nội dung CV không tự gửi cho Gemini.

**Chưa nghiệm thu Gemini thật:** chưa có khóa hợp lệ trong môi trường. Demo đã được kiểm tra trên trình duyệt và qua REST/HTTP.

## Đóng gói và cấu trúc

```powershell
./scripts/package.ps1
```

Hai ZIP cài được trong WordPress nằm ở `artifacts/`: `techjob-ai-theme.zip` và `techjob-ai-core.zip`. Khi cài lên WordPress khác, cấu hình hằng `TECHJOB_PRIVATE_DIR` trỏ tới thư mục có quyền ghi **ngoài thư mục web công khai**; dùng script seed qua WP-CLI nếu muốn dữ liệu mẫu.

```text
wp-content/themes/techjob-ai/          Theme WordPress
wp-content/plugins/techjob-ai-core/    Nghiệp vụ, giao diện, CSS ATS, REST AI
scripts/                              Cài đặt, chạy, test, sao lưu, đóng gói
tests/                                Policy, WordPress integration, HTTP
docs/                                 Đặc tả, ánh xạ và kết quả kiểm chứng
runtime/                              WordPress/PHP runtime data và bí mật local (bỏ qua Git)
backups/                              SQL và CV riêng tư (bỏ qua Git)
reference/                            Checkout ATS tham chiếu (bỏ qua Git)
```

Xem `docs/IMPLEMENTATION.md`, `docs/MIGRATION.md`, `docs/TEST-RESULTS.md`, `docs/walkthrough.md`. Ảnh ATS cũ ở `docs/reference/screenshots/`; ảnh WordPress mới ở `docs/progress/`.

## Giới hạn bản đầu

Phục vụ chạy đồ án local; chưa triển khai hosting, email/SMS, cổng doanh nghiệp hoặc dịch nội dung tự động. Tài khoản dùng avatar chữ cái. Danh sách ứng viên/lịch/thông báo giới hạn 100 bản ghi trong bản demo. Tìm kiếm song ngữ hiện phục vụ tập dữ liệu nhỏ. Không có cam kết kiểm thử tải/production.
