<?php
/**
 * 免运费额度设置
 *
 *
 *
 **by 好商城V3 www.33hao.com 运营版*/


defined('InShopNC') or exit('Access Invalid!');
class store_free_freightControl extends BaseSellerControl {

    public function __construct(){
    	parent::__construct();
    }

    public function indexOp(){
        $model_store = Model('store');
        if (chksubmit()) {
            $store_free_price = floatval(abs($_POST['store_free_price']));
			$store_free_time = $_POST['store_free_time'];
            $model_store->editStore(array('store_free_price'=>$store_free_price,'store_free_time'=>$store_free_time),array('store_id'=>$_SESSION['store_id']));
            showDialog(L('nc_common_save_succ'),'reload','succ');
        }
        Tpl::output('store_free_price',$this->store_info['store_free_price']);
		Tpl::output('store_free_time',$this->store_info['store_free_time']);
        self::profile_menu('daddress','free_freight');
        Tpl::showpage('store_free_freight.index');
	}

	/**
	 * 用户中心右边，小导航
	 *
	 * @param string	$menu_type	导航类型
	 * @param string 	$menu_key	当前导航的menu_key
	 * @return
	 */
    private function profile_menu($menu_type,$menu_key='') {
        $menu_array		= array();
        switch ($menu_type) {
        	case 'free_freight':
        		$menu_array = array(
        		 array('menu_key'=>'free_freight',	'menu_name'=>'免运费额度',		'menu_url'=>'index.php?act=store_free_freight')
        		);
        	break;
        }
    	Tpl::output('member_menu',$menu_array);
    	Tpl::output('menu_key',$menu_key);
    }
}
?>
