<?php 
namespace App\Http\Controllers\Web\Task;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Task\Task\StoreRequest;
use App\Http\Requests\Task\Task\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Task\TaskServiceInterface as TaskService;


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
    public function store(StoreRequest $request): RedirectResponse{
        return $this->baseSave($request);
    }
    public function update(UpdateRequest $request, int $id){
        return $this->baseSave($request, $id);
    }


}   