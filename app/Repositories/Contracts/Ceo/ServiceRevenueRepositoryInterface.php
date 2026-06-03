<?php

namespace App\Repositories\Contracts\Ceo;

interface ServiceRevenueRepositoryInterface
{
    // 1. KPI dịch vụ toàn chuỗi: top revenue, active service count, no revenue count
    public function getServiceSummary(array $filters = []): array;

    // 2. Danh sách quản trị dịch vụ theo kỳ lọc: danh mục, trạng thái, giá, vốn, margin, lượt phục vụ, độ phủ
    public function getServiceCatalog(array $filters = []): array;

    // 3. Bảng/xếp hạng doanh thu từng dịch vụ theo kỳ lọc
    public function getServiceRevenueList(array $filters = []): array;

    // 4. Dịch vụ gánh doanh thu toàn chuỗi trong kỳ lọc
    public function getHighestRevenueService(array $filters = []): ?array;

    // 5. Dịch vụ có doanh thu thấp nhất trong kỳ
    public function getLowestRevenueService(array $filters = []): ?array;

    // 6. Dịch vụ không có doanh thu đã thanh toán trong kỳ lọc
    public function getNoActivityServices(array $filters = []): array;

    // 7. Dịch vụ siêu lợi nhuận toàn chuỗi theo kỳ lọc
    public function getMostProfitableService(array $filters = []): ?array;
}
