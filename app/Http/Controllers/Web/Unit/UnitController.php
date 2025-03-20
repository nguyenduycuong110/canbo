<?php 
namespace App\Http\Controllers\Web\Unit;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Unit\Unit\StoreRequest;
use App\Http\Requests\Unit\Unit\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Unit\UnitServiceInterface as UnitService;


class UnitController extends BaseController{

    protected $namespace = 'unit';
    protected $route = 'units';

    protected $service;


    public function __construct(
        UnitService $service
    )
    {
        $this->service = $service;
        parent::__construct($service);
    }
    public function store(StoreRequest $request): RedirectResponse{
        return $this->baseSave($request);
    }
    public function update(UpdateRequest $request, int $id){
        return $this->baseSave($request, $id);
    }


}   