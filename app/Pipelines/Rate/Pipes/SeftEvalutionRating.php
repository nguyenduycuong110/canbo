<?php 
namespace App\Pipelines\Rate\Pipes;
use App\Services\Interfaces\User\UserServiceInterface as UserService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

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
        $userLevel = $user->user_catalogues->level;
        $evaluations = $user->evaluations;
        $levelProcess = generateEvalationProcessArray();
        $totalTasks = 0;
        $auth = Auth::user();
        $monthExport = Carbon::createFromFormat('Y-m-d H:i:s', $data['month'])->format('Y_m_d');

        $cacheKey =  "seft:month_{$monthExport}:user_{$auth->id}";
        $cacheData = Cache::get($cacheKey);

        $data['totalTasks'] = $cacheData[$user->id]['totalTasks'];
        $data['rateInfo']['selfRating'] = $cacheData[$user->id]['selfRating'];

        
        return $next($data); 
    }   

}