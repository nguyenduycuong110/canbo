<?php 

namespace App\Http\Controllers\Web\Statistic;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request as CustomRequest;

use App\Http\Controllers\Web\BaseController;

class StatisticController extends BaseController{
    protected $namespace = 'statistic';
    protected $route = 'statistics';
    
    protected $service;
    protected $userService;

    public function __construct(
        EvaluationService $service,
        UserService $userService,
    )
    {
        $this->service = $service;
        $this->userService = $userService;
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
                'level' => $level - 1,  // 4 --> là level của đội phó
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
            // $users = $this->getUser($request, $auth, $level);

            $users = $this->getUserInsideNode($request, $level);
 
            return view($template , compact(
                'users',
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


            return view($template , compact(
                'users',
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
            return view("backend.{$this->namespace}.leader.day", compact(
                'auth',
                'users',
                'level'
            ));
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function leaderStatisticMonth(Request $request, int $level){
        try {
            $auth = Auth::user();
            $users = $this->getUser($request, $auth, $level);
            return view("backend.{$this->namespace}.leader.month", compact(
                'auth',
                'users',
                'level'
            ));
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function exportHistory(Request $request){
        return view("backend.{$this->namespace}.export.index");
    }

    private function getUser($request, $auth, $level = null){
        $auth = Auth::user();
        $request->merge([
            'lft' => [
                'gte' => $auth->lft
            ],
            'rgt' => [
                'lte' => $auth->rgt
            ],
            'relationFilter' => [
                'user_catalogues' => [
                    'level' => ['eq' => $level]
                ]
            ]
        ]);
        return $this->userService->paginate($request);
    }


}