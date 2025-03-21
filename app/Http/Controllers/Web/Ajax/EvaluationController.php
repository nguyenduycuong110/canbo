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

class EvaluationController extends BaseController
{
    use Loggable;

    protected $evaluationService;
    protected $statisticService;

    public function __construct(
        EvaluationService $evaluationService,
        StatisticService $statisticService
    ){
        $this->evaluationService = $evaluationService;
        $this->statisticService = $statisticService;
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
            if($request->dateType === 'day'){
                $evaluationList = $this->evaluationService->getDepartment($request, 'day');
                $formData['month'] = Carbon::createFromFormat('d/m/Y', $request->date)->startOfDay();
            }else if($request->dateType === 'month'){
                $evaluationList = $this->evaluationService->getDepartment($request, 'month');
                $formData['month'] = Carbon::createFromFormat('m/Y', $request->date)->startOfMonth();
            }
            
            
            //Khoi tao lich su export data de phuc vu cho luc in thong ke chi tiet
            $this->statisticService->createOrUpdate($formData);


            // dd($evaluationList->evaluations);
            if($request->exportType === 'excel'){
                $export = new EvaluationExport($evaluationList, $request->date, $formData);
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
    
}
