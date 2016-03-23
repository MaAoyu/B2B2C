<?php
/**
 * The AJAX call member information
 *
 *
 *
 *
 *by 33hao www.33hao.com 开发修正
 */

class member_cardControl extends MircroShopControl{
	public function mcard_infoOp(){
		$uid	= intval($_GET['uid']);
        if($uid <= 0) {
			echo 'false';exit;
        }
        $model_micro_member_info = Model('micro_member_info');
        $micro_member_info = $model_micro_member_info->getOneById($uid);
		if(empty($micro_member_info)){
			echo 'false';exit;
		}
		echo json_encode($micro_member_info);exit;
	}
}
