<?php 

namespace App\Http\Controllers\Web\Statistic;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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

    public function index(Request $request): View | RedirectResponse{
        try {
            $users = $this->getUser($request);
            return view("backend.{$this->namespace}.index", compact(
                'users'
            ));
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    
    private function getUser($request){
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