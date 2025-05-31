<?php

namespace App\Providers;

use App\Models\Evaluation;
use App\Models\Province;
use Illuminate\Support\ServiceProvider;
use App\Services\Interfaces\Auth\AuthWebServiceInterface;
use App\Services\Impl\Auth\AuthService;
use App\Services\Interfaces\User\UserCatalogueServiceInterface;
use App\Services\Impl\User\UserCatalogueService;
use App\Services\Interfaces\User\UserServiceInterface;
use App\Services\Impl\User\UserService;
use App\Services\Interfaces\Permission\PermissionServiceInterface;
use App\Services\Impl\Permission\PermissionService;
use App\Services\Interfaces\Area\ProvinceServiceInterface;
use App\Services\Impl\Area\ProvinceService;
use App\Services\Interfaces\Area\DistrictServiceInterface;
use App\Services\Impl\Area\DistrictService;
use App\Services\Interfaces\Team\TeamServiceInterface;
use App\Services\Impl\Team\TeamService;
use App\Services\Interfaces\Department\DepartmentServiceInterface;
use App\Services\Impl\Department\DepartmentService;
use App\Services\Interfaces\Unit\UnitServiceInterface;
use App\Services\Impl\Unit\UnitService;
use App\Services\Interfaces\Task\TaskServiceInterface;
use App\Services\Impl\Task\TaskService;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface;
use App\Services\Impl\Evaluation\EvaluationService;
use App\Services\Interfaces\Status\StatusServiceInterface;
use App\Services\Impl\Status\StatusService;
use App\Services\Interfaces\Statistic\StatisticServiceInterface;
use App\Services\Impl\Statistic\StatisticService;
use App\Services\Interfaces\Delegation\DelegationServiceInterface;
use App\Services\Impl\Delegation\DelegationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthWebServiceInterface::class, AuthService::class);
        $this->app->bind(UserCatalogueServiceInterface::class, UserCatalogueService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(PermissionServiceInterface::class, PermissionService::class);
        $this->app->bind(ProvinceServiceInterface::class, ProvinceService::class);
        $this->app->bind(DistrictServiceInterface::class, DistrictService::class);
        $this->app->bind(TeamServiceInterface::class, TeamService::class);
        $this->app->bind(DepartmentServiceInterface::class, DepartmentService::class);
        $this->app->bind(UnitServiceInterface::class, UnitService::class);
        $this->app->bind(TaskServiceInterface::class, TaskService::class);
        $this->app->bind(EvaluationServiceInterface::class, EvaluationService::class);
        $this->app->bind(StatusServiceInterface::class, StatusService::class);
        $this->app->bind(StatisticServiceInterface::class, StatisticService::class);
        $this->app->bind(DelegationServiceInterface::class, DelegationService::class);
    }


    public function boot(): void
    {
        //
    }
}
