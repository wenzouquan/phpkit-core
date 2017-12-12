<?php
namespace phpkit\core;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
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
		//默认redis
		if (empty(self::$cache)) {
			// $frontCache = new \Phalcon\Cache\Frontend\Data(array(
			// 	"lifetime" => 0,
			// ));
			// $cacheDir = phpkitRoot . '/cache/data/';
			// \phpkit\helper\mk_dir($cacheDir);
			// self::$cache = new \Phalcon\Cache\Backend\File($frontCache, array(
			// 	"cacheDir" => $cacheDir,
			// ));
			self::$cache = new \phpkit\redis\Redis(array(
				"prefix" => 'phpkit-data-cache-',
				'host' => '127.0.0.1',
				'port' => 6379,
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
				$config = self::getDi()->getConfig();
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
		$application = $this->init($config);
		echo $application->handle()->getContent();
	}

//	public function setXdebugSession(){
//        if(isset($_GET['XDEBUG_SESSION_START'])){
//            $xdebugSession = $_GET['XDEBUG_SESSION_START'];
//            \apc_store("XDEBUG_SESSION_START",$xdebugSession);
//        }
//    }

	public function init($config = array()) {
		try {
			error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
            //$this->setXdebugSession();
			if (empty($config['date_default_timezone_set'])) {
				date_default_timezone_set('PRC'); //设置为北京时间
			} else {
				date_default_timezone_set($config['date_default_timezone_set']);
			}
			// Register an autoloader

			$loader = new \Phalcon\Loader();
			if (is_array($config['registerDirs'])) {
				$loader->registerDirs(
					$config['registerDirs']
				);
			}

			if (is_array($config['registerNamespaces'])) {
				$loader->registerNamespaces($config['registerNamespaces']);
			}

			$loader->register();

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


			// Setting up the view component
			if (empty($di['view'])) {
				$di['view'] = function () use ($config) {
					$view = new View();
					$view->setViewsDir($config["viewsDir"] );
                    $view->registerEngines([
                        '.phtml' => '\Phalcon\Mvc\View\Engine\Php',
                        '.volt' => function($view, $di) use ($config) {
                            $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
                            $volt->setOptions(['compiledPath'       => $config['cacheDir'] . 'view/',
                                'compiledExtension' => '.compiled',
                                'compileAlways'     => true
                            ]);
                            $compiler = $volt->getCompiler();
                            $compiler->addFilter('floor', 'floor');
                            $compiler->addFunction('range', 'range');
                            return $volt;
                        },
                    ]);
					return $view;
				};
			}
			// Setup a base URI so that all generated URIs include the "tutorial" folder

			if (empty($config['di']['url'])) {
				$BaseUri = $config['appBaseUri'] ? "/" . $config['appBaseUri'] . "/" : "/";
				define("tmpBaseUri", $BaseUri);
				$di['url'] = function () {
					$url = new Url();
					$url->setBaseUri(tmpBaseUri);
					return $url;
				};
			}
			//缓存配置
			if (!empty($config['di']['cache'])) {
				self::$cache = $di->getCache();
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
				require 'apc.php';
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

			//自定义dispatcher
			if (empty($config['di']['dispatcher'])) {
				$di['dispatcher'] = function () {
					//创建一个事件管理
					$eventsManager = new EventsManager();
					//附上一个侦听者
					$eventsManager->attach("dispatch:beforeDispatchLoop", function ($event, $dispatcher) {
						$target = array();
						$get = $_GET;
						unset($get['_url']);
						$source = $dispatcher->getParams();
						//用奇数参数作key，用偶数作值
						for ($i = 0; $i < count($source); $i += 2) {
							$target[$source[$i]] = $source[$i + 1];
						}
						$params = array_merge($get, $target);
                        if(is_array($_POST)){
                            $params = array_merge($params, $_POST);
                        }
						//重写参数
						$dispatcher->setParams($params);
					});
					$dispatcher = new MvcDispatcher();
					$dispatcher->setEventsManager($eventsManager);
					return $dispatcher;
				};
			}
			//配置文件
			if (empty($config['di']['config'])) {
				$di['config'] = function () {
					$config = new \phpkit\config\Config();
					return $config;
				};
			}

			self::$di = $di;
			//执行action
			//call_user_func_array(array($controller, $actionName . "Action"), $params);
			// Handle the request
			$application = new \Phalcon\Mvc\Application($di);
			return $application;

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
