<?php
/*******
 * 圈子话题管理 
 *
 * by www.33hao.com 好商城V3
 */
defined('InShopNC') or exit('Access Invalid!');
class circle_cacheControl extends SystemControl{
	public function __construct(){
		parent::__construct();
	}
	public function indexOp(){
		rcache('circle_level',true);
		showMessage(L('nc_common_op_succ'), 'index.php?act=circle_setting');
	}
}






