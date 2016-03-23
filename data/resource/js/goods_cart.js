/**
 * 删除购物车
 * @param cart_id
 */
function drop_cart_item(cart_id){
    var parent_tr = $('#cart_item_' + cart_id).parent();
    var amount_span = $('#cart_totals');
    showDialog('确认删除吗?', 'confirm', '', function(){
        $.getJSON('index.php?act=cart&op=del&cart_id=' + cart_id, function(result){
            if(result.state){
                //删除成功
                if(result.quantity == 0){//判断购物车是否为空
                    window.location.reload();    //刷新
                } else {
                	$('tr[nc_group="'+cart_id+'"]').remove();//移除本商品或本套装
            		if (parent_tr.children('tr').length == 2) {//只剩下店铺名头和店铺合计尾，则全部移除
            		    parent_tr.remove();
            		}
            		calc_cart_price();
                }
            }else{
            	alert(result.msg);
            }
        });    	
    });
}

/**
 * 更改购物车数量
 * @param cart_id
 * @param input
 */
function change_quantity(cart_id, input){
    var subtotal = $('#item' + cart_id + '_subtotal');
    //暂存为局部变量，否则如果用户输入过快有可能造成前后值不一致的问题
    var _value = input.value;
    $.getJSON('index.php?act=cart&op=update&cart_id=' + cart_id + '&quantity=' + _value, function(result){
    	$(input).attr('changed', _value);
    	if(result.state == 'true'){
            $('#item' + cart_id + '_price').html(number_format(result.goods_price,2));
            subtotal.html(number_format(result.subtotal,2));
            $('#cart_id'+cart_id).val(cart_id+'|'+_value);
        }

        if(result.state == 'invalid'){
          subtotal.html(0.00);
          $('#cart_id'+cart_id).remove();
          $('tr[nc_group="'+cart_id+'"]').addClass('item_disabled');
          $(input).parent().next().html('');
          $(input).parent().removeClass('ws0').html('已下架');
          showDialog(result.msg, 'error','','','','','','','','',2);
          return;
        }

        if(result.state == 'shortage'){
          $('#item' + cart_id + '_price').html(number_format(result.goods_price,2));
          $('#cart_id'+cart_id).val(cart_id+'|'+result.goods_num);
          $(input).val(result.goods_num);
          showDialog(result.msg, 'error','','','','','','','','',2);
          return;
        }

        if(result.state == '') {
            //更新失败
        	showDialog(result.msg, 'error','','','','','','','','',2);
            $(input).val($(input).attr('changed'));
        }
        calc_cart_price();
    });
}

/**
 * 购物车减少商品数量
 * @param cart_id
 */
function decrease_quantity(cart_id){
    var item = $('#input_item_' + cart_id);
    var orig = Number(item.val());
    if(orig > 1){
        item.val(orig - 1);
        item.keyup();
    }
}

/**
 * 购物车增加商品数量
 * @param cart_id
 */
function add_quantity(cart_id){
    var item = $('#input_item_' + cart_id);
    var orig = Number(item.val());
    item.val(orig + 1);
    item.keyup();
}

/**
 * 购物车商品统计
 */
function calc_cart_price() {
    //每个店铺商品价格小计
    obj = $('table[nc_type="table_cart"]');
    if(obj.children('tbody').length==0) return;
    //购物车已选择商品的总价格
    var allTotal = 0;
    obj.children('tbody').each(function(){
        //购物车每个店铺已选择商品的总价格
        var eachTotal = 0;
        $(this).find('em[nc_type="eachGoodsTotal"]').each(function(){
            if ($(this).parent().parent().find('input[type="checkbox"]').eq(0).attr('checked') != 'checked') return;
            eachTotal = eachTotal + parseFloat($(this).html());  
        });
        allTotal += eachTotal;
        $(this).children('tr').last().find('em[nc_type="eachStoreTotal"]').eq(0).html(number_format(eachTotal,2));
    });
    $('#cartTotal').html(number_format(allTotal,2));
}
$(function(){
    calc_cart_price();
    $('#selectAll').on('click',function(){
        if ($(this).attr('checked')) {
            $('input[type="checkbox"]').attr('checked',true);
            $('input[type="checkbox"]:disabled').attr('checked',false);
        } else {
            $('input[type="checkbox"]').attr('checked',false);
        }
        calc_cart_price();
    });
    $('input[nc_type="eachGoodsCheckBox"]').on('click',function(){
        if (!$(this).attr('checked')) {
            $('#selectAll').attr('checked',false);
        }
        calc_cart_price();
    });
    $('#next_submit').on('click',function(){
        if ($(document).find('input[nc_type="eachGoodsCheckBox"]:checked').size() == 0) {
            showDialog('请选中要结算的商品', 'eror','','','','','','','','',2);
            return false;
        }else {
            $('#form_buy').submit();
        }
    });
});