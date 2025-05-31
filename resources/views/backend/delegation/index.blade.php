@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Uỷ quyền'" />

<div class="row mt20">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Uỷ quyền</h5>
            </div>
            <div class="ibox-content">
                <x-filter :config="$config" />
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" value="" id="checkAll" class="input-checkbox">
                            </th>
                            <th>Người được ủy quyền</th>
                            <th>Chức vụ</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
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
                                    {{ $record->delegates->name }}
                                </td>
                                <td>
                                    {{ $record->delegates->user_catalogues->name }}
                                </td>
                                <td>
                                    {{ !is_null($record->start_date) ? convertDateTime($record->start_date, 'd-m-Y', 'Y-m-d') : '' }}
                                </td>
                                <td>
                                    {{ !is_null($record->end_date) ? convertDateTime($record->end_date, 'd-m-Y', 'Y-m-d') : '' }}
                                </td>
                                <td class="text-center"> 
                                    <a href="{{ route("{$config['route']}.edit", $record->id) }}" class="btn btn-success"><i class="fa fa-edit"></i></a>
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