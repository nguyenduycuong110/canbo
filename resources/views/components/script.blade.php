@props(['config'])
<!-- Mainly scripts -->
<script src="{{ asset('backend/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('backend/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
<script src="{{ asset('backend/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('backend/plugins/jquery-ui.js') }}"></script>



<script src="{{ asset('backend/js/inspinia.js') }}"></script>
{{-- <script src="backend/js/plugins/pace/pace.min.js"></script> --}}
<!-- jQuery UI -->
<script src="{{ asset('backend/js/plugins/toastr/toastr.min.js') }}"></script>
@if(isset($config['js']) && is_array($config['js']))
    @foreach($config['js'] as $key => $val)
        {!! '<script src="'.asset($val).'"></script>' !!}
    @endforeach
@endif

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('backend/library/library.js') }}"></script>