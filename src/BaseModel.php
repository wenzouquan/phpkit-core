<?php
namespace phpkit\core;
use phpkit\core\Phpkit as Phpkit;

$Phpkit = new Phpkit();
$Phpkit->setDb();
//设置modelsMetadata缓存
// if (empty($GLOBALS['di']['modelsMetadata'])) {
// 	// $GLOBALS['di']['modelsMetadata'] = function () {
// 	// 	// Create a meta-data manager with APC
// 	// 	// $metaData = new \Phalcon\Mvc\Model\MetaData\Apc(array(
// 	// 	// 	"lifetime" => 1,
// 	// 	// 	"prefix" => "my-prefix",
// 	// 	// ));
// 	// 	$metaData = new \Phalcon\Mvc\Model\Metadata\Files(
// 	// 		[
// 	// 			"metaDataDir" => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/',
// 	// 		]
// 	// 	);
// 	// 	return $metaData;
// 	// };
// }

class BaseModel extends \Phalcon\Mvc\Model {
	protected $Pk;
	protected $TableName;
	public $findOptions = array();
	public $error;
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

	// public function columnMap() {
	// 	$metaData = $this->getModelsMetaData();
	// 	$attributes = $metaData->getAttributes($this);
	// 	$PrimaryKeys = $metaData->getPrimaryKeyAttributes($this);
	// 	$this->Pk = $PrimaryKeys[0];
	// 	$data = array();
	// 	foreach ($attributes as $key => $value) {
	// 		$data[$value] = $this->convertUnderline2($value);
	// 	}
	// 	return $data;
	// }

	public function getTableName() {
		return $this->TableName = $this->getSource();
	}

	//设置查询条件
	public function where($condition) {
		$this->findOptions['conditions'] = $condition;
		return $this;
	}
	//设置查询条件
	public function bind($conditionValue) {
		$this->findOptions['bind'] = $conditionValue;
		return $this;
	}

	//加载一条数据, 默认会缓存数据
	public function load($op = array()) {
		$tableName = $this->getTableName();
		$pk = $this->getPk();
		$config = new \phpkit\config\Config();
		if (is_array($op) || empty($op)) {
			$op = array_merge($this->findOptions, $op);
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

	//删一条缓存
	public function delCacheByPk() {
		$pk = $this->getPk();
		$tableName = $this->getTableName();
		$cacheKeyPk = $tableName . "_" . $this->$pk;
		if (Phpkit::cache()->exists($cacheKeyPk)) {
			Phpkit::cache()->delete($cacheKeyPk); //缓存结果
		}
	}

	public function afterUpdate() {

	}

	public function afterCreate() {

		//echo 'afterCreate';
	}
	//更新 添加之后清缓存
	public function afterSave() {

		$this->findOptions = array(); //清空查询
		$this->delCacheByPk();
	}
	//删除之后
	public function afterDelete() {
		///var_dump($this->Id);
		$this->findOptions = array(); //清空查询
		$this->delCacheByPk();
	}

	public function order($orderBy = "") {
		if (!empty($orderBy)) {
			$this->findOptions['order'] = $orderBy;
		}
		return $this;

	}
	public function limit($limit = array()) {
		if (!empty($limit)) {
			if (is_string($limit)) {
				$limits = explode(",", $limit);
				$arr = array('number' => intval($limits[0]) ? intval($limits[0]) : 10, 'offset' => intval($limits[1]));
			} else {
				$arr = $limit;
			}
			$this->findOptions['limit'] = $arr;
		}
		return $this;
	}

	//加载列表
	public function get($op = array()) {
		$res = array();
		if (is_array($op)) {
			$op = array_merge($this->findOptions, $op);
			ksort($op);
		}
		$res['recordsFiltered'] = $this->count(array('conditions' => $op['conditions']));
		$res['recordsTotal'] = $this->count();
		// if (empty($op)) {
		// 	throw new \Exception("查询条件不能为空", 1);
		// }
		//var_dump($res);
		$res['list'] = $this->find($op);
		$this->findOptions = array(); //清空查询
		return $res;
	}
	//删除
	public function deleteByFind($op = array()) {
		if (!is_array($op) && !empty($op)) {
			$res = $this->load($op);
			$lists = $res ? array($res) : array();

		}
		if (is_array($op)) {
			$res = $this->get($op);
			$lists = $res['list'] ? $res['list'] : array();
		}
		$this->deleteLists = $lists;
		$flag = 1;
		if (!empty($lists)) {
			foreach ($lists as $key => $list) {
				if ($list->delete() == false) {
					foreach ($list->getMessages() as $message) {
						$this->error[] = $message;
					}
					$flag = $flag * 0;
				} else {
					$flag = $flag * 1;
				}
			}
		} else {
			$this->error[] = "没有查询到删除数据";
		}

		$this->findOptions = array(); //清空查询
		return $flag;
	}

}