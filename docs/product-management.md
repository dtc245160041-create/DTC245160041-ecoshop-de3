# Quản lý sản phẩm EcoShop (WordPress)

Chức năng đã được viết trong theme `site/ecoshop-studio`. Không cần cài plugin bán hàng, không có thanh toán thật.

## Áp dụng trên máy ảo Ubuntu

Nếu đã cài giao diện EcoShop Studio, thực hiện:

```bash
cd ~/DTC245160041-ecoshop-de3
git pull --ff-only
bash scripts/update-ecoshop-design.sh
```

Script **sao lưu giao diện đang hoạt động**, kiểm tra cú pháp PHP và chỉ cập nhật theme trong volume `de3_wp_data`. Không thêm/xóa dữ liệu WordPress hay thay đổi MySQL. Nếu đang dùng theme WordPress mặc định hoặc chưa cài EcoShop Studio, chạy `bash scripts/install-ecoshop-demo.sh` sau khi cập nhật mã nguồn.

## Sử dụng trong WordPress Admin

Mở `http://192.168.254.128:18080/wp-admin/`, đăng nhập tài khoản quản trị WordPress hiện có.

Vào **Sản phẩm EcoShop → Thêm sản phẩm** và điền:

- **Tên sản phẩm** ở trên cùng, ví dụ: *Đèn bàn học Pro*.
- **Mô tả chi tiết** trong khung soạn thảo; **Tóm tắt** cho mô tả ngắn (nếu có).
- **Giá bán (VNĐ)** và **Giá gốc (VNĐ)** trong hộp **Thông tin sản phẩm**.
- **Danh mục**: Góc làm việc, Công nghệ hoặc Đời sống.
- **Nhãn** (không bắt buộc) và **Điểm nổi bật** (mỗi dòng một ý).
- **Ảnh đại diện** ở cột phải → *Đặt ảnh đại diện* → tải ảnh hoặc chọn từ Thư viện. Ảnh sẽ tự xuất hiện ở card sản phẩm và trang chi tiết. Nếu chưa chọn ảnh, dùng hình minh họa mặc định.

Nhấn **Đăng**. Sản phẩm mới xuất hiện trên `http://192.168.254.128:18080/san-pham/`; 6 sản phẩm mới nhất hiển thị tại trang chủ. Có thể dùng tìm kiếm và lọc theo nhóm.

## Sửa và xóa

Trong **Sản phẩm EcoShop → Tất cả sản phẩm**:

- Nhấn **Sửa** dưới tên sản phẩm để cập nhật giá, ảnh, mô tả hoặc danh mục.
- Nhấn **Thùng rác** để ẩn/xóa sản phẩm khỏi danh sách công khai. Có thể khôi phục trong Thùng rác.
- Nhấn **Xem** để xem trang sản phẩm.
- **Lưu bản nháp** để chuẩn bị sản phẩm mà không công khai.

Tất cả thao tác quản lý sử dụng tài khoản WordPress có quyền quản lý bài viết tương ứng và nonce bảo vệ khi lưu. Khách truy cập không có nút thêm/sửa/xóa.

## Demo nộp bài

1. Trước khi thêm: chụp danh sách 6 sản phẩm trong WordPress Admin.
2. Tạo sản phẩm mới, ví dụ *Giá đỡ laptop gấp gọn*, ảnh mẫu, giá `199000`, danh mục Công nghệ và nhấn Đăng.
3. Mở trang danh mục `/san-pham/` và tìm tên vừa thêm.
4. Vào trang chi tiết, kiểm tra ảnh, giá và mô tả. Quay lại Admin, sửa giá thành `189000`, lưu và tải lại trang để kiểm tra cập nhật.
5. Đưa sản phẩm thử vào Thùng rác; nó biến mất khỏi danh mục công khai.
6. Chụp ảnh WordPress Admin, phpMyAdmin và Grafana/Prometheus để chứng minh luồng website → database → giám sát.

**An toàn:** Không dùng `docker compose down -v`, không chạy lệnh xóa volume. Database gốc của website trước đây không bị theme mới chỉnh sửa. Nếu trang con báo 404, vào **Cài đặt → Đường dẫn tĩnh → Lưu thay đổi** để làm mới rewrite rules. Đây là website quảng bá sản phẩm; giá chỉ mang tính minh họa.
