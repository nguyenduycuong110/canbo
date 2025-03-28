@extends('backend.dashboard.layout')

@section('content')
    
<x-breadcrumb :title="'Quản Lý Đánh Giá'" />

<div class="row mt20 evaluations">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Quản lý đánh giá</h5>
            </div>
            <div class="ibox-content">
                @include('backend.evaluation.filterTeam')
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center col-stt">STT</th>
                                <th>Ngày xong</th>
                                <th>Thời gian</th>
                                <th>SP đầu ra</th>
                                <th>Cá nhân tự đánh giá</th>
                                <th>Đánh giá của Đội phó</th>
                                @foreach($allPositionsData as $posKey => $posData)
                                    <th>Đánh giá của {{ $posData['name'] }}</th>
                                @endforeach
                                <th>Điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($records) && (is_object($records) || is_array($records)) && count($records) > 0)
                                @foreach($records as $k => $record)
                                    <tr>
                                        <td class="text-center col-stt">
                                            {{ $record->id }}
                                        </td>
                                        <td class="col-time">
                                            {{ $record->due_date }}
                                        </td>
                                        <td class="text-center col-time">
                                            {{ $record->completion_date }}
                                        </td>
                                        <td>
                                            {{ $record->output }}
                                        </td>
                                        <td>
                                            @if($record->selfEvaluation)
                                                {{ $statuses->where('id', $record->selfEvaluation)->first()->name ?? 'N/A' }}
                                            @else
                                                <span class="text-muted">Chưa đánh giá</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isDeputyTeamLeader && !$record->higherLevelEvaluated)
                                                <!-- Nếu người đăng nhập là Đội phó và bản ghi chưa bị khóa, chỉ hiển thị dropdown -->
                                                <select name="status_id" class="form-control setupSelect2" data-record-id="{{ $record->id }}" data-deputy="true">
                                                    <option value="0">[Chọn Đánh Giá]</option>
                                                    @foreach($statuses as $status)
                                                        <option value="{{ $status->id }}" {{ $record->deputyEvaluation && $status->id == $record->deputyEvaluation['status_id'] ? 'selected' : '' }}>
                                                            {{ $status->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <!-- Nếu bản ghi đã bị khóa hoặc người đăng nhập không phải Đội phó, hiển thị text -->
                                                @if($record->deputyEvaluation)
                                                    {{ $statuses->where('id', $record->deputyEvaluation['status_id'])->first()->name ?? 'N/A' }}
                                                    <br>
                                                    <small class="text-success">Họ Tên: {{ $record->deputyEvaluation['user_name'] }}</small>
                                                @else
                                                    <span class="text-muted">Chưa đánh giá</span>
                                                @endif
                                                @if($record->higherLevelEvaluated)
                                                    <br>
                                                    <span class="text-danger">Đã khóa đánh giá</span>
                                                @endif
                                            @endif
                                        </td>
                                        @foreach($allPositionsData as $posKey => $posData)
                                            <td class="p-w-100">
                                                @if($posData['is_current_user'] && !$record->higherLevelEvaluated)
                                                    <select name="status_id" class="form-control setupSelect2 w-100" data-record-id="{{ $record->id }}">
                                                        <option value="0">[Chọn Đánh Giá]</option>
                                                        @foreach($statuses as $status)
                                                            <option value="{{ $status->id }}" {{ $status->id == $record->currentUserStatusId ? 'selected' : '' }}>
                                                                {{ $status->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @elseif($posData['is_current_user'] && $record->higherLevelEvaluated && $record->currentUserStatusId > 0)
                                                    {{ $statuses->where('id', $record->currentUserStatusId)->first()->name ?? 'N/A' }}
                                                    <br>
                                                    <span class="text-danger">Đã khóa đánh giá</span>
                                                @elseif($posData['is_current_user'] && $record->higherLevelEvaluated)
                                                    <span class="text-muted">Chưa đánh giá</span>
                                                    <br>
                                                    <span class="text-danger">Đã khóa đánh giá</span>
                                                @else
                                                    @if(isset($record->positionEvaluations[$posData['name']]))
                                                        {{ $statuses->where('id', $record->positionEvaluations[$posData['name']]['status_id'])->first()->name ?? 'N/A' }}
                                                        <br>
                                                        <small class="text-success">
                                                            Họ Tên: {{ $record->positionEvaluations[$posData['name']]['user_name'] }} <span class="text-danger">({{ $record->positionEvaluations[$posData['name']]['point'] }}đ)</span>
                                                        </small>
                                                    @else
                                                        <span class="text-muted">Chưa đánh giá</span>
                                                    @endif
                                                    @if($record->higherLevelEvaluated)
                                                        <br>
                                                        <span class="text-danger">Đã khóa đánh giá</span>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            <input 
                                                type="number" 
                                                class="form-control text-left" 
                                                name="point"
                                                value="{{ $record->pointForCurrentUser ?? 0  }}"
                                                min="1"
                                                data-id="{{ $auth->id }}"
                                                data-user-seft-evaluation="{{ $record->user_id }}"
                                                data-evaluation="{{ $record->id }}"
                                                max="100"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ 6 + count($allPositionsData) }}" class="text-center text-danger">Không tìm thấy bản ghi phù hợp</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                {{ $records->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

@endsection