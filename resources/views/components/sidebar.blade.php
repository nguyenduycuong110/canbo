@php
   $user =  request()->user();
   $user->load('user_catalogues');
   $segment = request()->segment(1);
@endphp
<nav class="navbar-default navbar-static-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav metismenu" id="side-menu">
            <li class="nav-header">
                <div class="dropdown profile-element"> <span>
                        <img alt="image" class="img-circle" src="backend/img/profile_small.jpg" />
                         </span>
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold">{{ $user->name }}</strong>
                         </span> <span class="text-muted text-xs block">{{ $user->user_catalogues->name }}<b class="caret"></b></span> </span> </a>
                    <ul class="dropdown-menu animated fadeInRight m-t-xs">
                        <li><a href="{{ route('users.profile') }}">Profile</a></li>
                        <li><a href="contacts.html">Contacts</a></li>
                        <li><a href="mailbox.html">Mailbox</a></li>
                        <li class="divider"></li>
                        <li><a href="{{ route('auth.signout') }}">Đăng xuất</a></li>
                    </ul>
                </div>
                <div class="logo-element">
                    IN+
                </div>
            </li>
            @foreach(__('function.module') as $key => $val)
            <li class=" {{ (isset($val['class'])) ? $val['class'] : '' }} {{ (in_array($segment, $val['name'])) ? 'active' : '' }}">
                <a href="{{ (isset($val['route'])) ? $val['route'] : '' }}">
                    <i class="{{ $val['icon'] }}"></i> 
                    <span class="nav-label">{{ $val['title'] }}</span> 
                    @if(isset($val['items']) && count($val['items']))
                    <span class="fa arrow"></span>
                    @endif
                </a>
                @if(isset($val['items']))
                    <ul class="nav nav-second-level">
                        @foreach($val['items'] as $module)
                            <?php
                                // Kiểm tra xem có menu cấp 3 nào đang active không
                                $isSubMenuActive = false;
                                if (isset($module['items']) && count($module['items'])) {
                                    foreach ($module['items'] as $subItem) {
                                        if (request()->is($subItem['route'] . '*')) {
                                            $isSubMenuActive = true;
                                            break;
                                        }
                                    }
                                } else {
                                    // Nếu không có menu cấp 3, kiểm tra route của menu cấp 2
                                    $isSubMenuActive = request()->is($module['route'] . '*');
                                }
                            ?>
                            <li class="{{ (isset($module['items']) && count($module['items'])) ? 'has-submenu' : '' }} {{ $isSubMenuActive ? 'active' : '' }}">
                                @if(isset($module['items']) && count($module['items']))
                                    <a href="#">{{ $module['title'] }} <span class="fa arrow"></span></a>
                                    <ul class="nav nav-third-level">
                                        @foreach($module['items'] as $subItem)
                                        <li class="{{ (request()->is($subItem['route'] . '*')) ? 'active' : '' }}">
                                            <a href="{{ $subItem['route'] }}">{{ $subItem['title'] }}</a>
                                        </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <a href="{{ $module['route'] }}">{{ $module['title'] }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
        </ul>
    </div>
</nav>