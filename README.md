# TechJob AI — Final project

Website tuyển dụng IT trên WordPress, PHP và MariaDB với hai vai trò Admin và ứng viên.

## Nhóm tải và chạy

Cần Git, PowerShell 7 và Docker Desktop đang chạy Linux engine. Tại terminal PowerShell 7, chạy các lệnh sau.

~~~powershell
git clone --branch final-project https://github.com/HieuIPO/techjob-ai-wordpress.git
cd techjob-ai-wordpress
.\scripts\setup.ps1 -ProjectName techjob-final-project -Port 8082 -SkipDemo
~~~

Mở http://localhost:8082/. Đăng nhập tại http://localhost:8082/dang-nhap/ và quản trị nghiệp vụ tại http://localhost:8082/quan-tri/.

Setup tự tạo WordPress, database và tài khoản Admin mới. Tài khoản/mật khẩu được lưu riêng ở runtime/demo-accounts.txt trên máy vừa cài. Với -SkipDemo, bạn bắt đầu bằng database tuyển dụng trống rồi tạo công ty, danh mục, tin và ứng viên theo [hướng dẫn test tay](docs/MANUAL-TEST-20261117.md). Nếu chủ môi trường muốn bộ dữ liệu demo trên máy mới, bỏ -SkipDemo trong lần cài đầu.

Project name và cổng có thể đổi để chạy song song các bản khác. Chọn giá trị riêng trước khi cài. Hướng dẫn chi tiết và cách xử lý lỗi nằm ở [bàn giao cho nhóm](docs/FINAL-PROJECT.md).

## Chạy kiểm tra tuyển dụng

~~~powershell
.\scripts\test.ps1 -RecruitmentOnly
~~~

Lệnh trên chạy policy và hai suite HTTP, tự tạo tài khoản, tin, CV giả lập và dọn đúng fixture của lượt chạy. Các bài kiểm tra bao phủ tìm việc, đăng ký/đăng nhập, CV, ứng tuyển, xử lý hồ sơ, lịch phỏng vấn, nội dung song ngữ, phiếu đánh giá và báo cáo/CSV.

Với bản portable đã được cài trên Windows, có thể truyền -Local và đường dẫn PHP. Biến TJ_TEST_WP_LOAD cho phép chọn wp-load.php của bản kiểm thử local.

~~~powershell
.\scripts\test.ps1 -Local -RecruitmentOnly -Php 'C:\xampp\php\php.exe'
~~~

[Hướng dẫn test tay từng chức năng](docs/MANUAL-TEST-20261117.md) có các ca PASS/FAIL và số liệu mẫu để nhóm tự đối chiếu.

## Các chức năng

- Tìm theo từ khóa/công ty/kỹ năng, lọc nhóm nghề, vị trí, địa điểm, loại hình và lương, phân trang.
- Admin quản lý công ty, tin Nháp/Đang tuyển/Đã đóng, danh mục và ứng viên.
- Hồ sơ ứng viên, CV riêng tư, lưu việc, ứng tuyển và lịch sử trạng thái.
- Admin tải CV, ghi chú nội bộ, cập nhật hồ sơ, tạo/sửa/hủy lịch và tải ICS.
- Nội dung công ty/tin/danh mục Việt/Anh, fallback về tiếng Việt khi bản dịch trống.
- Phiếu đánh giá từng lịch với ba tiêu chí chấm 1–5, đề xuất và nhận xét nội bộ, kiểm tra phiên bản khi hai tab sửa cùng lúc.
- Báo cáo theo ngày nộp và tin tuyển dụng, đếm ứng viên duy nhất, phân bố trạng thái và xuất CSV.
- Luyện phỏng vấn demo/Gemini chạy ở backend.

## Khởi động lại và dừng

~~~powershell
docker compose up -d wordpress
docker compose stop
~~~

Database, WordPress runtime và CV của bản Docker nằm trong volume riêng của project. Source và cấu hình mẫu nằm trong Git. Cấu hình máy, mật khẩu, database và CV được tạo trong quá trình cài ở máy nhận.

## Đóng gói theme/plugin

~~~powershell
.\scripts\package.ps1
~~~

Kết quả trong artifacts/ gồm techjob-ai-theme.zip và techjob-ai-core.zip. Khi cài lên WordPress khác, cấu hình TECHJOB_PRIVATE_DIR trỏ tới thư mục có quyền ghi ngoài webroot.

## Cấu trúc

| Thư mục | Nội dung |
|---|---|
| wp-content/plugins/techjob-ai-core/ | Route, nghiệp vụ, template, CSS/JS và tích hợp AI |
| wp-content/themes/techjob-ai/ | Theme WordPress |
| scripts/ | Cài đặt, chạy, kiểm tra, sao lưu và đóng gói |
| tests/ | Policy, HTTP E2E và kiểm tra chức năng |
| docs/ | Hướng dẫn test tay, kết quả và tài liệu bàn giao |

Xem [kết quả rà soát cuối](docs/FINAL-TEST-RESULTS.md), [gói bàn giao trước](docs/HANDOFF-20261104.md) và [ánh xạ ATS → WordPress](docs/MIGRATION.md).

## Gemini

Đặt khóa trong `.env` trên máy chủ, **không gửi khóa qua chat**:

```dotenv
GEMINI_API_KEY=YOUR_KEY
GEMINI_MODEL=gemini-flash-latest
```

Portable: dừng rồi chạy lại `stop-local.ps1` / `start-local.ps1` để nạp biến môi trường. Docker: `docker compose up -d --force-recreate wordpress`. Vào **Quản trị → Cấu hình hệ thống → Kiểm tra kết nối Gemini**. Có thể đổi model và hướng dẫn bot tại đây; khóa không hiển thị trong trang quản trị.

Không có khóa sẽ chạy bộ câu hỏi demo, ghi rõ đó là hướng dẫn cố định, không phải AI chấm bài. Mỗi tài khoản giới hạn 10 yêu cầu/phút và 100/ngày. Phiên hết hạn sau 24 giờ. Nội dung CV không tự gửi cho Gemini.

**Chưa nghiệm thu Gemini thật:** chưa có khóa hợp lệ trong môi trường. Demo đã được kiểm tra trên trình duyệt và qua REST/HTTP.
