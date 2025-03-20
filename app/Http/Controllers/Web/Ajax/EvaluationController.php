<?php

namespace App\Http\Controllers\Web\Ajax;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;
use App\Services\Interfaces\Evaluation\EvaluationServiceInterface as EvaluationService;

class EvaluationController extends BaseController
{
    use Loggable;

    protected $evaluationService;

    public function __construct(
        EvaluationService $evaluationService
    ){
        $this->evaluationService = $evaluationService;
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
            $response = $this->evaluationService->getDepartment($request);
            return response()->json(['flag' => $response]); 
        }  catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }
    
}
