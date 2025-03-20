@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Chức vụ'" />
<x-errors :errors="$errors" />

@php
    $url = ($config['method'] == 'create') ? route('user_catalogues.store') : route('user_catalogues.update', $model->id);
@endphp

<form action="{{ $url }}" method="post" class="box">
    @if($config['method'] == 'update')
        @method('PUT')
    @endif

    @csrf
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row mb15">
            <div class="col-lg-5">
                <div class="panel-head">
                    <div class="panel-title">Thông tin chung</div>
                    <div class="panel-description">
                        <p>Nhập thông tin chung của chức vụ</p>
                        <p>Lưu ý: Những trường đánh dấu <span class="text-danger">(*)</span> là bắt buộc</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="ibox">
                    <div class="ibox-content">
                        <div class="row mb15">
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Chức vụ <span class="text-danger">(*)</span></label>
                                    <input 
                                        type="text"
                                        name="name"
                                        value="{{ old('name', ($model->name) ?? '' ) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Cấp bậc <span class="text-danger">(*)</span></label>
                                    <select name="level" class="form-control setupSelect2">
                                        @foreach (config('apps.general.level') as $k => $item)
                                            <option 
                                                {{ 
                                                    $k == old('level', (isset($model->level)) ? $model->level : '') ? 'selected' : '' 
                                                }}
                                                value="{{ $k }}"
                                            >
                                                {{ $item }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Quyền tạo công việc <span class="text-danger">(*)</span></label>
                                    <select name="can_create_tasks" class="form-control setupSelect2">
                                        @foreach (config('apps.general.can_create_tasks') as $k => $item)
                                            <option 
                                                {{ 
                                                    $k == old('can_create_tasks', (isset($model->can_create_tasks)) ? $model->can_create_tasks : '') ? 'selected' : '' 
                                                }}
                                                value="{{ $k }}"
                                            >
                                               {{ $item }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Ghi chú</label>
                                    <input 
                                        type="text"
                                        name="description"
                                        value="{{ old('description', ($model->description) ?? '' ) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox">
                    <div class="ibox-content">
                        <table class="table table-striped table-bordered custom">
                            @foreach($permissions as $permission)
                                <tr>
                                    <td class="lft">
                                        <a href="" class="uk-flex uk-flex-middle uk-flex-space-between">{{ $permission->title }} <span style="color:red;">({{ $permission->name }})</span> </a>
                                    </td>
                                    <td class="check">
                                        <input 
                                            type="checkbox" 
                                            name="permissions[]" 
                                            value="{{ $permission->id }}" class="form-control"
                                            {{ (isset($model) && collect($model->permissions)->contains('id', $permission->id)) || in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right mb15">
            <button class="btn btn-primary" type="submit" name="send" value="send">Lưu lại</button>
        </div>
    </div>
</form>


@endsection