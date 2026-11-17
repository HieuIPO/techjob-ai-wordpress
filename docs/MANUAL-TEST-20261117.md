# Hướng dẫn test tay TechJob AI

Ba phần được bổ sung trong nhánh codex/recruitment-features gồm nhập nội dung Việt/Anh, báo cáo tuyển dụng và CSV, phiếu đánh giá phỏng vấn. Thực hiện theo thứ tự bên dưới để cùng một bộ dữ liệu đi qua toàn bộ luồng.

## Chuẩn bị trên máy hiện tại

1. Mở http://localhost:18781/ và http://localhost:18781/dang-nhap/?lang=vi.
2. Tài khoản Admin cài đặt nằm trong tệp local runtime/features-private/demo-accounts.txt. Mở tệp ở máy để lấy tài khoản. Tệp này nằm ngoài Git.
3. Nếu site đang tắt, chạy PowerShell tại thư mục dự án với lệnh dưới đây. Script runtime này dành cho bản kiểm thử hiện tại trên máy, dùng PHP/MariaDB XAMPP và database riêng. Database kiểm thử nằm trong thư mục Temp, đường dẫn được ghi tại runtime/audit-db-path.txt. Nếu thư mục đó đã bị dọn, cần cài lại môi trường kiểm thử cô lập.
4. Chuẩn bị một PDF giả lập dưới 5 MB, hai cửa sổ trình duyệt với phiên đăng nhập độc lập, chẳng hạn cửa sổ thường cho Admin và cửa sổ ẩn danh cho ứng viên.
5. Ghi lại ID tin, ứng viên, CV, hồ sơ và lịch mà bạn tạo. Thêm dấu thời gian vào tên đăng nhập/email để tránh trùng với lượt test trước.

~~~powershell
.\runtime\features-resume.ps1
~~~

Sau lượt kiểm tra tự động, database hiện chỉ còn Admin cài đặt. Bạn sẽ tạo dữ liệu thử qua giao diện theo các bước tiếp theo. Các đường dẫn runtime dành riêng cho máy này. Khi ghép branch vào bản WordPress khác, dùng URL và tài khoản của bản đó.

## 1. Tạo công ty và danh mục song ngữ

Đăng nhập Admin rồi vào Quản trị → Công ty → Thêm công ty.

| Thao tác | Kết quả mong đợi |
|---|---|
| Nhập tên Việt “MANUAL Công nghệ” và tên Anh “MANUAL Technology”, nhập giới thiệu ở cả hai ngôn ngữ rồi lưu. | Có thông báo thành công. Công ty xuất hiện trong danh sách Admin. |
| Mở trang Công ty ở phía công khai, đổi English rồi Tiếng Việt. | Tên và giới thiệu đổi đúng theo ngôn ngữ. |
| Vào Quản trị → Danh mục & kỹ năng. Tạo kỹ năng “MANUAL Kiến trúc PHP” / “MANUAL PHP Architecture”. | Cả hai tên được lưu trong cùng một mục. |
| Tạo thêm nhóm nghề, vị trí và địa điểm với tên Việt/Anh khác nhau. Ví dụ “MANUAL Hà Nội” / “MANUAL Hanoi”. | Có bốn loại danh mục để chọn trong form tin. |
| Sửa tên Anh của kỹ năng rồi lưu. Tải lại trang. | Tên mới vẫn còn. |
| Bỏ trống tên Anh và lưu. | Lưu được. Ở giao diện English, danh mục dùng tên Việt. |
| Điền lại tên Anh để dùng trong các bước tiếp theo. | Bộ lọc và thẻ kỹ năng phía công khai hiển thị tên Anh khi chọn English. |
| Tạo một mục không gắn với tin rồi xóa. | Có xác nhận thao tác, mục không còn trong danh sách. |

Tên Việt là trường bắt buộc. Tên Anh là tùy chọn. Form sửa luôn cho thấy riêng hai trường để bạn biết đang sửa bản dịch nào.

## 2. Tạo hai tin, kiểm tra Nháp và xuất bản

Vào Quản trị → Tin tuyển dụng → Thêm tin.

| Trường | Tin A | Tin B |
|---|---|---|
| Tiêu đề Việt | MANUAL Kỹ sư PHP A | MANUAL Kỹ sư PHP B |
| Tiêu đề Anh | MANUAL PHP Engineer A | MANUAL PHP Engineer B |
| Công ty | MANUAL Công nghệ | MANUAL Công nghệ |
| Mô tả Việt/Anh | Hai đoạn khác nhau có chữ “Mô tả A” / “Description A” | Hai đoạn có chữ “Mô tả B” / “Description B” |
| Yêu cầu Việt/Anh | “Yêu cầu A” / “Requirements A” | “Yêu cầu B” / “Requirements B” |
| Lương | Chọn Lương thỏa thuận hoặc nhập khoảng lương hợp lệ | Nhập một khoảng lương khác để thử lọc |
| Hạn nộp | Một ngày trong tương lai | Một ngày trong tương lai |
| Kỹ năng, nhóm nghề, vị trí, địa điểm | Chọn các mục MANUAL vừa tạo | Chọn các mục MANUAL vừa tạo |

1. Lưu tin A ở trạng thái Nháp. Mở cửa sổ chưa đăng nhập, tìm “MANUAL Kỹ sư PHP A”. Tin chưa xuất hiện ở trang chủ và danh sách. Mở URL chi tiết của tin bằng phiên khách phải nhận trang không tìm thấy.
2. Sửa tin A thành Đang tuyển. Trang chủ, danh sách và chi tiết công khai phải thấy cùng tin.
3. Đổi English trên trang chi tiết. Tiêu đề, mô tả, yêu cầu, kỹ năng và địa điểm phải đổi đúng. Đường dẫn vẫn tham chiếu cùng tin.
4. Quay lại Admin, kiểm tra cả bốn editor mô tả/yêu cầu vẫn giữ nội dung sau khi tải lại.
5. Xóa ba bản dịch tiếng Anh của tin A rồi lưu. Ở English, nội dung tương ứng phải trở về tiếng Việt. Điền lại bản dịch để tiếp tục.
6. Tạo và xuất bản tin B.
7. Tìm theo tên công ty Việt hoặc Anh, tìm “MANUAL PHP Architecture”, thử bộ lọc kỹ năng, địa điểm, loại hình và lương. Tin phù hợp xuất hiện, tin không thỏa điều kiện bị loại.
8. Tạo thử tin có lương tối đa thấp hơn tối thiểu hoặc thiếu mô tả/yêu cầu. Form phải báo lỗi và không tạo thêm tin hợp lệ.
9. Thử xóa danh mục đang gắn với tin, hoặc xóa công ty đang có tin. Hệ thống phải từ chối và giữ dữ liệu tham chiếu.

## 3. Đăng ký, CV và ứng tuyển

Dùng cửa sổ ẩn danh để đăng ký ứng viên A. Chọn mật khẩu ít nhất 10 ký tự và email giả lập của bạn.

1. Mở tin A khi chưa hoàn thiện số điện thoại. Giao diện phải hướng dẫn cập nhật hồ sơ.
2. Vào Hồ sơ & CV, nhập họ tên, email và điện thoại hợp lệ rồi lưu. Tải lại để đối chiếu.
3. Đổi đuôi một tệp văn bản thành fake.pdf và tải thử. Hệ thống phải báo CV không hợp lệ.
4. Tải PDF giả lập hợp lệ. Tên CV phải xuất hiện. Bấm tải xuống và mở lại để kiểm tra đúng nội dung.
5. Vào tin A, bấm Lưu việc làm, kiểm tra mục Đã lưu, rồi Bỏ lưu và kiểm tra mục đó lần nữa.
6. Chọn CV, nhập lời giới thiệu và đánh dấu đồng ý gửi thông tin. Bấm Nộp hồ sơ. Trong Đã ứng tuyển phải có đúng một hồ sơ cho tin A với trạng thái Mới nộp.
7. Chuyển ngôn ngữ sang English rồi quay lại tin A. Hệ thống phải nhận ra bạn đã nộp, có liên kết xem hồ sơ đã gửi. Gửi lại form cũ phải bị từ chối và không tăng số hồ sơ.
8. Nộp tiếp tin B bằng cùng CV hoặc dùng trường tải CV mới ngay trên form ứng tuyển. Nếu dùng tải mới, Admin phải tải được đúng tệp bạn vừa nộp.
9. Lúc này ứng viên A có hai hồ sơ cho hai tin khác nhau.
10. Đăng ký ứng viên B ở một phiên riêng. Dùng URL hồ sơ/CV của ứng viên A để thử truy cập. Phải bị chặn, không tải được CV và không đọc được hồ sơ.
11. Dùng tài khoản ứng viên truy cập /quan-tri/ hoặc /quan-tri/bao-cao/. Phải bị từ chối quyền.
12. Đăng xuất rồi mở trang tài khoản. Hệ thống phải chuyển về đăng nhập. Mật khẩu sai phải hiện lỗi trên trang.

Đã kiểm tra tự động với PDF. Bạn có thể kiểm tra thêm DOC/DOCX thật và tệp lớn hơn 5 MB. Tệp quá giới hạn phải bị từ chối.

## 4. Admin xử lý hồ sơ và chấm phiếu phỏng vấn

1. Ở Admin → Hồ sơ ứng tuyển, tìm ứng viên A và mở hồ sơ tin A. Đối chiếu họ tên, email, điện thoại, lời giới thiệu và CV.
2. Đổi trạng thái thành Đang xem xét, nhập ghi chú nội bộ rồi lưu. Ứng viên mở lại phải thấy trạng thái mới và thông báo. Ghi chú nội bộ chỉ xuất hiện ở Admin.
3. Từ chi tiết hồ sơ, chọn Tạo lịch phỏng vấn. Chọn ngày giờ trong tương lai, địa điểm/link và lưu. Ứng viên phải thấy lịch, trạng thái hồ sơ chuyển thành Mời phỏng vấn. Tải ICS và kiểm tra thời gian.
4. Vào Admin → Lịch phỏng vấn → Chỉnh sửa / đánh giá. Cuộn xuống Phiếu đánh giá phỏng vấn.
5. Chấm Chuyên môn 4, Giải quyết vấn đề 3, Giao tiếp 5. Chọn Cân nhắc thêm, nhập nhận xét nội bộ rồi bấm Lưu đánh giá.
6. Sau lưu và tải lại, tổng phải là 12/15. Ba điểm, đề xuất, nhận xét, người cập nhật và thời gian vẫn còn.
7. Đổi Chuyên môn thành 5 rồi lưu. Tổng phải là 13/15. Cùng một lịch chỉ có một phiếu được cập nhật.
8. Bỏ một điểm hoặc bỏ chọn đề xuất rồi lưu. Phải hiện lỗi và giữ nguyên phiếu đã lưu trước đó.
9. Để thử xung đột, mở cùng phiếu trong hai tab Admin trước khi sửa. Tab thứ nhất sửa và lưu. Tab thứ hai dùng dữ liệu cũ rồi lưu phải nhận thông báo phiếu vừa được cập nhật ở phiên khác. Tải lại tab thứ hai để sửa trên bản mới.
10. Ứng viên mở trang lịch và hồ sơ của mình. Chỉ thấy lịch và trạng thái, không thấy phiếu điểm hay nhận xét nội bộ.
11. Việc lưu đề xuất trên phiếu giữ nguyên trạng thái tuyển dụng đang có. Để kết luận hồ sơ, chỉnh Kết quả của lịch thành Đạt rồi bấm Lưu thay đổi trong form lịch. Hồ sơ chuyển thành Đạt và ứng viên thấy cập nhật.

Mỗi phiếu có ba điểm nguyên từ 1 đến 5, tổng tối đa 15. Nhận xét tối đa 5.000 ký tự. Phiếu gắn với từng lịch phỏng vấn và chỉ Admin được đọc/sửa.

## 5. Báo cáo và CSV với số liệu có thể đối chiếu

Ở bước này, ứng viên A đã nộp tin A và B. Hồ sơ A ở trạng thái Đạt, hồ sơ B ở trạng thái Mới nộp. Nếu database chỉ có bộ MANUAL này, kết quả toàn bộ phải như bảng dưới. Nếu có dữ liệu khác, chọn từng tin MANUAL để đối chiếu riêng.

1. Vào Admin → Báo cáo tuyển dụng.
2. Chọn Từ ngày và Đến ngày bao gồm ngày bạn nộp hai hồ sơ, sau đó bấm Xem báo cáo.
3. Đối chiếu thẻ số liệu, phân bố trạng thái và bảng theo tin.

| Bộ lọc | Tổng hồ sơ | Ứng viên duy nhất | Hồ sơ đạt | Tỷ lệ đạt |
|---|---|---|---|---|
| Hai tin A và B là toàn bộ dữ liệu trong khoảng | 2 | 1 | 1 | 50% |
| Chỉ tin A | 1 | 1 | 1 | 100% |
| Chỉ tin B | 1 | 1 | 0 | 0% |
| Khoảng ngày không có hồ sơ | 0 | 0 | 0 | 0% |

4. Chọn tin A rồi bấm Xem báo cáo. Bấm Xuất CSV. Mở CSV trong Excel hoặc công cụ đọc CSV, chọn mã hóa UTF-8 và dấu phân cách phẩy nếu cần.
5. CSV phải có đúng dòng của tin A, khoảng ngày đã chọn và các số lượng giống bảng trên trang. Tên tiếng Việt phải đọc được.
6. Bấm Xuất CSV lần thứ hai mà không tải lại trang. Phải tải được tiếp.
7. Đổi English, giữ cùng khoảng ngày/tin và xem lại. Nhãn giao diện và tiêu đề CSV chuyển tiếng Anh, số lượng giữ nguyên.
8. Chọn khoảng ngày không có hồ sơ. Trang hiện số 0 và thông báo rỗng, CSV chỉ có hàng tiêu đề.
9. Chọn ngày bắt đầu sau ngày kết thúc. Trang báo lỗi rõ ràng. Chọn lại khoảng hợp lệ để tiếp tục.
10. Chuyển một hồ sơ qua Đang xem xét hoặc Không đạt, quay lại báo cáo và bấm Xem báo cáo. Số theo trạng thái phải thay đổi đúng.
11. Đóng tin A trong Admin rồi mở báo cáo cho cùng khoảng ngày. Hồ sơ đã nộp vẫn được thống kê. Tin đóng không nhận thêm ứng tuyển từ form cũ.

Báo cáo dùng ngày nộp hồ sơ theo múi giờ WordPress, bao gồm cả ngày bắt đầu và ngày kết thúc. Số liệu phản ánh trạng thái hiện tại của hồ sơ trong nhóm ngày nộp đã chọn. Ứng viên duy nhất được tính theo tài khoản trong bộ lọc. Tỷ lệ đạt bằng số hồ sơ Đạt chia tổng hồ sơ. Tin chưa có hồ sơ trong khoảng sẽ không có dòng thống kê.

## 6. Hủy lịch và rà tiếp các luồng sẵn có

1. Hủy lịch A ở Admin và xác nhận thao tác. Ứng viên thấy Đã hủy, ICS ghi lịch đã hủy.
2. Nếu đây là lịch duy nhất và trước đó lịch mang kết quả Đạt, hồ sơ trở về Đang xem xét. Báo cáo phải phản ánh thay đổi này khi bạn xem lại.
3. Bấm Xem đánh giá ở lịch đã hủy. Điểm và nhận xét đã lưu vẫn đọc được, không có form sửa phiếu.
4. Mở lại tab sửa phiếu từ trước khi hủy và thử lưu. Hệ thống phải từ chối.
5. Tạo lịch mới cho hồ sơ ở trạng thái còn mở. Lịch mới có phiếu riêng và ban đầu chưa có điểm.
6. Kiểm tra thông báo cho cả Admin/ứng viên và nút đánh dấu tất cả đã đọc.
7. Khóa ứng viên B ở Admin. Phiên cũ của ứng viên B phải bị thu hồi, mật khẩu đúng vẫn không đăng nhập được. Mở khóa rồi đăng nhập lại phải thành công.
8. Kiểm tra tin hết hạn, danh sách công ty, tìm kiếm, phân trang và đăng nhập/đăng xuất ở cả hai ngôn ngữ.

## 7. Rà giao diện và scale

1. Kiểm tra trang chủ, danh sách việc, form tin song ngữ, danh mục, báo cáo, chi tiết hồ sơ, lịch và phiếu chấm.
2. Dùng DevTools responsive ở chiều rộng 320, 390, 768, 1280 và 1440 px. Thử thêm zoom trình duyệt 125%, 150%, 200%.
3. Đặt tiêu đề và tên công ty dài. Chữ phải xuống dòng hoặc được giới hạn bên trong control, không đẩy toàn trang sang ngang.
4. Các form mới phải chuyển về một cột ở màn hình nhỏ. Nhãn nằm ngay trên trường, nút bấm dùng được, editor không tràn khỏi panel.
5. Bảng báo cáo rộng được cuộn ngang trong khung của bảng trên mobile. Toàn trang vẫn giữ chiều rộng màn hình.
6. Mở/đóng menu mobile. Nút đóng phải truy cập được. Tab bàn phím phải bỏ qua sidebar đang ẩn.
7. So sánh số liệu, nội dung và quyền ở desktop/mobile. Thay đổi kích thước không được làm thay đổi dữ liệu đã lưu.

Lượt tự động đã rà Chromium ở các chiều rộng trên. Zoom trình duyệt thực và các trình duyệt khác là các bước kiểm tra bổ sung dành cho bạn.

## Ghi nhận kết quả và dọn dữ liệu test tay

Với mỗi ca, ghi PASS/FAIL, URL, tên/ID dữ liệu và kết quả thực tế. Nếu gặp lỗi, ghi thứ tự thao tác để tái hiện và chụp màn hình. Giữ mật khẩu và CV cá nhân ngoài ảnh minh chứng.

Dữ liệu bạn tạo khi test tay là bộ MANUAL của bạn. Chỉ dọn đúng các tin/danh mục/công ty/tài khoản đó sau khi đã ghi kết quả. Tin có thể đưa vào thùng rác qua Admin. Hồ sơ/lịch vẫn giữ lịch sử theo hành vi hiện tại của hệ thống.

Có thể chạy lại bộ kiểm tra tự động cho ba chức năng mới tại thư mục dự án. Script chỉ chạy với WordPress ở môi trường local, tự tạo fixture ngẫu nhiên và dọn fixture của chính lượt đó.

~~~powershell
& 'C:\xampp\php\php.exe' -d extension=zip tests/recruitment-features.php
~~~

Bộ kiểm tra này dùng runtime/wordpress/wp-load.php mặc định. Có thể đặt biến môi trường TJ_TEST_WP_LOAD trỏ tới wp-load.php của một bản WordPress local khác. Site và database của bản đó phải đang chạy. Việc chạy liên tiếp nhiều lượt đăng nhập có thể chạm giới hạn đăng nhập 20 lần/15 phút của hệ thống.
