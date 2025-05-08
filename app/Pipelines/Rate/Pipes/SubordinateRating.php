<?php 
namespace App\Pipelines\Rate\Pipes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Services\Interfaces\Statistic\StatisticServiceInterface as StatisticService;
use App\Services\Interfaces\User\UserServiceInterface as UserService;

class SubordinateRating {

    protected $statisticService;
    protected $userService;

    public function __construct(
        StatisticService $statisticService,
        UserService $userService
    )
    {
        $this->statisticService = $statisticService;
        $this->userService = $userService;
    }

    /**
     * Trường hợp này chỉ xảy ra nếu như là cấp lãnh đạo, những người có userlevel < 5 bị phụ thuộc vào cả xếp loại của cấp dưới để lấy xếp loại của mình
     * 
     * 
     */
    public function handle($data, \Closure $next){

        $user = $data['user'];
        $userLevel = $user->user_catalogues->level;
        // $evaluations = $user->evaluations;
        // $levelProcess = generateEvalationProcessArray();
        // $totalTasks = 0;
        $auth = Auth::user();
        $monthExport = $data['monthExport'];

        $cacheKey =  "seft:month_{$monthExport}:user_{$auth->id}";
        $cacheData = Cache::get($cacheKey);

        /** Lấy thống kê tháng của user */
        $statistic = $user->statistics->where('month', $data['month']->format('Y-m-d'))->first();
        /** Lấy số lần bị kỉ luật */
        $disciplinaryCount = 0;
        if ($statistic) {
            $disciplinaryCount = (int)$statistic->disciplinary_action;
        }
        $currentFinalRating = $cacheData[$user->id]['finalRating'];
        // dd($currentFinalRating);

        if($disciplinaryCount > 0){
            $newRating = $this->applyDisciplinaryRules($currentFinalRating, $disciplinaryCount);
            $cacheData[$user->id]['finalRating'] = $newRating;
        }else if($userLevel < 5){
            $subordinateRatings = $this->getSubordinateRatingFromCache($userLevel, $cacheData);
            // dd($subordinateRatings);
            if($subordinateRatings > 0){
                $newRating = $this->applySubordinateRatingRule($currentFinalRating, $subordinateRatings);
                $cacheData[$user->id]['finalRating'] = $newRating;
                $data['rateInfo']['final_rating'] = $newRating;
            }
        }
        
        $data['working_days'] = $statistic ? $statistic->working_days_in_month : 0;

        $data['leave_days'] = $statistic ? $statistic->leave_days_with_permission : 0;

        $data['violation_count'] = $statistic ? $statistic->violation_count : 0;

        $data['disciplinary_action'] = $statistic ? $statistic->disciplinary_action : 0;

        $data['completion_percentage'] = $cacheData[$user->id]['completion_percentage'];

        return $next($data);

    }   

    private function applySubordinateRatingRule($currentFinalRating, $subordinateRatings){
        $percentA = ($subordinateRatings['A'] / $subordinateRatings['total']) * 100;
        $percentB = ($subordinateRatings['B'] / $subordinateRatings['total']) * 100;
        $percentC = ($subordinateRatings['C'] / $subordinateRatings['total']) * 100;
        $percentD = ($subordinateRatings['D'] / $subordinateRatings['total']) * 100;

        if($currentFinalRating === 'A' && $percentA > 70 && $subordinateRatings['D'] === 0){
            return 'A';
        }

        if($percentB >= 70 && $subordinateRatings['D'] === 0){
            return 'B';
        }

        if($percentC >= 70){
            return 'C';
        }

        if($percentD > 30){
            return 'D';
        }
        return $currentFinalRating;

    }

    private function getSubordinateRatingFromCache($userLevel, $cacheData){
        $ratings = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'total' => 0,
            'details' => [           // Thêm mảng chi tiết để lưu thông tin
                'A' => [],
                'B' => [],
                'C' => [],
                'D' => [],
            ]
        ];

        foreach($cacheData as $userId => $userData){
            if(!isset($userData) || !isset($userData['level']) || !isset($userData['finalRating'])) continue;
            
            $subordinateLevel = $userData['level'];
            $temp = [];

            $isDirect = false;
            if($userLevel === 1 || $userLevel === 2 || $userLevel === 4){
                $isDirect = $subordinateLevel === $userLevel + 1;
            }else if($userLevel === 3){ 
                $isDirect = ($subordinateLevel === $userLevel + 1) || ($subordinateLevel === $userLevel + 2); // Đội trưởng có thể quản lý trực tiếp user mà k cần đội phó
            }

            
            if($isDirect){
                $rating = $userData['finalRating'];
                if(in_array($rating, ['A', 'B', 'C', 'D'])){
                    $ratings[$rating]++;
                    $ratings['total']++;

                    // Lưu vào detail để debug dữ liệu
                    $ratings['details'][$rating][] = [
                        'id' => $userId,
                        'name' => $userData['name'] ?? 'Unknown',
                        'level' => $subordinateLevel,
                        'rating' => $rating
                    ];
                }
            }
        }
        return $ratings;
    }

    private function applyDisciplinaryRules($currentRating, $disciplinaryCount){
        if($disciplinaryCount >= 3){
            return 'D';
        }else if($disciplinaryCount >= 2){
            return 'C';
        } else if($disciplinaryCount == 1){
            switch ($currentRating) {
                case 'A': return 'B';
                case 'B': return 'C';
                case 'C': return 'D'; 
                case 'D': return 'D';
                default: return 'D';
            }
        }
        //Tra ve trang thai ban dau neu nhu k bi ki luat
        return $currentRating;
    }
    

}

