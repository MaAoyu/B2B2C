<?php defined('InShopNC') or exit('Access Invalid!');?>
<!--好商城V3-B11 33 HAO . C OM -->
<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <h3><?php echo $lang['nc_message_set'];?></h3>
      <?php echo $output['top_link'];?>
    </div>
  </div>
  <div class="fixed-empty"></div>
  <form method="post" id="form_email" name="settingForm">
    <input type="hidden" name="form_submit" value="ok" />
    <table class="table tb-type2">
      <tbody>
       <tr class="noborder">
          <td colspan="2" class="required">选择短信平台:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform">
          
         
          <?php if($output['list_setting']['mobile_host_type']==1){ ?>
          <label>
          <input type="radio" name="mobile_host_type" value="1" checked="checked" />短信宝
          </label>
          <input type="radio" name="mobile_host_type" value="2" />
          云片网络
          </label>
          <?php }else{ ?>
           <input type="radio" name="mobile_host_type" value="1" />短信宝
          </label>
          <input type="radio" name="mobile_host_type" value="2" checked="checked" />
          云片网络
          </label>
          
          <?php }?>
          
          
          </td>
          <td class="vatop tips"><label class="field_notice">点击可以切换短信商，只能二选一</label></td>
        </tr>
        
        <tr class="noborder">
          <td colspan="2" class="required">短信服务商名称:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php echo $output['list_setting']['mobile_host'];?>" name="mobile_host" id="mobile_host" class="txt"></td>
          <td class="vatop tips"><label class="field_notice"> 	可选填写<a href="http://api.smsbao.com/?33hao" target="_blank">短信宝</a>或
          <a href="http://www.yunpian.com/?33hao" target="_blank">云片短信</a></label></td>
        </tr>
        <tr>
          <td colspan="2" class="required">短信平台账号:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php echo $output['list_setting']['mobile_username'];?>" name="mobile_username" id="mobile_username" class="txt"></td>
          <td class="vatop tips"><label class="field_notice">用户名</label></td>
        </tr>
        <tr>
          <td colspan="2" class="required">短信平台密码:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php echo $output['list_setting']['mobile_pwd'];?>" name="mobile_pwd" id="mobile_pwd" class="txt"></td>
          <td class="vatop tips"><label class="field_notice">短信平台密码</label></td>
        </tr>
        <tr>
          <td colspan="2" class="required">短信平台Key:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php echo $output['list_setting']['mobile_key'];?>" name="mobile_key" id="mobile_key" class="txt"></td>
          <td class="vatop tips"><label class="field_notice">选填，使用云片短信时用到</label></td>
        </tr>
        
         <tr>
          <td colspan="2" class="required">短信内容签名:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform"><input type="text" value="<?php echo $output['list_setting']['mobile_signature'];?>" name="mobile_signature" id="mobile_signature" class="txt"></td>
          <td class="vatop tips"><label class="field_notice">如： 好商城</label></td>
        </tr>
        
        <tr>
          <td colspan="2" class="required">备注信息:</td>
        </tr>
        <tr class="noborder">
          <td class="vatop rowform">
          <textarea id="statistics_code" class="tarea" rows="6" name="mobile_memo"><?php echo $output['list_setting']['mobile_memo'];?></textarea></td>
          <td class="vatop tips"><label class="field_notice">选填</label></td>
        </tr>
       
      </tbody>
      <tfoot>
        <tr class="tfoot">
          <td colspan="2" ><a href="JavaScript:void(0);" class="btn" onclick="document.settingForm.submit()"><span><?php echo $lang['nc_submit'];?></span></a></td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
<script>
$(document).ready(function(){
	$('#send_test_mobile').click(function(){
		$.ajax({
			type:'POST',
			url:'index.php',
			data:'act=message&op=email_testing&email_host='+$('#email_host').val()+'&email_port='+$('#email_port').val()+'&email_addr='+$('#email_addr').val()+'&email_id='+$('#email_id').val()+'&email_pass='+$('#email_pass').val()+'&email_test='+$('#email_test').val(),
			error:function(){
					alert('<?php echo $lang['test_email_send_fail'];?>');
				},
			success:function(html){
				alert(html.msg);
			},
			dataType:'json'
		});
	});
});
</script>