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
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Str;

class UserController extends BaseController{

    protected $namespace = 'user';
    protected $route = 'users';
    protected $nestedset;

    protected $service;
    protected $userCatalogueService;
    protected $provinceService;
    protected $teamService;
    protected $unitService;

    private const ACTIVE_STATUS = 2;

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

    public function index(Request $request): View | RedirectResponse{
        try {
            $request = $this->userNode($request);
            $records = $this->service->paginate($request);
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            $data = $this->getData();
            extract($data);
            return view("backend.{$this->namespace}.index", compact(
                'records',
                'config',
                ...array_keys($data)
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function store(StoreRequest $request): RedirectResponse{
        return $this->baseSave($request);
    }

    public function update(UpdateRequest $request, int $id){
        return $this->baseSave($request, $id);
    }

    protected function getData(): array{
        $fakeRequest =  new \Illuminate\Http\Request();
        $fakeRequest = $this->userNode($fakeRequest);
        $fakeRequest->merge([
            'type' => 'all',
            'user_catalogues' => [
                'level' => [ 'lte' => 4 ]
            ]
        ]);
        return [
            'user_catalogues' => isset($this->userCatalogueService) ? $this->userCatalogueService?->all() : null,
            'provinces' => isset($this->provinceService) ?  $this->provinceService->all() : null,
            'teams' => isset($this->teamService) ? $this->teamService->teamPublish() : null,
            'units' => isset($this->unitService) ? $this->unitService->unitPublish() : null,
            'dropdown'  => $this->service->paginate($fakeRequest),
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

    private function userNode($request){
        $auth = Auth::user();
        $request->merge([
            'lft' => [
                'gte' => $auth->lft
            ],
            'rgt' => [
                'lte' => $auth->rgt
            ],
        ]);
        return $request;
    }

}   