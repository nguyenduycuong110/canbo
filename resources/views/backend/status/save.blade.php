@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Trạng thái'" />
<x-errors :errors="$errors" />

@php
    $url = ($config['method'] == 'create') ? route('statuses.store') : route('statuses.update', $model->id);
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
                        <p>Nhập thông tin chung của trạng thái</p>
                        <p>Lưu ý: Những trường đánh dấu <span class="text-danger">(*)</span> là bắt buộc</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="ibox">
                    <div class="ibox-content">
                        <div class="row mb15">
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Trạng thái<span class="text-danger">(*)</span></label>
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
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Điểm<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="text"
                                        name="point"
                                        value="{{ old('point', ($model->point) ?? 0 ) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Level<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="text"
                                        name="level"
                                        value="{{ old('level', ($model->level) ?? 0 ) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Mô tả</label>
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
        <div class="text-right mb15">
            <button class="btn btn-primary" type="submit" name="send" value="send">Lưu lại</button>
        </div>
    </div>
</form>
@endsection