<?php   

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

$user = Auth::user();
$user->load(['user_catalogues']);
$user->load(['user_catalogues.permissions']);
$user_catalogues = DB::table('user_catalogues')->where('level','>', $user->user_catalogues->level)->get();
$item = [];
if(isset($user_catalogues) && count($user_catalogues)){
    foreach($user_catalogues as $k => $v){
        $item[] = [
            'title' => "Đánh giá {$v->name}",
            'route' => "evaluations/teams/{$v->id}"
        ];
    }
}
$dashboardMenu  = [
    'title' => 'Dashboard',
    'icon' => 'fa fa-database',
    'name' => ['dashboard'],
    'route' => 'dashboard',
    'class' => 'special'
];
if(!count($user->user_catalogues->permissions)){
    return ['module' => [$dashboardMenu]];
}
$userModules = [];
foreach($user->user_catalogues->permissions as $key => $val){
    $userModules[] = $val->module;
}
$userModules = array_unique($userModules);
$fullMenu = [
    'module' => [
        [
            'title' => 'Dashboard',
            'icon' => 'fa fa-database',
            'name' => ['dashboard'],
            'route' => 'dashboard',
            'class' => 'special'
        ],
        [
            'title' => 'Xếp loại',
            'icon' => 'fa fa-github',
            'name' => ['statistics'],
            'items' => [
                [
                    'title' => 'Xếp loại',
                    'route' => 'statistics'
                ]
            ]
        ],
        [
            'title' => 'QL Cán Bộ',
            'icon' => 'fa fa-user',
            'name' => ['users','user_catalogues', 'permissions','teams','departments'],
            'items' => [
                [
                    'title' => 'QL Chức Vụ',
                    'route' => 'user_catalogues'
                ],
                [
                    'title' => 'QL Cán Bộ',
                    'route' => 'users'
                ],
                [
                    'title' => 'QL Quyền',
                    'route' => 'permissions'
                ],
                [
                    'title' => 'QL Đội',
                    'route' => 'teams'
                ],
                [
                    'title' => 'QL Phòng / Chi cục',
                    'route' => 'units'
                ],
            ]
        ],
        [
            'title' => 'QL Công Việc',
            'icon' => 'fa fa-file',
            'name' => ['tasks'],
            'items' => [
                [
                    'title' => 'QL Công Việc',
                    'route' => 'tasks'
                ]
            ]
        ],
        [
            'title' => 'QL Trạng Thái',
            'icon' => 'fa fa-github',
            'name' => ['statuses'],
            'items' => [
                [
                    'title' => 'QL Trạng Thái',
                    'route' => 'statuses'
                ]
            ]
        ],
        [
            'title' => 'QL Đánh giá',
            'icon' => 'fa fa-github',
            'name' => ['evaluations'],
            'items' => [
                [
                    'title' => 'Tự Đánh Giá',
                    'route' => 'evaluations'
                ],
                ...$item
            ]
        ],
    ]
];
$filteredModule = [];
foreach ($fullMenu['module'] as $module) {
    if (in_array('dashboard', $module['name'])) {
        $filteredModule[] = $module;
        continue;
    }
    $hasPermission = false;
    foreach ($module['name'] as $name) {
        if (in_array($name, $userModules)) {
            $hasPermission = true;
            break;
        }
    }
    if ($hasPermission) {
        if (isset($module['items'])) {
            $filteredItems = [];
            if (in_array('evaluations', $module['name'])) {
                foreach ($module['items'] as $item) {
                    if ($item['route'] === 'evaluations' && in_array('evaluations', $userModules)) {
                        $filteredItems[] = $item;
                    } 
                    else if (strpos($item['route'], 'evaluations/teams/') === 0) {
                        $filteredItems[] = $item;
                    }
                }
            } 
            else {
                foreach ($module['items'] as $item) {
                    $route = $item['route'];
                    if (in_array($route, $userModules)) {
                        $filteredItems[] = $item;
                    }
                }
            }
            if (!empty($filteredItems)) {
                $module['items'] = $filteredItems;
                $filteredModule[] = $module;
            }
        } else {
            $filteredModule[] = $module;
        }
    }
}
return ['module' => $filteredModule];