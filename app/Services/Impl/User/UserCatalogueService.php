<?php  
namespace App\Services\Impl\User;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\User\UserCatalogueServiceInterface;
use App\Repositories\User\UserCatalogueRepository;

class UserCatalogueService extends BaseService implements UserCatalogueServiceInterface{

    protected $repository;

    protected $simpleFilter = ['level'];

    public function __construct(
        UserCatalogueRepository $repository
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
   

}