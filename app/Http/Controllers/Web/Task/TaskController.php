<?php 
namespace App\Http\Controllers\Web\Task;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Task\Task\StoreRequest;
use App\Http\Requests\Task\Task\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use App\Services\Interfaces\Task\TaskServiceInterface as TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TaskController extends BaseController{

    protected $namespace = 'task';
    protected $route = 'tasks';

    protected $service;


    public function __construct(
        TaskService $service
    )
    {
        $this->service = $service;
        parent::__construct($service);
    }

    public function index(Request $request): View | RedirectResponse{
        try {
            $auth = Auth::user();
            $request->merge([
               'user_id' => ['eq' => $auth->id]
            ]);
            $records = $this->service->paginate($request);
            $config = $this->config();
            $config['model'] = Str::studly(Str::singular($this->route));
            $data = $this->getData();
            extract($data);
            return view("backend.{$this->namespace}.index", compact(
                'auth',
                'records',
                'config',
                ...array_keys($data)
            ));
        } catch (\Throwable $th) {
            dd($th);
            return $this->handleWebLogException($th);
        }
    }

    public function store(StoreRequest $request): RedirectResponse{
        return $this->baseSave($request);
    }
    public function update(UpdateRequest $request, int $id){
        return $this->baseSave($request, $id);
    }


}   