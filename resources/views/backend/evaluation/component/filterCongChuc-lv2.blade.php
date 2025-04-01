@props(['config'])
@php
    $level = $config['level'];
@endphp
<form action="{{ route('evaluations.teams', ['level' => $level]) }}">
    <div class="filter-wrapper filter-officer">
        <div class="uk-flex uk-flex-middle uk-flex-space-between">
            <div class="perpage">
                @php
                    $perpage = request('perpage') ?: old('perpage');
                    $user_id = request('user_id') ?: old('user_id');
                    $team_id = request('team_id') ?: old('team_id');
                    $captain_id = request('captain_id') ?: old('captain_id');
                    $vice_id = request('vice_id') ?: old('vice_id');
                    $oldValueDay = request()->old('created_at.eq') ?? request('created_at')['eq'] ?? null;
                    $selectedDay = $oldValueDay ? (int)substr($oldValueDay, 0, 2) : null;
                    $oldValueMonth = request()->old('created_at.between') ?? request('created_at')['between'] ?? null;
                    $selectedMonth = null;
                    if ($oldValueMonth) {
                        $selectedMonth = (int)substr($oldValueMonth, 3, 2);
                    }
                @endphp
                <div class="uk-flex uk-flex-middle uk-flex-space-between">
                    <select name="perpage" class="form-control input-sm perpage filter mr10">
                        @for($i = 20; $i<= 200; $i+=20)
                        <option {{ ($perpage == $i)  ? 'selected' : '' }}  value="{{ $i }}">{{ $i }} bản ghi</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="action">
                <div class="uk-flex uk-flex-middle">
                    <div class="uk-search uk-flex uk-flex-middle mr10">
                        <input type="text" value="{{ request('start_date.eq') ?: old('start_date.eq') }}"  placeholder="Chọn ngày giao việc" name="start_date[eq]" class="datepicker mr10 form-control">
                        <select name="team_id" class="form-control setupSelect2 team_id">
                            @php
                                $teamIds = [];
                            @endphp
                            <option value="0">Chọn Đội</option>
                            @foreach($config['usersOnBranch'] as $record)
                                @if(in_array($record->teams->id, $teamIds)) @continue;  @endif
                                @php
                                    $teamIds[] = $record->teams->id;
                                @endphp
                                <option 
                                    {{ ($team_id == $record->teams->id)  ? 'selected' : '' }}
                                    value="{{ $record->teams->id }}"
                                >
                                    {{ $record->teams->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="user_id" class="form-control setupSelect2 ">
                            <option value="0">Chọn công chức</option>
                            @foreach($config['usersOnBranch'] as $record)
                                <option 
                                    {{ ($user_id == $record->id)  ? 'selected' : '' }}
                                    value="{{ $record->id }}"
                                >
                                    {{ $record->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="input-group">
                            <input 
                                type="text" 
                                name="keyword" 
                                value="{{ request('keyword') ?: old('keyword') }}" 
                                placeholder="Nhập Từ khóa bạn muốn tìm kiếm..." class="form-control"
                            >
                           <span class="input-group-btn">
                               <button type="submit" name="search" value="search" class="btn btn-primary mb0 btn-sm">Tìm Kiếm
                                </button>
                           </span>
                        </div>
                    </div>
                    @if(!isset($config['isCreate']))
                        <div class="uk-flex uk-flex-middle">
                            <a 
                                href="{{ route("{$config['route']}.create") }}" 
                                class="btn btn-danger"
                            >
                                <i class="fa fa-plus mr5"></i>
                                {{ $config['route'] == 'evaluations' ? 'Tạo phiếu tự đánh giá' : 'Thêm bản ghi mới' }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>