<?php  
namespace App\Services\Impl\Department;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Department\DepartmentServiceInterface;
use App\Repositories\Department\DepartmentRepository;

class DepartmentService extends BaseService implements DepartmentServiceInterface{

    protected $repository;

    public function __construct(
        DepartmentRepository $repository
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