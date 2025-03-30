<?php  
namespace App\Services\Impl\Statistic;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Statistic\StatisticServiceInterface;
use App\Repositories\Statistic\StatisticRepository;
use Illuminate\Support\Facades\DB;

class StatisticService extends BaseService implements StatisticServiceInterface{

    protected $repository;

    public function __construct(
        StatisticRepository $repository
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
   
    public function createOrUpdate($formData){
        try {
            DB::beginTransaction();
            $this->repository->createOrUpdate(['user_id' => $formData['user_id'], 'month' => $formData['month']], $formData);


            DB::commit();

        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


}