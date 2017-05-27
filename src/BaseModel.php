<?php
namespace phpkit\core;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use phpkit\core\Phpkit as Phpkit;

if (!(isset($GLOBALS['di']) && get_class($GLOBALS['di']) == 'Phalcon\Di\FactoryDefault')) {
	$GLOBALS['di'] = new \Phalcon\DI\FactoryDefault();
}
//设置phpkitDb ， 数据连接
if (empty($GLOBALS['di']['phpkitDb'])) {
	$GLOBALS['di']['phpkitDb'] = function () {
		$config = new \phpkit\config\Config();
		$DbConfig = $config->get("phpkitDb", 'setIfNull');
		return new DbAdapter($DbConfig);
	};
}

//设置modelsMetadata缓存
if (empty($GLOBALS['di']['modelsMetadata'])) {
	// $GLOBALS['di']['modelsMetadata'] = function () {
	// 	// Create a meta-data manager with APC
	// 	// $metaData = new \Phalcon\Mvc\Model\MetaData\Apc(array(
	// 	// 	"lifetime" => 1,
	// 	// 	"prefix" => "my-prefix",
	// 	// ));
	// 	$metaData = new \Phalcon\Mvc\Model\Metadata\Files(
	// 		[
	// 			"metaDataDir" => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/',
	// 		]
	// 	);
	// 	return $metaData;
	// };
}

class BaseModel extends \Phalcon\Mvc\Model {
	protected $Pk;
	protected $TableName;
	public function onConstruct($name = "") {
		if ($name) {
			$this->setSource($name);
		}
	}
	public function initialize() {
		$this->setConnectionService('phpkitDb');
	}

	function convertUnderline2($str, $ucfirst = true) {
		$str = explode('_', $str);
		foreach ($str as $key => $val) {
			$str[$key] = ucfirst($val);
		}

		if (!$ucfirst) {
			$str[0] = strtolower($str[0]);
		}
		return implode('', $str);
	}

	public function getPk() {
		$metaData = $this->getModelsMetaData();
		$PrimaryKeys = $metaData->getPrimaryKeyAttributes($this);
		$this->Pk = $this->convertUnderline2($PrimaryKeys[0]);
		return $this->Pk;
	}

	public function columnMap() {
		$metaData = $this->getModelsMetaData();
		$attributes = $metaData->getAttributes($this);
		$PrimaryKeys = $metaData->getPrimaryKeyAttributes($this);
		$this->Pk = $PrimaryKeys[0];
		$data = array();
		foreach ($attributes as $key => $value) {
			$data[$value] = $this->convertUnderline2($value);
		}
		return $data;
	}

	public function getTableName() {
		return $this->TableName = $this->getSource();
	}

	//加载一条数据, 默认会缓存数据
	public function load($op = array()) {
		$tableName = $this->getTableName();
		$pk = $this->getPk();
		$config = new \phpkit\config\Config();
		if (is_array($op)) {
			ksort($op);
		}
		$res = null;
		$cacheKey = $tableName . "_" . md5(json_encode($op)); //查询缓存
		$cacheKeyPk = Phpkit::cache()->get($cacheKey); //所的缓存用主键来存
		if ($cacheKeyPk) {
			$res = Phpkit::cache()->get($cacheKeyPk); //通过主键来取缓存
		}
		if ($res === null) {
			$res = $this->findFirst($op);
			//查询到的结果
			if ($res) {
				$cacheKeyPk = $tableName . "_" . $res->$pk;
				Phpkit::cache()->save($cacheKeyPk, $res); //缓存结果
				Phpkit::cache()->save($cacheKey, $cacheKeyPk); //缓存查询条件
			}
		}
		return $res;
	}

	public function afterUpdate() {
		echo 'afterUpdate';
	}

	public function afterCreate() {
		echo 'afterCreate';
	}

	public function afterSave() {
		echo 'afterSave';
	}

	//加载列表
	public function get($op) {

	}
}