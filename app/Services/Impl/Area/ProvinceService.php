<?php  
namespace App\Services\Impl\Area;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Area\ProvinceServiceInterface;
use App\Repositories\Area\ProvinceRepository;

class ProvinceService extends BaseService implements ProvinceServiceInterface{

    protected $repository;

    public function __construct(
        ProvinceRepository $repository
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