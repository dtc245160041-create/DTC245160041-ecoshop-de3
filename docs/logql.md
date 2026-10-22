# LogQL – 3 câu truy vấn bắt buộc cho EcoShop Đề 3

**Nguồn dữ liệu:** Nginx ghi access/error log vào volume `de3_nginx_logs`; Promtail đọc log với nhãn `job="nginx", site="ecoshop"` và đẩy đến Loki. Grafana kết nối datasource Loki qua Docker monitoring network.

Để có dữ liệu mới, trước tiên truy cập trang chủ EcoShop cổng 18080, mở mục Sản phẩm và một URL không tồn tại. Trong Grafana → **Explore** → chọn datasource **Loki** → **Last 15 minutes** → **Run query**.

## Q1 – Xem toàn bộ lượt truy cập Nginx
```logql
{job="nginx",site="ecoshop",log_type="access"}
```
Kỳ vọng: nhiều dòng nhật ký truy cập, có request GET đến trang chủ hoặc sản phẩm.

## Q2 – Lọc các request GET
```logql
{job="nginx",site="ecoshop",log_type="access"} |= "GET "
```
Kỳ vọng: hiển thị riêng những dòng chứa `GET`. Đây là bộ lọc LogQL pipe-line, không phải query SQL.

## Q3 – Số lượt request trong 5 phút
```logql
sum(count_over_time({job="nginx",site="ecoshop",log_type="access"}[5m]))
```
Kỳ vọng: biểu đồ/tổng số request lớn hơn 0 sau khi tạo traffic. Chụp ảnh biểu đồ thời gian và query.

## Bổ sung – lỗi HTTP 404 (tùy chọn)
```logql
{job="nginx",site="ecoshop",log_type="access"} |= " 404 "
```
Mở một đường dẫn không tồn tại để tạo HTTP 404 trước khi chạy.

## Bổ sung – Nginx error.log (tùy chọn)
```logql
{job="nginx",site="ecoshop",log_type="error"}
```
Nếu không có lỗi thực tế, query này có thể không có dòng. Đừng tạo lỗi hệ thống chỉ để lấy hình minh chứng.

## Tự kiểm tra trực tiếp Loki API từ Ubuntu

```bash
python3 scripts/final-audit.py
```

Script sinh request HTTP bình thường rồi chạy Q1–Q3 qua Loki API nội bộ; lưu kết quả dạng đã lược bỏ dữ liệu nhạy cảm ở `evidence-de3/.../logql-query-results.txt`. Script không mở port Loki ra ngoài, không xóa dữ liệu và không chỉnh sửa cấu hình. Kết quả API không thay thế cho **3 ảnh Grafana Explore**, vì đó là minh chứng trình bày bài học theo yêu cầu giảng viên.

**Chú ý:** Promtail là thành phần legacy dùng để đáp ứng đúng yêu cầu Đề 3. Triển khai sản phẩm thật nên chuyển sang Grafana Alloy thay thế.
