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
                   $levelCurrentUser = $auth->user_catalogues->level;
                @endphp
                @if($config['level'] == 4)
                    @include('backend.evaluation.component.filterDoiPho-lv'.$levelCurrentUser)
                @elseif($config['level'] == 3)
                    @include('backend.evaluation.component.filterDoiTruong-lv'.$levelCurrentUser)
                @else
                @include('backend.evaluation.component.filterChiCucPho')
                @endif
                @php
                    // Khởi tạo mảng chứa tất cả các vị trí và thông tin
                    $allPositionsData = [];
                    
                    // Lấy thông tin của người đăng nhập
                    $currentUser = Auth::user();
                    $currentUserCatalogue = $currentUser->user_catalogues()->first();
                    $currentUserPosition = $currentUserCatalogue ? $currentUserCatalogue->name : 'Chưa xác định';
                    $currentUserLevel = $currentUserCatalogue ? $currentUserCatalogue->level : 999;
                    $selfPosition = null;
                    
                    // Thêm vị trí của người đăng nhập vào mảng với một key đặc biệt
                    $allPositionsData['__CURRENT_USER__'] = [
                        'name' => $currentUserPosition,
                        'level' => $currentUserLevel,
                        'is_current_user' => true
                    ];
                    
                    if(isset($records) && count($records) > 0) {
                        foreach($records as $record) {
                            // Lấy chức vụ của người được đánh giá
                            $recordUser = \App\Models\User::find($record->user_id);
                            if ($recordUser) {
                                $recordUserCatalogue = $recordUser->user_catalogues()->first();
                                if ($recordUserCatalogue) {
                                    $selfPosition = $recordUserCatalogue->name;
                                }
                            }
                            
                            $evaluationUsers = $record->statuses;
                            
                            foreach($evaluationUsers as $evaluation) {
                                $userId = $evaluation->pivot->user_id;
                                
                                // Bỏ qua nếu là người đang đăng nhập hoặc người được đánh giá
                                if($userId == Auth::id() || $userId == $record->user_id) {
                                    continue;
                                }
                                
                                $user = \App\Models\User::find($userId);
                                
                                if($user) {
                                    $userCatalogue = $user->user_catalogues()->first();
                                    
                                    if($userCatalogue && $userCatalogue->name != $selfPosition) {
                                        $positionName = $userCatalogue->name;
                                        $positionLevel = $userCatalogue->level;
                                        
                                        // Thêm vào mảng nếu chưa có
                                        if($positionLevel >= $currentUserLevel && !isset($allPositionsData[$positionName])) {
                                            $allPositionsData[$positionName] = [
                                                'name' => $positionName,
                                                'level' => $positionLevel,
                                                'is_current_user' => false
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // Sắp xếp mảng theo level (level càng lớn thì chức vụ càng thấp, nên dùng arsort)
                    uasort($allPositionsData, function($a, $b) {
                        return $b['level'] - $a['level']; // Sắp xếp từ cấp cao xuống cấp thấp (level thấp lên trên)
                    });
                @endphp
                <div class="table-responsive">
                    <table class="table table-striped table-bordered ">
                        <thead>
                        <tr>
                            <th class="text-center col-stt">STT</th>
                            <th>Nội dung công việc</th>
                            <th class="col-time">Ngày</th>
                            <th>Tổng số công việc / nhiệm vụ được giao</th>
                            <th>Số công việc / nhiệm vụ hoàn thành <br> vượt mức về thời gian hoặc chất lượng</th>
                            <th>Số công việc / nhiệm vụ hoàn thành <br> đúng hạn , đảm bảo chất lượng</th>
                            <th>Số công việc / nhiệm vụ không hoàn thành <br> đúng hạn hoặc không đảm bảo yêu cầu</th>
                            <th>Cá nhân tự đánh giá</th>
                            @foreach($allPositionsData as $posKey => $posData)
                                <th>
                                    Đánh giá của 
                                    @if($posData['is_current_user'])
                                        bạn ({{ $posData['name'] }})
                                    @else
                                        {{ $posData['name'] }}
                                    @endif
                                </th>
                            @endforeach
                            <th>Điểm</th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(isset($records) && (is_object($records) || is_array($records)) && count($records) > 0)
                                @foreach($records as $k => $record)
                                @php
                                    // Lấy đánh giá của bản thân
                                    $seftEvaluation = $record->statuses()->where('user_id', $record->user_id)->first()->pivot->status_id ?? null;
                                    
                                    // Lấy đánh giá hiện tại của người đăng nhập (nếu có)
                                    $currentUser = Auth::user();
                                    $currentUserEvaluation = $record->statuses()
                                        ->where('user_id', $currentUser->id)
                                        ->first();
                                    
                                    $currentUserStatusId = $currentUserEvaluation ? $currentUserEvaluation->pivot->status_id : 0;
                                    $lock = $currentUserEvaluation ? $currentUserEvaluation->pivot->lock : 0;
                                    
                                    // Lấy đánh giá theo chức vụ
                                    $positionEvaluations = [];
                                    $evaluations = $record->statuses;
                                    
                                    foreach($evaluations as $evaluation) {
                                        $userId = $evaluation->pivot->user_id;
                                        
                                        // Bỏ qua nếu là người được đánh giá
                                        if($userId == $record->user_id) {
                                            continue;
                                        }
                                        
                                        $user = \App\Models\User::find($userId);
                                        
                                        if($user) {
                                            $userCatalogue = $user->user_catalogues()->first();
                                            
                                            if($userCatalogue) {
                                                $positionEvaluations[$userCatalogue->name] = [
                                                    'status_id' => $evaluation->pivot->status_id,
                                                    'user_name' => $user->name
                                                ];
                                            }
                                        }
                                    }
                                    // Kiểm tra xem có cấp cao hơn đã đánh giá chưa
                                    $higherLevelEvaluated = false;
                                    $currentUserLevel = $currentUserCatalogue ? $currentUserCatalogue->level : null;

                                    if ($currentUserLevel !== null) {
                                        // Kiểm tra từng đánh giá trong statuses của bản ghi hiện tại
                                        foreach($record->statuses as $status) {
                                            $evaluatorId = $status->pivot->user_id;
                                            
                                            // Chỉ kiểm tra đánh giá của người KHÁC
                                            if ($evaluatorId == Auth::id() || $evaluatorId == $record->user_id) {
                                                continue;
                                            }
                                            
                                            // Lấy thông tin người đánh giá
                                            $evaluator = \App\Models\User::find($evaluatorId);
                                            if ($evaluator) {
                                                $evaluatorCatalogue = $evaluator->user_catalogues()->first();
                                                $evaluatorLevel = $evaluatorCatalogue ? $evaluatorCatalogue->level : null;
                                                
                                                // Chỉ xem là cấp cao hơn nếu level thực sự nhỏ hơn
                                                if ($evaluatorLevel !== null && $evaluatorLevel < $currentUserLevel) {
                                                    $higherLevelEvaluated = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }

                                    // Kiểm tra xem người dùng hiện tại có phải là cấp cao nhất không
                                    $isHighestLevel = true;
                                    if ($currentUserLevel !== null) {
                                        foreach($record->statuses as $s) {
                                            $evaluatorId = $s->pivot->user_id;
                                            if($evaluatorId != Auth::id() && $evaluatorId != $record->user_id) {
                                                $evaluator = \App\Models\User::find($evaluatorId);
                                                if($evaluator) {
                                                    $evaluatorCatalogue = $evaluator->user_catalogues()->first();
                                                    $evaluatorLevel = $evaluatorCatalogue ? $evaluatorCatalogue->level : null;
                                                    if($evaluatorLevel !== null && $evaluatorLevel < $currentUserLevel) {
                                                        $isHighestLevel = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center col-stt">
                                        {{ $record->id }}
                                    </td>
                                    <td>
                                        {{ $record->tasks->name }}
                                    </td>
                                    <td class="col-time">
                                        {{ convertDateTime( $record->created_at, 'Y-m-d') }}
                                    </td>
                                    <td class="text-center">
                                        {{ $record->total_tasks }}
                                    </td>
                                    <td class="text-center">
                                        {{ $record->overachieved_tasks }}
                                    </td>
                                    <td class="text-center">
                                        {{ $record->completed_tasks_ontime }}
                                    </td>
                                    <td class="text-center" style="width:200px;">
                                        {{ $record->failed_tasks_count }}
                                    </td>
                                    <td>
                                        @if($seftEvaluation)
                                            {{ $statuses->where('id', $seftEvaluation)->first()->name ?? 'N/A' }}
                                        @else
                                            Chưa đánh giá
                                        @endif
                                    </td>
                                    
                                    @foreach($allPositionsData as $posKey => $posData)
                                        <td>
                                            @if($posData['is_current_user'])
                                                {{-- Hiển thị đánh giá của người dùng hiện tại --}}
                                                @if($higherLevelEvaluated && $currentUserStatusId == 0)
                                                    <span class="text-danger">Đã khóa đánh giá</span>
                                                @elseif($higherLevelEvaluated && $currentUserStatusId > 0)
                                                    {{ $statuses->where('id', $currentUserStatusId)->first()->name ?? 'N/A' }}
                                                @elseif($isHighestLevel || (!$higherLevelEvaluated && $record->canEvaluate))
                                                    <select name="status_id" class="form-control setupSelect2" data-record-id="{{ $record->id }}">
                                                        <option value="0">[Chọn Đánh Giá]</option>
                                                        @foreach($statuses as $status)
                                                            <option value="{{ $status->id }}" {{ $status->id == $currentUserStatusId ? 'selected' : '' }}>
                                                                {{ $status->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="text-warning">Không có quyền đánh giá</span>
                                                @endif
                                            @else
                                                {{-- Hiển thị đánh giá của các vị trí khác --}}
                                                @if(isset($positionEvaluations[$posData['name']]))
                                                    {{ $statuses->where('id', $positionEvaluations[$posData['name']]['status_id'])->first()->name ?? 'N/A' }}
                                                    <br>
                                                    <small class="text-success">Họ Tên: {{ $positionEvaluations[$posData['name']]['user_name'] }}</small>
                                                @else
                                                    <span class="text-muted">Chưa đánh giá</span>
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
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ 8 + count($allPositionsData) }}" class="text-center text-danger">Không tìm thấy bản ghi phù hợp</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                {{ (!is_null($records)) ? $records->links('pagination::bootstrap-4') : '' }}
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('select[name=status_id]').on('change', function(){
            let _this = $(this)
            let recordId = _this.data('record-id')
            let statusId = _this.val()
            
            $.ajax({
                url: '/evaluations/evaluate/' + recordId,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'PUT',
                    status_id: statusId,
                },pói
                success: function(response) {
                    if (response.flag) {
                        // Sử dụng flasher để hiển thị thông báo thành công
                        toastr.success("Cập nhật đánh giá thành công");
                        
                        // Nếu muốn cập nhật giao diện
                        location.reload();
                    } else {
                        // Hiển thị thông báo lỗi
                        toastr.error("Có lỗi xảy ra, vui lòng thử lại");
                    }
                },
            });
        });
    });
</script>

@endsection