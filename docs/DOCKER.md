# Docker TechJob AI

## Trạng thái hiện tại

Docker Desktop 4.84.0 và Linux Engine 29.6.2 đang hoạt động. Website Compose: **http://localhost:8081/**. Database không xuất cổng ra host. Không reset Docker hoặc xóa image/volume của các dự án khác.

Lần kiểm tra mới đã nhận phản hồi thành công từ Docker Engine; lỗi socket `dockerInference` chỉ còn trong log cũ. Không cần xóa socket đang được backend sử dụng.

## Dùng hằng ngày

```powershell
docker compose up -d wordpress
docker compose ps
docker compose logs --tail 50 wordpress db
./scripts/test.ps1
docker compose stop
```

Chạy từ thư mục TechJob AI. Tài khoản demo Docker: `runtime/demo-accounts.txt`. Các volume `techjob-ai_database`, `techjob-ai_wordpress`, `techjob-ai_private-cvs` giữ dữ liệu sau khi dừng/khởi động lại.

`./scripts/setup.ps1 -Port 8081` dùng cho cài mới hoặc kiểm tra lại cài đặt. Script cập nhật cổng trong `.env`; chạy không có `-Port` sẽ dùng cổng đã lưu. Khóa và mật khẩu trong `.env` không đưa vào Git.

## Hai bản đang tồn tại

| Bản | Địa chỉ | Tài khoản |
|---|---|---|
| Docker | http://localhost:8081/ | runtime/demo-accounts.txt |
| Portable dự phòng | http://localhost:8080/ | runtime/private/demo-accounts.txt |

Hai bản cùng dùng source theme/plugin nhưng database và kho CV riêng. Dữ liệu chỉnh sửa ở bản portable chưa được nhập sang Docker. Dừng bản portable bằng `./scripts/stop-local.ps1` nếu chỉ muốn dùng Docker; thao tác này không xóa dữ liệu.

## Các chỉnh sửa đã kiểm chứng

- Setup nhận cổng được yêu cầu ngay cả khi `.env` đã có sẵn; chạy lại giữ cổng đã lưu.
- Setup kiểm tra WordPress sẵn sàng và báo lỗi nếu chưa sẵn sàng.
- Flush rewrite rules không yêu cầu tạo lại `.htaccess`; image Apache có sẵn rewrite chuẩn và các URL đã trả HTTP 200.
- Cài mới thành công; 52 kiểm tra policy/nghiệp vụ trong container đã qua; dữ liệu còn sau khi stop/up.
- Sao lưu Docker đã chạy được bằng `./scripts/backup.ps1`: SQL và `private.tgz` trong `backups/`. Script kiểm chứng phục hồi `verify-backup-local.php` chỉ chọn backup portable có thư mục `private/`, không nhầm với backup Docker.

Gemini vẫn cần khóa hợp lệ; sửa Docker không tự tạo hoặc cấp khóa Gemini.
