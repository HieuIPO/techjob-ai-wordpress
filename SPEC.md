# Đặc tả TechJob AI — bản WordPress

## Mục tiêu và nguồn
Chuyển giao diện và luồng ATS Mini sang WordPress, giữ cả phía ứng viên và dashboard quản trị. Giao diện lấy từ bản local đã chụp; nghiệp vụ đối chiếu GitHub HieuIPO/ats-mini-mvc commit 56d6080f85d2f233bca468da6d31e81c0617d7e7. Không sửa dự án ATS cũ.

## Phạm vi đã chốt
- Hai vai trò: Ứng viên và Admin; khách là trạng thái chưa đăng nhập. Bỏ HR và bước duyệt giữa HR/Admin.
- Trang chủ, việc làm nổi bật, công ty, tìm/lọc kỹ năng/lương/địa điểm/loại hình, chi tiết và ứng tuyển.
- Đăng ký/đăng nhập, hồ sơ cá nhân, CV, lưu việc, lịch sử ứng tuyển, trạng thái, lịch và thông báo.
- Admin: dashboard dữ liệu thực, CRUD tin/công ty/danh mục, hồ sơ/CV, trạng thái, ghi chú nội bộ, lịch phỏng vấn/kết quả, khóa/mở ứng viên, cấu hình AI/ngôn ngữ.
- Giao diện và dữ liệu mẫu Việt–Anh; nội dung hai bản dịch dùng chung ID việc làm.
- Gemini phía máy chủ và demo ghi nhãn, 5 câu mỗi phiên. Không tự gửi CV sang AI.
- Dữ liệu IT mẫu mới; không nhập tài khoản/CV cũ.

## Kiến trúc và quy tắc
Theme techjob-ai; plugin techjob-ai-core. WordPress users/cookie/capability cho tài khoản. CPT cho việc làm/công ty, taxonomy cho kỹ năng/danh mục, bảng InnoDB riêng cho hồ sơ, tệp, lịch sử, lịch, lưu việc, thông báo, audit và AI.
CV PDF/DOC/DOCX tối đa 5 MB, kiểm tra extension/chữ ký, lưu ngoài webroot; chỉ chủ sở hữu và Admin tải được. Một ứng viên một hồ sơ cho một việc làm. Snapshot tên/email/điện thoại và CV lúc nộp không đổi theo profile mới.
Trạng thái: submitted/reviewing/interview/passed/rejected. Tin: Nháp/Đang tuyển/Đã đóng. Lịch và kết quả tự đối soát trạng thái theo ATS cũ. Dữ liệu UTC, hiển thị Asia/Ho_Chi_Minh.
Không sửa core/vendor. Validate/sanitize đầu vào, escape đầu ra, nonce và kiểm tra quyền phía máy chủ. Không commit khóa, runtime, backup. PHP dùng tiền tố tj_; tách dữ liệu/nghiệp vụ/template/assets.

## Chạy và cấu trúc
- wp-content/themes/techjob-ai: theme.
- wp-content/plugins/techjob-ai-core: nghiệp vụ, giao diện, assets.
- scripts: cài đặt, chạy, dừng, test, backup, đóng gói.
- tests: policy, WordPress integration, HTTP.
- docs: ánh xạ, kết quả, audit và ảnh.
- runtime và backups: dữ liệu local/bí mật, bỏ qua Git.

Docker Compose là cấu hình cài mới. Docker Desktop hiện lỗi socket dockerInference; bản chạy kiểm thử dùng PHP có sẵn và MariaDB portable riêng trong dự án.

```powershell
./scripts/setup.ps1
./scripts/start-local.ps1
./scripts/test.ps1 -Local
./scripts/backup.ps1 -Local
./scripts/package.ps1
```

## Nghiệm thu và giới hạn
Kiểm thử luồng ứng viên/Admin, chống trùng, quyền CV/hồ sơ, nonce, trạng thái và lịch, Việt–Anh, demo AI và phục hồi backup. Kết quả thực tế: docs/TEST-RESULTS.md.
Bản đầu phục vụ đồ án local, chưa hosting/email/SMS/cổng doanh nghiệp/avatar ảnh/kiểm thử tải. Chưa nghiệm thu Gemini thật khi thiếu API key và Docker runtime khi engine còn lỗi. Xem README để chạy và cấu hình.
