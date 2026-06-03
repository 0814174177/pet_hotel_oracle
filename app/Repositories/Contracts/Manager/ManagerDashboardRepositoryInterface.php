<?php

namespace App\Repositories\Contracts\Manager;

interface ManagerDashboardRepositoryInterface
{
    /**
     * Return Manager dashboard overview data for the selected branch.
     */
    public function getOverview(int|string $branchId, array $filters = []): array;

    /**
     * Return manager overview schedule KPI cards for the selected branch.
     */
    public function getScheduleKpis(int|string $branchId, array $filters = []): array;

    /**
     * Return inventory warning counts for the selected branch.
     */
    public function getInventoryWarning(int|string $branchId, array $filters = []): array;

    /**
     * Return temporary health warning counts for the selected branch and period.
     */
    public function getHealthWarning(int|string $branchId, array $filters = []): array;

    /**
     * Return financial risk warning counts and lost revenue for the selected branch and period.
     */
    public function getFinancialRiskWarning(int|string $branchId, array $filters = []): array;

    /**
     * Return cancelled bookings with check-in proximity warning for the selected branch and period.
     */
    public function getLateCancelledBookings(int|string $branchId, array $filters = []): array;

    /**
     * Return top revenue services for the selected branch and period.
     */
    public function getTopRevenueServices(int|string $branchId, array $filters = []): array;

    /**
     * Return revenue structure groups for the selected branch and period.
     */
    public function getRevenueStructure(int|string $branchId, array $filters = []): array;

    /**
     * Return unpaid invoices and remaining debt for the selected branch and period.
     */
    public function getUnpaidInvoices(int|string $branchId, array $filters = []): array;

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
