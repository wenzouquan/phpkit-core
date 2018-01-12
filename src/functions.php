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


/**获取ip**/
function getIp($type = 0) {
	$type = $type ? 1 : 0;
	static $ip = NULL;
	if ($ip !== NULL) {
		return $ip[$type];
	}

	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$pos = array_search('unknown', $arr);
		if (false !== $pos) {
			unset($arr[$pos]);
		}

		$ip = trim($arr[0]);
	} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	// IP地址合法验证
	$long = ip2long($ip);
	$ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
	return $ip[$type];
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true) {
	$label = ($label === null) ? '' : rtrim($label) . ' ';
	if (!$strict) {
		if (ini_get('html_errors')) {
			$output = print_r($var, true);
			$output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		} else {
			$output = $label . print_r($var, true);
		}
	} else {
		ob_start();
		var_dump($var);
		$output = ob_get_clean();
		if (!extension_loaded('xdebug')) {
			$output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
			$output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
		}
	}
	if ($echo) {
		echo ($output);
		return null;
	} else {
		return $output;
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
			throw new \Exception("Permission denied in : $path " , 1);
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
	if (is_file($fileName) && $overwrite !=1) {
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
    if(!is_array($array)){
        return $array;
    }
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



/***
 *
 *
 *    //口语化时间
 *
 * */

function time_tran($the_time) {
	$now_time = time();
	$show_time = $the_time;
	$dur = $now_time - $show_time;
	switch ($dur) {
	case $dur < 0:
		return '1秒前';
		break;

	case $dur < 60:
		return $dur . '秒前';
		break;
	case $dur < 3600:
		return floor($dur / 60) . '分钟前';
		break;
	case strtotime(date("Y-m-d 00:00:00")) < $the_time && $the_time < strtotime(date("Y-m-d 23:59:00")):
		return "今天 " . date("H:i", $the_time);
		break;
	case $dur > 3600 * 24 && $dur < 3600 * 24 * 30: //30天前
		return floor($dur / (24 * 3600)) . '天前';
		break;
	case date("Y", $the_time) == date("Y", $now_time): //大于1天小于1年
		return date("m-d H:i", $the_time);
		break;
	default:
		return date("Y-m-d H:i", $the_time);
	}

}


/**
 * 验证邮箱格式
 * @param  [type]  $email
 * @param  boolean $test_mx
 * @return boolean          [description]
 */
function is_email($email, $test_mx = false) {
	if (eregi("^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{1,4})$", $email)) {
		if ($test_mx) {
			list($username, $domain) = split("@", $email);
			return getmxrr($domain, $mxrecords);
		} else {
			return true;
		}
	} else {
		return false;
	}

}




function cutstr($str, $length = 0, $append = true, $charset = 'utf8') {
	$str = trim($str);
	$strlength = strlen($str);
	$charset = strtolower($charset);
	if ($charset == 'utf8') {
		$l = 0;
		while ($i <= $strlength) {
			if (ord($str{
				$i}) < 0x80
			) {
				$l++;
				$i++;
			} else
			if (ord($str{
				$i}) < 0xe0
			) {
				$l++;
				$i += 2;
			} else
			if (ord($str{
				$i}) < 0xf0
			) {
				$l += 2;
				$i += 3;
			} else
			if (ord($str{
				$i}) < 0xf8
			) {
				$l += 1;
				$i += 4;
			} else
			if (ord($str{
				$i}) < 0xfc
			) {
				$l += 1;
				$i += 5;
			} else
			if (ord($str{
				$i}) < 0xfe
			) {
				$l += 1;
				$i += 6;
			}

			if ($l >= $length) {
				$newstr = substr($str, 0, $i);
				break;
			}
		}
		if ($l < $length) {
			return $str;
		}
	} elseif ($charset == 'gbk') {
		if ($length == 0 || $length >= $strlength) {
			return $str;
		}
		while ($i <= $strlength) {
			if (ord($str{
				$i}) > 0xa0
			) {
				$l += 2;
				$i += 2;
			} else {
				$l++;
				$i++;
			}

			if ($l >= $length) {
				$newstr = substr($str, 0, $i);
				break;
			}
		}
	}

	if ($append && $str != $newstr) {
		$newstr .= '...';
	}

	return $newstr;
}



/**
 * cutl
 * @param  array post_file
 * @param  string url
 * @return string
 */
function mycurl($url, $post_file, $file = 0, $write) {

	if ($file) {
		$cookie_dir = BOX_PATH . "/Runtime/cookie";
		$SCRIPT_ROOT = $cookie_dir . "/" . $file;

		if (!is_dir($cookie_dir)) {
			mk_dir($cookie_dir);
		}
		$_GET['SCRIPT_ROOT'] = $SCRIPT_ROOT;
		if (is_readable($SCRIPT_ROOT) == false) {
			$fp = fopen("$SCRIPT_ROOT", "a+");
			//$content=file_get_contents($cookie_dir."/360E19.tmp");
			//	file_put_contents("$SCRIPT_ROOT",$content);
			fclose($fp);
			$bool = tempnam($SCRIPT_ROOT, 'JSESSIONID');
			//	dump($bool);exit();
		}
		$cookie_file = $SCRIPT_ROOT;

	}

	$agent = 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0';

	$ch = curl_init(); /////初始化一个CURL对象
	curl_setopt($ch, CURLOPT_URL, $url);
	//curl_setopt($ch, CURLOPT_HTTPHEADER,$headerArr);  //构造IP
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	//$headers = array();
	$headers = "'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0"; ////定义content-type为plain

	//curl 'http://kjks.bjcz.gov.cn/cjcx/page/cjcx.pr.prcjcxdl_CJQuery.do' -H 'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0' --data 'KJ_KSCJXX%2FPNAME=%CE%C2%D7%F7%C8%A8&KJ_KSCJXX%2FCID=441424198908176578&KJ_KSPHONE%2FPHONENM=15502154827'
	//$header[] = "User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0";
	// $header=implode(";",$header);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置HTTP头
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); ///设置不输出在浏览器上
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); //50秒超时
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);

	/*curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
		    curl_setopt($ch, CURLOPT_PROXY, "58.220.2.133"); //代理服务器地址
		    curl_setopt($ch, CURLOPT_PROXYPORT, 80); //代理服务器端口
		    //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式
	*/

	/******提交********/
	if ($post_file) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_file); ////传递一个作为HTTP "POST"操作的所有数据的字符
	}

	if ($file) {
		if ($write) {
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //保存
		} else {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		}
	}

	//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	/***认证**/
	//curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true); ;
	// curl_setopt($ch,CURLOPT_CAINFO,ROOT_PATH.'/cacert.pem');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$content = curl_exec($ch);
	//dump($ch);
	if (curl_errno($ch)) {
//出错则显示错误信息
		return array('error' => 1, 'content' => curl_error($ch));
	}
	return $content;
}




/*************汉字的首写字母************/
function getfirstchar($s0) {
	$fchar = ord($s0{0});
	if ($fchar >= ord("A") and $fchar <= ord("z")) {
		return strtoupper($s0{0});
	}

	$s1 = iconv("UTF-8", "gb2312", $s0);
	$s2 = iconv("gb2312", "UTF-8", $s1);
	if ($s2 == $s0) {
		$s = $s1;
	} else {
		$s = $s0;
	}
	$asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
	if ($asc >= -20319 and $asc <= -20284) {
		return "A";
	}

	if ($asc >= -20283 and $asc <= -19776) {
		return "B";
	}

	if ($asc >= -19775 and $asc <= -19219) {
		return "C";
	}

	if ($asc >= -19218 and $asc <= -18711) {
		return "D";
	}

	if ($asc >= -18710 and $asc <= -18527) {
		return "E";
	}

	if ($asc >= -18526 and $asc <= -18240) {
		return "F";
	}

	if ($asc >= -18239 and $asc <= -17923) {
		return "G";
	}

	if ($asc >= -17922 and $asc <= -17418) {
		return "I";
	}

	if ($asc >= -17417 and $asc <= -16475) {
		return "J";
	}

	if ($asc >= -16474 and $asc <= -16213) {
		return "K";
	}

	if ($asc >= -16212 and $asc <= -15641) {
		return "L";
	}

	if ($asc >= -15640 and $asc <= -15166) {
		return "M";
	}

	if ($asc >= -15165 and $asc <= -14923) {
		return "N";
	}

	if ($asc >= -14922 and $asc <= -14915) {
		return "O";
	}

	if ($asc >= -14914 and $asc <= -14631) {
		return "P";
	}

	if ($asc >= -14630 and $asc <= -14150) {
		return "Q";
	}

	if ($asc >= -14149 and $asc <= -14091) {
		return "R";
	}

	if ($asc >= -14090 and $asc <= -13319) {
		return "S";
	}

	if ($asc >= -13318 and $asc <= -12839) {
		return "T";
	}

	if ($asc >= -12838 and $asc <= -12557) {
		return "W";
	}

	if ($asc >= -12556 and $asc <= -11848) {
		return "X";
	}

	if ($asc >= -11847 and $asc <= -11056) {
		return "Y";
	}

	if ($asc >= -11055 and $asc <= -10247) {
		return "Z";
	}

	return null;
}




/**
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lng1, $lat1, $lng2, $lat2) {
	$earthRadius = 6367000; //approximate radius of earth in meters

	/*
		      Convert these degrees to radians
		      to work with the formula
	*/
	$lat1 = ($lat1 * pi()) / 180;
	$lng1 = ($lng1 * pi()) / 180;

	$lat2 = ($lat2 * pi()) / 180;
	$lng2 = ($lng2 * pi()) / 180;
	/*
		      Using the
		      Haversine formula
		      http://en.wikipedia.org/wiki/Haversine_formula

		      calculate the distance
	*/

	$calcLongitude = $lng2 - $lng1;
	$calcLatitude = $lat2 - $lat1;
	$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
	$stepTwo = 2 * asin(min(1, sqrt($stepOne)));
	$calculatedDistance = $earthRadius * $stepTwo;
	return round($calculatedDistance);
}





/********url转超连接********/
function autolink($foo) {
	// Modified from:  http://www.szcpost.com
	$foo = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1" target="_blank" rel="nofollow">\\1</a>', $foo);
	if (strpos($foo, "http") === FALSE) {
		$foo = eregi_replace('(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="http://\\1" target="_blank" rel="nofollow" >\\1</a>', $foo);
	} else {
		$foo = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '\\1<a href="http://\\2" target="_blank" rel="nofollow" >\\2</a>', $foo);
	}
	return $foo;
}


/******是否手机号**/
function is_tel($mobile) {
	if (!is_numeric($mobile)) {
		return false;
	}
	return preg_match('#^^1[\d]{10}$#', $mobile) ? true : false;
}



/******下载*****/
function download($filename) {
	if (!is_file($filename)) {
		die('nothing');
	}
	ob_clean();
	$p = explode("/", $filename);
	$name = $p[count($p) - 1];
	//header 的作用是 新建一个被下载的test.xls
	header("Content-Type: application/vnd.ms-excel; charset=utf8");
	header("Content-Disposition: attachment; filename=" . $name);
	//这里是需要被输出的文件
	readfile($filename);
	unlink($filename); //删除文件
}






function downloadXls($filename) {
	if (!is_file($filename)) {
		die('nothing');
	}
	ob_clean();
	$p = explode("/", $filename);
	$name = $p[count($p) - 1];
	//header 的作用是 新建一个被下载的test.xls
	header("Content-Type: application/vnd.ms-excel; charset=utf8");
	header("Content-Disposition: attachment; filename=" . $name);
	//这里是需要被输出的文件
	readfile($filename);
	unlink($filename); //删除文件
}


