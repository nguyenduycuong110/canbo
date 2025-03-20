<?php  
namespace App\Http\Controllers\Web\Dashboard;

use App\Http\Controllers\Controller;
use App\Traits\Loggable;
use App\Services\Interfaces\Auth\AuthWebServiceInterface as AuthService;

class DashboardController extends Controller{

    use Loggable;

    private $authService;

    public function __construct(
        AuthService $authService,
    ){
        $this->authService = $authService;
    }

    public function index(){
        return view('backend.dashboard.home.index');
    }

   
}