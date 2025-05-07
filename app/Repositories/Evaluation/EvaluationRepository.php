<?php 
namespace App\Repositories\Evaluation;
use App\Repositories\BaseRepository;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class EvaluationRepository extends  BaseRepository{

    protected $model;

    public function __construct(
        Evaluation $model
    )
    {
        $this->model = $model;    
        parent::__construct($model);
    }

    public function updateLockByUserEvaluate(int $id){
        DB::table('evaluation_status')->where('evaluation_id', $id)->update(['lock' => 1]);
    }

    
    public function findByCondition($user_id, $date){
        return $this->model->where('user_id', $user_id)->whereDate('start_date', $date)->get();
    }

    public function getEvaluationsByUserIdsAndMonth(array $usersId, Carbon $month, $chunkSize = 500, callable  $callback)
    {
        return $this->model
            ->whereIn('user_id', $usersId)
            ->whereBetween('start_date', [
                $month->copy()->startOfMonth()->toDateTimeString(),
                $month->copy()->endOfMonth()->toDateTimeString()
            ])
            ->with(['users.user_catalogues']) // Load quan heej ra
            ->chunk($chunkSize, $callback);
    }


}