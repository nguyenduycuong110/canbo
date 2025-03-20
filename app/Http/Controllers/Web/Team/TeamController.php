<?php 
namespace App\Http\Controllers\Web\Team;

use App\Http\Controllers\Web\BaseController;
use App\Http\Requests\Team\Team\StoreRequest;
use App\Http\Requests\Team\Team\UpdateRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Team\TeamServiceInterface as TeamService;


class TeamController extends BaseController{

    protected $namespace = 'team';
    protected $route = 'teams';

    protected $service;


    public function __construct(
        TeamService $service
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