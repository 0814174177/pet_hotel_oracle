<?php

namespace App\Providers;

use App\Models\Pet;
use App\Policies\PetPolicy;
use App\Repositories\Contracts\Ceo\BranchNetworkRepositoryInterface;
use App\Repositories\Contracts\Ceo\CeoDashboardRepositoryInterface;
use App\Repositories\Contracts\Ceo\CeoFinanceRepositoryInterface;
use App\Repositories\Contracts\Ceo\CeoVendorRepositoryInterface;
use App\Repositories\Contracts\Ceo\ServiceRevenueRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\EmployeeManagementRepositoryInterface;
use App\Repositories\Contracts\Manager\BranchScopedInventoryMaterialRepositoryInterface;
use App\Repositories\Contracts\Manager\BranchScopedRevenueReportRepositoryInterface;
use App\Repositories\Contracts\Manager\BranchScopedServiceManagementRepositoryInterface;
use App\Repositories\Contracts\Manager\ManagerDashboardRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\PromotionRepositoryInterface;
use App\Repositories\Eloquent\Ceo\BranchNetworkRepository;
use App\Repositories\Eloquent\Ceo\CeoDashboardRepository;
use App\Repositories\Eloquent\Ceo\CeoFinanceRepository;
use App\Repositories\Eloquent\Ceo\CeoVendorRepository;
use App\Repositories\Eloquent\Ceo\ServiceRevenueRepository;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\EmployeeManagementRepository;
use App\Repositories\Eloquent\Manager\BranchScopedInventoryMaterialRepository;
use App\Repositories\Eloquent\Manager\BranchScopedRevenueReportRepository;
use App\Repositories\Eloquent\Manager\BranchScopedServiceManagementRepository;
use App\Repositories\Eloquent\Manager\ManagerDashboardRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\PromotionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ràng buộc Interface với Eloquent Implementation
        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class
        );

        $this->app->bind(
            EmployeeManagementRepositoryInterface::class,
            EmployeeManagementRepository::class
        );

        $this->app->bind(
            PromotionRepositoryInterface::class,
            PromotionRepository::class
        );

        $this->app->bind(
            CeoDashboardRepositoryInterface::class,
            CeoDashboardRepository::class
        );

        $this->app->bind(
            CeoFinanceRepositoryInterface::class,
            CeoFinanceRepository::class
        );

        $this->app->bind(
            BranchNetworkRepositoryInterface::class,
            BranchNetworkRepository::class
        );

        $this->app->bind(
            ServiceRevenueRepositoryInterface::class,
            ServiceRevenueRepository::class
        );

        $this->app->bind(
            CeoVendorRepositoryInterface::class,
            CeoVendorRepository::class
        );

        $this->app->bind(
            ManagerDashboardRepositoryInterface::class,
            ManagerDashboardRepository::class
        );

        $this->app->bind(
            BranchScopedServiceManagementRepositoryInterface::class,
            BranchScopedServiceManagementRepository::class
        );

        $this->app->bind(
            BranchScopedRevenueReportRepositoryInterface::class,
            BranchScopedRevenueReportRepository::class
        );

        $this->app->bind(
            BranchScopedInventoryMaterialRepositoryInterface::class,
            BranchScopedInventoryMaterialRepository::class
        );

    }

    public function boot(): void
    {
        Gate::policy(Pet::class, PetPolicy::class);
    }
}
