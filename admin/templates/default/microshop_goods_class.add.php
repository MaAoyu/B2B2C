<?php defined('InShopNC') or exit('Access Invalid!');?>

<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <h3><?php echo $lang['nc_microshop_goods_class'];?></h3>
      <ul class="tab-base">
        <?php   foreach($output['menu'] as $menu) {  if($menu['menu_type'] == 'text') { ?>
        <li><a href="<?php echo $menu['menu_url'];?>" class="current"><span><?php echo $menu['menu_name'];?></span></a></li>
        <?php }  else { ?>
        <li><a href="<?php echo $menu['menu_url'];?>" ><span><?php echo $menu['menu_name'];?></span></a></li>
        <?php  } }  ?>
      </ul>
    </div>
  </div>
  <div class="fixed-empty"></div>
  <form id="add_form" method="post" enctype="multipart/form-data" action="index.php?act=microshop&op=goodsclass_save">
    <input name="class_id" type="hidden" value="<?php echo $output['class_info']['class_id'];?>" />
    <table class="table tb-type2">
      <tbody>
        <tr class="noborder">
          <td colspan="2" class="required"><label class="validation" for="class_name"><?php echo $lang['microshop_class_name'].$lang['nc_colon'];?></label></td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php if(isset($output['class_info']['class_name'])) echo $output['class_info']['class_name'];?>" name="class_name" id="class_name" class="txt"></td>
          <td class="vatop tips"><?php echo $lang['class_name_error'];?></td>
        </tr>
        <?php if(empty($output['class_info'])) { ?>
        <tr>
          <td colspan="2" class="required"><label for="class_parent_id"><?php echo $lang['microshop_parent_class'].$lang['nc_colon'];?></label></td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><select id="class_parent_id" name="class_parent_id" class="valid" >
              <option value="0"><?php echo $lang['nc_common_pselect'];?></option>
              <?php if(!empty($output['list']) && is_array($output['list'])) {?>
              <?php foreach($output['list'] as $key=>$val) {?>
              <option value="<?php echo $val['class_id'];?>" <?php if($output['class_parent_id'] == $val['class_id']) echo 'selected';?>><?php echo $val['class_name'];?></option>
              <?php } ?>
              <?php } ?>
            </select></td>
          <td class="vatop tips"></td>
        </tr>
        <?php } ?>
        <tr>
          <td colspan="2" class="required"><label for="class_image"><?php echo $lang['microshop_class_image'].$lang['nc_colon'];?></label></td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><span class="type-file-show"> <img class="show_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/preview.png">
            <div class="type-file-preview">
              <?php if(empty($output['class_info']['class_image'])) { ?>
              <img src="<?php echo UPLOAD_SITE_URL.DS.ATTACH_MICROSHOP.DS.'default_goods_class_image.gif';?>">
              <?php } else { ?>
              <img src="<?php echo UPLOAD_SITE_URL.DS.ATTACH_MICROSHOP.DS.$output['class_info']['class_image'];?>">
              <?php } ?>
            </div>
            </span> <span class="type-file-box">
            <input name="old_class_image" type="hidden" value="<?php echo $output['class_info']['class_image'];?>" />
            <input name="class_image" type="file" class="type-file-file" id="class_image" size="30" hidefocus="true" nc_type="microshop_goods_class_image">
            </span></td>
          <td class="vatop tips"></td>
        </tr>
        <?php if(empty($output['class_info']) || intval($output['class_info']['class_parent_id']) > 0) { ?>
        <tr class="class_keyword">
          <td colspan="2" class="required"><label for="class_sort" class="validation"><?php echo $lang['microshop_class_keyword'].$lang['nc_colon'];?></label></td>
        </tr>
        <tr class="noborder class_keyword">
          <td class="vatop rowform"><textarea name="class_keyword" rows="25" cols="30"><?php echo empty($output['class_info']['class_keyword'])?'':$output['class_info']['class_keyword'];?></textarea></td>
          <td class="vatop tips"><?php echo $lang['class_keyword_explain'];?></td>
        </tr>
        <?php } ?>
        <tr>
          <td colspan="2" class="required"><label for="class_sort" class="validation"><?php echo $lang['nc_sort'].$lang['nc_colon'];?></label></td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input id="class_sort" name="class_sort" type="text" class="txt" value="<?php echo !isset($output['class_info'])?'255':$output['class_info']['class_sort'];?>" /></td>
          <td class="vatop tips"><?php echo $lang['class_sort_explain'];?></td>
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="2"><a id="submit" href="javascript:void(0)" class="btn"><span><?php echo $lang['nc_submit'];?></span></a></td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
<script type="text/javascript">
$(document).ready(function(){

    //文件上传
    var textButton="<input type='text' name='textfield' id='textfield1' class='type-file-text' /><input type='button' name='button' id='button1' value='' class='type-file-button' />";
    $(textButton).insertBefore("#class_image");
    $("#class_image").change(function(){
        $("#textfield1").val($("#class_image").val());
    });

    $("#submit").click(function(){
        $("#add_form").submit();
    });

    $("input[nc_type='microshop_goods_class_image']").live("change", function(){
		var src = getFullPath($(this)[0]);
		$(this).parent().prev().find('.low_source').attr('src',src);
		$(this).parent().find('input[class="type-file-text"]').val($(this).val());
	});
    <?php if(empty($output['class_info'])) { ?>
    class_keyword_display();    
    $("#class_parent_id").change(function(){
        class_keyword_display();    
    });
    function class_keyword_display() {
        var class_parent_id = $("#class_parent_id").val();
        if(class_parent_id > 0) {
            $(".class_keyword").show();
        } else {
            $(".class_keyword").hide();
        }
    }
    <?php } ?>

    $('#add_form').validate({
        errorPlacement: function(error, element){
            error.appendTo(element.parent().parent().prev().find('td:first'));
        },
        rules : {
            class_name: {
                required : true,
                maxlength : 10
            },
            class_keyword: {
                maxlength : 150
            },
            class_sort: {
                required : true,
                digits: true,
                max: 255,
                min: 0
            }
        },
        messages : {
            class_name: {
                required : "<?php echo $lang['class_name_required'];?>",
                maxlength : jQuery.validator.format("<?php echo $lang['class_name_maxlength'];?>")
            },
            class_keyword: {
                maxlength : jQuery.validator.format("<?php echo $lang['class_keyword_maxlength'];?>"),
            },
            class_sort: {
                required : "<?php echo $lang['class_sort_required'];?>",
                digits: "<?php echo $lang['class_sort_digits'];?>",
                max : jQuery.validator.format("<?php echo $lang['class_sort_max'];?>"),
                min : jQuery.validator.format("<?php echo $lang['class_sort_min'];?>")
            }
        }
    });
});
</script> 
