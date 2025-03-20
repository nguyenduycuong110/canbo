<?php 
namespace App\Repositories\Evaluation;
use App\Repositories\BaseRepository;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;

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
        return $this->model->where('user_id', $user_id)->whereDate('created_at', $date)->get();
    }

}