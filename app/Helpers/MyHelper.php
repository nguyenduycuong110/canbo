<?php  

if(!function_exists('convertDateTime')){
    function convertDateTime(string $date = '', string $format = 'd/m/Y H:i', string $inputDateFormat = 'Y-m-d H:i:s'){
       $carbonDate = \Carbon\Carbon::createFromFormat($inputDateFormat, $date);

       return $carbonDate->format($format);
    }
}