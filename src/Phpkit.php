<?php
namespace phpkit\core;
use \Phalcon\Db\Adapter\Pdo\Mysql as AdapterMsql;
use \Phalcon\DI\FactoryDefault;
use \Phalcon\Mvc\Url;
use \Phalcon\Mvc\View;

class Phpkit {
	static $cache;
	static $di;
	static $BaseModel;
	public function __construct($config = null) {
		//phpkit 根目录
		if (!defined("phpkitRoot")) {
			define("phpkitRoot", dirname(dirname(dirname(dirname(dirname(__FILE__))))));
		}

	}

	//缓存
	public static function cache() {
		if (empty(self::$cache)) {
			$frontCache = new \Phalcon\Cache\Frontend\Data(array(
				"lifetime" => 0,
			));
			$cacheDir = phpkitRoot . '/cache/data/';
			\phpkit\helper\mk_dir($cacheDir);
			self::$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
				"cacheDir" => $cacheDir,
			));
			self::$di['cache'] = self::$cache;
		}
		return self::$cache;
	}
//设置 di
	public static function getDi() {
		if (self::$di && get_class(self::$di) == 'Phalcon\Di\FactoryDefault') {
			return self::$di;
		}
		if (empty($GLOBALS['di'])) {
			$GLOBALS['di'] = new FactoryDefault();
		}
		self::$di = $GLOBALS['di'];
		return self::$di;
	}

	//设置phpkitDb
	public function setDb() {
		$di = self::getDi();
		if (empty($di['phpkitDb'])) {
			$di['phpkitDb'] = function () {
				$config = new \phpkit\config\Config();
				$DbConfig = $config->get("phpkitDb", 'setIfNull');
				return new AdapterMsql($DbConfig);
			};
		}
	}
	//设置显示
	public static function getViews($viewDir = "") {
		$view = new \Phalcon\Mvc\View\Simple();
		$view->setViewsDir($viewDir);
		return $view;
	}

	public function run($config = array()) {
		try {
			error_reporting(E_ALL ^ E_NOTICE);
			if (empty($config['date_default_timezone_set'])) {
				date_default_timezone_set('PRC'); //设置为北京时间
			} else {
				date_default_timezone_set($config['date_default_timezone_set']);
			}
			// Register an autoloader
			$loader = new \Phalcon\Loader();
			$loader->registerDirs(
				array(
					$config["appDir"] . '/app/controllers/',
					$config["appDir"] . '/app/models/',
				)
			)->register();

			if (empty($config['di']) || is_array($config['di']) || get_class($config['di']) != 'Phalcon\Di\FactoryDefault') {
				$di = new \Phalcon\DI\FactoryDefault();
			} else {
				$di = $config['di'];
			}
			//设置di
			if (is_array($config['di'])) {
				foreach ($config['di'] as $key => $value) {
					$di[$key] = $value;
				}
			}

			// Create a DI
			// Set the database service
			if (empty($di['db'])) {
				$di['db'] = function () {
					$config = new \phpkit\config\Config();
					$DbConfig = $config->get("phpkitDb", 'setIfNull');
					return new AdapterMsql($DbConfig);
				};
			}

			// Setting up the view component
			if (empty($di['view'])) {
				$ViewsDir = $config["appDir"] . "/app/views/";
				define("tmpViewsDir", $ViewsDir);
				$di['view'] = function () {
					$view = new View();
					$view->setViewsDir(tmpViewsDir);
					return $view;
				};
			}

			// Setup a base URI so that all generated URIs include the "tutorial" folder

			if (empty($config['di']['url'])) {
				$BaseUri = "/" . $config['appBaseUri'] . "/";
				define("tmpBaseUri", $BaseUri);
				$di['url'] = function () {
					$url = new Url();
					$url->setBaseUri(tmpBaseUri);
					return $url;
				};
			}
			//缓存配置
			if (!empty($config['di']['cache'])) {
				self::$cache = $config['di']['cache'];
			}

			//设置数据库modelsMetadata 缓存
			//var_dump($di['modelsMetadata']);
			// if (empty($config['di']['modelsMetadata'])) {
			// 	$di['modelsMetadata'] = function () {
			// 		$metaData = new \Phalcon\Mvc\Model\Metadata\Files([
			// 			"metaDataDir" => phpkitRoot . '/cache/metadata/',
			// 		]);
			// 		return $metaData;
			// 	};
			// }

			if (empty($config['di']['modelsMetadata'])) {
				$di['modelsMetadata'] = function () {
					$metaData = new \Phalcon\Mvc\Model\MetaData\Apc(array(
						"lifetime" => 0,
						"prefix" => "phpkit-modelsMetadata",
					));

					return $metaData;
				};
			}

			if (empty($config['di']['phpkitDb'])) {
				$di['phpkitDb'] = function () {
					$config = new \phpkit\config\Config();
					$DbConfig = $config->get("phpkitDb", 'setIfNull');
					return new AdapterMsql($DbConfig);
				};
			}

			if (empty($config['di']['session'])) {
				$di['session'] = function () {
					$session = new \Phalcon\Session\Adapter\Files(
						array(
							'uniqueId' => 'phpkit',
						)
					);
					$session->start();
					return $session;
				};
			}
			//配置文件
			$di['phpkitConfig'] = function () {
				$config = new \phpkit\config\Config();
				return $config;
			};

			self::$di = $di;
			// Handle the request
			$application = new \Phalcon\Mvc\Application($di);
			echo $application->handle()->getContent();
		} catch (Exception $e) {
			echo "Exception: ", $e->getMessage();
		}
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