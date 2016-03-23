<?php defined('InShopNC') or exit('Access Invalid!');?>
<div class="page">
  <div class="fixed-bar">
    <div class="item-title">
      <h3>经验值管理</h3>
      <ul class="tab-base">
      	<li><a href="index.php?act=exppoints&op=index" ><span>经验值明细</span></a></li>
        <li><a href="JavaScript:void(0);" class="current"><span>规则设置</span></a></li>
      </ul>
    </div>
  </div>
  <div class="fixed-empty"></div>
  <form method="post" name="settingForm" id="settingForm">
    <input type="hidden" name="form_submit" value="ok" />
    <table class="table tb-type2">
      <tbody>
        <tr>
          <td class="" colspan="2"><table class="table tb-type2 nomargin">
              <thead>
                <tr class="space">
                  <th colspan="16">经验值获取规则如下:</th>
                </tr>
                <tr class="thead">
                  <th>项目</th>
                  <th>获得经验值</th>
                </tr>
              </thead>
              <tbody>
                <tr class="hover">
                  <td class="w200">会员每天第一次登录</td>
                  <td><input id="exp_login" name="exp_login" value="<?php echo $output['list_setting']['exppoints_rule']['exp_login'];?>" class="txt" type="text" style="width:60px;"></td>
                </tr>
                <tr class="hover">
                  <td class="w200">订单商品评论</td>
                  <td><input id="exp_comments" name="exp_comments" value="<?php echo $output['list_setting']['exppoints_rule']['exp_comments'];?>" class="txt" type="text" style="width:60px;"></td>
                </tr>
              </tbody>
            </table>
            <table class="table tb-type2 nomargin">
              <thead>
                <tr class="thead">
                  <th colspan="2">购物</th>
                </tr>
              </thead>
              <tbody>
                <tr class="hover">
                  <td class="w200">消费额与赠送经验值比例</td>
                  <td><input id="exp_orderrate" name="exp_orderrate" value="<?php echo $output['list_setting']['exppoints_rule']['exp_orderrate'];?>" class="txt" type="text" style="width:60px;">
                   该值为大于0的数， 例:设置为10，表明消费10单位货币赠送1经验值</td>
                </tr>
                <tr class="hover">
                  <td>每订单最多赠送经验值</td>
                  <td><input id="exp_ordermax" name="exp_ordermax" value="<?php echo $output['list_setting']['exppoints_rule']['exp_ordermax'];?>" class="txt" type="text" style="width:60px;">
                    该值为大于等于0的数，填写为0表明不限制最多经验值，例:设置为100，表明每订单赠送经验值最多为100经验值</td>
                </tr>
      </tbody>
      <tfoot>
        <tr class="tfoot">
          <td colspan="2" ><a href="JavaScript:void(0);" class="btn" id="submitBtn"><span><?php echo $lang['nc_submit'];?></span></a></td>
        </tr>
      </tfoot>
    </table>
  </form>
</div>
<script>
$(function(){
	$("#submitBtn").click(function(){
		$("#settingForm").submit();
	});
});
</script> 
