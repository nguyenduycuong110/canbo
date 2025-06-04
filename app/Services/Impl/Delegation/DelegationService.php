<?php  
namespace App\Services\Impl\Delegation;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Delegation\DelegationServiceInterface;
use App\Repositories\Delegation\DelegationRepository;
use Illuminate\Support\Carbon;

class DelegationService extends BaseService implements DelegationServiceInterface{

    protected $repository;

    protected $simpleFilter = ['delegator_id']; // hook

    public function __construct(
        DelegationRepository $repository
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
        $startDate = $request?->start_date;
        $endDate = $request?->end_date;
        $this->modelData = $request->only($fillable);
        $this->modelData['start_date'] = !is_null($startDate) 
        ? Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d') 
        : null;
        $this->modelData['end_date'] = !is_null($endDate) 
            ? Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d') 
            : null;
        return $this;
    }
   

}