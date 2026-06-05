<p align="center">
  <a href="https://www.uit.edu.vn/" title="Trường Đại học Công nghệ Thông tin">
    <img src="https://i.imgur.com/WmMnSRt.png" alt="Trường Đại học Công nghệ Thông tin | University of Information Technology" width="750">
  </a>
</p>

<h1 align="center">ĐỒ ÁN</h1>
<h2 align="center">Môn học: Phát triển ứng dụng Web</h2>
<h3 align="center">Mã lớp: IS207.Q23</h3>
<h3 align="center">Đề tài: Xây dựng Website Hệ thống quản lý chuỗi khách sạn thú cưng</h3>
<h3 align="center">Nhóm thực hiện: Nhóm sinh viên thực hiện</h3>
<h3 align="center">GVHD: ThS. Trình Trọng Tín</h3>

---

## NHÓM THỰC HIỆN

| STT | MSSV     | Họ và tên         |
| --- | -------- | ----------------- |
| 1   | 22520419 | Nguyễn Thanh Hiển |
| 2   | 24521045 | Trần Đức Mạnh     |
| 3   | 24521060 | Lê Quang Minh     |
| 4   | 24521078 | Nguyễn Trọng Minh |
| 5   | 24522027 | Thành Công Vinh   |
| 6   | 24590003 | Trần Quốc Danh    |

---

# Pet Hotel - Website quản lý chuỗi khách sạn thú cưng

**Pet Hotel** là website quản lý chuỗi khách sạn thú cưng, hỗ trợ các nghiệp vụ đặt phòng, chăm sóc thú cưng, quản lý dịch vụ, thanh toán và theo dõi dữ liệu vận hành theo từng chi nhánh.

Hệ thống hỗ trợ khách hàng xem thông tin dịch vụ, chi nhánh, loại phòng, quản lý hồ sơ thú cưng, tạo booking, áp dụng mã giảm giá và thanh toán. Bên cạnh đó, các vai trò Manager và CEO có thể theo dõi dashboard, doanh thu, dịch vụ, khuyến mãi, nhân viên, vật tư và tồn kho.

---

## 1. Công nghệ sử dụng

| Nhóm công nghệ     | Nội dung                                                                                      |
| ------------------ | --------------------------------------------------------------------------------------------- |
| Frontend           | HTML5, CSS3, JavaScript, Laravel Blade, Vite, TailwindCSS, Chart.js, Leaflet, Font Awesome    |
| Backend            | PHP 8.2, Laravel 12, Eloquent ORM, Middleware, FormRequest, Repository pattern, Service layer |
| Database           | Oracle Database, package `yajra/laravel-oci8`, Laravel migration và seeder                    |
| Công cụ phát triển | XAMPP, Composer, Node.js, npm, Laravel Artisan, Git, GitHub                                   |

---

## 2. Hướng dẫn cài đặt

### 2.1. Phần mềm cần cài đặt

Trước khi chạy project, cần chuẩn bị các phần mềm sau:

| Phần mềm              | Mục đích                            |
| --------------------- | ----------------------------------- |
| PHP 8.2 trở lên       | Chạy Laravel backend                |
| Composer              | Cài đặt thư viện PHP                |
| Node.js và npm        | Cài đặt và build frontend bằng Vite |
| Oracle Database       | Lưu trữ dữ liệu chính của hệ thống  |
| Oracle Instant Client | Cho phép PHP kết nối Oracle         |
| PHP extension `oci8`  | Extension Oracle cho PHP            |
| Git                   | Clone và quản lý mã nguồn           |

Kiểm tra nhanh các công cụ trong Terminal:

```bash
php -v
composer -V
node -v
npm -v
git --version
```

Kiểm tra PHP đã nhận extension `oci8`:

```bash
php -m
php --ri oci8
```

Nếu kết quả có hiển thị `oci8`, PHP đã có thể kết nối Oracle.

### 2.2. Clone project và cài dependency

Clone source code từ GitHub và di chuyển vào thư mục project:

```bash
git clone <repo-url>
cd pet-hotel
```

Nếu project đã có sẵn trên máy, mở Terminal tại thư mục chứa source code. Ví dụ:

```bash
cd D:\zInstall\PHP\htdocs\pet-hotel-v2\pet_hotel_oracle
```

Cài đặt thư viện PHP và frontend:

```bash
composer install
npm install
npm run build
```

Tạo file môi trường `.env` từ file mẫu:

```bash
cp .env.example .env
```

Trên Windows PowerShell có thể dùng:

```powershell
Copy-Item .env.example .env
```

Tạo application key cho Laravel:

```bash
php artisan key:generate
```

### 2.3. Cấu hình Oracle trong file `.env`

Mở file `.env` và cập nhật thông tin kết nối Oracle theo máy đang chạy project.

Ví dụ cấu hình:

```env
DB_CONNECTION=oracle
DB_HOST=127.0.0.1
DB_PORT=1521
DB_DATABASE=FREEPDB1
DB_SERVICE_NAME=FREEPDB1
DB_TNS=
DB_USERNAME=PET_HOTEL
DB_PASSWORD=your_password
DB_CHARSET=AL32UTF8
DB_SERVER_VERSION=11g
ORA_MAX_NAME_LEN=30
```

Ý nghĩa các biến chính:

| Biến cấu hình      | Ý nghĩa                                                       |
| ------------------ | ------------------------------------------------------------- |
| `DB_CONNECTION`    | Loại database, dùng `oracle`                                  |
| `DB_HOST`          | Địa chỉ Oracle Database, thường là `127.0.0.1` khi chạy local |
| `DB_PORT`          | Cổng Oracle, thường là `1521`                                 |
| `DB_DATABASE`      | Tên service/PDB Oracle, ví dụ `FREEPDB1`                      |
| `DB_SERVICE_NAME`  | Tên service Oracle, thường giống `DB_DATABASE`                |
| `DB_USERNAME`      | Oracle user/schema dùng cho project                           |
| `DB_PASSWORD`      | Mật khẩu của Oracle user/schema                               |
| `DB_CHARSET`       | Bộ mã ký tự, nên dùng `AL32UTF8` để hỗ trợ tiếng Việt         |
| `ORA_MAX_NAME_LEN` | Giới hạn độ dài tên object Oracle, nên để `30`                |

Không commit file `.env` thật hoặc mật khẩu Oracle thật lên GitHub.

### 2.4. Tạo Oracle user/schema

Nếu chưa có Oracle user/schema cho project, đăng nhập Oracle bằng tài khoản có quyền DBA rồi chạy:

```sql
CREATE USER PET_HOTEL IDENTIFIED BY your_password;

GRANT CONNECT, RESOURCE TO PET_HOTEL;

ALTER USER PET_HOTEL QUOTA UNLIMITED ON USERS;
```

Sau đó cập nhật lại `.env`:

```env
DB_USERNAME=PET_HOTEL
DB_PASSWORD=your_password
```

### 2.5. Chạy migration, seeder và khởi động web

Sau khi chỉnh `.env`, xóa cache cấu hình để Laravel nhận thông tin mới:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Tạo bảng và nạp dữ liệu mẫu:

```bash
php artisan migrate:fresh --seed
```

Khởi động Laravel server:

```bash
php artisan serve
```

Mở trình duyệt và truy cập:

```text
http://127.0.0.1:8000
```

---

## 3. Tài khoản demo

Các tài khoản seed mặc định sử dụng chung mật khẩu:

```text
password123
```

Một số tài khoản demo có thể dùng:

```text
admin.demo@pethotel.test
manager.central@pethotel.test
customer.small@pethotel.test
customer.medium@pethotel.test
customer.large@pethotel.test
```

Ngoài ra, người dùng cũng có thể tự tạo tài khoản khách hàng mới trực tiếp trên website thông qua trang đăng ký.

---

## 4. Luồng demo chính

Luồng demo chính của hệ thống gồm:

1. Public: xem trang chủ, dịch vụ, chi nhánh, khách sạn cho chó, khách sạn cho mèo và chi tiết loại phòng.
2. Customer: đăng ký, đăng nhập, cập nhật hồ sơ, quản lý thú cưng, tạo booking, chọn dịch vụ, áp dụng coupon, thanh toán và xem lịch sử booking.
3. Manager: xem dashboard, quản lý dịch vụ, khuyến mãi, nhân viên, vật tư và tồn kho theo phạm vi quản lý.
4. CEO: xem dashboard tổng quan, thống kê doanh thu, báo cáo vận hành và dữ liệu tổng hợp toàn hệ thống.

---

## 5. Một số đường dẫn thường dùng

```text
/authentication/register
/authentication/login
/profile
/profile/edit
/pets
/pets/create
/booking
/booking/branch/4
/payment/booking/{bookingId}
/profile/history-booking
/booking/{bookingId}
```

API kiểm tra phòng trống:

```text
/api/booking/branch/{branchId}/room-types/availability
```

---

## 6. Ghi chú về cơ sở dữ liệu Oracle

Project sử dụng Oracle Database làm cơ sở dữ liệu chính và đã được cấu hình để tránh chạy nhầm sang MySQL hoặc SQLite.

Các điểm đã cấu hình gồm:

- `.env.example` sử dụng Oracle làm database mặc định
- `config/database.php` fallback sang `oracle` và có connection `oracle`
- `config/queue.php` fallback database batch/failed jobs sang Oracle
- `composer.json` không tạo file SQLite mặc định
- `phpunit.xml` không ép test dùng SQLite in-memory

Vì vậy, khi clone project về, người dùng chỉ cần cấu hình đúng file `.env` theo Oracle trên máy cá nhân là có thể chạy migration, seeder và website.

---

## 7. Tổ chức mã nguồn

| Thư mục/file               | Vai trò                                                                         |
| -------------------------- | ------------------------------------------------------------------------------- |
| `app/Http/Controllers/Web` | Xử lý các trang web theo nhóm Authentication, Customer, Manager, CEO và Default |
| `app/Http/Controllers/Api` | Xử lý API JSON cho dashboard, báo cáo, tài chính, dịch vụ và tồn kho            |
| `app/Http/Requests`        | Chứa các lớp validate dữ liệu đầu vào                                           |
| `app/Http/Middleware`      | Chứa middleware phân quyền và kiểm soát truy cập                                |
| `app/Models`               | Chứa Eloquent model ánh xạ với các bảng Oracle                                  |
| `app/Repositories`         | Tách phần truy vấn dữ liệu theo interface và implementation                     |
| `app/Services`             | Xử lý nghiệp vụ booking và các logic dùng lại                                   |
| `routes/web`               | Route giao diện web, tách theo public, customer, manager, ceo và authentication |
| `routes/api`               | Route API trả JSON                                                              |
| `resources/views`          | Chứa Blade template, layout, component và các trang giao diện                   |
| `public/assets`            | Chứa CSS, JavaScript và hình ảnh                                                |
| `database/migrations`      | Định nghĩa cấu trúc bảng                                                        |
| `database/seeders`         | Tạo dữ liệu mẫu phục vụ demo                                                    |

---

## 8. Lệnh chạy nhanh

Nếu đã cài đủ môi trường và cấu hình `.env`, có thể chạy nhanh theo thứ tự sau:

```bash
git clone <repo-url>
cd pet-hotel
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:fresh --seed
php artisan serve
```

Nếu `cache:clear` hoặc `route:clear` báo lỗi do cache chưa được tạo, có thể bỏ qua và chạy tiếp các lệnh còn lại.

Trên Windows PowerShell, nếu lệnh `cp` không chạy thì dùng:

```powershell
Copy-Item .env.example .env
```

Sau đó truy cập:

```text
http://127.0.0.1:8000
```

---

## 9. Ghi chú khi demo

Một số màn hình quản lý sử dụng API và dữ liệu seed để phục vụ demo, kiểm thử và nghiệm thu.

Luồng khách hàng, booking và payment là luồng chính dùng để demo. Đây là luồng có tạo dữ liệu thật và có xử lý transaction.

Trong nghiệp vụ đặt phòng, backend không được chỉ dựa vào dữ liệu phòng trống đang hiển thị trên giao diện. Khi khách hàng bấm xác nhận booking, hệ thống cần kiểm tra lại availability trong transaction và khóa phòng trước khi tạo booking, nhằm tránh việc hai khách hàng đặt trùng một phòng trong cùng thời gian.
