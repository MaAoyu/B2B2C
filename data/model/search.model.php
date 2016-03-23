<?php
/**
 * 搜索
 *
 *
 *
 *
 * by 33hao 好商城V3  www.33hao.com 开发
 */


defined('InShopNC') or exit('Access Invalid!');

class searchModel{

    //是否开启分面搜索
    private $_open_face = false;

    //全文搜索对象
    private $_xs_search;

    //全文搜索对象
    private $_xs_index;

    //全文搜索到的商品ID数组
    private $_indexer_ids = array();

    //全文搜索到的品牌数组
    private $_indexer_brands = array();

    //全文检索到的商品分类数组
    private $_indexer_cates = array();


    //全文搜索结果总数
    private $_indexer_count;

    //搜索结果中品牌分面信息
    private $_face_brand = array();

    //搜索结果中品牌分面信息
    private $_face_attr = array();

    /**
     * 从全文索引库搜索关键词
     * @param unknown $condition 条件
     * @param unknown $order 排序
     * @param number $pagesize 每页显示商品数
     * @return
     */
    public function getIndexerList($condition = array(), $order = array(), $pagesize = 24) {
        try {
            //全文搜索初始化
            $this->_createXS($pagesize,C('fullindexer.appname'));

            //设置搜索内容
            $this->_setQueryXS($condition,$order);

            //执行搜索
            $this->_searchXS();
            return array($this->_indexer_ids, $this->_indexer_count);

        } catch (XSException $e) {
//             if (C('debug')) {
//                 showMessage($e->getMessage(),'','html','error');
//             } else {
                Shopnc\Log::record('search\index'.$e->getMessage()."\r\n".$sql,Shopnc\Log::RUN);
                return false;
//             }
        }
    }

    /**
     * 设置全文检索查询条件
     * @param unknown $condition
     * @param array $order
     */
    private function _setQueryXS($condition,$order) {
        $condition['keyword'] = preg_replace("/([a-zA-Z0-9]{2,})/",' ${1} ',$condition['keyword']);
        $this->_xs_search->setQuery(is_null($condition['keyword']) ? '':$condition['keyword']);
        if (isset($condition['cate'])) {
            $this->_xs_search->addQueryString($condition['cate']['key'].':'.$condition['cate']['value']);
        } else {
			$this->_open_face = false;
		}
        if (isset($condition['brand_id'])) {
            $this->_xs_search->addQueryString('brand_id'.':'.$condition['brand_id']);
        }
        if (isset($condition['store_id'])) {
            $this->_xs_search->addQueryString('store_id'.':'.$condition['store_id']);
        }
        if (isset($condition['area_id'])) {
            $this->_xs_search->addQueryString('area_id'.':'.$condition['area_id']);
        }
        if (isset($condition['have_gift'])) {
            $this->_xs_search->addQueryString('have_gift'.':'.$condition['have_gift']);
        }
        if (is_array($condition['attr_id'])) {
            foreach ($condition['attr_id'] as $attr_id) {
                $this->_xs_search->addQueryString('attr_id'.':'.$attr_id);
            }
        }
        if (count($order) > 1) {
            $this->_xs_search->setMultiSort($order);
        } else {
            $this->_xs_search->setSort($order);
        }
    }

    /**
     * 创建全文搜索对象，并初始化基本参数
     * @param number $pagesize 每页显示商品数
     * @param string $appname 全文搜索INI配置文件名
     */
    private function _createXS($pagesize,$appname) {
        if (is_numeric($_GET['curpage']) && $_GET['curpage'] > 0) {
            $curpage = intval($_GET['curpage']);
            $start =  ($curpage-1) * $pagesize;
        } else {
            $start = null;
        }
        require_once(BASE_DATA_PATH.'/api/xs/lib/XS.php');
        $obj_doc = new XSDocument();
        $obj_xs = new XS(C('fullindexer.appname'));
        $this->_xs_search = $obj_xs->search;
        $this->_xs_index = $obj_xs->index;
        $this->_xs_search->setCharset(CHARSET);
        $this->_xs_search->setLimit($pagesize,$start);
        //设置分面
        if ($this->_open_face) {
            $this->_xs_search->setFacets(array('brand_id','attr_id'));
        }
    }

    /**
     * 执行全文搜索
     */
    private function _searchXS(){

//            $goods_class = Model('goods_class')->getGoodsClassIndexedListAll();
        $docs = $this->_xs_search->search();
        $count = $this->_xs_search->getLastCount();
        $goods_ids = array();
        $brands = array();
        $cates = array();
        foreach ($docs as $k => $doc) {
            $goods_ids[] = $doc->goods_id;
//                 if ($doc->brand_id > 0) {
//                     $brands[$doc->brand_id]['brand_id'] = $doc->brand_id;
//                     $brands[$doc->brand_id]['brand_name'] = $doc->brand_name;
//                 }
//                 if ($doc->gc_id > 0) {
//                     $cates[$doc->gc_id]['gc_id'] = $doc->gc_id;
//                     $cates[$doc->gc_id]['gc_name'] = $goods_class[$doc->gc_id]['gc_name'];
//                 }
        }
        $this->_indexer_ids = $goods_ids;
        $this->_indexer_count = $count;
        $this->_indexer_brands = $brands;
        $this->_indexer_cates = $cates;

        //读取分面结果
        if ($this->_open_face) {
            $this->_face_brand = $this->_xs_search->getFacets('brand_id');
            $this->_face_attr = $this->_xs_search->getFacets('attr_id');
            $this->_parseFaceAttr($this->_face_attr);
        }
        return true;
    }

    /**
     * 处理属性多面信息
     */
    private function _parseFaceAttr($face_attr = array()) {
        if (!is_array($face_attr)) return;
        $new_attr = array();
        foreach ($face_attr as $k => $v) {
            $new_attr = array_merge($new_attr,explode('_',$k));
        }
        $this->_face_attr = $new_attr;
    }

    /**
     * 删除没有商品的品牌(不显示)
     * @param unknown $brand_array
     * @return unknown|multitype:
     */
    public function delInvalidBrand($brand_array = array()) {
        if (!$this->_open_face) return $brand_array;
        if (is_array($brand_array) && is_array($this->_face_brand)) {
            foreach ($brand_array as $k => $v) {
                if (!isset($this->_face_brand[$k])) {
                    unset($brand_array[$k]);
                }
            }
        }
        return $brand_array;
    }

    /**
     * 删除没有商品的属性(不显示)
     * @param unknown $brand_array
     * @return unknown|multitype:
     */
    public function delInvalidAttr($attr_array = array()) {
        if (!$this->_open_face) return $attr_array;
        if (is_array($attr_array) && is_array($this->_face_attr)) {
            foreach ($attr_array as $key => $value) {
                if (is_array($value['value'])) {
                    foreach ($value['value'] as $k => $v) {
                    	if (!in_array($k,$this->_face_attr)) {
                    	    unset($attr_array[$key]['value'][$k]);
                    	}
                    }
                }
            }
        }
        return $attr_array;
    }

    public function __get($key) {
        return $this->$key;
    }

    /**
     * 删除搜索索引中的无效商品
     * @param unknown $brand_array
     * @return unknown|multitype:
     */
    public function delInvalidGoods($goods_list, $indexer_ids = array()) {
        $goods_ids = array();
        foreach ($goods_list as $k => $v) {
            $goods_ids[] = $v['goods_id'];
        }
        $_diff_ids = array_diff($indexer_ids,$goods_ids);

        if (!empty($_diff_ids)) {
            file_put_contents(BASE_DATA_PATH.'/log/search.log',date('Y-m-d H:i:s',TIMESTAMP)."\r\n",FILE_APPEND);
            file_put_contents(BASE_DATA_PATH.'/log/search.log',implode(',',$indexer_ids)."\r\n",FILE_APPEND);
            file_put_contents(BASE_DATA_PATH.'/log/search.log',implode(',',$goods_ids)."\r\n",FILE_APPEND);
            file_put_contents(BASE_DATA_PATH.'/log/search.log',implode(',',$_diff_ids)."\r\n\r\n",FILE_APPEND);
//             $this->_xs_index->del($_diff_ids);
//             QueueClient::push('flushIndexer', '');
        }
    }

    /**
     * 取得商品分类详细信息
     *
     * @param array $search_param 需要的参数内容
     * @return array 数组类型的返回结果
     */
    public function getAttr($get, $default_classid){
        if(!empty($get['a_id'])){
            $attr_ids = explode('_', $get['a_id']);
        }
        $data = array();
        $model_type = Model('type');

        // 获取当前的分类内容
        $class_array = Model('goods_class')->getGoodsClassForCacheModel();
        $data['class'] = $class_array[$get['cate_id']];
        if (empty($data['class']['child']) && empty($data['class']['childchild'])) {
            // 根据属性查找商品
            if (is_array($attr_ids)) {
                // 商品id数组
                $goodsid_array = array();
                $data['sign'] = false;
                foreach ($attr_ids as $val) {
                    $where = array();
                    $where['attr_value_id'] = $val;
                    if ($data['sign']) {
                        $where['goods_id'] = array('in', $goodsid_array);
                    }
                    $goodsattrindex_list = Model('goods_attr_index')->getGoodsAttrIndexList($where, 'goods_id');
                    if (!empty($goodsattrindex_list)) {
                        $data['sign'] = true;
                        $tpl_goodsid_array = array();
                        foreach ($goodsattrindex_list as $val) {
                            $tpl_goodsid_array[] = $val['goods_id'];
                        }
                        $goodsid_array = $tpl_goodsid_array;
                    } else {
                        $data['goodsid_array'] = $goodsid_array = array();
                        $data['sign'] = false;
                        break;
                    }
                }
                if ($data['sign']) {
                    $data['goodsid_array'] = $goodsid_array;
                }
            }
        }

        $class = $class_array[$default_classid];
        if (empty($class['child']) && empty($class['childchild'])) {


            //获得分类对应的类型ID
            $type_id = $class['type_id'];

            //品牌列表
            $typebrand_list = Model('type')->getTypeBrandList(array('type_id' => $type_id), 'brand_id');
            if (!empty($typebrand_list)) {
                $brandid_array = array();
                foreach ($typebrand_list as $val) {
                    $brandid_array[] = $val['brand_id'];
                }
                $brand_array = Model('brand')->getBrandPassedList(array('brand_id' => array('in', $brandid_array)), 'brand_id,brand_name');
                $brand_array = array_under_reset($brand_array, 'brand_id');
            }
            // 被选中的品牌
            $brand_id = intval($get['b_id']);
            if ($brand_id > 0 && !empty($brand_array)){
                $checked_brand = array();
                if(isset($brand_array[$brand_id])){
                    $checked_brand[$brand_id]['brand_name'] = $brand_array[$brand_id]['brand_name'];
                }
            }

            //属性列表
            $model_attribute = Model('attribute');
            $attribute_list = $model_attribute->getAttributeShowList(array('type_id' => $type_id), 'attr_id,attr_name');
            $attributevalue_list = $model_attribute->getAttributeValueList(array('type_id' => $type_id), 'attr_value_id,attr_value_name,attr_id');
            $attributevalue_list = array_under_reset($attributevalue_list, 'attr_id', 2);
            $attr_array = array();
            if (!empty($attribute_list)) {
                foreach ($attribute_list as $val) {
                    $attr_array[$val['attr_id']]['name'] = $val['attr_name'];
                    $tpl_array = array_under_reset($attributevalue_list[$val['attr_id']], 'attr_value_id');
                    $attr_array[$val['attr_id']]['value'] = $tpl_array;
                }
            }
            // 被选中的属性
            if(is_array($attr_ids) && !empty($attr_array)){
                $checked_attr = array();
                foreach ($attr_ids as $s){
                    foreach ($attr_array as $k=>$d){
                        if(isset($d['value'][$s])){
                            $checked_attr[$k]['attr_name']		= $d['name'];
                            $checked_attr[$k]['attr_value_id']	= $s;
                            $checked_attr[$k]['attr_value_name']= $d['value'][$s]['attr_value_name'];
                        }
                    }
                }
            }

            if (C('fullindexer.open')) {
                $brand_array = $this->delInvalidBrand($brand_array);
                $attr_array = $this->delInvalidAttr($attr_array);
            }
        }

        return array($data, $brand_array, $attr_array, $checked_brand, $checked_attr);
    }

    /**
     * 从TAG中查找分类
     */
   public function getTagCategory($keyword = '') {
        if ($keyword != '') {
            // 跟据class_tag缓存搜索出与keyword相关的分类
            $tag_list = rkcache('class_tag', true);
            if (!empty($tag_list) && is_array($tag_list)) {
                foreach($tag_list as $key => $val) {
                    $tag_value = str_replace(',', '==ShopNC==', $val['gc_tag_value']);
                    if (strpos($tag_value, $keyword)) {
                        $data[] = $val['gc_id'];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取父级分类，递归调用
     */
    private function _getParentCategory($gc_id, $goods_class, $data) {
        array_unshift($data, $gc_id);
        if ($goods_class[$gc_id]['gc_parent_id'] != 0) {
            return $this->_getParentCategory($goods_class[$gc_id]['gc_parent_id'], $goods_class, $data);
        } else {
            return $data;
        }
    }

    /**
     * 显示左侧商品分类
     * @param array $param 分类id
     * @sign int $sign 0为取得最后一级的同级分类，1为不取得
     */
     public function getLeftCategory($param, $sign = 0) {
        $data = array();
        if (!empty($param)) {
            $goods_class = Model('goods_class')->getGoodsClassForCacheModel();
            foreach ($param as $val) {
                $data[] = $this->_getParentCategory($val, $goods_class, array());
            }
        }
        $tpl_data = array();
        $gc_list = Model('goods_class')->get_all_category();
        foreach ($data as $value) {
            //$tpl_data[$val[0]][$val[1]][$val[2]] = $val[2];
            if (!empty($gc_list[$value[0]])){   // 一级
                $tpl_data[$value[0]]['gc_id'] = $gc_list[$value[0]]['gc_id'];
                $tpl_data[$value[0]]['gc_name'] = $gc_list[$value[0]]['gc_name'];
                if (!empty($gc_list[$value[0]]['class2'][$value[1]])) { // 二级
                    $tpl_data[$value[0]]['class2'][$value[1]]['gc_id'] = $gc_list[$value[0]]['class2'][$value[1]]['gc_id'];
                    $tpl_data[$value[0]]['class2'][$value[1]]['gc_name'] = $gc_list[$value[0]]['class2'][$value[1]]['gc_name'];
                    if (!empty($gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]])) {    // 三级
                        $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_id'] = $gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_id'];
                        $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_name'] = $gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_name'];
                        if (!$sign) {   // 取得全部三级分类
                            foreach ($gc_list[$value[0]]['class2'][$value[1]]['class3'] as $val) {
                                $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['gc_id'] = $val['gc_id'];
                                $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['gc_name'] = $val['gc_name'];
                                if ($value[2] == $val['gc_id']) {
                                    $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['default'] = 1;
                                }
                            }
                        }
                    } else {    // 取得全部二级分类
                        if (!$sign) {   // 取得同级分类
                            if (!empty($gc_list[$value[0]]['class2'])) {
                                foreach ($gc_list[$value[0]]['class2'] as $gc2) {
                                    $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['gc_id'] = $gc2['gc_id'];
                                    $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['gc_name'] = $gc2['gc_name'];
                                    if (!empty($gc2['class3'])) {
                                        foreach ($gc2['class3'] as $gc3) {
                                            $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_id'] = $gc3['gc_id'];
                                            $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_name'] = $gc3['gc_name'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {    // 取得全部一级分类
                    if (!$sign) {   // 取得同级分类
                        if (!empty($gc_list)) {
                            foreach ($gc_list as $gc1) {
                                $tpl_data[$gc1['gc_id']]['gc_id'] = $gc1['gc_id'];
                                $tpl_data[$gc1['gc_id']]['gc_name'] = $gc1['gc_name'];
                                if (!empty($gc1['class2'])) {
                                    foreach ($gc1['class2'] as $gc2) {
                                        $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['gc_id'] = $gc2['gc_id'];
                                        $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['gc_name'] = $gc2['gc_name'];
                                        if (!empty($gc2['class3'])) {
                                            foreach ($gc2['class3'] as $gc3) {
                                                $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_id'] = $gc3['gc_id'];
                                                $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_name'] = $gc3['gc_name'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tpl_data;
    }

    /**
     * 全文搜索
     * @return array 商品主键，搜索结果总数
     */
    public function indexerSearch($get = array(),$pagesize) {
        if (!C('fullindexer.open')) return array(null,0);

        $condition = array();

        //拼接条件
        if (intval($get['cate_id']) > 0) {
            $cate_id = intval($get['cate_id']);
        } elseif (intval($get['gc_id']) > 0) {
            $cate_id = intval($get['gc_id']);
        }
        if ($cate_id) {
            $goods_class = Model('goods_class')->getGoodsClassForCacheModel();
            $depth = $goods_class[$cate_id]['depth'];
            $cate_field = 'cate_'.$depth;
            $condition['cate']['key'] = $cate_field;
            $condition['cate']['value'] = $cate_id;
        }
        if ($get['keyword'] != '') {
            $condition['keyword'] = $get['keyword'];
        }
        if (intval($get['b_id']) > 0) {
            $condition['brand_id'] = intval($get['b_id']);
        }
        if (preg_match('/^[\d_]+$/',$get['a_id'])) {
            $attr_ids = explode('_',$get['a_id']);
            if (is_array($attr_ids)){
                foreach ($attr_ids as $v) {
                    if (intval($v) > 0) {
                        $condition['attr_id'][] = intval($v);
                    }
                }
            }
        }
        if ($get['type'] == 1) {
            $condition['store_id'] = 1;
        }
        if (intval($get['area_id']) > 0) {
            $condition['area_id'] = intval($get['area_id']);
        }
        if ($get['gift'] == 1) {
            $condition['have_gift'] = 1;
        }
        //拼接排序(销量,浏览量,价格)
        $order = array();
        $order = array('store_id' => false,'goods_id' => false);
        if (in_array($get['key'],array('1','2','3'))) {
            $order = array(str_replace(array('1','2','3'), array('goods_salenum','goods_click','goods_price'), $get['key'])
                    => $get['order'] == '1' ? true : false
                    );
        }

        //取得商品主键等信息
        $result = $this->getIndexerList($condition,$order,$pagesize);
        if ($result !== false) {
            list($indexer_ids,$indexer_count) = $result;
            //如果全文搜索发生错误，后面会再执行数据库搜索
        } else {
            $indexer_ids = null;
            $indexer_count = 0;
        }

        return array($indexer_ids,$indexer_count);
    }
}
