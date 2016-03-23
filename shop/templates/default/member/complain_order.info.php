
<div class="ncm-flow-item">
  <div class="title">相关商品交易</div>
  <div class="item-goods">
    <?php if (is_array($output['goods_list']) && !empty($output['goods_list'])) { ?>
        <?php foreach ($output['goods_list'] as $key => $val) { ?>
      <dl>
        <dt>
          <div class="ncm-goods-thumb-mini"><a target="_blank" href="<?php echo urlShop('goods','index',array('goods_id'=> $val['goods_id'])); ?>">
            <img src="<?php echo thumb($val,60); ?>" onMouseOver="toolTip('<img src=<?php echo thumb($val,240); ?>>')" onMouseOut="toolTip()" /></a></div>
        </dt>
        <dd><a target="_blank" href="<?php echo urlShop('goods','index',array('goods_id'=> $val['goods_id'])); ?>"><?php echo $val['goods_name']; ?></a>
            <?php echo $lang['currency'];?><?php echo $val['goods_price']; ?> * <?php echo $val['goods_num']; ?> <font color="#AAA">(数量)</font>
            <span><?php echo orderGoodsType($val['goods_type']);?></span>
        </dd>
      </dl>
    <?php } ?>
    <?php } ?>
  </div>
  <div class="item-order">
      <dl>
        <dt>运&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;费：</dt>
        <dd><?php echo $output['order']['shipping_fee']>0 ? ncPriceFormat($output['order']['shipping_fee']):$lang['nc_common_shipping_free']; ?></dd>
      </dl>
    <dl>
      <dt><?php echo $lang['order_price'].$lang['nc_colon'];?></dt>
      <dd><strong><?php echo $lang['currency'].$output['order']['order_amount'];?></strong></dd>
    </dl>
    <dl class="line">
      <dt> <?php echo $lang['order_sn'].$lang['nc_colon'];?></dt>
      <dd> <a href="<?php echo SHOP_SITE_URL;?>/index.php?act=member_order&op=show_order&order_id=<?php echo $output['order']['order_id'];?>"  target="_blank"> <?php echo $output['order']['order_sn'];?> </a> <a href="javascript:void(0);" class="a">更多<i class="icon-angle-down"></i>
        <div class="more"> <span class="arrow"></span>
          <ul>
              <?php if($output['order']['payment_code'] != 'offline' && !in_array($output['order']['order_state'],array(ORDER_STATE_CANCEL,ORDER_STATE_NEW))) { ?>
              <li><?php echo '付款单号'.$lang['nc_colon'];?><span><?php echo $output['order']['pay_sn']; ?></span></li>
              <?php } ?>
              <li>支付方式：<span><?php echo $output['order']['payment_name']; ?>
              </span> </li>
              <li><?php echo $lang['member_order_time'].$lang['nc_colon'];?><span><?php echo date("Y-m-d H:i:s",$output['order']['add_time']); ?></span></li>
              <?php if ($output['order']['payment_time'] > 0) { ?>
              <li><?php echo $lang['member_show_order_pay_time'].$lang['nc_colon'];?><span><?php echo date("Y-m-d H:i:s",$output['order']['payment_time']); ?></span></li>
              <?php } ?>
              <?php if ($output['order_common']['shipping_time'] > 0) { ?>
              <li><?php echo $lang['member_show_order_send_time'].$lang['nc_colon'];?><span><?php echo date("Y-m-d H:i:s",$output['order_common']['shipping_time']); ?></span></li>
              <?php } ?>
              <?php if ($output['order']['finnshed_time'] > 0) { ?>
              <li><?php echo $lang['member_show_order_finish_time'].$lang['nc_colon'];?><span><?php echo date("Y-m-d H:i:s",$output['order']['finnshed_time']); ?></span></li>
              <?php } ?>
          </ul>
        </div>
        </a> </dd>
    </dl>
      <?php if (!empty($output['order']['shipping_code'])) { ?>
      <dl>
        <dt>物流单号：</dt>
        <dd><a target="_blank" href="index.php?act=member_order&op=search_deliver&order_id=<?php echo $output['order']['order_id']; ?>"><?php echo $output['order']['shipping_code'];?></a>
            <a href="javascript:void(0);" class="a"><?php echo $output['e_name'];?></a></dd>
      </dl>
      <?php } ?>
    <dl class="line">
        <dt>商&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;家：</dt>
        <dd><?php echo $output['order']['store_name'];?><a href="javascript:void(0);" class="a">更多<i class="icon-angle-down"></i>
          <div class="more"><span class="arrow"></span>
            <ul>
              <li><?php echo $lang['member_address_location'].$lang['nc_colon'];?><span><?php echo $output['store']['area_info'].'&nbsp;'.$output['store']['store_address'];?></span></li>
              <li>联系电话：<span><?php echo $output['store']['store_phone'];?></span></li>
            </ul>
          </div>
          </a>
          <div><span member_id="<?php echo $output['store']['member_id'];?>"></span>
          <?php if (!empty($output['store']['store_qq'])) { ?>
          <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&uin=<?php echo $output['store']['store_qq'];?>&site=qq&menu=yes" title="QQ: <?php echo $output['store']['store_qq'];?>"><img border="0" src="http://wpa.qq.com/pa?p=2:<?php echo $output['store']['store_qq'];?>:52" style=" vertical-align: middle;"/></a>
          <?php } ?>
          <?php if (!empty($output['store']['store_ww'])) { ?>
          <a target="_blank" href="http://amos.im.alisoft.com/msg.aw?v=2&uid=<?php echo $output['store']['store_ww'];?>&site=cntaobao&s=2&charset=<?php echo CHARSET;?>"  class="vm" ><img border="0" src="http://amos.im.alisoft.com/online.aw?v=2&uid=<?php echo $output['store']['store_ww'];?>&site=cntaobao&s=2&charset=<?php echo CHARSET;?>" alt="Wang Wang"  style=" vertical-align: middle;"/></a>
          <?php } ?>
          </div>
        </dd>
    </dl>
  </div>
</div>
