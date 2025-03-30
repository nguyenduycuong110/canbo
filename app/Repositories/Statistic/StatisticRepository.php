<?php 
namespace App\Repositories\Statistic;
use App\Repositories\BaseRepository;
use App\Models\Statistic;

class StatisticRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Statistic $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }


}