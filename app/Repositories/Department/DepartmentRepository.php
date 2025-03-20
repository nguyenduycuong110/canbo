<?php 
namespace App\Repositories\Department;
use App\Repositories\BaseRepository;
use App\Models\Department;

class DepartmentRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Department $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    

}