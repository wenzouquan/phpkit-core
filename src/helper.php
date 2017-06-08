<?php
if (!function_exists('apc_add')) {
	function apc_add($key = "", $value = "", $exp = 0) {
		return apcu_add($key, $value, $exp);
	}
	function apc_store($key = "", $value = "", $exp = 0) {
		return apcu_store($key, $value, $exp);
	}
	function apc_fetch($key = "", $success) {
		return apcu_fetch($key, $success);
	}
	function apc_delete($key = "") {
		return apcu_delete($key);
	}
	function apc_delete($key = "") {
		return apcu_delete($key);
	}
	function apc_cache_info() {
		return apcu_cache_info();
	}

}