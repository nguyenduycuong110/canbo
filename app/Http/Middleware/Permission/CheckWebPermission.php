<?php

namespace App\Http\Middleware\Permission;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\Loggable;
use Illuminate\Support\Str;
use App\Repositories\Permission\PermissionRepository;
use App\Enums\Config\Common;
use Illuminate\Support\Facades\Lang;

class CheckWebPermission
{

    protected $baseRedirect = 'dashboard.index';

    use Loggable;

    private $permissionRepository;
    private $auth;

    public function __construct(
        PermissionRepository $permissionRepository
    )
    {
        $this->permissionRepository = $permissionRepository;
        $this->auth = auth(COMMON::WEB);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $action = $request->route()->getActionName();
            [$controller, $method] = explode('@', $action);
            $baseControllerName = str_replace('_controller', '', Str::snake(class_basename($controller)));
            $controllerName = $baseControllerName . (substr($baseControllerName, -1) === 's' ? 'es' : 's');
            $methodNoCheck = ['store','update', 'destroy'];
            if(in_array($method, $methodNoCheck)){
                return $next($request);
            }
            $permissionName = "{$controllerName}:{$method}";
            if(!$permission = $this->permissionRepository->findByName($permissionName)){
                flash()->error(Lang::get('message.not_permission'));
                return redirect()->route($this->baseRedirect);
            }
            $requiredValue = (int)$permission->value;
            /** @var User user */
            $user = $this->auth->user();
            if(!$user){
                flash()->error(Lang::get('message.not_permission'));
                return redirect()->route($this->baseRedirect);
            }
            $user->load(['user_catalogues']);
            $user->load(['user_catalogues.permissions']);
            if(!$user->user_catalogues){
                flash()->error(Lang::get('message.not_permission'));
                return redirect()->route($this->baseRedirect);
            }
            $hasPermission = false;
            foreach($user->user_catalogues->permissions as $key => $val){
                if($val->module != $controllerName){
                    continue;
                }
                $permissions = [$val->value];
                $totalPermissions = array_reduce($permissions, function($carry, $item){
                    return $carry | $item;
                }, 0);
                if(($totalPermissions & $requiredValue) === $requiredValue){
                    $hasPermission = true;
                    break;
                }
            }
            if(!$hasPermission){
                flash()->error(Lang::get('message.not_permission'));
                return redirect()->route($this->baseRedirect);
            }

        } catch (\Exception $e) {
            return $this->handleLogException($e);
        }

        return $next($request);
    }
}
