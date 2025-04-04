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
                @php
                    $level = $auth->user_catalogues->level;
                @endphp
                @include('backend.evaluation.component.filterCongChuc-lv'.$level)
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        @php
                            $positionsByLevel = [];
                            foreach($allPositionsData as $posKey => $posData) {
                                $level = $posData['level'];
                                if (!isset($positionsByLevel[$level])) {
                                    $positionsByLevel[$level] = [];
                                }
                                $positionsByLevel[$level][] = $posData;
                            }
                            krsort($positionsByLevel);
                        @endphp
                        <thead>
                            <tr>
                                <th class="text-center col-stt">STT</th>
                                <th>Tiêu đề</th>
                                <th>Ngày giao</th>
                                <th>Ngày xong</th>
                                <th>Thời gian</th>
                                <th>SP đầu ra</th>
                                <th>Cá nhân tự đánh giá</th>
                                <th>Đánh giá của Đội phó</th>
                                @foreach($positionsByLevel as $level => $positions)
                                    @php
                                        $positionNames = array_column($positions, 'name');
                                        $headerText = 'Đánh giá của ' . implode(', ', $positionNames);
                                    @endphp
                                    <th>{{ $headerText }}</th>
                                @endforeach
                                {{-- @foreach($allPositionsData as $posKey => $posData)
                                    <th>Đánh giá của {{ $posData['name'] }}</th>
                                @endforeach --}}
                                <th>Điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($records) && (is_object($records) || is_array($records)) && count($records) > 0)
                                @foreach($records as $k => $record)
                                    @if(isset($allParams['perpage']) ?  true : $record->created_at >= $startOfMonth && $record->created_at <= $endOfMonth )
                                        <tr>
                                            <td class="text-center col-stt">
                                                {{ $record->id }}
                                            </td>
                                            <td class="text-center col-stt">
                                                {{ $record->tasks->name }}
                                            </td>
                                            <td class="col-time">
                                                {{ $record->start_date }}
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
                                                        <small class="text-success">Họ Tên: {{ $record->deputyEvaluation['user_name'] }} <span class="text-danger">({{  $record->deputyEvaluation['point'] }}đ)</span></small>
                                                    @else
                                                        <span class="text-muted">Chưa đánh giá</span>
                                                    @endif
                                                    @if($record->higherLevelEvaluated)
                                                        <br>
                                                        <span class="text-danger">Đã khóa đánh giá</span>
                                                    @endif
                                                @endif
                                            </td>
                                            @foreach($positionsByLevel as $level => $positionsGroup)
                                                <td class="p-w-100">
                                                    @php
                                                        $currentUserInLevel = false;
                                                        $evaluationFound = false;
                                                        $evaluationData = null;
                                                        $currentUserPosition = null;
                                                        
                                                        foreach($positionsGroup as $posKey => $posData) {
                                                            if($posData['is_current_user']) {
                                                                $currentUserInLevel = true;
                                                                $currentUserPosition = $posData;
                                                                break;
                                                            }
                                                        }
                                                        
                                                        // Find first evaluation for this level group
                                                        foreach($positionsGroup as $posKey => $posData) {
                                                            if(isset($record->positionEvaluations[$posData['name']])) {
                                                                $evaluationFound = true;
                                                                $evaluationData = $record->positionEvaluations[$posData['name']];
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    @if($currentUserInLevel && !$record->higherLevelEvaluated)
                                                        <select name="status_id" class="form-control setupSelect2 w-100" data-record-id="{{ $record->id }}">
                                                            <option value="0">[Chọn Đánh Giá]</option>
                                                            @foreach($statuses as $status)
                                                                <option value="{{ $status->id }}" {{ $status->id == $record->currentUserStatusId ? 'selected' : '' }}>
                                                                    {{ $status->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    @elseif($currentUserInLevel && $record->higherLevelEvaluated && $record->currentUserStatusId > 0)
                                                        {{ $statuses->where('id', $record->currentUserStatusId)->first()->name ?? 'N/A' }}
                                                        <br>
                                                        <span class="text-danger">Đã khóa đánh giá</span>
                                                    @elseif($currentUserInLevel && $record->higherLevelEvaluated)
                                                        <span class="text-muted">Chưa đánh giá</span>
                                                        <br>
                                                        <span class="text-danger">Đã khóa đánh giá</span>
                                                    @else
                                                        @if($evaluationFound)
                                                            {{ $statuses->where('id', $evaluationData['status_id'])->first()->name ?? 'N/A' }}
                                                            <br>
                                                            <small class="text-success">
                                                                Họ Tên: {{ $evaluationData['user_name'] }} <span class="text-danger">({{ $evaluationData['point'] }}đ)</span>
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
                                                    class="form-control text-left setPoint" 
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
                                    @endif
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