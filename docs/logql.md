# LogQL – minh chứng tập trung Nginx → Promtail → Loki

Mở **Grafana → Explore → Loki** và chọn `Last 30 minutes`. Tải lại website để phát sinh access logs.

## Q1 – Access log
```logql
{job="nginx",site="ecoshop",log_type="access"}
```

## Q2 – HTTP 404
```logql
{job="nginx",site="ecoshop",log_type="access"} |~ "HTTP/[0-9.]+\\\" 404 "
```

## Q3 – Số lượt HTTP theo trạng thái trong 5 phút
```logql
sum by (status) (count_over_time({job="nginx",site="ecoshop",log_type="access"} | regexp `HTTP/[0-9.]+" (?P<status>[0-9]{3})` [5m]))
```

## Q4 – Error log
```logql
{job="nginx",site="ecoshop",log_type="error"}
```

LogQL chạy trong Grafana, không phải trong Ubuntu Terminal.
