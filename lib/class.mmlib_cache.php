<?php

class mmlib_databuffer {
	var $databuffer = array();
	var $enableCache = false;
	
	function init($enableCache) { 
		$this->enableCache = $enableCache;
		//$this->enableCache = false;
		//t3lib_div::debug($enableCache,'$enableCache');
	}

	function getFromBuffer($key) {
		if($this->enableCache == false || !isset($this->databuffer[$key])) return null;
		return $this->databuffer[$key];
	}
	
	function setBuffer($key,$value) {
		if($this->enableCache == false) return;
		$this->databuffer[$key] = $value;
	}
	
	function resetBuffer() {
		if($this->enableCache == false) return;
		$this->databuffer = array();
	}
}

class mmlib_cache extends mmlib_databuffer {
	var $retvalues = array();
	
	function getResult($functionName,$functioParams = '') {
		if($this->enableCache == false) return null;
		
		$md5 = $this->_getMD5($functionName,$functioParams);
		if($md5 == null) return null;
		
		if(isset($this->retvalues[$md5])) {
			//t3lib_div::debug('getResult ' . $md5,'getResult ' . $functionName);
			return $this->retvalues[$md5];
		}
		else return null;
	}
	
	function setResult($functionName,$functioParams,$result) {
		if($this->enableCache == false) return;
		
		$md5 = $this->_getMD5($functionName,$functioParams);
		if($md5 == null) return;
		
		//t3lib_div::debug('setResult ' . $md5,'setResult ' . $functionName);
		$this->retvalues[$md5] = $result;
	}
	
	function _getMD5($functionName,$functioParams = '') {
		if(is_array($functioParams)) $params = implode('',$functioParams);
		else $params = $functioParams;
		
		$params = trim($functionName) . $params;
		 
		if($params == '') return null;
		
		$md5 = t3lib_div::shortmd5($params);

		//t3lib_div::debug($md5,'$md5');
		return $md5;
	}
	
	
	function _getTempPath() {
		return PATH_site.'typo3temp/';
	}
	
}
?>