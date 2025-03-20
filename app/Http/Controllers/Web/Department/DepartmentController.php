<?php 
namespace App\Http\Controllers\Web\Department;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Department\Department\StoreRequest;
use App\Http\Requests\Department\Department\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Department\DepartmentServiceInterface as DepartmentService;


class DepartmentController extends BaseController{

    protected $namespace = 'department';
    protected $route = 'departments';

    protected $service;


    public function __construct(
        DepartmentService $service
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