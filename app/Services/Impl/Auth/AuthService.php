<?php  
namespace App\Services\Impl\Auth;

use App\Http\Requests\Web\Auth\AuthRequest;
use App\Services\BaseService;
use App\Services\Interfaces\Auth\AuthWebServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthService implements AuthWebServiceInterface{

    private $auth;

    public function __construct()
    {
        
    }

    public function signin(AuthRequest $request): bool
    {
        try {
            $credentials = [
                'account' => $request->string('account'),
                'password' => $request->string('password')
            ];

            if(Auth::attempt($credentials)){
                $request->session()->regenerate();
                return true;
            }
            return false;
        } catch (\Throwable $th) {
          throw $th;
        }
    }

    public function signout(Request $request): bool{
        try {
            Auth::logout();
            $request->session()->forget('session_web');
            $request->session()->regenerateToken();
            return true;
           
        } catch (\Throwable $th) {
            throw $th;
        }
    }


}