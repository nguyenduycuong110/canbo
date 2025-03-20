<?php  
namespace App\Services\Impl\Unit;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Unit\UnitServiceInterface;
use App\Repositories\Unit\UnitRepository;

class UnitService extends BaseService implements UnitServiceInterface{

    protected $repository;

    public function __construct(
        UnitRepository $repository
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