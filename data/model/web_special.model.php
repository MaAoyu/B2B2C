<?php
/**
 * 商城专题模型
 *
 * @copyright  QQ355716601
 * @link       http://33haocom.taobao.com
 */
defined('InShopNC') or exit('Access Invalid!');
class web_specialModel extends Model{

    public function __construct(){
        parent::__construct('web_special');
    }

	/**
	 * 读取列表 
	 * @param array $condition
	 *
	 */
	public function getList($condition, $page=null, $order='', $field='*', $limit=''){
        $result = $this->field($field)->where($condition)->page($page)->order($order)->limit($limit)->select();
        return $result;
	}

    /**
	 * 读取单条记录
	 * @param array $condition
	 *
	 */
    public function getOne($condition,$order=''){
        $result = $this->where($condition)->order($order)->find();
        return $result;
    }

	/*
	 *  判断是否存在 
	 *  @param array $condition
     *
	 */
	public function isExist($condition) {
        $result = $this->getOne($condition);
        if(empty($result)) {
            return FALSE;
        }
        else {
            return TRUE;
        }
	}

	/*
	 * 增加 
	 * @param array $param
	 * @return bool
	 */
    public function save($param){
        return $this->insert($param);	
    }
	
	/*
	 * 更新
	 * @param array $update
	 * @param array $condition
	 * @return bool
	 */
    public function modify($update, $condition){
        return $this->where($condition)->update($update);
    }
	
	/*
	 * 删除
	 * @param array $condition
	 * @return bool
	 */
    public function drop($condition){
        return $this->where($condition)->delete();
    }
	
}

