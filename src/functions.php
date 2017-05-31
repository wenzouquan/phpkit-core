<?php
namespace phpkit\helper;
function convertUnderline($str, $ucfirst = true) {
	$str = explode("-", $str);
	foreach ($str as $key => $val) {
		$str[$key] = ucfirst($val);
	}

	if (!$ucfirst) {
		$str[0] = strtolower($str[0]);
	}
	$return = implode('', $str);
	if (strpos($return, "_")) {
		return convertUnderline($return, 0);
	}
	return $return;
}

/***********递归生成目录**********/
function mk_dir($path) {
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
		mk_dir(dirname($path)); //创建父目录
		mkdir($path, 0777);
	}
}

/***********递归生成目录**********/
	 function mk_dir($path) {
		//第1种情况，该目录已经存在
		if (is_dir($path)) {
			return;
		}
		//第2种情况，父目录存在，本身不存在
		if (is_dir(dirname($path))) {
			if (!is_writable(dirname($path))) {
				throw new \Exception("Permission denied in" . dirname($path), 1);
			}
			mkdir($path, 0777);
		}
		//第3种情况，父目录不存在
		if (!is_dir(dirname($path))) {
			mk_dir(dirname($path)); //创建父目录
			mkdir($path, 0777);
		}
	}
	

//生成文件
	/**
	 * 7      * 保存文件
	 * 8      *
	 * 9      * @param string $fileName 文件名（含相对路径）
	 * 10      * @param string $text 文件内容
	 * 11      * @return boolean
	 * 12      */
	function saveFile($fileName, $text, $overwrite = 0) {
		if (!$fileName || !$text) {
			return false;
		}
		if (!is_writable(dirname($fileName))) {
			throw new \Exception("Permission denied in" . dirname($fileName), 1);
		}
		if (is_file($fileName) && $overwrite === 0) {
			throw new \Exception($fileName . " exist", 1);

		}
		if ($fp = fopen($fileName, "w")) {
			if (@fwrite($fp, $text)) {
				fclose($fp);
				return true;
			} else {
				fclose($fp);
				return false;
			}
		}
		return false;
	}