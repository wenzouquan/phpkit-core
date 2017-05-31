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