# Pet Hotel Seeders

Bộ seed này dùng cho demo hệ thống Pet Hotel trên Laravel/Oracle.

## Cách chạy

> `database/seeders_v2` là thư mục staging. Trước khi chạy bằng Artisan, cần đồng bộ các file `.php` sang `database/seeders/`. Không chạy `CleanupSeeder` ngoài môi trường `local` hoặc `testing`.

```bash
php artisan migrate:fresh
php artisan db:seed
```

Hoặc chạy riêng bộ demo flow:

```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoBookingFlowSeeder
```

## Thành phần dữ liệu

- 4 chi nhánh: Quận 1, Thủ Đức, Quận 7, Gò Vấp.
- 3 loại phòng: `Phòng nhỏ`, `Phòng vừa`, `Phòng lớn`.
- 120 phòng, trong đó mỗi chi nhánh có phòng chó/phòng mèo theo kích cỡ.
- 18 user nhân sự/hệ thống và 320 customer user.
- Khoảng 448 pet, gồm chó, mèo và một số pet nhỏ khác.
- Dịch vụ chó/mèo/cơ bản: tắm, grooming, kiểm tra sức khỏe, cắt móng.
- Coupon, tồn kho, booking, order, order detail, payment và audit log mẫu.
- Booking trải từ 12/2025 đến 05/2026, phù hợp dashboard tháng/quý và demo nghiệp vụ.

## Tài khoản demo

Mật khẩu chung: `password123`

| Email | Role |
|---|---|
| `admin.demo@pethotel.test` | ADMIN |
| `manager.govap@pethotel.test` | MANAGER |
| `manager.q1@pethotel.test` | MANAGER |
| `manager.q7@pethotel.test` | MANAGER |
| `manager.thuduc@pethotel.test` | MANAGER |
| `inventory.demo@pethotel.test` | MANAGER |
| `groomer.govap@pethotel.test` | GROOMER |
| `groomer.q1@pethotel.test` | GROOMER |
| `groomer.q7@pethotel.test` | GROOMER |
| `groomer.thuduc@pethotel.test` | GROOMER |
| `receptionist.govap@pethotel.test` | RECEPTIONIST |
| `receptionist.q1@pethotel.test` | RECEPTIONIST |
| `receptionist.q7@pethotel.test` | RECEPTIONIST |
| `receptionist.thuduc@pethotel.test` | RECEPTIONIST |
| `customer1001@pethotel.test` | CUSTOMER |
| `customer1002@pethotel.test` | CUSTOMER |

Các user bác sĩ thú y/cleaner được giữ để liên kết hồ sơ employee nhưng đặt `is_active = 0`, tránh đăng nhập nhầm bằng quyền quản lý.

## Ghi chú logic đã chỉnh

- Tỷ lệ booking hủy khoảng 4%, không còn chi nhánh nào có tỷ lệ hủy bất thường cao.
- Booking thành công chiếm đa số: chủ yếu `COMPLETED`/`CHECKED_OUT`.
- Có thêm một số booking `PENDING`, `CONFIRMED`, `CHECKED_IN` ở cuối tháng 05/2026 để demo luồng nghiệp vụ.
- Booking hủy không tạo doanh thu ảo.
- Customer được tạo từ 11/2025 và luôn được chọn trước booking. Seeder sẽ dừng ngay nếu dữ liệu đầu vào vi phạm điều kiện này.
- Có dữ liệu multi-pet room cho loại phòng `max_slot = 2`.
- Dịch vụ có thêm case cho mèo.
- Sequence Oracle của `jobs` và `failed_jobs` cũng được đồng bộ sau khi seed ID thủ công.

Xem thêm `SEED_FIX_NOTES.md` và `# DEMO SQL Queries.txt` để kiểm tra dữ liệu sau khi seed.
