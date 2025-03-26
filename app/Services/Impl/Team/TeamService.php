<?php  
namespace App\Services\Impl\Team;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Team\TeamServiceInterface;
use App\Repositories\Team\TeamRepository;

class TeamService extends BaseService implements TeamServiceInterface{

    protected $repository;

    public function __construct(
        TeamRepository $repository
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
   
    public function teamPublish(){
        return $this->repository->teamPublish();
    }
}