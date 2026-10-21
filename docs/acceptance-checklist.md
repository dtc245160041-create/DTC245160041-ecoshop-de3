# Tiêu chí nghiệm thu trên Ubuntu VM – Đề 3 EcoShop

> **Trạng thái:** Bộ mã nguồn đã đẩy lên GitHub. Chưa có kết nối SSH tới máy ảo từ ChatGPT, vì thế chưa thể xác nhận stack Compose mới đã được triển khai, không được đánh dấu đạt các kiểm thử live khi chưa chạy.

## Chạy bộ triển khai riêng, không ảnh hưởng EcoShop cũ
```bash
git clone https://github.com/dtc245160041-create/DTC245160041-ecoshop-de3.git
cd DTC245160041-ecoshop-de3
bash scripts/init-env.sh
bash scripts/preflight.sh
bash scripts/deploy-lab.sh
bash scripts/smoke-test.sh
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
