# Hardening Checklist – EcoShop Đề 3

1. **Network isolation:** MySQL chỉ dùng `database` network nội bộ; MySQL và Loki không mở host port, giao diện quản trị mặc định bind `127.0.0.1`.
2. **Database least privilege:** WordPress dùng tài khoản `wpuser`, exporter dùng `exporter` có quyền giám sát. Không dùng MySQL root cho WordPress/phpMyAdmin.
3. **Privilege control:** `no-new-privileges:true`; `cap_drop: ALL` cho exporter, Loki, Promtail, Prometheus, Grafana. Loki UID 10001; Grafana UID 472.
4. **Strong passwords:** Tạo mật khẩu ngẫu nhiên bằng `scripts/init-env.sh`, file `.env` 0600 và được bỏ qua bởi Git. Cần thu hồi các SMTP App Password từng lộ trong ảnh.
5. **Security headers:** `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy` và CSP `frame-ancestors`; chặn dotfiles tại Nginx.
6. **Log and metrics retention:** Docker log 10 MB × 3 một số dịch vụ; Prometheus giới hạn 2 ngày/200MB. Theo dõi dung lượng.
7. **Host monitoring:** cAdvisor có quyền đọc mount host nhạy cảm; chỉ chạy trên máy ảo lab tin cậy.
8. **Transport security:** Môi trường demo dùng HTTP kèm security headers đúng tùy chọn đề bài. Nếu public internet, cần TLS + firewall.
9. **Immutable images:** Định phiên bản image để tránh cập nhật bất ngờ và quét lỗ hổng trước khi triển khai thật.
10. **WordPress:** `DISALLOW_FILE_EDIT=true`; chỉ dùng plugin tin cậy và cập nhật thường xuyên.

**Giới hạn:** Một số container chính thức (WordPress/MySQL) cần đặc quyền trong bước init. Không tuyên bố tất cả container đều non-root.

Minh chứng:
```bash
sudo docker compose config --services
sudo docker compose exec -T grafana id
sudo docker compose exec -T loki id
curl -I http://127.0.0.1:18080/
sudo docker compose ps
```

**File logging:** Nginx ghi file vào `/shared-logs` (không dùng các symlink stdout mặc định tại `/var/log/nginx`); Promtail chỉ đọc volume Nginx. Container `promtail-init` tạo quyền cho positions trước khi Promtail chạy non-root.
