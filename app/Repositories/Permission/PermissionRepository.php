<?php 
namespace App\Repositories\Permission;
use App\Repositories\BaseRepository;
use App\Models\Permission;

class PermissionRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Permission $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function findByName(string $permissionName = ''){
        return $this->model->where('name', $permissionName)->first();
    }
    

}