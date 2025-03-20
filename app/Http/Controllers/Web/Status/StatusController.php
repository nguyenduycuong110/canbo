<?php 
namespace App\Http\Controllers\Web\Status;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Status\Status\StoreRequest;
use App\Http\Requests\Status\Status\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Status\StatusServiceInterface as StatusService;


class StatusController extends BaseController{

    protected $namespace = 'status';
    protected $route = 'statuses';

    protected $service;


    public function __construct(
        StatusService $service
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