# Tiêu chí nghiệm thu trên Ubuntu VM – Đề 3 EcoShop

> **Trạng thái đến 22/10/2026:** Repo đã được triển khai trên Ubuntu VM theo ảnh người dùng gửi: Docker Compose chạy, MySQL healthy, WordPress cổng 18080 hoạt động, restore SQL thành công, `SMOKE_TEST_BASIC_OK` và Prometheus 6/6 Targets UP. ChatGPT không có SSH trực tiếp nên chưa thể xác minh **3 LogQL, security headers, hardening, ảnh dashboard và sản phẩm CRUD mới**. Không tự đánh dấu 100% nếu thiếu kết quả.

## Chạy bộ triển khai riêng, không ảnh hưởng EcoShop cũ
```bash
git clone https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3.git
cd DTC245160041-ecoshop-de3
git pull --ff-only
python3 scripts/final-audit.py
```
Lưu ý: VM trước đây chỉ cài **docker-compose v1.17.1**, phải cài Compose v2 plugin trước khi dùng.

## Screenshot bắt buộc (chụp sau khi Compose chạy)
| Minh chứng | Lệnh/trang |
|---|---|
| Compose services | `sudo docker compose ps` |
| Website | `http://192.168.254.128:18080` (trong LAN riêng) |
| phpMyAdmin | `http://localhost:18081` qua SSH tunnel |
| MySQL | `sudo docker compose exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES"'` |
| Nginx security headers | `curl -I http://127.0.0.1:18080` |
| Prometheus Targets | `http://localhost:19090/targets`; UP cho node/cAdvisor/Nginx/MySQL |
| Grafana | `http://localhost:13000` + Dashboard |
| Loki/Promtail | `sudo docker compose logs --tail=40 promtail loki` + Grafana Explore |
| 3 LogQL | `docs/logql.md` |
| hardening | `docker compose config --format json`, `docker compose exec -T grafana id` và `docker compose exec -T loki id` |
| GitHub commits | `https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3/commits/main` |

**Khác biệt bắt buộc ghi rõ:** ảnh WordPress/Grafana trước đây là môi trường Docker-run, *chưa thay thế* được ảnh demo Compose. Mật khẩu App Password Gmail từng lộ trong ảnh cần đổi lại.

## An toàn dữ liệu
- KHÔNG mount các volume gốc vào Compose; stack mới dùng `de3_*`.
- KHÔNG chạy `docker compose down -v`, `docker volume prune`, `docker system prune --volumes`.
- SQL MariaDB cũ có thể cần chỉnh trước khi nhập vào MySQL 8. Chỉ restore vào database lab đã sao lưu và xác minh rỗng.
- Copy dữ liệu ra ổ vật lý khác để tránh mất toàn bộ khi máy ảo hỏng.


## Kiểm thử cuối có kết quả file và 3 LogQL thật

`python3 scripts/final-audit.py` kiểm tra live và ghi biên bản `evidence-de3/YYYYMMDD-HHMMSS/bao-cao-nghiem-thu.md`, cùng `logql-query-results.txt`, `nginx-security-headers.txt` và `prometheus-targets.txt`. Không thay đổi dữ liệu WordPress/MySQL hay volume. Các mục `REVIEW` cần screenshot trên VM. Đối chiếu `docs/final-submission.md`.
