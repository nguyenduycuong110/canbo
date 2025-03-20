<?php 
namespace App\Repositories\Area;
use App\Repositories\BaseRepository;
use App\Models\District;

class DistrictRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        District $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

}