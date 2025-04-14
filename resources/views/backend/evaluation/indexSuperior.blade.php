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
                <x-filter :config="$config" />
                @php
                    // Lấy danh sách các vị trí từ dữ liệu
                    $positions = [];
                    $positionLevels = []; // Thêm mảng để lưu trữ level của từng vị trí
                    $currentUserPosition = null;

                    // Lấy chức vụ của người đăng nhập
                    $currentUser = Auth::user();
                    $currentUserCatalogue = $currentUser->user_catalogues()->first();
                    if ($currentUserCatalogue) {
                        $currentUserPosition = $currentUserCatalogue->name;
                    }

                    if(isset($records) && count($records) > 0) {
                        foreach($records as $record) {
                            $evaluationUsers = $record->statuses;
                        
                            foreach($evaluationUsers as $evaluation) {
                                $userId = $evaluation->pivot->user_id;
                            
                                // Bỏ qua nếu là người đang đánh giá bản thân
                                if($userId == $record->user_id) {
                                    continue;
                                }
                            
                                $user = \App\Models\User::find($userId);
                            
                                if($user) {
                                    $userCatalogues = $user->user_catalogues()->orderBy('level', 'desc')->get();
                                    foreach($userCatalogues as $catalogue) {
                                        // Thêm vào danh sách các vị trí nếu chưa có
                                        if(!in_array($catalogue->name, $positions)) {
                                            $positions[] = $catalogue->name;
                                            $positionLevels[$catalogue->name] = $catalogue->level; // Lưu level tương ứng
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Tạo mảng kết hợp vị trí và level để sắp xếp
                    $sortableArray = [];
                    foreach ($positions as $position) {
                        $sortableArray[$position] = $positionLevels[$position] ?? 999; // Sử dụng 999 nếu không có level
                    }
                    
                    // Sắp xếp mảng theo level (level thấp = cấp cao ở đầu)
                    arsort($sortableArray);
                    
                    // Lấy lại mảng positions đã sắp xếp (chỉ giữ keys)
                    $positions = array_keys($sortableArray);

                @endphp
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th  class="col-stt">STT</th>
                            <th>Nội dung công việc</th>
                            <th>Ngày giao việc</th>
                            <th>Tổng số công việc / nhiệm vụ được giao</th>
                            <th>Số công việc / nhiệm vụ hoàn thành <br> vượt mức về thời gian hoặc chất lượng</th>
                            <th>Số công việc / nhiệm vụ hoàn thành <br> đúng hạn , đảm bảo chất lượng</th>
                            <th>Số công việc / nhiệm vụ không hoàn thành <br> đúng hạn hoặc không đảm bảo yêu cầu</th>
                            <th class="note">Ghi chú</th>
                            <th>Cá nhân tự đánh giá</th>
                            @foreach($positions as $position)
                                <th>Đánh giá của {{ $position }}</th>
                            @endforeach
                            <th class="text-center">Thao tác</th>
                        </tr>
                        </thead>
                        <tbody>
                            @if(isset($records) && (is_object($records) || is_array($records)) && count($records) > 0)
                                @foreach($records as $key => $record)
                                    @php
                                        $lock = $record->statuses()->where('user_id', $record->user_id)->first()->pivot->lock;
                                        $status_id = $record->statuses()->where('user_id', $record->user_id)->first()->pivot->status_id;
                                        
                                        // Lấy đánh giá theo từng vị trí
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
                                                
                                                if($userCatalogue && in_array($userCatalogue->name, $positions)) {
                                                    $positionEvaluations[$userCatalogue->name] = [
                                                        'status_id' => $evaluation->pivot->status_id,
                                                        'user_name' => $user->name
                                                    ];
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td class="col-stt">
                                            {{ $key + 1 }}
                                        </td>
                                        <td>
                                            {{ $record->tasks->name }}
                                        </td>
                                        <td>
                                            {{ convertDateTime($record->start_date, 'd-m-Y', 'Y-m-d') }}
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
                                        <td class="note">
                                            <span>{{ $record->note }}</span>
                                        </td>
                                        <td>
                                            @if($lock == 0)
                                                <select name="status_id" class="form-control setupSelect2" data-record-id="{{ $record->id }}">
                                                    <option value="0">[Chọn Đánh Giá]</option>
                                                    @if(isset($statuses))
                                                        @foreach($statuses as $key => $val)
                                                            <option 
                                                                {{
                                                                    $val->id == old('status_id', $status_id ) ? 'selected' : '' 
                                                                }}
                                                                value="{{ $val->id }}">{{ $val->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            @else
                                                @foreach($statuses as $k => $v)
                                                    @if($v->id == $status_id)
                                                       {{ $v['name'] }}
                                                    @endif
                                                @endforeach
                                                {{-- {{ $statuses[$status_id]['name'] }} --}}
                                            @endif
                                        </td>
                                        <!-- Hiển thị đánh giá theo từng vị trí -->
                                        @foreach($positions as $position)
                                            <td>
                                                @if(isset($positionEvaluations[$position]))
                                                    {{ $statuses->where('id', $positionEvaluations[$position]['status_id'])->first()->name ?? 'N/A' }}
                                                    <br>
                                                    <small class="text-success">Họ Tên: {{ $positionEvaluations[$position]['user_name'] }}</small>
                                                @else
                                                    <span class="text-muted">Chưa đánh giá</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        <td class="text-center"> 
                                            @if($lock == 0)
                                                <a href="{{ route("{$config['route']}.edit", $record->id) }}" class="btn btn-success"><i class="fa fa-edit"></i></a>
                                                <a href="{{ route("{$config['route']}.delete", $record->id) }}" class="btn btn-danger"><i class="fa fa-trash"></i></a>
                                            @else
                                                Đã khóa
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="{{ 8 + count($positions) }}" class="text-center text-danger">Không tìm thấy bản ghi phù hợp</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                {{  $records->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection