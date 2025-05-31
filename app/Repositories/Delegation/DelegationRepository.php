<?php 
namespace App\Repositories\Delegation;
use App\Repositories\BaseRepository;
use App\Models\Delegation;

class DelegationRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Delegation $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    

}