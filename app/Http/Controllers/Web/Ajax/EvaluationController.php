<?php

namespace App\Http\Controllers\Web\Ajax;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;
use App\Exports\EvaluationExport;
use App\Exports\Pdf\PdfEvaluationExport;
use App\Services\Interfaces\Statistic\StatisticServiceInterface as StatisticService;
use Illuminate\Support\Carbon;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use App\Exports\LeaderEvaluationExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\MonthRateExport;
use App\Exports\MonthRankExport;
use App\Repositories\User\UserRepository;
use Illuminate\Support\Facades\Log;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class EvaluationController extends BaseController
{
    use Loggable;

    protected $evaluationService;
    protected $statisticService;
    protected $userService;
    protected $userRepository;

    public function __construct(
        EvaluationService $evaluationService,
        StatisticService $statisticService,
        UserService $userService,
        UserRepository $userRepository,

    ){
        $this->evaluationService = $evaluationService;
        $this->statisticService = $statisticService;
        $this->userService = $userService;
        $this->userRepository = $userRepository;
    }


    public function evaluate(Request $request, $id){
        try {
            $response = $this->evaluationService->evaluate($request, $id);
            return response()->json(['flag' => $response]); 
        }  catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function getDepartment(Request $request){
        try {
            $response = $this->evaluationService->getDepartment($request, 'month');
            return response()->json(['response' => $response]); 
        }  catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function getDepartmentEvaluationHistory(Request $request){
        try {
            $response = $this->evaluationService->getDepartment($request, 'day');
            return response()->json(['response' => $response]); 
        }  catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }
    
    
    public function export(Request $request){
        try {
            $formData = [
                'working_days_in_month' => (int)$request->working_days_in_month ?? 0,
                'leave_days_with_permission' => (int)$request->leave_days_with_permission ?? 0,
                'leave_days_without_permission' => (int)$request->leave_days_without_permission ?? 0,
                'violation_count' => (int)$request->violation_count ?? 0,
                'violation_behavior' => $request->violation_behavior ?? '',
                'disciplinary_action' => $request->disciplinary_action ?? '',
                'user_id' => $request->user_id,
            ];


            $user = $this->userService->findById($request->user_id);
            $userLevel = $user->user_catalogues->level;
        
            if($request->dateType === 'day'){
                $evaluationList = $this->evaluationService->getDepartment($request, 'day');
                $formData['month'] = Carbon::createFromFormat('d/m/Y', $request->date)->startOfDay();
            }else if($request->dateType === 'month'){
                $evaluationList = $this->evaluationService->getDepartment($request, 'month');
                $formData['month'] = Carbon::createFromFormat('m/Y', $request->date)->startOfMonth();
                $this->statisticService->createOrUpdate($formData);
            }
            
            //Khoi tao lich su export data de phuc vu cho luc in thong ke chi tiet
            

            // dd($evaluationList->evaluations);
            if($request->exportType === 'excel'){
                if($userLevel == 5){
                    $export = new EvaluationExport($evaluationList, $request->date, $formData);
                }else{
                    $export = new LeaderEvaluationExport($evaluationList, $request->date, $formData);
                }

                $temp_file = $export->export();
                $date = str_replace('/', '_', $request->date);
                // return response()->download($temp_file, 'evaluation_report_' . $request->date . '_.xlsx')->deleteFileAfterSend(true);
                $filename = "evaluation_report_{$date}_{$evaluationList->account}_{$request->dateType}";
                return response()->json([
                    'status' => 'success',
                    'file_url' => url('temp/' . basename($temp_file)), // URL của file tạm thời
                    'filename' => $filename,
                ]);
            }else if($request->exportType === 'pdf'){
                $export = new PdfEvaluationExport($evaluationList, $request->date, $formData);
                $temp_file = $export->export();
                $date = str_replace('/', '_', $request->date);
                $filename = "pdf_evaluation_report_{$date}_{$evaluationList->account}_{$request->dateType}.pdf";

                return response()->json([
                    'status' => 'success',
                    'file_url' => url('temp/' . basename($temp_file)), // URL của file tạm thời
                    'filename' => $filename,
                ]);
            }
            return response()->json(['status' => 'error', 'message' => 'Invalid export type'], 400);
           
        } catch (\Throwable $th) {
            dd($th);
        }
    }
    
    public function exportHistory(Request $request){
        try {
            
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
            }

            $monthInput = $request->month ?? now()->format('m/Y');

            $month = Carbon::createFromFormat('m/Y', $monthInput)->startOfMonth();

            $users = $this->userService->getUserInNode($currentUser);
            
            $userIds = $users->pluck('id')->toArray();

            $evaluations = $this->evaluationService->getEvaluationsByUserIdsAndMonth($userIds, $month);

            // Tính xếp loại cho từng người dùng
            $ratedUsers = [];
            foreach ($users as $user) {
                $rating = $this->calculateUserRating($user, $month, $evaluations);
                
                $statistic = $user->statistics->where('month', $month->format('Y-m-d'))->first();

                $ratedUsers[] = [
                    'user' => $user,
                    'working_days' => $statistic ? $statistic->working_days_in_month : 0,
                    'leave_days' => $statistic ? $statistic->leave_days_with_permission : 0,
                    'violation_count' => $statistic ? $statistic->violation_count : 0,
                    'disciplinary_action' => $statistic ? $statistic->disciplinary_action : '',
                    'self_rating' => $rating['self_rating'],
                    'completion_percentage' => $rating['completion_percentage'],
                    'final_rating' => $rating['final_rating'],
                    'totalTask' => $rating['totalTask']
                ];
            }
            
            $export = new MonthRateExport($ratedUsers, $monthInput);
            $temp_file = $export->export();
            $filename = "excel_rating_report_{$monthInput}.xlsx";

            return response()->json([
                'status' => 'success',
                'file_url' => url('temp/' . basename($temp_file)), // URL của file tạm thời
                'filename' => $filename,
            ]);
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            dd($th);
        }
    }

    private function calculateUserRating($user, $month, $evaluations)
    {
        // Khởi tạo biến kết quả
        $selfRating = null;
        $subordinateRating = null; // Khai báo mặc định
        $completionPercentage = 0;
        $finalRating = 'D';

        // Lấy statistic của user trong tháng
        $statistic = $user->statistics->where('month', $month->format('Y-m-d'))->first();

        // Sử dụng $month cố định
        $evaluationMonth = $month;

        // Bước 1: Lấy evaluations của user trong tháng
        $userEvaluations = $evaluations->filter(function ($evaluation) use ($user, $evaluationMonth) {
            return $evaluation->user_id == $user->id &&
                Carbon::parse($evaluation->created_at)->format('m/Y') == $evaluationMonth->format('m/Y');
        });

        // Bước 2: Tính totalTasks
        $totalTasks = $userEvaluations->count();

        // Bước 3: Tính completion_percentage và tự đánh giá
        $level3And4Tasks = 0;
        $level4Tasks = 0;
        $level3Tasks = 0;
        $level2Tasks = 0;
        $level1Tasks = 0;

        foreach ($userEvaluations as $evaluation) {

            $statuses = $evaluation->statuses;

            $finalStatus = null;
            $selfStatus = null;

            foreach ($statuses as $status) {
                $lock = $status->pivot->lock ?? 1;
                if ($lock == 0) {
                    $finalStatus = $status;
                    break;
                }
                if ($status->pivot->user_id == $user->id) {
                    $selfStatus = $status;
                }
            }

            $effectiveStatus = $finalStatus ?? $selfStatus;
            $statusLevel = $effectiveStatus ? ($effectiveStatus->level ?? 1) : 1;

            if ($statusLevel == 4) {
                $level4Tasks += 1;
                $level3And4Tasks += 1;
            } elseif ($statusLevel == 3) {
                $level3Tasks += 1;
                $level3And4Tasks += 1;
            } elseif ($statusLevel == 2) {
                $level2Tasks += 1;
            } elseif ($statusLevel == 1) {
                $level1Tasks += 1;
            }
        }

        $completionPercentage = $totalTasks > 0 ? ($level3And4Tasks / $totalTasks) * 100 : 0;

        // Bước 4: Kiểm tra kỷ luật
        if ($statistic && (!empty($statistic->disciplinary_action) && $statistic->disciplinary_action !== '0')) {
            return [
                'self_rating' => 'D',
                'subordinate_rating' => null,
                'completion_percentage' => round($completionPercentage, 2),
                'final_rating' => 'D',
                'totalTask' => $totalTasks,
            ];
        }

        // Bước 5: Tính tự đánh giá
        $level4Percentage = $totalTasks > 0 ? ($level4Tasks / $totalTasks) * 100 : 0;
        $level3Percentage = $totalTasks > 0 ? (($level3Tasks + $level4Tasks) / $totalTasks) * 100 : 0;
        $level2Percentage = $totalTasks > 0 ? ($level2Tasks / $totalTasks) * 100 : 0;
        $level1Percentage = $totalTasks > 0 ? ($level1Tasks / $totalTasks) * 100 : 0;

        if ($level3Percentage == 100 && $level4Percentage >= 50 || $level2Percentage == 100) {
            $selfRating = 'A';
        } elseif ($level3Percentage == 100) {
            $selfRating = 'B';
        } elseif ($level2Percentage <= 20) {
            $selfRating = 'C';
        } else {
            $selfRating = 'D';
        }

        if($totalTasks == 0) { $selfRating = ''; }

        // Bước 6: Đánh giá theo cấp dưới
        $user->load('user_catalogues');

        $level = $user->user_catalogues->level ?? 5;

        if ($level < 5) {
            if($totalTasks == 0){
                $finalRating = $this->combineRatings($selfRating, 'Không đánh giá', $totalTasks);
            }else{
                $subordinates = collect();
                if ($level <= 3) {
                    $subordinates = $this->userRepository->findByField('parent_id', $user->id);
                } elseif ($level == 4) {
                    $subordinateIds = DB::table('user_subordinate')
                        ->where('manager_id', $user->id)
                        ->pluck('subordinate_id')
                        ->toArray();

                    Log::info('Subordinate IDs for User', [
                        'user_id' => $user->id,
                        'subordinate_ids' => $subordinateIds,
                    ]);

                    if (!empty($subordinateIds)) {
                        $subordinates = $this->userRepository->findWhereIn('id', $subordinateIds);
                    }
                }

                $subordinateRatings = [];

                foreach ($subordinates as $subordinate) {
                    $subordinateRating = $this->calculateUserRating($subordinate, $month, $evaluations);
                    $subordinateRatings[] = $subordinateRating['final_rating'];
                }

                Log::info('Subordinate Ratings for User', [
                    'user_id' => $user->id,
                    'subordinate_ratings' => $subordinateRatings,
                ]);

                $totalSubordinates = count($subordinateRatings);


                if ($totalSubordinates > 0) {
                    $typeACount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'A'));
                    $typeBCount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'B'));
                    $typeCCount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'C'));
                    $typeDCount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'D'));

                    $typeAPercentage = ($typeACount / $totalSubordinates) * 100;
                    $typeBPercentage = ($typeBCount / $totalSubordinates) * 100;
                    $typeCOrBetterPercentage = (($typeACount + $typeBCount + $typeCCount) / $totalSubordinates) * 100;
                    $typeDPercentage = ($typeDCount / $totalSubordinates) * 100;

                    if ($typeAPercentage >= 70) {
                        $subordinateRating = 'A';
                    } elseif ($typeBPercentage >= 70 && $typeDCount == 0) {
                        $subordinateRating = 'B';
                    } elseif ($typeCOrBetterPercentage >= 70) {
                        $subordinateRating = 'C';
                    } elseif ($typeDPercentage > 30) {
                        $subordinateRating = 'D';
                    } else {
                        $subordinateRating = 'Không đánh giá'; // Giá trị mặc định nếu không thỏa mãn điều kiện nào
                    }
                }
                $finalRating = $this->combineRatings($selfRating, $subordinateRating, $totalTasks);
                Log::info('Final Rating for User', [
                    'user_id' => $user->id,
                    'self_rating' => $selfRating,
                    'subordinate_rating' => $subordinateRating,
                    'total_tasks' => $totalTasks,
                    'final_rating' => $finalRating,
                ]);
            }
        } 
        else {
            $finalRating = ($totalTasks != 0 ) ? $selfRating : 'Không đánh giá';
        }

        return [
            'self_rating' => $selfRating,
            'subordinate_rating' => $subordinateRating,
            'completion_percentage' => round($completionPercentage, 2),
            'final_rating' => $finalRating,
            'totalTask' => $totalTasks,
        ];
    }

    private function combineRatings($selfRating, $subordinateRating, $totalTasks)
    {
        // Nếu không có selfRating, trả về subordinateRating (mặc định là 'D' nếu không có)
        if (!$selfRating) {
            return $subordinateRating ?? '';
        }

        // Nếu không có subordinateRating, trả về selfRating
        if (!$subordinateRating) {
            return $selfRating;
        }

        if($subordinateRating == 'Không đánh giá')
        {
            $selfRating = 'D';
            return $selfRating;
        }

        // Nếu lãnh đạo không có evaluation (totalTasks = 0), lấy theo subordinateRating
        if ($totalTasks == 0) {
            return $subordinateRating;
        }

        // Nếu lãnh đạo có evaluation (totalTasks > 0), lấy theo selfRating
        return $selfRating;
    }

    private function getUser($request, $auth, $level = null){
        $auth = Auth::user();
        $request->merge([
            'lft' => [
                'gte' => $auth->lft
            ],
            'rgt' => [
                'lte' => $auth->rgt
            ],
            'type' => 'all'
        ]);


        return $this->userService->paginate($request);
    }

    public function setPoint(Request $request){
        try {
            $response = $this->evaluationService->setPoint($request);
            return response()->json(['response' => $response]); 
        }  catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }
   
    public function filterOfficerTeam(Request $request){
        $auth = Auth::user();
        $users = [];
        $userIds = [];
        $teamId = $request?->team_id;
        if($auth->user_catalogues->level == 4){
            $vicer = $auth;
            if(!empty($vicer->subordinates)){
                foreach($vicer->subordinates as $item){
                    if($item->teams->id != $teamId || in_array($item->id , $userIds)){
                        continue;
                    }
                    $userIds[] = $item->id;
                    $users[] = $item;
                }
            }
        }else{
            $request->merge([
                'lft' => ['gt' => $auth->lft],
                'rgt' => ['lt' => $auth->rgt],
                'relationFilter' => ['user_catalogues' => ['level' => ['eq' => 4]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
                'type' => 'all'
            ]);
            $vicers = $this->userService->paginate($request);
            foreach($vicers as $k => $vicer){
                if(empty($vicer->subordinates)){
                    continue;
                }
                foreach($vicer->subordinates as $item){
                    if($item->teams->id != $teamId || in_array($item->id , $userIds)){
                        continue;
                    }
                    $userIds[] = $item->id;
                    $users[] = $item;
                }
            }
        }
        $response['users'] = $users;
        return response()->json(['response' => $response]); 
    }

    public function filterViceTeam(Request $request){
        $auth = Auth::user();
        $users = [];
        $userIds = [];
        $request->merge([
            'lft' => ['gt' => $auth->lft],
            'rgt' => ['lt' => $auth->rgt],
            'relationFilter' => ['user_catalogues' => ['level' => ['eq' => 4]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
            'type' => 'all'
        ]);
        $vicers = $this->userService->paginate($request);
        $teamId = $request?->team_id;
        if($vicers){
            foreach($vicers as $k => $vicer){
                if($vicer->teams->id != $teamId || in_array($vicer->id , $userIds)){
                    continue;
                }
                $users[] = $vicer;
            }
        }
        $response['users'] = $users;
        return response()->json(['response' => $response]); 
    }

    public function exportRank(Request $request){
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
            }
            $monthInput = $request->month ?? now()->format('m/Y');
            $month = Carbon::createFromFormat('m/Y', $monthInput)->startOfMonth();
            $users = $this->userService->getUserInNode($currentUser);
            $userIds = $users->pluck('id')->toArray();
            $evaluations = $this->evaluationService->getEvaluationsByUserIdsAndMonth($userIds, $month);
            $ratedUsers = [];
            foreach ($users as $user) { 
                $rating = $this->calculateUserRating($user, $month, $evaluations);
                $statistic = $user->statistics->where('month', $month->format('Y-m-d'))->first();
                $ratedUsers[] = [
                    'user' => $user,
                    'working_days_in_month' => $statistic ? $statistic->working_days_in_month : 0,
                    'working_actual_days_in_month' => $statistic ? ($statistic->working_days_in_month - $statistic->leave_days_with_permission) : 0,
                    'leave_days_with_permission' => $statistic ? $statistic->leave_days_with_permission : 0,
                    'violation_count' => $statistic ? $statistic->violation_count : 0,
                    'disciplinary_action' => $statistic ? $statistic->disciplinary_action : '',
                    'final_rating' => $rating['final_rating'],
                ];
            }
            $export = new MonthRankExport($ratedUsers, $monthInput);
            $temp_file = $export->export();
            $temp_file = $export->export();
            $filename = "Bảng tổng hợp xếp loại tháng {$monthInput}.xlsx";

            return response()->json([
                'status' => 'success',
                'file_url' => url('temp/' . basename($temp_file)), // URL của file tạm thời
                'filename' => $filename,
            ]);
            return response()->download($filePath)->deleteFileAfterSend(true);
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    public function getOfficer(Request $request){
        $users = [];
        $userIds = [];
        $vicer_id = $request?->vice_id;
        $vicer = $this->userService->findById($vicer_id);
        if(!empty($vicer->subordinates)){
            foreach($vicer->subordinates as $item){
                if(in_array($item->id , $userIds)){
                    continue;
                }
                $userIds[] = $item->id;
                $users[] = $item;
            }
        }
        $response['users'] = $users;
        return response()->json(['response' => $response]); 
    }

    public function filterCaptainDeputy(Request $request){
        $deputy_id = $request?->deputy_id;
        $deputy = $this->userService->findById($deputy_id);
        $request->merge([
            'lft' => ['gt' => $deputy->lft],
            'rgt' => ['lt' => $deputy->rgt],
            'relationFilter' => ['user_catalogues' => ['level' => ['eq' => $deputy->user_catalogues->level + 1]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
            'type' => 'all'
        ]);
        $users = $this->userService->paginate($request);
        $response['users'] = $users;
        return response()->json(['response' => $response]); 
    }


}
