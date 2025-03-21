<?php  
namespace App\Services\Impl\Evaluation;

use App\Services\BaseService;
use Illuminate\Http\Request;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface;
use App\Repositories\Evaluation\EvaluationRepository;
use App\Repositories\Status\StatusRepository;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluationService extends BaseService implements EvaluationServiceInterface{

    protected $repository;
    protected $userRepository;
    protected $statusRepository;

    protected $with = ['users', 'tasks'];

    protected $simpleFilter = ['user_id']; 

    protected $dateFilter = ['created_at', 'start_date'];

    public function __construct(
        EvaluationRepository $repository,
        UserRepository $userRepository,
        StatusRepository $statusRepository
    )
    {
        $this->repository = $repository;
        $this->userRepository = $userRepository;
        $this->statusRepository = $statusRepository;
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

    protected function beforeSave(?int $id = null): self{
        $user = Auth::user();
        if($id && $user->rgt - $user->lft > 1){
            $this->repository->updateLockByUserEvaluate($id);
        }
        return $this;
    }

    public function evaluate(Request $request, int $id){
        try {
            DB::beginTransaction();
            DB::table('evaluation_status')
            ->where('evaluation_id', $id)
            ->update(['lock' => 1]);
            DB::table('evaluation_status')->updateOrInsert(
                [
                    'evaluation_id' => $id,
                    'user_id' => Auth::id()
                ],
                [
                    'status_id' => $request->status_id,
                    'lock' => 0
                ]
            );
            DB::commit();
            return true;
        } catch (\Throwable $th) {
           return false;
        }
    }

    public function getDepartment($request, $dateType = 'month'){
        try {
            $user_id = $request->user_id;
            $date = $request->date;

            if($dateType === 'month'){
                $inputDate = \Carbon\Carbon::createFromFormat('m/Y', $date);

                $startOfMonth = $inputDate->copy()->startOfMonth()->toDateTimeString();
                $endOfMonth = $inputDate->copy()->endOfMonth()->toDateTimeString();
    
                $request->merge([
                    'user_id' => $user_id,
                    'start_date' => [
                        'gte' => $startOfMonth,
                        'lte' => $endOfMonth 
                    ],
                    'type' => 'all'
                ]);
            }else if($dateType === 'day'){

                $inputDate = \Carbon\Carbon::createFromFormat('d/m/Y', $date);

                $startOfDate = $inputDate->copy()->startOfDay()->toDateTimeString();
                $endOfDate = $inputDate->copy()->endOfDay()->toDateTimeString();
    
                $request->merge([
                    'user_id' => $user_id,
                    'start_date' => [
                        'gte' => $startOfDate,
                        'lte' => $endOfDate 
                    ],
                    'type' => 'all'
                ]);
            }
           

            $user = $this->userRepository->findById($user_id);
            $user->load('user_catalogues');
            $user->load('teams');
            $user->load('units');

            // $evaluations = $this->repository->findByCondition($user_id , $inputDate);
            $evaluations = $this->paginate($request);


            // $evaluations->load('tasks');
            // $evaluations->load('statuses');
            $leadershipApproval = [];
            $assessmentLeader = [];
            if(count($evaluations)){
                foreach($evaluations as $evaluation){
                    foreach($evaluation->statuses as $k  => $v){
                        if($v->pivot->lock == 1) {
                            continue;
                        }
                        $user_id = $v->pivot->user_id;
                        $status_id = $v->pivot->status_id;
                        $userInfo = $this->userRepository->findById($user_id);
                        if($userInfo->parent_id == 0) {
                            $leadershipApproval = [
                                'infoUser' => $userInfo,
                                'infoStatus' => $this->statusRepository->findById($status_id),
                            ];
                        } else {
                            if (!isset($v->assessmentLeader) || 
                                ($userInfo->parent_id < $v->assessmentLeader['infoUser']->parent_id && $userInfo->parent_id != 0)) {
                                $assessmentLeader = [
                                    'infoUser' => $userInfo,
                                    'infoStatus' => $this->statusRepository->findById($status_id),
                                ];
                            }
                        }
                    }
                    $evaluation->assessmentLeader = $assessmentLeader;
                    $evaluation->leadershipApproval = $leadershipApproval;
                }
            }
            $user['evaluations'] = $evaluations;
            return $user;
        } catch (\Throwable $th) {
            dd($th);
           return false;
        }
    }

}