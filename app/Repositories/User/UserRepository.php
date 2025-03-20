<?php 
namespace App\Repositories\User;
use App\Repositories\BaseRepository;
use App\Models\User;

class UserRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        User $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function getUsersOnBranch($user , $userCatalogueId){
        return $this->model->where('lft', '>=', $user->lft)
            ->where('rgt','<=', $user->rgt)
            ->where('user_catalogue_id','=', $userCatalogueId)->get();
    }

}