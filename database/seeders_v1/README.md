# Migration v1 Seeders Complete

## Cách dùng

Copy tất cả file `.php` vào:

```text
database/seeders/
```

Sau đó chạy:

```bash
php artisan db:seed
```

Hoặc reset database và seed lại:

```bash
php artisan migrate:fresh --seed
```

## File quan trọng

- `DatabaseSeeder.php`: gọi toàn bộ seeder đúng thứ tự.
- `COVERAGE.md`: liệt kê bảng nào được seed bởi file nào.

## Lưu ý

Bản này bổ sung cả các bảng hệ thống Laravel như `cache`, `jobs`, `password_reset_tokens`, `sessions`, và `audit_log`.
Nếu trong đồ án bạn không muốn có dữ liệu mẫu ở bảng hệ thống, có thể bỏ dòng gọi:

```php
LaravelSystemSeeder::class,
AuthSupportSeeder::class,
```

trong `DatabaseSeeder.php`.
