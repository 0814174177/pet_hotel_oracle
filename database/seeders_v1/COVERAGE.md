# Coverage Seeder cho migration_v1

Bản trước thiếu dữ liệu mẫu cho một số bảng hệ thống / bảng audit, nên bản này bổ sung lại đầy đủ hơn.

## Bảng được seed trong bản này

### Bảng hệ thống Laravel
1. `cache` → `LaravelSystemSeeder`
2. `cache_locks` → `LaravelSystemSeeder`
3. `jobs` → `LaravelSystemSeeder`
4. `job_batches` → `LaravelSystemSeeder`
5. `failed_jobs` → `LaravelSystemSeeder`

### Bảng auth/user
6. `users` → `UserSeeder`
7. `password_reset_tokens` → `AuthSupportSeeder`
8. `sessions` → `AuthSupportSeeder`

### Bảng nghiệp vụ chính
9. `audit_log` → trigger tự sinh + `AuditLogSeeder`
10. `branch` → `BranchRoomSeeder`
11. `type_room` → `BranchRoomSeeder`
12. `room` → `BranchRoomSeeder`
13. `category_product` → `ProductServiceSeeder`
14. `product` → `ProductServiceSeeder`
15. `category_services` → `ProductServiceSeeder`
16. `services` → `ProductServiceSeeder`
17. `service_product_detail` → `ProductServiceSeeder`
18. `employee` → `PeoplePetSeeder`
19. `customer` → `PeoplePetSeeder`
20. `pet` → `PeoplePetSeeder`
21. `coupon` → `CouponInventorySeeder`
22. `branch_inventory` → `CouponInventorySeeder`
23. `booking` → `BookingSeeder`
24. `booking_room` → `BookingSeeder`
25. `booking_room_pet` → `BookingSeeder`
26. `booking_service_pet` → `BookingSeeder`
27. `orders` → `OrderSeeder`
28. `order_details` → `OrderSeeder`
29. `booking_coupon_log` → `OrderSeeder`

## Ghi chú quan trọng

- Nếu bạn chỉ tính bảng nghiệp vụ, số bảng chính là nhóm từ `users` đến `booking_coupon_log`.
- Nếu tính luôn bảng Laravel system như `cache`, `jobs`, `sessions`, tổng số bảng sẽ nhiều hơn 21.
- Bản này cố tình seed cả bảng hệ thống để bạn không còn bị thiếu coverage khi kiểm tra trong phpMyAdmin.
- Dữ liệu vẫn được thiết kế để không vi phạm trigger database-level:
  - Pet đúng khoảng cân nặng phòng.
  - Số pet không vượt slot.
  - Order detail đúng công thức `line_total = quantity * unit_price`.
  - Order đúng công thức `grand_total = subtotal - discount_amount`.
  - Order `COMPLETED` có `paid_at`.
