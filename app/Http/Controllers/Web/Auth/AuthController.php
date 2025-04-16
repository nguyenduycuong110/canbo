<?php  
namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Traits\Loggable;
use App\Http\Requests\Web\Auth\AuthRequest;
use Illuminate\Http\RedirectResponse;
use App\Services\Interfaces\Auth\AuthWebServiceInterface as AuthService;
use Flasher\Prime\FlasherInterface as Flasher;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;

class AuthController extends Controller{

    use Loggable;

    private $authService;
    
    public function __construct(
        AuthService $authService,
    ){
        $this->authService = $authService;
    }

    public function index(){
        return view('backend.auth.login');
    }

    public function signin(AuthRequest $request, Flasher $flasher): RedirectResponse{
        try {
            if( $response = $this->authService->signin($request)){
                flash()->success(Lang::get('message.auth_success'));
                return redirect()->route('dashboard.index');
            }else{
                flash()->error(Lang::get('message.auth_failed'));
                return redirect()->route('auth.login');
            }
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }

    public function signout(Request $request): RedirectResponse{
        try {
            if($response = $this->authService->signout($request)){
                flash()->success(Lang::get('message.signout_success'));
                return redirect()->route('auth.login');
            }else{
                flash()->error(Lang::get('message.error'));
                return redirect()->back();
            }
        } catch (\Throwable $th) {
            return $this->handleWebLogException($th);
        }
    }
}