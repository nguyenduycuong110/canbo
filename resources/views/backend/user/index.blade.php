@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Quản Lý Cán Bộ'" />

<div class="row mt20">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Quản lý cán bộ</h5>
            </div>
            <div class="ibox-content">
                @include('components.filter',['config' => $config, 'teams' => $teams])
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>
                            <input type="checkbox" value="" id="checkAll" class="input-checkbox">
                        </th>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Chức vụ</th>
                        <th>Đội</th>
                        <th>Phòng / chi cục</th>
                        <th>Tên đăng nhập</th>
                        <th class="text-center">Tình Trạng</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                        @if(isset($records) && (is_object($records) || is_array($records)) && count($records) > 0)
                            @foreach($records as $record)
                            <tr >
                                <td>
                                    <input type="checkbox" value="{{ $record->id }}" class="input-checkbox checkBoxItem">
                                </td>
                                <td>
                                    {{ $record->id }}
                                </td> 
                                <td>
                                    {{ str_repeat('|----', (($record->level > 0)?($record->level - 1):0)).$record->name }}
                                </td>
                                <td>
                                    {{ $record->user_catalogues->name }}
                                </td>
                                <td>
                                    {{ $record->teams->name }}
                                </td>
                                <td>
                                    {{ $record->units->name }}
                                </td>
                                <td>
                                    {{ 
                                        $record->account
                                    }}
                                </td>
                                <td class="text-center js-switch-{{ $record->id }}"> 
                                    <input type="checkbox" value="{{ $record->publish }}" class="js-switch status " data-field="publish" {{ ($record->publish == 2) ? 'checked' : '' }} data-model="{{ $config['model'] }}" data-modelId="{{ $record->id }}" />
                                </td>
                                <td class="text-center"> 
                                    <a href="{{ route("{$config['route']}.edit", $record->id) }}" class="btn btn-success"><i class="fa fa-edit"></i></a>
                                    <a href="{{ route("{$config['route']}.delete", $record->id) }}" class="btn btn-danger"><i class="fa fa-trash"></i></a>
                                    <a href="{{ route("users.resetPassword", $record->id) }}" class="btn btn-warning"><i class="fa fa-unlock"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center text-danger">Không tìm thấy bản ghi phù hợp</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                {{ $records->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>



@endsection