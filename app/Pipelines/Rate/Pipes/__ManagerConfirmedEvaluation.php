<?php 
namespace App\Pipelines\Rate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;

class ManagerConfirmedEvaluation {

    protected $userService;


    public function __construct(
        UserService $userService
    )
    {
        $this->userService = $userService;
    }

    public function handle($data, \Closure $next){

        $level4Tasks = 0;

        $level3Tasks = 0;

        $level2Tasks = 0;

        $level1Tasks = 0;

        $user = $data['user'];

        $userLevel = $user->user_catalogues->level ?? 5;

        $totalTasks = count($data['rateInfo']['superiorLeaderRatings']);

        $hasSelfEvaluation = $totalTasks > 0 ? true : false;

        $counts = array_count_values($data['rateInfo']['superiorLeaderRatings']);

        $level4Tasks += $counts['A'] ?? 0;

        $level3Tasks += $counts['B'] ?? 0;

        $level2Tasks += $counts['C'] ?? 0;

        $level1Tasks += $counts['D'] ?? 0;

        $level4Percentage = $totalTasks > 0 ? ($level4Tasks / $totalTasks) * 100 : 0;

        $level3Percentage = $totalTasks > 0 ? (($level3Tasks + $level4Tasks) / $totalTasks) * 100 : 0;

        $level2Percentage = $totalTasks > 0 ? ($level2Tasks / $totalTasks) * 100 : 0;

        $level1Percentage = $totalTasks > 0 ? ($level1Tasks / $totalTasks) * 100 : 0;

        $finalRating = null;

        if($totalTasks > 0){
            if ($level3Percentage == 100 && $level4Percentage >= 50) {
                $finalRating = 'A';
            } elseif ($level3Percentage == 100) {
                $finalRating = 'B';
            } elseif ($level2Percentage <= 20) {
                $finalRating = 'C';
            } elseif ($level1Percentage > 20) {
                $finalRating = 'D';
            }
        } elseif ($userLevel < 5 && !$hasSelfEvaluation) {  
            $finalRating = 'A';
        } elseif ($userLevel == 5 && !$hasSelfEvaluation) { 
            $finalRating = 'Không đánh giá';
        } 

        $data['rateInfo']['final_rating'] = $finalRating;
        return $next($data);
    }   

}

/**
 * 1. Tạo 1 nút tính toán sơ bộ dữ liệu cạnh nút excel
 * 2. Thực hiện lấy user lấy evaluation thực hiện pipe --> cache tổng thể dữ liệu vào trong Cache
 *  
 * 
 * 
 * 
 */