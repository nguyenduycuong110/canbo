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
use App\Models\User;
use App\Models\Task;
use App\Services\Interfaces\Team\TeamServiceInterface as TeamService;
use Illuminate\Support\Carbon;

class EvaluationController extends BaseController{

    protected $namespace = 'evaluation';
    protected $route = 'evaluations';
    
    protected $service;
    protected $taskService;
    protected $statusService;
    protected $userService;
    protected $userCatalogueService;
    protected $teamService;

    private const CANBO_LEVEL = 5;
    private const DOIPHO_LEVEL = 4;


    public function __construct(
        EvaluationService $service,
        TaskService $taskService,
        StatusService $statusService,
        UserService $userService,
        UserCatalogueService $userCatalogueService,
        TeamService $teamService,
    )
    {
        $this->service = $service;
        $this->taskService = $taskService;
        $this->statusService = $statusService;
        $this->userService = $userService;
        $this->userCatalogueService = $userCatalogueService;
        $this->teamService = $teamService;
        parent::__construct($service);
    }


    public function index(Request $request): View | RedirectResponse{
        try {
            $user = Auth::user();
            $date = now()->format('m/Y');
            $monthCurrent = \Carbon\Carbon::createFromFormat('m/Y', $date);
            $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();
            $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();
            $request->merge([
                'user_id' => $user->id,
                'start_date' => [
                    'gte' => $startOfMonth,
                    'lte' => $endOfMonth
                ]
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
            $tasks = $this->getTask($request , $user);
            extract($data);
            $template = ($user->user_catalogue_id != config('apps.general.officer')) ? "backend.{$this->namespace}.superior" :  "backend.{$this->namespace}.save";
            return view($template, compact(
                'config',
                'tasks',
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
            $tasks = $this->getTask($request , $user);
            extract($data);
            $config['method'] = 'update';
            $template = ($user->rgt - $user ->lft > 1) ? "backend.{$this->namespace}.superior" :  "backend.{$this->namespace}.save";
            return view($template, compact(
                'config',
                'model',
                'tasks',
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
            'statuses' => $this->statusService->all(),
            'teams' => $this->teamService->teamPublish(),
        ];
    }

    public function teams(Request $request, int $level)
    {
        try {
            $date = now()->format('m/Y');
            $monthCurrent = \Carbon\Carbon::createFromFormat('m/Y', $date);
            $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();
            $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();
            $auth = Auth::user();
            $currentUserCatalogue = $auth->user_catalogues;
            $listSubordinate = $this->userCatalogueService->listSubordinate($auth,$currentUserCatalogue);
            $currentUserPosition = $currentUserCatalogue->name;
            $currentUserLevel = $currentUserCatalogue->level;
            $isDeputyTeamLeader = $currentUserLevel == 4;

            $records = match ($level) {
                5 => $this->getCongChucInsideNodeEvaluation($request, $level, $monthCurrent),
                default => $this->getInsideNodeEvaluation($request, $level, $monthCurrent),
            };

            $allParams = $request->query();
            
            $allPositionsData = [];

            $hasCurrentUserEvaluated = false;

            if (!is_null($records)) {
                $records->load(['tasks', 'statuses', 'users.user_catalogues']);

                foreach ($records as $record) {
                    $record->pointForCurrentUser = $record->statuses()->where('user_id', $auth->id)->first()?->pivot->point;
                    $record->selfEvaluation = $record->statuses()->where('user_id', $record->user_id)->first()?->pivot->status_id;
                    $currentUserEvaluation = $record->statuses()
                        ->where('user_id', $auth->id)
                        ->first();
                    $record->currentUserStatusId = $currentUserEvaluation ? $currentUserEvaluation->pivot->status_id : 0;
                    $record->lock = $currentUserEvaluation ? $currentUserEvaluation->pivot->lock : 0;

                    if ($currentUserEvaluation) {
                        $hasCurrentUserEvaluated = true;
                    }

                    $positionEvaluations = [];
                    $record->deputyEvaluation = null;
                    $evaluations = $record->statuses;

                    // Lấy đánh giá mới nhất của Đội phó theo updated_at
                    $deputyEvaluations = $evaluations->filter(function ($evaluation) use ($record) {
                        $userId = $evaluation->pivot->user_id;
                        if ($userId == $record->user_id) return false;
                        $user = $this->userService->findById($userId);
                        if ($user) {
                            $user->load('user_catalogues');
                            $userCatalogue = $user->user_catalogues()->first();
                            return $userCatalogue && $userCatalogue->level == 4;
                        }
                        return false;
                    })->sortByDesc('pivot.updated_at'); // Sắp xếp theo updated_at (mới nhất trước)

                    if ($deputyEvaluations->isNotEmpty()) {
                        $latestDeputyEvaluation = $deputyEvaluations->first();
                        $userId = $latestDeputyEvaluation->pivot->user_id;
                        $user = $this->userService->findById($userId);
                        $record->deputyEvaluation = [
                            'status_id' => $latestDeputyEvaluation->pivot->status_id,
                            'user_id' => $userId,
                            'user_name' => $user->name,
                            'point' => $latestDeputyEvaluation->pivot->point
                        ];
                    }

                    foreach ($evaluations as $evaluation) {
                        $userId = $evaluation->pivot->user_id;

                        if ($userId == $record->user_id) {
                            continue;
                        }

                        $user = $this->userService->findById($userId);
                        if ($user) {
                            $user->load('user_catalogues');
                            $userCatalogue = $user->user_catalogues()->first();
                            if ($userCatalogue) {
                                $positionName = $userCatalogue->name;
                                $positionLevel = $userCatalogue->level;

                                if ($positionName !== 'Đội phó') { // Bỏ qua Đội phó vì đã xử lý ở trên
                                    if (!isset($allPositionsData[$positionName])) {
                                        $allPositionsData[$positionName] = [
                                            'name' => $positionName,
                                            'level' => $positionLevel,
                                            'is_current_user' => ($positionName == $currentUserPosition)
                                        ];
                                    }

                                    $positionEvaluations[$positionName] = [
                                        'status_id' => $evaluation->pivot->status_id,
                                        'user_name' => $user->name,
                                        'point' => $evaluation->pivot->point,
                                    ];
                                }
                            }
                        }
                    }

                    $record->positionEvaluations = $positionEvaluations;

                    $record->higherLevelEvaluated = false;
                    if ($currentUserLevel !== null) {
                        foreach ($record->statuses as $status) {
                            $evaluatorId = $status->pivot->user_id;

                            if ($evaluatorId == $auth->id || $evaluatorId == $record->user_id) {
                                continue;
                            }

                            $evaluator = $this->userService->findById($evaluatorId);
                            if ($evaluator) {
                                $evaluator->load('user_catalogues');
                                $evaluatorCatalogue = $evaluator->user_catalogues()->first();
                                $evaluatorLevel = $evaluatorCatalogue ? $evaluatorCatalogue->level : null;

                                if ($evaluatorLevel !== null && $evaluatorLevel < $currentUserLevel) {
                                    $record->higherLevelEvaluated = true;
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!$hasCurrentUserEvaluated && !$isDeputyTeamLeader) {
                $allPositionsData['__CURRENT_USER__'] = [
                    'name' => 'Đánh giá của bạn',
                    'level' => $currentUserLevel,
                    'is_current_user' => true
                ];
            }

            $allPositionsData = array_filter($allPositionsData, function ($position) use ($currentUserLevel) {
                return $position['level'] >= $currentUserLevel;
            });

            uasort($allPositionsData, function ($a, $b) {
                return $b['level'] - $a['level'];
            });

            $config = [
                'route' => $this->route,
                'isCreate' => false,
                'filter' => false,
                'usersOnBranch' => $this->getCongChucInsideNode($request, $level),
                'level' => $level
            ];

            $userByLevel = $this->userService->getUserByLevel($level);

            $deputyDepartment = null;

            if($auth->parent_id == 0){
                $request->merge([
                    'lft' => ['gt' => $auth->lft],
                    'rgt' => ['lt' => $auth->rgt],
                    'relationFilter' => ['user_catalogues' => ['level' => ['eq' => $auth->user_catalogues->level + 1]]], // 4 --> là level của đội phó
                    'type' => 'all'
                ]);
                $deputyDepartment = $this->userService->paginate($request);
            }

            $data = $this->getData();
            extract($data);
            $template = ($level != self::CANBO_LEVEL) ? "backend.{$this->namespace}.team.teamSuperior" : "backend.{$this->namespace}.team.team";
            return view($template, compact(
                'records',
                'auth',
                'config',
                'allPositionsData',
                'isDeputyTeamLeader',
                'listSubordinate',
                'statuses',
                'userByLevel',
                'deputyDepartment',
                'startOfMonth',
                'endOfMonth',
                'allParams',
                ...array_keys($data),
            ));
        } catch (\Throwable $th) {
            dd($th);
        }
    }

   
    public function getCongChucInsideNodeEvaluation($request, $level, $monthCurrent = null){
        /** @var \App\Models\User $auth */
        $auth = Auth::user();
        $auth->load(['subordinates']);
        $subordinateIds = [];
        $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();
        if($auth->user_catalogues->level < 4){
            $request->merge([
                'lft' => ['gt' => $auth->lft],
                'rgt' => ['lt' => $auth->rgt],
                'relationFilter' => ['user_catalogues' => ['level' => ['lte' => $level - 1]]], // 4 --> là level của đội phó
                'type' => 'all'
            ]);
            $users = $this->userService->paginate($request);
            if(!is_null($users) && count($users)){
                foreach($users as $key => $deputy){ 
                    $subordinates = $deputy->subordinates()->get()->pluck('id')->toArray();
                    $subordinateIds = array_merge($subordinateIds, $subordinates);
                }
            }
            $subordinateIds = array_unique($subordinateIds);
        }else{
            if($auth->user_catalogues->level == 4){
                $subordinateIds = $auth->subordinates()->get()->pluck('id')->toArray();
            }
        }

        $userId = [];
        if($request->user_id){
            $userId = [$request->user_id];
        }else{
            $userId = $subordinateIds;
        }

        $evaluationRequest = new CustomRequest();

        $relationFilter = [
            'users' => [
                'user_id' => [
                    'in' => 'user_id|' . implode(',', $userId)
                ]
            ]
        ];

        // Thêm điều kiện team_id nếu có
        if ($request->has('team_id') && $request->team_id != 0) {
            $relationFilter['users.teams'] = [
                'id' => [
                    'in' => 'id|' . $request->team_id
                ]
            ];
        }

        if($request->has('start_date') && $request->start_date != ''){
            $evaluationRequest->merge([
                'start_date' => $request->start_date,
            ]);
        }


        $evaluationRequest->merge([
            'start_date' => [
                'gte' => $startOfMonth,
                'lte' => $endOfMonth
            ],
            'sort' => 'start_date,desc',
            'relationFilter' => $relationFilter
        ]);

        $evaluations = $this->service->paginate($evaluationRequest);

        return $evaluations;
    }

    public function getInsideNodeEvaluation($request, $level, $monthCurrent = null)
    {
        /** @var \App\Models\User $user */
        $auth = Auth::user();
    
        // Lấy danh sách user thuộc cấp $level trong nhánh của người đăng nhập
        $userIds = [];
        $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();
        $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();
        $request->merge([
            'lft' => ['gt' => $auth->lft],
            'rgt' => ['lt' => $auth->rgt],
            'relationFilter' => ['user_catalogues' => ['level' => ['eq' => $level - 1]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
            'type' => 'all'
        ]);

        $users = $this->userService->paginate($request);

        if (!is_null($users) && count($users)) {
            $userIds = $users->pluck('id')->toArray();
        }
    
        // Nếu không có user nào, trả về null
        if (empty($userIds)) {
            return null;
        }

        $userId = [];
        if($request->user_id){
            $userId = [$request->user_id];
        }else{
            $userId = $userIds;
        }


        // Lấy danh sách đánh giá của các user này
        $evaluationRequest = new CustomRequest();
        $relationFilter = [
            'users' => [
                'user_id' => [
                    'in' => 'user_id|' . implode(',', $userId)
                ]
            ]
        ];

        // Thêm điều kiện team_id nếu có
        if ($request->has('team_id') && $request->team_id != 0) {
            $relationFilter['users.teams'] = [
                'id' => [
                    'in' => 'id|' . $request->team_id
                ]
            ];
        }

        if($request->has('start_date') && $request->start_date != ''){
            $evaluationRequest->merge([
                'start_date' => $request->start_date
            ]);
        }

        $evaluationRequest->merge([
            'start_date' => [
                'gte' => $startOfMonth,
                'lte' => $endOfMonth
            ],
            'relationFilter' => $relationFilter
        ]);
    
        $evaluations = $this->service->paginate($evaluationRequest);
        return $evaluations;
    }
   
    public function getCongChucInsideNode($request , $level){
        /** @var \App\Models\User $auth */
        $auth = Auth::user();
        $auth->load(['subordinates']);
        $subordinateIds = [];
        if($auth->user_catalogues->level < 4){
            $request->merge([
                'lft' => ['gt' => $auth->lft],
                'rgt' => ['lt' => $auth->rgt],
                'relationFilter' => ['user_catalogues' => ['level' => ['eq' => $level - 1]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
                'type' => 'all'
            ]);
            $users = $this->userService->paginate($request);
            if(!is_null($users) && count($users)){
                foreach($users as $key => $deputy){ 
                    $subordinates = $deputy->subordinates()->get()->pluck('id')->toArray();
                    $subordinateIds = array_merge($subordinateIds, $subordinates);
                }
            }
            $subordinateIds = array_unique($subordinateIds);
        }else{
            if($auth->user_catalogues->level == 4){
                $subordinateIds = $auth->subordinates()->get()->pluck('id')->toArray();
            }
        }
        $users = User::whereIn('id', $subordinateIds)->get();
        return $users;
    }
    
    private function getTask($request, $user){
        $level = $user->user_catalogues->level;
        $request->merge([
            'lft' => ['lte' => $user->lft],
            'rgt' => ['gte' => $user->rgt],
            'relationFilter' => 
                [
                    'user_catalogues' => [
                        'level' => [
                            'lte' => $level,
                            'gte' => $level - 2
                        ],
                    ]
                ], 
            'type' => 'all'
        ]);
        $users = $this->userService->paginate($request)->pluck('id')->toArray();
        $tasks = Task::whereIn('user_id', $users)->get();
        return $tasks;
    }

    

}   