# EcoShop – Giỏ hàng và thanh toán mô phỏng (22/10/2026)

## Đã bổ sung

- Nút **Thêm vào giỏ hàng** trên mỗi card sản phẩm và trang chi tiết.
- Giỏ hàng theo trình duyệt, lưu phía server bằng WordPress transients, cookie `HttpOnly` và `SameSite=Lax`; giới hạn 15 loại hàng, 20 đơn vị mỗi loại.
- Trang **Giỏ hàng**: xem ảnh, giá, tổng tiền, thay số lượng và xóa từng mặt hàng.
- Trang **Thanh toán demo**: họ tên, số điện thoại, email, địa chỉ và ghi chú **giả lập**; chọn sẵn **Thanh toán khi nhận hàng (COD – mô phỏng)**.
- Nút xác nhận đơn: kiểm tra nonce, dữ liệu đầu vào; lấy lại giá và tình trạng sản phẩm từ MySQL server; lưu đơn `eco_order` riêng tư trong WordPress.
- Trang **Đặt hàng thành công** trả mã đơn và tổng tiền (chỉ hiện cho URL có mã xác nhận hợp lệ).
- **WordPress Admin → Đơn hàng demo**: quản trị viên xem sản phẩm, số lượng, tổng và thông tin giả lập.

**Không có cổng VNPAY/MoMo/PayPal, thanh toán thẻ, gửi tiền, giao hàng hay quản lý kho.** Phần thanh toán là mô phỏng phục vụ demo trên VM; tuyệt đối không nhập dữ liệu cá nhân thật.

## Cài đặt – trên Ubuntu VM đã có EcoShop và 7 sản phẩm

Chỉ cần chạy từ thư mục repo đã clone:

```bash
cd ~/DTC245160041-ecoshop-de3
git pull --ff-only
bash scripts/install-demo-commerce.sh
```

Script kiểm tra volume đúng `de3_mysql_data` và `de3_wp_data`, sao lưu SQL lab trong `private-backups/`, chạy linter PHP cho toàn theme, lưu một bản sao theme đang chạy, cập nhật template và tạo ba trang WordPress **không trùng lặp**. Không chạm volume WordPress/MariaDB cũ, không dừng dịch vụ, không xóa dữ liệu sản phẩm.

Kết quả mong đợi:

```text
MYSQL_LAB_BACKUP_OK
THEME_PHP_LINT_OK
ECOSHOP_THEME_UPDATED
DESIGN_UPDATE_OK
PAGE_CREATED: gio-hang ...
PAGE_CREATED: thanh-toan ...
PAGE_CREATED: dat-hang-thanh-cong ...
CHECKOUT_DEMO_ENABLED
ECOSHOP_SHOP_PAGE: /gio-hang/ HTTP 200
ECOSHOP_SHOP_PAGE: /thanh-toan/ HTTP 200
ECOSHOP_SHOP_PAGE: /dat-hang-thanh-cong/ HTTP 200
ECOSHOP_CART_CHECKOUT_READY
```

Khi đã cài từ lần trước, kết quả có thể là `PAGE_REUSED` thay `PAGE_CREATED`.

## Kiểm tra toàn bộ chức năng

1. Mở `http://192.168.254.128:18080/san-pham/`; chọn một sản phẩm và nhấn **Thêm vào giỏ hàng**.
2. Xem `http://192.168.254.128:18080/gio-hang/`; xác minh tên sản phẩm, giá; tăng số lượng lên 2, nhấn **Cập nhật số lượng** để thấy tổng mới; nhấn **Xóa khỏi giỏ** nếu muốn thử bỏ hàng.
3. Chọn **Tiếp tục thanh toán thử**; mở `http://192.168.254.128:18080/thanh-toan/`.
4. Nhập **thông tin giả**: *Nguyễn Văn A*; điện thoại *0900000000*; `demo@example.com`; *Số 123 Đường Mẫu, Việt Nam*. Tích xác nhận đơn demo và nhấn **Xác nhận đơn hàng demo**.
5. Xác nhận trang thành công có mã đơn và tổng tiền, giỏ hàng đã trống.
6. Trong WordPress Admin `/wp-admin/` → **Đơn hàng demo**, tìm đơn vừa tạo, xem chi tiết và tổng tiền. Chụp ảnh màn hình.
7. Thay đổi giá sản phẩm trong Admin rồi thêm vào giỏ: tổng tiền giỏ sử dụng **giá mới trong MySQL**, không tin số tiền gửi từ phía trình duyệt.

Lệnh xác minh nhanh:

```bash
sudo docker compose ps
curl -sSI -H 'Host: 192.168.254.128:18080' http://127.0.0.1:18080/gio-hang/
curl -sSI -H 'Host: 192.168.254.128:18080' http://127.0.0.1:18080/thanh-toan/
```

## Đánh giá và giới hạn bảo mật

- Giá được tính lại server-side từ `eco_price` của `eco_product` đã xuất bản; không có hidden price form field.
- `admin-post.php` có WordPress nonce + chuyển hướng POST/Redirect/GET.
- Đơn lưu private post type, chỉ người có quyền quản trị site mới thấy trong dashboard; mã xác nhận trên URL được băm SHA256 trong DB.
- Hạn mức giỏ hàng và khóa đơn ngắn hạn giúp hạn chế lỗi bấm trùng.
- Dữ liệu chỉ phù hợp môi trường lab. Chưa có phòng chống gian lận, chống bot, quản lý tồn kho, thuế, xác nhận vận chuyển hoặc phương thức thanh toán trực tuyến thật. Nếu triển khai cửa hàng thật, cần chuyển sang WooCommerce/giải pháp thương mại điện tử đã kiểm toán và tích hợp cổng thanh toán theo quy định.

**Chú ý:** Một lần chạy lệnh không chứng minh đã thanh toán thử thành công. Cần kiểm thử thao tác qua Chrome và chụp ảnh trang giỏ, checkout, xác nhận và trang quản trị đơn hàng thực tế.
