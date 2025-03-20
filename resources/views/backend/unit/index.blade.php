@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Quản Lý Phòng / Chi cục'" />

<div class="row mt20">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Quản lý phòng / chi cục</h5>
            </div>
            <div class="ibox-content">
                <x-filter :config="$config" />
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>
                            <input type="checkbox" value="" id="checkAll" class="input-checkbox">
                        </th>
                        <th>Phòng / Chi cục</th>
                        <th class="text-center">Mô tả</th>
                        <th class="text-center">Tình trạng</th>
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
                                    {{ $record->name }}
                                </td>
                                <td>
                                    {{ $record->description }}
                                </td>
                                <td class="text-center js-switch-{{ $record->id }}"> 
                                    <input type="checkbox" value="{{ $record->publish }}" class="js-switch status " data-field="publish" {{ ($record->publish == 2) ? 'checked' : '' }} data-model="{{ $config['model'] }}" data-modelId="{{ $record->id }}" />
                                </td>
                                <td class="text-center"> 
                                    <a href="{{ route("{$config['route']}.edit", $record->id) }}" class="btn btn-success"><i class="fa fa-edit"></i></a>
                                    <a href="{{ route("{$config['route']}.delete", $record->id) }}" class="btn btn-danger"><i class="fa fa-trash"></i></a>
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
                {{  $records->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>



@endsection