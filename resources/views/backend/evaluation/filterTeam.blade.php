@props(['config'])
@php
    $userCatalogueId = $config['userCatalogue']->id;
@endphp
<form action="{{ route($config['route'].'.teams.search', ['user_catalogue' => $userCatalogueId]) }}">
    <div class="filter-wrapper">
        <div class="uk-flex uk-flex-middle uk-flex-space-between">
            <div class="perpage">
                @php
                    $perpage = request('perpage') ?: old('perpage');
                    $user_id = request('user_id') ?: old('user_id');
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
                        <select name="created_at[eq]" class="form-control setupSelect2">
                            <option value="0">Chọn ngày</option>
                            @for($i = 1 ; $i <= 31 ; $i++)
                                @php
                                    $dateValue = str_pad($i, 2, '0', STR_PAD_LEFT) . '-' . date('m') . '-' . date('Y');
                                @endphp
                                <option
                                    value="{{ $dateValue }}"
                                    {{ $selectedDay == $i ? 'selected' : '' }}
                                >
                                    Ngày {{ $i }}
                                </option>
                            @endfor
                        </select>
                        <select name="created_at[between]" class="form-control setupSelect2">
                            <option value="0">Chọn tháng</option>
                            @for($i = 1 ; $i <= 12 ; $i++)
                                @php
                                    $monthValue = "01-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-" . date('Y') . ", 31-" . str_pad($i, 2, '0', STR_PAD_LEFT) . "-" . date('Y');
                                @endphp
                                <option
                                    value="{{ $monthValue }}"
                                    {{ $selectedMonth == $i ? 'selected' : '' }}
                                >
                                    Tháng {{ $i }}
                                </option>
                            @endfor
                        </select>
                        <select name="user_id" class="form-control setupSelect2 ">
                            <option value="0">Chọn {{ isset($config['userCatalogue']) ? $config['userCatalogue']->name : '' }}</option>
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