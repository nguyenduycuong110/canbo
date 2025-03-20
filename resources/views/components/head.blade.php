@props(['config'])
<base href="{{ config('app.url') }}">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>INSPINIA | Dashboard v.2</title>
<link href="{{ asset('backend/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('backend/font-awesome/css/font-awesome.css') }}" rel="stylesheet">
<link href="{{ asset('backend/css/animate.css') }}" rel="stylesheet">
<link href="{{ asset('backend/plugins/jquery-ui.css') }}" rel="stylesheet">
@if(isset($config['css']) && is_array($config['css']))
    @foreach($config['css'] as $key => $val)
        {!! '<link rel="stylesheet" href="'.asset($val).'"></script>' !!}
    @endforeach
@endif
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link href="{{ asset('backend/css/style.css') }}" rel="stylesheet">
<link href="{{ asset('backend/css/customize.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('backend/plugins/datetimepicker-master/build/jquery.datetimepicker.min.css') }}">




<script src="{{ asset('backend/js/jquery-3.1.1.min.js') }}"></script>
<script src="{{ asset('backend/plugins/datetimepicker-master/build/jquery.datetimepicker.full.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    var BASE_URL = '{{ config('app.url')  }}'
    var SUFFIX = '{{ config('apps.general.suffix')  }}'
</script>
