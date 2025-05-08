<?php

namespace App\Http\Controllers\Web\Ajax;
use App\Traits\Loggable;
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
use App\Pipelines\Rate\RatingPipeManager;
use App\Pipelines\CacheRate\CacheRatingPipeManager;
use Illuminate\Support\Facades\Cache;

class EvaluationController extends BaseController
{
    use Loggable;

    protected $evaluationService;
    protected $statisticService;
    protected $userService;
    protected $userRepository;

    private $ratingPipeManager;
    private $cacheRatingPipeManager;

    public function __construct(
        EvaluationService $evaluationService,
        StatisticService $statisticService,
        UserService $userService,
        UserRepository $userRepository,
        RatingPipeManager $ratingPipeManager,
        CacheRatingPipeManager $cacheRatingPipeManager
    ){
        $this->evaluationService = $evaluationService;
        $this->statisticService = $statisticService;
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->ratingPipeManager = $ratingPipeManager;
        $this->cacheRatingPipeManager = $cacheRatingPipeManager;
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
            $users = $this->userService->getUserInNodeSortByLevel($currentUser);
            $userIds = $users->pluck('id')->toArray();
            
            $userEvaluations = [];
            // Evaluation này là tất cả evaluation của tất cả userIds được lấy ra 
            $evaluations = $this->evaluationService->getEvaluationsByUserIdsAndMonth($userIds, $month, 500, function($evaluations) use (&$userEvaluations){
                foreach($evaluations as $evaluation){
                    $userId = $evaluation->user_id;
                    if(!isset($userEvaluations[$userId])){
                        $userEvaluations[$userId] = [];
                    }
                    $userEvaluations[$userId][] = $evaluation;
                }
            });

            foreach($users as $user){
                $user->evaluations = $userEvaluations[$user->id] ?? [];
            }

            $ratedUsers = [];

            $pipeResult = null;

            foreach ($users as $item) {
                $pipeResult = $this->ratingPipeManager->send(['user' => $item, 'month' => $month]);
                $ratedUsers[] = $pipeResult;
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

    public function prepareData(Request $request){
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
            }
            $monthInput = $request->month ?? now()->format('m/Y');
            $month = Carbon::createFromFormat('m/Y', $monthInput)->startOfMonth();
            $users = $this->userService->getUserInNode($currentUser);
            $userIds = $users->pluck('id')->toArray();
            $userEvaluations = [];
            $evaluations = $this->evaluationService->getEvaluationsByUserIdsAndMonth($userIds, $month, 500, function($evaluations) use (&$userEvaluations){
                foreach($evaluations as $evaluation){
                    $userId = $evaluation->user_id;
                    if(!isset($userEvaluations[$userId])){
                        $userEvaluations[$userId] = [];
                    }
                    $userEvaluations[$userId][] = $evaluation;
                }
            });

            $ratedUsers = [];
            $pipeResult = null;

            foreach($users as $user){
                // if($user->id !== 147) continue;
                $user->evaluations = $userEvaluations[$user->id] ?? [];
                $pipeResult = $this->cacheRatingPipeManager->send(['user' => $user, 'month' => $month, 'auth' => $currentUser]);
            }

            // $monthExport = Carbon::createFromFormat('Y-m-d H:i:s', $month)->format('Y_m_d');
            // $seftRatingCacheKey = "seft:month_{$monthExport}:user_{$currentUser->id}";
            // $seftRatingCacheData = Cache::get($seftRatingCacheKey);
            // dd($seftRatingCacheData[147]);

            return response()->json('Chuẩn bị dữ liệu thành công');die();
           
           

        } catch (\Throwable $th) {
            dd($th);
        }
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
            'perpage' => 100,
            'level' => null,
            'lft' => ['gt' => $auth->lft],
            'rgt' => ['lt' => $auth->rgt],
            'relationFilter' => ['user_catalogues' => ['level' => ['eq' => (int)$request->level]]], // Lấy user ở cấp $level (ví dụ: 4 cho Đội phó)
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
            $users = $this->userService->getUserInNodeSortByLevel($currentUser);
            $userIds = $users->pluck('id')->toArray();
            $userEvaluations = [];
            $evaluations = $this->evaluationService->getEvaluationsByUserIdsAndMonth($userIds, $month, 500, function($evaluations) use (&$userEvaluations){
                foreach($evaluations as $evaluation){
                    $userId = $evaluation->user_id;
                    if(!isset($userEvaluations[$userId])){
                        $userEvaluations[$userId] = [];
                    }
                    $userEvaluations[$userId][] = $evaluation;
                }
            });

            foreach($users as $user){
                $user->evaluations = $userEvaluations[$user->id] ?? [];
            }

            $ratedUsers = [];

            $pipeResult = null;

            foreach ($users as $item) { 
                $pipeResult = $this->ratingPipeManager->send(['user' => $item, 'month' => $month]);
                $ratedUsers[] = $pipeResult;
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
