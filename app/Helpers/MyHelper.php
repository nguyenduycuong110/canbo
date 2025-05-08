<?php  

if(!function_exists('convertDateTime')){
    function convertDateTime(string $date = '', string $format = 'd/m/Y H:i', string $inputDateFormat = 'Y-m-d H:i:s'){
       $carbonDate = \Carbon\Carbon::createFromFormat($inputDateFormat, $date);

       return $carbonDate->format($format);
    }
}

// Define the helper function
if (!function_exists('array_map_with_keys')) {
    function array_map_with_keys(callable $callback, array $array) {
        $result = [];
        foreach ($array as $key => $value) {
            $result += $callback($value, $key);
        }
        return $result;
    }
}


// Define the helper function
if (!function_exists('generateEvalationProcessArray')) {
    function generateEvalationProcessArray() {
        return $levelCount = [
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
    }
}

// Define the helper function
if (!function_exists('caculateTaskPercentage')) {
    function caculateTaskPercentage(array $levelProcessCount = [], int $totalTasks = 0) {
        $percentage = [
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        foreach($levelProcessCount as $key => $val){
            $percentage[$key] = $totalTasks > 0 ? $val / $totalTasks * 100 : 0;
        }
        return $percentage;
    }
}


