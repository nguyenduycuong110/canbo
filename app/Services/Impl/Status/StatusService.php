<?php  
namespace App\Services\Impl\Status;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Status\StatusServiceInterface;
use App\Repositories\Status\StatusRepository;

class StatusService extends BaseService implements StatusServiceInterface{

    protected $repository;

    public function __construct(
        StatusRepository $repository
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