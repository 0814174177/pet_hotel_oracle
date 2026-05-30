<?php

namespace App\Repositories\Contracts\Manager;

interface ManagerDashboardRepositoryInterface
{
    /**
     * Prepare the empty response structure for the Manager dashboard overview.
     */
    public function getOverview(array $filters = []): array;

    /**
     * Count valid check-in bookings for the selected branch and reporting period.
     */
    public function countCheckIns(int|string|null $branchId, array $period): int;

    /**
     * Count valid check-out bookings for the selected branch and reporting period.
     */
    public function countCheckOuts(int|string|null $branchId, array $period): int;

    /**
     * Count valid Spa/Grooming appointments for the selected branch and reporting period.
     */
    public function countGroomingAppointments(int|string|null $branchId, array $period): int;

    /**
     * Return room availability or occupancy status for the selected branch and reporting period.
     */
    public function getRoomStatus(int|string|null $branchId, array $period): array;

    /**
     * Return urgent operational task data for the selected branch and reporting period.
     */
    public function getUrgentTaskData(int|string|null $branchId, array $period): array;

    /**
     * Return financial risk radar data for the selected branch and reporting period.
     */
    public function getFinancialRiskData(int|string|null $branchId, array $period): array;

    /**
     * Return cancelled booking summary data for the selected branch and reporting period.
     */
    public function getCancelledBookingSummary(int|string|null $branchId, array $period): array;

    /**
     * Return cancelled booking detail data for the selected branch and reporting period.
     */
    public function getCancelledBookingDetails(int|string|null $branchId, array $period): array;

    /**
     * Return top revenue service data for the selected branch and reporting period.
     */
    public function getTopRevenueServiceData(int|string|null $branchId, array $period): array;

    /**
     * Return revenue structure data for the selected branch and reporting period.
     */
    public function getRevenueStructureData(int|string|null $branchId, array $period): array;

    /**
     * Return debt ledger data for the selected branch and reporting period.
     */
    public function getDebtData(int|string|null $branchId, array $period): array;
}
