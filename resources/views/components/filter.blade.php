@props(['config'])
<form action="{{ route("{$config['route']}.index") }}">
    <div class="filter-wrapper filter-officer">
        <div class="uk-flex uk-flex-middle uk-flex-space-between">
            <div class="perpage">
                @php
                    $perpage = request('perpage') ?: old('perpage');
                    $team_id = request('team_id') ?: old('team_id');
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
                        @if(isset($config['filter']))
                            <select name="user_id" class="form-control setupSelect2 ">
                                <option value="0">Chọn {{ isset($config['userCatalogue']) ? $config['userCatalogue']->name : '' }}</option>
                                @if(isset($config['usersOnBranch']))
                                    @foreach($config['usersOnBranch'] as $record)
                                        <option 
                                            @if(old('user_id') == $record->id) selected @endif
                                            value="{{ $record->id }}"
                                        >
                                            {{ $record->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        @endif
                        @if($config['route'] == 'evaluations')
                            <input type="text" value="{{ request('start_date.eq') ?: old('start_date.eq') }}"  placeholder="Chọn ngày giao việc" name="start_date[eq]" class="datepicker start_date mr10 form-control">
                        @endif
                        @if($config['route'] == 'users')
                            <select name="team_id" class="form-control setupSelect2 team_id">
                                <option value="0">Chọn đội</option>
                                @if(isset($teamsInNode))
                                    @foreach($teamsInNode as $team)
                                        <option {{ !is_null($team_id) && ($team_id['eq'] == $team->id)  ? 'selected' : '' }} value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        @endif
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