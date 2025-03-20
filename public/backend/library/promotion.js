(function($) {
	"use strict";
	var HT = {}; 
    var typingTimer;
    var doneTyingInterval = 300; // 1s

    $.fn.elExist = function () {
        return this.length > 0
    }

    HT.promotionNeverEnd = () => {
        $(document).on('change', '#neverEnd', function(){
            let _this = $(this)
            let isChecked = _this.prop('checked')
            if(isChecked){
                $('input[name=endDate]').val('').attr('disabled', true)
            }else{
                let endDate = $('input[name=startDate]').val()
                $('input[name=endDate]').val(endDate).attr('disabled', false)
            }
        })
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

    // HT.deleteCondition = () => {
    //     $(document).on('click', '.wrapperConditionItem .delete', function(){
    //         let _this = $(this)
    //         let unSelectedValue = _this.attr('data-condition-item')
    //         let selectedItem = $('.conditionItem').val()
    //         let indexOf = selectedItem.indexOf(unSelectedValue)
    //         if(indexOf !== -1){
    //             selectedItem.splice(selectedItem, indexOf)
    //         }

    //         console.log(selectedItem);

    //         // $('.conditionItem').val(unSelectedValue).trigger('change')
    //     })
    // }

   
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


    HT.btnJs100 = () => {
        $(document).on('click', '.btn-js-100', function(){
            let _button = $(this)
            let trLastChild = $('.order_amount_range').find('tbody tr:last-child')
            let newTo = parseInt(trLastChild.find('.order_amount_range_to input').val().replace(/\./g, ''))


            let $tr = $('<tr>')
            let tdList = [
                {
                    class: 'order_amount_range_from td-range',
                    name: 'promotion_order_amount_range[amountFrom][]', 
                    value: addCommas(parseInt(newTo) + 1), 
                },
                {
                    class: 'order_amount_range_to td-range',
                    name: 'promotion_order_amount_range[amountTo][]', 
                    value: 0, 
                },
            ]


            for(let i = 0; i < tdList.length; i++){
                let $td = $('<td>', { class: tdList[i].class })
                let $input = $('<input>')
                                .addClass('form-control int')
                                .attr('name', tdList[i].name)
                                .attr('value', tdList[i].value)

                $td.append($input)
                $tr.append($td)
            }

            let $discountTd = $('<td>').addClass('discountType')
            $discountTd.append(
                $('<div>', { class: 'uk-flex uk-flex-middle'}).append(
                    $('<input>', {
                        type: 'text',
                        name: 'promotion_order_amount_range[amountValue][]',
                        class: 'form-control int',
                        placeholder: 0,
                        value: 0
                    })
                ).append(
                    $('<select>', {
                        class: 'multipleSelect2'
                    })
                    .attr('name', 'promotion_order_amount_range[amountType][]')
                    .append( $('<option>', {value: 'cash', text: 'đ'}) )
                    .append( $('<option>', {value: 'percent', text: '%'}) )
                )
            )
            $tr.append($discountTd)
            let deleteButton = $('<td>').append(
                $('<div>', {
                    class: 'delete-some-item delete-order-amount-range-condition'
                }).append('<svg data-icon="TrashSolidLarge" aria-hidden="true" focusable="false" width="15" height="16" viewBox="0 0 15 16" class="bem-Svg" style="display: block;"><path fill="currentColor" d="M2 14a1 1 0 001 1h9a1 1 0 001-1V6H2v8zM13 2h-3a1 1 0 01-1-1H6a1 1 0 01-1 1H1v2h13V2h-1z"></path></svg>')
            )
            $tr.append(deleteButton)

            $('.order_amount_range table tbody').append($tr)
            HT.promotionMultipleSelect2()
        })
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
            console.log(option)
            switch (option) {
                case "order_amount_range":
                    HT.renderOrderAmountRange()
                    break;
                case "product_and_quantity":
                    HT.renderProductAndQuantity()
                    break;
                case "buy_product_take_gift":
                    HT.renderBuyProductTakeGift()
                    break;
                case "buy_combo":
                    HT.renderBuyCombo()
                    break;
                // case "product_quantity_range":
                //     console.log("product_quantity_range");
                //     break;
                // case "goods_discount_by_quantity":
                //     console.log("goods_discount_by_quantity");
                //     break;
                default:
                    HT.removePromotionContainer()
            }
        })

        let method = $('.preload_promotionMethod').val()
        if(method.length && typeof method !== 'undefined'){
            $('.promotionMethod').val(method).trigger('change')
        }
    }

    HT.renderBuyCombo = () => {
        let preloadData = JSON.parse($('.input_product_combo').val()) || ''
        let searchData = ''
        if(preloadData.price != null){
            searchData += HT.oldChooseData(preloadData);
        }
        let price = preloadData.price || '';
        let html = `
            <div class="buy_combo">
                <table class="table table-striped mt20 mb0">
                    <thead>
                        <tr>
                            <th style="width:400px;max-width:400px;">Sản phẩm mua</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="search-combo">
                                    <i class="fa fa-search"></i>
                                    <input type="text" name="keyword" class="form-control input-search-combo" placeholder="Chọn sản phẩm mua">
                                    <div class="ajax-search-combo"></div>
                                </div>
                                <div class="search-combo-result mt20 mb20">
                                    <div class="wrapper-cb">${searchData}</div>
                                </div>
                                <div class="count">
                                    <div class="uk-flex uk-flex-middle">
                                        <span class="summay-title bold mr5">Tổng tiền :</span>
                                        <input type="text" name="product_combos[price]" class="form-control int custom_ip" value="${price}" placeholder="Nhập số tiền">đ
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `
        HT.renderPromionalContainer(html)
    }

    HT.oldChooseData = (preloadData) => {
        let html = ''
        for(let i = 0 ; i < preloadData.id.length; i++){
            let id = preloadData.id[i]
            let image = preloadData.image[i]
            let name = preloadData.name[i]
            let quantity = preloadData.quantity[i]
            html += `
                    <div class="search-pd-item" id="model-pd-${id}" data-modelid="${id}">
                        <input type="hidden" name="product_combos[id][]" value="${id}">
                        <input type="hidden" name="product_combos[image][]" value="${image}">
                        <input type="hidden" name="product_combos[name][]" value="${name}">
                        <div class="col-lg-8">
                            <div class="uk-flex uk-flex-middle">
                                <span class="image img-cover">
                                    <img src="${image}" alt="">
                                </span>
                                <span class="pd_name">${name}</span>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="product_combos[quantity][]" class="form-control int custom_ip" value="${quantity}" placeholder="Nhập số lượng">
                        </div>
                        <div class="col-lg-1 deleted">
                            <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                                    <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                                    </path>
                                </svg>
                            </svg>
                        </div>
                    </div>
            `
        }
        return html;
    }

    HT.searchCombo = () => {
        $(document).on('keyup','.input-search-combo', function(e){
            e.preventDefault()
            let _this = $(this)
            let keyword = _this.val()
            let option = {
                keyword : keyword,
                model : 'Product'
            }
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function(){
                $.ajax({
                    url: 'ajax/product/loadProductPromotion', 
                    type: 'GET', 
                    data: option,
                    dataType: 'json', 
                    success: function(res) {
                        let html = HT.fillProductToCombo(res.objects)
                        if(html.length){
                            $('.ajax-search-combo').html(html).show()
                        }
                    },
                    beforeSend: function() {
                        
                    },
                });
               
            }, doneTyingInterval)
        })
    }

    HT.fillProductToCombo = (object) => {
        let html = ''
        if(object.data.length){
            for(let i = 0; i < object.data.length ; i++){
                let image = object.data[i].image
                let name = object.data[i].variant_name
                let product_variant_id = object.data[i].product_variant_id
                let product_id = object.data[i].id
                let uuid = object.data[i].uuid
                let price = addCommas(object.data[i].price)
                let check = uuid || product_id
                let flag = ($('#model-pd-'+check).length) ? 1 : 0;
                let setChecked = ($('#model-pd-'+check).length) ? HT.setCheckedPd() : ''
                html += 
                `
                    <button 
                            class="ajax-search-cb" 
                            data-flag="${flag}"
                            data-image="${image}" 
                            data-name="${name}" 
                            data-id="${product_id}"
                            data-variant_id="${product_variant_id}" 
                            data-uuid="${uuid}"
                            data-price="${object.data[i].price}"
                        >
                        <div class="uk-flex uk-flex-middle uk-flex-space-between">
                            <span>${name}</span>
                            <div class="uk-flex uk-flex-middle">
                                <span class="price mr20">${price}đ</span>
                                <div class="auto-icon">
                                    ${setChecked}
                                </div>
                            </div>
                        </div>
                    </button>
                `
            }
        }
        return html;
    }
 
    HT.addProductToCombo = () => {
        $(document).on('click','.ajax-search-combo .ajax-search-cb', function(e){
            e.preventDefault()
            let _this = $(this)
            let data = _this.data()
            let flag = _this.attr('data-flag')
            if(flag == 0){
                _this.find('.auto-icon').html(HT.setCheckedPd())
                _this.attr('data-flag', 1)
                let html = HT.productComboTemplate(data)
                $('.wrapper-cb').append(html)
            }else{
                $('#model-pd-'+data.id).remove()
                _this.find('.auto-icon').html('')
                _this.attr('data-flag', 0)
            }
        })
    }

    HT.productComboTemplate = (data) => {
        let id = data.id
        let uuid = data.uuid
        let image = data.image
        let name = data.name
        let price = addCommas(data.price)
        let html = `
            <div class="search-pd-item" id="model-pd-${id}" data-model="${id}">
                <input type="hidden" name="product_combos[id][]" value="${id}">
                <input type="hidden" name="product_combos[uuid][]" value="${uuid}">
                <input type="hidden" name="product_combos[image][]" value="${image}">
                <input type="hidden" name="product_combos[name][]" value="${name}">
                <div class="col-lg-8">
                    <div class="uk-flex uk-flex-middle">
                        <span class="image img-cover">
                            <img src="${image}" alt="">
                        </span>
                        <div class="it">
                            <span class="pd_name">${name}</span>
                            <span class="price">${price}đ</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <input type="text" name="product_combos[quantity][]" class="form-control int custom_ip" value="" placeholder="Nhập số lượng">
                </div>
                <div class="col-lg-1 deleted">
                        <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                                <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                                </path>
                            </svg>
                        </svg>
                </div>
            </div>`
        return html
    }

    HT.unfocusSearchCombo = () => {

        $(document).on('click', 'html', function(e){
            if(!$(e.target).hasClass('search-pd-result') && !$(e.target).hasClass('input-search-pd')){
                $('.ajax-search-pd').html('')
            }
        })
        
        $(document).on('click', '.ajax-search-pd', function(e){
            e.stopPropagation();
        })
    }

    HT.renderBuyProductTakeGift = () => {
        let selectData = JSON.parse($('.input-buy-product-take-gift').val())
        let selectHtml = ''
        let moduleType = $('.preload_select-buy-product-take-gift').val()
        for(let key in selectData){
            selectHtml += '<option '+ ((moduleType.length && typeof moduleType !== 'undefined' && moduleType == key) ? 'selected' : '') +'  value="'+key+'"> '+selectData[key]+' </option>'
        }
        let preloadDataProducts = JSON.parse($('.input_products').val()) || ''
        let preloadDataProductGifts = JSON.parse($('.input_product_gifts').val()) || ''
        let searchProduct = ''
        let searchProductGift = ''
        if (!preloadDataProducts && !preloadDataProductGifts) {
            searchProduct = '';
            searchProductGift = '';
        } else {
            if (preloadDataProducts) {
                searchProduct += HT.oldChooseProduct(preloadDataProducts);
            }
            if (preloadDataProductGifts) {
                searchProductGift += HT.oldChooseProductGift(preloadDataProductGifts);
            }
        }
        let html = `
            <div class="buy_product_take_gift">
                <div class="choose-module mt20">
                    <div class="fix-label mt20">Áp dụng</div>
                    <select name="module_type" id="" class="multipleSelect2 select-buy-product-take-gift">
                        ${selectHtml}
                    </select>
                </div>
                <table class="table table-striped mt20 mb0">
                    <thead>
                        <tr>
                            <th style="width:400px;max-width:400px;">Sản phẩm mua</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="search-product">
                                    <i class="fa fa-search"></i>
                                    <input type="text" name="keyword" class="form-control input-search-pd" placeholder="Chọn sản phẩm mua">
                                    <div class="ajax-search-pd"></div>
                                </div>
                                <div class="search-pd-result mt20">
                                    <div class="wrapper-search">
                                        ${searchProduct}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="table table-striped mb0">
                    <thead>
                        <tr>
                            <th style="width:400px;max-width:400px;">Quà tặng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="search-product-gift">
                                    <i class="fa fa-search"></i>
                                    <input type="text" name="keyword" class="form-control input-search-pd-gift" placeholder="Chọn quà tặng">
                                    <div class="ajax-search-pd-gift"></div>
                                </div>
                                <div class="search-pd-gift-result mt20">
                                    <div class="wrapper-search">${searchProductGift}</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `
        HT.renderPromionalContainer(html)
    }

    HT.oldChooseProduct = (preloadDataProducts) => {
        let html = ''
        for(let i = 0 ; i < preloadDataProducts.id.length; i++){
            let id = preloadDataProducts.id[i]
            let image = preloadDataProducts.image[i]
            let name = preloadDataProducts.name[i]
            let quantity = preloadDataProducts.quantity[i]
            html += `
                    <div class="search-pd-item" id="model-pd-${id}" data-modelid="${id}">
                        <input type="hidden" name="products[id][]" value="${id}">
                        <input type="hidden" name="products[image][]" value="${image}">
                        <input type="hidden" name="products[name][]" value="${name}">
                        <div class="col-lg-8">
                            <div class="uk-flex uk-flex-middle">
                                <span class="image img-cover">
                                    <img src="${image}" alt="">
                                </span>
                                <span class="pd_name">${name}</span>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="products[quantity][]" class="form-control int custom_ip" value="${quantity}" placeholder="Nhập số lượng">
                        </div>
                        <div class="col-lg-1 deleted">
                            <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                                    <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                                    </path>
                                </svg>
                            </svg>
                        </div>
                 </div>
            `
        }

        return html;

    }

    HT.oldChooseProductGift = (preloadDataProductGifts) => {
        let html = ''
        for(let i = 0 ; i < preloadDataProductGifts.id.length; i++){
            let id = preloadDataProductGifts.id[i]
            let image = preloadDataProductGifts.image[i]
            let name = preloadDataProductGifts.name[i]
            let quantity = preloadDataProductGifts.quantity[i]
            html += `
                    <div class="search-pd-item" id="model-pdg-${id}" data-modelid="${id}">
                        <input type="hidden" name="product_gifts[id][]" value="${id}">
                        <input type="hidden" name="product_gifts[image][]" value="${image}">
                        <input type="hidden" name="product_gifts[name][]" value="${name}">
                        <div class="col-lg-8">
                            <div class="uk-flex uk-flex-middle">
                                <span class="image img-cover">
                                    <img src="${image}" alt="">
                                </span>
                                <span class="pd_name">${name}</span>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="product_gifts[quantity][]" class="form-control int custom_ip" value="${quantity}" placeholder="Nhập số lượng">
                        </div>
                        <div class="col-lg-1 deleted">
                            <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                                    <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                                    </path>
                                </svg>
                            </svg>
                        </div>
                 </div>
            `
        }

        return html;

    }

/*Product*/

    HT.searchProduct = () => {
        $(document).on('keyup','.input-search-pd', function(e){
            e.preventDefault()
            let _this = $(this)
            let keyword = _this.val()
            let option = {
                keyword : keyword
            }
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function(){
                $.ajax({
                    url: 'ajax/dashboard/findProduct', 
                    type: 'GET', 
                    data: option,
                    dataType: 'json', 
                    success: function(res) {
                        let html = HT.renderSearchResultProduct(res)
                        if(html.length){
                            $('.ajax-search-pd').html(html).show()
                        }
                    },
                    beforeSend: function() {
                        $('.ajax-search-pd').html('').hide()
                    },
                });
               
            }, doneTyingInterval)
        })
    }

    HT.renderSearchResultProduct = (data) => {
        let html = ''
        if(data.length){
            for(let i = 0; i < data.length; i++){
                let flag = ($('#model-pd-'+data[i].id).length) ? 1 : 0;
                let setChecked = ($('#model-pd-'+data[i].id).length) ? HT.setCheckedPd() : ''
                html += 
                `
                    <button 
                            class="ajax-search-item" 
                            data-flag="${flag}" 
                            data-canonical="${data[i].languages[0].pivot.canonical}" 
                            data-image="${data[i].image}" 
                            data-name="${data[i].languages[0].pivot.name}" 
                            data-id="${data[i].id}"
                        >
                        <div class="uk-flex uk-flex-middle uk-flex-space-between">
                            <span>${data[i].languages[0].pivot.name}</span>
                            <div class="auto-icon">
                                ${setChecked}
                            </div>
                        </div>
                    </button>
                `
            }
        }
        return html
    }

    HT.setCheckedPd = () => {
        return '<svg class="svg-next-icon button-selected-combobox svg-next-icon-size-12" width="12" height="12"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26"><path d="m.3,14c-0.2-0.2-0.3-0.5-0.3-0.7s0.1-0.5 0.3-0.7l1.4-1.4c0.4-0.4 1-0.4 1.4,0l.1,.1 5.5,5.9c0.2,0.2 0.5,0.2 0.7,0l13.4-13.9h0.1v-8.88178e-16c0.4-0.4 1-0.4 1.4,0l1.4,1.4c0.4,0.4 0.4,1 0,1.4l0,0-16,16.6c-0.2,0.2-0.4,0.3-0.7,0.3-0.3,0-0.5-0.1-0.7-0.3l-7.8-8.4-.2-.3z"></path></svg></svg>'
    }

    HT.addProduct = () => {
        $(document).on('click', '.ajax-search-item', function(e){
            e.preventDefault()
            let _this = $(this)
            let data = _this.data()
            let flag = _this.attr('data-flag')
            if(flag == 0){
                _this.find('.auto-icon').html(HT.setCheckedPd())
                _this.attr('data-flag', 1)
                $('.search-pd-result .wrapper-search').append(HT.productTemplate(data))
            }else{
                $('#model-pd-'+data.id).remove()
                _this.find('.auto-icon').html('')
                _this.attr('data-flag', 0)
            }
        })
    }

    HT.productTemplate = (data) => {
        let html = `<div class="search-pd-item" id="model-pd-${data.id}" data-modelid="${data.id}">
            <input type="hidden" name="products[id][]" value="${data.id}">
            <input type="hidden" name="products[image][]" value="${data.image}">
            <input type="hidden" name="products[name][]" value="${data.name}">
            <div class="col-lg-8">
                <div class="uk-flex uk-flex-middle">
                    <span class="image img-cover">
                        <img src="${data.image}" alt="">
                    </span>
                    <span class="pd_name">${data.name}</span>
                </div>
            </div>
            <div class="col-lg-3">
                <input type="text" name="products[quantity][]" class="form-control int custom_ip" value="" placeholder="Nhập số lượng">
            </div>
            <div class="col-lg-1 deleted">
                    <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                            <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                            </path>
                        </svg>
                    </svg>
            </div>
        </div>`
        return html
    }

    HT.unfocusSearchBox = () => {
        $(document).on('click', 'html', function(e){
            if(!$(e.target).hasClass('search-combo-result') && !$(e.target).hasClass('input-search-combo')){
                $('.ajax-search-combo').html('')
            }
        })
        
        $(document).on('click', '.ajax-search-combo', function(e){
            e.stopPropagation();
        })
    }

    HT.removeProduct = () => {
        $(document).on('click', '.deleted', function(){
            let _this = $(this)
            _this.parents('.search-pd-item').remove()
        })
    }

/*End*/

/*Product_gift*/
    
    HT.searchProductGift = () => {
        $(document).on('keyup','.input-search-pd-gift', function(e){
            e.preventDefault()
            let _this = $(this)
            let keyword = _this.val()
            let option = {
                keyword : keyword
            }
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function(){
                $.ajax({
                    url: 'ajax/dashboard/findProduct', 
                    type: 'GET', 
                    data: option,
                    dataType: 'json', 
                    success: function(res) {
                        let html = HT.renderSearchResultProductGift(res)
                        if(html.length){
                            $('.ajax-search-pd-gift').html(html).show()
                        }
                    },
                    beforeSend: function() {
                        $('.ajax-search-pd-gift').html('').hide()
                    },
                });
            
            }, doneTyingInterval)
        })
    }
     
    HT.renderSearchResultProductGift = (data) => {
        let html = ''
        if(data.length){
            for(let i = 0; i < data.length; i++){
                let flag = ($('#model-pdg-'+data[i].id).length) ? 1 : 0;
                let setChecked = ($('#model-pdg-'+data[i].id).length) ? HT.setCheckedPdGift() : ''
                html += 
                `
                    <button 
                            class="ajax-pd-gift" 
                            data-flag="${flag}" 
                            data-canonical="${data[i].languages[0].pivot.canonical}" 
                            data-image="${data[i].image}" 
                            data-name="${data[i].languages[0].pivot.name}" 
                            data-id="${data[i].id}"
                        >
                        <div class="uk-flex uk-flex-middle uk-flex-space-between">
                            <span>${data[i].languages[0].pivot.name}</span>
                            <div class="auto-icon">
                                ${setChecked}
                            </div>
                        </div>
                    </button>
                `
            }
        }
        return html
    }

    HT.setCheckedPdGift = () => {
        return '<svg class="svg-next-icon button-selected-combobox svg-next-icon-size-12" width="12" height="12"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26"><path d="m.3,14c-0.2-0.2-0.3-0.5-0.3-0.7s0.1-0.5 0.3-0.7l1.4-1.4c0.4-0.4 1-0.4 1.4,0l.1,.1 5.5,5.9c0.2,0.2 0.5,0.2 0.7,0l13.4-13.9h0.1v-8.88178e-16c0.4-0.4 1-0.4 1.4,0l1.4,1.4c0.4,0.4 0.4,1 0,1.4l0,0-16,16.6c-0.2,0.2-0.4,0.3-0.7,0.3-0.3,0-0.5-0.1-0.7-0.3l-7.8-8.4-.2-.3z"></path></svg></svg>'
    }

    HT.addProductGift = () => {
        $(document).on('click', '.ajax-pd-gift', function(e){
            e.preventDefault()
            let _this = $(this)
            let data = _this.data()
            let flag = _this.attr('data-flag')
            if(flag == 0){
                _this.find('.auto-icon').html(HT.setCheckedPd())
                _this.attr('data-flag', 1)
                $('.search-pd-gift-result .wrapper-search').append(HT.productGiftTemplate(data))
            }else{
                $('#model-pdg-'+data.id).remove()
                _this.find('.auto-icon').html('')
                _this.attr('data-flag', 0)
            }
        })
    }
    
    HT.productGiftTemplate = (data) => {
        let html = `<div class="search-pd-item" id="model-pdg-${data.id}" data-modelid="${data.id}">
            <input type="hidden" name="product_gifts[id][]" value="${data.id}">
            <input type="hidden" name="product_gifts[name][]" value="${data.name}">
            <input type="hidden" name="product_gifts[image][]" value="${data.image}">
            <div class="col-lg-8">
                <div class="uk-flex uk-flex-middle">
                    <span class="image img-cover">
                        <img src="${data.image}" alt="">
                    </span>
                    <span class="pd_name">${data.name}</span>
                </div>
            </div>
            <div class="col-lg-3">
                <input type="text" name="product_gifts[quantity][]" class="form-control int custom_ip" value="" placeholder="Nhập số lượng">
            </div>
            <div class="col-lg-1 deleted-gift">
                <svg class="svg-next-icon svg-next-icon-size-12" width="12" height="12">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
                        <path d="M18.263 16l10.07-10.07c.625-.625.625-1.636 0-2.26s-1.638-.627-2.263 0L16 13.737 5.933 3.667c-.626-.624-1.637-.624-2.262 0s-.624 1.64 0 2.264L13.74 16 3.67 26.07c-.626.625-.626 1.636 0 2.26.312.313.722.47 1.13.47s.82-.157 1.132-.47l10.07-10.068 10.068 10.07c.312.31.722.468 1.13.468s.82-.157 1.132-.47c.626-.625.626-1.636 0-2.26L18.262 16z">
                        </path>
                    </svg>
                </svg>
            </div>
        </div>`
        return html
    }

    HT.unfocusSearchBoxGift = () => {

        $(document).on('click', 'html', function(e){
            if(!$(e.target).hasClass('search-pd-gift-result') && !$(e.target).hasClass('input-search-pd-gift')){
                $('.ajax-search-pd-gift').html('')
            }
        })
        
        $(document).on('click', '.ajax-search-pd-gift', function(e){
            e.stopPropagation();
        })
    }

    HT.removeProductGift = () => {
        $(document).on('click', '.deleted-gift', function(){
            let _this = $(this)
            _this.parents('.search-pd-item').remove()
        })
    }


/*End*/


    HT.removePromotionContainer = () => {
        $('.promotion-container').html('')
    }

    HT.renderOrderAmountRange = () => {
        let $tr = ''
        let order_amount_range = JSON.parse($('.input_order_amount_range').val()) || {
            amountFrom: ['0'],
            amountTo: ['0'],
            amountValue: ['0'],
            amountType: ['cash'],
        }

        for(let i = 0 ; i < order_amount_range.amountFrom.length; i++){
            let $amountFrom = order_amount_range.amountFrom[i]
            let $amountTo = order_amount_range.amountTo[i]
            let $amountType = order_amount_range.amountType[i]
            let $amountValue = order_amount_range.amountValue[i]

            $tr += `<tr>
                <td class="order_amount_range_from td-range">
                    <input 
                        type="text"
                        name="promotion_order_amount_range[amountFrom][]"
                        class="form-control int"
                        placeholder="0"
                        value="${$amountFrom}"
                    >
                </td>
                <td class="order_amount_range_to td-range">
                    <input 
                        type="text"
                        name="promotion_order_amount_range[amountTo][]"
                        class="form-control int"
                        placeholder="0"
                        value="${$amountTo}"
                    >
                </td>
                <td class="discountType">
                    <div class="uk-flex uk-flex-middle">
                        <input 
                            type="text"
                            name="promotion_order_amount_range[amountValue][]"
                            class="form-control int"
                            placeholder="0"
                            value="${$amountValue}"
                        >
                        <select name="promotion_order_amount_range[amountType][]" class="multipleSelect2" id="">
                            <option value="cash" ${ ($amountType == 'cash') ? 'selected' : '' }>đ</option>
                            <option value="percent" ${ ($amountType == 'percent') ? 'selected' : '' }>%</option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="delete-some-item delete-order-amount-range-condition"><svg data-icon="TrashSolidLarge" aria-hidden="true" focusable="false" width="15" height="16" viewBox="0 0 15 16" class="bem-Svg" style="display: block;"><path fill="currentColor" d="M2 14a1 1 0 001 1h9a1 1 0 001-1V6H2v8zM13 2h-3a1 1 0 01-1-1H6a1 1 0 01-1 1H1v2h13V2h-1z"></path></svg></div>
                </td>
            </tr>`
        }

        let html = `<div class="order_amount_range">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="text-right">Giá trị từ</th>
                        <th class="text-right">Giá trị đến</th>
                        <th class="text-right">Chiết khấu</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    ${$tr}
                </tbody>
            </table>
            <button class="btn btn-success btn-custom btn-js-100" value="" type="button">Thêm điều kiện</button>
        </div>`
        HT.renderPromionalContainer(html)
    }

    HT.renderProductAndQuantity = () => {
        let selectData = JSON.parse($('.input-product-and-quantity').val())
        let selectHtml = ''
        let moduleType = $('.preload_select-product-and-quantity').val()
        
        for(let key in selectData){
            selectHtml += '<option '+ ((moduleType.length && typeof moduleType !== 'undefined' && moduleType == key) ? 'selected' : '') +'  value="'+key+'"> '+selectData[key]+' </option>'
        }

        let preloadData = JSON.parse($('.input_product_and_quantity').val()) || {
            quantity: ['1'],
            maxDiscountValue: ['0'],
            discountValue: ['0'],
            discountType: ['cash'],
        }
        let html = `<div class="product_and_quantity">
            <div class="choose-module mt20">
                <div class="fix-label" style="color:blue;" for="">Sản phẩm áp dụng</div>
                <select name="module_type" id="" class="multipleSelect2 select-product-and-quantity">
                    ${selectHtml}
                </select>
            </div>
            <table class="table table-striped mt20">
                <thead>
                    <tr>
                        <th class="text-right" style="width:400px;max-width:400px;">Sản phẩm mua</th>
                        <th class="text-right" style="width:80px">Tối thiểu</th>
                        <th class="text-right">Giới hạn KM</th>
                        <th class="text-right">Chiết khấu</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="chooseProductPromotionTd">
                            <div 
                                class="product-quantity"
                                data-toggle="modal" 
                                data-target="#findProduct" 
                            >
                                <div class="boxWrapper">
                                    <div class="boxSearchIcon">
                                        <i class="fa fa-search"></i>
                                    </div>   
                                    <div class="boxSearchInput fixGrid6">
                                        <p>Tìm theo tên, mã sản phẩm</p>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <input 
                                type="text"
                                name="product_and_quantity[quantity]"
                                class="form-control int"
                                value="${preloadData.quantity}"
                            >
                        </td>
                        <td class="order_amount_range_to td-range">
                            <input 
                                type="text"
                                name="product_and_quantity[maxDiscountValue]"
                                class="form-control int"
                                placeholder="0"
                                value="${preloadData.maxDiscountValue}"
                            >
                        </td>
                        <td class="discountType">
                            <div class="uk-flex uk-flex-middle">
                                <input 
                                    type="text"
                                    name="product_and_quantity[discountValue]"
                                    class="form-control int"
                                    placeholder="0"
                                    value="${preloadData.discountValue}"
                                >
                                <select name="product_and_quantity[discountType]" class="multipleSelect2" id="">
                                <option value="cash" ${ (preloadData.discountType == 'cash') ? 'selected' : '' }>đ</option>
                                <option value="percent" ${ (preloadData.discountType == 'percent') ? 'selected' : '' }>%</option>
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
            url: 'ajax/product/loadProductPromotion', 
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
            e.preventDefault()
            let option = {
                model: $('.select-product-and-quantity').val(),
            }
            HT.loadProduct(option)
        })
    }

    HT.fillToObjectList = (data) => {
        switch (data.model) {
            case "Product":
                HT.fillProductToList(data.objects)
                break;
            case "ProductCatalogue":
                HT.fillProductCatalogueToList(data.objects)
                break;
        }
    }

    HT.fillProductCatalogueToList = (object) => {
        let html = ''
        if(object.data.length){
            let model = $('.select-product-and-quantity').val() 
            for(let i = 0; i < object.data.length; i++){
                let name = object.data[i].name
                let id = object.data[i].id
                let classBox = model + '_' + id
                let isChecked = ($('.boxWrapper .'+classBox+'').length ) ? true : false

                html += ` <div class="search-object-item" data-productid="${id}" data-name="${name}">
                <div class="uk-flex uk-flex-middle uk-flex-space-between">
                    <div class="object-info">
                        <div class="uk-flex uk-flex-middle">
                            <input 
                                type="checkbox"
                                name=""
                                value="${id}"
                                class="input-checkbox"
                                ${ (isChecked) ? 'checked' : '' }
                            >
                            <div class="object-name">
                                <div class="name" style="margin:0 0 0 5px">${name}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`
           }
        }
        html = html + HT.paginationLinks(object.links).prop('outerHTML')
        
        $('.search-list').html(html)
    }

    HT.fillProductToList = (object) => {
        let html = ''
        if(object.data.length){
            let model = $('.select-product-and-quantity').val() 
            for(let i = 0; i < object.data.length; i++){
                let image = object.data[i].image
                let name = object.data[i].variant_name
                let product_variant_id = object.data[i].product_variant_id
                let product_id = object.data[i].id
                let inventory = (typeof  object.data.inventory != 'undefined') ? inventory : 0
                let couldSell = (typeof  object.data.couldSell != 'undefined') ? couldSell : 0
                let sku = object.data[i].sku
                let price = object.data[i].price
                let classBox = model + '_' + product_id + '_' + product_variant_id
                let isChecked = ($('.boxWrapper .'+classBox+'').length ) ? true : false
                let uuid = object.data[i].uuid

                html += ` <div 
                    class="search-object-item" 
                    data-productid="${product_id}" 
                    data-variant_id="${product_variant_id}" 
                    data-name="${name}" 
                    data-uuid="${uuid}"
                >
                <div class="uk-flex uk-flex-middle uk-flex-space-between">
                    <div class="object-info">
                        <div class="uk-flex uk-flex-middle">
                            <input 
                                type="checkbox"
                                name=""
                                value="${product_id+'_'+product_variant_id}"
                                class="input-checkbox"
                                ${ (isChecked) ? 'checked' : '' }
                            >
                            <span class="image img-scaledown">
                                <img src="${image}" alt="">
                            </span>
                            <div class="object-name">
                                <div class="name">${name}</div>
                                <div class="jscode">Mã SP: ${sku}</div>
                            </div>
                        </div>
                    </div>
                    <div class="object-extra-info">
                        <div class="price">${addCommas(price)}</div>
                        <div class="object-inventory">
                            <div class="uk-flex uk-flex-middle">
                                <span class="text-1">Tồn kho:</span>
                                <span class="text-value">${inventory}</span>
                                <span class="text-1 slash">|</span>
                                <span class="text-value">Có thể bán: ${couldSell}</span>
                            </div>
                        </div>
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
                product_variant_id: _this.attr('data-variant_id'),
                name: _this.attr('data-name'),
                uuid: _this.attr('data-uuid')
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
            product_variant_id: [],
            name: [],
            variant_uuid: [],
        }

        let objectArray = []
        if(preloadObject.id && preloadObject.id.length > 0){
            objectArray = preloadObject.id.map((id, index) => ({
                product_id: id || 'null',
                product_variant_id: preloadObject.product_variant_id[index] || 'null',
                name: preloadObject.name[index] || 'null', 
                uuid: preloadObject.variant_uuid[index] || 'null',
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
                let { product_id, product_variant_id, name, uuid } = objectData[i]
                let classBox = `${model}_${product_id}_${product_variant_id}`
                if(!$(`.boxWrapper .${classBox}`).length){
                    html += `<div class="fixGrid6 ${classBox}">
                        <div class="goods-item">
                            <a class="goods-item-name" title="${name}">${name}</a>
                            <button class="delete-goods-item">
                                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" font-size="20"><path d="m12.667 4.273-.94-.94L8 7.06 4.273 3.333l-.94.94L7.06 8l-3.727 3.727.94.94L8 8.94l3.727 3.727.94-.94L8.94 8l3.727-3.727Z" fill="currentColor"></path></svg>
                            </button>
                            <div class="hidden">
                                <input name="object[id][]" value="${product_id}">
                                <input name="object[product_variant_id][]" value="${product_variant_id}">
                                <input name="object[variant_uuid][]" value="${uuid}">
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

    
   
	$(document).ready(function(){

        HT.searchCombo()
        HT.addProductToCombo()
        HT.unfocusSearchCombo()

        // HT.searchHandler()
        // HT.searchProduct()
        // HT.searchProductGift()

        HT.searchProductGift()
        HT.addProductGift()
        HT.removeProductGift()
        HT.unfocusSearchBoxGift()
        
        HT.searchProduct()
        HT.addProduct()
        HT.removeProduct()
        HT.unfocusSearchBox()

        HT.promotionNeverEnd()
        HT.promotionSource()
        HT.promotionMultipleSelect2()
        HT.chooseCustomerCondition()
        HT.chooseApplyItem()
        // HT.deleteCondition()
        HT.btnJs100()
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
