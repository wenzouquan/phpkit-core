<?php
if (extension_loaded('apcu') && !function_exists('apc_add')) {
	// if (!extension_loaded('apcu')) {
	// 	throw new \Exception("not  suppert apc", 1);
	// }
	function apc_add($key = "", $value = "", $exp = 0) {
		return apcu_add($key, $value, $exp);
	}
	function apc_store($key = "", $value = "", $exp = 0) {
		return apcu_store($key, $value, $exp);
	}
	function apc_fetch($key = "", $success = "") {
		return apcu_fetch($key, $success);
	}
	function apc_delete($key = "") {
		return apcu_delete($key);
	}

	function apc_cache_info() {
		return apcu_cache_info();
	}

}else if(!extension_loaded("apc") && !function_exists('apc_add')){
	$GLOBALS['apcData'] = array();
	function apc_add($key = "", $value = "", $exp = 0) {
		$GLOBALS['apcData'][$key]=array(
			'value'=>$value,
			'exp'=>time()+$exp
		);
		return $GLOBALS['apcData'][$key];
	}
	function apc_store($key = "", $value = "", $exp = 0) {
		$GLOBALS['apcData'][$key]=array(
			'value'=>$value,
			'exp'=>time()+$exp
		);
		return $GLOBALS['apcData'][$key];
	}
	function apc_fetch($key = "", $success = "") {
		 $data= $GLOBALS['apcData'][$key];
		 if($data['exp'] && $data['exp']>time()){
		 	return $data['value'];
		 }else{
		 	$GLOBALS['apcData'][$key]=array();
		 	return array();
		 }
	}
	
	function apc_delete($key = "") {
		$GLOBALS['apcData'][$key]=array();
		return true;
	}

	function apc_cache_info() {
		return $GLOBALS['apcData'];
	}
}