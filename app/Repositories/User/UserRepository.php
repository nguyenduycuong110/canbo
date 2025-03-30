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

    public function getUserInNodeLowerThanEqualLevel4($currentUser){
        return $this->model->where('lft', '>=', $currentUser->lft)->where('rgt', '<=', $currentUser->rgt)->where('level', '<=', 4)->orderBy('lft', 'asc')->get();
    }

    public function findByField($field, $value){
        return $this->model->where($field, $value)->get();
    }

    public function findWhereIn($field, $in){
        return $this->model->whereIn($field, $in)->get();
    }

    public function getUserByLevel($level){
        return $this->model->whereHas('user_catalogues', function($subQuery) use($level){
            $subQuery->where('level', $level);
        })->get();
    }

    public function updatePassword($payload = [], $id){
        return $this->model->where('id', $id)->update($payload);
    }
    

}