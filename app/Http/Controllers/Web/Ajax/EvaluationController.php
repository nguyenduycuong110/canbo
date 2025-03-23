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

class EvaluationController extends BaseController
{
    use Loggable;

    protected $evaluationService;
    protected $statisticService;
    protected $userService;

    public function __construct(
        EvaluationService $evaluationService,
        StatisticService $statisticService,
        UserService $userService
    ){
        $this->evaluationService = $evaluationService;
        $this->statisticService = $statisticService;
        $this->userService = $userService;
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

            $users = $this->getUser($request, $currentUser);

            $statistics = $this->getStatisticsForTeamMembers($users, $month);
            

            // Tính toán xếp loại cho từng thành viên
            $evaluations = $this->calculateEvaluations($statistics, $month);
            // dd($evaluations);

            $export = new MonthRateExport($evaluations, $monthInput);
            $temp_file = $export->export();
            $filename = "excel_rating_report_{$monthInput}.xlsx";

            return response()->json([
                'status' => 'success',
                'file_url' => url('temp/' . basename($temp_file)), // URL của file tạm thời
                'filename' => $filename,
            ]);
            // Trả về file để tải xuống
            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Throwable $th) {
            dd($th);
        }
    }

    /**
     * Tính toán xếp loại cho từng thành viên
     */
    private function calculateEvaluations($statistics, $month)
    {
        $evaluations = [];
        $currentUser = Auth::user();

        // Lấy danh sách trạng thái từ bảng statuses, bao gồm cột point
        $statuses = DB::table('statuses')->get()->keyBy('id');

        foreach ($statistics as $item) {
            $user = $item->user;
            $stat = $item->statistics;

            // Bỏ qua user hiện tại (lãnh đạo) để tránh trùng lặp
            if ($user->id === $currentUser->id) {
                continue;
            }

            // Lấy dữ liệu trạng thái của user trong tháng từ bảng evaluation_status, sắp xếp theo created_at
            $userEvaluationStatuses = DB::table('evaluation_status')
                ->where('user_id', $user->id)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->orderBy('created_at', 'asc')
                ->get();

            // 1. Tính tự xếp loại (self_rating) dựa trên bản ghi đầu tiên (giả định là tự đánh giá)
            $selfEvaluation = $userEvaluationStatuses->first(); // Bản ghi đầu tiên
            $selfRating = '';
            if ($selfEvaluation) {
                $selfTotalRecords = 1; // Chỉ lấy 1 bản ghi đầu tiên
                $selfOverachievedRecords = 0;
                $selfOnTimeRecords = 0;
                $selfFailedRecords = 0;

                $statusId = $selfEvaluation->status_id;
                if (isset($statuses[$statusId])) {
                    $point = $statuses[$statusId]->point;
                    if ($point == 100) {
                        $selfOverachievedRecords++;
                        $selfOnTimeRecords++;
                    } elseif ($point >= 80) {
                        $selfOnTimeRecords++;
                    } else {
                        $selfFailedRecords++;
                    }
                }

                $selfOverachievedPercentage = $selfTotalRecords > 0 ? ($selfOverachievedRecords / $selfTotalRecords) * 100 : 0;
                $selfOnTimePercentage = $selfTotalRecords > 0 ? ($selfOnTimeRecords / $selfTotalRecords) * 100 : 0;
                $selfFailedPercentage = $selfTotalRecords > 0 ? ($selfFailedRecords / $selfTotalRecords) * 100 : 0;

                if ($selfTotalRecords > 0) {
                    if ($selfOnTimePercentage == 100 && $selfOverachievedPercentage >= 50) {
                        $selfRating = 'A';
                    } elseif ($selfOnTimePercentage == 100 && $selfOverachievedPercentage >= 20) {
                        $selfRating = 'B';
                    } elseif ($selfFailedPercentage <= 20) {
                        $selfRating = 'C';
                    } else {
                        $selfRating = 'D';
                    }
                }
            }

            // 2. Tính xếp loại của lãnh đạo (rating) và % mức độ hoàn thành (overachieved_percentage) dựa trên bản ghi mới nhất (lock = 0)
            $leaderEvaluation = $userEvaluationStatuses->where('lock', 0)->first(); // Bản ghi mới nhất (lock = 0)
            $rating = '';
            $overachievedPercentage = '';
            if ($leaderEvaluation) {
                $leaderTotalRecords = 1; // Chỉ lấy 1 bản ghi mới nhất
                $leaderOverachievedRecords = 0;
                $leaderOnTimeRecords = 0;
                $leaderFailedRecords = 0;

                $statusId = $leaderEvaluation->status_id;
                if (isset($statuses[$statusId])) {
                    $point = $statuses[$statusId]->point;
                    if ($point == 100) {
                        $leaderOverachievedRecords++;
                        $leaderOnTimeRecords++;
                    } elseif ($point >= 80) {
                        $leaderOnTimeRecords++;
                    } else {
                        $leaderFailedRecords++;
                    }
                }

                $leaderOverachievedPercentage = $leaderTotalRecords > 0 ? ($leaderOverachievedRecords / $leaderTotalRecords) * 100 : 0;
                $leaderOnTimePercentage = $leaderTotalRecords > 0 ? ($leaderOnTimeRecords / $leaderTotalRecords) * 100 : 0;
                $leaderFailedPercentage = $leaderTotalRecords > 0 ? ($leaderFailedRecords / $leaderTotalRecords) * 100 : 0;

                if ($leaderTotalRecords > 0) {
                    if ($leaderOnTimePercentage == 100 && $leaderOverachievedPercentage >= 50) {
                        $rating = 'A';
                    } elseif ($leaderOnTimePercentage == 100 && $leaderOverachievedPercentage >= 20) {
                        $rating = 'B';
                    } elseif ($leaderFailedPercentage <= 20) {
                        $rating = 'C';
                    } else {
                        $rating = 'D';
                    }
                    $overachievedPercentage = round($leaderOverachievedPercentage, 2);
                }
            }

            $evaluations[] = (object)[
                'user' => $user,
                'position' => $user->user_catalogues->name ?? '',
                'team' => $user->teams->name ?? 'Không xác định',
                'working_days' => $stat->working_days_in_month ?? '',
                'leave_days_with_permission' => $stat->leave_days_with_permission ?? '',
                'leave_days_without_permission' => $stat->leave_days_without_permission ?? '',
                'violation_count' => $stat->violation_count ?? '',
                'violation_behavior' => $stat->violation_behavior ?? '',
                'disciplinary_action' => $stat->disciplinary_action ?? '',
                'self_rating' => $selfRating,
                'overachieved_percentage' => $overachievedPercentage,
                'rating' => $rating,
                'note' => '',
            ];
        }

        // Xếp loại cho lãnh đạo (nếu user hiện tại là lãnh đạo)
        $currentUserStat = collect($statistics)->firstWhere('user.id', $currentUser->id);
        if ($currentUserStat && $currentUserStat->user->level != 5) { // Giả sử level != 5 là lãnh đạo
            $totalMembers = count($evaluations);
            $aCount = count(array_filter($evaluations, fn($e) => $e->rating === 'A'));
            $bCount = count(array_filter($evaluations, fn($e) => $e->rating === 'B'));
            $cCount = count(array_filter($evaluations, fn($e) => $e->rating === 'C'));
            $dCount = count(array_filter($evaluations, fn($e) => $e->rating === 'D'));

            $aPercentage = $totalMembers > 0 ? ($aCount / $totalMembers) * 100 : 0;
            $bPercentage = $totalMembers > 0 ? ($bCount / $totalMembers) * 100 : 0;
            $cPercentage = $totalMembers > 0 ? ($cCount / $totalMembers) * 100 : 0;
            $dPercentage = $totalMembers > 0 ? ($dCount / $totalMembers) * 100 : 0;

            $leaderRating = '';
            if ($totalMembers > 0) {
                if ($aPercentage >= 70 && $dPercentage == 0) {
                    $leaderRating = 'A';
                } elseif ($bPercentage >= 70 && $dPercentage == 0) {
                    $leaderRating = 'B';
                } elseif ($cPercentage > 30) {
                    $leaderRating = 'C';
                } elseif ($dPercentage > 30) {
                    $leaderRating = 'D';
                }
            }

            // Tính tự xếp loại cho lãnh đạo
            $leaderEvaluations = DB::table('evaluation_status')
                ->where('user_id', $currentUser->id)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->orderBy('created_at', 'asc')
                ->get();

            $leaderSelfEvaluation = $leaderEvaluations->first(); // Bản ghi đầu tiên (tự đánh giá)
            $leaderSelfRating = '';
            if ($leaderSelfEvaluation) {
                $leaderSelfTotalRecords = 1;
                $leaderSelfOverachievedRecords = 0;
                $leaderSelfOnTimeRecords = 0;
                $leaderSelfFailedRecords = 0;

                $statusId = $leaderSelfEvaluation->status_id;
                if (isset($statuses[$statusId])) {
                    $point = $statuses[$statusId]->point;
                    if ($point == 100) {
                        $leaderSelfOverachievedRecords++;
                        $leaderSelfOnTimeRecords++;
                    } elseif ($point >= 80) {
                        $leaderSelfOnTimeRecords++;
                    } else {
                        $leaderSelfFailedRecords++;
                    }
                }

                $leaderSelfOverachievedPercentage = $leaderSelfTotalRecords > 0 ? ($leaderSelfOverachievedRecords / $leaderSelfTotalRecords) * 100 : 0;
                $leaderSelfOnTimePercentage = $leaderSelfTotalRecords > 0 ? ($leaderSelfOnTimeRecords / $leaderSelfTotalRecords) * 100 : 0;
                $leaderSelfFailedPercentage = $leaderSelfTotalRecords > 0 ? ($leaderSelfFailedRecords / $leaderSelfTotalRecords) * 100 : 0;

                if ($leaderSelfTotalRecords > 0) {
                    if ($leaderSelfOnTimePercentage == 100 && $leaderSelfOverachievedPercentage >= 50) {
                        $leaderSelfRating = 'A';
                    } elseif ($leaderSelfOnTimePercentage == 100 && $leaderSelfOverachievedPercentage >= 20) {
                        $leaderSelfRating = 'B';
                    } elseif ($leaderSelfFailedPercentage <= 20) {
                        $leaderSelfRating = 'C';
                    } else {
                        $leaderSelfRating = 'D';
                    }
                }
            }

            $evaluations[] = (object)[
                'user' => $currentUserStat->user,
                'position' => $currentUserStat->user->user_catalogues->name ?? '',
                'team' => $currentUserStat->user->teams->name ?? 'Không xác định',
                'working_days' => $currentUserStat->statistics->working_days_in_month ?? '',
                'leave_days_with_permission' => $currentUserStat->statistics->leave_days_with_permission ?? '',
                'leave_days_without_permission' => $currentUserStat->statistics->leave_days_without_permission ?? '',
                'violation_count' => $currentUserStat->statistics->violation_count ?? '',
                'violation_behavior' => $currentUserStat->statistics->violation_behavior ?? '',
                'disciplinary_action' => $currentUserStat->statistics->disciplinary_action ?? '',
                'self_rating' => $leaderSelfRating,
                'overachieved_percentage' => '', // Lãnh đạo không tính % mức độ hoàn thành
                'rating' => $leaderRating,
                'note' => '',
            ];
        }

        return $evaluations;
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

    private function getStatisticsForTeamMembers($teamMembers, $month)
    {
        // Loại bỏ trùng lặp dựa trên id
        $teamMembers = collect($teamMembers)->unique('id')->values();
        $userIds = $teamMembers->pluck('id')->toArray();

        // Đảm bảo $month có định dạng YYYY-MM-DD
        $monthFormatted = $month instanceof \Carbon\Carbon
            ? $month->format('Y-m-d')
            : \Carbon\Carbon::parse($month)->format('Y-m-d');

        // Lấy dữ liệu từ bảng statistics
        $statistics = DB::table('statistics')
            ->whereIn('user_id', $userIds)
            ->where('month', $monthFormatted)
            ->get()
            ->keyBy('user_id'); // Lập chỉ mục theo user_id để tra cứu nhanh

        // Kết hợp dữ liệu statistics với thông tin user
        $result = [];
        foreach ($teamMembers as $member) {
            $stat = $statistics->get($member->id) ?? (object)[
                'working_days_in_month' => null, // Sử dụng null để phân biệt với giá trị 0 thực tế
                'leave_days_with_permission' => null,
                'leave_days_without_permission' => null,
                'violation_count' => null,
                'violation_behavior' => '',
                'disciplinary_action' => '',
            ];

            $result[] = (object)[
                'user' => $member,
                'statistics' => $stat,
            ];
        }

        return $result;
    }

}
