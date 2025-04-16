<?php 
namespace App\Http\Controllers\Web\User;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\User\User\StoreRequest;
use App\Http\Requests\User\User\UpdateRequest;
use App\Http\Requests\User\User\UpdatePasswordRequest;
use App\Http\Requests\User\User\UpdateProfileRequest;
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
use Illuminate\Support\Facades\Lang;
use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

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
            if(isset($request->team_id) && $request->team_id != 0){
                $request->merge([
                    'team_id' => [
                        'eq' => $request->team_id
                    ]
                ]);
            }
            $records = $this->service->paginate($request);
            $teamsInNode = $this->getTeamNode();
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            $data = $this->getData();
            extract($data);
            return view("backend.{$this->namespace}.index", compact(
                'records',
                'config',
                'teamsInNode',
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

    public function resetPassword(Request $request, $id): View | RedirectResponse{
        try {
            $config = $this->config();
            return view("backend.{$this->namespace}.resetPassword", compact(
                'config',
                'id',
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request, $id): RedirectResponse{
        try {
            if($response = $this->service->updatePassword($request, $id)){
                flash()->success(Lang::get('message.save_success'));
                return redirect()->route("{$this->route}.index");
            }else{
                flash()->error(Lang::get('message.save_failed'));
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    protected function getData(): array{
        $fakeRequest =  new \Illuminate\Http\Request();
        $fakeRequest = $this->userNode($fakeRequest);
        $fakeRequest->merge([
            'type' => 'all',
            // 'user_catalogues' => [
            //     'level' => [ 'lte' => 4 ]
            // ]
        ]);
        return [
            'user_catalogues' => isset($this->userCatalogueService) ? $this->userCatalogueService?->all() : null,
            'provinces' => isset($this->provinceService) ?  $this->provinceService->all() : null,
            'teams' => isset($this->teamService) ? $this->teamService->teamPublish() : null,
            'units' => isset($this->unitService) ? $this->unitService->unitPublish() : null,
            'dropdown'  => User::select(['id', 'name', 'account', 'user_catalogue_id', 'parent_id'])  // Chỉ chọn cột cần thiết
                ->without(['units', 'teams', 'subordinates', 'statistics'])  // Tắt eager loading
                ->whereHas('user_catalogues', function($query){
                    $query->where('level', '<=', 4);
                })->get()
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

    // private function getTeamNode(){

    //     try {
    //         $auth = Auth::user();
    //         $allUserInNode = User::where('lft','>=', $auth->lft)->where('rgt','<=', $auth->rgt)->offset(15)->limit(5)->get();

    //         $teamsId = array_unique($allUserInNode->pluck('team_id')->toArray());
    //         $teams = Team::whereIn('id', $teamsId)->get();
    //         return $teams;
    //     } catch (\Throwable $th) {
    //         Log::error('Error Get Teamnode', [
    //             'message' => $th->getMessage(),
    //             'trace' => $th->getTraceAsString()
    //         ]);
    //         return collect([]);
    //     }

    // }

    private function getTeamNode(){
        $auth = Auth::user();
        
        // Tối ưu hóa truy vấn chỉ lấy team_id
        $teamsId = User::where('lft','>=', $auth->lft)
            ->where('rgt','<=', $auth->rgt)
            ->select('team_id')
            ->distinct()
            ->pluck('team_id')
            ->toArray();
        
        // Lọc giá trị null nếu có
        $teamsId = array_filter($teamsId, function($id) {
            return $id !== null && $id !== 0;
        });
        
        if (empty($teamsId)) {
            return collect([]);
        }
        
        $teams = Team::whereIn('id', $teamsId)->get();
        return $teams;
    }

    public function profile(Request $request): View | RedirectResponse{
        try {
            $data = $this->getData();
            extract($data);
            $auth = Auth::user();
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            return view("backend.{$this->namespace}.profile", compact(
                'auth',
                'config',
                ...array_keys($data)
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function updateProfile(UpdateProfileRequest $request, $id): RedirectResponse{
        try {
            if($response = $this->service->updateProfile($request, $id)){
                flash()->success(Lang::get('message.save_success'));
                return redirect()->route("{$this->route}.index");
            }else{
                flash()->error(Lang::get('message.save_failed'));
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

}   