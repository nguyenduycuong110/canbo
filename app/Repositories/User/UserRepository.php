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
        return $this->model->where('lft', '>=', $currentUser->lft)->with('user_catalogues')->where('rgt', '<=', $currentUser->rgt)->where('level', '<=', 4)->orderBy('level', 'asc')->get()->sortByDesc(function($user){
            return $user->user_catalogues->level ?? 0;
        });
    }

    public function getUserInNodeLowerThanEqualLevel4SortByLevel($currentUser){
        return $this->model->where('lft', '>=', $currentUser->lft)->with('user_catalogues')->where('rgt', '<=', $currentUser->rgt)->where('level', '<=', 4)->orderBy('lft', 'asc')->get()->sortByDesc(function($user){
            return $user->user_catalogues->level ?? 0;
        });
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

    public function getManager($auth, $level){
        return $this->model
            ->where('lft', '>' , $auth->lft)
            ->where('rgt', '<' , $auth->rgt)
            ->whereHas('user_catalogues', function($subQuery) use($level){
                $subQuery->where('level', $level -  1 )->orWhere('level', $level-2);
            })
            ->get();
    }
    


}