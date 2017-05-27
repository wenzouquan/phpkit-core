<?php
namespace phpkit\core;
class Phpkit {
	static $cache;
	static $BaseModel;
	public function __construct($config = null) {

	}

	//缓存
	public static function cache() {
		if (empty(self::$cache)) {
			$frontCache = new \Phalcon\Cache\Frontend\Data(array(
				"lifetime" => 172800000,
			));

			self::$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
				"cacheDir" => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/',
			));
		}
		return self::$cache;
	}

	//直接查数据库
	public static function BaseModel($tableName = "") {
		//if (empty(self::$BaseModel)) {
		$model = new \phpkit\core\BaseModel($tableName);
		//}
		return $model;
		//$model =setSource

	}

}