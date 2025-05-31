@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Uỷ quyền'" />
<x-errors :errors="$errors" />

@php
    $url = ($config['method'] == 'create') ? route('delegations.store') : route('delegations.update', $model->id);
@endphp

<form action="{{ $url }}" method="post" class="box">
    @if($config['method'] == 'update')
        @method('PUT')
    @endif

    @csrf
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-5">
                <div class="panel-head">
                    <div class="panel-title">Thông tin chung</div>
                    <div class="panel-description">
                        <p>Nhập thông tin chung </p>
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
                                    <label for="" class="control-label text-left">Chọn người ủy quyền <span class="text-danger">(*)</span></label>
                                    <select name="delegate_id" class="form-control setupSelect2">
                                        <option value="0">Chọn người ủy quyền </option>
                                        @if(isset($users))
                                            @foreach($users as $key => $val)
                                                    <option {{ 
                                                        $val->id == old('delegate_id', (isset($model->delegate_id)) ? $model->delegate_id : '') ? 'selected' : '' 
                                                    }}  value="{{ $val->id }}">{{ $val->name }} - {{ $val->user_catalogues->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Từ ngày <span class="text-danger">(*)</span></label>
                                    <input 
                                        type=""
                                        name="start_date"
                                        value="{{ old('start_date', (isset($model->start_date)) ? date('d/m/Y', strtotime($model->start_date)) : '') }}"
                                        class="form-control datepicker"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <div class="form-row">
                                    <label for="" class="control-label text-left">Đến ngày <span class="text-danger">(*)</span></label>
                                    <input 
                                        type=""
                                        name="end_date"
                                        value="{{ old('end_date', (isset($model->end_date)) ? date('d/m/Y', strtotime($model->end_date)) : '') }}"
                                        class="form-control datepicker"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                                </div>
                            </div>
                            <input type="hidden" name="delegator_id" value="{{ $auth->id }}">
                        </div>
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