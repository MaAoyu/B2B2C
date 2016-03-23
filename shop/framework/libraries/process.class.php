<?php
defined('InShopNC') or exit('Access Invalid!');

/**
 * 连续操作验证
 *
 */

class processClass{

	const MAX_LOGIN = 3;	//密码连续输入多少次被暂时锁定
	const MAX_COMMIT = 2;	//连续评论多少次被暂时锁定
	const MAX_REG = 2;		//连续注册多少个账号被暂时锁定
	const MAX_FORGET = 3;	//找回密码输入多少次被暂时锁定

	/**
	 * 是否启用验证
	 *
	 * @var unknown_type
	 */
	private static $ifopen = true;

	/**
	 * 锁表对象
	 *
	 * @var unknown_type
	 */
	private static $lock;

	/**
	 * 记录ID
	 *
	 * @var unknown_type
	 */
	private static $processid = array();

	/**
	 * 锁ID
	 *
	 * @var unknown_type
	 */
	private static $lockid = array();
	
	/**
	 * 初始化，未启用内存保存时默认使用lock表存储
	 *
	 * @param unknown_type $type
	 */
	private static function init($type){
		if (C('cache.type') != 'file'){
			self::$lock = Cache::getInstance(C('cache.type'));
		}else{
			self::$lock = new lock();
		}
		if (!isset(self::$processid[$type])){
			$ip = sprintf('%u',ip2long(getIp()));
			self::$processid[$type] = str_pad($ip,10,'0').self::parsekey($type);;
			self::$lockid[$type] = str_pad($ip,11,'0').self::parsekey($type);;
		}
	}

	/**
	 * 判断是否已锁
	 *
	 * @param unknown_type $type
	 * @return unknown
	 */
	public static function islock($type = null){
		if (!self::$ifopen) return;
		self::init($type);
		return self::$lock->get(self::$lockid[$type]);
	}
	
	/**
	 * 添加锁
	 *
	 * @param unknown_type $type
	 * @param unknown_type $ttl
	 */
	private static function addlock($type = null,$ttl = 600){
		if (!self::$ifopen) return;
		self::init($type);
		self::$lock->set(self::$lockid[$type],1,'',$ttl);
	}
	
	/**
	 * 删除锁
	 *
	 * @param unknown_type $type
	 */
	public static function dellock($type = null){
		if (!self::$ifopen) return;
		self::$lock->rm(self::$lockid[$type]);
	}
	
	/**
	 * 添加记录
	 *
	 * @param unknown_type $type
	 * @param unknown_type $ttl
	 */
	public static function addprocess($type = null,$ttl = 600){
		if (!self::$ifopen) return;
		self::init($type);
		$tims = self::parsetimes($type);
		$t = self::$lock->get(self::$processid[$type]);
		if ($t >= $tims-1) {
			self::addlock($type,$ttl);
			self::$lock->rm(self::$processid[$type]);
		}else{
			if (empty($t)) $t = 0;
			self::$lock->set(self::$processid[$type],$t+1,'',$ttl);
		}
	}
	
	/**
	 * 删除记录
	 *
	 * @param unknown_type $type
	 */
	public static function delprocess($type = null){
		if (!self::$ifopen) return;
		self::$lock->rm(self::$processid[$type]);
	}

	/**
	 * 清空
	 *
	 */
	public static function clear($type = ''){
		if (!self::$ifopen) return;
		if (empty($type)) return ;
		self::dellock($type);
		self::delprocess($type);		
	}
	public static function parsekey($type){
		return str_replace(array('login','commit','reg','forget'),array(1,2,3,4),$type);
	}

	/**
	 * 设置最多尝试次数
	 *
	 * @param unknown_type $type
	 * @return unknown
	 */
	public static function parsetimes($type){
		return str_replace(array('login', 'commit', 'reg', 'forget'),array(self::MAX_LOGIN, self::MAX_COMMIT, self::MAX_REG, self::MAX_FORGET),$type);
	}
}

/**
 * lock表 操作
 *
 * @package    library
 * @copyright  Copyright (c) 2007-2013 ShopNC Inc. (http://www.shopnc.net)
 * @license    http://www.shopnc.net
 * @link       http://www.shopnc.net
 * @author	   ShopNC Team
 * @since      File available since Release v1.1
 */
defined('InShopNC') or exit('Access Invalid!');

class lock {
	private $model;
	public function __construct(){
		$this->model = Model('lock');
	}

	/**
	 * 设置值
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @param string $type
	 * @param int $ttl
	 * @return bool
	 */
	public function set($key, $value, $type='', $ttl = SESSION_EXPIRE){
		$info = $this->model->where(array('pid'=>$key))->find();
		if ($info){
			$this->model->where(array('pid'=>$key))->update(array('pvalue'=>$value,'expiretime'=>TIMESTAMP+$ttl));
		}else{
			$this->model->insert(array('pid'=>$key,'pvalue'=>$value,'expiretime'=>TIMESTAMP+$ttl));
		}
	}

	/**
	 * 取得值
	 *
	 * @param mixed $key
	 * @param mixed $type
	 * @return bool
	 */
	public function get($key, $type=''){
		$info = $this->model->where(array('pid'=>$key))->find();
		if ($info && ($info['expiretime'] < TIMESTAMP)){
			$this->rm($key);return null;
		}else{
			return $info['pvalue'];
		}
	}

	/**
	 * 删除值
	 *
	 * @param mixed $key
	 * @param mixed $type
	 * @return bool
	 */
	public function rm($key, $type=''){
		return $this->model->where(array('pid'=>$key))->delete();
	}
}
?>