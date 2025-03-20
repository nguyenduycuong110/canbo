<?php  
namespace App\Services\Interfaces\Auth;
use App\Http\Requests\Web\Auth\AuthRequest;
use Illuminate\Http\Request;

interface AuthWebServiceInterface {

    public function signin(AuthRequest $request): bool;
    public function signout(Request $request): bool;

}