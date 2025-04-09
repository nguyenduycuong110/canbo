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
use Illuminate\Support\Facades\Log;
use App\Models\Evaluation;
use DateTime;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Carbon;

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
        $startDate = $request?->start_date;
        $dueDate = $request?->due_date;
        $this->modelData = $request->only($fillable);
        $this->modelData['user_id'] = Auth::id();
        $this->modelData['start_date'] = !is_null($startDate) 
        ? Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d') 
        : null;
        $this->modelData['due_date'] = !is_null($dueDate) 
            ? Carbon::createFromFormat('d/m/Y', $dueDate)->format('Y-m-d') 
            : null;
        if($request->hasFile('file')){
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('/userfiles/file');
            $file->move($destinationPath, $fileName);
            $this->modelData['file'] = '/userfiles/file/' . $fileName;
        }
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


            $now = now();
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
                    'lock' => 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            );
            DB::commit();
            return true;
        } catch (\Throwable $th) {
           return false;
        }
    }

    public function getDepartment($request, $dateType = 'month')
    {
        try {
            $user_id = $request->user_id;
            $date = $request->date;

            // Xử lý khoảng thời gian theo tháng hoặc ngày
            if ($dateType === 'month') {
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
            } else if ($dateType === 'day') {
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

            // Lấy thông tin user và các quan hệ
            $user = $this->userRepository->findById($user_id);
            $user->load('user_catalogues');
            $user->load('teams');
            $user->load('units');
            $user->load('statistics');
            if($user->statistics && $dateType == 'month'){
                foreach($user->statistics as $k => $item){
                    $dateTime = new DateTime($item->month);
                    $month = $dateTime->format('Y-m-d H:i:s');
                    if($month < $startOfMonth || $month > $endOfMonth){
                        unset($user->statistics[$k]);
                    }
                }
            }
            // Lấy danh sách đánh giá
            $evaluations = $this->paginate($request);

            $evaluationList = is_array($evaluations) ? $evaluations : (isset($evaluations->data) ? $evaluations->data : $evaluations);

            if (!empty($evaluationList)) {
                foreach ($evaluationList as $evaluation) {
                    $evaluation->start_date = !is_null($evaluation->start_date) ? convertDateTime($evaluation->start_date, 'd-m-Y', 'Y-m-d') : '';
                    $evaluation->due_date = !is_null($evaluation->due_date) ? convertDateTime($evaluation->due_date, 'd-m-Y', 'Y-m-d') : '';
                    $selfAssessment = []; // Tự đánh giá của công chức
                    $deputyAssessment = []; // Đánh giá của lãnh đạo trực tiếp (dựa trên level)
                    $leadershipApproval = []; // Đánh giá của lãnh đạo cấp cao nhất

                    // Lấy tất cả statuses của evaluation
                    $statuses = $evaluation->statuses;

                    // Log để kiểm tra dữ liệu gốc của $statuses
                    Log::info('Raw Statuses:', [
                        'evaluation_id' => $evaluation->id,
                        'statuses' => $statuses->toArray(),
                    ]);

                    // 1. Tự đánh giá của công chức
                    $selfAssessmentStatus = $statuses->firstWhere('pivot.user_id', $evaluation->user_id);
                    if ($selfAssessmentStatus) {
                        $selfAssessment = [
                            'infoUser' => $this->userRepository->findById($evaluation->user_id),
                            'infoStatus' => $this->statusRepository->findById($selfAssessmentStatus->pivot->status_id),
                        ];
                    }

                    // Lấy level của người tự đánh giá
                    $selfUser = $this->userRepository->findById($evaluation->user_id);
                    $selfUser->load('user_catalogues');
                    $selfLevel = $selfUser->user_catalogues->level ?? null;

                    // 2. Đánh giá của lãnh đạo trực tiếp (người có level cao hơn người tự đánh giá 1 cấp)
                    $directLeaderStatuses = $statuses->filter(function ($status) use ($evaluation, $selfLevel) {
                        $userId = $status->pivot->user_id;
                        if ($userId == $evaluation->user_id) {
                            return false; // Bỏ qua tự đánh giá
                        }
                        $user = $this->userRepository->findById($userId);
                        $user->load('user_catalogues');
                        $catalogue = $user->user_catalogues;
                        if (!$catalogue || !isset($catalogue->level)) {
                            return false;
                        }
                        // Lấy người có level cao hơn người tự đánh giá 1 cấp
                        return $catalogue->level == ($selfLevel - 1);
                    })->sortByDesc('pivot.updated_at');

                    if ($directLeaderStatuses->isNotEmpty()) {
                        $latestDirectLeaderStatus = $directLeaderStatuses->first();
                        $directLeaderUserId = $latestDirectLeaderStatus->pivot->user_id;
                        $deputyAssessment = [
                            'infoUser' => $this->userRepository->findById($directLeaderUserId),
                            'infoStatus' => $this->statusRepository->findById($latestDirectLeaderStatus->pivot->status_id),
                            'point' => $latestDirectLeaderStatus->pivot->point
                        ];
                    } else {
                        Log::warning('No direct leader found for evaluation_id: ' . $evaluation->id . ' with level: ' . ($selfLevel - 1));
                    }

                    // 3. Đánh giá của lãnh đạo cấp cao nhất (ưu tiên lock = 0 và updated_at mới nhất)
                    $leadershipStatuses = $statuses->filter(function ($status) use ($evaluation) {
                        $userId = $status->pivot->user_id;
                        if ($userId == $evaluation->user_id) {
                            return false; // Bỏ qua tự đánh giá
                        }
                        $user = $this->userRepository->findById($userId);
                        $user->load('user_catalogues');
                        $status->user = $user; // Gán user vào status để sử dụng sau
                        return true; // Lấy tất cả các lãnh đạo còn lại
                    });

                    // Ưu tiên bản ghi có lock = 0 và updated_at mới nhất
                    $leadershipStatuses = $leadershipStatuses->sortByDesc('pivot.updated_at') // Sắp xếp theo updated_at (mới nhất trước)
                        ->sortByDesc(function ($status) {
                            return $status->pivot->lock == 0 ? 1 : 0; // Ưu tiên lock = 0
                        });


                    // Log để kiểm tra leadershipStatuses
                    Log::info('Leadership Statuses after sorting:', [
                        'evaluation_id' => $evaluation->id,
                        'statuses' => $leadershipStatuses->map(function ($status) {
                            return [
                                'user_id' => $status->pivot->user_id,
                                'name' => $status->user->name,
                                'level' => $status->user->user_catalogues->level ?? 'N/A',
                                'lock' => $status->pivot->lock,
                                'updated_at' => $status->pivot->updated_at,
                            ];
                        })->toArray(),
                    ]);


                    if ($leadershipStatuses->isNotEmpty()) {
                        // Lấy bản ghi đầu tiên (có updated_at mới nhất và lock = 0)
                        $highestLeaderStatus = $leadershipStatuses->first();

                        // Nếu có nhiều bản ghi cùng updated_at, sắp xếp lại theo level
                        $latestStatuses = $leadershipStatuses->filter(function ($status) use ($highestLeaderStatus) {
                            return $status->pivot->updated_at == $highestLeaderStatus->pivot->updated_at;
                        })->sortBy(function ($status) {
                            return $status->user->user_catalogues->level ?? PHP_INT_MAX; // Sắp xếp theo level (nhỏ nhất trước)
                        });

                        if ($latestStatuses->isNotEmpty()) {
                            $highestLeaderStatus = $latestStatuses->first();
                        }


                        $leaderUserId = $highestLeaderStatus->pivot->user_id;

                        $leadershipApproval = [
                            'infoUser' => $this->userRepository->findById($leaderUserId),
                            'infoStatus' => $this->statusRepository->findById($highestLeaderStatus->pivot->status_id),
                            'point' => $highestLeaderStatus->pivot->point
                        ];

                        // Log lãnh đạo được chọn
                        Log::info('Selected Leader:', [
                            'user_id' => $leaderUserId,
                            'name' => $leadershipApproval['infoUser']->name,
                            'level' => $leadershipApproval['infoUser']->user_catalogues->level ?? 'N/A',
                        ]);
                    }

                    // Gán dữ liệu vào evaluation
                    $evaluation->selfAssessment = $selfAssessment;
                    $evaluation->deputyAssessment = $deputyAssessment;
                    $evaluation->leadershipApproval = (count($deputyAssessment) && $deputyAssessment['infoUser']->user_catalogues->level == $leadershipApproval['infoUser']->user_catalogues->level) ? '' : $leadershipApproval;
                    
                    // Log dữ liệu cuối cùng
                    Log::info('Evaluation Data:', [
                        'evaluation_id' => $evaluation->id,
                        'selfAssessment' => $selfAssessment,
                        'deputyAssessment' => $deputyAssessment,
                        'leadershipApproval' => $leadershipApproval,
                    ]);
                }
            }
            $user['evaluations'] = $evaluationList;
            return $user;
        } catch (\Throwable $th) {
            Log::error('Error in getDepartment:', ['error' => $th->getMessage()]);
            return false;
        }
    }
    
    public function getEvaluationsByUserIdsAndMonth($usersId, $month){
        return $this->repository->getEvaluationsByUserIdsAndMonth($usersId, $month);
    }
    
    public function setPoint($request){
        try {
            $evaluationUserId = $request->currentUserId;
            $evaluationId = $request->evaluationId;
            $selfEvaluationId = $request->selfEvaluationId;
            $point = $request->point;
            if(!$evaluation = Evaluation::where('id', $evaluationId)->where('user_id', $selfEvaluationId)->first()){
                throw new ModelNotFoundException(Lang::get('message.not_found'));
            }
            $statusEvaluation = $evaluation->statuses()->where('user_id', $evaluationUserId)->first();
            if(is_null($statusEvaluation)){
                return [
                    'code' => 404
                ];
            }
            $range = $statusEvaluation->point;
            list($min, $max) = explode('-', $range);
            $min = (int) $min;
            $max = (int) $max;
            if($point < $min || $point > $max){
                return [
                    'status' => false,
                    'min' => $min,
                    'max' => $max 
                ];
            }
            return $evaluation->statuses()->where('user_id', $evaluationUserId)->update(['evaluation_status.point' => $point]);
        } catch (\Throwable $th) {
            throw $th;
        }
        
    }

}