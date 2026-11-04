# Hướng dẫn agent cho TechJob AI

## Bối cảnh bắt buộc

- Đây là dự án WordPress chạy PHP và MariaDB, được chuyển từ ATS Mini MVC. Đọc `docs/HANDOFF-20261104.md` và `README.md` trước khi xử lý yêu cầu.
- Nghiệp vụ chính nằm trong `wp-content/plugins/techjob-ai-core/`; theme `wp-content/themes/techjob-ai/` giữ tối giản.
- Hai vai trò hiện tại là Admin và ứng viên. Không tự thêm cổng/tài khoản nhà tuyển dụng.
- Giữ WordPress/PHP. Không chuyển sang React/Next.js hoặc thay backend nếu người dùng chưa yêu cầu.
- Chủ dự án đã ủy quyền lượt rà soát end-to-end và chỉnh các lỗi giao diện WordPress đang tích hợp. Có thể sửa giao diện hiện hữu trong phạm vi này; giữ route, tên trường, nonce, nội dung đồng ý và nghiệp vụ backend. Không tự lấy prototype chưa duyệt làm giao diện chính.

## Ranh giới dữ liệu và vận hành

- Dữ liệu demo và tài khoản ứng viên đã được xóa ở cả Docker và portable theo trạng thái bàn giao. Không seed bộ demo hoặc phục hồi backup. Chủ dự án đã yêu cầu kiểm thử E2E; được phép tạo tài khoản/việc làm/CV tạm có định danh rõ trong đúng môi trường kiểm thử, rồi chỉ dọn các bản ghi/tệp do lượt test đó tạo.
- Không xóa, sửa hoặc dọn dữ liệu có trước lượt test; không xóa Admin chính. Nếu không thể xác định an toàn bản ghi/tệp nào do test tạo, dừng phần cleanup đó và báo rõ.
- Không chạy `docker compose down -v`; lệnh đó xóa volume dữ liệu.
- Docker và portable dùng database, tài khoản, CV riêng. Không giả định dữ liệu đồng bộ.
- CV phải ở kho ngoài webroot, giới hạn PDF/DOC/DOCX 5 MB và chỉ chủ CV/Admin được tải.
- Gemini chỉ gọi từ backend. Khóa chỉ lấy từ biến môi trường; không yêu cầu người dùng gửi khóa qua chat và không ghi khóa vào log/tài liệu.
- Phần AI/Gemini do chủ dự án tự xử lý; agent nhận bàn giao không sửa logic, prompt, model hoặc cấu hình AI trừ khi được yêu cầu rõ ràng.
- Đọc `git status` và `git diff` trước khi sửa. Worktree có thay đổi chưa commit; không reset, checkout đè hoặc xóa chúng.

## Quy ước nghiệp vụ

- Trạng thái hồ sơ được lưu bằng mã: `submitted`, `reviewing`, `interview`, `passed`, `rejected`.
- Không giả dạng chế độ demo thành AI thật. Khi Gemini chưa cấu hình, phải giữ nhãn hướng dẫn cố định/demo.
- Giữ route, tên trường form, nonce, nội dung đồng ý, kiểm tra quyền và luồng PRG nếu thay giao diện.
- Nếu yêu cầu còn mơ hồ (ví dụ “duyệt tin” có cần trạng thái chờ duyệt riêng không), nêu rõ cách hiểu trước khi thay đổi mô hình dữ liệu.

## Mã nguồn chính

- `includes/routes.php`: route, phân quyền trang, render.
- `includes/actions.php`: POST handlers, CRUD Admin, đăng ký/đăng nhập.
- `includes/data.php`: truy vấn việc làm, taxonomy và bảng nghiệp vụ.
- `includes/applications.php`: nộp hồ sơ, đổi trạng thái, lịch phỏng vấn.
- `includes/files.php`: upload và tải CV riêng tư.
- `includes/ai.php`: REST luyện phỏng vấn, Gemini và demo.
- `includes/i18n.php`, `includes/view.php`: Việt/Anh, template helpers, widget và danh sách hồ sơ.
- `templates/`: trang public, candidate và Admin.

## Kiểm chứng

- Đọc `scripts/test.ps1` trước khi chạy: các bài integration/HTTP có thể cần việc làm, ứng viên hoặc CV làm fixture. Thực hiện luồng E2E trên Docker `http://localhost:8081/` nếu xác minh URL đúng là TechJob AI; cổng portable `8080` gần đây không phục vụ site.
- Không seed bộ dữ liệu demo để làm cho test qua. Có thể tạo fixture E2E nhỏ, tạm thời và có định danh riêng theo yêu cầu chủ dự án; dọn đúng fixture đó sau khi kiểm chứng.
- Báo rõ lệnh nào đã chạy và giới hạn kiểm tra; kết quả tài liệu cũ không phải bằng chứng cho worktree hiện tại.
- Địa chỉ Docker trong tài liệu là `http://localhost:8081/`; tại lượt rà soát 2026-11-04, `localhost:8080` không trả TechJob AI. Kiểm tra cổng thực tế trước khi kết luận.
