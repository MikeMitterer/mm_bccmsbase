<?php
/*
 * Created on 12. Sep 06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
/** 
 * This class displays a folders and subfolders.
 * It also helps you the click throu the folders
 *
 * @author	Mike Mitterer <mike.mitterer@bitcon.at>
 */
 
 class mmlib_folderui {
	var $folderBase; 
	var $folderCurrent;
	var $aValidFolders;
	var $parent;
	var	$renderObject;
	var $renderFunction;
	
 	function mmlib_folderui($folderBase = null) {
		$this->folderBase 			= 'fileadmin/';
		$this->aValidFolders		= array();
		$this->parent				= null;
		
		$this->renderObject			= null;
		$this->renderFunction		= null;
 	}
 	
 	/**
	 * Is called after the t3lib_div::makeInstance('mmlib_folderui') function.
	 * It does all the nessecary initialisation.
	 *   
	 * @param	[string]		$parentClass: tslib_pibase Class - used for calling functions like: getAllFoldersInPath
	 * @param	[string]		$folderBase: normaly DOCUMENT_ROOT/fileadmin
	 *
	 * @return	[void]	
	 */	
	 function init($parentClass,$folderBase = null) {
 		if($folderBase != null) $this->folderBase 		= $folderBase;
 			
 		$this->folderCurrent 	= $this->folderBase;
 		$this->parent 			= $parentClass;
 		
 		$this->_init();
 	}
 	

 	/**
	 * Returns the currently selected folder
	 *
	 * @return	[String]	The current folder	
	 */	
 	function getCurrentFolder() {
		if($this->parent == null) die("Please call the init-function before...");

 		return $this->folderCurrent;	
 	}

 	function isTopLevelFolder($folder = null) {
 		if($folder == null) $folder = $this->getCurrentFolder();
 		
 		return ($this->folderBase == $folder);
 	}
 	
 	function getContent() {
		$content 		= '';
		
		if($this->parent == null) die("Please call the init-function before...");
		
	 	$countFolders	= 0;
	 	foreach($this->aValidFolders as $folder) {
	 		//debug($this->pi_linkTP_keepPIvars_url(array('getSubFolders' => $folder),$cache=1),1);
	 		
			// split dirname into an array
			$dirs = preg_split('#\/#', substr($folder,0,strlen($folder) - 1));
	 		$linkText = $countFolders > 0 ? $dirs[count($dirs) - 1] : '..';
	 		
	 		if($this->renderObject != null && $this->renderFunction != null) {
	 			$obj	= $this->renderObject;
	 			$func	= $this->renderFunction;

	 			$content .= $obj->$func($linkText,$folder,$countFolders);
	 		}
			else $content .= $this->_renderPath($linkText,$folder,$countFolders);
			
			$countFolders++;	 		
	 	}
	 	
	 			
	 	return $content;
 	}
 	
 	/**
	 * Internal initialisation function. Reads the sub-folder-structure
	 *   
	 * @return	[void]	
	 */	
 	function _init() {
		$allFolders = array();
	 	$allFolders = $this->parent->getAllFoldersInPath($allFolders,$this->folderBase);
		
	 	// Is the current folder set an ist the current Folder a valid path
	 	if($this->parent->piVars['getSubFolders'] && 
	 		in_array($this->parent->piVars['getSubFolders'],$allFolders))	{

	 		$this->folderCurrent = $this->parent->piVars['getSubFolders'];
	 	} 
		$this->folderCurrent = trim($this->folderCurrent);
		
		// get the next level and save the array as member for later use (getContent)
		$this->aValidFolders = array();
	 	$this->aValidFolders = $this->parent->getAllFoldersInPath($this->aValidFolders,$this->folderCurrent,1);
		sort($this->aValidFolders);
	 	
	 	// Set the right basfolder (on top of folderlist)
	 	if($this->aValidFolders[0] != $this->folderBase)	{
			// split dirname into an array
			$dirs = preg_split('#\/#', substr($this->aValidFolders[0],0,strlen($this->aValidFolders[0]) - 1)); // cut off the last slash
			
			// Set the basefolder on level higher
			if(count($dirs) > 1)  { 
				unset($dirs[count($dirs) - 1]); // Delete last element in array
				$this->aValidFolders[0] = implode('/', $dirs) . '/';
			}
	 	}
 	}
 	
	
 	/**
	 * Shows the folderstructure and links it with pi_linkTP_keepPIvars.
	 * This function can (should) be overwritten in a subclass.
	 *  
	 * @param	[string]		$linkText: Text wich should be linked against the subfolder
	 * @param	[string]		$folder: subfolder
	 *
	 * @return	[void]	
	 */	
 	function _renderPath($linkText,$folder,$countFolders) {
 		return $this->parent->pi_linkTP_keepPIvars($linkText,array('getSubFolders' => $folder),$cache=1) . "<br>";	
 	}
 	
 	/**
	 * You can either inherit from this class to overwrite the _renderPath function but you
	 * can also use this callback-function to do the same in your plugin-class
	 *  
	 * @param	[object]		$renderObject: Object which has the $renderFunction as a method
	 * @param	[string]		$renderFunction: The function used for rendering the Path
	 *
	 * @return	[void]	
	 */	
 	function setRenderingCallback($renderObject,$renderFunction) {
 		if($renderObject != null &&
 			$renderFunction != null &&
 			method_exists($renderObject,$renderFunction)
 			) {
 				
		$this->renderObject			= $renderObject;
		$this->renderFunction		= $renderFunction;
 		}
 	}
	 	
}

?>
