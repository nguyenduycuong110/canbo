<!DOCTYPE html>
<html>

<head>
   <x-head :config="$config ?? []" />

</head>

    <body>
        <div id="wrapper">
            <x-sidebar />

            <div id="page-wrapper" class="gray-bg">
                <x-nav />
                @yield('content')
                <x-footer />
            </div>
        </div>
        <x-script :config="$config ?? []" />
    </body>
</html>
