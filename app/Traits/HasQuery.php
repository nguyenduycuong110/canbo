<?php  
namespace App\Traits;
use Illuminate\Support\Carbon;

trait HasQuery {

    public function scopeKeyword($query, array $keyword = []){
        if(!empty($keyword['q'])){
            foreach($keyword['fields'] as $field){
                $query->orWhere($field, 'LIKE', '%'.$keyword['q'].'%');
            }
        }
        return $query;
    }

    public function scopeSimpleFilter($query, array $simpleFilter = []){
        if(count($simpleFilter)){
            foreach($simpleFilter as $key => $val){
                if($val !== 0 && !empty($val) && !is_null($val)){
                    $query->where($key, $val);
                }
            }
        }
    }

    public function scopeComplexFilter($query, array $complexFilter = []){
        if(count($complexFilter)){
            foreach($complexFilter as $field => $condition){
                foreach($condition as $operator => $val){
                   
                    switch ($operator) {
                        case 'gt':
                            $query->where($field, '>', $val);
                            break;
                        case 'gte':
                            $query->where($field, '>=', $val);
                            break;
                        case 'lt':
                            $query->where($field, '<', $val);
                            break;
                        case 'lte':
                            $query->where($field, '<=', $val);
                            break;
                        case 'eq':
                            $query->where($field, '=', $val);
                            break;
                        case 'between':
                            [$min, $max] = explode(',', $val); 
                            $query->whereBetween($field, [ $min, $max]);
                            break;
                        case 'in': 
                            [$field, $in] = explode('|', $val);
                            $whereIn = explode(',', $in);
                            if(count($whereIn)){
                                $query->whereIn($field, $whereIn);
                            }
                        default:
                            # code...
                            break;
                    }
                }
            }
        }
        return $query;
    }

    public function scopeDateFilter($query, array $dateFilter = []){
        if(count($dateFilter)){
            foreach($dateFilter as $field => $condition){
                foreach($condition as $operator => $date){
                    if($date == 0) { continue; }
                    switch ($operator) {
                        case 'gt':
                            $query->whereDate($field, '>', Carbon::parse($date)->startOfDay());
                            break;
                        case 'gte':
                            $query->whereDate($field, '>=', Carbon::parse($date)->startOfDay());
                            break;
                        case 'lt':
                            $query->whereDate($field, '<', Carbon::parse($date)->startOfDay());
                            break;
                        case 'lte':
                            $query->whereDate($field, '<=', Carbon::parse($date)->startOfDay());
                            break;
                        case 'eq':
                            $query->whereDate($field, '=', Carbon::parse($date)->startOfDay());
                            break;
                        case 'between':
                            [$startDate, $endDate] = explode(',', $date); 
                            $query->whereBetween($field, [
                                Carbon::parse($startDate)->startOfDay(),
                                Carbon::parse($endDate)->endOfDay(),
                            ]);
                            break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }
        return $query;
    }

    public function scopeRelation($query, array $relations = []){
        if(count($relations)){
            $query->with($relations);
            $query->withCount($relations);
        }

        return $query;
    }

    public function scopeRelationFilter($query, array $relationFilter = []){
        if(count($relationFilter)){
            foreach($relationFilter as $key => $val){
                $query->whereHas($key, function($subQuery) use ($val){
                    foreach($val as $field => $condition){
                        foreach($condition as $operator => $valFilter){
                            switch ($operator) {
                                case 'gt':
                                    $subQuery->where($field, '>', $valFilter);
                                    break;
                                case 'gte':
                                    $subQuery->where($field, '>=', $valFilter);
                                    break;
                                case 'lt':
                                    $subQuery->where($field, '<', $valFilter);
                                    break;
                                case 'lte':
                                    $subQuery->where($field, '<=', $valFilter);
                                    break;
                                case 'eq':
                                    $subQuery->where($field, '=', $valFilter);
                                    break;
                                case 'between':
                                    [$min, $max] = explode(',', $valFilter); 
                                    $subQuery->whereBetween($field, [ $min, $max]);
                                    break;
                                case 'in': 
                                    [$field, $in] = explode('|', $valFilter);
                                    $whereIn = explode(',', $in);
                                    if(count($whereIn)){
                                        $subQuery->whereIn($field, $whereIn);                                                    
                                    }
                                default:
                                    # code...
                                    break;
                            }
                        }
                    }
                });
            }
        }
        return $query;
    }

}
