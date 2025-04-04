<?php 
namespace App\Repositories\Unit;
use App\Repositories\BaseRepository;
use App\Models\Unit;

class UnitRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Unit $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function unitPublish(){
        return $this->model->where('publish', 2)->get();
    }

}