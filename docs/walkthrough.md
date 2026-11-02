# Kiểm tra bảo mật TechJob AI

**Trạng thái:** đã rà soát mã và chạy kiểm tra local; không phải kết quả scan tự động toàn diện.

SecureCoder không có sidecar/API hoặc biến `SECURECODER_API_PORT` trong môi trường nên bước scan tự động được bỏ qua đúng hướng dẫn skill. Không tuyên bố công cụ đã scan hoặc không còn lỗ hổng.

## Biện pháp đã triển khai và kiểm chứng

| Khu vực | Biện pháp | Bằng chứng |
|---|---|---|
| Phân quyền | WordPress session/capability; vai trò ứng viên cố định khi đăng ký | Integration và HTTP chặn ứng viên vào Admin |
| Hồ sơ và CV | Kiểm tra chủ sở hữu phía máy chủ; tệp ngoài webroot; download nosniff/attachment | HTTP chặn ID người khác, tải được CV của mình |
| CSRF | Nonce theo thao tác form; REST cookie + nonce; logout có nonce | HTTP nonce sai trả 403 |
| Upload | 5 MB, extension và signature; ZIP phải có cấu trúc DOCX; UUID tên lưu | Policy PDF/ZIP giả, HTTP PDF thật/giả |
| SQL | `$wpdb->prepare`, insert/update và whitelist bảng | Rà soát source nghiệp vụ |
| XSS | Escape output, `wp_kses_post` nội dung tin, chatbot dùng `textContent` | Rà soát template/JS |
| Nộp trùng | Unique user/job, transaction và khóa hàng job | Integration/HTTP duplicate |
| AI | Khóa trong biến môi trường; endpoint Gemini cố định; hạn mức; session owner và step | Integration 5 câu và HTTP REST demo |
| Tài khoản | Khóa tài khoản vô hiệu session hiện có; không cho UI ứng viên đổi role | Rà soát handler |
| Bí mật | `.env`, runtime, backup và ZIP build bỏ qua Git | Kiểm tra ignore và đóng gói chỉ theme/plugin |
| Backup | SQL và CV riêng tư; restore kiểm tra vào DB tạm | Restore 24 tin/8 ứng viên/8 CV |

## Giới hạn

Không thực hiện pentest độc lập, kiểm thử tải hoặc scan dependency toàn hệ thống. Không thêm thư viện npm/composer để triển khai ứng dụng; dùng PHP/WordPress API và assets của dự án cũ. Runtime PHP có sẵn chỉ dùng local, chưa nghiệm thu triển khai public. Gemini thật chưa kiểm chứng khi không có khóa.

CV được tải về cho người có quyền, không có antivirus/document malware scanning trong bản đầu. Không tự gửi CV sang AI. Nhật ký nghiệp vụ không lưu mật khẩu, khóa API hoặc nội dung CV.
