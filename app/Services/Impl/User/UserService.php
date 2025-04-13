<?php  
namespace App\Services\Impl\User;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\User\UserServiceInterface;
use App\Repositories\User\UserRepository;
use App\Classes\Nestedsetbie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService implements UserServiceInterface{

    protected $repository;
    protected $route = 'users';
    protected $nestedset;

    protected $fieldSearchs = ['name','account'];

    protected $sort = ['lft', 'asc'];

    protected $complexFilter = ['rgt' , 'lft', 'user_catalogue_id']; 

    protected $simpleFilter = ['level','team_id'];

    protected $with = ['evaluations'];

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

    public function findByIds($ids){
        return $this->repository->findByIds($ids);
    }

    public function getUserInNode($currentUser){
        
        $userLevel1To4 = $this->repository->getUserInNodeLowerThanEqualLevel4($currentUser);

        $allUser = $userLevel1To4;

        $level4User = $userLevel1To4->filter(function ($user) {
            return $user->user_catalogues->level == 4;
        });

        $level5User = collect();

        if(!is_null($level4User)){
            foreach($level4User as $key => $val){
                $val->load(['subordinates']);
                $subordinates = $val->subordinates()->get();
                $level5User = $level5User->merge($subordinates);
            }
        }

        $allUsers = $allUser->merge($level5User);

        return $allUsers;

    }

    public function getUserByLevel($level){
        return $this->repository->getUserByLevel($level);
    }

    public function updatePassword($request , $id){
        $payload = [
            'password' => Hash::make($request->password),
        ];
        return $this->repository->updatePassword($payload, $id);
    }
    
    public function updateProfile($request , $id){
        $payload = $request->all();
        $payload['password'] = $request->password ?? Auth::user()->password;
        return $this->repository->update($id, $payload);
    }


}