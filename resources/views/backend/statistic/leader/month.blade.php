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
                <form action="">
                    <div class="action">
                        <div class="active-name mb10">Chọn ngày và công chức để hoàn thành đánh giá</div>
                        <div class="uk-flex uk-flex-middle uk-flex-space-between">
                            <div class="filter uk-flex uk-flex-middle">
                                <input 
                                    type="text"
                                    name="date"
                                    id="date"
                                    value="{{ old('date') }}"
                                    class="form-control monthPicker mr10 evaluation-time"
                                    style="height:32px;"
                                />
                                <input type="hidden" value="month" class="date-type">
                                @if($auth->user_catalogues->level == 1 || $auth->user_catalogues->level == 2)
                                    <select name="team_id" class="setupSelect2 team_vice_id">
                                        <option value="">[Chọn đội]</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team['id'] }}">{{ $team['name'] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @if($auth->rgt - $auth->lft > 1 && $auth->user_catalogues->level !== $level)
                                    <select name="user_id" class="setupSelect2 user_id">
                                        <option value="">[Chọn cán bộ]</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->account }}</option>
                                        @endforeach
                                    </select>
                                @else
                                <input type="text" class="hidden user_id" value="{{ $auth->id }}">
                                @endif
                            </div>
                            <div class="action">
                                <div class="uk-flex uk-flex-middle">
                                    <button type="submit" value="pdf" class="btn-export btn btn-warning mr10">Xuất ra PDF</button>
                                    <button type="submit" value="excel" class="btn-export btn btn-primary">Xuất ra Excel</button>
                                </div>
                            </div>
                        </div>
                        <div class="user-info mt20">
                            <p><span class="label-text">1. Họ Tên</span><span class="value name"> {{  $auth->name }} </span></p>
                            <p><span class="label-text">2. Vị trí, đơn vị công tác</span><span class="value cat_name"> {{ $auth->teams->name }}, {{ $auth->units->name }} </span></p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">3. Số ngày làm việc theo quy định của pháp luật trong tháng</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="working_days_in_month" 
                                    > 
                                </span>
                            </p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">4. Số ngày nghỉ trong tháng (có phép)</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="leave_days_with_permission" 
                                    > 
                                </span>
                            </p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">5. Số ngày nghỉ trong tháng (không phép)</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="leave_days_without_permission" 
                                    > 
                                </span>
                            </p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">6. Số lần vi phạm qui chế, qui định</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="violation_count" 
                                    > 
                                </span>
                            </p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">7. Hành vi vi phạm</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="violation_behavior" 
                                    > 
                                </span>
                            </p>
                            <p class="uk-flex uk-flex-middle">
                                <span class="label-text">8. Hình thức kỷ luật</span>
                                <span class="value"> 
                                    <input 
                                        type="text"
                                        class="form-control form-control-fix"
                                        name="disciplinary_action" 
                                    > 
                                </span>
                            </p>
                        </div>
                        <input type="text" class="hidden level" value="{{ $level }}">
                    </div>
                </form>
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
                                <th  class="col-stt">STT</th>
                                <th>Nội dung công việc</th>
                                <th>Ngày</th>
                                <th>Tổng số công việc / nhiệm vụ được giao</th>
                                <th>Số công việc / nhiệm vụ hoàn thành <br> vượt mức về thời gian hoặc chất lượng</th>
                                <th>Số công việc / nhiệm vụ hoàn thành <br> đúng hạn , đảm bảo chất lượng</th>
                                <th>Số công việc / nhiệm vụ không hoàn thành <br> đúng hạn hoặc không đảm bảo yêu cầu</th>
                                <th>Ghi chú</th>
                                <th>Cá nhân tự đánh giá</th>
                                <th>Lãnh đạo trực tiếp đánh giá</th>
                                <th class="text-center">Lãnh đạo đánh giá</th>
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