<?php  
namespace App\Traits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

trait Loggable {

    public function handleWebLogException(\Throwable $th, $message = "Error Message: "){
        Log::error($message, [
            'message' => $th->getMessage(),
            'line' => $th->getLine(),
            'file' => $th->getFile()
        ]);
        flash()->error(Lang::get('message.action_failed'));
        return redirect()->route($this->baseRedirect);
    }
    

}