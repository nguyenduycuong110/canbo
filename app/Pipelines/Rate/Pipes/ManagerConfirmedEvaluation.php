<?php 
namespace App\Pipelines\Rate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

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
        $auth = Auth::user();
        $monthExport = Carbon::createFromFormat('Y-m-d H:i:s', $data['month'])->format('Y_m_d');

        $cacheKey =  "seft:month_{$monthExport}:user_{$auth->id}";
        $cacheData = Cache::get($cacheKey);

        $data['rateInfo']['final_rating'] = $cacheData[$user->id]['finalRating'];
        $data['monthExport'] = $monthExport;
        
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