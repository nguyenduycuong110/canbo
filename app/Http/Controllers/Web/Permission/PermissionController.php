<?php 
namespace App\Http\Controllers\Web\Permission;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Permission\Permission\StoreRequest;
use App\Http\Requests\Permission\Permission\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Permission\PermissionServiceInterface as PermissionService;


class PermissionController extends BaseController{

    protected $namespace = 'permission';
    protected $route = 'permissions';

    protected $service;


    public function __construct(
        PermissionService $service
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