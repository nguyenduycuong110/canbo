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

    public function scopeDateFilter($query, array $dateFilter = [])
    {
        if (count($dateFilter)) {
            foreach ($dateFilter as $field => $condition) {
                foreach ($condition as $operator => $date) {
                    if ($date == 0) {
                        continue;
                    }

                    // Hàm helper để chuẩn hóa định dạng ngày
                    $parseDate = function ($dateString) {
                        // Danh sách các định dạng ngày phổ biến
                        $possibleFormats = [
                            'Y-m-d',        // 2025-03-16
                            'd-m-Y',        // 16-03-2025
                            'd/m/Y',        // 16/03/2025
                            'Y/m/d',        // 2025/03/16
                            'd.m.Y',        // 16.03.2025
                            'Y-m-d H:i:s',
                            'Y-m-d H:i',
                            'Y-m-d H'
                        ];

                        // Thử parse với từng định dạng
                        foreach ($possibleFormats as $format) {
                            try {
                                $parsedDate = Carbon::createFromFormat($format, $dateString);
                                if ($parsedDate) {
                                    return $parsedDate->format('Y-m-d'); // Chuẩn hóa về Y-m-d
                                }
                            } catch (\Exception $e) {
                                // Bỏ qua nếu không parse được với định dạng này
                                continue;
                            }
                        }

                        // Nếu không parse được với bất kỳ định dạng nào, ném ngoại lệ
                        throw new \Exception("Không thể parse ngày: $dateString. Định dạng không hợp lệ.");
                    };

                    try {
                        switch ($operator) {
                            case 'gt':
                                $parsedDate = $parseDate($date);
                                $query->whereDate($field, '>', Carbon::parse($parsedDate)->startOfDay());
                                break;
                            case 'gte':
                                $parsedDate = $parseDate($date);
                                $query->whereDate($field, '>=', Carbon::parse($parsedDate)->startOfDay());
                                break;
                            case 'lt':
                                $parsedDate = $parseDate($date);
                                $query->whereDate($field, '<', Carbon::parse($parsedDate)->startOfDay());
                                break;
                            case 'lte':
                                $parsedDate = $parseDate($date);
                                $query->whereDate($field, '<=', Carbon::parse($parsedDate)->startOfDay());
                                break;
                            case 'eq':
                                $parsedDate = $parseDate($date);
                                $query->whereDate($field, '=', Carbon::parse($parsedDate)->startOfDay());
                                break;
                            case 'between':
                                [$startDate, $endDate] = array_map('trim', explode(',', $date));
                                $parsedStartDate = $parseDate($startDate);
                                $parsedEndDate = $parseDate($endDate);
                                $query->whereBetween($field, [
                                    Carbon::parse($parsedStartDate)->startOfDay(),
                                    Carbon::parse($parsedEndDate)->endOfDay(),
                                ]);
                                break;
                            default:
                                break;
                        }
                    } catch (\Exception $e) {
                        // Ghi log lỗi nếu cần
                        // \Log::error('Lỗi parse ngày trong scopeDateFilter: ' . $e->getMessage());
                        // Bỏ qua điều kiện này để không làm gián đoạn truy vấn
                        // dd($e);
                        continue;
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
