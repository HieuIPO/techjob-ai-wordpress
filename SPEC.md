# Đặc tả TechJob AI

## Mục tiêu
Xây dựng website WordPress tuyển dụng IT và luyện phỏng vấn, có giao diện tiếng Việt và tiếng Anh.

## Phạm vi
- Trang chủ: tìm kiếm, việc làm nổi bật, công ty nổi bật.
- Danh sách việc làm: lọc kỹ năng, lương, địa điểm; phân trang.
- Chi tiết việc làm: mô tả, yêu cầu, hạn nộp và nộp CV.
- Hồ sơ ứng viên: đăng ký, đăng nhập, cập nhật thông tin, xem lịch sử ứng tuyển và trạng thái.
- Widget phỏng vấn: chọn kỹ năng, trò chuyện với AI, nhận nhận xét; thông báo rõ khi dịch vụ chưa được cấu hình.
- Chuyển ngôn ngữ Việt/Anh cho giao diện; nội dung tin cần bản dịch riêng.
- Quản trị: thống kê, CRUD và duyệt việc làm, nhóm nghề/kỹ năng, ứng viên/hồ sơ, cấu hình AI/ngôn ngữ.

## Giả định cần xác nhận
- Bản đầu phục vụ đồ án, chạy local.
- Hai vai trò: ứng viên và quản trị viên. Chưa có cổng riêng cho doanh nghiệp.
- CV chỉ chủ sở hữu và quản trị viên có quyền phù hợp được tải; không công khai URL tệp.
- Trạng thái hồ sơ: đã nộp, đang xem xét, mời phỏng vấn, từ chối, trúng tuyển.
- Khóa API AI chỉ lưu phía máy chủ; không đưa vào JavaScript trình duyệt.

## Công nghệ và cấu trúc dự kiến
WordPress/PHP/MySQL. Phiên bản cụ thể sẽ chọn sau khi kiểm tra môi trường.
- wp-content/themes/techjob-ai/: giao diện responsive và chuỗi giao diện có thể dịch.
- wp-content/plugins/techjob-ai-core/: việc làm, phân quyền, ứng tuyển, CV, thống kê và tích hợp AI.
- docs/: đặc tả, hướng dẫn cài đặt và nghiệm thu.
Không sửa WordPress core hoặc mã của plugin bên thứ ba.

## Lệnh kiểm tra dự kiến
Kiểm tra PHP khi có PHP trên PATH:
`php -l wp-content/plugins/techjob-ai-core/techjob-ai-core.php`
Lệnh cài/chạy phụ thuộc lựa chọn LocalWP, XAMPP hoặc Docker; sẽ bổ sung khi môi trường được xác định.

## Quy ước mã
Dùng tiền tố techjob_ai_, WordPress Coding Standards, kiểm tra capability và nonce cho thao tác thay đổi dữ liệu.
Ví dụ: `$job_id = absint( $_POST['job_id'] ?? 0 );`
Validate đầu vào, escape đầu ra, dùng API WordPress cho truy vấn và người dùng.

## Kiểm thử và nghiệm thu
- Ứng viên tìm và lọc đúng việc làm, đăng nhập và nộp CV hợp lệ.
- Chặn CV sai định dạng/quá dung lượng; người không có quyền không truy cập được CV.
- Ứng viên chỉ xem được hồ sơ ứng tuyển của mình.
- Quản trị thay đổi trạng thái và thống kê phản ánh dữ liệu thực.
- Giao diện hoạt động ở điện thoại và desktop, chuyển được Việt/Anh.
- Chatbot phản hồi khi cấu hình hợp lệ; xử lý lỗi, giới hạn lượt gọi và không lộ khóa.
- Kiểm tra thủ công luồng đầu cuối trên WordPress và kiểm thử logic phân quyền/upload trước khi bàn giao.

## Giới hạn
- Luôn: bảo vệ CV, xác thực và phân quyền phía máy chủ, ghi lại hướng dẫn cài đặt.
- Cần thống nhất: môi trường chạy, nhà cung cấp AI, cổng doanh nghiệp nếu bổ sung.
- Không: commit khóa bí mật, công khai CV, sửa WordPress core.

## Điểm chưa chốt
Máy đã có WordPress/LocalWP/XAMPP chưa? Ưu tiên dùng plugin sẵn có hay tự viết theme/plugin để nộp kèm mã nguồn?

## Quyết định cập nhật ngày 02/11/2026
Giữ giao diện ứng viên và quản trị ATS, giữ các chức năng cũ nhưng chỉ có hai vai trò: Ứng viên và Admin; bỏ HR và duyệt tin giữa các vai trò. Chạy Docker Compose, dùng dữ liệu IT mẫu mới, Gemini API kèm demo ghi nhãn, dịch giao diện và tin/công ty mẫu Việt/Anh. Xem docs/reference/README.md và ảnh chụp bản ATS local trong docs/reference/screenshots. Bản local khác nhánh main GitHub; cần chốt nguồn tham chiếu trước khi port.

