<?php 
namespace App\Pipelines\CacheRate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;

class SeftEvalutionRating {

    protected $userService;

    public function __construct(
        UserService $userService
    )
    {
        $this->userService = $userService;
    }

    public function handle($data, \Closure $next){

        $user = $data['user'];

        $userLevel = $user->user_catalogues->level ?? 5;

        $userEvaluations = $user->evaluations;

        $totalTasks = 0;

        $level3And4Tasks = 0;

        $level4Tasks = 0;

        $level3Tasks = 0;

        $level2Tasks = 0;

        $level1Tasks = 0;

        $approverIds = [];
        
        $superiorLeaderRatings = [];

        $hasLeaderApprover = false;

        foreach($userEvaluations as $item){
            foreach($item->statuses as $status){
                $lock = $status->pivot->lock ?? 1;
                if ($status->pivot->user_id != $user->id && $lock == 0) {
                    $approverIds[] = $status->pivot->user_id;
                }
            }
        }

        $approverIds = array_unique($approverIds);

        $approvers = empty($approverIds) ? collect([]) : $this->userService->findByIds($approverIds, ['user_catalogues']);

        foreach ($userEvaluations as $item) {

            $listUserEvaluations = [];
            
            $statuses = $item->statuses;

            foreach($statuses as $status){
                if( $status->pivot->user_id != $user->id){
                    $listUserEvaluations[] = $status->pivot->user_id;
                }
            }

            foreach($listUserEvaluations as $item){
                foreach($approvers as $approver){
                    if($approver->id == $item && $userLevel - $approver->user_catalogues->level >= 2){
                        $hasLeaderApprover = true;
                        break;
                    }
                }
            }
            
            if(count($statuses) <= 2 && $hasLeaderApprover == false){ 
                continue; 
            }

            $totalTasks++;

            $finalStatus = null;

            $selfStatus = null;

            //loop qua mỗi trạng thái
            foreach ($statuses as $status) {
                $lock = $status->pivot->lock ?? 1;
                if ($status->pivot->user_id != $user->id && $lock == 0) {
                    $approver = $approvers->firstWhere('id', $status->pivot->user_id);
                    if ($approver) {
                        $approverLevel = $approver->user_catalogues->level ?? 5;
                        $isValidApprover = ($userLevel == 2 && $approverLevel <= ($userLevel - 1)) || 
                        ($userLevel != 2 && $approverLevel <= ($userLevel - 2)) || 
                        $approverLevel == 1;
                        if ($isValidApprover) {
                            $statusLevel = $status->level ?? 1;
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
            } elseif ($statusLevel == 2) {
                $level2Tasks += 1;
            } elseif ($statusLevel == 1) {
                $level1Tasks += 1;
            }
            
        }

        $hasSelfEvaluation = $totalTasks > 0 ? true : false;

        $level4Percentage = $totalTasks > 0 ? ($level4Tasks / $totalTasks) * 100 : 0;

        $level3Percentage = $totalTasks > 0 ? (($level3Tasks + $level4Tasks) / $totalTasks) * 100 : 0;

        $level2Percentage = $totalTasks > 0 ? ($level2Tasks / $totalTasks) * 100 : 0;

        $level1Percentage = $totalTasks > 0 ? ($level1Tasks / $totalTasks) * 100 : 0;

        $selfRating = null;

        if ($totalTasks > 0) {
            if ($level3Percentage == 100 && $level4Percentage >= 50) {
                $selfRating = 'A';
            } elseif ($level3Percentage == 100) {
                $selfRating = 'B';
            } elseif ($level2Percentage <= 20) {
                $selfRating = 'C';
            } elseif ($level1Percentage > 20) {
                $selfRating = 'D';
            }
        } elseif ($userLevel < 5 && !$hasSelfEvaluation) { 
            $selfRating = 'A';
        } elseif ($userLevel == 5 && !$hasSelfEvaluation) { 
            $selfRating = 'Không đánh giá';
        } 

        $data['rateInfo']['selfRating'] = $selfRating;

        $data['rateInfo']['superiorLeaderRatings'] = $superiorLeaderRatings;

        return $next($data); 
    }   

}