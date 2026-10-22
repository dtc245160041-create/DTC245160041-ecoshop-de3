# EcoShop Đề 3 – Hồ sơ nộp bài và danh mục minh chứng

Cập nhật: 22/10/2026. Mục tiêu là hoàn thiện 7 tiêu chí theo rubric, không công bố kết quả chưa kiểm chứng là đạt.

## Chạy bộ nghiệm thu tự động

```bash
cd ~/DTC245160041-ecoshop-de3
git pull --ff-only
python3 scripts/final-audit.py
```

Xem `evidence-de3/YYYYMMDD-HHMMSS/bao-cao-nghiem-thu.md` (đường dẫn in ngay cuối lệnh). Không thêm bằng chứng chứa mật khẩu/.env lên GitHub.

## Bảng đối chiếu 7 tiêu chí

| Mục chấm | File cấu hình/mã nguồn | Chứng minh bắt buộc | Hiện trạng đã xác nhận |
|---|---|---|---|
| 1. GitHub (1.5đ) | `compose.yaml`, `README.md`, toàn bộ `site/`, `scripts/`, `docs/`, 3+ commit ý nghĩa | Screenshot tab Commits và cấu trúc repo | Có repo và commit thực; xem GitHub |
| 2. Website+DB+DB tool (1.5đ) | Compose `wordpress/mysql/phpmyadmin`, bản SQL đã khôi phục | Browser 18080 với dữ liệu EcoShop; phpMyAdmin 12 bảng; demo thêm/sửa/xóa sản phẩm | `SMOKE_TEST_BASIC_OK`, restore SQL thành công, website cũ ở cổng 18080 có ảnh |
| 3. Reverse proxy Nginx (1.5đ) | `nginx/default.conf` | `curl -I http://127.0.0.1:18080` hiển thị 3 security headers | Code có 5 headers; cần ảnh chạy VM |
| 4. Prometheus+Grafana (1.5đ) | `prometheus/prometheus.yml`, `grafana/` | Target UP, biểu đồ host/container/nginx/mysql của Compose mới | 6/6 Targets UP từ smoke test; dashboard cần ảnh Compose mới |
| 5. Loki+Promtail+LogQL (1.5đ) | `loki/`, `promtail/`, `docs/logql.md` | 3 LogQL có kết quả thực, screenshot Grafana Explore | Loki/Promtail Up; 3 query chưa có biên bản thực tế |
| 6. Hardening (1.5đ) | `docs/hardening.md`, `compose.yaml`, Nginx | Network internal, no-new-privileges, non-root UID, secrets 0600, headers | Các mục hiện có trong cấu hình; cần kết quả audit thực |
| 7. Tổng thể & trình bày (1.0đ) | Report DOCX và folder ảnh, README hướng dẫn | Báo cáo tối thiểu 10 trang, bìa cá nhân, demo đầy đủ thành phần | Báo cáo có nền tảng; cần ảnh bản Compose và thông tin bìa |

## Những thao tác demo trên Chrome

**WordPress:** `http://192.168.254.128:18080` → chụp trang chủ/sản phẩm. Mở `/wp-admin/`, đăng nhập, thêm sản phẩm demo (ảnh, tên, giá), đổi giá, chuyển Thùng rác và chụp từng thao tác; không cần làm trên DB gốc.

**Trang quản trị giới hạn localhost Ubuntu:** Từ Windows mở một Terminal PowerShell rồi chạy (nhập mật khẩu Ubuntu):

```powershell
ssh -N -L 18081:127.0.0.1:18081 -L 13000:127.0.0.1:13000 -L 19090:127.0.0.1:19090 hoc@192.168.254.128
```

Sau đó Chrome Windows:
- phpMyAdmin: `http://localhost:18081`
- Grafana: `http://localhost:13000`
- Prometheus Targets: `http://localhost:19090/targets`

**LogQL trong Grafana Explore (chọn Loki, Last 15 minutes):**

```logql
{job="nginx",site="ecoshop",log_type="access"}
```

```logql
{job="nginx",site="ecoshop",log_type="access"} |= "GET "
```

```logql
sum(count_over_time({job="nginx",site="ecoshop",log_type="access"}[5m]))
```

Nếu query không có dữ liệu, mở website nhiều lần để tạo access log rồi chạy lại. Nếu Loki query lỗi thì kiểm tra kết quả audit, không thay đổi volume.

**Nginx và network isolation:** Ảnh Terminal của `curl -I http://127.0.0.1:18080`, `sudo docker compose ps`, `sudo docker compose config --services`, `sudo docker network inspect ecoshopde3_database` (không gửi output `docker compose config` đầy đủ vì có mật khẩu).

## Phần báo cáo

Bản Word `Bao_cao_De3_EcoShop_Hoan_Thien.docx` cập nhật các lần khôi phục SQL/Compose và có ảnh màn hình thực đã gửi. Hãy điền họ tên, tên trường/khoa, MSSV chính xác, giáo viên hướng dẫn theo đề trước khi nộp. Chèn kết quả audit và ảnh LogQL/headers/Grafana mới vào các trang minh chứng; không sử dụng ảnh cũ để khẳng định đã chạy trên Compose mới.

## Điều kiện được xem là hoàn tất

- Báo cáo audit có **0 FAIL** ở các kiểm thử tự động.
- Tất cả 3 LogQL có kết quả và 3 screenshot thực tế.
- Header, dashboard, phpMyAdmin và chức năng CRUD sản phẩm được chụp thật.
- Tài khoản GitHub, README, commit và thông tin bìa đã chính xác.

Nếu chưa đủ, ghi rõ mục chờ nghiệm thu thay vì tự đánh dấu đạt 100%.
