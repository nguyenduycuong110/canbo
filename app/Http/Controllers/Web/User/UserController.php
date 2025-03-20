<?php 
namespace App\Http\Controllers\Web\User;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\User\User\StoreRequest;
use App\Http\Requests\User\User\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use App\Services\Interfaces\User\UserCatalogueServiceInterface as UserCatalogueService;
use App\Services\Interfaces\Area\ProvinceServiceInterface as ProvinceService;
use App\Services\Interfaces\Team\TeamServiceInterface as TeamService;
use App\Services\Interfaces\Unit\UnitServiceInterface as UnitService;
use App\Classes\Nestedsetbie;

class UserController extends BaseController{

    protected $namespace = 'user';
    protected $route = 'users';
    protected $nestedset;

    protected $service;
    protected $userCatalogueService;
    protected $provinceService;
    protected $teamService;
    protected $unitService;

    public function __construct(
        UserService $service,
        UserCatalogueService $userCatalogueService,
        ProvinceService $provinceService,
        TeamService $teamService,
        UnitService $unitService,
    )
    {
        $this->service = $service;
        $this->userCatalogueService = $userCatalogueService;
        $this->provinceService = $provinceService;
        $this->teamService = $teamService;
        $this->unitService = $unitService;
        parent::__construct($service);
        $this->initialize();
    }

    private function initialize(){
        $this->nestedset = new Nestedsetbie([
            'table' => $this->route,
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse{
        return $this->baseSave($request);
    }

    public function update(UpdateRequest $request, int $id){
        return $this->baseSave($request, $id);
    }

    protected function getData(): array{
        return [
            'user_catalogues' => isset($this->userCatalogueService) ? $this->userCatalogueService?->all() : null,
            'provinces' => isset($this->provinceService) ?  $this->provinceService->all() : null,
            'teams' => isset($this->teamService) ? $this->teamService->all() : null,
            'units' => isset($this->unitService) ? $this->unitService->all() : null,
            'dropdown'  => $this->nestedset?->Dropdown(),
        ];
    }

    protected function config(){
        return [
            'js' => [
                'backend/js/plugins/switchery/switchery.js',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                'backend/plugins/ckfinder_2/ckfinder.js',
                'backend/library/finder.js',
                'backend/library/location.js'
            ],
            'css' => [
                'backend/css/plugins/switchery/switchery.css',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
            ],
            'route' => $this->route
        ];
    }

}   