<?php

//require(t3lib_extMgm::extPath('mm_bccmsbase'). "xajax/xajax_core/xajax.inc.php");

class mmlib_xajaxwrapper {
	var $xajax = null;
	
	function init($debug = false,$requestURI = '') {
		$this->xajax = new xajax($requestURI);
		if($debug) $this->xajax->setFlag("debug", true);
	}
	
	function registerFunction($function) {
		if($this->xajax == null) $this->init();
		
		$this->xajax->registerFunction($function);
	}
	
	function processRequest() {
		if($this->xajax == null) die("Before 'processRequest' you must register a function...");
		
		$this->xajax->processRequest();
		
		ob_start();
		$this->xajax->printJavascript("/typo3conf/ext/mm_bccmsbase/xajax/");
		$GLOBALS['TSFE']->additionalHeaderData[] = ob_get_contents();
		ob_end_clean();
		
		
	}
}
?>