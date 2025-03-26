<?php   
namespace App\Services\Interfaces\Evaluation;
use Illuminate\Http\Request;
use App\Services\Interfaces\BaseServiceInterface;

interface EvaluationServiceInterface extends BaseServiceInterface {

    public function evaluate(Request $request, int $id);
    public function getDepartment($request);
    public function getEvaluationsByUserIdsAndMonth($userIds, $month);

}