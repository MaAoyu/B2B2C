<?php defined('InShopNC') or exit('Access Invalid!');?>

<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <h3><?php echo $lang['link_index_mb_category'];?></h3>
      <ul class="tab-base">
        <li><a href="index.php?act=mb_category&op=mb_category_list" ><span><?php echo $lang['nc_manage'];?></span></a></li>
        <li><a href="index.php?act=mb_category&op=mb_category_add" ><span><?php echo $lang['nc_new'];?></span></a></li>
        <li><a href="JavaScript:void(0);" class="current"><span><?php echo $lang['nc_edit'];?></span></a></li>
      </ul>
    </div>
  </div>
  <div class="fixed-empty"></div>
  <form id="link_form" enctype="multipart/form-data" method="post">
    <input type="hidden" name="form_submit" value="ok" />
    <input type="hidden" name="gc_id" value="<?php echo $output['link_array']['gc_id'];?>" />
    <table class="table tb-type2">
      <tbody>
        <tr class="noborder">
          <td colspan="2" class="required"><?php echo $lang['link_index_category'];?>:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform">
 			<?php echo $output['goods_class'][$output['link_array']['gc_id']]['gc_name'];?>
          </td>
          <td class="vatop tips"></td>
        </tr>
        <tr>
          <td colspan="2" class="required"><label for=""><?php echo $lang['link_index_pic_sign'];?>:</label></td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><span class="type-file-show"><img class="show_image" src="<?php echo ADMIN_TEMPLATES_URL;?>/images/preview.png">
            <div class="type-file-preview"><img src="<?php echo UPLOAD_SITE_URL.DS.ATTACH_MOBILE.'/category/'.$output['link_array']['gc_thumb'];?>"></div>
            </span> <span class="type-file-box">
            <input name="link_pic" type="file" class="type-file-file" id="link_pic" size="30">
            </span></td>
          <td class="vatop tips">展示图片，建议大小72px*72px</td>
        </tr>
      <tfoot>
        <tr class="tfoot">
          <td colspan="15"><a href="JavaScript:void(0);" class="btn" id="submitBtn"><span><?php echo $lang['nc_submit'];?></span></a></td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
<script>
//按钮先执行验证再提交表单
$(function(){$("#submitBtn").click(function(){
    if($("#link_form").valid()){
     $("#link_form").submit();
	}
	});
});
//
$(document).ready(function(){
	$('#link_form').validate({
        errorPlacement: function(error, element){
			error.appendTo(element.parent().parent().prev().find('td:first'));
        },
        success: function(label){
            label.addClass('valid');
        },
        rules : {
            link_title : {
                required : true
            },
            link_url  : {
                required : true,
                url      : true
            },
            link_sort : {
                number   : true
            }
        },
        messages : {
            link_title : {
                required : '<?php echo $lang['link_add_title_null'];?>'
            },
            link_url  : {
                required : '<?php echo $lang['link_add_url_null'];?>',
                url      : '<?php echo $lang['link_add_url_wrong'];?>'
            },
            link_sort  : {
                number   : '<?php echo $lang['link_add_sort_int'];?>'
            }
        }
    });
});
</script> 
<script type="text/javascript">
$(function(){
    var textButton="<input type='text' name='textfield' id='textfield1' class='type-file-text' /><input type='button' name='button' id='button1' value='' class='type-file-button' />"
	$(textButton).insertBefore("#link_pic");
	$("#link_pic").change(function(){
	$("#textfield1").val($("#link_pic").val());
});
});
</script>
