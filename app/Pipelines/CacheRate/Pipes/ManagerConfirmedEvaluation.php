<?php 
namespace App\Pipelines\CacheRate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

/** 
 * Tính toán kết quả xếp loại của user theo đánh giá của Lãnh Đạo
 * 
 */
class ManagerConfirmedEvaluation {

    protected $userService;

    public function __construct(
        UserService $userService
    )
    {
        $this->userService = $userService;
    }

    public function handle($data, \Closure $next){

        $user = $data['user'];
        $userLevel = $user->user_catalogues->level;
        $evaluations = $user->evaluations;
        $levelProcess = generateEvalationProcessArray();
        $totalTasks = 0;
        $auth = $data['auth'];
        $monthExport = $data['monthExport'];

        $cacheKey =  "seft:month_{$monthExport}:user_{$auth->id}";
        $cacheData = Cache::get($cacheKey);

        //Nếu là lãnh đạo chi cục - những người ko có cấp lãnh đạo thứ 2 hoặc là những cán bộ mà ko có tự đánh giá thì trạng thái cuối cùng = tự đánh giá
        if($userLevel === 1 || $userLevel === 2 || ($userLevel === 5 && $cacheData[$user->id]['selfRating'] === 'Không Đánh Giá')){
            $cacheData[$user->id]['finalRating'] = $cacheData[$user->id]['selfRating'];
            $cacheData[$user->id]['level'] = $userLevel;
            $cacheData[$user->id]['completion_percentage'] = $cacheData[$user->id]['completion_percentage'];

        } else{
            $finalRating = null;
            /** Tiến hành phân tích từng đánh giá của user */

            if(count($evaluations)){
                foreach($evaluations as $evaluation){
                    /** Lấy ra toàn bộ trạng thái của của những người tham gia vào evaluation */
                    // if($evaluation->id !== 1066) continue;
                    $statuses = $evaluation->statuses;
    
                    if($statuses->isEmpty()) continue; // Bỏ qua đánh giá nếu như ko có trạng thái nào
                    $totalTasks++; //Vì là tự đánh giá nên nếu cứ có phiếu đánh giá sẽ tăng tổng số Task lên xem là bao nhiêu
    
                    /** 
                     * Tìm ra đánh giá của lãnh đạo 
                     * Như thế nào gọi là đánh giá của lãnh đạo: 
                     *  1. Trạng thái phê duyệt
                     *  2. Tức là phải là người có lock = 0 và có level của người đang đánh giá - đi level của lãnh đạo phải > 1 trừ trường hợp là phó chi cục trưởng và chi cục trương
                     *      --> Tức là những người có level = 1 và 2;
                     * 
                     * 
                     */
                    $managerConfirmedEvaluation = $statuses->first(function($status) use ($user, $userLevel){
                        //Nếu lock = 1 --> thì chưa phải là đánh giá cuối cùng
                        if($status->pivot->lock !== 0) return false;
                        $evaluatorId = $status->pivot->user_id;
                        $evaluator = $this->userService->findById($evaluatorId, ['user_catalogues']);
                        if(!$evaluator) return false;
                        return $userLevel - $evaluator->user_catalogues->level > 1;
    
                    });
    
    
                    /** Lấy Level tự đánh giá */
                    $statusLevel = $managerConfirmedEvaluation->level ?? 3;
                    if($statusLevel === 4){
                        $levelProcess[$statusLevel]++;
                        $levelProcess[$statusLevel - 1]++;
                    }else{
                        $levelProcess[$statusLevel]++;
                    }
                }
    
    
                $percentage = caculateTaskPercentage($levelProcess, $totalTasks);
    
                // Không cần xét trường hợp ko có đánh giá của lãnh đạo vì nếu ko có đã tự gán = 3 vì vậy ở đây ko thể xảy ra trường hợp xếp loại là ko đánh giá trừ trường hợp người đó k có phiếu nào 
                if($percentage[3] === 100 && $percentage[4] >= 50){
                    $finalRating = 'A';
                }else if($percentage[3] === 100 && $percentage[4] < 50){
                    $finalRating = 'B';
                } else if($percentage[2] >= 80){
                    $finalRating = 'C';
                }else{
                    $finalRating = 'D';
                }
                $cacheData[$user->id]['finalRating'] = $finalRating;
                $cacheData[$user->id]['completion_percentage'] = round($percentage[4], 2);
            }else{
                $cacheData[$user->id]['finalRating'] = 'A';
                $cacheData[$user->id]['completion_percentage'] = 0;
            }
            $cacheData[$user->id]['level'] = $userLevel;

            
        }
        Cache::put($cacheKey, $cacheData);
        return $next($data);
    }   

    

}
