<?php 
namespace App\Http\Controllers\Web\Delegation;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Delegation\Delegation\StoreRequest;
use App\Http\Requests\Delegation\Delegation\UpdateRequest;
use App\Services\Interfaces\Delegation\DelegationServiceInterface as DelegationService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use App\Services\Interfaces\User\UserCatalogueServiceInterface as UserCatalogueService;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\Status\StatusServiceInterface as StatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Delegation;
use Illuminate\Http\Request as CustomRequest;
use Illuminate\Support\Str;

class DelegationController extends BaseController{

    protected $namespace = 'delegation';

    protected $route = 'delegations';

    protected $service;

    protected $userService;

    protected $evaluationService;

    protected $statusService;

    protected $userCatalogueService;

    private const CANBO_LEVEL = 5;

    public function __construct(
        DelegationService $service,
        UserService $userService,
        EvaluationService $evaluationService,
        StatusService $statusService,
        UserCatalogueService $userCatalogueService,
    )
    {
        $this->service = $service;
        $this->userService = $userService;
        $this->evaluationService = $evaluationService;
        $this->statusService = $statusService;
        $this->userCatalogueService = $userCatalogueService;
        parent::__construct($service);
    }

    public function index(Request $request): View | RedirectResponse{
        try {
            $auth = Auth::user();
            $request->merge([
                'delegator_id' => $auth->id
            ]);
            $records = $this->service->paginate($request);
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            return view("backend.{$this->namespace}.index", compact(
                'auth',
                'records',
                'config',
            ));
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }


    public function create(Request $request){
        try {
            $auth = Auth::user();
            $users = $this->getUserInsideNode($auth);
            $config = $this->config();
            $config['method'] = 'create';
            return view("backend.{$this->namespace}.save", compact(
                'auth',
                'users',
                'config',
                'auth',
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
            $model = $this->service->findById($id);
            $auth = Auth::user();
            $users = $this->getUserInsideNode($auth);
            $config = $this->config();
            $config['method'] = 'update';
            return view("backend.{$this->namespace}.save", compact(
                'model',
                'auth',
                'users',
                'config',
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

    public function getUserInsideNode($auth){
        $officer_level = 5;
        $users = User::where('lft', '>', $auth->lft)
                ->where('rgt', '<', $auth->rgt)
                ->with('user_catalogues') 
                ->whereHas('user_catalogues', function ($query) use ($auth, $officer_level) {
                    $query->where('level', '>', $auth->user_catalogues->level)
                    ->where('level', '<', $officer_level); 
                })
                ->get();
        return $users;
    }

    public function teams(Request $request , int $level){
        try {

            $date = now()->format('m/Y');

            $monthCurrent = \Carbon\Carbon::createFromFormat('m/Y', $date);

            $auth = Auth::user();

            $delegator_id = Delegation::where('delegate_id', $auth->id)->first()->delegator_id;

            $delegator = $this->userService->findById($delegator_id);

            $currentUserCatalogueDelegator = $delegator->user_catalogues;

            $listSubordinate = $this->userCatalogueService->listSubordinate($auth,$currentUserCatalogueDelegator);

            $currentUserPosition = $currentUserCatalogueDelegator->name;

            $currentUserLevel = $currentUserCatalogueDelegator->level;

            $isDeputyTeamLeader = $currentUserLevel == 4;
            
            $records = match ($level) {
                5 => $this->getCongChucInsideNodeEvaluation($request, $level, $monthCurrent, $delegator),
                default => $this->getInsideNodeEvaluation($request, $level, $monthCurrent, $delegator),
            };

            if (!is_null($records)) {
                
                $records->load(['tasks', 'statuses', 'users.user_catalogues']);

                foreach ($records as $record) {
                    $record->pointForCurrentUser = $record->statuses()->where('user_id', $delegator->id)->first()?->pivot->point;
                    $record->selfEvaluation = $record->statuses()->where('user_id', $record->user_id)->first()?->pivot->status_id;
                    $currentUserEvaluation = $record->statuses()
                        ->where('user_id', $delegator->id)
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

                            if ($evaluatorId == $delegator->id || $evaluatorId == $record->user_id) {
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

            $userByLevel = $this->userService->getUserByLevel($level);

            $allPositionsData = [];

            $hasCurrentUserEvaluated = false;
            
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
                'level' => $level
            ];

            $data = $this->getData();

            extract($data);

            $template = ($level != self::CANBO_LEVEL) ? "backend.{$this->namespace}.team.teamSuperior" : "backend.{$this->namespace}.team.team";

            return view($template, compact(
                'auth',
                'records',
                'delegator',
                'config',
                'allPositionsData',
                'isDeputyTeamLeader',
                'listSubordinate',
                'userByLevel',
                'currentUserCatalogueDelegator',
                ...array_keys($data),
            ));

        } catch (\Throwable $th) {
            dd($th);
        }
    }

    protected function getData(): array{
        return [
            'statuses' => $this->statusService->all(),
        ];
    }

    public function getCongChucInsideNodeEvaluation($request, $level, $monthCurrent = null, $delegator){


        $delegator->load(['subordinates']);

        $subordinateIds = [];

        $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();

        $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();

        if($delegator->user_catalogues->level < 4){
            $request->merge([
                'lft' => ['gt' => $delegator->lft],
                'rgt' => ['lt' => $delegator->rgt],
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
            if($delegator->user_catalogues->level == 4){
                $subordinateIds = $delegator->subordinates()->get()->pluck('id')->toArray();
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

        if ($request->has('team_id') && $request->team_id != 0) {
            $relationFilter['users.teams'] = [
                'id' => [
                    'in' => 'id|' . $request->team_id
                ]
            ];
            $relationFilter['users.user_catalogues'] = [
                'level' => [
                    'eq' => 5
                ]
            ];
        }

        if ($request->has('vice_id') && $request->vice_id != 0 && $request->user_id == 0) {
            $subordinateIds = DB::table('user_subordinate')
            ->where('manager_id', $request->vice_id)
            ->pluck('subordinate_id')
            ->toArray();
            $relationFilter = [
                'users' => [
                    'user_id' => [
                        'in' => 'user_id|' . implode(',', $subordinateIds)
                    ]
                ]
            ];
        }

        if($request->user != 0 && $request->vice_id != 0){
            $relationFilter = [
                'users' => [
                    'user_id' => [
                        'in' => 'user_id|' . implode(',', $userId)
                    ]
                ]
            ];
        }

        $evaluationRequest->merge([
            'due_date' => [
                'gte' => $startOfMonth,
                'lte' => $endOfMonth
            ],
            'sort' => 'due_date,desc',
            'relationFilter' => $relationFilter
        ]);

        if($request->has('due_date') && $request->due_date != '' && $request->due_date['eq'] != null){
            $evaluationRequest->merge([
                'due_date' => $request->due_date,
            ]);
        }

        $evaluations = $this->evaluationService->paginate($evaluationRequest);

        return $evaluations;
    }

    public function getInsideNodeEvaluation($request, $level, $monthCurrent = null, $delegator)
    {
    
        $userIds = [];

        $startOfMonth = $monthCurrent->copy()->startOfMonth()->toDateTimeString();

        $endOfMonth = $monthCurrent->copy()->endOfMonth()->toDateTimeString();

        $request->merge([
            'lft' => ['gt' => $delegator->lft],
            'rgt' => ['lt' => $delegator->rgt],
            'relationFilter' => ['user_catalogues' => ['level' => ['eq' => $level]]], 
            'type' => 'all'
        ]);

        $users = $this->userService->paginate($request);

        if (!is_null($users) && count($users)) {
            $userIds = $users->pluck('id')->toArray();
        }
    
        if (empty($userIds)) {
            return null;
        }

        $userId = [];

        if($request->user_id){
            $userId = [$request->user_id];
        }else{
            $userId = $userIds;
        }

        $evaluationRequest = new CustomRequest();

        $relationFilter = [
            'users' => [
                'user_id' => [
                    'in' => 'user_id|' . implode(',', $userId)
                ]
            ]
        ];

        if ($request->has('team_id') && $request->team_id != 0) {
            $relationFilter['users.teams'] = [
                'id' => [
                    'in' => 'id|' . $request->team_id
                ]
            ];
        }

        if ($request->has('deputy_id') && $request->deputy_id != 0) {
            $deputy_id = $request->deputy_id;
            $user = User::where('id', $deputy_id)->first();
            $vicers = User::where('parent_id', $user->id)->pluck('id')->toArray();
            $relationFilter = [
                'users' => [
                    'user_id' => [
                        'in' => 'user_id|' . implode(',', $vicers)
                    ]
                ]
            ];
        }

        if($request->user_id != 0 && $request->deputy_id != 0){
            $relationFilter = [
                'users' => [
                    'user_id' => [
                        'in' => 'user_id|' . implode(',', $userId)
                    ]
                ]
            ];
        }

        $evaluationRequest->merge([
            'due_date' => [
                'gte' => $startOfMonth,
                'lte' => $endOfMonth
            ],
            'relationFilter' => $relationFilter
        ]);

        if($request->has('due_date') && $request->due_date['eq'] != null){
            $evaluationRequest->merge([
                'due_date' => $request->due_date
            ]);
        }

        $evaluations = $this->evaluationService->paginate($evaluationRequest);

        return $evaluations;
    }
}   