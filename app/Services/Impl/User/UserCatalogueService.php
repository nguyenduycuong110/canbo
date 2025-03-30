<?php  
namespace App\Services\Impl\User;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\User\UserCatalogueServiceInterface;
use App\Repositories\User\UserCatalogueRepository;
use App\Repositories\User\UserRepository;

class UserCatalogueService extends BaseService implements UserCatalogueServiceInterface{

    protected $repository;
    protected $userRepository;

    protected $simpleFilter = ['level'];

    public function __construct(
        UserCatalogueRepository $repository,
        UserRepository $userRepository
    )
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        parent::__construct($repository);
    }

    protected function prepareModelData(Request $request): self
    {
        return $this->initializeBasicData($request);
    }

    private function initializeBasicData(Request $request): self {
        $fillable = $this->repository->getFillable();
        $this->modelData = $request->only($fillable);
        return $this;
    }

    public function listSubordinate($auth ,$currentUserCatalogue){
        $listSubordinate = $this->repository->listSubordinate($currentUserCatalogue);
        $listUser = $this->userRepository->all();
        $temp = [];
        foreach($listSubordinate as $k => $subordinate){
            $temp[] = [
                'id' => $subordinate->id,
                'level' => $subordinate->level,
                'nameSubordinate' => $subordinate->name,
                'users' => []
            ];
        }
        foreach($temp as $k => $v){
            foreach($listUser as $user){
                if($user->user_catalogue_id == $v['id'] && ($user->lft > $auth->lft && $user->rgt < $auth->rgt)){
                    $temp[$k]['users'][] = $user;
                }
            }
        }
        return $temp;
    }
   

}