<?php 
namespace App\Http\Controllers\Web\Evaluation;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Evaluation\Evaluation\StoreRequest;
use App\Http\Requests\Evaluation\Evaluation\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\Task\TaskServiceInterface as TaskService;
use App\Services\Interfaces\Status\StatusServiceInterface as StatusService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use App\Services\Interfaces\User\UserCatalogueServiceInterface as UserCatalogueService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request as CustomRequest;

class EvaluationController extends BaseController{

    protected $namespace = 'evaluation';
    protected $route = 'evaluations';
    
    protected $service;
    protected $taskService;
    protected $statusService;
    protected $userService;
    protected $userCatalogueService;

    private const CANBO_LEVEL = 5;
    private const DOIPHO_LEVEL = 4;


    public function __construct(
        EvaluationService $service,
        TaskService $taskService,
        StatusService $statusService,
        UserService $userService,
        UserCatalogueService $userCatalogueService,
    )
    {
        $this->service = $service;
        $this->taskService = $taskService;
        $this->statusService = $statusService;
        $this->userService = $userService;
        $this->userCatalogueService = $userCatalogueService;
        parent::__construct($service);
    }


    public function index(Request $request): View | RedirectResponse{
        try {
            $user = Auth::user();
            $request->merge([
                'user_id' => $user->id
            ]);
            $records = $this->service->paginate($request);
            $config = $this->config();
            $data = $this->getData();
            extract($data);
            $template = ($user->rgt - $user->lft > 1) ? "backend.{$this->namespace}.indexSuperior" : "backend.{$this->namespace}.index";
            
            return view($template, compact(
                'records',
                'config',
                'user',
                ...array_keys($data),
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }


    public function create(Request $request){
        try {
            $user = Auth::user();
            $config = $this->config();
            $config['user_id'] = Auth::user()->id;
            $config['method'] = 'create';
            $data = $this->getData();
            extract($data);
            $template = ($user->user_catalogue_id != config('apps.general.officer')) ? "backend.{$this->namespace}.superior" :  "backend.{$this->namespace}.save";
            return view($template, compact(
                'config',
                ...array_keys($data)
            ));
        }catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
            return redirect()->route("{$this->route}.index");
        }catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
        
    }

    public function edit(Request $request, int $id) : View | RedirectResponse{
        try {
            $user = Auth::user();
            $model = $this->service->findById($id);
            $config = $this->config();
            $data = $this->getData();
            extract($data);
            $config['method'] = 'update';
            $template = ($user->rgt - $user ->lft > 1) ? "backend.{$this->namespace}.superior" :  "backend.{$this->namespace}.save";
            return view($template, compact(
                'config',
                'model',
                ...array_keys($data)
            ));

        } catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
           return redirect()->route("{$this->route}.index");
        }
         catch (\Throwable $th) {
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
            'tasks' =>  $this->taskService->checkTask(),
            'statuses' => $this->statusService->all(),
        ];
    }

    public function teams(Request $request, $level)
    {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['user_catalogues']);
        
        $fakeRequest = new CustomRequest();
        $fakeRequest->merge([
            'level' => $level,
            'type' => 'all'
        ]);

        // dd($fakeRequest->all());

        //Lấy ra nhóm công chức
        $userCataloguesByLevel = $this->userCatalogueService->paginate($fakeRequest);
        $userCataloguesId = $userCataloguesByLevel->pluck('id')->toArray(); //Mã công chức

        $usersId = $user->subordinates()->get()->pluck('id')->toArray();//Lấy ra danh sách id của các user đang trong nhóm hiện tại


        /** Lấy ra nhóm của người đang đăng nhập */
        $currentUserCatalogueLevel = $user->user_catalogues->level ?? null;

        switch ($level) {
            case self::CANBO_LEVEL:

                $request->merge([
                    'relationFilter' => [ 
                        'users' => [
                            'user_id' => [
                                'in' => 'user_id|'.implode(',', $usersId)
                            ],
                        ],
                    ],
                    'time' => [
                        'statuses' => [
                            'created_at' => [
                                'month' => $request?->month_id 
                            ]
                        ]
                    ]
                ]);
                break;
            default:
                $request->merge([
                    'relationFilter' => [ 
                        'users' => [
                            'lft' => [
                                'gt' => $user->lft
                            ],
                            'rgt' => [
                                'lt' => $user->rgt
                            ],
                            'user_catalogue_id' => [
                                'in' => 'user_catalogue_id|'.implode(',', $userCataloguesId)
                            ],
                        ],
                    ],
                    'time' => [
                        'statuses' => [
                            'created_at' => [
                                'month' => $request?->month_id 
                            ]
                        ]
                    ]
                ]);
                break;
        }
        
        try {
            $records = $this->service->paginate($request);

            /** Lấy ra nhóm đang muốn hiển thị đánh giá */
            $evaluatedCatalogueLevel = $level;


            foreach($records as $key => $record){
                // Mặc định không cho đánh giá
                $canEvaluate = false;
                // Đánh dấu xem có bị khóa bởi cấp trên không
                $isLockedByHigherLevel = false;
            
                // Kiểm tra xem có bất kỳ cấp nào cao hơn người đăng nhập đã đánh giá chưa
                if (!is_null($currentUserCatalogueLevel)) {
                    $higherLevelEvaluations = DB::table('evaluation_status')
                        ->join('users', 'evaluation_status.user_id', '=', 'users.id')
                        ->join('user_catalogue_user', 'users.id', '=', 'user_catalogue_user.user_id')
                        ->join('user_catalogues', 'user_catalogue_user.user_catalogue_id', '=', 'user_catalogues.id')
                        ->where('evaluation_status.evaluation_id', $record->id)
                        ->where('user_catalogues.level', '<', $currentUserCatalogueLevel) // Cấp cao hơn (level nhỏ hơn)
                        ->where('evaluation_status.lock', 1)
                        ->exists();
                    
                    // Nếu có bất kỳ cấp cao hơn đã đánh giá, đánh dấu đã bị khóa
                    if ($higherLevelEvaluations) {
                        $isLockedByHigherLevel = true;
                        $canEvaluate = false; // Đảm bảo không cho phép đánh giá
                    }
                    // Nếu không có cấp cao hơn đánh giá, kiểm tra quyền dựa trên level
                    else if (!is_null($evaluatedCatalogueLevel)) {
                        $levelDifference = $evaluatedCatalogueLevel - $currentUserCatalogueLevel;
                        
                        // Đối với đội phó (level = 4), cho phép đánh giá nếu có quan hệ quản lý với công chức
                        if ($currentUserCatalogueLevel == 4) { // Nếu là đội phó
                            // Kiểm tra xem người đánh giá (đội phó) có phải là người quản lý của người được đánh giá không
                            $isManager = DB::table('user_subordinate')
                                ->where('manager_id', Auth::id())
                                ->where('subordinate_id', $record->user_id)
                                ->exists();
                            
                            if ($isManager) {
                                $canEvaluate = true;
                            }
                        }
                        // Nếu là cấp trên trực tiếp hoặc cao hơn, luôn cho phép đánh giá
                        else if ($levelDifference >= 1) {
                            $canEvaluate = true;
                        }
                    }
                }
                
                $record->canEvaluate = $canEvaluate;
                $record->isLockedByHigherLevel = $isLockedByHigherLevel;
            }
            $config = $this->config();


            $fakeRequestForUsers = new CustomRequest();
            $fakeRequestForUsers->merge([
                'lft' => ['gt' => $user->lft],
                'rgt' => ['lt' => $user->rgt],
                'user_catalogue_id' => ['in' => 'user_catalogue_id|' . implode(',', $userCataloguesId)],
                'type' => 'all'
            ]);
            $usersOnBranch = $this->userService->paginate($fakeRequestForUsers);

            $config = [
                'route' => $this->route,
                'isCreate' => false,
                'filter' => false,
                'usersOnBranch' => $usersOnBranch,
                'level' => $level
            ];
            $data = $this->getData();
            extract($data);
            $template = ($level != self::CANBO_LEVEL) ? "backend.{$this->namespace}.teamSuperior" : "backend.{$this->namespace}.team";
            return view($template, compact(
                'records',
                'config',
                ...array_keys($data),
            ));
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }

    public function search(Request $request, $user_catalogue){
        $user = Auth::user();
        $request->merge([
            'relationFilter' => [ 
                'users' => [
                    'lft' => [
                        'gt' => $user->lft
                    ],
                    'rgt' => [
                        'lt' => $user->rgt
                    ],
                    'user_catalogue_id' =>[
                        'eq' => $user_catalogue
                    ],
                ],
            ],
            'time' => [
                'statuses' => [
                    'created_at' => [
                        'month' => $request?->month_id 
                    ]
                ]
            ]
        ]);
        $records = $this->service->paginate($request);
        $data = $this->getData();
        $usersOnBranch = $this->userService?->getUsersOnBranch($user, $user_catalogue);
        $config = $this->config();
        $config = [
            'route' => $this->route,
            'isCreate' => false,
            'filter' => false,
            'usersOnBranch' => $usersOnBranch,
            'userCatalogue' => $this->userCatalogueService?->findById($user_catalogue)
        ];
        extract($data);
        return view("backend.{$this->namespace}.team", compact(
            'records',
            'config',
            ...array_keys($data),
        ));
    }
   


}   