<?php  
namespace App\Services\Impl\Permission;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Permission\PermissionServiceInterface;
use App\Repositories\Permission\PermissionRepository;
use Illuminate\Support\Facades\Auth;

class PermissionService extends BaseService implements PermissionServiceInterface{

    protected $repository;

    public function __construct(
        PermissionRepository $repository
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
        $this->modelData['user_id'] = Auth::id();
        return $this;
    }
   

}