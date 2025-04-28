<?php 

namespace App\Http\Controllers\Web\Statistic;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use App\Services\Interfaces\Team\TeamServiceInterface as TeamService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Http\Request as CustomRequest;

use App\Http\Controllers\Web\BaseController;

class StatisticController extends BaseController{
    protected $namespace = 'statistic';
    protected $route = 'statistics';
    
    protected $service;
    protected $userService;
    protected $teamService;

    public function __construct(
        EvaluationService $service,
        UserService $userService,
        TeamService $teamService,
    )
    {
        $this->service = $service;
        $this->userService = $userService;
        $this->teamService = $teamService;
        parent::__construct($service);
    }

    public function getUserInsideNode(Request $request, $level){
        /** @var \App\Models\User $auth */
        $auth = Auth::user();
        $auth->load(['subordinates']);
        $subordinateIds = [];
        if($auth->user_catalogues->level < 4){
            $request->merge([
                'lft' => ['gt' => $auth->lft],
                'rgt' => ['lt' => $auth->rgt],
                'relationFilter' => ['user_catalogues' => ['level' => ['lte' => $level - 1]]], // 4 --> là level của đội phó
                'type' => 'all'
            ]);
            $users = $this->userService->paginate($request); // tìm ra đội phó
            if(!is_null($users) && count($users)){
                foreach($users as $key => $deputy){ 
                    $subordinates = $deputy->subordinates()->get()->pluck('id')->toArray();
                    $subordinateIds = array_merge($subordinateIds, $subordinates);
                }
            }
            $subordinateIds = array_unique($subordinateIds); // lấy được id của công chức
        }else if($auth->user_catalogues->level == 4){
            $subordinateIds = $auth->subordinates()->get()->pluck('id')->toArray();
        }else if($auth->user_catalogues->level == 5){
            $subordinateIds = [$auth->id];
        }
        
        return $this->userService->findByIds($subordinateIds);
    }

    public function leaderEvaluationStatisticDay(Request $request, $level){
        try {
            $auth = Auth::user();
            $isLeader = ($auth->rgt - $auth->lft > 1);
            $template =  "backend.{$this->namespace}.department.day";
            $users = $this->getUserInsideNode($request, $level);
            $teams = $this->getTeamInsideNode($users, $auth);
            return view($template , compact(
                'users',
                'teams',
                'auth',
                'level'
            ));


        } catch (\Throwable $th) {
           dd($th);
        }
    }

    public function evaluationStatisticMonth(Request $request, int $level){
        try {
            $auth = Auth::user();
            $isLeader = $auth->user_catalogues->level < 5;
            $template = $isLeader ? 
                "backend.{$this->namespace}.department.leader" : 
                "backend.{$this->namespace}.department.officer";
            
            $users = $this->getUserInsideNode($request, $level);
            $teams = $this->getTeamInsideNode($users, $auth);
            return view($template , compact(
                'users',
                'teams',
                'auth'
            ));
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }

    public function leaderStatisticDay(Request $request, int $level){
        try {
            $auth = Auth::user();
            $users = $this->getUser($request, $auth, $level);
            $teams = ($auth->parent_id == 0) ? $this->teamService->all() : $this->getTeamInsideNode($users, $auth);
            return view("backend.{$this->namespace}.leader.day", compact(
                'auth',
                'users',
                'level',
                'teams'
            ));
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function leaderStatisticMonth(Request $request, int $level){
        try {
            $auth = Auth::user();
            $users = $this->getUser($request, $auth, $level);
            $teams = ($auth->parent_id == 0) ? $this->teamService->all() : $this->getTeamInsideNode($users, $auth);
            return view("backend.{$this->namespace}.leader.month", compact(
                'auth',
                'users',
                'level',
                'teams'
            ));
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function exportHistory(Request $request){
        return view("backend.{$this->namespace}.export.index");
    }

    public function rankQuality(Request $request){
        return view("backend.{$this->namespace}.rank.index");
    }

    private function getUser($request, $auth, $level = null){
        $auth = Auth::user();
        $users = User::where('lft', '>', $auth->lft)
        ->where('rgt', '<', $auth->rgt)
        ->whereHas('user_catalogues', function ($query) use ($level) {
            $query->where('level', $level);
        })
        ->with(['user_catalogues' => function ($query) use ($level) {
            $query->where('level', $level);
        }])
        ->get();
        return $users;
    }
    
    private function getTeamInsideNode($users, $auth){
        $teams = [
            0 => [
                'id' => $auth->teams->id,
                'name' => $auth->teams->name,
            ]
        ];
        $teamIds = [
            0 => $auth->teams->id
        ];
        if(!$users){ 
            return $teams;
        }
        foreach($users as $k => $user){
            $teamId = $user->teams->id;
            if(!in_array($teamId, $teamIds)){
                $teams[] = [
                    'id' => $user->teams->id,
                    'name' => $user->teams->name
                ];
                $teamIds[] = $teamId;
            }
            
        }
        return $teams;
    }

}