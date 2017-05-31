<?php
namespace phpkit\core;
use Phalcon\Mvc\Controller;
use phpkit\backend\View as backendView;

class BaseController extends Controller {
	public function initialize() {
		//parent::initialize();
		$this->ControllerName = $this->convertUnderline($this->dispatcher->getControllerName());
		$this->ActionName = $this->dispatcher->getActionName();
	}

	protected function jump($msg, $url) {
		header("Content-type: text/html; charset=utf-8");
		$new_url = $url ? $url : $_SERVER['HTTP_REFERER'];
		if ($msg) {
			$msg = " alert('{$msg}');";
		}
		echo '<script language="javascript" type="text/javascript">' . $msg . ' window.location.href="' . $new_url . '"; </script>';

		exit();
	}

	public function convertUnderline($str, $ucfirst = true) {
		$str = explode('-', $str);
		foreach ($str as $key => $val) {
			$str[$key] = ucfirst($val);
		}

		if (!$ucfirst) {
			$str[0] = strtolower($str[0]);
		}
		return implode('', $str);
	}

	/***********递归生成目录**********/
	public function mk_dir($path) {
		//第1种情况，该目录已经存在
		if (is_dir($path)) {
			return;
		}
		//第2种情况，父目录存在，本身不存在
		if (is_dir(dirname($path))) {
			mkdir($path, 0777);
		}
		//第3种情况，父目录不存在
		if (!is_dir(dirname($path))) {
			$this->mk_dir(dirname($path)); //创建父目录
			mkdir($path, 0777);
		}
	}

	public function fetch($controllerName = "", $actionName = "") {
		$controllerName = $controllerName ? $controllerName : $this->ControllerName;
		$actionName = $actionName ? $actionName : $this->ActionName;
		$content = $this->view->getRender($controllerName, $actionName);
		return $content;
	}

	public function display($controllerName = "", $actionName = "") {
		echo $this->fetch($controllerName, $actionName);
	}

	public function adminDisplay($controllerName = "", $actionName = "") {
		//echo 'wen';
		$content = $this->fetch($controllerName, $actionName);
		$backendView = new backendView();
		$backendView->display($content);
	}

}