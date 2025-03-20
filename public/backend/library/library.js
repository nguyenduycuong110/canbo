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
        if($('.datepicker').length){
            $('.datepicker').datetimepicker({
                timepicker:true,
                format:'d/m/Y',
            });
        }
        
    }


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

    HT.triggerDate = () => {
        $(document).ready(function() {
            var today = new Date();
            var day = String(today.getDate()).padStart(2, '0');
            var month = String(today.getMonth() + 1).padStart(2, '0'); // Tháng bắt đầu từ 0
            var year = today.getFullYear();
            var currentDate = day + '/' + month + '/' + year;
            if ($('#date').val() === '') {
                $('#date').val(currentDate);
            }
        });
    };

    HT.changeStatusEvaluate = () => {
        $(document).ready(function(){
            $('select[name=status_id]').on('change', function(){
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
                            location.reload();
                        } else {
                            // Hiển thị thông báo lỗi
                            toastr.error("Có lỗi xảy ra, vui lòng thử lại");
                        }
                    },
                });
            });
        });
    }

    HT.triggerDepartment = () => {
        $(document).on('change','.statistic-form select[name="user_id"]', function(){
            let user_id = $(this).val();
            let date = $('input[name="date"]').val();
            if(!user_id){
                $('.statistic-form').find('.name').text('')
                $('.statistic-form').find('.cat_name').text('')
                return;
            }
            let option = {user_id : user_id, date : date}
            HT.loadEvaluation(option)
        })
    }

    HT.loadEvaluation = (option) => {
        $.ajax({
            url: 'ajax/evaluation/getDepartment', 
            type: 'GET', 
            data: option,
            dataType: 'json', 
            success: function(res) {
                $('.statistic-form').find('.name').text(res.flag.name)
                $('.statistic-form').find('.cat_name').text(res.flag.user_catalogues.name + ' - ' + res.flag.units.name)
                if(res.flag.evaluations){
                    HT.renderTd(res.flag.evaluations, res.flag.id)
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
               
            }
        });
    }

    HT.renderTd = (res, user_id) => {
        if(res.length == 0){
            return;
        }
        let html = ``;
        res.forEach((item, index) => {
            let leadershipApprovalName = (item.leadershipApproval && Object.keys(item.leadershipApproval).length > 0) ? 
            item.leadershipApproval.infoUser.name : '';
            let leadershipApprovalStatus = (item.leadershipApproval && Object.keys(item.leadershipApproval).length > 0) ? 
            item.leadershipApproval.infoStatus.name : '';
            let assessmentLeaderName = (item.assessmentLeader && Object.keys(item.assessmentLeader).length > 0) ? 
            item.assessmentLeader.infoUser.name : '';
            let assessmentLeaderStatus = (item.assessmentLeader && Object.keys(item.assessmentLeader).length > 0) ? 
            item.assessmentLeader.infoStatus.name : '';
            let statuesUser = null;
            item.statuses.forEach((val, key) => {
                if(val.pivot.user_id === user_id) {
                    statuesUser = val;
                }
            });
            html += `
                <tr>
                    <td>${index}</td>
                    <td>${item.tasks.name}</td>
                    <td>${item.start_date}</td>
                    <td>${item.due_date}</td>
                    <td>${item.completion_date}</td>
                    <td>${item.output}</td>
                    <td>${statuesUser.name}</td>
                    <td>${assessmentLeaderStatus}</td>
                    <td>${assessmentLeaderName}</td>
                    <td>${leadershipApprovalStatus}</td>
                    <td>${leadershipApprovalName}</td>
                </tr>
            `;
        });
        
        return $('.statistic-form').find('tbody').append(html);
    }

	$(document).ready(function(){
        HT.triggerDepartment()
        HT.triggerDate()
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