<?php  
namespace App\Services\Impl\User;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\User\UserServiceInterface;
use App\Repositories\User\UserRepository;
use App\Classes\Nestedsetbie;

class UserService extends BaseService implements UserServiceInterface{

    protected $repository;
    protected $route = 'users';
    protected $nestedset;

    protected $sort = ['lft', 'asc'];

    protected $complexFilter = ['rgt' , 'lft'];

    public function __construct(
        UserRepository $repository
    )
    {
        $this->repository = $repository;
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

    protected function afterSave(): self{
        $this->nested();
        return $this;
    }

    private function nested(){
        $this->nestedset = new Nestedsetbie([
            'table' => $this->route,
        ]);
        $this->nestedset();
    }

    public function getUsersOnBranch($user , $userCatalogueId){
        return $this->repository->getUsersOnBranch($user, $userCatalogueId);
    }


}