<?php 
namespace App\Pipelines\Rate\Pipes;
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
 
        // dd(count($userEvaluations));
        
        $itemId = [];
        foreach ($userEvaluations as $keyItem => $item) {

            $statuses = $item->statuses;

            // foreach($statuses as $status){
            //     if( $status->pivot->user_id != $user->id && $status->pivot->user_id = 0){
            //         $listUserEvaluations[] = $status->pivot->user_id;
            //     }
            // }

            // foreach($listUserEvaluations as $val){
            //     foreach($approvers as $approver){
            //         if($approver->id == $val && $userLevel - $approver['user_catalogues']->level >= 2){
            //             $hasLeaderApprover = true;
            //         }
            //     }
            // }

            // if(count($statuses) <= 2 && $hasLeaderApprover == false && $userLevel - $approver['user_catalogues']->level == 1){ 
            //     $level3Tasks += 1;
            //     continue;
            // }

            $lockEvaluation = $statuses->filter(function($statusItem){
                return $statusItem->pivot->lock == 0;
            });
            $lockEvaluationArray = $lockEvaluation->flatten()->toArray();
            $lockApprover = $this->userService->findById($lockEvaluationArray[0]['pivot']['user_id'], ['user_catalogues']);
            // if($lockApprover->user_catalogues->level - $userLevel > 1){
            //     $level3Tasks++;
            // }

            $totalTasks++;

            $finalStatus = null;

            $selfStatus = null;

            foreach ($statuses as $status) {
                $lock = $status->pivot->lock ?? 1;
                $userIdEvaluation = $status->pivot->user_id;

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
            $statusLevel = 3;

            foreach ($statuses as $key => $status) {
                if($status->pivot->user_id === $lockApprover->id && $userLevel - $lockApprover->user_catalogues->level > 1){
                    $statusLevel = $status->level ?? 3;
                }
            }

            if ($statusLevel == 4) {
                $level4Tasks += 1;
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

        $completionPercentage = round($level4Percentage, 2);

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

        $data['totalTasks'] = $totalTasks;

        $data['rateInfo']['selfRating'] = $selfRating;

        $data['rateInfo']['superiorLeaderRatings'] = $superiorLeaderRatings;

        $data['completion_percentage'] = $completionPercentage;

        return $next($data); 
    }   

}