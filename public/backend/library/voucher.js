(function($) {
	"use strict";
	var HT = {}; 
    var typingTimer;
    var doneTyingInterval = 500; // 1s

    $.fn.elExist = function () {
        return this.length > 0
    }


    HT.promotionSource = () => {
        $(document).on('click', '.chooseSource', function(){
            let _this = $(this)
            let flag = (_this.attr('id') == 'allSource') ? true : false;
            if(flag){
                _this.parents('.ibox-content').find('.source-wrapper').remove()
            }else{
                $.ajax({
                    url: 'ajax/source/getAllSource', 
                    type: 'GET', 
                    // data: option,
                    dataType: 'json', 
                    success: function(res) {
                        let sourceData = res.data
                        if(!$('.source-wrapper').length){
                            let sourceHtml = HT.renderPromotionSource(sourceData).prop('outerHTML')
                            _this.parents('.ibox-content').append(sourceHtml)
                            HT.promotionMultipleSelect2()
                        } 
                    },
                });


                
            }
        })
    }

    HT.renderPromotionSource = (sourceData) => {
        
        let wrapper = $('<div>').addClass('source-wrapper')
        // if(sourceData.length){
            let select = $('<select>')
                        .addClass('multipleSelect2')
                        .attr('name', 'sourceValue[]')
                        .attr('multiple', true)
            
            for(let i = 0; i < sourceData.length; i++ ){
                let option = $('<option>').attr('value', sourceData[i].id).text(sourceData[i].name)
                select.append(option)
            }
            wrapper.append(select)
        // }
        return wrapper
    }

    HT.chooseCustomerCondition = () => {
        $(document).on('change', '.chooseApply', function(){
            let _this = $(this)
            let id = _this.attr('id')
            if(id === 'allApply'){
                _this.parents('.ibox-content').find('.apply-wrapper').remove()
            }else{
                let applyHtml = HT.renderApplyCondition().prop('outerHTML')
                _this.parents('.ibox-content').append(applyHtml)
                HT.promotionMultipleSelect2()
            }
        })
    }
    
    HT.renderApplyCondition = () => {

        let applyConditionData = JSON.parse($('.applyStatusList').val())
        let wrapper = $('<div>').addClass('apply-wrapper')
        let wrapperContionItem = $('<div>').addClass('wrapper-condition')
        if(applyConditionData.length){
            let select = $('<select>')
                        .addClass('multipleSelect2 conditionItem')
                        .attr('name', 'applyValue[]')
                        .attr('multiple', true)
            
            for(let i = 0; i < applyConditionData.length; i++ ){
                let option = $('<option>').attr('value', applyConditionData[i].id).text(applyConditionData[i].name)
                select.append(option)
            }
            wrapper.append(select)
            wrapper.append(wrapperContionItem)
        }
        return wrapper 
    }

    HT.chooseApplyItem = () => {
        $(document).on('change', '.conditionItem', function(){
            let _this  = $(this)
            let condition = {
                value: _this.val(),
                label: _this.select2('data')
            }


            $('.wrapperConditionItem').each(function(){
                let _item = $(this)
                let itemClass = _item.attr('class').split(' ')[2]
                if(condition.value.includes(itemClass) == false){
                    _item.remove()
                }
            })

            for(let i = 0; i < condition.value.length; i++){
                let value = condition.value[i]
                HT.createConditionItem(value, condition.label[i].text)
            }
        })
    }

    HT.checkConditionItemSet = () => {
        let checkedValue = $('.conditionItemSelected').val()
        if(checkedValue.length && $('.conditionItem').length){
            checkedValue = JSON.parse(checkedValue)
            $('.conditionItem').val(checkedValue).trigger('change')
        }
    }

    HT.createConditionItem = (value, label) => {
        if(!$('.wrapper-condition').find('.' + value).elExist()){
            $.ajax({
                url: 'ajax/dashboard/getPromotionConditionValue', 
                type: 'GET', 
                data: {
                    value: value
                },
                dataType: 'json', 
                success: function(res) {
                    let optionData = res.data
                    let conditionItem = $('<div>').addClass('wrapperConditionItem mt10 '+ value)
                    let conditionHiddenInput = $('.condition_input_' + value)
                    let conditionHiddenInputValue = []
                    if(conditionHiddenInput.length){
                        conditionHiddenInputValue = JSON.parse(conditionHiddenInput.val())
                    }
                    
                    let select = $('<select>')
                                    .addClass('multipleSelect2 objectItem')
                                    .attr('name', value + "[]")
                                    .attr('multiple', true)
                    for(let i = 0; i < optionData.length; i++){
                        let option = $('<option>').attr('value', optionData[i].id)
                                                    .text(optionData[i].text)
                        select.append(option)
                    }
                    select.val(conditionHiddenInputValue).trigger('change')
                    

                    const conditionLabel = HT.createConditionLabel(label, value)
                    conditionItem.append(conditionLabel)
                    conditionItem.append(select)
            
                   
                    $('.wrapper-condition').append(conditionItem)
                    HT.promotionMultipleSelect2()
                },
            });
        } 
    }

    HT.createConditionLabel = (label, value) => {

        // let deleteButton = $('<div>').addClass('delete').html('<svg data-icon="TrashSolidLarge" aria-hidden="true" focusable="false" width="15" height="16" viewBox="0 0 15 16" class="bem-Svg" style="display: block;"><path fill="currentColor" d="M2 14a1 1 0 001 1h9a1 1 0 001-1V6H2v8zM13 2h-3a1 1 0 01-1-1H6a1 1 0 01-1 1H1v2h13V2h-1z"></path></svg>').attr('data-condition-item', value)

        let conditionLabel = $('<div>').addClass('conditionLabel').text(label)

        let flex = $('<div>').addClass('uk-flex uk-flex-middle uk-flex-space-between')

        let wrapperBox = $('<div>').addClass('mb5')
        flex.append(conditionLabel)
        wrapperBox.append(flex)
        return wrapperBox.prop('outerHTML')
        
    }
   
    HT.promotionMultipleSelect2 = () => {
        $('.multipleSelect2').select2({
            // minimumInputLength: 2,
            placeholder: 'Click vào ô để lựa chọn...',
            closeOnSelect: true
            // ajax: {
            //     url: 'ajax/attribute/getAttribute',
            //     type: 'GET',
            //     dataType: 'json',
            //     deley: 250,
            //     data: function (params){
            //         return {
            //             search: params.term,
            //             option: option,
            //         }
            //     },
            //     processResults: function(data){
            //         return {
            //             results: data.items
            //         }
            //     },
            //     cache: true
              
            //   }
        });
    }

    HT.deleteAmountRangeCondition = () => {
        $(document).on('click','.delete-order-amount-range-condition', function(){
            let _this = $(this)
            _this.parents('tr').remove()
        })
    }

    HT.renderOrderRangeConditionContainer = () => {

        $(document).on('change', '.promotionMethod', function(){
            let _this = $(this)
            let option = _this.val()
            switch (option) {
                case "TOTAL_ORDERS":
                    HT.renderVoucherTotalOrder()
                    break;
                case "SHIPPING_ORDERS":
                    HT.renderVoucherTotalShip()
                    break;
                case "PRODUCT_VOUCHER":
                    HT.renderProductAndQuantity()
                    break;
                default:
                    HT.removePromotionContainer()
            }
        })

        let method = $('.preload_promotionMethod').val()
        if(method.length && typeof method !== 'undefined'){
            $('.promotionMethod').val(method).trigger('change')
        }
    }


    HT.removePromotionContainer = () => {
        $('.promotion-container').html('')
    }

    HT.renderVoucherTotalOrder = () => {
        let $tr = ''
        let voucher_total_order = JSON.parse($('.input_voucher_total_order').val()) || {
            min_order_value: ['0'],
            max_order_value: ['0'],
            max_discount_amount: ['0'],
            discount_value : ['0'],
            discount_type: ['FIXED'],
        }

        let $min_order_value = voucher_total_order.min_order_value
        let $max_order_value = voucher_total_order.max_order_value
        let $discount_type = voucher_total_order.discount_type
        let $max_discount_amount = voucher_total_order.max_discount_amount
        let $discount_value = voucher_total_order.discount_value

        $tr += `<tr>
            <td class="order_amount_range_from td-range">
                <input 
                    type="text"
                    name="TOTAL_ORDERS[min_order_value]"
                    class="form-control int"
                    placeholder="0"
                    value="${$min_order_value}"
                >
            </td>
            <td class="order_amount_range_to td-range">
                <input 
                    type="text"
                    name="TOTAL_ORDERS[max_order_value]"
                    class="form-control int"
                    placeholder="0"
                    value="${$max_order_value}"
                >
            </td>
            <td class="order_amount_range_to td-range">
                <input 
                    type="text"
                    name="TOTAL_ORDERS[max_discount_amount]"
                    class="form-control int"
                    placeholder="0"
                    value="${$max_discount_amount}"
                >
            </td>
            <td class="discountType">
                <div class="uk-flex uk-flex-middle">
                    <input 
                        type="text"
                        name="TOTAL_ORDERS[discount_value]"
                        class="form-control int"
                        placeholder="0"
                        value="${$discount_value}"
                    >
                    <select name="TOTAL_ORDERS[discount_type]" class="multipleSelect2" id="">
                        <option value="FIXED" ${ ($discount_type == 'FIXED') ? 'selected' : '' }>đ</option>
                        <option value="PERCENTAGE" ${ ($discount_type == 'PERCENTAGE') ? 'selected' : '' }>%</option>
                    </select>
                </div>
            </td>
            <td>
                <div class="delete-some-item delete-order-amount-range-condition"><svg data-icon="TrashSolidLarge" aria-hidden="true" focusable="false" width="15" height="16" viewBox="0 0 15 16" class="bem-Svg" style="display: block;"><path fill="currentColor" d="M2 14a1 1 0 001 1h9a1 1 0 001-1V6H2v8zM13 2h-3a1 1 0 01-1-1H6a1 1 0 01-1 1H1v2h13V2h-1z"></path></svg></div>
            </td>
        </tr>`
        

        let html = `<div class="order_amount_range">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-right">Giá trị từ</th>
                        <th class="text-right">Giá trị đến</th>
                        <th class="text-right">Giới hạn</th>
                        <th class="text-right">Chiết khấu</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    ${$tr}
                </tbody>
            </table>
        </div>`
        HT.renderPromionalContainer(html)
    }

    HT.renderVoucherTotalShip = () => {
        let $tr = ''

        let ship_voucher = JSON.parse($('.input_voucher_ship').val()) || {
            min_shipping_value: ['0'],
            max_shipping_value: ['0'],
            max_discount_amount: ['0'],
            discount_value : ['0'],
            discount_type: ['FIXED'],
        }

        let $min_shipping_value = ship_voucher.min_shipping_value
        let $max_shipping_value = ship_voucher.max_shipping_value
        let $discount_type = ship_voucher.discount_type
        let $max_discount_amount = ship_voucher.max_discount_amount
        let $discount_value = ship_voucher.discount_value

        $tr += `<tr>
            <td class="order_amount_range_from td-range">
                <input 
                    type="text"
                    name="SHIPPING_ORDERS[min_shipping_value]"
                    class="form-control int"
                    placeholder="0"
                    value="${$min_shipping_value}"
                >
            </td>
            <td class="order_amount_range_to td-range">
                <input 
                    type="text"
                    name="SHIPPING_ORDERS[max_shipping_value]"
                    class="form-control int"
                    placeholder="0"
                    value="${$max_shipping_value}"
                >
            </td>
            <td class="order_amount_range_to td-range">
                <input 
                    type="text"
                    name="SHIPPING_ORDERS[max_discount_amount]"
                    class="form-control int"
                    placeholder="0"
                    value="${$max_discount_amount}"
                >
            </td>
            <td class="discountType">
                <div class="uk-flex uk-flex-middle">
                    <input 
                        type="text"
                        name="SHIPPING_ORDERS[discount_value]"
                        class="form-control int"
                        placeholder="0"
                        value="${$discount_value}"
                    >
                    <select name="SHIPPING_ORDERS[discount_type]" class="multipleSelect2" id="">
                        <option value="FIXED" ${ ($discount_type == 'FIXED') ? 'selected' : '' }>đ</option>
                        <option value="PERCENTAGE" ${ ($discount_type == 'PERCENTAGE') ? 'selected' : '' }>%</option>
                    </select>
                </div>
            </td>
            <td>
                <div class="delete-some-item delete-order-amount-range-condition"><svg data-icon="TrashSolidLarge" aria-hidden="true" focusable="false" width="15" height="16" viewBox="0 0 15 16" class="bem-Svg" style="display: block;"><path fill="currentColor" d="M2 14a1 1 0 001 1h9a1 1 0 001-1V6H2v8zM13 2h-3a1 1 0 01-1-1H6a1 1 0 01-1 1H1v2h13V2h-1z"></path></svg></div>
            </td>
        </tr>`
        

        let html = `<div class="order_amount_range">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-right">Giá trị từ</th>
                        <th class="text-right">Giá trị đến</th>
                        <th class="text-right">Giới hạn</th>
                        <th class="text-right">Chiết khấu</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    ${$tr}
                </tbody>
            </table>
        </div>`
        HT.renderPromionalContainer(html)
    }

    HT.renderProductAndQuantity = () => {

        let selectData = JSON.parse($('.input-product-and-quantity').val())

        let selectHtml = ''

        let moduleType = $('.preload_select-product-and-quantity').val()

        let className = `${moduleType == 'ALL_PRODUCTS' ? 'readonly' : 'product-quantity'}`;

        for(let key in selectData){
            selectHtml += '<option '+ ((moduleType.length && typeof moduleType !== 'undefined' && moduleType == key) ? 'selected' : '') +'  value="'+key+'" data-model="Product"> '+selectData[key]+' </option>'
        }

        let preloadData = JSON.parse($('.input_product_and_quantity').val()) || {
            max_discount_amount : ['0'],
            discount_value : ['0'],
            discount_type : ['FIXED'],
        }
        let html = `<div class="product_and_quantity">
            <div class="choose-module mt20 ">
                <div class="fix-label" style="color:blue;" for="">Sản phẩm áp dụng</div>
                <select name="product_scope" id="" class="multipleSelect2 select-product-and-quantity">
                    ${selectHtml}
                </select>
            </div>
            <table class="table table-striped mt20 voucher">
                <thead>
                    <tr>
                        <th class="text-right" style="width:400px;max-width:400px;">Sản phẩm mua</th>
                        <th class="text-right">Giới hạn</th>
                        <th class="text-right">Chiết khấu</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="chooseProductPromotionTd">
                            <div 
                                class="${className}"
                                data-toggle="modal" 
                                data-target="#findProduct" 
                            >
                                <div class="boxWrapper">
                                    <div class="boxSearchIcon">
                                        <i class="fa fa-search"></i>
                                    </div>   
                                    <div class="boxSearchInput ">
                                        <p>Tìm theo tên, mã sản phẩm</p>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="order_amount_range_to td-range">
                            <input 
                                type="text"
                                name="PRODUCT_VOUCHER[max_discount_amount]"
                                class="form-control int"
                                placeholder="0"
                                value="${preloadData.max_discount_amount}"
                            >
                        </td>
                        <td class="discountType">
                            <div class="uk-flex uk-flex-middle">
                                <input 
                                    type="text"
                                    name="PRODUCT_VOUCHER[discount_value]"
                                    class="form-control int"
                                    placeholder="0"
                                    value="${preloadData.discount_value}"
                                >
                                <select name="PRODUCT_VOUCHER[discount_type]" class="multipleSelect2" id="">
                                <option value="FIXED" ${ (preloadData.discount_type == 'FIXED') ? 'selected' : '' }>đ</option>
                                <option value="PERCENTAGE" ${ (preloadData.discount_type == 'PERCENTAGE') ? 'selected' : '' }>%</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>`


        HT.renderPromionalContainer(html)
    }

    HT.renderPromionalContainer = (html) => {
        $('.promotion-container').html(html)
        HT.promotionMultipleSelect2()
    }

    HT.loadProduct = (option) => {
        $.ajax({
            url: 'ajax/product/loadProductVoucher', 
            type: 'GET', 
            data: option,
            dataType: 'json', 
            success: function(res) {
                HT.fillToObjectList(res)
            },
        });
    }

    HT.getPaginationMenu = () => {
        $(document).on('click', '.page-link', function(e){
            e.preventDefault()
            let _this = $(this)
            let option = {
                model: $('.select-product-and-quantity').val(),
                page: _this.text(),
                keyword : $('.search-model').val()
            }
            HT.loadProduct(option)
        })
    }

    HT.productQuantityListProduct = () => {
        $(document).on('click', '.product-quantity', function(e){
            let option = {
                model: $('.select-product-and-quantity option:selected').data('model'),
            }
            HT.loadProduct(option)
        })
    }

    HT.fillToObjectList = (data) => {
        
        switch (data.model) {
            case "Product":
                HT.fillProductToList(data.objects)
                break;
        }
    }


    HT.fillProductToList = (object) => {
        let html = ''
        if(object.data.length){
            let model = $('.select-product-and-quantity').val() 
            for(let i = 0; i < object.data.length; i++){
                let image = object.data[i].image
                let name = object.data[i].name
                let product_id = object.data[i].id
                let code = object.data[i].code
                let price = object.data[i].price
                let classBox = model + '_' + product_id 
                let isChecked = ($('.boxWrapper .'+classBox+'').length ) ? true : false

                html += ` <div 
                    class="search-object-item" 
                    data-productid="${product_id}" 
                    data-name="${name}" 
                >
                <div class="uk-flex uk-flex-middle uk-flex-space-between">
                    <div class="object-info">
                        <div class="uk-flex uk-flex-middle">
                            <input 
                                type="checkbox"
                                name=""
                                value="${product_id}"
                                class="input-checkbox"
                                ${ (isChecked) ? 'checked' : '' }
                            >
                            <span class="image img-scaledown">
                                <img src="${image}" alt="">
                            </span>
                            <div class="object-name">
                                <div class="name">${name}</div>
                                <div class="jscode">Mã SP: ${code}</div>
                            </div>
                        </div>
                    </div>
                    <div class="object-extra-info">
                        <div class="price vc">${addCommas(price)}</div>
                    </div>
                </div>
            </div>`
           }
        }
        html = html + HT.paginationLinks(object.links).prop('outerHTML')
        
        $('.search-list').html(html)
    }

    HT.changePromotionMethod = () => {
        $(document).on('change', '.select-product-and-quantity', function(){
            $('.fixGrid6').remove()
            objectChoose = []
        })
    }

    HT.paginationLinks = (links) => {
        let nav = $('<nav>')
        if(links.length > 3){
            let paginationUl = $('<ul>').addClass('pagination')
            $.each(links, function(index, link){
                let liClass = 'page-item'
                if(link.active){
                    liClass += ' active disabled'
                }else if(!link.url){
                    liClass += ' disabled'
                }
    
                let li = $('<li>').addClass(liClass)
                if(link.label == 'pagination.previous'){
                    let span = $('<span>').addClass('page-link').attr('aria-hidden', true).html('‹')
                    li.append(span)
                }else if(link.label == 'pagination.next'){
                    let span = $('<a>').addClass('page-link').attr('aria-hidden', true).html('›')
                    li.append(span)
                }else if(link.url){
                    let a = $('<a>').addClass('page-link').text(link.label).attr('href', link.url).attr('data-page', link.label)
                    li.append(a)
                }
                paginationUl.append(li)
            })  
            nav.append(paginationUl)
        }
        return nav
    }

    HT.searchObject = () => {
        $(document).on('keyup', '.search-model', function(e){
            let _this = $(this)
            let keyword = _this.val()
            let option = {
                model: $('.select-product-and-quantity').val(),
                keyword : keyword
            }
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function(){
                HT.loadProduct(option)
            }, doneTyingInterval)
        })
    }

    var objectChoose = []

    HT.chooseProductPromotion = () => {
        $(document).on('click', '.search-object-item', function(e){
            e.preventDefault()
            let _this = $(this)
            let isChecked = _this.find('input[type=checkbox]').prop('checked')
            let objectItem = {
                product_id: _this.attr('data-productid'),
                name: _this.attr('data-name'),
            }
            if(isChecked){
                objectChoose = objectChoose.filter(item => item.product_id !== objectItem.product_id)
                _this.find('input[type=checkbox]').prop('checked', false)
            }else{
                objectChoose.push(objectItem)
                _this.find('input[type=checkbox]').prop('checked', true)
            }

        })
    }

    HT.confirmProductPromotion = () => {

        let preloadObject =  JSON.parse($('.input_object').val()) || {
            id: [],
            name: [],
        }

        let objectArray = []
        if(preloadObject.id && preloadObject.id.length > 0){
            objectArray = preloadObject.id.map((id, index) => ({
                product_id: id || 'null',
                name: preloadObject.name[index] || 'null', 
            }))
        }
        
        if(objectArray.length && typeof objectArray !== 'undefined'){
            let preloadHtml = HT.renderBoxWrapper(objectArray)
            HT.checkFixGrid(preloadHtml)
        }
        $(document).on('click', '.confirm-product-promotion', function(e){
            
            let html = HT.renderBoxWrapper(objectChoose)
            HT.checkFixGrid(html)
            $('#findProduct').modal('hide')
        })
    }

    HT.renderBoxWrapper = (objectData) => {

        let html = ''
        let model = $('.select-product-and-quantity').val()
        if(objectData.length){
            for(let i = 0; i < objectData.length; i++){
                let { product_id, name} = objectData[i]
                let classBox = `${model}_${product_id}`
                if(!$(`.boxWrapper .${classBox}`).length){
                    html += `<div class="fixGrid6 ${classBox}">
                        <div class="goods-item">
                            <a class="goods-item-name" title="${name}">${name}</a>
                            <button class="delete-goods-item">
                                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" font-size="20"><path d="m12.667 4.273-.94-.94L8 7.06 4.273 3.333l-.94.94L7.06 8l-3.727 3.727.94.94L8 8.94l3.727 3.727.94-.94L8.94 8l3.727-3.727Z" fill="currentColor"></path></svg>
                            </button>
                            <div class="hidden">
                                <input name="object[id][]" value="${product_id}">
                                <input name="object[name][]" value="${name}">
                            </div>
                    </div>
                    </div>`
                }
            }
        }
        return html
    }

    HT.checkFixGrid = (html) => {
        if($('.fixGrid6').elExist){
           $('.boxSearchIcon').remove()
           $('.boxWrapper').prepend(html)
        }else{
            $('.fixGrid6').remove()
            $('.boxWrapper').prepend(HT.boxSearchIcon())
        }
    }

    HT.boxSearchIcon = () => {
        return `<div class="boxSearchIcon">
            <i class="fa fa-search"></i>
        </div>`
    }

    HT.deleteGoodsItem = () => {
        $(document).on('click', '.delete-goods-item', function(e){
            e.stopPropagation()
            let _button = $(this)
            _button.parents('.fixGrid6').remove()
        })
    }

    HT.disableSelect = () =>{
        $(document).on('change','.select-product-and-quantity', function(){
            let _this = $(this)
            if(_this.val() == 'ALL_PRODUCTS'){
                $('.voucher .product-quantity').removeAttr('data-toggle data-target').removeClass('product-quantity').addClass('readonly');
            }else{
                $('.voucher .readonly').attr('data-toggle', 'modal').attr('data-target', '#findProduct').removeClass('readonly').addClass('product-quantity');
            }
        })
    }

    HT.removeModal = () => {
        $(document).ready(function () {
            $('.readonly').removeAttr('data-toggle data-target');
        });
    };
    
    HT.checked = () => {
        $(document).ready(function () {
            $('input[name="combine_promotion"]').on('change', function () {
                $(this).val($(this).is(':checked') ? 1 : 0);
            });
        });
    }
   
	$(document).ready(function(){
        HT.checked()
        HT.removeModal()
        HT.disableSelect()
        HT.promotionSource()
        HT.promotionMultipleSelect2()
        HT.chooseCustomerCondition()
        HT.chooseApplyItem()
        HT.deleteAmountRangeCondition()
        HT.renderOrderRangeConditionContainer()
        HT.productQuantityListProduct()
        HT.getPaginationMenu()
        HT.searchObject()
        HT.chooseProductPromotion()
        HT.confirmProductPromotion()
        HT.deleteGoodsItem()
        HT.changePromotionMethod()
        HT.checkConditionItemSet()
	});


})(jQuery);
