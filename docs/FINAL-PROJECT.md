# Bàn giao final-project cho nhóm

## Tải source và cài mới

Dùng nhánh final-project trên kho được gửi trong chat. Tên nhánh dùng dấu gạch nối vì Git không cho khoảng trắng trong tên ref.

Cài Git, PowerShell 7 và Docker Desktop có Linux engine. Mở Docker Desktop trước, kiểm tra docker version có cả Client và Server.

~~~powershell
git clone --branch final-project https://github.com/HieuIPO/techjob-ai-wordpress.git
cd techjob-ai-wordpress
.\scripts\setup.ps1 -ProjectName techjob-final-project -Port 8082 -SkipDemo
~~~

Nếu tải bằng nút Download ZIP, giải nén và mở PowerShell 7 tại thư mục có compose.yaml, rồi chạy lệnh setup ở trên.

Setup tải WordPress/PHP và MariaDB, tạo database, kích hoạt theme/plugin, nâng schema và tạo Admin. Mở http://localhost:8082/. Tài khoản Admin nằm trong runtime/demo-accounts.txt của máy vừa cài. Mỗi máy có mật khẩu được tạo riêng.

-SkipDemo tạo môi trường tuyển dụng trống. Tạo dữ liệu qua Admin theo MANUAL-TEST-20261117.md. Nếu nhóm muốn bộ demo hư cấu khi cài mới, bỏ -SkipDemo trong lần cài đầu. Dùng project name và cổng riêng cho từng clone chạy cùng máy. Project name dùng chữ thường, số, dấu gạch nối hoặc gạch dưới.

## Kiểm tra tự động

~~~powershell
.\scripts\test.ps1 -RecruitmentOnly
~~~

Lệnh này chạy 12 kiểm tra policy, 83 kiểm tra E2E tuyển dụng và 74 kiểm tra chức năng. Hai suite HTTP tự tạo dữ liệu giả lập có định danh ngẫu nhiên, rồi dọn dữ liệu của chính lượt chạy.

Các luồng gồm Nháp/xuất bản tin, tìm kiếm/lọc/phân trang, đăng ký/đăng nhập, profile/CV, nộp trùng, quyền xem/tải CV, Admin xử lý trạng thái/ghi chú, lịch phỏng vấn/ICS, khóa/mở ứng viên, CRUD công ty/danh mục, nhập Việt/Anh, chọn nhiều kỹ năng, phiếu điểm và báo cáo/CSV.

Nếu chạy bản portable đã cài, dùng lệnh sau. Có thể đặt TJ_TEST_WP_LOAD trỏ tới wp-load.php của bản local muốn kiểm tra.

~~~powershell
.\scripts\test.ps1 -Local -RecruitmentOnly -Php 'C:\xampp\php\php.exe'
~~~

Các bài test dùng dữ liệu tạm. Nếu bị dừng cưỡng bức giữa chừng, kiểm tra những tên bắt đầu bằng E2E hoặc tj_feature của lượt đó trước khi dọn. Không xóa dữ liệu khác để làm test qua.

## Kiểm tra bằng giao diện

Đọc MANUAL-TEST-20261117.md theo thứ tự từ tạo công ty/danh mục đến ứng tuyển, chấm điểm và báo cáo. Khi chạy Docker, thay URL 18781 trong tài liệu bằng URL của máy bạn, chẳng hạn http://localhost:8082/.

Ô Kỹ năng dùng danh sách checkbox khi bấm vào, có thể chọn nhiều mục rồi bấm lại để đóng hoặc dùng Escape. Bốn ô Kỹ năng, Nhóm nghề, Vị trí và Địa điểm dùng lưới 2×2 trên desktop và một cột trên mobile.

Kết quả báo cáo dùng ngày nộp theo múi giờ của site và trạng thái hiện tại. Ví dụ một ứng viên nộp hai tin, một hồ sơ Đạt thì tổng hồ sơ bằng 2, ứng viên duy nhất bằng 1 và tỷ lệ đạt bằng 50%.

## Chạy lại, dừng và đóng gói

~~~powershell
docker compose up -d wordpress
docker compose stop
.\scripts\package.ps1
~~~

Hai ZIP theme/plugin nằm trong artifacts/. Toàn bộ source, script cài đặt, kiểm tra, tài liệu và assets được quản lý trong branch. WordPress runtime, database, mật khẩu và CV được tạo riêng khi cài trên máy nhận.

## Khi gặp lỗi

| Hiện tượng | Cách kiểm tra |
|---|---|
| Không kết nối Docker daemon | Mở Docker Desktop, chọn Linux engine và kiểm tra docker version. |
| Docker Hub báo TLS timeout/EOF | Kiểm tra kết nối mạng, chạy docker compose pull rồi chạy lại setup với cùng project/cổng. |
| Cổng đã được sử dụng | Chọn cổng khác và truyền -Port. Trên clone đã có dữ liệu, giữ nguyên project name để dùng đúng volume. |
| Chưa có công ty hoặc danh mục để chọn | Tạo công ty và các mục ở Admin trước khi tạo tin. |
| Quá nhiều lần đăng nhập | Giới hạn đăng nhập là 20 lần/15 phút trên mỗi IP. Chờ hết cửa sổ giới hạn rồi thử lại. |
| Bộ test thất bại | Ghi lại dòng FAIL, đọc docker compose logs wordpress db, đối chiếu FINAL-TEST-RESULTS.md và gửi nhóm bước tái hiện. |

Tích hợp AI/Gemini do phần việc riêng của chủ dự án phụ trách. Kết quả nghiệm thu trong branch này tập trung vào tuyển dụng, giao diện và quy trình cài đặt cho nhóm.
