<?php 
namespace App\Repositories\Task;
use App\Repositories\BaseRepository;
use App\Models\Task;
use App\Models\User;

class TaskRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Task $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function getTaskByCondition(array $parentIds = []){
        return $this->model->where('publish', 2)->whereIn('user_id', $parentIds)->get();
    }

    public function getPublishTask(){
        return $this->model->where('publish', 2)->get(); 
    }

}