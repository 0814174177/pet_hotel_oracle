<?php

namespace App\Repositories\Contracts\Ceo;

interface ServiceRevenueRepositoryInterface
{
    // 1. KPI tổng quan dịch vụ toàn chuỗi
    public function getServiceSummary(array $filters): array;

    // 2. Danh sách quản trị dịch vụ: tên, danh mục, trạng thái, giá, vốn, margin, độ phủ
    public function getServiceCatalog(array $filters = []): array;

    // 3. Bảng/xếp hạng doanh thu từng dịch vụ
    public function getServiceRevenueList(array $filters): array;

    // 4. Dịch vụ có doanh thu cao nhất trong kỳ
    public function getHighestRevenueService(array $filters): ?array;

    // 5. Dịch vụ có doanh thu thấp nhất trong kỳ
    public function getLowestRevenueService(array $filters): ?array;

    // 6. Dịch vụ không phát sinh trong kỳ
    public function getNoActivityServices(array $filters): array;

    // 7. Dịch vụ có margin/lợi nhuận tạm tính cao nhất
    public function getMostProfitableService(array $filters): ?array;
}
