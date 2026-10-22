# EcoShop – Đề 3: Website quảng bá sản phẩm

WordPress + MySQL 8 + phpMyAdmin + Nginx Reverse Proxy + Prometheus/Grafana + Loki/Promtail triển khai bằng **Docker Compose**.

**GitHub:** https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3

> **An toàn:** Đây là một stack Compose **cô lập để demo**, không được triển khai kiểm thử trực tiếp trên VM của sinh viên. Các container EcoShop đã khôi phục trước đây vẫn chạy độc lập bằng `docker run` và Nginx host. Tuyệt đối không gắn những volume gốc vào MySQL mới.

> **Promtail:** công cụ legacy đã hết hỗ trợ từ 02/03/2026; chỉ sử dụng theo yêu cầu học thuật của đề bài. Vận hành lâu dài dùng Grafana Alloy.

## 1. Kiến trúc

```text
Browser --> Nginx :18080 --> WordPress --> MySQL 8
                    +--> phpMyAdmin :18081 --> MySQL
Prometheus :19090 <-- Node Exporter, cAdvisor, Nginx Exporter, MySQL Exporter
Grafana :13000 --> Prometheus + Loki
Nginx /shared-logs --> Promtail --> Loki --> Grafana Explore (LogQL)
```

Ba Docker network: `frontend`, `database` (`internal:true`) và `monitoring`. Không publish MySQL/Loki; Prometheus, Grafana và phpMyAdmin mặc định chỉ bind localhost Ubuntu.

## 2. Chuẩn bị

Cần Ubuntu Server, Docker Engine, **Docker Compose v2 plugin** (xác nhận bằng `docker compose version`), OpenSSL, RAM >= 4 GB và dung lượng trống đủ để pull images (khuyến nghị >= 5 GB). Bản `docker-compose 1.17.1` cũ trên VM **không** dùng được với Compose này.

Kiểm tra:
```bash
docker compose version
df -h /
```

## 3. Chạy stack lab mới (không ghi đè stack cũ)

```bash
git clone https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3.git
cd DTC245160041-ecoshop-de3
bash scripts/init-env.sh
sudo docker compose config -q
bash scripts/deploy-lab.sh
sudo docker compose ps
```

**LƯU Ý:** Chỉ chạy trên VM đủ dung lượng. File `.env` chứa mật khẩu riêng và bị `.gitignore` loại khỏi Git. Không dùng `down -v` hoặc Docker prune volume.

WordPress demo ở `http://192.168.254.128:18080` (trong mạng lab). Dùng SSH tunnel để xem các giao diện quản trị localhost từ Windows:
```powershell
ssh -N -L 18081:127.0.0.1:18081 -L 13000:127.0.0.1:13000 -L 19090:127.0.0.1:19090 hoc@192.168.254.128
```

Sau đó dùng `http://localhost:18081` cho phpMyAdmin, `http://localhost:13000` cho Grafana, `http://localhost:19090/targets` cho Prometheus. Mật khẩu xem trong file `.env` trên VM, không chia sẻ công khai.

## 4. Khôi phục **bản sao** nội dung EcoShop cũ

Nếu cổng 18080 hiện trang **Welcome / Install WordPress** nghĩa là MySQL lab chưa có bảng nội dung cũ. **Không nhấn Install WordPress.** Đầu tiên kiểm tra read-only, rồi khôi phục bản SQL và wp-content trong các volume lab `de3_*`. Nếu volume nguồn thực tế khác `wordpress_up_data`, xác nhận bằng `sudo docker volume ls` trước khi chạy. Không nhập dữ liệu lại khi schema đã có bảng.

Các volume gốc cần giữ nguyên: `wordpress_db_data`, `wordpress_wp_data`, `ecoshop-db-recovery-test`, `ecoshop-db-restored`, `ecoshop-wp-restored`, `grafana-storage`, `ecoshop-loki-data`.

Bản SQL cũ (trên VM): `/home/hoc/ecoshop-wordpress-recovered.sql`. Không commit SQL backup hoặc `.env`.

```bash
# Sao lưu MariaDB cu co collation utf8mb4_uca1400_ai_ci KHONG ho tro tren MySQL8.
# Chi su dung script tao BAN SAO SQL chuyen doi, khong sua SQL goc:
bash scripts/restore-mysql8-compatible.sh /home/hoc/ecoshop-wordpress-recovered.sql wordpress_wp_data
```

Script chỉ nhập vào volume mới `de3_mysql_data` khi schema còn trống, và chỉ sao chép WordPress vào `de3_wp_data` khi chưa có file. **Không** gắn trực tiếp volume MariaDB cũ vào MySQL 8. Nếu chuyển dữ liệu MariaDB → MySQL gặp lỗi collation/charset/SQL thì dừng, không xóa dữ liệu gốc.

Nếu `wp-config.php` trong bản sao còn kết nối DB cũ, chỉ sửa bản sao để dùng `WORDPRESS_DB_*`. Kiểm tra `home` / `siteurl` của WordPress vì đường dẫn có thể vẫn trỏ về port 80, trong khi lab dùng 18080.

## 5. Tiêu chí chấm và minh chứng

| Hạng mục | Cấu hình | Ảnh/kiểm tra cần nộp |
|---|---|---|
| GitHub/3 commit | Repo này, README và `git log` | GitHub commits |
| WordPress/MySQL/phpMyAdmin | Compose app+DB | Trang chủ, MySQL `SHOW TABLES`, phpMyAdmin |
| Nginx reverse proxy | `nginx/default.conf` | `curl -I http://127.0.0.1:18080` thấy security headers |
| Prometheus/Grafana | Exporters + Dashboard | Targets UP, CPU/RAM/Disk/Container/DB/Web |
| Loki/Promtail/LogQL | `loki/`, `promtail/` | 3 câu LogQL trong `docs/logql.md` |
| Hardening | `docs/hardening.md` | Network, non-root, credentials, headers |
| Báo cáo | File DOCX riêng | >= 10 trang, minh chứng thực tế |

Dashboard sẽ được provisioning ở Grafana dưới tên **EcoShop - Web, DB, Containers & Nginx**. Đợi 30–60 giây để exporters/targets phát dữ liệu.

## 6. Lệnh demo nhanh

```bash
sudo docker compose ps
bash scripts/check.sh
curl -I http://127.0.0.1:18080
sudo docker compose logs --tail=40 nginx mysql-exporter prometheus loki promtail
sudo docker compose exec -T grafana id
sudo docker compose exec -T loki id
```

Tạo Access Log:
```bash
curl -s http://127.0.0.1:18080/ >/dev/null
curl -s http://127.0.0.1:18080/notfound-demo >/dev/null
```

Trong Grafana Explore chạy Q1–Q4 ở `docs/logql.md`. Chụp ảnh trang web, DB tool, headers Nginx, Dashboard, Targets, LogQL và hardening.

## 7. Backup và bảo mật

```bash
bash scripts/backup.sh
```

Backup ở `private-backups/` (bị Git ignore); sao chép ra Windows hoặc ổ khác. **Không** chạy `docker compose down -v`, `docker volume prune`, `docker system prune --volumes`. Phải thay các App Password từng xuất hiện trong ảnh cũ.

Repo chứa cấu hình và source dành cho môi trường lab. **Chưa chứng minh stack mới chạy được trên VM**; cần kiểm tra Compose v2, dịch vụ, dữ liệu và LogQL thật khi triển khai. Các ảnh trong báo cáo trước đây thuộc hệ thống Docker-run đã vận hành; không được coi là minh chứng tự động cho stack Compose mới.


## 8. Lệnh chạy dễ nhất trên Ubuntu (không can thiệp stack cũ)

```bash
git clone https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3.git
cd DTC245160041-ecoshop-de3
bash scripts/install-compose-plugin.sh
bash scripts/init-env.sh
bash scripts/deploy-lab.sh
bash scripts/smoke-test.sh
```

Khi cần mang dữ liệu **cũ** vào **volume lab mới**, dùng **sau** khi đã đọc cảnh báo:
```bash
bash scripts/restore-lab.sh /home/hoc/ecoshop-wordpress-recovered.sql wordpress_up_data
```

Sau import, script chỉ sửa `home`/`siteurl` trong **MySQL lab**, không đổi SQL nguồn hoặc MariaDB cũ. Nếu tên volume gốc khác, kiểm tra `sudo docker volume ls` và chọn volume chứa `wp-content`.

**Báo cáo thực hành:** `docs/acceptance-checklist.md` mô tả chính xác ảnh minh chứng cần chụp; báo cáo DOCX 15 trang được bàn giao riêng. Lịch sử có ít nhất ba commit theo rubric và một số commit tăng cường chất lượng.


## 9. Lỗi khôi phục MariaDB → MySQL8 `Unknown collation utf8mb4_uca1400_ai_ci`

MySQL 8.0 không hỗ trợ collation UCA1400 của MariaDB. Lệnh `restore-mysql8-compatible.sh` sẽ **kiểm tra database lab hoàn toàn rỗng** rồi tạo file SQL mới trong `$HOME`, có quyền 0600 và thay `utf8mb4_uca1400_ai_ci` bằng `utf8mb4_unicode_ci`. File SQL gốc, database cũ và mọi Docker volume không bị xóa. Collation thay thế có quy tắc so sánh khác; hãy kiểm chứng nội dung bài viết/tài khoản sau khi khôi phục.

Nếu trước đó đã import một phần, script sẽ in `STOP_PARTIAL_IMPORT` và **không** xóa các bảng. Khi đó kiểm tra danh sách bảng rồi lên kế hoạch khôi phục trong một schema thử nghiệm khác. Không chạy lại `restore-lab.sh` với file SQL cũ và không nhấn Install WordPress khi vẫn ở trang Welcome.

## 10. Cài giao diện EcoShop Studio tự động (không cần thao tác WordPress Admin)

Sau khi khôi phục WordPress cũ và chạy Compose thành công, chỉ cần:

```bash
cd ~/DTC245160041-ecoshop-de3
git pull --ff-only
bash scripts/install-ecoshop-demo.sh
```

Script sao lưu MySQL lab, đưa theme mới vào **de3_wp_data**, tạo trang chủ, danh mục, giới thiệu, liên hệ và **6 sản phẩm mẫu**. Hình minh họa sản phẩm dạng SVG nằm trong GitHub, không cần download ảnh ngoài. Các container và volume EcoShop gốc không bị chỉnh sửa. Xem [hướng dẫn giao diện demo](docs/site-demo.md).

Trang demo: `http://192.168.254.128:18080`. Nếu cần xem bản mới, nhấn Ctrl+F5. Kết quả mong đợi `ECOSHOP_STUDIO_READY`.


## 11. Quản lý sản phẩm WordPress – thêm / sửa / xóa

Đã tích hợp trực tiếp trong theme EcoShop Studio, không cần plugin ngoài:

- WordPress Admin → **Sản phẩm EcoShop** → **Thêm sản phẩm**; nhập tên, giá, danh mục, điểm nổi bật, mô tả, ảnh đại diện trong Thư viện Media rồi nhấn **Đăng**.
- **Tất cả sản phẩm**: sửa thông tin, thay ảnh, chuyển Thùng rác hoặc khôi phục. Sản phẩm mới hiển thị tự động trong trang **/san-pham/**; trang chủ hiển thị 6 bài sản phẩm mới nhất.
- Không có chức năng mua hàng thật; đây là website quảng bá sản phẩm minh họa.

Áp dụng thay đổi trên bản **Compose mới** khi theme đã được cài:

```bash
cd ~/DTC245160041-ecoshop-de3
git pull --ff-only
bash scripts/update-ecoshop-design.sh
```

Script kiểm tra PHP, sao lưu theme trước khi chép; không chạy câu lệnh SQL hay thay đổi database. Xem [hướng dẫn quản lý sản phẩm](docs/product-management.md).
