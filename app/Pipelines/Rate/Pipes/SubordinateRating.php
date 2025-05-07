<?php 
namespace App\Pipelines\Rate\Pipes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Services\Interfaces\Statistic\StatisticServiceInterface as StatisticService;

class SubordinateRating {

    protected $statisticService;

    public function __construct(
        StatisticService $statisticService
    )
    {
        $this->statisticService = $statisticService;
    }

    public function handle($data, \Closure $next){

        $currentUser = Auth::user();

        $cacheKey = 'month_export_'.$data['month'].'_'.$currentUser->id;
        
        $cacheData = Cache::get($cacheKey);

        $user = $data['user'];

        $statistic = $user->statistics->where('month', $data['month']->format('Y-m-d'))->first();

        $userLevel = $user->user_catalogues->level ?? 5;

        $totalTasks = $data['totalTasks'];

        $hasSelfEvaluation = ($totalTasks == 0) ? false : true;

        if($userLevel < 5){

            $hasSubordinateWithRatingD = false;

            if(!$hasSelfEvaluation){

                $selfRating = $data['rateInfo']['selfRating'];

                $filteredData = array_filter($cacheData, function ($value) use ($userLevel) {
                    return $value['finalRating'] !== 'Không đánh giá' && $value['level'] < $userLevel + 1;
                });

                $finalRatings = array_column($filteredData, 'finalRating');
                
                $counts = array_count_values($finalRatings);

                $typeACount = $counts['A'] ?? 0;

                $typeDCount = $counts['D'] ?? 0;

                $typeAPercentage = ($typeACount / count($filteredData)) * 100;

                $typeDPercentage = ($typeDCount / count($filteredData)) * 100;

                if ($typeAPercentage > 70 && !$hasSubordinateWithRatingD) {

                    $tempRating = 'A';

                } elseif ($typeAPercentage <= 70 && !$hasSubordinateWithRatingD){

                    $tempRating = 'B';

                }  elseif ($hasSubordinateWithRatingD) {

                    if ($typeDPercentage > 30) {

                        $tempRating = 'D';

                    } else {

                        $tempRating = 'C';

                    }

                }

                $ratingValues = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];

                $reverseRatingValues = [4 => 'A', 3 => 'B', 2 => 'C', 1 => 'D'];

                $selfRatingValue = $ratingValues[$selfRating] ?? 1;

                $tempRatingValue = $ratingValues[$tempRating] ?? 1;

                if ($selfRatingValue < $tempRatingValue) {

                    $finalRating = $selfRatingValue;

                }elseif ($selfRatingValue = $tempRatingValue){

                    $newRatingValue = max(1, $selfRatingValue);

                    $finalRating = $reverseRatingValues[$newRatingValue];

                }
                else {

                    $newRatingValue = max(1, $selfRatingValue - 1);

                    $finalRating = $reverseRatingValues[$newRatingValue];

                }
                
            }else{

                $filteredData = array_filter($cacheData, function ($value) {
                    return $value['finalRating'] !== 'Không đánh giá' && $value['level'] == 5;
                });

                $finalRatings = array_column($filteredData, 'finalRating');

                $counts = array_count_values($finalRatings);

                if($counts == []){

                    $finalRating = 'A';

                    $data['rateInfo']['final_rating'] = $finalRating;

                    return $next($data);

                }

                $typeACount = $counts['A'] ?? 0;

                $typeDCount = $counts['D'] ?? 0;

                $typeAPercentage = ($typeACount / count($filteredData)) * 100;

                $typeDPercentage = ($typeDCount / count($filteredData)) * 100;

                $hasSubordinateWithRatingD = $typeDCount > 0;

                if ($typeAPercentage > 70 && !$hasSubordinateWithRatingD) {

                    $finalRating = 'A';

                } elseif ($typeAPercentage > 70 && $hasSubordinateWithRatingD){

                    $finalRating = 'B';

                } elseif ($typeAPercentage <= 70 && !$hasSubordinateWithRatingD){

                    $finalRating = 'B';

                }  elseif ($hasSubordinateWithRatingD) {

                    if ($typeDPercentage > 30) {

                        $finalRating= 'D';

                    } else {

                        $finalRating= 'C';

                    }

                }

            }

            $data['rateInfo']['final_rating'] = $finalRating;
            
        }

        $data['working_days'] = $statistic ? $statistic->working_days_in_month : 0;

        $data['leave_days'] = $statistic ? $statistic->leave_days_with_permission : 0;

        $data['violation_count'] = $statistic ? $statistic->violation_count : 0;

        $data['disciplinary_action'] = $statistic ? $statistic->disciplinary_action : 0;

        return $next($data);
    }   

}

