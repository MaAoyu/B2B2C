<?php
/**
 * cms首页
 *
 *
 **by 好商城V3 www.33hao.com 运营版*/

defined('InShopNC') or exit('Access Invalid!');
class indexControl extends CMSHomeControl{

	public function __construct() {
		parent::__construct();
        Tpl::output('index_sign','index');
    }
	public function indexOp(){
        Tpl::showpage('index');
	}
}
