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
        $leaderSelfRating = null;    
        $civilServantSelfRating = null;    
        $subordinateRating = null;
        $completionPercentage = 0;
        $finalRating = 'D';
        $isValidEvaluation = false; // Biến kiểm tra phiếu hợp lệ
        $hasLeaderApproval = false; // Biến kiểm tra có phê duyệt từ lãnh đạo cấp trên

        // Lấy statistic của user trong tháng
        $statistic = $user->statistics->where('month', $month->format('Y-m-d'))->first();

        // Sử dụng $month cố định
        $evaluationMonth = $month;

        // Lấy level của user hiện tại
        $user->load('user_catalogues');
        $userLevel = $user->user_catalogues->level ?? 5;

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
        $hasSelfEvaluation = false;
        $leaderEvaluation = false;

        foreach ($userEvaluations as $evaluation) {
            $statuses = $evaluation->statuses;
            $finalStatus = null;
            $selfStatus = null;
            $selfEvaluationId = null;

            if ($evaluation->user_id == $user->id) {
                $selfEvaluationId = $evaluation->id;
                $hasSelfEvaluation = true;
            }

            // Kiểm tra phê duyệt của lãnh đạo (phải trên 2 cấp)
            foreach ($statuses as $status) {
                $lock = $status->pivot->lock ?? 1;
                if ($status->pivot->user_id != $user->id && $lock == 0) {
                    $approver = $this->userRepository->findById($status->pivot->user_id);
                    if ($approver) {
                        $approver->load('user_catalogues');
                        $approverLevel = $approver->user_catalogues->level ?? 5;
                        if ($approverLevel <= ($userLevel - 2)) {
                            $leaderEvaluation = true;
                            $isValidEvaluation = true; // Phiếu hợp lệ
                            $hasLeaderApproval = true; // Có phê duyệt từ lãnh đạo cấp trên
                            break;
                        }
                    }
                }
            }

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

        // Log thông tin kiểm tra tự đánh giá
        Log::info('Kiểm tra tự đánh giá của user', [
            'user_id' => $user->id,
            'total_tasks' => $totalTasks,
            'has_self_evaluation' => $hasSelfEvaluation,
            'is_valid_evaluation' => $isValidEvaluation,
            'has_leader_approval' => $hasLeaderApproval,
        ]);

        // Tính completion percentage
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

        // Tính xếp loại dựa trên dữ liệu
        $selfRating = null;
        if ($totalTasks > 0) {
            if ($level3Percentage == 100 && $level4Percentage >= 50) {
                $selfRating = 'A';
            } elseif ($level3Percentage == 100) {
                $selfRating = 'B';
            } elseif ($level2Percentage <= 20) {
                $selfRating = 'C';
            } else {
                $selfRating = 'D';
            }
        } elseif ($userLevel < 5 && !$hasSelfEvaluation) {
            $selfRating = 'A';
        } elseif ($userLevel == 5 && !$hasSelfEvaluation) {
            $selfRating = 'Không đánh giá';
        }

        // Gán xếp loại phù hợp dựa vào level
        if ($userLevel < 5) {
            $leaderSelfRating = $selfRating;
        } else {
            $civilServantSelfRating = $selfRating;
        }

        if ($userLevel < 5) { // Nếu là lãnh đạo
            // Kiểm tra xem có phê duyệt từ lãnh đạo cấp trên hợp lệ không (level nhỏ hơn ít nhất 2 cấp)
            $hasValidLeaderApproval = false;
            
            $superiorLeaderRatings = []; // Mảng lưu đánh giá của lãnh đạo cấp trên
            
            // Kiểm tra từng phiếu đánh giá
            foreach ($userEvaluations as $evaluation) {
                foreach ($evaluation->statuses as $status) {
                    $lock = $status->pivot->lock ?? 1;
                    if ($status->pivot->user_id != $user->id && $lock == 0) {
                        $approver = $this->userRepository->findById($status->pivot->user_id);
                        if ($approver) {
                            $approver->load('user_catalogues');
                            $approverLevel = $approver->user_catalogues->level ?? 5;
                            if ($approverLevel <= ($userLevel - 2)) {
                                $hasValidLeaderApproval = true;
                                $hasLeaderApproval = true;
                                $isValidEvaluation = true;
                                
                                // Lấy đánh giá cụ thể của lãnh đạo cấp trên
                                $statusLevel = $status->level ?? 1;
                                
                                // Chuyển đổi status level thành xếp loại A, B, C, D
                                $rating = 'D';
                                if ($statusLevel == 4) {
                                    $rating = 'A';
                                } elseif ($statusLevel == 3) {
                                    $rating = 'B';
                                } elseif ($statusLevel == 2) {
                                    $rating = 'C';
                                }
                                
                                $superiorLeaderRatings[] = $rating;
                            }
                        }
                    }
                }
            }
            
            // Ưu tiên 1: Xử lý nếu có phê duyệt từ lãnh đạo cấp trên hợp lệ
            if ($hasValidLeaderApproval) {
                // Nếu có đánh giá từ lãnh đạo cấp trên, sử dụng đánh giá đó
                if (!empty($superiorLeaderRatings)) {
                    // Nếu có nhiều lãnh đạo cấp trên, lấy đánh giá thấp nhất
                    $ratingValues = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];
                    $minRating = min(array_map(function($r) use ($ratingValues) {
                        return $ratingValues[$r] ?? 1;
                    }, $superiorLeaderRatings));
                    
                    $reverseRatingValues = [4 => 'A', 3 => 'B', 2 => 'C', 1 => 'D'];
                    $finalRating = $reverseRatingValues[$minRating];
                    
                    Log::info('Lãnh đạo cấp trên đánh giá', [
                        'user_id' => $user->id,
                        'ratings' => $superiorLeaderRatings,
                        'final_rating' => $finalRating
                    ]);
                } else {
                    // Nếu không tìm thấy đánh giá cụ thể từ lãnh đạo cấp trên (hiếm gặp)
                    $finalRating = 'A';
                }
            } else {
                // Ưu tiên 2: Lấy danh sách lãnh đạo cấp dưới (không cần điều kiện thêm)
                $subordinateLeaders = collect();
                
                if ($userLevel <= 3) {
                    // Lấy cấp dưới trực tiếp
                    $allSubordinates = $this->userRepository->findByField('parent_id', $user->id);
                    // Lọc chỉ lấy lãnh đạo (level < 5)
                    $subordinateLeaders = $allSubordinates->filter(function ($subordinate) {
                        $subordinate->load('user_catalogues');
                        $level = $subordinate->user_catalogues->level ?? 5;
                        return $level < 5;
                    });
                } elseif ($userLevel == 4) {
                    $subordinateIds = DB::table('user_subordinate')
                        ->where('manager_id', $user->id)
                        ->pluck('subordinate_id')
                        ->toArray();
                        
                    Log::info('Subordinate IDs for User', [
                        'user_id' => $user->id,
                        'subordinate_ids' => $subordinateIds,
                    ]);
                    
                    if (!empty($subordinateIds)) {
                        $allSubordinates = $this->userRepository->findWhereIn('id', $subordinateIds);
                        // Lọc chỉ lấy lãnh đạo (level < 5)
                        $subordinateLeaders = $allSubordinates->filter(function ($subordinate) {
                            $subordinate->load('user_catalogues');
                            $level = $subordinate->user_catalogues->level ?? 5;
                            return $level < 5;
                        });
                    }
                }
                
                if ($subordinateLeaders->count() > 0) {
                    $subordinateRatings = [];
                    
                    foreach ($subordinateLeaders as $subordinate) {
                        $result = $this->calculateUserRating($subordinate, $month, $evaluations);
                        
                        // Chỉ lấy những xếp loại hợp lệ
                        if ($result['final_rating'] !== 'Không hợp lệ' && $result['final_rating'] !== 'Không xếp loại') {
                            $subordinateRatings[] = $result['final_rating'];
                        }
                    }
                    
                    Log::info('Đánh giá từ lãnh đạo cấp dưới', [
                        'user_id' => $user->id,
                        'subordinate_leader_ratings' => $subordinateRatings,
                    ]);
                    
                    $totalSubordinateLeaders = count($subordinateRatings);
                    
                    if ($totalSubordinateLeaders > 0) {
                        $typeACount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'A'));
                        $typeDCount = count(array_filter($subordinateRatings, fn($rating) => $rating == 'D'));
                        
                        $typeAPercentage = ($typeACount / $totalSubordinateLeaders) * 100;
                        $typeDPercentage = ($typeDCount / $totalSubordinateLeaders) * 100;
                        $hasDRating = $typeDCount > 0;
                        
                        // Xác định đánh giá dựa trên lãnh đạo cấp dưới
                        if ($typeAPercentage > 70 && !$hasDRating) {
                            $finalRating = 'A';
                        } elseif ($typeAPercentage <= 70 && !$hasDRating) {
                            $finalRating = 'B';
                        } elseif ($hasDRating) {
                            if ($typeDPercentage > 30) {
                                $finalRating = 'D';
                            } else {
                                $finalRating = 'C';
                            }
                        } else {
                            // Fallback nếu không đủ điều kiện
                            $finalRating = $hasSelfEvaluation ? $leaderSelfRating : 'B';
                        }
                    } else {
                        // Không có đánh giá từ lãnh đạo cấp dưới
                        $finalRating = $hasSelfEvaluation ? $leaderSelfRating : 'A';
                    }
                } else {
                    $civilServants = collect();
                    
                    if ($userLevel <= 3) {
                        // Lấy công chức dưới quyền
                        $allSubordinates = $this->userRepository->findByField('parent_id', $user->id);
                        // Lọc chỉ lấy công chức (level = 5)
                        $civilServants = $allSubordinates->filter(function ($subordinate) {
                            $subordinate->load('user_catalogues');
                            $level = $subordinate->user_catalogues->level ?? 5;
                            return $level == 5;
                        });
                    } elseif ($userLevel == 4) {
                        $subordinateIds = DB::table('user_subordinate')
                            ->where('manager_id', $user->id)
                            ->pluck('subordinate_id')
                            ->toArray();
                            
                        if (!empty($subordinateIds)) {
                            $allSubordinates = $this->userRepository->findWhereIn('id', $subordinateIds);
                            // Lọc chỉ lấy công chức (level = 5)
                            $civilServants = $allSubordinates->filter(function ($subordinate) {
                                $subordinate->load('user_catalogues');
                                $level = $subordinate->user_catalogues->level ?? 5;
                                return $level == 5;
                            });
                        }
                    }
                    
                    $civilServantRatings = [];
                    
                    foreach ($civilServants as $civilServant) {
                        $result = $this->calculateUserRating($civilServant, $month, $evaluations);
                        
                        // Chỉ lấy những xếp loại hợp lệ
                        if ($result['final_rating'] !== 'Không hợp lệ' && $result['final_rating'] !== 'Không xếp loại') {
                            $civilServantRatings[] = $result['final_rating'];
                        }
                    }
                    
                    Log::info('Đánh giá từ công chức cho lãnh đạo', [
                        'user_id' => $user->id,
                        'civil_servant_ratings' => $civilServantRatings,
                    ]);
                    
                    $totalCivilServants = count($civilServantRatings);
                    
                    if ($totalCivilServants > 0) {
                        $typeACount = count(array_filter($civilServantRatings, fn($rating) => $rating == 'A'));
                        $typeDCount = count(array_filter($civilServantRatings, fn($rating) => $rating == 'D'));
                        
                        $typeAPercentage = ($typeACount / $totalCivilServants) * 100;
                        $typeDPercentage = ($typeDCount / $totalCivilServants) * 100;
                        $hasDRating = $typeDCount > 0;
                        
                        // Xác định đánh giá dựa trên công chức
                        if ($typeAPercentage > 70 && !$hasDRating) {
                            $finalRating = 'A';
                        } elseif ($typeAPercentage <= 70 && !$hasDRating) {
                            $finalRating = 'B';
                        } elseif ($hasDRating) {
                            if ($typeDPercentage > 30) {
                                $finalRating = 'D';
                            } else {
                                $finalRating = 'C';
                            }
                        } else {
                            // Fallback nếu không đủ điều kiện
                            $finalRating = $hasSelfEvaluation ? $leaderSelfRating : 'A';
                        }
                    } else {
                        // Không có đánh giá từ công chức
                        $finalRating = $hasSelfEvaluation ? $leaderSelfRating : 'A';
                    }
                }
            }
        } else { 
            if ($isValidEvaluation) {
                $finalRating = $civilServantSelfRating;
            } else {
                $finalRating = 'Không xếp loại';
            }
        }

        $selfRating = ($userLevel < 5) ? $leaderSelfRating : $civilServantSelfRating;
        
        return [
            'self_rating' => $selfRating,
            'subordinate_rating' => $subordinateRating,
            'completion_percentage' => round($completionPercentage, 2),
            'final_rating' => $finalRating,
            'totalTask' => $totalTasks,
        ];
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
