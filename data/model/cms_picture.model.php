<?php
/**
 * cms文章模型
 *
 * 
 *
 *
 * by 33hao www.33hao.com 开发修正
 */
defined('InShopNC') or exit('Access Invalid!');
class cms_pictureModel extends Model{

    public function __construct(){
        parent::__construct('cms_picture');
    }

	/**
	 * 读取列表 
	 * @param array $condition
	 *
	 */
	public function getList($condition, $page=null, $order='', $field='*', $limit=''){
        $result = $this->table('cms_picture')->field($field)->where($condition)->page($page)->order($order)->limit($limit)->select();
        return $result;
	}
	
    /**
     * 画报数量
     * @param array $condition
     * @return int
     */
    public function getCmsPictureCount($condition) {
        return $this->where($condition)->count();
    }

    /**
     * 根据tag编号查询
     */
    public function getListByTagID($condition, $page=null, $order='', $field='*', $limit=''){
        $condition['relation_type'] = 2;
        $on = 'cms_picture.picture_id= cms_tag_relation.relation_object_id';
        $result = $this->table('cms_picture,cms_tag_relation')->field($field)->join('left')->on($on)->where($condition)->page($page)->order($order)->limit($limit)->select();
        $this->cls();
        return $result;
    }

   /**
	 * 读取单条记录
	 * @param array $condition
	 *
	 */
    public function getOne($condition,$order=''){
        $result = $this->table('cms_picture')->where($condition)->order($order)->find();
        return $result;
    }

	/*
	 *  判断是否存在 
	 *  @param array $condition
     *
	 */
	public function isExist($condition) {
        $result = $this->table('cms_picture')->getOne($condition);
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
        return $this->table('cms_picture')->insert($param);	
    }
	
	/*
	 * 更新
	 * @param array $update
	 * @param array $condition
	 * @return bool
	 */
    public function modify($update, $condition){
        return $this->table('cms_picture')->where($condition)->update($update);
    }
	
	/*
	 * 删除
	 * @param array $condition
	 * @return bool
	 */
    public function drop($condition){
        $this->drop_picture_image($condition);
        return $this->table('cms_picture')->where($condition)->delete();
    }
	
    /**
     * 删除图片
     */
    private function drop_picture_image($condition) {
        $model_picture_image = Model('cms_picture_image');
        $picture_list = self::getList($condition);
        $picture_ids = '';
        if(!empty($picture_list) && is_array($picture_list)) {

            //删除画报图片文件
            foreach($picture_list as $picture) {
                $picture_ids .= $picture['picture_id'].',';
                $picture_image_list = $model_picture_image->getList(array('image_picture_id'=>$picture['picture_id']), NULL);
                if(!empty($picture_image_list)) {
                    foreach ($picture_image_list as $value) {
                        list($base_name, $ext) = explode('.',$value['image_name']);
                        $image = BASE_UPLOAD_PATH.DS.ATTACH_CMS.DS.'article'.DS.$picture['picture_attachment_path'].DS.$value['image_name'];
                        $image_list = BASE_UPLOAD_PATH.DS.ATTACH_CMS.DS.'article'.DS.$picture['picture_attachment_path'].DS.$base_name.'_list.'.$ext;
                        $image_max = BASE_UPLOAD_PATH.DS.ATTACH_CMS.DS.'article'.DS.$picture['picture_attachment_path'].DS.$base_name.'_max.'.$ext;
                        if(is_file($image)) {
                            unlink($image);
                        }
                        if(is_file($image_list)) {
                            unlink($image_list);
                        }
                        if(is_file($image_max)) {
                            unlink($image_max);
                        }
                    }
                }

            }

            //删除画报图片记录
            $model_picture_image->drop(array('image_picture_id'=>array('in', trim($picture_ids, ','))));

        }

    }
}

