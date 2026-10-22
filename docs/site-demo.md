# Giao diện demo EcoShop Studio

Bộ giao diện được viết sẵn và cài tự động cho bản Docker Compose mới ở cổng **18080**.

## Cài đặt – chỉ 2 lệnh trên Ubuntu

```bash
cd ~/DTC245160041-ecoshop-de3 && git pull --ff-only
bash scripts/install-ecoshop-demo.sh
```

Script:
1. Chỉ chấp nhận volume lab `de3_mysql_data` và `de3_wp_data`, không thao tác volume EcoShop gốc.
2. Sao lưu database lab vào `private-backups/de3-before-theme-*.sql` (quyền riêng tư 0600, không commit GitHub).
3. Chép theme vào `de3_wp_data:/var/www/html/wp-content/themes/ecoshop-studio`.
4. Kích hoạt theme, tạo (hoặc dùng lại) các trang **Trang chủ, Sản phẩm, Giới thiệu, Liên hệ**, tạo 6 sản phẩm mẫu và hình minh họa SVG riêng.
5. Đặt trang chủ tĩnh và kiểm tra qua Nginx ở `http://127.0.0.1:18080`.

Kết quả mong muốn:
```text
LAB_BACKUP_OK
THEME_FILES_INSTALLED
PRODUCTS_CREATED: 6
THEME_ACTIVE: ecoshop-studio
ECOSHOP_DEMO_INSTALL_OK
ECOSHOP_HOMEPAGE_VERIFIED
ECOSHOP_STUDIO_READY
```

Nếu đã có sản phẩm demo từ lần trước, `PRODUCTS_REUSED` có thể lớn hơn 0.

## Trang demo

- **Trang chủ:** `http://192.168.254.128:18080` – banner, sản phẩm nổi bật, điểm mạnh, CTA và footer.
- **Sản phẩm:** `/san-pham/` – lọc 3 nhóm và tìm kiếm tại trình duyệt.
- **Giới thiệu:** `/gioi-thieu/` – câu chuyện EcoShop và kiến trúc hệ thống.
- **Liên hệ:** `/lien-he/` – form thử nghiệm, lưu tin nhắn thành bản ghi *Liên hệ demo* trong WordPress; không gửi email ra bên ngoài.
- **Chi tiết sản phẩm:** Các card dẫn tới bài viết sản phẩm trong WordPress (kiểu nội dung `eco_product`).

**Lưu ý:** Tất cả tên, giá và hình minh họa là **dữ liệu demo**; không quảng cáo như hàng thực tế. Nếu các URL trang con 404, vào WordPress → Cài đặt → Đường dẫn tĩnh → Lưu thay đổi để làm mới rewrite rules.

## Ảnh minh chứng nên chụp

1. Trang chủ mới có banner và ít nhất 3 sản phẩm.
2. Danh mục 6 sản phẩm + bộ lọc/search hoạt động.
3. Trang chi tiết sản phẩm có giá và đặc điểm.
4. Form liên hệ demo đã lưu bản ghi, sau đó kiểm tra trong WordPress Admin → Liên hệ demo.
5. WordPress Admin → Sản phẩm EcoShop, thấy đủ 6 bài.
6. `sudo docker compose ps` và Prometheus/Grafana/Loki theo checklist tổng.

## An toàn

Dữ liệu được thêm **chỉ** vào database thử nghiệm của Docker Compose mới. Bản SQL trước khi cài lưu trong `private-backups/`; cần sao chép bản backup ra ổ khác. Không chạy `docker compose down -v` hoặc `docker volume prune`.

Việc thêm giao diện lên GitHub **không tự thay đổi máy ảo**. Cần chạy script nói trên trên Ubuntu để giao diện xuất hiện trong website cổng 18080.
