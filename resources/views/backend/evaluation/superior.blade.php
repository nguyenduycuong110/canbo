@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Đánh giá'" />
<x-errors :errors="$errors" />

@php
    $url = ($config['method'] == 'create') ? route('evaluations.store') : route('evaluations.update', $model->id);
@endphp
<form action="{{ $url }}" method="post" class="box">
    @if($config['method'] == 'update')
        @method('PUT')
    @endif
    @csrf
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-4">
                <div class="panel-head">
                    <div class="panel-title">Thông tin chung</div>
                    <div class="panel-description">
                        <p>Nhập thông tin chung của phiếu đánh giá</p>
                        <p>Lưu ý: Những trường đánh dấu <span class="text-danger">(*)</span> là bắt buộc</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="ibox">
                    <div class="ibox-content">
                        <div class="row mb15">
                            <div class="col-lg-12">
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
                        </div>
                        <div class="row mb15">
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Tổng công việc / nhiệm vụ được giao<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="number"
                                        name="total_tasks"
                                        value="{{ old('total_tasks', (isset($model->total_tasks)) ? $model->total_tasks : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                        min="1"
                                        max="100"
                                        step="1"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row mb15">
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Số công việc / nhiệm vụ hoàn thành vượt mức về thời gian hoặc chất lượng<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="number"
                                        name="overachieved_tasks"
                                        value="{{ old('overachieved_tasks', (isset($model->overachieved_tasks)) ? $model->overachieved_tasks : '') }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                        min="1"
                                        max="100"
                                        step="1"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Số công việc / nhiệm vụ hoàn thành đúng hạn, đảm bảo chất lượng<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="number"
                                        name="completed_tasks_ontime"
                                        value="{{ old('completed_tasks_ontime', (isset($model->completed_tasks_ontime )) ? $model->completed_tasks_ontime  : 0) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                        min="1"
                                        max="100"
                                        step="1"
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-row">
                                    <label for="" class="control-label text-left">Số công việc / nhiệm vụ không hoàn thành đúng hạn hoặc không đảm bảo yêu cầu<span class="text-danger">(*)</span></label>
                                    <input 
                                        type="number"
                                        name="failed_tasks_count"
                                        value="{{ old('failed_tasks_count', (isset($model->failed_tasks_count)) ? $model->failed_tasks_count : 0) }}"
                                        class="form-control"
                                        placeholder=""
                                        autocomplete="off"
                                        min="1"
                                        max="100"
                                        step="1"
                                    >
                                </div>
                            </div>
                            <div class="col-lg-6">
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