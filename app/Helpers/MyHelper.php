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