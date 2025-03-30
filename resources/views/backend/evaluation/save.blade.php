@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Đánh giá'" />
<x-errors :errors="$errors" />

@php
    $url = ($config['method'] == 'create') ? route('evaluations.store') : route('evaluations.update', $model->id);
@endphp
<form action="{{ $url }}" method="post" class="box" enctype="multipart/form-data">
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
                        <p>Nhập thông tin chung của phiếu đánh giá</p>
                        <p>Lưu ý: Những trường đánh dấu <span class="text-danger">(*)</span> là bắt buộc</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="ibox">
                    <div class="ibox-content">
                        <div class="row mb15">
                            <div class="col-lg-4">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Nội dung công việc<span class="text-danger">(*)</span></label>
                                    <select name="task_id" class="form-control setupSelect2 ">
                                        <option value="0">[Chọn Công Việc]</option>
                                        @if(isset($tasks))
                                            @foreach($tasks as $key => $val)
                                                <option {{ 
                                                    $val->id == old('task_id', (isset($model->tasks->id)) ? $model->tasks->id : '') ? 'selected' : '' 
                                                    }}  value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Ngày giao việc</label>
                                    <input 
                                        type="date"
                                        name="start_date"
                                        value="{{ old('start_date', (isset($model->start_date)) ? date('Y-m-d', strtotime($model->start_date)) : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Thời gian hoàn thành</label>
                                    <input 
                                        type="date"
                                        name="due_date"
                                        value="{{ old('due_date', (isset($model->due_date)) ? date('Y-m-d', strtotime($model->due_date)) : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row mb15">
                            <div class="col-lg-4">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Thời gian thực tế</label>
                                    <input 
                                        type="number"
                                        name="completion_date"
                                        value="{{ old('completion_date', (isset($model->completion_date)) ? $model->completion_date : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                        min="1.0"
                                        max="100.0"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-row">
                                    <div class="uk-flex uk-flex-middle uk-flex-space-between">
                                        <label for="" class="control-label text-left">Sản phẩm đầu ra</label>
                                    </div>
                                    <input 
                                        type="text"
                                        name="output"
                                        value="{{ old('output', (isset($model->output)) ? $model->output : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-row">
                                    @php
                                        $status_id = null;
                                        if(isset($model)){
                                            $status_id = $model->statuses()->where('user_id', $model->user_id)->first()->pivot->status_id;
                                        }
                                    @endphp
                                    <label for="" class="control-label text-left">Cá nhân tự đánh giá</label>
                                    <select name="status_id" class="form-control setupSelect2 ">
                                        <option value="0">[Chọn Đánh Giá]</option>
                                        @if(isset($statuses))
                                            @foreach($statuses as $key => $val)
                                                <option {{ 
                                                    $val->id == old('status_id', (isset($status_id)) ? $status_id : '') ? 'selected' : '' 
                                                    }}  value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Upload file</label>
                                    <input 
                                        type="file"
                                        name="file"
                                        class="form-control mb10"
                                        placeholder=""
                                        autocomplete="off"
                                    >
                                    @if(isset($model->file) && !empty($model->file))
                                        <div class="mt-2">
                                            <span class="text">
                                                File hiện tại: <a href="{{ asset($model->file) }}" download>{{ basename($model->file) }}</a>
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right mb15">
            <button class="btn btn-primary" type="submit" name="send" value="send">Lưu đánh giá</button>
        </div>
    </div>
</form>
@endsection