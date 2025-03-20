@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Xếp loại'" />

<div class="row mt20 statistic-form">
    <div class="col-lg-12">
        <div class="ibox float-e-margins mb20">
            <div class="ibox-title">
                <h5>Đánh giá công chức</h5>
            </div>
            <div class="ibox-content">
                <div class="action">
                    <div class="active-name mb10">Chọn ngày và công chức để hoàn thành đánh giá</div>
                    <div class="uk-flex uk-flex-middle uk-flex-space-between">
                        <div class="filter uk-flex uk-flex-middle">
                            <input 
                                type="text"
                                name="date"
                                id="date"
                                value="{{ old('date') }}"
                                class="form-control datepicker mr10"
                                placeholder="dd/mm/yyyy"
                                autocomplete="off"
                            >
                            <select name="user_id" class="setupSelect2">
                                <option value="">[Chọn cán bộ]</option>
                                @foreach($users as $user)
                                    @if($user->user_catalogue_id == config('apps.general.officer'))
                                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->account }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="action">
                            <div class="uk-flex uk-flex-middle">
                                <a href="" class="btn btn-warning mr10">Xuất ra PDF</a>
                                <a href="" class="btn btn-primary">Xuất ra Excel</a>
                            </div>
                        </div>
                    </div>
                    <div class="user-info mt20">
                        <p><span class="label-text">1. Họ Tên</span><span class="value name"> - </span></p>
                        <p><span class="label-text">2. Vị trí, đơn vị công tác</span><span class="value cat_name"> - </span></p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">3. Số ngày làm việc theo quy định của pháp luật trong tháng</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">4. Số ngày nghỉ trong tháng (có phép)</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">5. Số ngày nghỉ trong tháng (không phép)</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">6. Số lần vi phạm qui chế, qui định</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">7. Hành vi vi phạm</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                        <p class="uk-flex uk-flex-middle">
                            <span class="label-text">8. Hình thức kỷ luật</span>
                            <span class="value"> 
                                <input 
                                    type="text"
                                    class="form-control form-control-fix"
                                    name="" 
                                > 
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="ibox float-e-margins mb50">
            <div class="ibox-title">
                <h5>Bảng Kê chi tiết</h5>
            </div>
            <div class="ibox-content">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="col-stt">STT</th>
                                <th>Nội dung công việc</th>
                                <th>Ngày giao việc</th>
                                <th>Ngày hoàn thành</th>
                                <th>Thời gian thực tế</th>
                                <th>Sản phẩm đầu ra</th>
                                <th style="width:220px;">Cá nhân tự đánh giá</th>
                                <th>Lãnh đạo trực tiếp đánh giá</th>
                                <th>Tên lạnh đạo trực tiếp đánh giá</th>
                                <th>Lãnh đạo phê duyệt</th>
                                <th>Tên lãnh đạo phê duyệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection