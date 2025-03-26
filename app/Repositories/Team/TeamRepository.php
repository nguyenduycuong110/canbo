<?php 
namespace App\Repositories\Team;
use App\Repositories\BaseRepository;
use App\Models\Team;

class TeamRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Team $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function teamPublish(){
        return $this->model->where('publish', 2)->get();
    }

}