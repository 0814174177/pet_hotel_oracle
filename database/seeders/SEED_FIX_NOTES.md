# Seed Fix Notes

Bản này đã chỉnh các điểm chưa hợp lý trong bộ seed trước đó.

## Các lỗi/điểm lệch đã sửa

- Giảm tỷ lệ booking hủy từ khoảng 10% xuống khoảng 4%, rải đều theo chi nhánh để không có chi nhánh nào bị hủy cao bất thường.
- Tăng tỷ lệ booking thành công: phần lớn booking là `COMPLETED` hoặc `CHECKED_OUT`; cuối tháng 05/2026 có thêm một nhóm nhỏ `PENDING`, `CONFIRMED`, `CHECKED_IN` để demo flow đặt phòng.
- Booking hủy không còn tạo doanh thu ảo: `subtotal = 0`, `grand_total = 0`. Payment thất bại vẫn lưu số tiền attempt dương theo constraint Oracle và có `payment.status = FAILED`; báo cáo doanh thu phải loại payment thất bại.
- Booking chỉ chọn customer đã được tạo trước thời điểm `booking.created_at`, tránh lỗi logic khách chưa tồn tại đã đặt phòng.
- Ngày tạo customer được rải đều theo từng chi nhánh trong toàn kỳ 11/2025 -> 05/2026, không còn gom chi nhánh 1 trước rồi chi nhánh 2/3/4 sau.
- Có cohort customer nền từ 11/2025 và `BookingSeeder` dừng ngay nếu không có customer được tạo trước booking; không còn fallback sang customer tương lai.
- Thêm dữ liệu một số trường hợp 2 pet cùng chủ ở chung phòng để tận dụng `type_room.max_slot = 2`.
- Thêm dịch vụ cho mèo: `Tắm mèo`, `Grooming mèo` và định mức vật tư tương ứng.
- `AuthSupportSeeder` sửa email/user_id demo cho khớp customer thật: `customer1001@pethotel.test`, `user_id = 1001`.
- `LaravelSystemSeeder` và `AuthSupportSeeder` được gọi trong `DatabaseSeeder`/`DemoBookingFlowSeeder`, nhưng có kiểm tra `Schema::hasTable()` để không lỗi nếu project chưa có bảng hệ thống.
- `AuditLogSeeder` không insert ID cố định nếu bảng audit đã có dữ liệu, tránh trùng khóa khi database có trigger audit thật.
- `OracleSequenceSeeder` đồng bộ cả sequence của `jobs` và `failed_jobs`.
- `CleanupSeeder` chỉ cho phép chạy trong môi trường `local` hoặc `testing`.
- User của bác sĩ thú y/cleaner được chuyển `is_active = 0` để không vô tình đăng nhập bằng role `MANAGER`; employee vẫn active để phục vụ dữ liệu nhân sự.
- Tài liệu demo và SQL demo đã được rút gọn/sửa lại để khớp email, tên phòng và trạng thái hiện tại.

## Tài khoản demo chính

Mật khẩu chung: `password123`

| Email | Role | Ghi chú |
|---|---|---|
| `admin.demo@pethotel.test` | ADMIN | Quản trị tổng |
| `manager.govap@pethotel.test` | MANAGER | Quản lý Gò Vấp |
| `manager.q1@pethotel.test` | MANAGER | Quản lý Quận 1 |
| `manager.q7@pethotel.test` | MANAGER | Quản lý Quận 7 |
| `manager.thuduc@pethotel.test` | MANAGER | Quản lý Thủ Đức |
| `groomer.govap@pethotel.test` | GROOMER | Groomer Gò Vấp |
| `groomer.q1@pethotel.test` | GROOMER | Groomer Quận 1 |
| `groomer.q7@pethotel.test` | GROOMER | Groomer Quận 7 |
| `groomer.thuduc@pethotel.test` | GROOMER | Groomer Thủ Đức |
| `receptionist.govap@pethotel.test` | RECEPTIONIST | Lễ tân Gò Vấp |
| `customer1001@pethotel.test` | CUSTOMER | Khách demo đầu tiên |
| `customer1002@pethotel.test` | CUSTOMER | Khách demo thứ hai |

## Query kiểm tra tỷ lệ hủy theo chi nhánh

```sql
SELECT b.branch_name,
       COUNT(*) AS total_booking,
       SUM(CASE WHEN bk.status = 'CANCELLED' THEN 1 ELSE 0 END) AS cancelled_booking,
       ROUND(100 * SUM(CASE WHEN bk.status = 'CANCELLED' THEN 1 ELSE 0 END) / COUNT(*), 2) AS cancel_rate_percent
FROM booking bk
JOIN branch b ON b.branch_id = bk.branch_id
GROUP BY b.branch_name
ORDER BY b.branch_name;
```

Kỳ vọng: tỷ lệ hủy quanh mức 4%, không còn chi nhánh nào lên khoảng 20%.
