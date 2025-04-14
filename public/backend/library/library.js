(function($) {
	"use strict";
	var HT = {}; 
    var _token = $('meta[name="csrf-token"]').attr('content');

    HT.switchery = () => {
        $('.js-switch').each(function(){
            // let _this = $(this)
            var switchery = new Switchery(this, { color: '#1AB394', size: 'small'});
        })
    }

    HT.select2 = () => {
        if($('.setupSelect2').length){
            $('.setupSelect2').select2();
        }
        
    }

    HT.sortui = () => {
        $( "#sortable" ).sortable();
		$( "#sortable" ).disableSelection();
    }

    HT.changeStatus = () => {
        $(document).on('change', '.status', function(e){

            let _this = $(this)
            let option = {
                'value' : _this.val(),
                'modelId' : _this.attr('data-modelId'),
                'model' : _this.attr('data-model'),
                'field' : _this.attr('data-field'),
                '_token' : _token
            }

            $.ajax({
                url: 'ajax/dashboard/changeStatus', 
                type: 'POST', 
                data: option,
                dataType: 'json', 
                success: function(res) {
                    let inputValue = ((option.value == 1)?2:1)
                    if(res.flag == true){
                        _this.val(inputValue)
                    }
                  
                },
                error: function(jqXHR, textStatus, errorThrown) {
                  
                  console.log('Lỗi: ' + textStatus + ' ' + errorThrown);
                }
            });

            e.preventDefault()
        })
    }

    HT.changeStatusAll = () => {
        if($('.changeStatusAll').length){
            $(document).on('click', '.changeStatusAll', function(e){
                let _this = $(this)
                let id = []
                $('.checkBoxItem').each(function(){
                    let checkBox = $(this)
                    if(checkBox.prop('checked')){
                        id.push(checkBox.val())
                    }
                })

                let option = {
                    'value' : _this.attr('data-value'),
                    'model' : _this.attr('data-model'),
                    'field' : _this.attr('data-field'),
                    'id'    : id,
                    '_token' : _token
                }

                $.ajax({
                    url: 'ajax/dashboard/changeStatusAll', 
                    type: 'POST', 
                    data: option,
                    dataType: 'json', 
                    success: function(res) {
                        if(res.flag == true){
                            let cssActive1 = 'background-color: rgb(26, 179, 148); border-color: rgb(26, 179, 148); box-shadow: rgb(26, 179, 148) 0px 0px 0px 16px inset; transition: border 0.4s ease 0s, box-shadow 0.4s ease 0s, background-color 1.2s ease 0s;';
                            let cssActive2 = 'left: 13px; background-color: rgb(255, 255, 255); transition: background-color 0.4s ease 0s, left 0.2s ease 0s;';
                            let cssUnActive = 'background-color: rgb(255, 255, 255); border-color: rgb(223, 223, 223); box-shadow: rgb(223, 223, 223) 0px 0px 0px 0px inset; transition: border 0.4s ease 0s, box-shadow 0.4s ease 0s;'
                            let cssUnActive2 = 'left: 0px; transition: background-color 0.4s ease 0s, left 0.2s ease 0s;'

                            for(let i = 0; i < id.length; i++){
                                if(option.value == 2){
                                    $('.js-switch-'+id[i]).find('span.switchery').attr('style', cssActive1).find('small').attr('style', cssActive2)
                                }else if(option.value == 1){
                                    $('.js-switch-'+id[i]).find('span.switchery').attr('style', cssUnActive).find('small').attr('style', cssUnActive2)
                                }
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                      
                      console.log('Lỗi: ' + textStatus + ' ' + errorThrown);
                    }
                });

                e.preventDefault()
            })
        }
    }

    HT.checkAll = () => {
        if($('#checkAll').length){
            $(document).on('click', '#checkAll', function(){
                let isChecked = $(this).prop('checked')
                $('.checkBoxItem').prop('checked', isChecked);
                $('.checkBoxItem').each(function(){
                    let _this = $(this)
                    HT.changeBackground(_this)
                })
            })
        }
    }

    HT.checkBoxItem = () => {
        if($('.checkBoxItem').length){
            $(document).on('click', '.checkBoxItem', function(){
                let _this = $(this)
                HT.changeBackground(_this)
                HT.allChecked()
            })
        }
    }

    HT.changeBackground = (object) => {
        let isChecked = object.prop('checked')
        if(isChecked){
            object.closest('tr').addClass('active-bg')
        }else{
            object.closest('tr').removeClass('active-bg')
        }
    }

    HT.allChecked = () => {
        let allChecked = $('.checkBoxItem:checked').length === $('.checkBoxItem').length;
        $('#checkAll').prop('checked', allChecked);
    }

    HT.int = () => {
        $(document).on('change keyup blur', '.int', function(){
            let _this = $(this)
            let value = _this.val()
            if(value === ''){
                $(this).val('0')
            }
            value = value.replace(/\./gi, "")
            _this.val(HT.addCommas(value))
            if(isNaN(value)){
                _this.val('0')
            }
        })

        $(document).on('keydown', '.int', function(e){
            let _this = $(this)
            let data = _this.val()
            if(data == 0){
                let unicode = e.keyCode || e.which;
                if(unicode != 190){
                    _this.val('')
                }
            }
        })
    }

    HT.intCid = () => {
        $(document).on('change keyup blur', '.cid', function(){
            let _this = $(this)
            let value = _this.val()
            if(value === ''){
                $(this).val('0')
            }
            value = value.replace(/\./gi, "")
            // _this.val(HT.addCommas(value))
            if(isNaN(value)){
                _this.val('0')
            }
        })

        $(document).on('keydown', '.cid', function(e){
            let _this = $(this)
            let data = _this.val()
            if(data == 0){
                let unicode = e.keyCode || e.which;
                if(unicode != 190){
                    _this.val('')
                }
            }
        })
    }

    HT.addCommas = (nStr) => { 
        nStr = String(nStr);
        nStr = nStr.replace(/\./gi, "");
        let str ='';
        for (let i = nStr.length; i > 0; i -= 3){
            let a = ( (i-3) < 0 ) ? 0 : (i-3);
            str= nStr.slice(a,i) + '.' + str;
        }
        str= str.slice(0,str.length-1);
        return str;
    }

    HT.setupDatepicker = () => {
        // Khởi tạo tất cả .datepicker
        const $allDatepickers = $('.datepicker');
        if ($allDatepickers.length) {
            $allDatepickers.datetimepicker({
                timepicker: true,
                format: 'd/m/Y',
            });
        }
    
        const $colLg6Datepickers = $('.col-lg-6 .datepicker');
        if ($colLg6Datepickers.length) {
            const today = new Date();
            const currentDate = today.getDate().toString().padStart(2, '0') + '/' + 
                (today.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                today.getFullYear(); // Ví dụ: "09/04/2025"
    
            $colLg6Datepickers.each(function() {
                const $input = $(this);
                // Chỉ gán ngày hiện tại nếu input không có giá trị ban đầu
                if (!$input.val()) {
                    $input.val(currentDate);
                }
            });
        }
    };

    HT.setupMonthPicker = () => {
        if ($('.monthPicker').length) {
            // Lấy ngày hiện tại
            const today = new Date();
            const currentMonthYear = (today.getMonth() + 1).toString().padStart(2, '0') + '/' + today.getFullYear(); // Ví dụ: "03/2025"
    
            // Lấy ô input
            const $input = $('.monthPicker');
    
            // Kiểm tra giá trị từ old('date'), nếu không có thì dùng tháng hiện tại
            const oldValue = $input.val();
            const defaultValue = oldValue || currentMonthYear;
    
            // Gán giá trị mặc định cho ô input
            $input.val(defaultValue);
    
            // Khởi tạo Bootstrap Datepicker
            $input.datepicker({
                format: 'mm/yyyy', // Định dạng tháng/năm
                viewMode: 'months', // Hiển thị dạng lưới tháng
                minViewMode: 'months', // Chỉ cho phép chọn tháng (không chọn ngày)
                autoclose: true, // Tự động đóng khi chọn xong
                language: 'vi' // Ngôn ngữ tiếng Việt
            });
        }
    };


    HT.setupDateRangePicker = () => {
        if($('.rangepicker').length > 0){
            $('.rangepicker').daterangepicker({
                timePicker: true,
                locale: {
                    format: 'dd-mm-yy'
                }
            })
        }
    }

    // HT.triggerDate = () => {
    //     $(document).ready(function() {
    //         var today = new Date();
    //         var day = String(today.getDate()).padStart(2, '0');
    //         var month = String(today.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
    //         var year = today.getFullYear();
    //         var currentDate = day + '/' + month + '/' + year;
    //         if ($('#date').val() === '') {
    //             $('#date').val(currentDate);
    //         }
    //     });
    // };

    HT.changeStatusEvaluate = () => {
        $(document).ready(function(){
            $('.evaluations select[name=status_id]').on('change', function(){
                let _this = $(this)
                let recordId = _this.data('record-id')
                let statusId = _this.val()

                $.ajax({
                    url: '/evaluations/evaluate/' + recordId,
                    type: 'POST',
                    data: {
                        _token: _token,
                        _method: 'PUT',
                        status_id: statusId,
                    },
                    success: function(response) {
                        if (response.flag) {
                            // Sử dụng flasher để hiển thị thông báo thành công
                            toastr.success("Cập nhật đánh giá thành công");
                            
                            // Nếu muốn cập nhật giao diện
                            // location.reload();
                        } else {
                            // Hiển thị thông báo lỗi
                            toastr.error("Có lỗi xảy ra, vui lòng thử lại");
                        }
                    },
                });
            });
        });
    }

    HT.triggerEvaluationList = () => {
        if($('.evaluation-time').length){
            let date = $('.evaluation-time').val();
            let user_id = $('.user_id').val()
            let option = {user_id : user_id, date : date}
            HT.loadEvaluation(option)
        }
       
    }


    HT.loadEvaluation = (option) => {
        console.log(option)
        $.ajax({
            url: 'ajax/evaluation/getDepartment', 
            type: 'GET', 
            data: option,
            dataType: 'json', 
            success: function(res) {
                $('.statistic-form').find('.name').text(res.response?.name || '');

                let displayText = '';
                if (res.response?.user_catalogues?.name) {
                    displayText = res.response.user_catalogues.name;
                }
                if (res.response?.units?.name) {
                    displayText = displayText ? `${displayText} - ${res.response.units.name}` : res.response.units.name;
                }

                $('.statistic-form').find('.cat_name').text(displayText);
                HT.renderStatistic(res.response.statistics)
                if(res.response.evaluations && res.response.evaluations.length > 0){
                    HT.renderTd(res.response.evaluations, res.response.id, res)
                }else{
                    $('.statistic-form').find('tbody').html(`<tr><td colspan="11" class="text-danger text-center">Không có dữ liệu phù hợp</td></tr>`);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $('.statistic-form').find('tbody').html(`<tr><td colspan="11" class="text-danger text-center">Không có dữ liệu phù hợp</td></tr>`);
            }
        });
    }


    HT.formatDate = (isoDate) => {
        if (!isoDate) return 'N/A'; // Xử lý trường hợp không có ngày
        const date = new Date(isoDate);
        const day = String(date.getDate()).padStart(2, '0'); // Đảm bảo 2 chữ số
        const month = String(date.getMonth() + 1).padStart(2, '0'); // getMonth() bắt đầu từ 0
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    };

    HT.renderStatistic = (res) => {
        if (!res) {
            $('.statistic-form').find('input[name="working_days_in_month"]').val('')
            $('.statistic-form').find('input[name="leave_days_with_permission"]').val('')
            $('.statistic-form').find('input[name="leave_days_without_permission"]').val('')
            $('.statistic-form').find('input[name="violation_count"]').val('')
            $('.statistic-form').find('input[name="violation_behavior"]').val('')
            $('.statistic-form').find('input[name="disciplinary_action"]').val('')
            return;
        }
        Object.values(res).forEach(item => {
            $('.statistic-form').find('input[name="working_days_in_month"]').val(item.working_days_in_month)
            $('.statistic-form').find('input[name="leave_days_with_permission"]').val(item.leave_days_with_permission)
            $('.statistic-form').find('input[name="leave_days_without_permission"]').val(item.leave_days_without_permission)
            $('.statistic-form').find('input[name="violation_count"]').val(item.violation_count)
            $('.statistic-form').find('input[name="violation_behavior"]').val(item.violation_behavior)
            $('.statistic-form').find('input[name="disciplinary_action"]').val(item.disciplinary_action)
        })
    }

    HT.renderTd = (res, user_id, resOriginal = null) => {
        let countCompletionDate = 0;
        if (res.length == 0) {
            return;
        }
        let html = ``;

        res.forEach((item, index) => {
            countCompletionDate += item.completion_date
            // Lãnh đạo phê duyệt (cấp cao nhất)
            let leadershipApprovalName = (item.leadershipApproval && Object.keys(item.leadershipApproval).length > 0) 
                ? item.leadershipApproval.infoUser.name 
                : '';
            let leadershipApprovalStatus = (item.leadershipApproval && Object.keys(item.leadershipApproval).length > 0) 
                ? item.leadershipApproval.infoStatus.name 
                : '';

            let leadershipApprovalPoint = (item.leadershipApproval && Object.keys(item.leadershipApproval).length > 0) 
            ? item.leadershipApproval.point
            : '';

            // Đánh giá của Đội phó (mới nhất)
            let deputyAssessmentName = (item.deputyAssessment && Object.keys(item.deputyAssessment).length > 0) 
                ? item.deputyAssessment.infoUser.name 
                : '';
            let deputyAssessmentStatus = (item.deputyAssessment && Object.keys(item.deputyAssessment).length > 0) 
                ? item.deputyAssessment.infoStatus.name 
                : '';

            let deputyAssessmentPoint = (item.deputyAssessment && Object.keys(item.deputyAssessment).length > 0) 
            ? item.deputyAssessment.point
            : '';

            // Tự đánh giá của công chức
            let selfAssessmentName = item.selfAssessment?.infoUser?.name || '';
            let selfAssessmentStatus = item.selfAssessment?.infoStatus?.name || '';
    
            // Tìm trạng thái của người dùng hiện tại (nếu cần)
            let statuesUser = null;
            item.statuses.forEach((val, key) => {
                if (val.pivot.user_id === user_id) {
                    statuesUser = val;
                }
            });

            let file = (item.file == null) ? '' : 'Click để dowload'
    
            if (resOriginal.response.user_catalogues.level == 5) {
                html += `
                    <tr>
                        <td class="text-center col-stt">${index + 1}</td>
                        <td>${item.tasks.name}</td>
                        <td>${item.start_date}</td>
                        <td>${item.due_date}</td>
                        <td class="completion-time text-center"><span>${item.completion_date}</span></td>
                        <td class="output"><span>${item.output}</span></td>
                        <td>
                            ${selfAssessmentStatus || 'Chưa tự đánh giá'}
                            <br>
                            <span class="text-success">Họ Tên: ${selfAssessmentName}</span>
                        </td>
                        <td>
                            ${deputyAssessmentStatus || 'Chưa đánh giá'}
                            <br>
                            <span class="text-success">Họ Tên: ${deputyAssessmentName}<span class="text-danger">(${deputyAssessmentPoint}đ)</span></span>
                        </td>
                        <td>
                            ${leadershipApprovalStatus || 'Chưa phê duyệt'}
                            <br>
                            <span class="text-success">Họ Tên: ${leadershipApprovalName}<span class="text-danger">(${leadershipApprovalPoint}đ)</span></span>
                        </td>
                        <td>
                           <a href="${item.file}" target="_blank" dowload>${file}</a>
                        </td>
                    </tr>
                `;
            } else {
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td class="title"><span>${item.tasks.name}</span></td>
                        <td>${HT.formatDate(item.created_at)}</td>
                        <td>${item.total_tasks}</td>
                        <td>${item.overachieved_tasks}</td>
                        <td>${item.completed_tasks_ontime}</td>
                        <td>${item.failed_tasks_count}</td>
                        <td>
                            ${selfAssessmentStatus || 'Chưa tự đánh giá'}
                            <br>
                            <span class="text-success">Họ Tên: ${selfAssessmentName}</span>
                        </td>
                        <td>
                            ${deputyAssessmentStatus || 'Chưa đánh giá'}
                            <br>
                            <span class="text-success">Họ Tên: ${deputyAssessmentName}<span class="text-danger">(${deputyAssessmentPoint}đ)</span></span>
                        </td>
                        <td>
                            ${leadershipApprovalStatus || 'Chưa phê duyệt'}
                            <br>
                            <span class="text-success">Họ Tên: ${leadershipApprovalName}<span class="text-danger">(${leadershipApprovalPoint}đ)</span></span>
                        </td>
                    </tr>;
                `
            }
        });

        let countTr = `
            <tr>
                <td colspan="4" class="text-right">Tổng thời gian</td>
                <td class="text-success text-bold">${ countCompletionDate }</td>
            </tr>
            
        `

        return $('.statistic-form').find('tbody').html(html).append(countTr);
    }

    HT.MonthChangeStatisticEvaluation = () => {
        $(document).on('change', '.evaluation-time', function(){
            HT.triggerEvaluationList()
        })
    }

    HT.UserChangeStatisticEvaluation = () => {
        $(document).on('change', '.user_id', function(){
            HT.triggerEvaluationList()
        })
    }

    HT.exportExcel = () => {
        $(document).on('click', '.btn-export', function(e){
            e.preventDefault()
            let _this = $(this)
            let exportType = _this.val()
            const dateType = $('.date-type').val()
            let date = (dateType === 'month') ? $('.evaluation-time').val() : $('.evaluation-day').val() ;
            let user_id = (dateType === 'month') ?  $('.user_id').val() : $('.user_day_id').val() ;
            let option = {user_id : user_id, date : date}
            HT.setupDataForExport(exportType, option);
            
        })

    }

    HT.exportStatistic = () => {
        $(document).on('click', '.btn-export-total', function(e){
            e.preventDefault()
            let _this = $(this)
            let month = $('.evaluation-time').val()
            let option = {month : month}
            HT.setupDataForStatisticExport(option);
        })
    }

    HT.setupDataForStatisticExport = (option) => {
        const loadingOverlay = $('<div class="loading-overlay">Đang tải file...</div>');
        $('body').append(loadingOverlay);
        $.ajax({
            url: 'ajax/statistics/exportHistory', 
            type: 'POST', 
            data: {
                ...option,
                _token: $('meta[name="csrf-token"]').attr('content') // Thêm CSRF token
            },
            dataType: 'json', 
            success: function(res) {
                if (res.status === 'success') {
                    // Tạo một link ẩn để tải file
                    const link = document.createElement('a');
                    link.href = res.file_url;
                    link.download = res.filename; // Sử dụng tên file từ response
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    console.error('Error:', res.message);
                }
    
                // Ẩn trạng thái loading
                loadingOverlay.remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                loadingOverlay.remove();
            }
        })
    }

    HT.setupDataForExport = (type, option) => {
        const loadingOverlay = $('<div class="loading-overlay">Đang tải file...</div>');
        $('body').append(loadingOverlay);
        const working_days_in_month = $('input[name="working_days_in_month"]').val();
        const leave_days_with_permission = $('input[name="leave_days_with_permission"]').val();
        const leave_days_without_permission = $('input[name="leave_days_without_permission"]').val();
        const violation_count = $('input[name="violation_count"]').val();
        const violation_behavior = $('input[name="violation_behavior"]').val();
        const disciplinary_action = $('input[name="disciplinary_action"]').val();
        const dateType = $('input.date-type').val() ?? 'month'

        $.ajax({
            url: 'ajax/statistics/export', 
            type: 'POST', 
            data: {
                ...option,
                exportType: type,
                working_days_in_month: working_days_in_month,
                leave_days_with_permission: leave_days_with_permission,
                leave_days_without_permission: leave_days_without_permission,
                violation_count: violation_count,
                violation_behavior: violation_behavior,
                disciplinary_action: disciplinary_action,
                dateType: dateType,
                _token: $('meta[name="csrf-token"]').attr('content') // Thêm CSRF token
            },
            dataType: 'json', 
            success: function(res) {
                if (res.status === 'success') {
                    // Tạo một link ẩn để tải file
                    const link = document.createElement('a');
                    link.href = res.file_url;
                    link.download = res.filename; // Sử dụng tên file từ response
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    console.error('Error:', res.message);
                }
    
                // Ẩn trạng thái loading
                loadingOverlay.remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                loadingOverlay.remove();
            }
        });
    }

    HT.dayAndUserChange = () => {
        $(document).on('change', '.evaluation-day, .user_day_id', function(){
            let date = $('.evaluation-day').val();
            let user_id = $('.user_day_id').val()
            let option = {user_id : user_id, date : date}
            
            HT.loadEvaluationDay(option)
            
        })
    }

    HT.loadEvaluationDay = (option) => {
        if(option.user_id && option.date){
            $.ajax({
                url: 'ajax/evaluation/getDepartmentDay', 
                type: 'GET', 
                data: option,
                dataType: 'json', 
                success: function(res) {
                    console.log(res)
                    let displayText = '';
                    if (res.response?.user_catalogues?.name) {
                        displayText = res.response.user_catalogues.name;
                    }
                    if (res.response?.units?.name) {
                        displayText = displayText ? `${displayText} - ${res.response.units.name}` : res.response.units.name;
                    }

                    $('.statistic-form').find('.cat_name').text(displayText);
    
    
                    if(res.response.evaluations && res.response.evaluations.length > 0){
                        HT.renderTd(res.response.evaluations, res.response.id, res)
                    }else{
                        $('.statistic-form').find('tbody').html(`<tr><td colspan="11" class="text-danger text-center">Không có dữ liệu phù hợp</td></tr>`);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('.statistic-form').find('tbody').html(`<tr><td colspan="11" class="text-danger text-center">Không có dữ liệu phù hợp</td></tr>`);
                }
            });
        }else{
            $('.statistic-form').find('tbody').html(`<tr><td colspan="11" class="text-danger text-center">Không có dữ liệu phù hợp</td></tr>`);
        }
        
    }

    HT.manager = () => {
        $(document).ready(function(){
            $(document).on('change', 'select[name=user_catalogue_id]', function(){
                let _this = $(this)
                if(_this.val() == 31){
                    $('.manager-select').prop('disabled', false)
                }else{
                    $('.manager-select').val(null).trigger('change').prop('disabled', true);
                }
            })
        })
    }

    HT.setPointForEvaluation = () => {
        $(document).on('change','.setPoint', function(){
            let _this = $(this)
            let point = _this.val()
            if(point == 0){
                return;
            }
            let option = {
                currentUserId : _this.data('id'),
                evaluationId : _this.data('evaluation'),
                selfEvaluationId  : _this.data('user-seft-evaluation'),
                point : point,
                _token: $('meta[name="csrf-token"]').attr('content')
            }
            $.ajax({
                url: 'ajax/evaluation/setPoint', 
                type: 'POST', 
                data: option,
                dataType: 'json', 
                success: function(res) {
                    if(res.response.code == 404){
                        toastr.error('Vui lòng chọn đánh giá của bạn trước khi nhập điểm !');
                        return;
                    }
                    if(res.response.status == false){
                        let min = res.response.min
                        let max = res.response.max
                        toastr.error('Cập nhật điểm không thành công . Khoảng điểm phù hợp với đánh giá của bạn nằm trong khoảng từ '+min+' đến '+max+' !');
                        return;
                    }
                    toastr.success('Cập nhật điểm thành công !');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    
                }
            });
        })
    }

    HT.filterOfficerTeam = () => {
        $(document).on('change', '.team_id', function(){
            let _this = $(this)
            let team_id = _this.val()
            if(team_id == 0){
                return;
            }
            let option = {
                team_id : team_id,
            }
            HT.sendAjaxFilterOfficerTeam(option)
        })
    }

    HT.sendAjaxFilterOfficerTeam = (option) => {
        $.ajax({
            url: 'ajax/evaluation/filterOfficerTeam', 
            type: 'GET', 
            data: option,
            dataType: 'json', 
            success: function(res) {
                HT.appendSelectBoxUserStatitics(res.response)
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    HT.appendSelectBoxUserStatitics = (res) => {
        console.log(res.users)
        let userSelect = $('select[name="user_id"]');
        userSelect.empty();
        userSelect.append('<option value="0">Chọn cán bộ</option>');
        if(res.users && res.users.length > 0) {
            $.each(res.users, function(index, user) {
                userSelect.append(
                    $('<option></option>')
                    .val(user.id)
                    .text(user.name)
                );
            });
        }
        $('.setupSelect2').select2();
    }

    HT.filterViceTeam = () => {
        $(document).on('change', '.team_vice_id', function(){
            let _this = $(this)
            let team_id = _this.val()
            if(team_id == 0){
                return;
            }
            let option = {
                team_id : team_id,
            }
            HT.sendAjaxFilterViceTeam(option)
        })
    }

    HT.sendAjaxFilterViceTeam = (option) => {
        $.ajax({
            url: 'ajax/evaluation/filterViceTeam', 
            type: 'GET', 
            data: option,
            dataType: 'json', 
            success: function(res) {
                HT.appendSelectBoxUserStatitics(res.response)
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    // HT.filterCaptainDeputy = () => {
    //     $(document).on('change', '.deputy_id', function(){
    //         let _this = $(this)
    //         let deputy_id = _this.val()
    //         if(deputy_id == 0){
    //             return;
    //         }
    //         let option = {
    //             deputy_id : deputy_id,
    //         }
    //         $.ajax({
    //             url: 'ajax/evaluation/filterCaptainDeputy', 
    //             type: 'GET', 
    //             data: option,
    //             dataType: 'json', 
    //             success: function(res) {
    //                 HT.appendSelectBoxCaptain(res.response)
    //             },
    //             error: function(jqXHR, textStatus, errorThrown) {
                    
    //             }
    //         });
    //     })
    // }

    // HT.sendAjaxFilterCaptainDeputy = (option) => {
    //     $.ajax({
    //         url: 'ajax/evaluation/filterCaptainDeputy', 
    //         type: 'GET', 
    //         data: option,
    //         dataType: 'json', 
    //         success: function(res) {
    //             HT.appendSelectBoxCaptain(res.response)
    //         },
    //         error: function(jqXHR, textStatus, errorThrown) {
                
    //         }
    //     });
    // }

    HT.appendSelectBoxCaptain = (res) => {
        console.log(res.users)
        let userSelect = $('select[name="user_id"]');
        userSelect.empty();
        userSelect.append('<option value="0">Chọn lãnh đạo</option>');
        if(res.users && res.users.length > 0) {
            $.each(res.users, function(index, user) {
                userSelect.append(
                    $('<option></option>')
                    .val(user.id)
                    .text(user.name)
                );
            });
        }
        $('.setupSelect2').select2();
    }

    HT.exportRankQuality = () => {
        $(document).on('click', '.btn-export-rank', function(e){
            e.preventDefault()
            let _this = $(this)
            let month = $('.evaluation-time').val()
            let option = {month : month}
            HT.setupDataForRankQuality(option);
        })
    }

    HT.setupDataForRankQuality = (option) => {
        const loadingOverlay = $('<div class="loading-overlay">Đang tải file...</div>');
        $('body').append(loadingOverlay);
        $.ajax({
            url: 'ajax/statistics/exportRank', 
            type: 'POST', 
            data: {
                ...option,
                _token: $('meta[name="csrf-token"]').attr('content') // Thêm CSRF token
            },
            dataType: 'json', 
            success: function(res) {
                if (res.status === 'success') {
                    // Tạo một link ẩn để tải file
                    const link = document.createElement('a');
                    link.href = res.file_url;
                    link.download = res.filename; // Sử dụng tên file từ response
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    console.error('Error:', res.message);
                }
                loadingOverlay.remove();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                loadingOverlay.remove();
            }
        })
    }

    HT.filterOfficerByVice = () => {
        $(document).on('change', '.vice_id', function(){
            let _this = $(this)
            let vice_id = _this.val()
            if(vice_id == 0){
                return;
            }
            $.ajax({
                url: 'ajax/evaluation/getOfficer', 
                type: 'GET', 
                data: {
                    vice_id : vice_id
                },
                dataType: 'json', 
                success: function(res) {
                    if(res.response){
                        HT.appendSelectBoxOfficer(res.response)
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    
                }
            });
        });
    }


    HT.appendSelectBoxOfficer = (res) => {
        let userSelect = $('.filter-officer select[name="user_id"]');
        userSelect.empty();
        userSelect.append('<option value="0">Chọn công chức</option>');

        if(res.users && res.users.length > 0) {
            $.each(res.users, function(index, user) {
                userSelect.append(
                    $('<option></option>')
                    .val(user.id)
                    .text(user.name)
                );
            });
        }
        
        $('.setupSelect2').select2();
    };
    

    HT.loadUser = () => {
        if(typeof team_id !== 'undefined' && team_id != ''){
            let option = {
                team_id : team_id,
            }
            HT.sendAjaxFilterOfficerTeam(option)
        }
    }

    HT.loadVice = () => {
        if(typeof team_vice_id !== 'undefined' && team_vice_id != ''){
            let option = {
                team_id : team_vice_id,
            }
            HT.sendAjaxFilterViceTeam(option)
        }
    }

    // HT.loadCaptain = () => {
    //     if(typeof deputy_id !== 'undefined' && deputy_id != ''){
    //         let option = {
    //             deputy_id : deputy_id,
    //         }
    //         HT.sendAjaxFilterCaptainDeputy(option)
    //     }
    // }

    HT.filterEvaluationByField = () => {
        $('.start_date, .perpage, .team_id, .user_id, .deputy_id, .vice_id').change(function() {
            const $this = $(this);
            if(!$this.val()){
                return;
            }
            if ($this.closest('.filter-officer').length) {
                $this.closest('form').submit();
            }
        });
        
        $('.start_date').on('changeDate', function() {
            const $this = $(this);
            if(!$this.val()){
                return;
            }
            if ($this.closest('.filter-officer').length) {
                $this.closest('form').submit();
            }
        });
        
        $('.team_id, .user_id, .deputy_id').on('select2:select', function() {
            const $this = $(this);
            if(!$this.val()){
                return;
            }
            if ($this.closest('.filter-officer').length) {
                $this.closest('form').submit();
            }
        });
    };

	$(document).ready(function(){
       
        HT.loadUser()
        HT.filterEvaluationByField()
        HT.loadVice()
        // HT.loadCaptain()
        // HT.filterCaptainDeputy()
        HT.filterViceTeam()
        HT.filterOfficerByVice()
        HT.exportRankQuality()
        HT.filterOfficerTeam()
        HT.setPointForEvaluation()
        HT.changeStatusEvaluate()
        HT.switchery()
        HT.select2()
        HT.changeStatus()
        HT.checkAll()
        HT.checkBoxItem()
        HT.allChecked()
        HT.changeStatusAll()
        HT.sortui()
        HT.int()
        HT.intCid()
        HT.setupDatepicker()
        HT.setupDateRangePicker()
        // HT.setupMonthPicker()
        // HT.StatisticEvaluation()
        HT.setupMonthPicker()
        HT.triggerEvaluationList()
        HT.MonthChangeStatisticEvaluation()
        HT.UserChangeStatisticEvaluation()

        HT.exportExcel();
        HT.dayAndUserChange()

        HT.exportStatistic()
        HT.manager()
        
	});

})(jQuery);


addCommas = (nStr) => { 
    nStr = String(nStr);
    nStr = nStr.replace(/\./gi, "");
    let str ='';
    for (let i = nStr.length; i > 0; i -= 3){
        let a = ( (i-3) < 0 ) ? 0 : (i-3);
        str= nStr.slice(a,i) + '.' + str;
    }
    str= str.slice(0,str.length-1);
    return str;
}