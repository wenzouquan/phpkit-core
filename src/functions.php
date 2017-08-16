<?php
namespace phpkit\helper;
function convertUnderline($str, $ucfirst = true, $split = "-") {
	$str = explode($split, $str);
	foreach ($str as $key => $val) {
		$str[$key] = ucfirst($val);
	}

	if (!$ucfirst) {
		$str[0] = strtolower($str[0]);
	}
	$return = implode('', $str);
	if (strpos($return, "_")) {
		return convertUnderline($return, true, "_");
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
function saveFile($fileName, $text, $overwrite = 1) {
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

/*******数组转字符***/
function arrayeval($array, $level = 0) {

	$space = '';

	for ($i = 0; $i <= $level; $i++) {

		$space .= "\t";

	}

	$evaluate = "Array\n$space(\n";

	$comma = $space;

	foreach ($array as $key => $val) {

		$key = is_string($key) ? '\'' . addcslashes($key, '\'\\') . '\'' : $key;

		$val = !is_array($val) && (!preg_match("/^\-?\d+$/", $val) || strlen($val) > 12) ? '\'' . addcslashes($val, '\'\\') . '\'' : $val;

		if (is_array($val)) {

			$evaluate .= "$comma$key => " . arrayeval($val, $level + 1);

		} else {

			$evaluate .= "$comma$key => $val";

		}

		$comma = ",\n$space";

	}

	$evaluate .= "\n$space)";

	return $evaluate;
}
//删除文件
function deldir($dir) {
	//先删除目录下的文件：
	$dh = opendir($dir);

	while ($file = readdir($dh)) {

		if ($file != "." && $file != "..") {

			$fullpath = $dir . "/" . $file;

			if (!is_dir($fullpath)) {

				unlink($fullpath);

			} else {

				deldir($fullpath);

			}

		}

	}

	closedir($dh);

	//删除当前文件夹：

	if (rmdir($dir)) {

		return true;

	} else {

		return false;

	}

}