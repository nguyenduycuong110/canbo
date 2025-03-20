<?php 
namespace App\Repositories\Area;
use App\Repositories\BaseRepository;
use App\Models\Province;

class ProvinceRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Province $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

}