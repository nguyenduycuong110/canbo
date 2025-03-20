<?php  
namespace App\Services\Impl\Area;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Area\DistrictServiceInterface;
use App\Repositories\Area\DistrictRepository;

class DistrictService extends BaseService implements DistrictServiceInterface{

    protected $repository;

    public function __construct(
        DistrictRepository $repository
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