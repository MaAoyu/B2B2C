<?php
/**
 * 所有店铺首页 好商城v3 33hao.com
 */

//use shopnc\Tpl;

defined('InShopNC') or exit('Access Invalid!');


class shopControl extends mobileHomeControl {

    public function __construct(){
        parent::__construct();
    }

    /*
     * 首页显示
     */
    public function indexOp(){


        $this->_get_Own_Store_List();

    }


    private  function  _get_Own_Store_List(){

        //获取自营店列表
        $model_store = Model("store");
	//如果只想显示自营店铺，把下面的//去掉即可
        //$condition = array(
         //   'is_own_shop' => 1,
        //);

        $own_store_list = $model_store->getStoreList($condition);

        $simply_store_list = array();

        foreach ($own_store_list as $key => $value) {

            $simply_store_list[$key]['store_id'] = $own_store_list[$key]['store_id'];
            $simply_store_list[$key]['store_name'] = $own_store_list[$key]['store_name'];
            $simply_store_list[$key]['store_address'] = $own_store_list[$key]['store_address'];
            $simply_store_list[$key]['store_area_info'] = $own_store_list[$key]['area_info'];

        }

        output_data(array('store_list' => $simply_store_list));
    }
}