<?php 
namespace App\Http\Controllers\Web\User;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\User\UserCatalogue\StoreRequest;
use App\Http\Requests\User\UserCatalogue\UpdateRequest;
use App\Services\Impl\Permission\PermissionService;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\User\UserCatalogueServiceInterface as UserCatalogueService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Interfaces\Permission\PermissionCatalogueServiceInterface as PermissionCatalogueService;


class UserCatalogueController extends BaseController{

    protected $namespace = 'user.catalogue';
    protected $route = 'user_catalogues';

    protected $service;
    protected $permissionService;


    public function __construct(
        UserCatalogueService $service,
        PermissionService $permissionService
    )
    {
        $this->service = $service;
        $this->permissionService = $permissionService;
        parent::__construct($service);
    }

    public function index(Request $request): View | RedirectResponse{
        try {
            $records = $this->service->paginate($request);
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            $config['permission'] = false;
            return view("backend.{$this->namespace}.index", compact(
                'records',
                'config',
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
        return [
            'permissions' => $this->permissionService?->all()
        ];
    }
    

}   