<?php   

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


/** @var \App\Models\User|null $user */
$currentDate = Carbon::now()->format('Y-m-d');
$user = Auth::user();
$user->load(['user_catalogues.permissions']);
$user_catalogues = DB::table('user_catalogues')
        ->select(
            'level',
            DB::raw('GROUP_CONCAT(id) as ids'), 
            DB::raw('MAX(name) as names') 
        )
        ->where('level','>=', $user->user_catalogues->level)
        ->groupBy(['level'])
        ->get();

/*Danh sách ủy quyền */

$authorizedMenu = null;

$delegator_id = DB::table('delegations')->where('delegate_id', $user->id)
                ->where('start_date', '<=', $currentDate)
                ->where('end_date', '>=', $currentDate)
                ->first()->delegator_id ?? null;

if(!is_null($delegator_id)){
    $temp = [];
    $delegator = DB::table('users')->where('id', $delegator_id)->first();
    $level = DB::table('user_catalogues')->where('id', $delegator->user_catalogue_id)->first()->level;
    $user_catalogues = DB::table('user_catalogues')
        ->select(
            'level',
            DB::raw('GROUP_CONCAT(id) as ids'), 
            DB::raw('MAX(name) as names') 
        )
        ->where('level','>', $level)
        ->groupBy(['level'])
        ->get();
    
    foreach($user_catalogues as $k => $v){
        $displayName = str_replace(',', ',', $v->names);
        $temp[] = [
            'title' => "{$displayName}",
            'route' => "delegations/evaluations/teams/{$v->level}"
        ];
        
    }
    $authorizedMenu = [
        'title' => 'Danh sách ủy quyền',
        'name' => ['delegationsList'],
        'route' => 'delegations/evaluation',
        'icon' => 'fa fa-github',
        'items' => [
            ...$temp
        ]
    ];
}

$authorityMenu =  null;

if($user->user_catalogues->level == 3){

    $authorityMenu = [
            'title' => 'Uỷ quyền',
            'icon' => 'fa fa-github',
            'name' => ['delegations'],
            'route' => 'delegations'
    ];

}

$item = [];

$userLevel = $user->user_catalogues->level;

$statisticItems = [];

if(isset($user_catalogues) && count($user_catalogues)){
    foreach($user_catalogues as $k => $v){
        $displayName = str_replace(',', ',', $v->names);
        if($userLevel !== $v->level){
            $item[] = [
                'title' => "{$displayName}",
                'route' => "evaluations/teams/{$v->level}"
            ];
        }
       
        $statisticItems[] = [
            'title' => "{$displayName}", 
            'route' => '#',
            'items' => [ 
                [
                    'title' => 'Đánh giá theo ngày',
                    'route' => "statistics/departmentDay".(($v->level !== 5) ? '/leader' : '')."/{$v->level}"
                ],
                [
                    'title' => 'Đánh giá theo tháng',
                    'route' => "statistics/departmentMonth".(($v->level !== 5) ? '/leader' : '')."/{$v->level}"
                ],
            ]
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
        [
            'title' => 'Lịch sử đánh giá',
            'icon' => 'fa fa-github',
            'name' => ['statistics'],
            'items' => $statisticItems
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
            'title' => 'Xếp loại chất lượng',
            'icon' => 'fa fa-github',
            'name' => ['statistics'],
            'route' => 'team/rank',
            'class' => 'special'
        ],
        [
            'title' => 'Kết xuất',
            'icon' => 'fa fa-database',
            'name' => ['statistics'],
            'route' => 'team/export',
            'class' => 'special'
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
        $authorityMenu,
        $authorizedMenu
    ]
];

$filteredModule = [];

foreach ($fullMenu['module'] as $module) {

    if($module == null){ continue; }

    if (in_array('dashboard', $module['name'])) {
        $filteredModule[] = $module;
        continue;
    }

    if (in_array('delegationsList', $module['name'])) {
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

    if (isset($module['route']) && $module['route'] === 'team/rank') {
        $hasPermission = false;
        foreach ($user->user_catalogues->permissions as $permission) {
            if ($permission->module === 'statistics' && $permission->name=== 'statistics:rankQuality') {
                $hasPermission = true;
                break;
            }
        }
    }
    
    if (isset($module['route']) && $module['route'] === 'team/export') {
        $hasPermission = false;
        foreach ($user->user_catalogues->permissions as $permission) {
            if ($permission->module === 'statistics' && $permission->name=== 'statistics:exportHistory') {
                $hasPermission = true;
                break;
            }
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
            } else if(in_array('statistics', $module['name'])){
                foreach ($module['items'] as $item) {
                    $filteredItems[] = $item;
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