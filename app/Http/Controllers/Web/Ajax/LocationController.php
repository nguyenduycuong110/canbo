<?php

namespace App\Http\Controllers\Web\Ajax;

use App\Traits\Loggable;
use Illuminate\Http\Request;
use App\Http\Controllers\Web\BaseController;
use App\Services\Interfaces\Area\ProvinceServiceInterface as ProvinceService;
use App\Services\Interfaces\Area\DistrictServiceInterface as DistrictService;

class LocationController extends BaseController{

    protected $provinceService;
    protected $districtService;

    use Loggable;

    public function __construct(
        ProvinceService $provinceService,
        DistrictService $districtService,
    )
    {
        $this->provinceService = $provinceService;
        $this->districtService = $districtService;
    }

    public function getLocation(Request $request){
        try {
            $get = $request->input();
            $html = '';
            if($get['target'] == 'districts'){
                $province = $this->provinceService?->findByCode($get['data']['location_id'],['districts']);
                if(is_null($province)){ return; }
                $html = $this->renderHtml($province->districts);
            }else if($get['target'] == 'wards'){
                $district = $this->districtService?->findByCode($get['data']['location_id'],['wards']);
                if(is_null($district)){ return; }
                $html = $this->renderHtml($district->wards, '[Chọn Phường/Xã]');
            }
            $response = [
                'html' => $html
            ];
            return response()->json($response); 
        }catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function renderHtml($districts, $root = '[Chọn Quận/Huyện]'){
        $html = '<option value="0">'.$root.'</option>';
        foreach($districts as $district){
            $html .= '<option value="'.$district->code.'">'.$district->name.'</option>';
        }
        return $html;
    }


}