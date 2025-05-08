<?php 
namespace App\Pipelines\CacheRate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class SeftEvalutionRating {

    protected $userService;

    public function __construct(
        UserService $userService
    )
    {
        $this->userService = $userService;
    }


    /**
     * 
     * Nguyễn Ngọc Anh: 3 đúng tiến độ,25 vượt tiến độ
     */
    public function handle($data, \Closure $next){
        $user = $data['user'];
        $userLevel = $user->user_catalogues->level;
        $evaluations = $user->evaluations;
        $levelProcess = generateEvalationProcessArray();
        $totalTasks = 0;
        $auth = $data['auth'];
        $monthExport = Carbon::createFromFormat('Y-m-d H:i:s', $data['month'])->format('Y_m_d');
        $data['monthExport'] = $monthExport;

        /** Tiến hành phân tích từng đánh giá của user */
        foreach($evaluations as $evaluation){
            /** Lấy ra toàn bộ trạng thái của của những người tham gia vào evaluation */
            $statuses = $evaluation->statuses;
            if($statuses->isEmpty()) continue; // Bỏ qua đánh giá nếu như ko có trạng thái nào
            $totalTasks++; //Vì là tự đánh giá nên nếu cứ có phiếu đánh giá sẽ tăng tổng số Task lên xem là bao nhiêu
            /** Tìm ra đánh giá của người dùng trong số statuses đó */
            $selfEvaluation = $statuses->first(function($status) use ($user){
                return $status->pivot->user_id === $user->id;
            });
            /** Lấy Level tự đánh giá */
            $statusLevel = $selfEvaluation->level;
            if($statusLevel === 4){
                $levelProcess[$statusLevel]++;
                $levelProcess[$statusLevel - 1]++;
            }else{
                $levelProcess[$statusLevel]++;
            }
        }

        
        $hasSelfEvaluation = $totalTasks > 0;
        $percentage = caculateTaskPercentage($levelProcess, $totalTasks);
        $selfRating = null;

        if($totalTasks > 0){
            if($percentage[3] === 100 && $percentage[4] >= 50){
                $selfRating = 'A';
            }else if($percentage[3] === 100 && $percentage[4] < 50){
                $selfRating = 'B';
            } else if($percentage[2] >= 80){
                $selfRating = 'C';
            }else{
                $selfRating = 'D';
            }
        }else  if($userLevel === 5 && !$hasSelfEvaluation){
            $selfRating = 'Không Đánh Giá';
        }else if($userLevel < 5 && !$hasSelfEvaluation){
            $selfRating = 'A';
        }

        $cacheKey = "seft:month_{$monthExport}:user_{$auth->id}";
        $cacheData = Cache::get($cacheKey, []);
        $cacheData[$user->id] = [
            'name' => $user->name,
            'selfRating' => $selfRating,
            'totalTasks' => $totalTasks,
            'completion_percentage' => round($percentage[4], 2)
        ];
        Cache::put($cacheKey, $cacheData);
        
        return $next($data);
    }   

    

}