<?php

namespace App\Http\Controllers\Web\Ajax;
use App\Traits\Loggable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Web\BaseController;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    use Loggable;

    public function __construct(){}

    public function changeStatus(Request $request){
        try {
            $post = $request->input();
            $impl = $post['model'];
            $impl = str_contains($impl, 'Catalogue') ? str_replace('Catalogue', '' , $impl) :  $impl;
            $serviceInterfaceNamespace = 'App\Services\Impl\\' . $impl . '\\' . ucfirst($post['model']) . 'Service';
            if(class_exists($serviceInterfaceNamespace)) {
                $serviceInstance = app($serviceInterfaceNamespace);
            }
            $flag = $serviceInstance->updateStatus($post);
            return response()->json(['flag' => $flag]); 
        } catch (ModelNotFoundException $e) {
            flash()->error($e->getMessage());
        } catch(\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }
    
}
