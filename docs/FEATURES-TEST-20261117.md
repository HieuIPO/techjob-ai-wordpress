# Kết quả bổ sung chức năng và kiểm thử ngày 17/11/2026

## Phạm vi đã hoàn thành

- Nội dung song ngữ trong Admin cho tiêu đề, mô tả và yêu cầu tuyển dụng, cùng tên Anh của bốn nhóm taxonomy. Dữ liệu dùng chung ID, bản dịch trống quay về tiếng Việt.
- Báo cáo tuyển dụng tại /quan-tri/bao-cao/ với khoảng ngày nộp và bộ lọc tin, số hồ sơ, ứng viên duy nhất, hồ sơ đạt, tỷ lệ đạt, phân bố trạng thái và bảng theo tin. CSV dùng cùng truy vấn và bộ lọc.
- Phiếu đánh giá gắn với từng lịch phỏng vấn, gồm ba điểm nguyên 1–5, tổng /15, đề xuất và nhận xét nội bộ. Có người cập nhật, thời gian, phiên bản kiểm tra xung đột sửa. Lịch hủy giữ phiếu ở chế độ chỉ đọc.
- Hướng dẫn test tay có dữ liệu mẫu, thao tác, kết quả mong đợi, ca lỗi, phân quyền và số liệu để đối chiếu ở MANUAL-TEST-20261117.md.

Thang điểm, ba tiêu chí và ý nghĩa báo cáo là các lựa chọn triển khai của lượt này. Tiêu chí gồm chuyên môn, giải quyết vấn đề và giao tiếp. Đề xuất trên phiếu phục vụ Admin cân nhắc. Kết quả tuyển dụng tiếp tục được cập nhật bằng form xử lý hồ sơ hoặc kết quả lịch phỏng vấn.

## Môi trường

Nhánh codex/recruitment-features phát triển tiếp từ c6bc743 của lượt sửa E2E trước. Worktree sạch khi bắt đầu và đã đọc lại AGENTS.md, README.md, HANDOFF-20261104.md cùng source liên quan.

Site thực tế chạy tại http://localhost:18781/ với WordPress 7.1.4, PHP 8.2.12, MariaDB 10.4.32 của XAMPP. Database mới cô lập ở cổng loopback 13318, CV nằm ngoài webroot trong runtime/features-private. Database tạm của lượt 09/11 đã không còn nên lượt này tạo database mới và dùng fixture có định danh riêng.

Schema 3 bổ sung bảng tj_evaluations. Plugin tự kiểm tra version khi khởi tạo. Kiểm tra chạy lại nâng cấp giữ được hồ sơ và phiếu đã lưu. Các trường và bảng tuyển dụng cũ được giữ trong lượt nâng cấp này.

Docker engine chưa hoạt động khi kiểm tra nên môi trường Compose chưa được xác minh trong lượt này. AI/Gemini ngoài phạm vi kiểm thử và chỉnh sửa.

## Kiểm tra đã chạy

| Nhóm | Kết quả | Nội dung |
|---|---|---|
| Luồng tuyển dụng cũ | PASS 74/74 | Khách, Admin, hai ứng viên; Nháp → xuất bản → tìm/lọc → hoàn thiện hồ sơ → tải CV → ứng tuyển → chống trùng → Admin xem CV, đổi trạng thái, ghi chú → lịch, kết quả, hủy, ICS → thông báo → khóa/mở tài khoản. |
| CRUD và phân trang bổ sung | PASS 9/9 | Tạo/sửa/lưu trữ công ty, chặn công ty còn được tham chiếu, tạo/sửa/xóa nhóm nghề và phân trang 10 tin theo 9 + 1. |
| Ba chức năng mới | PASS 65/65 | Form song ngữ, fallback, chung ID ứng tuyển; điểm hợp lệ/sai/thiếu, giới hạn nhận xét, xung đột phiên bản, nâng schema giữ dữ liệu, quyền Admin; báo cáo ngày đầu/cuối theo múi giờ, ngày sai, dữ liệu rỗng, đếm ứng viên duy nhất, lọc tin, CSV khớp bảng, bảo vệ ô giống công thức, nonce và phân quyền xuất. |
| Policy | PASS 12/12 | Bộ tests/policy.php hiện có. |
| Giao diện Chromium | PASS các màn hình đã rà | Form tin, editor song ngữ, danh mục, báo cáo, phiếu đánh giá ở 320/390/768/1280/1440 px với nội dung dài, giao diện Việt/Anh. Đo scrollWidth bằng clientWidth của trang ở các lần kiểm tra. Bảng rộng cuộn trong wrapper mobile. |
| Thao tác trình duyệt | PASS | Lưu editor Anh rồi đối chiếu public, sửa tên kỹ năng ở mobile, lưu phiếu 12/15, tải CSV hai lần liên tiếp, ứng viên thấy lịch và không thấy nhận xét nội bộ. Console kiểm tra không có lỗi/cảnh báo. |
| PHP/JS và diff | PASS | Lint PHP trong wp-content/scripts/tests, node --check cho techjob.js và git diff --check. |
| Docker, thiết bị/trình duyệt khác | BLOCKED / chưa chạy | Docker engine chưa hoạt động. Chưa kiểm tra Safari, Firefox, thiết bị vật lý hoặc zoom trình duyệt thực. |

Các lệnh đã chạy trên máy hiện tại, tại thư mục dự án.

~~~powershell
& 'C:\xampp\php\php.exe' -d extension=zip runtime/audit-http.php
& 'C:\xampp\php\php.exe' -d extension=zip runtime/features-extra.php
& 'C:\xampp\php\php.exe' -d extension=zip tests/recruitment-features.php
& 'C:\xampp\php\php.exe' -d extension=zip tests/policy.php
node --check wp-content/plugins/techjob-ai-core/assets/techjob.js
git diff --check
~~~

Hai script runtime là harness cục bộ của lượt E2E, được Git bỏ qua. Bộ tests/recruitment-features.php được bàn giao cùng source, tự tạo và dọn fixture của chính lượt chạy trên môi trường WordPress local. Bộ scripts/test.ps1 đã được đọc. Bộ đó có các bài cần seed và có phần AI nên lượt này chạy bộ tuyển dụng riêng.

## Sửa và hoàn thiện trong lượt này

| Vấn đề | Thay đổi và kiểm tra |
|---|---|
| Logic đã hỗ trợ bản dịch nhưng Admin thiếu trường nhập. | Bổ sung tiêu đề/editor Anh cho tin, tên Anh cho taxonomy. Lưu bằng handler và metadata hiện có. Form không có bản dịch vẫn dùng dữ liệu Việt. |
| Form taxonomy nhiều trường bị chật trên mobile. | Dùng cùng panel/field với các form Admin, bố cục hai trường trên desktop và một cột trên mobile. Danh sách mục có form sửa riêng. |
| Các trường/editor mới có thể làm tăng chiều ngang. | Grid dùng minmax(0, 1fr), editor giới hạn trong panel, các nhóm điểm/form đổi số cột theo breakpoint. Rà tới 320 px. |
| Nút tải CSV có thể bị cơ chế chống bấm lặp của form khóa sau lần tải đầu. | Đánh dấu form tải riêng và giữ nút hoạt động sau submit. Tải liên tiếp hai lần trên trình duyệt thành công. |
| Hai tab Admin có thể ghi đè phiếu của nhau. | Lưu trong transaction, khóa dòng lịch và đối chiếu revision. Tab cũ nhận lỗi rõ ràng, phiếu mới giữ nguyên. |
| Báo cáo theo ngày có thể lệch giữa giờ Việt Nam và UTC lưu trong DB. | Dùng khoảng đầu ngày địa phương đến đầu ngày tiếp theo, đổi sang UTC khi truy vấn. Đã kiểm tra đầu ngày, giây cuối và đầu ngày kế tiếp. |
| CSV chứa tên tin bắt đầu bằng ký tự giống công thức. | Ghi CSV có escaping và BOM UTF-8, thêm dấu nháy đơn cho tiêu đề có tiền tố công thức. Đã đối chiếu file qua HTTP. |

## Dọn dữ liệu và giới hạn kết quả

Hai lượt chạy bộ feature tự dọn đúng fixture của từng lượt trong finally. Lượt CRUD/phân trang tự dọn 10 tin, 1 công ty và 1 mục taxonomy.

Sau kiểm tra giao diện, dọn tiếp 4 tài khoản thử, 5 bài tin/công ty, 8 mục taxonomy, 3 hồ sơ, 2 lịch, 1 phiếu và 3 CV của lượt hồi quy và UI. Đối chiếu cuối xác nhận applications, history, interviews, evaluations, files, saved, notifications, audit và limits đều có 0 bản ghi. Không còn tin/công ty hoặc taxonomy tuyển dụng thử, kho riêng tư có 0 CV. Tài khoản còn lại là Admin cài đặt.

Chưa thấy lỗi chặn luồng trong các ca đã chạy. Upload được kiểm chứng với PDF, chưa thử DOC/DOCX thật hoặc mốc 5 MB qua HTTP trong lượt này. Chưa kiểm thử tải đồng thời thực tế, lỗi COMMIT do hạ tầng hoặc triển khai HTTPS/hosting. Ca xung đột phiếu dùng hai phiên bản form liên tiếp, không phải bài tải nhiều request đồng thời.

Ảnh minh chứng chứa dữ liệu giả lập và nằm trong docs/progress/features-20261117.
