<?php 
namespace App\Repositories\Area;
use App\Repositories\BaseRepository;
use App\Models\Ward;

class WardRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Ward $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

}