<?php 

namespace App\Http\Controllers\Web\Statistic;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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

    public function leaderEvaluationStatisticDay(Request $request){
        try {
            $auth = Auth::user();
            $isLeader = ($auth->rgt - $auth->lft > 1);
            $template =  "backend.{$this->namespace}.department.day";
            $users = $this->getUser($request, $auth);

            return view($template , compact(
                'users',
                'auth'
            ));


        } catch (\Throwable $th) {
           dd($th);
        }
    }


    public function evaluationStatisticMonth(Request $request){
        try {
            $auth = Auth::user();
            $isLeader = ($auth->rgt - $auth->lft > 1);
            $template = $isLeader ? 
                "backend.{$this->namespace}.department.leader" : 
                "backend.{$this->namespace}.department.officer";
            $users = $this->getUser($request, $auth);


            return view($template , compact(
                'users',
                'auth'
            ));
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }

    public function exportTeamRating(Request $request){
        try {
            dd(123);
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }

    private function getUser($request, $auth){
        $auth = Auth::user();
        $request->merge([
            'lft' => [
                'gt' => $auth->lft
            ],
            'rgt' => [
                'lt' => $auth->rgt
            ],
        ]);
        return $this->userService->paginate($request);
    }


}