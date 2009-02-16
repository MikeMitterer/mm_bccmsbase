<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Mike Mitterer (mike.mitterer@bitcon.at)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Baseclass for various extensions. Makes live easier for me.
 *
 * @author	Mike Mitterer <mike.mitterer@bitcon.at>
 */

// PATH_tslib is not defined in BE-Mode....
if (!defined('PATH_tslib')) {
         define('PATH_tslib', t3lib_extMgm::extPath('cms').'tslib/');
}

if (!defined('PATH_tslib')) {
         if (@is_dir(PATH_site.TYPO3_mainDir.'sysext/cms/tslib/')) {
                 define('PATH_tslib', PATH_site.TYPO3_mainDir.'sysext/cms/tslib/');
         } elseif (@is_dir(PATH_site.'tslib/')) {
                 define('PATH_tslib', PATH_site.'tslib/');
         }
}

if (PATH_tslib=='') {
         die('Cannot find tslib/. Please set path by defining $configured_tslib_path in '.basename(PATH_thisScript).'.');
}

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once('class.mmlib_filehandling.php');

// include the $mimeTypes Array
// Sample: $mimeTypes["aifc"][0]="audio/x-aiff";	$mimeTypes["aifc"][1]="AIFF-Sound-Dateien";
require_once('class.mmlib_mimetypes.php');
require_once('class.mmlib_cache.php');


class mmlib_extfrontend extends tslib_pibase
{
	var $_dummyFieldList 		= null;
	var $_objUserAuth			= null;
	var $_uploadFolder;
	var $_viewType				= null;	// singleView or listView
	var $_secureFilePrefix		= 'temp_';
	var $extKey;
	var $local_cObj				= null;
	var $mmlib_cache			= null;
	var $_recurse 				= false;
	var $_debug					= false;
	
	/**
	 * Does the same as "initFromArray"
	 *
	 * @param	[array]		$conf: This array comes from the main-function and represents the values from the TS-Field and from setup.txt
	 * @param	[string]	$currentTableName: Holds the plugin tablename. Example: tx_dam
	 * @param	[string]	$uploadFolder: Name of the plugin-specific uploadfolder. Example: tx_mmdamfilelist
	 * @param	[string]	$extensionKey: The extension key. Example: mm_dam_filelist
	 * @return	[void]
	 */
	function init($conf,$currentTableName,$uploadFolder,$extensionKey)
	{
		$aInitData['tablename'] = $currentTableName;
		$aInitData['uploadfolder'] = $uploadFolder;
		$aInitData['extensionkey'] = $extensionKey;
		
		$this->initFromArray($conf,$aInitData);
	}

	/**
	 * Gets the language for the module, makes the initialisation for the PID data,
	 * get information about the logged in user aso.
	 *
	 * @param	[array]		$conf: This array comes from the main-function and represents the values from the TS-Field and from setup.txt
	 * @param	[array]		$aInitData: Holds the data for initialisation in an array
	 *									The array should hold the following variables:
	 *											$aInitData['tablename']) - Holds the plugin tablename. Example: tx_dam
	 *											$aInitData['uploadfolder']) - Name of the plugin-specific uploadfolder. Example: tx_mmdamfilelist
	 *											$aInitData['extensionkey']) - The extension key. Example: mm_dam_filelist
	 *
	 * @return	[void]
	 */
	function initFromArray($conf,$aInitData)
	{
	static $initCounter = 0;
	
		// Avoids multiple calls to this INIT-Functions
		//if($initCounter >= 1) return;
		
		if(!isset($aInitData['tablename'])) 	die('Please set a tablename in initFromArray');
		if(!isset($aInitData['uploadfolder'])) 	die('Please set a uploadfolder in initFromArray');
		if(!isset($aInitData['extensionkey'])) 	die('Please set a extensionkey in initFromArray');
		if(!isset($aInitData['prefix'])) 		$aInitData['prefix'] = get_class($this);

		// Saves the first piVars for later use (isEmptyFirstPageTurnedOn)
		if(isset($this->piVars)) {
			$this->internal['piVarsOnInit']		= $this->piVars;
		} else $this->internal['piVarsOnInit']	= null;
		
		// Change keys to lowercase
		$this->reformatPIVarsKey();
		
		// These extensions are images - prop. needed for the isImage-Function
		$this->internal['image_extension'] = array('jpg','gif','png','jpeg','pdf');
		
		// reduces the DAM Output to this folder (forlder must be in DAM DB)
		$this->internal['this_dam_path_only'] = '';

		// If the flex2conf Array ist set - we can make the configuration a bit mor userfriendly
		// Look here for more info about the FlexForm's 
		// 	http://wiki.typo3.org/index.php/Extension_Development,_using_Flexforms
		if(isset($aInitData['flex2conf']) && is_array($aInitData['flex2conf'])) {
			$this->pi_initPIflexform();
		
			$conf = $this->mergeTSconfFlex($aInitData['flex2conf'],$conf,$this->cObj->data['pi_flexform']);
		}
		
		$this->conf 			= $conf;					// Setting the TypoScript passed to this function in $this->conf
		$this->_uploadFolder 	= $aInitData['uploadfolder'];	// Example: tx_mmreflist
		
		$this->setExtensionKey($aInitData['extensionkey']);  // The extension key.
		
		$this->internal['iconset_path_mimetypes'] = '/pi1/res/images/default/22x22/mimetypes/';
		$this->internal['iconset_path_filesystem'] = '/pi1/res/images/default/22x22/filesystem/';
		
		// Sets the "prfixID" - this is by default the class name - tx_mmpropman_pi1
		if(!isset($this->prefixId)) $this->prefixId = $aInitData['prefix'];

		//If internal TypoScript property "_DEFAULT_PI_VARS." is set then it will merge the current $this->piVars array onto these default values.
		$this->pi_setPiVarDefaults();

		// To find out more about T3...
		//debug($GLOBALS);
		//debug($this->piVars);
		//debug($this->conf["basegroupname"]);
		//debug($GLOBALS["TSFE"]->fe_user->groupData['title'][2]);
		//debug($GLOBALS["TSFE"]->fe_user);
		//debug($this->conf);

		// Preconfigure the typolink (For future Versions)
		$this->initLocalCObj();
		
	
		$this->typolink_conf = $this->conf["typolink."];
		$this->typolink_conf["parameter."]["current"] = 1;
		$this->typolink_conf["additionalParams"] = $this->cObj->stdWrap($this->typolink_conf["additionalParams"],$this->typolink_conf["additionalParams."]);

    	// Configure caching - experimental...
		$this->mmlib_cache = t3lib_div::makeInstance("mmlib_cache");
		$this->allowCaching = $this->conf["allowCaching"] ? 1 : 0;
		//t3lib_div::debug($this->allowCaching,'$this->allowCaching');
		if ($this->allowCaching == 0) $GLOBALS["TSFE"]->set_no_cache();
		else {
			$this->mmlib_cache->init($this->allowCaching);
			//$this->pi_autoCacheEn = true; // removed 1.9.2008 - makes problems with RealURL (generates no_cache with pi_linkTP_keepPIVars) 
			//$this->local_cObj->pi_autoCacheEn = true;
			foreach($this->piVars as $key => $value) {
				$this->pi_autoCacheFields[$key] = array('list' => array($value));
			}
			$this->pi_autoCacheFields['pointer'] = array('range' => array(0,9999));
			$this->pi_autoCacheFields['viewmode'] = array('range' => array(0,9999));
			//t3lib_div::debug($this->pi_autoCacheFields,'$this->pi_autoCacheFields');
		}

		
		// Save the current Table Name
		$this->setTableName($aInitData['tablename']);

		// Set the enabled-Fields for this table
		$this->enableFields = $this->cObj->enableFields($this->getTableName());

		// With this you can get the data for example from a SysFolder
		$this->initPIDList();

		// If a FE-User is logged in - get that information
		$this->initUserAuth();

		// Makes the languagespecific settings
		$this->initLanguage();

		// Loads the current table record into the internal array $this->internal['currentRow']		
		$this->initCurrentRow($this->piVars["showuid"] ? $this->piVars["showuid"] : $this->piVars["showuid"]);

		// Removes old TempFiles from Cache
		$this->_clearSecureCache(30);

		// Adds the nessecary header (css, js) Files
		$this->lookForAdditionalHeaderFiles();

		// Adds the nessecary header (css, js) Files
		if($initCounter == 0) {
			$this->lookForJSOnLoadFunctions();
		}
		
		//t3lib_div::debug("MAde init..." . $initCounter,1);
		$initCounter++;
			
		return $this->conf;
	}

	function lookForJSOnLoadFunctions() {
		$onLoadFunction = array();
		//t3lib_div::debug($this->conf['stylesheetFile']);
		
		if(isset($this->conf['JSOnLoadFunction'])) $onLoadFunction[] = $this->conf['JSOnLoadFunction'];
		else if(isset($this->conf['JSOnLoadFunction.'])) {
			foreach($this->conf['JSOnLoadFunction.'] as $functionname) {
				$onLoadFunction[] = $functionname;	
			}
		}
		
		if(count($onLoadFunction) == 0) return;
	
		foreach($onLoadFunction as $functionname) {
			if(strstr($functionname,'()') == false) $functionname .= '()';
			if(strstr($functionname,';') == false) $functionname .= ';';
			
			// Sample:
			// $GLOBALS['TSFE']->JSeventFuncCalls['onload'][] = 'onLoadKENTBREW();';
			$GLOBALS['TSFE']->JSeventFuncCalls['onload'][] = $functionname;
		}
	}
	/**
	 * Looks if there is a "stylesheetFile" entry in the conf file.
	 * If so it checks some form	#additionalHeaderData.3 = pi1/res/javascript/toggle.js
	 * s filepath versions.
	 * If one fits it adds the the file to the html-header.
	 * 
	 * Configsample:
	 * 		plugin.tx_mmdamfilelist_pi1 {
				#stylesheetFile = pi1/res/css/base.css
				stylesheetFile.1 = pi1/res/css/base.css
				stylesheetFile.2 = pi1/res/css/buttons.css
			}
	*/
	function lookForAdditionalHeaderFiles() {
		$headerfiles = array();
		//t3lib_div::debug($this->conf['stylesheetFile']);
		
		if(isset($this->conf['additionalHeaderData'])) $headerfiles[] = $this->conf['additionalHeaderData'];
		else if(isset($this->conf['additionalHeaderData.'])) {
			foreach($this->conf['additionalHeaderData.'] as $file) {
				$headerfiles[] = $file;	
			}
		}
		
		if(count($headerfiles) == 0) return;
		
		foreach($headerfiles as $file) {
			$possibleFile = array();
			
			$possibleFile[] = t3lib_extMgm::extPath($this->extKey) . $file;
			$possibleFile[] = PATH_site . $file;
			$possibleFile[] = $file;
			$possibleFile[] = '/' . $file;
			
			//t3lib_div::debug($possibleFile,1);
			//t3lib_div::debug(PATH_site,1);
			//t3lib_div::debug('---------------',1);
			
			foreach($possibleFile as $possible) {
				if(is_file($possible)) $this->addAdditionalHeaderFile($possible);
				}
	
				
		}
	}
	
	/**
	 * If the extension of the filename ist .css it adds the file
	 * to the html-header.
	 * 
	 * @param	[String] $filename: The filename to add
	 * 
	 * @return	[void]
	 *  
	*/
	function addAdditionalHeaderFile($filename) {
		$filename 	= '/' . str_replace(PATH_site,'',$filename);
		$fileparts 	= t3lib_div::split_fileref(strtolower($filename));
		
		if($fileparts['realFileext'] == 'css') {
			//$GLOBALS['TSFE']->additionalHeaderData['special_css'] .= 
			$GLOBALS['TSFE']->additionalHeaderData[] =
				"\t" . 
				'<link rel="stylesheet" href="'.$filename.'" type="text/css" media="screen" />' . 
				"\r";
			
		} else if($fileparts['realFileext'] == 'js') {
			$GLOBALS['TSFE']->additionalHeaderData[] =
				"\t" . 
				'<script type="text/javascript" src="' . $filename . '"></script>'; 
				"\r";
		}
		
		//$GLOBALS['TBE_TEMPLATE']->postCode .= '<-- Hallo Mike -->';
		//$GLOBALS['TSFE']->JSeventFuncCalls['onload'][] = 'onLoadKENTBREW();';
		//t3lib_div::debug($TBE_TEMPLATE,1);
		//t3lib_div::debug('++++++++++++++++++',1);
	}
	
	/**
	 * Returns informationa about converting FLEX-Info to conf-Arra
	 * 
	 * Sample:
			return array(
			'view_mode'					=> 'sMAIN:view_mode',
			
			'listView.' => array (
				'templateFile' 			=> 'sLISTVIEW:templatefile',
				),
			'typodbfield.' => array (
				'file_name.' => array (
					'file.'			=> array (
						'maxW'	=> 'sIMAGES:preview_max_w',
						'maxH'	=> 'sIMAGES:preview_max_h',
						),
					),
				),	
			);	
	 * 
	 * @return	[array]	conversion Info
	 * 
	 */
	function getFLEXConversionInfo()	
		{
		return array();
		}

	/**
	 * Set the extension key
	 * 
	 * @return [void]
	 */
	function setExtensionKey($key) {
		$this->extKey = $key;  // The extension key.
	}
	
	/**
	 * Makes a local cObj. Necessary if you are using this class for a backend-module
	 * where noch cObj exists
	 * 
	 * @return [Object]	cObj;
	 */
	function initLocalCObj() {
		require_once (PATH_tslib . "class.tslib_content.php");
		
		$this->local_cObj = t3lib_div::makeInstance("tslib_cObj");
		$this->local_cObj->setCurrentVal($GLOBALS["TSFE"]->id);
		
		// If we are in BE-Mode do die Template-Init here because it does
		// not exist per default (in BE Mode)
		$this->initBETemplates();
		
		return $this->local_cObj;
	}
	
	/**
	 * Merges TS-Setup and the FLEX Settings together
	 * This function will handle that process automatically. 
	 * It will check for flexform values, and replace the corresponding values 
	 * in the $conf array. All you have to do is to provide a mapping array, 
	 * with informations about where the values are located.	 
	 *
	 * I copied this function from the "API" Extension - Thanks to macmade@gadlab.net
	 * For more info look here:
	 * 	http://typo3.org/documentation/document-library/extension-manuals/api_macmade/current/view/1/1/
	 *
	 * @param	[array]		$flex2conf: Describes the mapping
	 * @param	[array]			$conf: 		TS Configuration for this plugin
	 * @param	[array]			$flexRes: 		FLEX-Form Settings
	 *
	 * @return	[array]	The new TS-Conf Array
	 */
	
	function mergeTSconfFlex($flex2conf,$conf,$flexRes) {
		// Temporary config array
		$tempConfig = $conf;

		//t3lib_div::debug($flexRes,1);	
		
		if(!is_array($flex2conf) || !is_array($conf)) return $tempConfig;

		// Process each entry of the mapping array
		foreach($flex2conf as $key=>$value) {
				
			// Check if current TS object has sub objects
			if (is_array($value)) {
				
				// Item has sub objects - Process the array
				$tempConfig[$key] = $this->mergeTSconfFlex($value,$conf[$key],$flexRes);

			} else {
				
				// No sub objects - Get informations about the flexform value to get
				$flexInfo = explode(':',$value);

				// Try to get the requested flexform value
				$flexValue = (string) $this->pi_getFFvalue($flexRes, $flexInfo[1], $flexInfo[0]);
				
				// Check for an existing value, or a zero value
				if (!empty($flexValue) || $flexValue == '0') {
					/*
					t3lib_div::debug($key,1);	
					t3lib_div::debug($flexValue,1);
					t3lib_div::debug($tempConfig[$key],1);
					t3lib_div::debug("........................",1);
					*/
				
					// Override TS setup
					$tempConfig[$key] = $flexValue;
				}
			}
		}

		//t3lib_div::debug($tempConfig);
		// Return configuration array
		return $tempConfig;
	}

	/**
	 * Looks for UIDs in the $nameForeignTable.
	 *
	 * @param	[string]		$UIDFieldInContentTable: Name of the field in the content-table where the UIDs are stored
	 *												This field can also be a FLEX-Form Description like: sSHEETNAME:fieldname
	 *											
	 * @param	[string]		$nameForeignTable: In this Table there are the Data for the UIDs
	 * @param	[string]		$fieldnameInForeignTable: Fieldname in the foreign Table where the Discription is stored
	 * @param	[boolean]		$sort: If there is a sorting field in the table the result will be sorted

	 * @return	[array]		The index of the array is the uid, the Contents of the field ist stored as array-data
	 */
	function getDataFromForeignTable($UIDFieldInContentTable,$nameForeignTable,$fieldnameInForeignTable,$sort = true,$parentUID = -1)
	{
		if($this->isDebug()) { 
			t3lib_div::debug($this->cObj->data,'$this->cObj->data');
			t3lib_div::debug($this->internal['currentRow'],'$this->internal[\'currentRow\']');
		}
		
		// returns all entries
		if($UIDFieldInContentTable == null) {
			$conf['uidInList'] = '';
		
		// $this->cObj->data - data from the current tt_content-record
		} else if(isset($this->cObj->data[$UIDFieldInContentTable])) {
			$conf['uidInList'] = $this->cObj->data[$UIDFieldInContentTable];
			if($this->isDebug()) { 
				t3lib_div::debug($conf['uidInList'],'if(isset($this->cObj->data[$UIDFieldInContentTable]))');
			}

		// the current table record
		} else if(isset($this->internal['currentRow'][$UIDFieldInContentTable])) {
			$conf['uidInList'] = $this->internal['currentRow'][$UIDFieldInContentTable];
			if($this->isDebug()) {
				t3lib_div::debug($conf['uidInList'],'if(isset($this->internal[\'currentRow\'][$UIDFieldInContentTable]))');
			}
			
		// Here it comes from the FlexForm
		} else {
			$flexInfo = explode(':',$UIDFieldInContentTable);
			$conf['uidInList'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $flexInfo[1], $flexInfo[0]);
			if($this->isDebug()) { 
				t3lib_div::debug($conf['uidInList'],'else...');
			}
			//debug($this->cObj->data['pi_flexform']);
		}

		// Here is the foreign table - Initialized by $this->initPIDList()
		$conf['pidInList'] = $this->pid_list;
		// Overwrite PID for special needs (Expample - get data from tt_address
		if($parentUID != -1) $conf['pidInList'] = $parentUID;
		
		if($conf['pidInList'] == 0 && $conf['uidInList'] == 0) {
			//t3lib_div::debug(debug_backtrace(),'var_dump');
			die('ATTENTION: uidInList AND pidInList is 0, so the QUERY must return 0 records (getDataFromForeignTable)...');
		}
		
		$conf['selectFields'] = $fieldnameInForeignTable . ',uid';

		$conf['where'] = $this->cObj->enableFields($nameForeignTable);
		if($this->internal['overruleFEUser']) {
			$showHidden = $this->internal['overruleFEUser'];
			$conf['where'] = $this->enableFields($nameForeignTable,null,$showHidden,array('fe_group'));
		}
		
		if($sort) {
			$conf['orderBy'] = $this->getSortFieldFromTCA($nameForeignTable);
		}
		
		if($this->isDebug()) t3lib_div::debug($conf,'$conf');
		$SQLStatement = $this->cObj->getQuery($nameForeignTable,$conf);
		$SQLStatement = str_replace('AND AND','AND',$SQLStatement);
		if($this->isDebug()) t3lib_div::debug($SQLStatement,'$SQLStatement');
		
		$result = $GLOBALS['TYPO3_DB']->sql_query($SQLStatement);
		$tempListdata = array();
		$fieldnames = split(',',$fieldnameInForeignTable);
		while(($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))) {
			if(count($fieldnames) > 1) {
				foreach($fieldnames as $fieldname) {
					$tempListdata[$record['uid']][$fieldname] = $record[$fieldname];
				}
			} else $tempListdata[$record['uid']] = $record[$fieldnameInForeignTable];
		}
		
		// Reorder the values from the the table to the order sequence of the UID-List (Frontendorder!!!!)
		if($conf['uidInList'] != null) {
			$uidList = 	explode(',',$conf['uidInList']);
			foreach($uidList as $key) {
				if(strlen($key) > 0 && isset($tempListdata[$key])) $listdata[$key] = $tempListdata[$key];
			}
		} else {
			$listdata = $tempListdata;
			//asort($listdata);
			}

		//$this->mmlib_cache->setResult('getDataFromForeignTable',array($UIDFieldInContentTable,$nameForeignTable,$fieldnameInForeignTable,$sort),$listdata);
			
		return $listdata;
	}

	/**
	 * Returns the TCA sortinginformation.
	 * 
	 * @param	[string]		$tabelname: The table for which the information is needed
	 * 
	 * @return	[string]		Fieldname of the sortfield or an empty string if there is no sorting information
	 */
	function getSortFieldFromTCA($tabelname) {
		global $TCA;
		$sortfield = '';
		
		$GLOBALS['TSFE']->includeTCA();
			
		if(isset($TCA[$tabelname]['ctrl']['default_sortby'])) {
			$sortfield = $TCA[$tabelname]['ctrl']['default_sortby'];
		} else if(isset($TCA[$tabelname]['ctrl']['sortby'])) {
			$sortfield = $TCA[$tabelname]['ctrl']['sortby'];
		}
		
		//t3lib_div::debug($TCA['tx_mmhutinfo_hutguide']['ctrl']['default_sortby'],'default_sortby=');
		//t3lib_div::debug($TCA['tx_mmhutinfo_hutguide']['ctrl']['sortby'],'sortby=');
		
		return str_replace('ORDER BY','',$sortfield);
	}
	
	/**
	 * Just an example (until now (060105)) for getting the data for a MM Relation
	 *
	 * @param	[string]		$uidFieldFromCurrentTable: Field in the current Table where the UIDs are stored
	 * @param	[string]		$nameMMTable: Name of the MM-Table
	 * @param	[string]		$nameTable: Name of the foreign-table
	 * @param	[boolean]		$fCountRecords: Count only
	 
	 * @return	[pointer MySQL select result pointer / DBAL object]
	 */
	function getMMData($uidFieldFromContentTable,$nameMMTable,$nameTable,$fCountRecords = false)
	{
		$nameMainTable			= $this->getTableName();
		$uidList				= $this->internal['currentRow'][$uidFieldFromContentTable];
		$SELECT_FIELDS 			= $nameMainTable . '.*,' . $nameMMTable . '.uid_foreign';
		$WHERE_CAT 				= 'AND ' . $nameTable . '.uid IN (' . $uidList . ')';
		//$WHERE_ENABLE_FIELDS 	= $this->cObj->enableFields($nameMainTable);
		$LIMIT 					= '';

		if($fCountRecords == true)
		{
			$SELECT_FIELDS 	= 'count(*)';
		}

		$showLastQuery = true;
		if($showLastQuery) {
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
		$SELECT_FIELDS,
		$nameMainTable,
		$nameMMTable,
		$nameTable,
		$WHERE_CAT . $WHERE_ENABLE_FIELDS,
			'', 							//	groupBy,
			'', 							// 	orderBy,
		$LIMIT 						//	limit
		);

		if($showLastQuery) t3lib_div::debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"lastBuiltQuery=");
		
		/*
		if($res == null)
		{
		debug(mysql_error());
		debug($SELECT_FIELDS);
		debug($WHERE_CAT . $WHERE_ENABLE_FIELDS . $strWhereStatement);
		debug($LIMIT);
	}

	while($res != null && ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)))
	{
	debug($record);
	}
	*/
		
		return $res;

	}

	/**
	 * You can set the plugins table name.
	 * If $tablename is null the function tries to find the name
	 * by itself.
	 *
	 * @param	[string]			$tablename: The name or null for autofind the name
	 * @return	[string]		The actual name of the table
	 */
	function setTableName($tablename = null)
	{
		if ($tablename == null)
		{
			list($t) = explode(":",$this->cObj->currentRecord);
			//debug($this->cObj->data);
			$this->internal["currentTable"] = $t;
		}
		else $this->internal["currentTable"] = $tablename;

		return $this->internal["currentTable"];
	}

	/**
	 * Returns the name of the plugins database table.
	 * If there is no name set, the function tries to find the name by itself.
	 *
	 * @return	[string]	The plugins table name
	 */
	function getTableName()
	{
		if(!isset($this->internal['currentTable']) || strlen(trim($this->internal['currentTable'])) == 0)
		{
			$this->setTableName();
		}
		return $this->internal['currentTable'];
	}
	
	/**
	 * Makes the language-specific settings
	 *
	 * @return	[void]
	 */
	function initLanguage() {
		// Set the language
		// You can set the language like this: plugin.tx_<pluginname>_pi1.language = de
		// or with the global Setup: config.language = de
	
		// sys_language_mode defines what to do if the requested translation is not found
		$this->sys_language_mode = $this->conf['sys_language_mode'] ? $this->conf['sys_language_mode'] : $GLOBALS['TSFE']->sys_language_mode;

		$defaultLanguage = ($GLOBALS['TSFE']->config['config']['language'] ? $GLOBALS['TSFE']->config['config']['language'] : $this->sys_language_mode); // Fr�her: 'default';

		$this->LLkey = ($this->conf["language"] ? $this->conf["language"] : $defaultLanguage);
		//$this->altLLkey = $this->LLkey;

	
		
		// Fills the internal array '$this->langArr' with the available syslanguages
		$lres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'sys_language',
			'1=1' . $this->cObj->enableFields('sys_language'));

		$this->langArr = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($lres)) {
			$this->langArr[$row['uid']] = $row;
		}

		// Load Backend-Lables
		// check if the $GLOBALS["LANG"]-object is available - if not, load it
		// You can get the BackendLable with $this->getBL(...)
		if (!is_callable($this->internal['BACKEND_LANG']->sL))
		{
			require_once(t3lib_extMgm::extPath('lang').'lang.php');

			$this->internal['BACKEND_LANG'] = t3lib_div::makeInstance('language');
			$this->internal['BACKEND_LANG']->lang = $this->LLkey;
			//debug($this->internal['BACKEND_LANG']->sL('LLL:EXT:mm_propman/locallang_db.php:tx_mmpropman_data.previewimage'));
		}

		// Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
		// Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
		$this->pi_loadLL();
		
		/*		
		t3lib_div::debug($this->LOCAL_LANG,"LOCAL_LANG=");
		//t3lib_div::debug($this->conf,"\$this->conf=");
		
		$basePath = t3lib_extMgm::extPath($this->extKey).dirname($this->scriptRelPath).'/locallang.php';
		$file = t3lib_div::getFileAbsFileName($basePath);
		$baseFile = ereg_replace('\.(php|xml)$', '', $file);
		t3lib_div::debug($baseFile,"\$baseFile=");
		$filename = $baseFile . '.xml';
		t3lib_div::debug(t3lib_div::readLLXMLfile($filename,'default'),"$baseFile.xml=");
		$xmlString = t3lib_div::getUrl($filename);
		t3lib_div::debug($xmlString,"\$xmlString=");
		$xmlContent = t3lib_div::xml2array($xmlString);
		t3lib_div::debug($xmlContent['data']['de'],"\$xmlContent['data']['de']=");
		$langKey = 'de';
		t3lib_div::debug(is_string($LOCAL_LANG[$langKey]) && strlen($LOCAL_LANG[$langKey]),"is_string=");
		t3lib_div::debug($this->conf['_LOCAL_LANG.'],"\$this->conf['_LOCAL_LANG.']=");
		t3lib_div::debug($this->altLLkey,"\$this->altLLkey=");
		t3lib_div::debug($this->LLkey,"\$this->LLkey=");
		//typo3conf/l10n/de/mm_hutinfo/pi1/de.locallang.xml
		*/
		
		/*		
		t3lib_div::debug($this->sys_language_mode,1);
		t3lib_div::debug($this->LLkey,1);
		t3lib_div::debug($this->LOCAL_LANG,"LOCAL_LANG=");
		t3lib_div::debug($this->scriptRelPath,1);
		
		$basePath = t3lib_extMgm::extPath($this->extKey).dirname($this->scriptRelPath).'/locallang.php';
		$tempLOCAL_LANG = t3lib_div::readLLfile($basePath,$this->altLLkey);
		t3lib_div::debug($basePath,1);
		t3lib_div::debug($tempLOCAL_LANG,1);
		*/
	}
	
	/**
	 * Gets the date from the current table record
	 *
	 * @return	[array]	$this->internal['currentRow']
	 */
	function initCurrentRow($showUID) {
		$this->mmlib_cache->resetBuffer();
		
		// Do we have to overrule FEUsers
		$where = $this->cObj->enableFields($this->getTableName());
		if($this->internal['overruleFEUser']) {
			$showHidden = $this->internal['overruleFEUser'];
			$where = $this->enableFields($this->getTableName(),null,$showHidden,array('fe_group'));
		}
	
		// - old version. Overruling was not possible
		//$this->internal['currentRow'] 	= $this->pi_getRecord($this->getTableName(),$showUid);
		
		// now 28.2.08 - we overrule...
		$this->internal['currentRow'] = $this->checkRecordOverruleFields($this->getTableName(),$showUID,0,$where);
		
		//$langUID 						= $GLOBALS["TSFE"]->sys_language_uid; // config.sys_language_mode = content_fallback must be defined
		$langUID 						= $GLOBALS["TSFE"]->config["config"]["sys_language_uid"];

		if(!is_array($this->internal['currentRow'])) {
			$this->internal['currentRow'] = $this->cObj->data;
			return $this->internal['currentRow'];
		}
		
		if($langUID != $this->internal['currentRow']["sys_language_uid"]) {
			$uid 									= $this->internal['currentRow']["l18n_parent"] ? $this->internal['currentRow']["l18n_parent"] : $showUID;
			$sys_language_content = 1;
			$OLmode 							= ($this->sys_language_mode == 'strict' ? 'hideNonTranslated' : '');
		
			// Get the parent
			$this->internal['currentRow'] = $this->pi_getRecord($this->getTableName(),$uid);
			$this->internal['currentRow'] = $GLOBALS['TSFE']->sys_page->getRecordOverlay($this->getTableName(), $this->internal['currentRow'], $langUID, $OLmode);
		}
		return $this->internal['currentRow'];
	}
	
	/**
	 * extends the pid_list given from $conf or from $this->cObj->data recursively by the pids of the subpages
	 * generates an array from the pagetitles of those pages
	 * (copied from tx_ttnews) THX!
	 *
	 * @return	[void]
	 */
	function initPIDList()
	{
		$pidArray = array();
		
		// pid_list is the pid/list of pids from where to fetch the plugin items.
		$pid_list_temp = $this->cObj->data['pages'];
		if($pid_list_temp) $pidArray = t3lib_div::intExplode(',', $pid_list_temp); 

		trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		if(isset($this->conf['pidList'])) {
			$pidArray = array_merge($pidArray,t3lib_div::intExplode(',', $this->conf['pidList']));
		}
		
		// Use the current page as basis
		if(isset($this->cObj->data['pid'])) {
			$pidArray = array_merge($pidArray,array($this->cObj->data['pid']));
			//t3lib_div::debug($this->cObj->data['pid'],'$this->cObj->data[\'pid\']');
		}
		
		$pid_list = count($pidArray) ? implode($pidArray, ',') : $GLOBALS['TSFE']->id;
		//t3lib_div::debug($pid_list,'$pid_list');
		
		$recursive = $this->cObj->data['recursive'];
		$recursive = is_numeric($recursive) ? $recursive:
		$this->cObj->stdWrap($this->conf['recursive'], $this->conf['recursive.']);

		// extend the pid_list by recursive levels
		$this->pid_list = $this->pi_getPidList($pid_list, $recursive);
		$this->pid_list = $this->pid_list?$this->pid_list:0;

		$this->conf['pidList'] = $this->pid_list;
		$this->conf['recursive'] = $recursive;
		//t3lib_div::debug($this->pid_list,'$this->pid_list');
		}

	/**
	 * Makes a new instance of the tslib_feUserAuth Object
	 *
	 * @return	[void]		
	 */
	function initUserAuth()
		{
		require_once (PATH_tslib."class.tslib_feuserauth.php");
			
		$this->_objUserAuth = t3lib_div::makeInstance('tslib_feUserAuth');

		//debug($GLOBALS["TSFE"]->fe_user);
		if(!isset($GLOBALS["TSFE"]->fe_user->user)) {		
			$this->_objUserAuth->start();
			
			if(isset($this->conf["allowCachingIfUserIsLoggedIn"])) {
				if($this->conf["allowCachingIfUserIsLoggedIn"] == 0) {
					$GLOBALS["TSFE"]->set_no_cache();
				}
			}
		}
		//debug($GLOBALS["TSFE"]->fe_user);

		// If someone knows a better way to give the username to
		// zip.php - please let me know...
		setcookie('user',$GLOBALS["TSFE"]->fe_user->user['username']);
			
		return $this->_objUserAuth;
		}

	/**
	 * Different languages in ONE SysFolder
	 * For more configuration-details look here:
	 * http://typo3.org/documentation/document-library/tt_news/Configuration-1/#oodoc_part_7405
	 * 
	 * Example:
	 *	function execQuery($fCountRecords = 0,$strWhereStatement = '') {
	 *		$selectConf = generateLangSpecificSelectConf();
	 * 		$res = $this->cObj->exec_getQuery('tx_cfabwwwminifaq_items', $selectConf);
	 *		return $res;
	 *		}
	 *
	 * @return	[array]		...
	 */
	function generateLangSpecificSelectConf()
		{
		$currentTable = $this->getTableName();
		$enableFields	= $this->cObj->enableFields($currentTable);
		//$langUID		= $GLOBALS["TSFE"]->sys_language_uid; // config.sys_language_mode = content_fallback must be defined		
		//$langUID 		= $GLOBALS['TSFE']->sys_language_content;
		$langUID 		= $GLOBALS["TSFE"]->config["config"]["sys_language_uid"];
		$checkLangUID	= !isset($this->conf['turnOffDifferentLanguagesInOneFolder']) || $this->conf['turnOffDifferentLanguagesInOneFolder'] == false;
		
		if ($langUID && $checkLangUID)
			{
			$aTempQueryConfig = array(
				'selectFields' => $currentTable . '.uid', // difference to ttnews - ttnews looks for l18n_parent and does the rest later
				'pidInList' => $this->pid_list,
				'where' => $currentTable . '.sys_language_uid = ' . $langUID . $enableFields
				);
			$tmpres = $this->cObj->exec_getQuery($currentTable,$aTempQueryConfig);
			$strictUids = array();
			while ($tmprow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tmpres))
				{
				$strictUids[] = $tmprow['uid'];
				}
			$strStrictUids = implode(',', $strictUids);
			// strict UID and with "-1" the "global" FAQ's
			$selectConf['where'] .= ' (' . $currentTable . '.uid IN (' . ($strStrictUids ? $strStrictUids : 0) . ') OR ' . $currentTable . '.sys_language_uid=-1)';
			}
		else
			{
			// sys_language_mode != 'strict': If a certain language is requested, select only news-records in the default language. The translated articles (if they exist) will be overlayed later in the list or single function.
			$selectConf['where'] .= ' ' . $currentTable . '.sys_language_uid IN (0,-1)';
			}
		$selectConf['selectFields'] = $currentTable . '.*';
		$selectConf['pidInList'] = $this->pid_list;

		return $selectConf;
		}


	/**
	 * You can set some vars for use over the class functions (Class-global)
	 * This function is called by the framework and is (C++) virtual
	 * So if you implement this function in your plugin-class - it is called automatically
	 *
	 * @param	[type]		$strView: Just for the case... (maybe used in the future)
	 * @return	[void]
	 */
	function initInternalVars($strView)
		{
		$conf =  $this->conf[$strView . '.'];
		
		$this->internal['modeselector'] = array('0' => 'Default mode');
		
		// ORDER or SORT info is set in setInternalQueryParameters
		//list($this->internal["orderBy"],$this->internal["descFlag"]) = explode(":",$this->piVars["order"]);
		//$this->internal["orderByList"]= $conf['orderByList'];
		
		$this->initInternalOrderSelector();
		$this->initInternalViewSelector();
		$this->initInternalFilterSelector();
		}

	function initInternalOrderSelector() {
		//$conf =  $this->conf[$this->getViewType() . '.'];
		$conf =  $this->conf;
		
		for($counter = 1;isset($conf["orderselector$counter"]);$counter++) {
			$this->internal['orderselector'][$counter]['field'] 		= $conf["orderselector$counter"];
			$this->internal['orderselector'][$counter]['desc_flag'] 	= $conf["orderselector$counter" . "_desc_flag"];
		}
		//debug($this->internal['orderselector']);
	}

	function initInternalViewSelector() {
		//$conf =  $this->conf[$this->getViewType() . '.'];
	}

	function initInternalFilterSelector() {
		//$conf =  $this->conf[$this->getViewType() . '.'];
		debug('','initInternalFilterSelector()');
	}
	
	/**
	 * Sets some important params for execQuery like like results_at_a_time aso.
	 * The basevalues can be set in TS
	 *
	 * TS-Sample:
	 * 	plugin.tx_mmdamfilelist_pi1.listView {
	 * 	results_at_a_time = 3
	 * 	maxPages =
	 * 	colsOnPage = 1
	 * 	searchFieldList = 
	 * 	orderByList = 
	 * 	}
	 *
	 * @param	[string]		$strView: listView or singleView - these are the names from the TS Code
	 * @return	[void]		
	 */
	function setInternalQueryParameters($strView)
		{
		$lConf = $this->conf[$strView . '.'];	// get LocalSettings

		// Initializing the query parameters:
		
		// CommandlineParam overwrites internal SORT Info / OR ORDER!!! Info
		// fieldnames must be in $lConf['orderByList']
		$orderInfo = $this->piVars["order"] ? $this->piVars["order"] : $this->conf['order'];
		if($orderInfo == '') $orderInfo = $this->piVars["sort"] ? $this->piVars["sort"] : $this->conf['sort'];
		list($this->internal["orderBy"],$this->internal["descFlag"]) = explode(":",$orderInfo);

		$this->internal["results_at_a_time"] 		= t3lib_div::intInRange($lConf["results_at_a_time"],0,1000,3);		// Number of results to show in a listing.
		$this->internal["maxPages"]					= t3lib_div::intInRange($lConf["maxPages"],0,1000,10);;		// The maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
		$this->internal["colsOnPage"]				= t3lib_div::intInRange($lConf["colsOnPage"],1,100,1);;
		$this->internal['orderByList']				= $this->conf['orderByList'];
		$this->internal['dontLinkActivePage']		= $lConf['dontLinkActivePage'];
		$this->internal['showFirstLast']			= $lConf['showFirstLast'];
		$this->internal['pagefloat']				= $lConf['pagefloat'];
		$this->internal['showResultsNumbersWrap']	= $lConf['showResultsNumbersWrap'];

		$this->internal['searchFieldList']			= $this->conf['searchFieldList'];
		
		//debug($this->internal);
		}

	/**
	 * Get Informations from the backend language fileb (locallang_db)
	 * If you need the german name of DB-Field - use this function
	 *
	 * Example:
	 *	Values in locallang_db.php: 
	 *			"tx_mmpropman_data.salestype.I.1" => "Rent",	
	 *			"tx_mmpropman_data.salestype.I.0" => "Buy",	
	 *
	 *  With getBL('salestype',1) yout get Rent
	 *
	 * @param	[string]		$index: Hash-Indexstring - Look at the sample
	 * @param	[short]			$subindex: Look at the sample
	 * @param	[string]		$foreignTableName: Per default the request is made with the current Table name - you can overwrite this value with this param
	 * @param	[string]		$fromPlugin: From which plugin to you want to retreive the Data
	 * 	 * @return	[string]	The name of the DB-Field in the required Language
	 */
	function getBL($keyword,$subindex = -1,$foreignTableName = null,$fromPlugin = null)
		{
		$plugin		= $this->extKey;
		
		$fromTable = $this->getTableName();
		if($foreignTableName != null) $fromTable = $foreignTableName;
		
		if($fromPlugin != null) $plugin = $fromPlugin;
		
		// DAM
		$realIndex = 'LLL:EXT:' . $plugin .'/locallang_db.php:' . $fromTable . '.' . $keyword . ($subindex != -1 ? '.I.' . $subindex : '');
		// Sample: $realIndex = 'LLL:EXT:mm_propman/locallang_db.php:tx_mmpropman_data.' . $keyword . ($subindex != -1 ? '.I.' . $subindex : '');
		// t3lib_div::debug($realIndex);
		
		// Weg lt. path von maringer@maringer-it.de am 22.4.08
		//return htmlentities($this->internal['BACKEND_LANG']->sL($realIndex));
		return $GLOBALS['TSFE']->sL($realIndex);		
		}

	/**
	 * This function first looks in locallang.php for a front-end lable description.
	 * If the descrition is not found, the function looks in the backend file (locallang_db.php)
	 *
	 * 
	 * Example:
	 *	Values in locallang_db.php: 
	 *			"tx_mmpropman_data.salestype.I.1" => "Rent",	
	 *			"tx_mmpropman_data.salestype.I.0" => "Buy",	
	 *
	 *
	 * @param	[string]		$keyword: the entry in in the language file
	 * @param	[string]		$default: The default value if nothing is found
	 * @param	[short] 		$subindex: Look at the sample ( I.<subindex> )
	 * 
	 * @return	[string]	The field description
	 */
	function getLLabel($keyword,$default = '',$subindex = -1) {
		$tempDefault = '#' . $default . '#';
		
		$result = $this->pi_getLL($keyword . ($subindex != -1 ? '.I.' . $subindex : ''),$tempDefault);
		if($result != $tempDefault) {
			return $result;
			//return htmlentities($result);
		}

		$result = $this->getBL($keyword,$subindex);
		if($result != '') return $result;
		
		return $default;
	}
	
	/**
	 * OBSOLET!!!! use getTemplateContent($strView)
	 *
	 * Looks for the right filename for the required view
	 * TS-Example: plugin.tx_mmdamfilelist_pi1.listView.templateFile = list_view.tmpl
	 *
	 * @param	[string]		$strView: Name of the view wich is set in TS
	 * @return	[string]		Returns the name of the file 
	 */
	function getTemplateName($strView)
		{
		$lConf 						= $this->conf[$strView . '.'];
		$strTemplateName	= ($lConf["templateFile"] ? $lConf["templateFile"] : 'list_view.tmpl');


		$aTemplateFileName	= t3lib_div::split_fileref($strTemplateName);
		if(!isset($aTemplateFileName['path']) || strlen(trim($aTemplateFileName['path'])) == 0)
			{
			$aTemplateFileName['path'] = 'EXT:' . $this->extKey . '/pi1/res/';
			}
		
		$templateContent = $this->cObj->fileResource($aTemplateFileName['path'] . $aTemplateFileName['file']);
		
		return $strTemplateName;
		}

	/**
	 * Returns the '###TEMPLATE_DEFAULT###' or '###TEMPLATE_<language>_###' section
	 * from the given Templatefile
	 *
	 * @param	[string]		$templateName: The filename wich is returned by getTemplateName
	 * @return	[string]	Section from templatefile
	 */
	function getTemplateContentFromFilename($templateName)
		{
		$langKey 			= strtoupper($GLOBALS['TSFE']->config['config']['language']);
		$templateName 		= preg_replace('#^/#','',$templateName);
		
		$templateFile[] 	= $templateName;
		$templateFile[] 	= PATH_site . $templateName;
		$templateFile[] 	= 'EXT:' . $this->extKey . '/pi1/res/' . $templateName;
		$templateFile[] 	= 'EXT:' . $this->extKey . '/mod1/res/' . $templateName;
		$templateFile[] 	= t3lib_extMgm::extPath($this->extKey) . 'pi1/res/' . $templateName;
		$templateFile[] 	= t3lib_extMgm::extPath($this->extKey) . 'mod1/res/' . $templateName;
		$templateFile[] 	= str_replace(PATH_site,'',t3lib_extMgm::extPath($this->extKey)) . 'pi1/res/' . $templateName;
		$templateFile[] 	= str_replace(PATH_site,'',t3lib_extMgm::extPath($this->extKey)) . 'mod1/res/' . $templateName;
		
		$templateContent = '';
		foreach($templateFile as $filename) {
			$templateContent = $this->local_cObj->fileResource($filename);
			if($templateContent != '') break;
		}

		if($templateContent == '') die("Sorry - the template $templateName is not available.");
		
		// Get language version of the help-template
		$templateContent_lang = '';
		if ($langKey) {
			$templateContent_lang = $this->local_cObj->getSubpart($templateContent, '###TEMPLATE_' . $langKey . '###');
			}

		$templateContent = $templateContent_lang ? $templateContent_lang : $this->local_cObj->getSubpart($templateContent, '###TEMPLATE_DEFAULT###');

		return $templateContent;
		}

	/**
	 * Returns the '###TEMPLATE_DEFAULT###' or '###TEMPLATE_<language>_###' section
	 * from specified Templatefile 
	 *
	 * @param	[string]		$strView: Name of the view wich is set in TS
	 * @return	[string]	Right section from templatefile
	 */
	function getTemplateContent($strView)
		{
		$lConf 				= $this->conf[$strView . '.'];
		$strTemplateName	= ($lConf["templateFile"] ? $lConf["templateFile"] : 'list_view.tmpl');
		$aTemplateFileName	= t3lib_div::split_fileref($strTemplateName);

		//debug($strView);
		//debug($this->conf);
		//debug($lConf);
		//debug($aTemplateFileName);
		
		// Remove the trailing / from the path
		$aTemplateFileName['path'] = preg_replace('#^/#','',$aTemplateFileName['path']);
		
		if(!isset($aTemplateFileName['path']) || strlen(trim($aTemplateFileName['path'])) == 0)	{
			$aTemplateFileName['path'] = $this->_getUploadFolder();
			}

		//debug($aTemplateFileName);
		$wildCardFilename = $aTemplateFileName['filebody'] . '*.' . $aTemplateFileName['fileext'];
		$filename = $GLOBALS['TSFE']->tmpl->getFileName($aTemplateFileName['path'] . $aTemplateFileName['file']);
		if($filename == null) {
			// Add the whole SystemPath to the template-path
			$filename = $GLOBALS['TSFE']->tmpl->getFileName(PATH_site . $aTemplateFileName['path'] . $aTemplateFileName['file']);			
		}
		
		// If the path for the template is not specified or not valid - asume that
		// the template is in the extensions "res" folder
		if(!$filename || !is_file($filename)) {
			$aTemplateFileName['path'] = 'EXT:' . $this->extKey . '/pi1/res/';
			}
		//debug($aTemplateFileName['path'] . $aTemplateFileName['file'],1);
		
		$templateContent = $this->cObj->fileResource($aTemplateFileName['path'] . $aTemplateFileName['file']);
		if($templateContent == '') die("Sorry - the template for $strView: (" . $aTemplateFileName['path'] . $aTemplateFileName['file'] . ' is not available.');

		// Get language version of the help-template
		$langKey = strtoupper($GLOBALS['TSFE']->config['config']['language']);

		
		$templateContent_lang = '';
		if ($langKey) {
			$templateContent_lang = $this->cObj->getSubpart($templateContent, '###TEMPLATE_' . $langKey . '###');
			}

		$templateContent = $templateContent_lang ? $templateContent_lang : $this->cObj->getSubpart($templateContent, '###TEMPLATE_DEFAULT###');

		return $templateContent;
		}
		
	/**
	 * Depending on the description from plugin.<your pluginname>.typodbfield
	 * the links are created automaticaly
	 * This function ist call for every DB-field and for every field from dummyfieldlist (TS)
	 *
	 * @param	[string]		$fieldname: The name of the field
	 * @param	[string]		$content: The content wich is in the DB-Table
	 * @param	[Array]			$confField: Overwrites the TS-Configuration
	 * 	 * @return	[string]	The processed content
	 */
	function getAutoFieldContent($fieldname,$content,$confField = null)
		{
		$confDBField = $this->conf['typodbfield.'][$fieldname . '.'];
		if(!isset($confDBField) && isset($this->internal['bccms_plugin_name'])) {
			$confDBField = $this->conf[$this->internal['bccms_plugin_name'] .'.']['typodbfield.'][$fieldname . '.'];
		}
		
		// overwrite the TS-Configuration
		if($confField != null) $confDBField = $confDBField;
		 
		// You can change the content of dummyfield with an entry for 'field'
		// The Tablename for field must exist as a Table-Fieldname
		// TS-Sample: 
		// plugin.tx_mmreflist_pi1.typodbfield.dummyfieldlist = preview
		// plugin.tx_mmreflist_pi1.typodbfieldpreview.field = image

		if(isset($confDBField['field'])) // && isset($this->internal['currentRow'][$confDBField['field']]))
			{
			$content = $this->getFieldVal($confDBField['field']);
			}
			
		// Make the link to the singleView Mode
		if(isset($confDBField) &&
			$this->getViewType() == 'listView' &&
			((isset($confDBField['singlelink']) && $confDBField['singlelink'] == 1) ||
			(isset($confDBField['fieldtype']) && $confDBField['fieldtype'] == 'singlelink')))
			{
			
			$content = $this->_getSingleLinkContent($fieldname,$content,$confDBField);
			return $content;	
			}
		
		if(isset($confDBField) && isset($confDBField['fieldtype']))
			{
			// If this Content comes from DAM then set the right filepath
			// A little hack for DAM
			if($this->getTableName() == 'tx_dam' && !isset($confDBField['path']))
				{
				$confDBField['path'] = $this->internal['currentRow']['file_path'];
				}

			switch($confDBField['fieldtype'])
				{
				case 'image':
					$content = $this->_getImageContent($fieldname,$content,$confDBField);
					break;
				case 'link':
					$content = $this->_getLinkContent($fieldname,$content,$confDBField);
					break;
				case 'filelink':
					$content = $this->_getFileLinkContent($fieldname,$content,$confDBField);
					break;
				case 'stdwrap':
					// Otherwise stdWrap gets confused
					unset($confDBField['field']);
					
					$content = $this->cObj->stdWrap($content,$confDBField);
					break;
				case 'text':
					$content = $this->cObj->TEXT($confDBField);
					break;
					
				case 'rtecsstext':
					$content = $this->pi_RTEcssText($content);
					break;
				}
			}
		return $content;
		}

	/**
	 * Returns the value for the field from $this->data. If "//" is found in the $field value that token will split the field values apart and the first field having a non-blank value will be returned.
	 *
	 * @param	string		The fieldname, eg. "title" or "navtitle // title" (in the latter case the value of $this->data[navtitle] is returned if not blank, otherwise $this->data[title] will be)
	 * @return	string
	 */
	function getFieldVal($field)	
		{
		if (!strstr($field,'//'))	
			{
			return $this->internal['currentRow'][$field];
			} 
		else 
			{
			$sections = t3lib_div::trimExplode('//',$field,1);
			while (list(,$k) = each($sections)) 
				{
				if (strcmp($this->internal['currentRow'][$k],''))	return $this->internal['currentRow'][$k];
				}
			}
		}

	/**
	 * Shows an image on the website depending on all the settings from your setup.txt file
	 * If the parame "secure" is set in TS the function copies the file to
	 * typo3temp/pics/ and changes the Filename to a hash string.
	 *
	 * @param	[string]		$fieldname: Fieldname from the DB-Table
	 * @param	[string]		$content: The content from the DB-Field
	 * @param	[string]		$confDBField: Configurationsettings from TS
	 * @return	[string]	The processed content
	 */
	function _getImageContent($fieldname,$content,$confDBField)
		{
		if(strpos($content,',') === false) $aImages[0] = $content;
		else $aImages = split(',',$content);

		if(!isset($aImages[0]) || strlen($aImages[0]) == 0) {
			return '';
		}

		$strContent = '';
		$nCounter = 0;
		$useImageWithIndex = -1;
		$useMIMEImage = 0;
		
		
		// In the settings you can specify which image you want
		if(isset($confDBField['file.']['import.']['listNum'])) {
			$useImageWithIndex = $confDBField['file.']['import.']['listNum'];
			}
		if(isset($confDBField['listNum'])) {
			$useImageWithIndex = $confDBField['listNum'];
			}
		if(isset($confDBField['useMIMEImage'])) {
			$useMIMEImage = $confDBField['useMIMEImage'];
			}
			
			
		
		foreach($aImages as $image)
			{
			if($useImageWithIndex != -1 && $nCounter != $useImageWithIndex) {
				$nCounter++;
				continue;
				}

			$img = $confDBField;

			if(!isset($confDBField['path'])) $confDBField['path'] = $this->_getUploadFolder();
			$img["file"] = $confDBField['path'] . $image;
			
			$img["altText"] = $this->getFieldVal('alt_text');
			$img["titleText"] = $this->getFieldVal('title');
			
			if(isset($confDBField['secure']) && $confDBField['secure'] == 1)
				{
				$targetPath = $this->_getUploadFolder(); // Secure-Upload geht immer in den uploads-Ordern
				//$targetPath = 'uploads/' . $this->_uploadFolder . '/';
				//$targetPath = 'typo3temp/pics/' ;

				$imgSource = $confDBField['path'] . $image;
				$imgTarget = 'typo3temp/pics/' . $this->_getSecureFilename($image);
				$imgTarget =  $targetPath . $this->_getSecureFilename($image);
				//$cmd['data'] = PATH_site . $imgSource;
				//$cmd['target'] = 'uploads/' . $this->_uploadFolder . '/';
				//$cmd['altName'] = false;

				// MD5 - Check ist hier die einzig wahre L�sung!
				$fileMD5Source =  (file_exists(PATH_site . $imgSource) ? md5_file(PATH_site . $imgSource) : "0");
				$fileMD5Target =  (file_exists(PATH_site . $imgTarget) ? md5_file(PATH_site . $imgTarget) : "0");
				
				if($fileMD5Source != $fileMD5Target) // !file_exists(PATH_site . $imgTarget) - nicht mehr notwendig
					{
					//debug(PATH_site . $imgSource . "-" . $fileMD5Source,1);
					//debug(PATH_site . $imgTarget . "-" . $fileMD5Target,1);
					if(!copy(PATH_site . $imgSource,PATH_site . $imgTarget)) die("Copy failed - SRC: $imgSource, TARGET: $imgTarget");
					
					//global $FILEMOUNTS, $TYPO3_CONF_VARS,$BE_USER;
					//$file = t3lib_div::makeInstance('t3lib_extFileFunctions');
					//$file->init($FILEMOUNTS, $TYPO3_CONF_VARS['BE']['fileExtensions']);
					//$file->init_actionPerms($BE_USER->user['fileoper_perms']);

					//$ret = $file->func_copy($cmd);
					//debug($BE_USER);
					//debug($TYPO3_CONF_VARS['BE']['fileExtensions']);
					}
				$img["file"] = $targetPath  . $this->_getSecureFilename($image);
				}
			//debug($GLOBALS["TSFE"]->fe_user->getKey("ses","image"));
			//$GLOBALS["TSFE"]->fe_user->setKey("ses","image", $img);
			//debug(session_id());

			//srand ((double)microtime()*1000000);
			//$randval = rand();
			//debug($randval);
			//$randval = rand();
			//setcookie ("TestCookie", $randval);
			//debug("CO" . $_COOKIE['PHPSESSID']);

			//t3lib_div::debug($img);
			if($this->isImage($img['file']) && $useMIMEImage == 0) {
				$strIMG = $this->cObj->IMAGE($img);
			} else {
				$strIMG = $this->getMIMEImage($img['file']);
				
				if($this->isImage($img['file']) && ($confDBField['linkWrap'] || $confDBField['imageLinkWrap.'])) {
					if ($confDBField['linkWrap'])  {
						$strIMG = $this->cObj->linkWrap($strIMG,$confDBField['linkWrap']);
					} elseif ($confDBField['imageLinkWrap']) {
						$strIMG = $this->cObj->imageLinkWrap($strIMG,$img['file'],$confDBField['imageLinkWrap.']);
					}	
							
				} 
			}
			
			//t3lib_div::debug($strIMG,'$strIMG');
			
			$strContent .= ('<span' . $this->pi_classParam('image ' . $this->pi_getClassName('image-' . $nCounter)) . '>' .
				$strIMG . '</span>');

			$nCounter++;
			}

		return $strContent;
		}

	/**
	 * Returns an IMG-Tag which represents the MIME-Typ of the file (Word-Icon for application/msword)
	 * 
	 * Sample:
	 * 	If the MIME-Type ist application/msword the following files are possible. (Under these rules will be searched)
	 * 		application_msword.gif
	 * 		msword.gif
	 * 		application.gif
	 * 		unknown.gif
	 *
	 * @param	[string]		$filename: Filename which should be represented by the returned icon

	 * @return	[string]	Blank String if noch represetation is found - otherwise the IMG-Tag for the icon
	 */
	function getMIMEImage($filename) {
		
		$imageTag = '';
		$aFileName = t3lib_div::split_fileref($filename);
		
		if(!isset($aFileName['realFileext'])) return $imageTag;
		
		$mimetype = $this->getMimeType($filename);
		//debug($mimetype);
		
		$iconsetPath = 'EXT:' . $this->extKey . $this->internal['iconset_path_mimetypes'];
		if($this->conf['iconset_mimetypes'] && file_exists(PATH_site . $this->conf['iconset_mimetypes'])) {
			$iconsetPath =  $this->conf['iconset_mimetypes'];
		}
		$iconsetExtension = $this->conf['iconset_extension'] ? $this->conf['iconset_extension'] : '.gif';
		
		$imageIcon[0] 			= $iconsetPath . $mimetype[0] . '_' . $mimetype[1] . $iconsetExtension;
		$imageIcon[1] 			= $iconsetPath . $mimetype[1] . $iconsetExtension;
		$imageIcon[2] 			= $iconsetPath . $mimetype[0] . $iconsetExtension;
		$imageIcon[3] 			= $iconsetPath . 'unknown' . $iconsetExtension;
		
		//debug($imageIcon);
		
		foreach($imageIcon as $filename) {
			$imageTag = $this->cObj->fileResource($filename);	
			if($imageTag != '') break;
		}
		
		return $imageTag;
	}
	
	
	/**
	 * Makes the right links to an eMail-Address or a webpage
	 * If the link shows the text "more..." - you can overwrite this value
	 * in the language-file
	 *
	 * @param	[string]		$fieldname: Fieldname from the DB-Table
	 * @param	[string]		$content: The content from the DB-Field
	 * @param	[string]		$confDBField: Configurationsettings from TS
	 * @return	[string]	The processed content
	 */
	function _getLinkContent($fieldname,$content,$confDBField)
		{
		$isEMail = (strstr($content,'@') == true);
		$isPage = (preg_match('#\d+#',$content) == true);

		$strContent = $content;
		if($isEMail && strstr($content,'mailto:') == false)
			{
			$confDBField['makelinks'] = 1;

			$strContent = 'mailto:' . $content;
			$strContent = $this->cObj->parseFunc($strContent,$confDBField);
			}
		else if($isPage)
			{
			$confDBField['makelinks'] = 1;
			$confDBField['parameter'] = $strContent;
			$strContent = $this->cObj->typoLink($this->pi_getLL("continue_on_page","more..."),$confDBField);
			}
		else if(strlen(trim($content)) > 0) // External URL - only if the content is not blank
			{
			$defaultProtocol = 'http://';
			$confDBField['makelinks'] = 1;
			$confDBField['parameter'] = $strContent;		// for the typolink function
			$confDBField['protocol'] = isset($confDBField['protocol']) ? $confDBField['protocol'] : $defaultProtocol;

			$strTarget = isset($confDBField['target']) ? $confDBField['target'] : '_blank';
			$strLink = $content;
			if(preg_match('#(.*\.\D{2,4})/(.*)#',$content,$aTreffer))
				{
				$strLink 	= $aTreffer[1];
				$strTarget 	= $aTreffer[2];
				}
			if(strstr($strLink,$confDBField['protocol']) == false)
				{
				$strLink 					= $confDBField['protocol'] . $strLink;
				$content					= $confDBField['protocol'] . $content;
				//$confDBField['parameter'] 	= $confDBField['protocol'] . $strLink;
				}

			$confDBField['makelinks.']['http.']['extTarget'] = $strTarget;
			
			// Just a code sample
			// $confDBField['makelinks.']['http.']['wrap'] = "[ | ]";
			// it does the same as: (TS-Code)
			// plugin.tx_mmreflist_pi1.typodbfield.web.makelinks.http.wrap = [> | <]

			if(isset($confDBField['typolink']) && $confDBField['typolink'] == 1)
				{
				//$strLink = (isset($confDBField['linktext']) ? $confDBField['linktext'] : $this->pi_getLL("continue_on_page","more..."));
				$strLinkText = $strLink;
				if(isset($confDBField['labelStdWrap.']))
					{
					$strLinkText = $this->cObj->stdWrap($strLink,$confDBField['labelStdWrap.']);
					}

				$strContent = $this->cObj->typoLink($strLinkText,$confDBField);
				}
			else {
				$strContent = $this->cObj->parseFunc($content,$confDBField);
				}
			}

		return $strContent;
		}

					
	/**
	 * Makes a link from the listView to the singleView 
	 *
	 * @param	[string]		$fieldname: Fieldname from the DB-Table
	 * @param	[string]		$content: The content from the DB-Field
	 * @param	[string]		$confDBField: Configurationsettings from TS
	 * @return	[string]	The processed content
	 */
	function _getSingleLinkContent($fieldname,$content,$confDBField)
		{
		$strTextToShow 	= $content;
		$strLabelText		= null;
		$strLabelImage	= null;
		$strLinkContent = '';
		
		if(isset($confDBField['labelStdWrap.']))
			{
			$strLabelText = $this->cObj->stdWrap($strLabelText,$confDBField['labelStdWrap.']);
			}
			
		if(isset($confDBField['labelImage']) && $confDBField['labelImage'] == 'IMAGE')
			{
			$strLabelImage = $this->_getImageContent($fieldname,$content,$confDBField['labelImage.']);
			}
			
		$strTextToShow = ($strLabelText != null ? $strLabelText : $strTextToShow);
		$strTextToShow = ($strLabelImage != null ? $strLabelImage : $strTextToShow);
		
		// If there is no text or image to put a anocor-tag around 
		// return an empty string
		if(!strcmp($strTextToShow,'')) return $strLinkContent;
		
		$singlePid = 0;
		if(isset($this->conf['singlePid']) && $this->conf['singlePid'] != '') $singlePid = $this->conf['singlePid'];

		// The "1" means that the display of single items is CACHED! Set to zero to disable caching.	
		$strLinkContent = $this->pi_list_linkSingle($strTextToShow,
			$this->internal['currentRow']['uid'],
			$this->allowCaching,
			array(),false,$singlePid);

		return $strLinkContent;
		}
		
	/**
	 * Makes the links to a file (PDF,...)
	 *
	 * @param	[string]		$fieldname: Fieldname from the DB-Table
	 * @param	[string]		$content: The content from the DB-Field
	 * @param	[string]		$confDBField: Configurationsettings from TS
	 * @return	[string]	The processed content
	 */
	function _getFileLinkContent($fieldname,$content,$confDBField)
		{
		$aPDFFiles 		= split(',',$content);
		$strContent 	= '';

		if(!isset($aPDFFiles[0]) || strlen($aPDFFiles[0]) == 0) return $strContent;

		$confDBField['path'] = str_replace('$pluginname',$this->_uploadFolder,$confDBField['path']);
		$confDBField['makelinks'] = 1;
		foreach($aPDFFiles as $file)
			{
			$strFileLink 	= '';
			//$confDBField['labelStdWrap']['data'] = "TEst";

			$strFileLink = $this->cObj->filelink($file,$confDBField);
			if($strFileLink == '')
				{
				$confDBField['path'] = $confDBField['path2'];
				$strFileLink = $this->cObj->filelink($file,$confDBField);
				}
			$strContent .= $strFileLink;
			}
		return $strContent;
		}

	/**
	 * The filename is changed to MD5-String
	 *
	 * @param	[string]		$imageName: Filename
	 * @return	[string]	The path of the file as it was before, the filebody converted to a MD5 String
	 */
	function _getSecureFilename($imageName)
		{
		$aFileName = t3lib_div::split_fileref($imageName);
		$aFileName['filebody'] = $this->_secureFilePrefix . rawurlencode(t3lib_div::shortMD5($aFileName['filebody']));

		return $aFileName['path'] . $aFileName['filebody'] . '.' .  $aFileName['fileext'];
		}
		
	/**
	 * Returns the relativ (DOCROOT) uploadfolder of the current Extension
	 *
	 * @param	[string]		$imageName: Filename
	 * @return	[string]	The path of the file as it was before, the filebody converted to a MD5 String
	 */
	function _getUploadFolder() {
		return 'uploads/' . $this->_uploadFolder . '/';
		}
	
	/**
	 * Call by the framewort (pi_list_makelist). It processes the data from a DB record.
	 * It is used for the ListView and uses the template which is specified for this view.
	 * The function calls getFieldContent to get the processed data for the fields.
	 * After that everything ist packed in the template.
	 *
	 * With this function and the right Template you can vary the number of cols in your listView
	 *
	 * @param	[string]		$imageName: Filename
	 * @param	[string]		$fGetEmptyContents:
	 * @param	[string]		$templateSubPart: 
	 * @param	[array]			$fieldsToAvoid: Avoid Recursion 
	 * @param	[integer]		$colNumber: Current Col - Count starts at 1, negativ value means empty row
	 * 
	 * @return	[string]	The path of the file as it was before, the filebody converted to a MD5 String
	 */
	function _getColContents($nTableRowNumber,$fGetEmptyContents = false,$templateSubPart = '###LIST_COL###',$fieldsToAvoid = null,$colNumber = 1,$ignoreRecurseCheck = false)	{
		//$strTemplateName	= $this->getTemplateName('listView');
		if($this->_recurse == true && $ignoreRecurseCheck == false) return '';
		$this->_recurse = true;
		
		$dataBuffer				= array();
		$editPanel 				= $this->pi_getEditPanel();

		if ($editPanel)	$editPanel="<TD>".$editPanel."</TD>";

		$nFieldCounter = 0;
		$aFieldsToDisplay = array_merge($this->internal['currentRow'],$this->_dummyFieldList);

		#foreach($this->internal['currentRow'] as $key => $value)
		foreach($aFieldsToDisplay as $key => $value) {

			if($fieldsToAvoid != null && is_array($fieldsToAvoid) && in_array($key,$fieldsToAvoid)) {
				//t3lib_div::debug($key,'fieldToAvoid');
				continue;
			}
			
			if(isset($dataBuffer[$key])) { 
				$strContent = $dataBuffer['key'];
				//t3lib_div::debug($key,'$dataBuffer');
			}
			else {
				$strContent = ($fGetEmptyContents == false ? $this->getFieldContent($key) : '');
				$dataBuffer[$key] = $strContent;
			}
			
			$strFieldLabel  = $this->getLLabel($key,'(translate:' . $key . ')');
			
			// Wenn am Anfang und am Ende des Feldnamens ein [ bzw. ] steht dann ist das normalerweise der interne Name (internes Feld)
			if(preg_match('#^\[.*\]$#',$strFieldLabel)) {
				continue;
			}

			// Marks the current col - count starts with 1
			$markerArray['###COLCLASS###']	= $this->pi_classParam('col' . $colNumber .
				' ' . $this->pi_getClassName('col') . 
				($colNumber < 0 ? ' ' . $this->pi_getClassName('col-empty')  : ''));
			
			// Die beiden Felder werden auf den selben Wert gezogen da damit
			// entweder eine Tabelle erstellt werden kann die immer die Selben Zeilen verwendet
			// sowie eine Tabelle die ein individuelles Layout hat
			$markerArray['###LABEL###']	= '<div'.$this->pi_classParam('label ' . 'label_' . $key).'>' .
				$strFieldLabel . '</div>';

			$markerArray['###LABEL_' . strtoupper($key) . '###'] = $strFieldLabel;			

			$markerArray['###LABEL_' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('label_' . $key . ' label');

			$markerArray['###FIELD_' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('field_' . $key . ' field');
			
			// Makes an array-entry for a specific field name
			// Example for tablefield "name"
			// ###NAME_CLASS###
			// ###NAME###
			$markerArray['###' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam($key . ' value ' . $this->pi_getClassName($key));

			$markerArray['###' .  strtoupper($key) . '###']	= $strContent ;

			$markerArray['###FIELD_' .  strtoupper($key) . '###']	= 
				'<span'.$this->pi_classParam($key).'>' .$strContent . '</span>';
				
			// Makes an array-entry for a numberd alternative
			// Example for tablefield "name" wich is the third table field
			// ###FIELD3_CLASS###
			// ###FIELD3###
			// ###FIELD3_NAME###
			// ###FIELD3_NUMBER###
			$markerArray['###FIELD' .  $nFieldCounter . '_CLASS###']	= $this->pi_classParam('field' . $nFieldCounter);

			$markerArray['###FIELD' .  $nFieldCounter . '###']	= 
				'<span'.$this->pi_classParam('field' . $nFieldCounter).'>' . $strContent . '</span>';

			$markerArray['###FIELD' .  $nFieldCounter . '_NAME###']	= strtoupper($key);

			$markerArray['###FIELD' .  $nFieldCounter . '_NUMBER###']	= $nFieldCounter;
			
			$nFieldCounter++;
			}
		
		$markerArray['###SUBTABLE1CLASS###'] 	= $this->pi_classParam("subtable1");
		$markerArray['###SUBTABLE2CLASS###'] 	= $this->pi_classParam("subtable2");
		$markerArray['###SUBTABLE3CLASS###'] 	= $this->pi_classParam("subtable3");

		$markerArray['###FOOTERCLASS###']			= $this->pi_classParam('listView-footer');

		$markerArray['###EDITPANEL###'] 			= $editPanel;

		//debug($strTemplateName);
		//debug($markerArray);
		//---------------------------------
		$template 			= $this->getTemplateContent($this->getViewType());
		$templateFieldCol 	= $this->cObj->getSubpart($template,$templateSubPart);
		//t3lib_div::debug($templateFieldCol,$templateSubPart . ' / $templateFieldCol=');
		
		$this->_recurse = false;
		return $this->cObj->substituteMarkerArray($templateFieldCol,$markerArray);
	}

	/**
	 * Builds the content for the listView
	 *
	 * @param	[string]		$res: Resource-ID from the DB-Query
	 * @param	[string]		$tableParams: Additional params for the table
	 * @return	[string]	The HTML-Code for the listView
	 */
	function pi_list_makelist($res,$tableParams='')
			{
			$lConf 						= $this->conf[$this->getViewType() . '.'];

			//$strTemplateName	= $this->getTemplateName('listView');;

			// Make list table header:
			$tRows = array();
			$this->internal['currentRow']='';
			$tRows[] = $this->pi_list_header();

			$template 			= $this->getTemplateContent($this->getViewType());
			$templateFieldRow 	= $this->cObj->getSubpart($template,'###LIST_ROW###');

			// Make list table rows
			$nNumberOfCols = $this->internal["colsOnPage"];
			$nTableRowNumber = 0;
			$nTableColCounter = 0;
			$nDBRowCounter = 0;
			$tempRow = '';
			$tempFieldList = null;

			$this->_resetDummyFieldList();
			while($this->internal['currentRow'] = $this->_fetchData($res))
				{
				$this->mmlib_cache->resetBuffer();
				
				//t3lib_div::debug($this->internal['currentRow'],'currentRow');
				//t3lib_div::debug($this->internal['currentRow']['fe_group'],'currentRow');
				
				if(!$this->isThisRecordValid($this->internal['currentRow'])) continue;
				
				$listrowclass							= $this->pi_getClassName('listrow') . ' ' . $this->pi_getClassName($this->getViewType());
				$markerArray['###ROWCLASS###'] 			= ($nTableRowNumber % 2 ? $this->pi_classParam("listrow-odd " . $listrowclass) : $this->pi_classParam("listrow-even " . $listrowclass));
				$markerArray['###ROWCLASS2###'] 		= ($nTableRowNumber % 2 ? $this->pi_classParam("listrow2-odd " . $listrowclass) : $this->pi_classParam("listrow2-even " . $listrowclass));
				
				$tempRow .= $this->_getColContents($nDBRowCounter,false,'###LIST_COL###',null,$nTableColCounter + 1);
				$nTableColCounter++;
				$nDBRowCounter++;

				// Save the fieldnames for finishing the table-cols (save the array-fields)
				// Needed if the number of records does not fit with the table-cols
				if($tempFieldList == null) $tempFieldList = $this->internal['currentRow'];

				if($nTableColCounter >= $nNumberOfCols)
					{
					$templateRow = $this->cObj->substituteMarkerArray($templateFieldRow,$markerArray);
					$tRows[] = $this->cObj->substituteSubpart($templateRow,'###LIST_COL###',$tempRow);

					$tempRow = '';
					$nTableColCounter = 0;
					$nTableRowNumber++;
					}
				}

			//t3lib_div::debug(count($tRows),'count($tRows)'); 
			//t3lib_div::debug($tRows,'$tRows');
				
			// Finish Table Structure
			if($nTableColCounter < $nNumberOfCols && $nTableColCounter != 0) {
				// makes a "dummy" currentRow
				$this->internal['currentRow'] = $tempFieldList;
				$this->mmlib_cache->resetBuffer();
					
				for(;$nTableColCounter < $nNumberOfCols;$nTableColCounter++)
					{
					// true - means empty value in Array-Structure
					$tempRow .= $this->_getColContents($nDBRowCounter++,true,'###LIST_COL###',null,0 - ($nTableColCounter + 1));
					}
						
				$templateRow = $this->cObj->substituteMarkerArray($templateFieldRow,$markerArray);
				$tRows[] = $this->cObj->substituteSubpart($templateRow,'###LIST_COL###',$tempRow);
				}
				
			//t3lib_div::debug(count($tRows),'count($tRows)'); 
			//t3lib_div::debug($tRows,'$tRows');
			

			$debuginfo = '';
			$templateDebugInfo = trim($this->cObj->getSubpart($template,'###DEBUGINFO###'));
			if(strlen($templateDebugInfo) > 0 && is_array($tempFieldList))
				{
				$markerArray['###ALLFIELDNAMES###'] 	= implode(', ',array_keys($tempFieldList));
				$debuginfo = $this->cObj->substituteMarkerArray($templateDebugInfo,$markerArray);
				}
				
			$out = '
			<!--
			Record list:
			-->
			<div'.$this->pi_classParam('listrow').'>' . 
			($lConf['avoidTableTagAroundContent'] == 1 ? '' : '<' . trim('table '.$tableParams) . '>') . 
			implode('',$tRows) . 
			($lConf['avoidTableTagAroundContent'] == 1 ? '' : '</table>') . 
			$debuginfo . 
			'</div>';

			return $out;
			}

	/**
	 * Generates the header for the listView
	 *
	 * @return	[string]	HTML-Header block
	 */
	function pi_list_header()	{
		$lConf 		= $this->conf["listView."];
		$content	= '';
		//$strTemplateName	= $this->getTemplateName('listView');;

		// Header soll nicht angezeigt werden
		if(isset($lConf['showHeader']) && $lConf['showHeader'] == 0) return $content;

		$aFields 					= $GLOBALS['TYPO3_DB']->admin_get_fields($this->getTableName());
		$aFields					= $this->_addDummyFields($aFields);
		$nFieldCounter		= 0;
		
		foreach($aFields as $key => $value)
			{
			// Examples for tablefield "name" wich is field number 3
			
			// ###HEADER_NAME###
			$markerArray['###HEADER_' .  strtoupper($key) . '###']	= $this->getFieldHeader($key);
			// Old Version
			// '<div'.$this->pi_classParam('header_' . $key).'>' .	$this->getFieldHeader($key) . '</div>';

			// ###HEADER_NAME_CLASS###
			$markerArray['###HEADER_' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('header_' . $key);

			// ###HEADER3###
			$markerArray['###HEADER' .  $nFieldCounter . '###']	= $this->getFieldHeader($key);

			// ###HEADER3_CLASS###
			$markerArray['###HEADER' .  $nFieldCounter . '###']	= $this->pi_classParam('header_' . $key);

			// ###HEADER3_NAME### - will be substituted with 'name'
			$markerArray['###HEADER' .  $nFieldCounter . '_NAME###']	= $key;

			// ###HEADER3_NUMBER### - will be substituted with '3'
			$markerArray['###HEADER' .  $nFieldCounter . '_NUMBER###']	= $nFieldCounter;
				
			$nFieldCounter++;
			}
		$markerArray['###HEADERCLASS###'] = $this->pi_classParam("listheader");

		$template = $this->getTemplateContent($this->getViewType());
		$templateHeader = $this->cObj->getSubpart($template,'###LIST_HEADER###');

		$content = $this->cObj->substituteMarkerArray($templateHeader,$markerArray);
		//debug($strOutput);

		return $content;
		}

	/**
	 * Generates the HTML-Code for the OrderMode Selector
	 * The description-text for the order field is taken from locallang.php
	 * 		Fieldname: <name>_orderselector-desc-<desc-flag>
	 * 
	 * @return 	[string] The HTML-Code for the modeselector
	 */
	function getOrderSelector() {
		//$this->internal['orderselector']
		//$conf			= $this->conf[$this->getViewType() . '.'];
		//$conf			= $this->conf;
		$template 		= $this->getTemplateContent($this->getViewType());
		$templateOS		= $this->cObj->getSubpart($template,'###ORDER_SELECTOR###');
		$templateSEL	= $this->cObj->getSubpart($templateOS,'###SELECTOR###');
		$content		= '';
		$elements		= array();
		
		if(!isset($this->internal['orderselector']) || count($this->internal['orderselector']) == 0) return $content;
		
		$rowNumber = 0;
		foreach($this->internal['orderselector'] as $key) {
			$arryLinks										= array('order' => $key['field'] . ':' . $key['desc_flag']);
			
			$descritionEntry								= $key['field'] . '-orderselector-desc-' . ($key['desc_flag'] ? 'down' : 'up');
			$linkText										= $this->pi_getLL($descritionEntry,"Switch sort order of '" . $key['field'] . "' to " . ($key['desc_flag'] ? 'descending' : 'ascending'));
			$imagenameBase									= $key['field'] . '-orderselector' . ($key['desc_flag'] ? '-down' : '-up') . '.jpg';
			$linkImage										= $this->getImageFromIconset($imagenameBase,'iconset_navigation','/pi1/res/images/default/22x22/navigation/','alt="" title="' . $linkText . '"');
			if($linkImage == '') $linkImage					= $imagenameBase . ' not found...';
			
			$markerArray['###SELECTOR_ELEMENT###'] 			= $this->pi_linkTP_keepPIvars($linkText,$arryLinks,$this->allowCaching);	
			$markerArray['###SELECTOR_ELEMENT_IMG###']		= $this->pi_linkTP_keepPIvars($linkImage,$arryLinks,$this->allowCaching);	
			$markerArray['###SELECTOR_ELEMENT_CLASS###'] 	= ($rowNumber % 2 ? $this->pi_classParam("selector-element-odd selector-element-n" . $rowNumber) : $this->pi_classParam("selector-element-even selector-element-n" . $rowNumber)) ;
			
			$elements[] = $this->cObj->substituteMarkerArray($templateSEL,$markerArray);
			
			$rowNumber++;
		}
		
		unset($markerArray);
		$markerArray['###ORDERSELECTORCLASS###'] 	= $this->pi_classParam('orderselector');	
		$templateOS = $this->cObj->substituteMarkerArray($templateOS,$markerArray);
		
		$content = $this->cObj->substituteSubpart($templateOS,'###SELECTOR###',implode('',$elements));
		return $content;
	}
	
	/**
	 * Generates and returns the HTML-Code for the VIEW-Selector
	 */
	function getViewSelector() {
		$conf			= $this->conf[$this->getViewType() . '.'];
		$template 		= $this->getTemplateContent($this->getViewType());
		$templateOS		= $this->cObj->getSubpart($template,	'###VIEW_SELECTOR###');
		$content		= '';
		$elements		= array();
		
		if(!isset($this->internal['viewselector']) || count($this->internal['viewselector']) == 0) return '';
		
		$rowNumber = 0;
		foreach($this->internal['viewselector'] as $key => $value) {
			$selectorName	= '###SELECTOR_' . strtoupper($key) . '###';
			$templateSEL	= $this->cObj->getSubpart($templateOS,$selectorName);
			//t3lib_div::debug("Key: " . $key,1); // list, toplist, tree
			//t3lib_div::debug("Value: " . $value,1);
			
			$arryLinks										= array($this->prefixId . '[viewmode]' => $key);

			// gets the description of the button either from
			// locallang.php
			$imagenameBase									= $key .'-viewselector.jpg';
			$descritionEntry								= $key . '-viewselector-desc';
			$linkText										= $this->pi_getLL($descritionEntry,$value);
			$linkImage										= $this->getImageFromIconset($imagenameBase,'iconset_navigation','/pi1/res/images/default/22x22/navigation/','alt="" title="' . $linkText . '"');
			if($linkImage == '') $linkImage					= $imagenameBase . ' not found...';
			$markerArray['###SELECTOR_ELEMENT_IMG###']		= $this->pi_linkTP($linkImage,$arryLinks,$this->allowCaching);	
			
			$markerArray['###SELECTOR_ELEMENT###'] 			= $this->pi_linkTP($linkText,$arryLinks,$this->allowCaching);	
			$markerArray['###SELECTOR_ELEMENT_CLASS###'] 	= ($rowNumber % 2 ? $this->pi_classParam("selector-element-odd selector-element-n" . $rowNumber) : $this->pi_classParam("selector-element-even selector-element-n" . $rowNumber));
			
			$element = $this->cObj->substituteMarkerArray($templateSEL,$markerArray);
			$elements[] = $element;
			
			$templateOS = $this->cObj->substituteSubpart($templateOS,$selectorName,$element);
			$rowNumber++;
		}
		
		unset($markerArray);
		$markerArray['###VIEWSELECTORCLASS###'] 	= $this->pi_classParam('viewselector');	
		
		$templateOS = $this->cObj->substituteMarkerArray($templateOS,$markerArray);
		
		//$content = $templateOS;
		
		$content = $this->cObj->substituteSubpart($templateOS,'###VIEWSELECTORELEMENTS###',implode('',$elements));
		return $content;
		
	}
	
	/*
	function getModeSelector() {
		$content = '';
		
		$piVarsToClear = array('sword'=>'');
		
		if(isset($this->internal['modeselector']) && is_array($this->internal['modeselector'])) {
			foreach($this->internal['modeselector'] as $key => $value)
				{
				$this->internal['modeselector'][$key] = $this->pi_getLL('qlist_mode_' . $key,$value);
				}
			$content .= $this->pi_list_modeSelector($this->internal['modeselector'],$piVarsToClear);
			
		}
		return $content;
	}
	*/
	/**
	 * Generates HTML Code for the modeselector
	 * The description-text for the modeselector is taken from locallang.php
	 * 		Sample:	'kategorie2-modeselector-desc' => 'Dog',
	 * 
	 * 		Fieldname: qlist_mode_<key name (list, tree)>
	 * 	 * 
	 * @return 	[string] The HTML-Code for the modeselector
	 */
	function getModeSelector() {
		$conf			= $this->conf[$this->getViewType() . '.'];
		$template 		= $this->getTemplateContent($this->getViewType());
		$templateMS		= $this->cObj->getSubpart($template,	'###MODE_SELECTOR###');
		$templateSEL	= $this->cObj->getSubpart($templateMS,	'###SELECTOR###');
		$content		= '';
		$elements		= array();
		$piVarsToClear 	= array('sword'=>'');

		if(!isset($this->internal['modeselector']) || count($this->internal['modeselector']) == 0) return '';
		
		$itemNumber = 0;
		foreach($this->internal['modeselector'] as $key => $value) {
			$arryLinks										= array_merge(array('mode'=>$key),$piVarsToClear);
			$nameLinkBase									= strtolower(preg_replace('#\W#','',$value));
			$descritionEntry								= $nameLinkBase . '-modeselector-desc';
			//t3lib_div::debug($descritionEntry,1);
			
			$linkTextTemp									= $this->pi_getLL($descritionEntry,htmlspecialchars($value));
			$linkText										= ($this->conf['replace_blank_in_modeselectors'] ? str_replace(' ','&nbsp;',$linkTextTemp) : $linkTextTemp);
			$imagenameBase									= $nameLinkBase .'-modeselector.jpg';
			$linkImage										= $this->getImageFromIconset($imagenameBase,'iconset_navigation','/pi1/res/images/default/22x22/navigation/','alt="" title="' . $linkText . '"');
			if($linkImage == '') $linkImage					= $imagenameBase . ' not found...';
			$markerArray['###SELECTOR_ELEMENT_IMG###']		= $this->pi_linkTP_keepPIVars($linkImage,$arryLinks,$this->allowCaching);  


			$markerArray['###SELECTOR_ELEMENT_TEXT###']		= $linkText;
			$markerArray['###SELECTOR_ELEMENT###'] 			= $this->pi_linkTP_keepPIVars($linkText,$arryLinks,$this->allowCaching); 
			$markerArray['###SELECTOR_ELEMENT_CLASS###'] 	= $this->pi_classParam('modeSelector-SCell modeSelector-SCell-n' . $itemNumber);

			//t3lib_div::debug($arryLinks,'$arryLinks=');
			//t3lib_div::debug($nameLinkBase,'$nameLinkBase=');
			//t3lib_div::debug($key,'$key=');
			//t3lib_div::debug($linkText,'$linkText=');
			//t3lib_div::debug(tslib_pibase::pi_autoCache($arryLinks),'$this->pi_autoCacheEn=');
			
			
			if(preg_match('#href="([^"]*)"#',$markerArray['###SELECTOR_ELEMENT###'],$match)) {
				$markerArray['###SELECTOR_ELEMENT_HREF###'] = $match[1];
			}
			
			$markerArray['###SELECTED###']					= '';
			if(isset($this->piVars['mode']) && $this->piVars['mode'] == $key)  {
				
				$markerArray['###SELECTED###']				= 'selected';
				} 
			
			$elements[] = $this->cObj->substituteMarkerArray($templateSEL,$markerArray);
			
			$itemNumber++;
		}
		
		unset($markerArray);
		$markerArray['###MODESELECTORCLASS###'] 	= $this->pi_classParam('modeselector');	
		$markerArray['###LABEL###'] 				= $this->pi_getLL('label.modeselector');	
		
		$templateMS = $this->cObj->substituteMarkerArray($templateMS,$markerArray);
		
		$content = $this->cObj->substituteSubpart($templateMS,'###SELECTOR###',implode('',$elements));
		return $content;
	}
	
	/**
	 * Generates the modeSelector.
	 * This function was copied from the Typo-Source.
	 * t3lib_div::debug($this->internal['orderselector'],'$this->internal[\'orderselector\']');
	 * 
	 * The only difference ist the caching, which is set from the file setup.txt or the TS-settings
	 * This version works with div's instead of a table
	 */
	function pi_list_modeSelector($items=array(),$piVarsToClear = array())   {
	    $cells	= array();
	    $cache	= ($this->conf['allowCaching'] ? $this->conf['allowCaching'] : $this->pi_isOnlyFields($this->pi_isOnlyFields));
	    
	    reset($items);
	    $itemNumber = 0;
	    while(list($k,$v)=each($items)) {
	    		$cells[] = '<div ' . $this->pi_classParam('modeSelector-SCell modeSelector-SCell-n' . $itemNumber) . '>' . 
	    		$this->pi_linkTP_keepPIvars(htmlspecialchars($v),array_merge(array('mode'=>$k),$piVarsToClear),$cache) . 
	    		'</div>';
	    		$itemNumber++;
	    		/*
	            $cells[]='
	                    <td'.($this->piVars['mode']==$k?$this->pi_classParam('modeSelector-SCell'):'').'><p>'.
	                    $this->pi_linkTP_keepPIvars(htmlspecialchars($v),array('mode'=>$k),$cache).
	                    '</p></td>';
				*/
	    }
	
	    $content = '
	    <!--Mode selector (menu for list): -->
	    <div'.$this->pi_classParam('modeSelector').'>' .
			implode('',$cells).
	    '</div>';
	
	    return $content;
	}	
	
	function getFilterSelector() {
		$conf			= $this->conf[$this->getViewType() . '.'];
		$template 		= $this->getTemplateContent($this->getViewType());
		$templateFS		= $this->cObj->getSubpart($template,	'###FILTER_SELECTOR###');
		$content		= '';
		$elements		= array();
		
		if(!isset($this->internal['filterselector']) || count($this->internal['filterselector']) == 0) return '';
		
		$rowNumber = 0;
		foreach($this->internal['filterselector'] as $key => $value) {
			$templateSEL	= $this->cObj->getSubpart($templateFS,'###FILTERSELECTORELEMENTS###');
			//t3lib_div::debug("Key: " . $key,1); // list, toplist, tree
			//t3lib_div::debug("Value: " . $value,1);
			
			
			$markerArray['###SELECTOR_ELEMENT###'] 			= $value;	
			$markerArray['###SELECTOR_ELEMENT_CLASS###'] 	= ($rowNumber % 2 ? $this->pi_classParam("selector-element-odd selector-element-n" . $rowNumber) : $this->pi_classParam("selector-element-even selector-element-n" . $rowNumber));
			
			$element = $this->cObj->substituteMarkerArray($templateSEL,$markerArray);
			$elements[] = $element;
			
			//$templateOS = $this->cObj->substituteSubpart($templateOS,$selectorName,$element);
			$rowNumber++;
		}
		
		unset($markerArray);
		$markerArray['###FILTERSELECTORCLASS###'] 	= $this->pi_classParam('filterselector');	
		$templateFS = $this->cObj->substituteMarkerArray($templateFS,$markerArray);
		
		//$content = $templateOS;
		
		$content = $this->cObj->substituteSubpart($templateFS,'###FILTERSELECTORELEMENTS###',implode('',$elements));
		//t3lib_div::debug($content,'$content / getFilterSelector');
		return $content;	
	}
	
	/**
	 * Returns a Subtemplate filled with the contents of the dummy-fields
	 * 
	 * @param	[string]	$nameInListTemplate - Name of the subtemplate
	 * 
	 * @return [string] Returns the contents of the subtemplate
	 */
	function getDummyFieldsBlock($nameInListTemplate) {
		// Reset the current row to an empty array because in the "ListView" we have no "current-Row"		
		$this->internal['currentRow'] = array();
		
		// Reinitialize the dummy fields
		$this->_resetDummyFieldList();
		
		// Fill the contents with the dummy fields
		return $this->_getColContents(0,false,$nameInListTemplate);
	}
	
	/**
	 * Adds the dummy-fields from TS (plugin.tx_mmdamfilelist_pi1.typodbfield.dummyfieldlist = ziplink,normallink)
	 * to the fieldlist
	 *
	 * @param	[string]		$aFields: Array of the real DB Table
	 *
	 * @return	[string]	Extended DB-Table Array
	 */
	function _addDummyFields($aFields,$reset = true)
		{
		if($reset) $this->_resetDummyFieldList();
		if($this->_dummyFieldList != null)
			{
			foreach($this->_dummyFieldList as $key => $value)
				{
				// Fakes the Info from $GLOBALS['TYPO3_DB']->admin_get_fields(<tablename>)
				$aFakeFieldInfo = array( 
					'Field' => $key,
					'Type' => 	'tinytext',
					'Null' => '',	
					'Key' => 	'',
					'Default' => 	'',
					'Extra' => ''					
					);

				$aFields[$key] = $aFakeFieldInfo;	
				}
			}	
		return $aFields;
		}
		
	/**
	 * Get the language-specific Description from pi1/locallang.php
	 * If the contents of die fields has square-brackets arround [this is the value]
	 * then the contents will not be displayed.
	 *
	 * @param	[string]		$fieldname: Name of the table field
	 *
	 * @return	[string]	The language specific Name for $fieldname
	 */
	function getFieldHeader($fieldname)	{
		$searchStrings = array($this->extKey . '.','listFieldHeader_');
		$defaultValue	= '(translate: ' . $fieldname . ')';
		
		foreach($searchStrings as $searchString) {
			$searchString	+= $fieldname;
			$result			= $this->pi_getLL($searchString,$defaultValue);
			
			if($result == $defaultValue) {
				$result = $this->getBL($fieldname,-1);
			}
		}
		
		return $result;
	}


	/**
	 * Gets the data array for the current record.
	 * If the dummyfieldlist (Example:  plugin.tx_mmdamfilelist_pi1.typodbfield.dummyfieldlist = ziplink,normallink)
	 * ist set - these fields are also added to the data-array.
	 *
	 * For the rest of the functions it looks like these fields are also part of
	 * the current table.
	 *
	 * @param	[string]		$res: Resource-ID from the DB-Query
	 * @return	[array]		With the record values
	 */
	function _fetchData($res)
		{
		$data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if($data != null && $this->_dummyFieldList != null)
			{
			foreach($this->_dummyFieldList as $key => $value)
				{
				if(!isset($data[$key]))
					{
					$data[$key] = $value;
					}
				}
			}

		return $data;
		}

	/**
	 * Generate the additionals Fields for the recordset
	 *
	 * @return	[void]		
	 */
	function _resetDummyFieldList()
		{
		$this->_dummyFieldList 	= null;
		$lConf					= $this->conf['typodbfield.'];
		$aDummyFields			= null;
		
		// For keeping thing more clean - bccms-subplugins makes its settings one level deeper
		// Example-Normal Plugins: plugin.tx_mmbccms_pi1.typodbfield
		// Example BCCMS SubPlugin: plugin.tx_mmbccms_pi1.news.typodbfield
		if(!isset($lConf) && isset($this->internal['bccms_plugin_name'])) {
			$lConf = $this->conf[$this->internal['bccms_plugin_name'] .'.']['typodbfield.'];
		}

		// Collects all the fields from the typodbfield array, removes the . (dot) and adds it to the dummyField Array
		$aTempDummyFields = array_keys($lConf);
		//t3lib_div::debug($lConf,'$lConf');
				
		foreach($aTempDummyFields as $key) {
			if($key == 'dummyfieldlist') continue;
			$aDummyFields[] = trim(str_replace('.','',$key));
		}		
		// Old Version - dummyfieldlist array becomes obsolete
		//$aDummyFields = isset($lConf['dummyfieldlist']) ? explode(',',$lConf['dummyfieldlist']) : null;
		
		if($aDummyFields != null)
			{
			foreach($aDummyFields as $fieldname)
				{
				$value = '';
				if(isset($lConf[$fieldname . '.']['value'])) $value = $lConf[$fieldname . '.']['value'];
				/*
				if(!isset($this->conf['typodbfield'][$fieldname]['fieldtype'])
					{
					$this->conf['typodbfield'][$fieldname]['fieldtype'] = 'stdwrap';
					}
				*/
				$this->_dummyFieldList[$fieldname] = $value;
				}
			}
		}

	/**
	 * Basefunction for generating the conten for a specific field from
	 * the current recordset (or dummy-field)
	 *
	 * @param	[string]		$fieldname: Name of the table field
	 * @return	[string]	Processed content of the field
	 */
	function getFieldContent($fieldname)	
		{
		$content = '';
		switch($fieldname) 
			{
			case 'uid':
				$singlePid = 0;
				if(isset($this->conf['singlePid']) && $this->conf['singlePid'] != '') $singlePid = $this->conf['singlePid'];
			
				$content = $this->pi_list_linkSingle($this->getUnmodifiedFieldContent($fieldname)
					,$this->getUnmodifiedFieldContent('uid'),
					$this->allowCaching,
					array(),false,$singlePid);	// The "1" means that the display of single items is CACHED! Set to zero to disable caching.
			break;

			default:
				$content = $this->getAutoFieldContent($fieldname,$this->getUnmodifiedFieldContent($fieldname));
			break;
			}
		return $content;
		}

		
	/**
	 * Gets the data from the DB-Table
	 *
	 * @param	[string]	$fieldname: Name of the table field
	 * @return	[string]	Fieldcontent
	 */
	function getUnmodifiedFieldContent($fieldname) {
		if(isset($this->internal['currentRow'][$fieldname])) {
			$content = $this->internal['currentRow'][$fieldname];
		} elseif(isset($this->_dummyFieldList[$fieldname])) {
			$content = $this->_dummyFieldList[$fieldname];
		}
		
		return $content;
	}
	
	/**
	 * Normaly the same as for getFieldContent (listView) but maybe you need some special thing in the 
	 * singleView
	 *
	 * @param	[type]		$fieldName: Name of the table field
	 * @return	[string]	Processed content of the field
	 */
	function getSingleViewFieldContent($fieldName)	{
		return $this->getFieldContent($fieldName);
		}

	/**
	 * Builds the content for the listView.
	 *
	 * @param	[string]		$content: Basecontent from the plugins main function
	 * @return	[string]	HTML-Code
	 */
	function listView($content)
		{
		$lConf 					= $this->conf['listView.'];	// Local settings for the listView function
		$strTableClassName		= ($lConf['tableClassName']  ?  $lConf['tableClassName']  :  'table');
		$template 				= $this->getTemplateContent($this->getViewType());
		
		$this->internal['showBrowserResults'] = $lConf['showBrowserResults'];
		$this->setViewType('listView');
		
		// For example - modeselector
		$this->initInternalVars($this->getViewType());

		// A single element should be displayed - change to singleView
		if ($this->piVars['showuid'])
			{
			$this->internal['currentRow'] = $this->initCurrentRow($this->piVars["showuid"]);
			
			$content = $this->singleView($content);
			return $content;
			}

		// Makes default-settings for the modeselector
		if (!isset($this->piVars['viewmode']) || !isset($this->internal['modeselector'][$this->piVars['mode']]))
			{
			reset($this->internal['modeselector']);	// Use first element of the modeselector-array
			$this->piVars['viewmode']= key($this->internal['modeselector']);
			}

		// Makes the switch from Page1 to Page2
		if (!isset($this->piVars['pointer']))	$this->piVars['pointer'] = 0;

		// Switch to the first page if mode changes
		if($this->piVars['mode'] != $this->piVars['oldmode']) $this->piVars['pointer'] = 0;
		$this->piVars['oldmode'] = $this->piVars['mode'];

		// Initializing the query parameters like results_at_a_time, maxPages, colsOnPage aso.
		$this->setInternalQueryParameters('listView');

		$strWhereStatement = '';

		// Languagespecific!!
		$langSelectConf = $this->generateLangSpecificSelectConf();
		$strWhereStatement .= ($langSelectConf['where'] ? 'AND ' . $langSelectConf['where'] : '');

		// Create a special filter from TS
		if(is_array($lConf['filter.']))
			{
			foreach($lConf['filter.'] as $key => $value)
				{
				if($key == 'calendarweek' && $value == 'true') $value = date("W");
				$strWhereStatement .= "AND $key = '$value'";
				}
			}

		// Create WHERE Statement based on the things from the search field (if any...)
		if(isset($this->piVars["sword"]))
			{
			$searchTerm = $this->piVars["sword"];
			
			$swords = split(' ',$searchTerm);
			if(preg_match_all('/"[^"]+"|[^ ]+/',$searchTerm, $Tokens)) {
				$swords = array();
				//t3lib_div::debug($Tokens,0);
				foreach($Tokens[0] as $token) {
					$token = str_replace('"','',$token);
					if(strstr($token,'/')) {
						$swords[] = str_replace('/','\_',$token);	
					} else $swords[] = $token;
				}
				//t3lib_div::debug($swords,1);
			}
			$searchFields = split(',',$this->internal['searchFieldList']);
			
			$tempStatement = null;
			foreach($swords as $sword) {
				foreach($searchFields as $sfield) {
					$sword 		= trim($sword);
					$sfield 	= trim($sfield);
					$htmlsword 	= htmlentities($sword);
					
					$tempStatement[] = "$sfield LIKE '%$sword%' ";
					if($htmlsword != $sword) {
						$tempStatement[] = "$sfield LIKE '%$htmlsword%' ";
						}
					}
				}
			}
			
			if($tempStatement != null) {
				$strWhereStatement = ' AND (' . join(' OR ',$tempStatement) . ') ';
			}
		//}
		

		
		// Get number of records:
		if($this->isShowEmptyPageTurnedOn() == false) {
			$res = $this->execQuery(1,$strWhereStatement);
			if($res == false) return $this->pi_getLL('error_emty_query','');
		
			// If the query contains a "group by" then more then one results are possible
			$numberOfResults = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			if($numberOfResults == 1) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				list($this->internal['res_count']) = $row;
			
			// Groups
			} else $this->internal['res_count'] = $numberOfResults;
			
			
			/*
			$this->internal['res_count'] = 0;
			$counter = 0;
  
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
				list($numberingroup) = $row;
				$this->internal['res_count'] += $numberingroup;
				t3lib_div::debug($numberingroup,'$numberingroup');
				$counter++;
			}
			t3lib_div::debug($strWhereStatement,'$strWhereStatement');
			t3lib_div::debug($counter,'$counter');
			t3lib_div::debug($this->internal['res_count'],'$this->internal[res_count]');
			*/
			
			// ATTENTION: Dont stop the process here!!!
			//if($this->internal['res_count'] == 0) return $this->pi_getLL('error_emty_query','');
			// Make listing query, pass query to SQL database:
			$res = $this->execQuery(0,$strWhereStatement);
			if($res == false) {
				return $this->pi_getLL('error_emty_query','');
				}
		}

		// Put the whole list together:
		$content='';	// Clear var;
		//$content.=t3lib_div::view_array($this->piVars);	// DEBUG: Output the content of $this->piVars for debug purposes. REMEMBER to comment out the IP-lock in the debug() function in t3lib/config_default.php if nothing happens when you un-comment this line!

		// surround Selectors with a div
		$content .= '<div ' . $this->pi_classParam('selector-box') . '>';
		
			// can be disabled bei deselecting all the available views
			$content .= $this->getViewSelector();
	
			// Adds the mode selector.
			if($lConf['showModeSelector'] == 1) {
				$content .= $this->getModeSelector();
			}
			
			if($this->conf['showOrderSelector'] == 1) {
				$content .= $this->getOrderSelector();	
			}
	
			if($this->conf['show_filter'] == 1) {
				$content .= $this->getFilterSelector();
			}
	
			// Adds the search box:
			if($lConf['showSearchBox'] == 1) {
				//t3lib_div::debug($this->conf,'conf');
				if(isset($lConf['use_alternate_searchbox']) && $lConf['use_alternate_searchbox'] == 1) {
					$content .= $this->alternateSearchBox();
				} else $content .= $this->pi_list_searchBox(); 
			}
		
		$content .= '</div>';
		
		// Adds the whole list table
		// Bei der erzeugeten Tabelle wird auch der Klassenname angehaengt
		if($this->isShowEmptyPageTurnedOn() == false) {
			$content .= $this->getDummyFieldsBlock('###PRE_LIST_ROW###');
			$content .= $this->pi_list_makelist($res,'border="0" cellspacing="0" cellpadding="0"' . $this->pi_classParam($strTableClassName));
			$content .= $this->getDummyFieldsBlock('###POST_LIST_ROW###');
		}
		
		// Adds the result browser:
		// Param 1:
   		// If set to 0: only the result-browser will be shown
		//           1: (default) the text "Displaying results..." and the result-browser will be shown.
		//           2: only the text "Displaying results..." will be shown
		// Param 2:
		// tableParams - Attributes for the table tag which is wrapped around the table cells containing the browse links
		// Param 3:
		// Array with elements to overwrite the default $wrapper-array.
		// This is nessecary to get the values from locallang.php
		if($this->internal['showBrowserResults'] == 1 && $this->isShowEmptyPageTurnedOn() == false) { 
			$wrapArr = array(
				'browseBoxWrap' => '<div class="browseBoxWrap">|</div>',
				'showResultsWrap' => '<div class="showResultsWrap">|</div>',
				'browseLinksWrap' => '<div class="browseLinksWrap">|</div>',
				'showResultsNumbersWrap' => '<span class="showResultsNumbersWrap">|</span>',
				'disabledLinkWrap' => '<span class="disabledLinkWrap">|</span>',
				'inactiveLinkWrap' => '<span class="inactiveLinkWrap">|</span>',
				'activeLinkWrap' => '<span class="activeLinkWrap">|</span>'
			); 
			$content .= str_replace('&amp;','&',$this->pi_list_browseresults(1,'',$wrapArr));
		}

		// Returns the content from the plugin.
		return $content;
		}

	/**
	 * Makes the base Query for the listView - you can overwrite this funciton
	 * in your plugin
	 *
	 * @param	[boolean]		$fCountRecords: 0 - for normal query, 1 - for counting the records
	 * @param	[string]		$strWhereStatement: For a more complex query
	 *
	 * @return	[pointer MySQL select result pointer / DBAL object]
	 */
	function execQuery($fCountRecords = 0,$strWhereStatement = '')
		{
		$showLastQuery = true;
		if($showLastQuery) {
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		}
					
		$res = $this->pi_exec_query($this->getTableName(),$fCountRecords,$strWhereStatement);

		if(!$res) {
			t3lib_div::debug('----------- SQL Statement ---------------',1);
			t3lib_div::debug(mysql_error(),1);
			t3lib_div::debug($strWhereStatement,'strWhereStatement');
			
			if($showLastQuery) t3lib_div::debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"lastBuiltQuery=");
			t3lib_div::debug('++++++++++++++++++++++++++++++++++++++++++',1);
			}

		return $res;
		}

	/**
	 * Recursively gather all folders of a path.
	 * For more  information you can look at the T3 function getFilesInDir
	 * 
	 *		$aFolders = array();
	 *		$aFolders =$this->getAllFoldersInPath($aFolders,'fileadmin/');
	 * 
	 *
	 * @param	array		$folderArr: Empty input array (will have folders added to it)
	 * @param	string		$path: The path to read recursively from (absolute) (include trailing slash!)
	 * @param	integer	$recursivityLevels: The number of levels to dig down...
	 * 
	 * @return	array		An array with the found files/directories.
	 */
	function getAllFoldersInPath($folderArr,$path,$recursivityLevels=99)       {
                 $folderArr[] = $path;
                 //$folderArr = array_merge($folderArr, t3lib_div::getFilesInDir($path,$extList,1,1));
 
                 $dirs = t3lib_div::get_dirs($path);
                 if (is_array($dirs) && $recursivityLevels > 0)    {
                         foreach ($dirs as $subdirs)     {
                                 if ((string)$subdirs!='')       {
                                         $folderArr = mmlib_extfrontend::getAllFoldersInPath($folderArr,$path . $subdirs . '/', $recursivityLevels - 1);
                                 }
                         }
                 }
                
         return $folderArr;
         }	
         
	/**
	 * Builds the content for the singleView.
	 *
	 * @param	[string]		$content: Basecontent from the plugins main function
	 * @return	[string]	HTML-Code
	 */
	function singleView($content)	
		{
		$this->setViewType('singleView');
		
		//$this->internal = $this->initCurrentRow();
		//t3lib_div::debug($this->cObj->currentRecord);
		//t3lib_div::debug($this->internal);
		
		$this->pi_setPiVarDefaults();
		$this->_resetDummyFieldList();
		
	 	$lConf 					= $this->conf['singleView.'];	// Local settings for the singleView function
		$aGETVars 				= t3lib_div::_GET();	// Adress (Commandline)
		$aPOSTVars 				= t3lib_div::_POST(); 	// Form
		//$strTemplateName	= ($lConf['templateFile'] ? $lConf['templateFile'] : 'single_view.tmpl');
		$strDateFormat		= ($lConf['dateformat'] ? $lConf['dateformat'] : 'd-m-Y H:i');
		
			// This sets the title of the page for use in indexed search results:
			
		if($lConf['substitutePagetitle'] && $this->internal['currentRow']['title']) {
			$GLOBALS['TSFE']->page['title'] 	= $this->internal['currentRow']['title'];
			$GLOBALS['TSFE']->indexedDocTitle 	= $this->internal['currentRow']['title'];
		}
		
		$strContent = '';
		$template 				= $this->getTemplateContent($this->getViewType());
		$templateSingleView 	= $this->cObj->getSubpart($template,'###SINGLEVIEW###');
		$templateMarker 		= $this->cObj->getSubpart($template,'###MARKERLINE###');
		//$templateSingleViewROW 	= $this->cObj->getSubpart($template,'###SINGLEVIEW_ROW###');
		
		// If there is no special SingleView Marker - the whole template is the singleview
		if(trim($templateSingleView) == '') $templateSingleView = $template;
		
		$markerArray['###SYS_UID###'] 				= $this->internal['currentRow']["uid"];
		$markerArray['###SYS_CURRENTTABLE###'] 		= $this->internal["currentTable"];
		$markerArray['###SYS_LASTUPDATE###'] 		= date($strDateFormat,$this->internal['currentRow']["tstamp"]);
		$markerArray['###SYS_CREATION###'] 			= date($strDateFormat,$this->internal['currentRow']["crdate"]);
		$markerArray['###SYS_BACKLINK###'] 			= $this->pi_list_linkSingle($this->pi_getLL("back","Back"),0);
		$markerArray['###SYS_EDITPANEL###'] 		= $this->pi_getEditPanel();
		$markerArray['###SYS_ALLFIELDS###']			= '';

		for($iColCounter = 0;$iColCounter < 20;$iColCounter++)
			{
			$markerArrayCol['###COLCLASS' . $iColCounter . '###'] = $this->pi_classParam('listcol' . $iColCounter);
			}
		
		// Define the display sequence of the fields
		$aFieldsToDisplay = strlen($lConf['displayOrder']) > 0 ? explode(',',$lConf['displayOrder']) : array_keys($this->internal['currentRow']);
		// Add dummy-Fields to the valid-fields array
		if(is_array($this->_dummyFieldList)) {
			foreach($this->_dummyFieldList as $key=>$value ) $aFieldsToDisplay[] = $key;
		}
		
		// Hide these fields if they are empty or 0
		$aTemp = array();
		$aHideIfEmpty = strlen($lConf['hideIfEmpty']) > 0 ? explode(',',$lConf['hideIfEmpty']) : array();
		foreach($aHideIfEmpty as $evalue) $aTemp[] = trim($evalue);
		$aHideIfEmpty = $aTemp;

		$nCounter = 0;
		$strSingleViewROWContent = '';
		
		foreach($aFieldsToDisplay as $key) // Iterate throug all the fields
			{
			$key = trim($key);
			
			// Wenn im KEY (also im Feldnamen eine [ vorkommt dann ist das eine Leerzeile
			if(preg_match('#^\[marker(.*)\]$#',$key,$aMatches)) {
				$markerMarker['###MARKERTEXT###'] = '&nbsp;';
				if(isset($aMatches[1]) && trim(strlen($aMatches[1])>0))
					{
					$strMarkerLable = trim($aMatches[1]);
					$markerMarker['###MARKERTEXT###'] = $this->pi_getLL('marker_' . $strMarkerLable,$strMarkerLable);
					$markerMarker['###MARKERCLASS###'] = $this->pi_classParam('marker ' . 'marker_' . $strMarkerLable);
					}
				$strSingleViewROWContent .= $this->cObj->substituteMarkerArray($templateMarker,$markerMarker);
				
				continue;
				}

			$strFieldLabel = $this->getLLabel($key,'(translate:' . $key . ')');

			// Wenn am Anfang und am Ende des Feldnamens ein [ bzw. ] steht dann ist das normalerweise der interne Name (internes Feld)
			if(preg_match('#^\[.*\]$#',$strFieldLabel)) {
				continue;
			}

			// Check if the field is empty and if the field is in the aHideIfEmpty-Array then go to the next field
			if(($this->internal['currentRow'][$key] === '' ||
				$this->internal['currentRow'][$key] === 0) &&
				in_array($key,$aHideIfEmpty,true))
				{
				//t3lib_div::debug("$key ->" . $this->getSingleViewFieldContent($key) . '#' . $this->internal['currentRow'][$key] . '#');
				continue;
				}

			$markerArray['###SYS_ALLFIELDS###'] .= $key . ', ';
			// Die beiden Felder werden auf den selben Wert gezogen da damit
			// entweder eine Tabelle erstellt werden kann die immer die Selben Zeilen verwendet
			// sowie eine Tabelle die ein individuelles Layout hat
			$markerArrayCol['###LABEL###']	= '<div'.$this->pi_classParam('label ' . 'label_' . $key).'>' .
				$strFieldLabel . '</div>';

			$markerArrayCol['###LABEL_' . strtoupper($key) . '###'] = $strFieldLabel;

			// Und hier kommen die Feldwerte
			$markerArrayCol['###FIELD###']	= '<div'.$this->pi_classParam('field ' . 'field_' . $key).'>' .
				$this->getSingleViewFieldContent($key) . '</div>';

			$markerArrayCol['###FIELD_' .  strtoupper($key) . '###']	= $this->getSingleViewFieldContent($key);

			$markerArrayCol['###' .  strtoupper($key) . '###']	= $this->getSingleViewFieldContent($key);
			
			$markerArrayCol['###LABEL_' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('label_' . $key . ' label');

			$markerArrayCol['###FIELD_' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('field_' . $key . ' field');

			$markerArrayCol['###' .  strtoupper($key) . '_CLASS###']	= $this->pi_classParam('value ' .$key . ' ' . $this->pi_getClassName($key));
			
			$markerArrayCol['###ROWCLASS###'] = ($nCounter % 2 ? $this->pi_classParam('listrow-even') : $this->pi_classParam('listrow-odd'));

			$markerArrayCol['###ROWCLASS' . $nCounter . '###'] = ($nCounter % 2 ? $this->pi_classParam('listrow-even ' . 'listrow' . $nCounter) : $this->pi_classParam('listrow-odd ' . 'listrow' . $nCounter));

			$markerArrayCol['###TABLECLASS###'] = $this->pi_classParam($this->getViewType());

			//$strSingleViewROWContent .= $this->cObj->substituteMarkerArray($templateSingleViewROW,$markerArrayCol);
			$nCounter++;
			}

					
			
		//if($lConf['showFieldNames']	== 0) $markerArray['###SYS_ALLFIELDS###'] = '';

		//L�schen des Markerblocks - sonst wird dieser am Ende noch 1x angezeigt
		$templateSingleView = $this->cObj->substituteSubpart($templateSingleView,'###MARKERLINE###','');

		// Contents der Spalten wird in den Platzhalter LIST_COL geschrieben
		// $template = $this->cObj->substituteSubpart($template,'###SINGLEVIEW_ROW###',$strSingleViewROWContent);
		
		//$template = $this->cObj->substituteMarkerArray($templateSingleView,$markerArray);

		// Arraykeys von markerArray ersetzen die jeweiligen Platzhalter in $template
		$templateSingleView = $this->cObj->substituteMarkerArray($templateSingleView,$markerArray);

		$strContent = $this->cObj->substituteMarkerArray($templateSingleView,$markerArrayCol);

		$strContent .= $this->pi_getEditPanel();

		return $strContent;
		}

	/**
	 * You can define if the field should be displayed
	 * 
	 * @param	[String] $fieldname: The name of the DB-Field
	 * @return	[bool]	True if the field should be displayed, otherwise false
	 * 
	*/
	function isFieldValidToShow($fieldname) {
		return true;
	}
	
	/**
	 * Not use for the moment (keep it as a basefunction for further development)
	 *
	 * @param	[type]		$local_table: ...
	 * @param	[type]		$local_uid: ...
	 * @param	[type]		$select: ...
	 * @param	[type]		$whereClause: ...
	 * @param	[type]		$groupBy: ...
	 * @param	[type]		$orderBy: ...
	 * @param	[type]		$limit: ...
	 * @param	[type]		$MM_table: ...
	 * @return	[type]		...
	 */
	function get_mm_fileList($local_table, $local_uid, $select='', $whereClause='', $groupBy='', $orderBy='', $limit=100, $MM_table='tx_dam_mm_ref')
		{
		$select = $select ? $select : 'tx_dam.uid, tx_dam.title, tx_dam.file_path, tx_dam.file_name, tx_dam.file_type' ;
		//debug(tx_dam_db::SELECT_mm_query(
		//			$select,
		//			$local_table,
		//			'tx_dam_mm_ref',
		//			'tx_dam',
		//			'AND '.$local_table.'.uid IN ('.$local_uid.') '.$whereClause,
		//			$groupBy,
		//			$orderBy,
		//			$limit
		//		));

		if(!$orderBy)
			{
			$orderBy = $MM_table.'.sorting';
			}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			$select,
			$local_table,
			$MM_table,
			'tx_dam',
			'AND '.$local_table.'.uid IN ('.$local_uid.') '.$whereClause,
			$groupBy,
			$orderBy,
			$limit
			);

		$files = array();
		$rows = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
			{
			$files[$row['uid']] = $row['file_path'].$row['file_name'];
			$rows[$row['uid']] = $row;
			}

		return array('files'=>$files, 'rows'=>$rows);
		}

	/**
	 * Set's the current viewType (listView oder singleView)
	 *
	 * @param	[string]		$viewType: listView or singleView
	 * @return	[string]	The current view-type
	 */
	function setViewType($viewType = 'listView')
		{
		//$this->_viewType = (($viewType == 'singleView' || $viewType = 'listView') ? $viewType : 'listView');
		$this->_viewType = $viewType;
		return $this->_viewType;
		}
		
	/**
	 * Returns the current viewType (singleView or listView)
	 *
	 * @return	[string]	The current view-type
	 */
	function getViewType()
		{
		if($this->_viewType == null || 
			$this->_viewType == '' ||
			!method_exists($this,$this->_viewType)) $this->setViewType();
			
		return $this->_viewType;
		}
		
	/**
	 * Find out the right view-type and get the right data from the table
	 *
	 * @return	[string]	content for the view
	 */
	function getContentForView($overwriteView = null)
		{
		$content = '';
		$view = $overwriteView != null ? $overwriteView : (string)$this->conf['CMD'];
		$view = trim($view);
		
		// listView is the default View (getView does this automaticaly)
		if($view == null || $view == '') $view = $this->getViewType();
		
		switch($view)	
			{
			case 'singleViewTTContent':
				list($t) = explode(':',$this->cObj->currentRecord);
				
				$this->internal['currentTable']		=	$t;
				$this->internal['currentRow']		=	$this->cObj->data;
				$this->mmlib_cache->resetBuffer();
				
				if($this->piVars['showuid']) {
					$this->internal['currentRow'] 	= $this->initCurrentRow($this->piVars["showuid"]);
					}
				
				$content = $this->singleView($content);
			break;
			
			default:
				// Calls something like this: $content = $this->listView($content);
				if(method_exists($this,$view)) $content = $this->$view($content);
				else {
					die ('The method: $this->' . $view . '($conten) does not exist.<br>Please define this method!');
				}
				
			break;
			}
		return $content;	
		}
	
	/**
	 * Deletes the TEMPORARY Files from the the upload folder.
	 * The temporary file is created if you dont have direct access to
	 * the filename (Instaead you have access to something like q208uhkjhaf.jpg)
	 *
	 * These files may come obsolete during time - so thats why we delete them here.
	 *
	 * @return	[string]	content for the view
	 */
	function _clearSecureCache($maxDaysInChache	= 30) {
	$foldername 			= PATH_site . $this->_getUploadFolder();
	
	if(!is_dir($foldername)) return;
	
    $folder 				= opendir($foldername);
    $filehandler			= null;
    
    while($strFileName = readdir($folder))
			{
			$aFileName = t3lib_div::split_fileref($strFileName);

					//debug($aFileName['filebody'],1);
			if(strstr($strFileName,$this->_secureFilePrefix) || strlen($aFileName['filebody']) == 10)
				{
				$timeFile = filemtime($foldername . $strFileName);
				$timeNow	= time();
				
				if($timeFile < ($timeNow - ((60 * 60 * 24 ) * $maxDaysInChache)))
					{
					if($filehandler == null) $filehandler = t3lib_div::makeInstance('mmlib_FileHandling');
					
					//debug("DELETE ->" . $strFileName . ' ' . date("Y-m-d",$timeFile),1);
					$filehandler->removeFile($this->_getUploadFolder() . $strFileName);
					}
				}
			}
			
		}
		
	/**
	 * Gets the settings from the FlexForm.
	 * You can describe the fieldname in two ways.
	 * 1 - FlexSheetName:Fieldname
	 * 2 - Param1 - FlexSheetName
	 *		 Param2 - FlexFieldName
	 *
	 * @param	[string]	$FFLocation: FlexSheetName:Fieldname or FlexSheetName
	 * @param	[string]	$FlexFieldName: Fieldname or no param if you use the way 1 of describeing the FlexField
	 *
	 * @return	[string]	Value of the field
	 */
	function getFFSettings($FFLocation,$FFFieldName = null) {
		if($FFFieldName == null) {
			$flexInfo = explode(':',$FFLocation);
			}
		else {
			$flexInfo[0] = $FFLocation; 	// SheetName
			$flexInfo[1] = $FFFieldName; 	// FieldName
			}
		
		// Try to get the requested flexform value
		$flexValue = (string) $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $flexInfo[1], $flexInfo[0]);
		//debug($flexInfo);
		return $flexValue;
		}
		
	/**
	 * Wraps a string in a div class.
	 * The first part of the wrapping is always the class name (prfixId)
	 *
	 * @param	[string]	$str: String wich should wraped with a div
	 * @param	[string]	$blockname: Name of the block which should be wrapped
	 *
	 * @return	[string]	Value of the field
	 */
	function wrapInClass($str,$blockname) {
			$content = '<div class="'. str_replace('_','-',$this->prefixId . '-' . strtolower($blockname)).'">' .
				$str . '</div>';
			
			/*	
			if(!$GLOBALS['TSFE']->config['config']['disablePrefixComment']) {
				$content = 
					'<!-- BEGIN: Content of extension "' .
					$this->extKey .
					'", plugin "' . 
					$this->prefixId . '"-->' . 
					$content . 
					'<!-- END: Content of extension "'.$this->extKey.'", plugin "'.$this->prefixId.'" -->';
				}
			*/
			
			return $content;
			}	
			
	/**
	 * Returns the field-data from the current plugin DB-table.
	 *
	 * @param	[string]	$fieldname: Name of DB-Table field
	 *
	 * @return	[mixed]	Returns the data from the current DB-Table field
	 */
	function getPluginTableData($fieldname) {
		return $this->internal['currentRow'][$fieldname];
		}
		
	/**
	 * Returns the field-data from the current Typo-page.
	 *
	 * @param	[string]	$fieldname: Name of DB-Table field
	 *
	 * @return	[mixed]	Returns the data from the current DB-Table field
	 */
	function getPageTableData($fieldname) {
		return $this->cObj->data[$fieldname];
		}		
		
	/**
	 * implodes the pieces in an array. Let out the blank pieces
	 *
	 * @param	[string]	$glue: String which is between the pieces
	 * @param	[array]		$pieces: parts of array
	 * 	 *
	 * @return	[mixed]	Returns the data from the current DB-Table field
	 */
	function implodeWithoutBlankPiece($glue, $pieces) {
		$foundFirstNonBlank = false;
		
		if(!is_array($pieces)) return $pieces;
		$imploded = '';

		foreach($pieces as $value) {
			$value = trim($value);
			if($value == '') continue;

			$found = strstr($value,trim($glue));
			// if there is already a glue at the beginning of the first part - remove it
			if($found !== false && $found == 0) $value = preg_replace('#^' . $glue . '#','',$value);
			
			if($foundFirstNonBlank == false) {
				$imploded .= $value;
				$foundFirstNonBlank = true;
				continue;
				}

			$imploded .= ' '. $glue . $value . ' '; // changed 29.08.08 thx to Mohammed Iman
		}
		
		return $imploded;
	}
	
	/**
	 * Returns the right mime_type for the file es an array
	 * The first element in the array is the base type, the second
	 * element is the sub type.
	 *
	 * @param	[string]	$fName: Filename - works also with the Type-Shortcuts EXT: 
	 * 
	 * @return	[mixed]		mime-type array or null (Example: index 0 application, index 1 msword)
	 */
	function getMimeType($fName) {
		global			$mimeTypes;
		
		$fName			= trim($fName);
		$defaultType 	= 'application/octet-stream';
		$aMimeType 		= null;
		$mimetype		= $defaultType;
		
		$aFileName = t3lib_div::split_fileref($fName);
		if(!isset($aFileName['realFileext'])) return false;
		
		$extension = trim(strtolower($aFileName['realFileext']));
		
		// First look into the array if there is the right extension.
		// This is neccesary because I found some problems with the internal mime_content_type function.
		if(isset($mimeTypes[$extension])) $mimetype = $mimeTypes[$extension][0];
		else $mimetype = mime_content_type(PATH_site . $fName);

		$aMimeType	= split('/',$mimetype);
		if(count($aMimeType) != 2) return null;
				
		return $aMimeType;
	}
	
	
	/**
	 * Gives you the img-Tag of the specified Image.
	 * First the function checks if there is a preconfigured Path (from the user)
	 * if not - it thakes the Path from the Plugin
	 *
	 * @param	[string]	$imageName - name of the image (sample.jpg)
	 * @param	[string]	$$name$GLOBALS["TSFE"]->fe_user->user['username'];
	 * IconsetFromConf - Name of the configuration-setting (if you have one) (iconset_navigation)
	 * @param	[string]	$pathDefault - The plugin-internal path, if there is no path from the conf, this path is used - (iconset_navigation)
	 * 
	 * @return	[mixed]		The Image-Tag
	 */
	function getImageFromIconset($imageName,$nameIconsetFromConf,$pathDefault,$addParams = 'alt="" title=""') {
		$pathPlugin = $pathDefault;

		$fileResource = 'EXT:' . $this->extKey . $pathPlugin . $imageName;
		if($this->conf[$nameIconsetFromConf] && file_exists(PATH_site . $this->conf[$nameIconsetFromConf])) {
			$fileResource =  $this->conf[$nameIconsetFromConf] . $imageName;
		}
		//debug($fileResource,1);
		
		$imageTag 	= $this->cObj->fileResource($fileResource,$addParams);	
		return $imageTag;
	}

	/**
	 * Returs if a specific File is an Image - only the extension is checked - not the mime type
	 *
	 * @param	[string]	$filename - name of the image (sample.jpg)
	 * 
	 * @return	[bool]		true it it is an image, false if not
	 */
	function isImage($filename) {
		$aFileName = t3lib_div::split_fileref($filename);
		if(!isset($aFileName['realFileext'])) return false;
		
		$extension = strtolower($aFileName['realFileext']);
	
		return in_array($extension,$this->internal['image_extension']);
	}
	
	/**
	 * "virtual" function - can be overwritten
	 * 
	 * @param	[array]	$recordset - the record which should be checked
	 * 
	 * @return	[bool]		true if the record is OK, otherwise false
	 * 	 */
	function isThisRecordValid($record) {
		return true;
	}

	/*
	 * Initializes the internal-structure with the current record
	 * This initialisation is neccesary for getFieldContent
	 * 
	 * @param	[array]	$record - Current record from table
	 * 
	 */
	function setCurrentRow($record) {
		unset($this->internal['currentRow']);
		$this->mmlib_cache->resetBuffer();
		
		foreach($record as $key => $value) $this->internal['currentRow'][$key] = $value;
		
		if(!isset($this->_dummyFieldList) || $this->_dummyFieldList == null) {
			$this->_resetDummyFieldList();
		}
		foreach($this->_dummyFieldList as $key => $value) $this->internal['currentRow'][$key] = $value;	
	}
	
	
	/**
	 * Returns all available users. Before you can use this function
	 * it needs at least a call to initUserAuth()
	 * 
	 * Includes needed:
	 * 	require_once (PATH_tslib."class.tslib_feuserauth.php");
	 * 
	 * @return [array] All userrecords in an array or null if an error ocured
	 */
	function getAllUsers()  {
        if($this->_objUserAuth == null)	{
        	$this->initUserAuth();
        }
        
		$dbres 			= null;
		$userrecords	= null;
		
		// removes the first AND in the predefined where_clause
		$whereClause = preg_replace('#^\s*AND#','',$this->_objUserAuth->user_where_clause());
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->_objUserAuth->user_table,$whereClause);
		
		while($dbres && $record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres)) {
			$userrecords[] = $record;
		}
		
		if ($dbres) $GLOBALS['TYPO3_DB']->sql_free_result($dbres);
		
		return $userrecords;
    }	
	
    /**
     * Returns the current username or null if no user is logged in.
	*/
    function getCurrentFEUsername() {
        if($this->_objUserAuth == null)	{
        	$this->initUserAuth();
        }
    
        if(isset($GLOBALS["TSFE"]->fe_user->user['username'])) {
        	return $GLOBALS["TSFE"]->fe_user->user['username'];
        } else return null;
    }
    
    /**
     * Returns the current username or -1 if no user is logged in.
	*/
    function getCurrentFEUID() {
        if($this->_objUserAuth == null)	{
        	$this->initUserAuth();
        }
    
        if(isset($GLOBALS["TSFE"]->fe_user->user['uid'])) {
        	return $GLOBALS["TSFE"]->fe_user->user['uid'];
        } else return -1;
    }
    
    
    /**
     * Adds the TemplateSystem in BE-Mode. This does not exist by default
     * 
     * @return [array] TS-Configuration
     */
	function initBETemplates() {
		// We need to create our own template setup if we are in the BE
		// and we aren't currently creating a DirectMail page.
		if ((TYPO3_MODE == 'BE') && !is_object($GLOBALS['TSFE'])) {
			$template = t3lib_div::makeInstance('t3lib_TStemplate');
			// do not log time-performance information
			$template->tt_track = 0;
			$template->init();
			// Get the root line
			$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
			// the selected page in the BE is found
			// exactly as in t3lib_SCbase::init()
			$rootline = $sys_page->getRootLine(intval(t3lib_div::_GP('id')));
			// This generates the constants/config + hierarchy info for the template.
			$template->runThroughTemplates($rootline, 0);
			$template->generateConfig();
			$conf = $template->setup['plugin.']['tx_'.$this->extKey.'.'];
			$GLOBALS['TSFE']->tmpl = $template;
			$GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
			
			// change dir to the base-folder (docroot) otherwise the
			// whole filefunctions don't work because all the path are relative!!!!!
			// For an example - look at: class.t3lib_tstemplate.getFileName($filename)
			chdir(PATH_site);
			
			return $conf;
		} else {
			// On the front end, we can use the provided template setup.
			//$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_'.$this->extKey.'.'];
			$conf = $GLOBALS['TSFE']->tmpl;
		}
		
		return $conf;
	}
	
	/**
	 * Similar to the T3 function t3lib_pageSelect.enableFields except
	 * you can DEFINE the groups for which the SQL-Statement is made.
	 * The original T3 function uses the logged in FE-User.
	 * 
	 * Only the function-call to getMultipleGroupsWhereClause is different (groups - parameter)
	 * 
	 * @see t3lib_pageSelect.enableFields
	 */
	function enableFields($table,$groups,$show_hidden=-1,$ignore_array=array(),$noVersionPreview=FALSE)     {
		global $TYPO3_CONF_VARS;
		
		if ($show_hidden==-1 && is_object($GLOBALS['TSFE']))    {       // If show_hidden was not set from outside and if TSFE is an object, set it based on showHiddenPage and showHiddenRecords from TSFE
		        $show_hidden = $table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords;
		}
		if ($show_hidden==-1)   $show_hidden=0; // If show_hidden was not changed during the previous evaluation, do it here.
		
		$ctrl = $GLOBALS['TCA'][$table]['ctrl'];
		$query='';
		if (is_array($ctrl))    {
		
		                // Delete field check:
		        if ($ctrl['delete'])    {
		                $query.=' AND '.$table.'.'.$ctrl['delete'].'=0';
		        }
		
		                // Filter out new place-holder records in case we are NOT in a versioning preview (that means we are online!)
		        if ($ctrl['versioningWS'] && !$this->versioningPreview) {
		                $query.=' AND '.$table.'.t3ver_state!=1';       // Shadow state for new items MUST be ignored!
		        }
		
		                // Enable fields:
		        if (is_array($ctrl['enablecolumns']))   {
		                if (!$this->versioningPreview || !$ctrl['versioningWS'] || $noVersionPreview) { // In case of versioning-preview, enableFields are ignored (checked in versionOL())
		                		//t3lib_div::debug($field,'$field');
		                		//t3lib_div::debug($ctrl['enablecolumns']['disabled'],'$ctrl[\'enablecolumns\'][\'disabled\']');
		                		//t3lib_div::debug(!$show_hidden,'!$show_hidden');
		                		//t3lib_div::debug(!$ignore_array['disabled'],'!$ignore_array[\'disabled\']');
		                		
		                        if ($ctrl['enablecolumns']['disabled'] && !$show_hidden && !$ignore_array['disabled']) {
		                                $field = $table.'.'.$ctrl['enablecolumns']['disabled'];
		                                $query.=' AND '.$field.'=0';
		                        }
		                        if ($ctrl['enablecolumns']['starttime'] && !$ignore_array['starttime']) {
		                                $field = $table.'.'.$ctrl['enablecolumns']['starttime'];
		                                $query.=' AND ('.$field.'<='.$GLOBALS['SIM_EXEC_TIME'].')';
		                        }
		                        if ($ctrl['enablecolumns']['endtime'] && !$ignore_array['endtime']) {
		                                $field = $table.'.'.$ctrl['enablecolumns']['endtime'];
		                                $query.=' AND ('.$field.'=0 OR '.$field.'>'.$GLOBALS['SIM_EXEC_TIME'].')';
		                        }
		                        if ($ctrl['enablecolumns']['fe_group'] && !$ignore_array['fe_group'] && $groups != null) {
		                                $field = $table.'.'.$ctrl['enablecolumns']['fe_group'];
		                                $query.= $this->getMultipleGroupsWhereClause($field, $table,$groups);
		                        }
		                }
		        }
		} else {
		        die ('NO entry in the $TCA-array for the table "'.$table.'". This means that the function enableFields() is called with an invalid table name as argument.');
		        }
		
		        return $query;
		}
	
	/**
	 * Similar to the T3 function t3lib_pageSelect.getMultipleGroupsWhereClause except
	 * you can DEFINE the groups for which the SQL-Statement is made.
	 * The original T3 function uses the logged in FE-User.
	 * 
	 * Only the function-call to getMultipleGroupsWhereClause is different (groups - parameter)
	 * 
	 * @see t3lib_pageSelect.getMultipleGroupsWhereClause
	 */
	function getMultipleGroupsWhereClause($field,$table,$groups)   {
		$orChecks=array();
		$orChecks[]=$field.'=\'\'';     // If the field is empty, then OK
		$orChecks[]=$field.'=\'0\'';    // If the field contsains zero, then OK
		
		// field name must be something like this tx_dam.fe_group (tablename.fieldname)
		if(strstr($field,'.') == null) $field = $table . '.' . $field;
		
		foreach($groups as $value)        {
	        $orChecks[] = $GLOBALS['TYPO3_DB']->listQuery($field, $value, $table);
		}
		
		return ' AND ('.implode(' OR ',$orChecks).')';
	}	
	
	/**
	 * Returns the path to the extension relative seen from the DOCROOT
	 */
	function extRelPath($extKey) {
		return str_replace(PATH_site,'',t3lib_extMgm::extPath($this->extKey));
	}
	
	function linkTPkeepPIvars($str,$overrulePIvars=array(), $cache=0, $clearAnyway=0, $altPageId=0, $confLink = array()) {
		if (is_array($this->piVars) && is_array($overrulePIvars) && !$clearAnyway) {
			$piVars = $this->piVars;
			unset($piVars['DATA']);
			$overrulePIvars = t3lib_div::array_merge_recursive_overrule($piVars,$overrulePIvars);
			
			if ($this->pi_autoCacheEn) {
				$cache = $this->pi_autoCache($overrulePIvars);
			}
		}
		
		$urlParameters = Array($this->prefixId=>$overrulePIvars);
		
		$conf=array();
		$conf['useCacheHash'] = $this->pi_USER_INT_obj ? 0 : $cache;
		$conf['no_cache'] = $this->pi_USER_INT_obj ? 0 : !$cache;
		$conf['parameter'] = $altPageId ? $altPageId : ($this->pi_tmpPageId ? $this->pi_tmpPageId : $GLOBALS['TSFE']->id);
		$conf['additionalParams'] = $this->conf['parent.']['addParams'] . t3lib_div::implodeArrayForUrl('',$urlParameters,'',1) . $this->pi_moreParams;

		if(is_array($confLink)) {
			$confLink = t3lib_div::array_merge_recursive_overrule($conf,$confLink);
		}
		
		return $this->cObj->typoLink($str, $confLink);
	}
	
	/**
	 * Returs a short md5-hash value of a specific table record
	 * 
	 * @param	[string]	$tablename - name of the table
	 * @param	[string]	$uid - uid in table where the records are
	 * 
	 * @return	[string]	10digig md5 hash or null if anything fails
	 */
	function getRecordMD5($tablename,$uid) {
		$md5				= null;
		
		$SQL['select'] 				= '*';
		$SQL['from']				= $tablename;
		$SQL['order_by']			= '';
		$SQL['group_by']			= '';
    	$SQL['limit']				= '';
		
		
		$SQL['where']				= 'uid=' . $uid;
		
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$SQL['select'],
			$SQL['from'],
			$SQL['where'],             
			$SQL['group_by'],
			$SQL['order_by'],
			$SQL['limit']
			);	
			
		if($res) {
			$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);	
			if(count($record)) {
				//t3lib_div::debug($record,'$record');
				$md5 = t3lib_div::stdAuthCode($record);
				//$md5 = t3lib_div::shortmd5(implode('',$record));
			}
		}

		return $md5;
	}
	
	/**
	 * Returns a record specified by its tablename and its uid.
	 * 
	 * @param	[string]	tablename - the Tabel in the DB
	 * @param 	[string]	uid - the UID for the Record
	 * 
	 * @return	[array]		Either the record or null if the request fails
	 */
	function getRecord($tablename,$field,$value) {
		$record				= null;
		
		$SQL['select'] 				= '*';
		$SQL['from']				= $tablename;
		$SQL['order_by']			= '';
		$SQL['group_by']			= '';
    	$SQL['limit']				= '';
		
		
		$SQL['where']				= $field . "='" . $value . "'";
		
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$SQL['select'],
			$SQL['from'],
			$SQL['where'],             
			$SQL['group_by'],
			$SQL['order_by'],
			$SQL['limit']
			);	
			
		if($res) {
			$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);	
		}

		return $record;
	}	
	
	/**
	 * Retursn true if the settings show_empty_page_if_no_pivars ist true
	 * and if there are no piVars at the beginning of the initialisation function
	 * 
	 * @return	[boolean]	true if piVars are empty, otherwise false
	 * 
	 */
	function isShowEmptyPageTurnedOn() {
		$turnEmpty = (isset($this->conf['show_empty_page_if_no_pivars']) && 
		$this->conf['show_empty_page_if_no_pivars'] == 1 &&
		isset($this->internal['piVarsOnInit']) &&
		$this->internal['piVarsOnInit'] == null &&
		count($this->internal['piVarsOnInit']) == 0);
		
		//t3lib_div::debug(count($this->internal['piVarsOnInit']),'count($this->internal[\'piVarsOnInit\'])');
		//t3lib_div::debug($this->internal['piVarsOnInit'],'$this->internal[\'piVarsOnInit\']');
		//t3lib_div::debug($turnEmpty,'$turnEmpty');
		
		return $turnEmpty;
	}
	
	/**
	 * Overwrites / Replaces the function checkRecord (pi_getRecord)
	 * because we need to Overrule the enableFields functionality.
	 * 
	 * @param	[string]	$table - The tablename
	 * @param	[string]	$uid - uid of Record
	 * @param	[boolean]	$checkPage - 	If checkPage is set, it's also required that the page on which the record resides is accessible 
	 * @param	[string]	$enableFields - SQL Statement for enabling the record fields
	 * 
	 *  
	 * @return	[array]		Recordset or 0 if anything fails
	 * 
	 */
	function checkRecordOverruleFields($table,$uid,$checkPage=0,$enableFields = null)  {
         global $TCA;
                 
         if($enableFields == null) $enableFields = $this->enableFields($table);
         
         $uid=intval($uid);
                 if (is_array($TCA[$table])) {
                         $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.intval($uid).$enableFields);
                         if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                                 t3lib_pageSelect::versionOL($table,$row);
                                 $GLOBALS['TYPO3_DB']->sql_free_result($res);
 
                                 if (is_array($row))     {
                                         if ($checkPage) {
                                                 $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid='.intval($row['pid']).$this->enableFields('pages'));
                                                 if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))   {
                                                         return $row;
                                                 } else {
                                                         return 0;
                                                 }
                                         } else {
                                                 return $row;
                                         }
                                 }
                         }
                 }
         }

	/**
	 * Crates a Cobmo or a Listbox from a table-contents.
	 * 
	 * @param	[string]	$tablename - The tablename from which we want to get the data
	 * @param	[string]	$fieldname - The fieldname from the table
	 * @param	[string]	$filterfield - From this field we will get the ID to filter the current database
	 * @param	[string]	$elementtype - list or combo
	 * 
	 * @param	[string]	$firstComboEntry - Can be something like --- please choose ---
	 * @param	[string]	$label - The label string
	 * @param	[string]	$entry2remove - These strings (Comma separated) will be removed
	 * @param	[string]	$linkfield - getTypoLink - linkfield
	 * @param	[string]	$linktarget - the link-target
	 * @param	[string]	$targetpageid - It the targetpage is not the current page we need a specific ID
	 *  
	 * @return	[string]	congtent-string with htmlcode of combo or listbox
	 * 
	 */
   	function createSubTableLinkWidgetFromArray($conf) {
		$tablename			= $conf['tablename'];
		$fieldname			= $conf['fieldname'];
		$filterfield		= $conf['filterfield'];
		$elementtype		= $conf['elementtype'];
		$firstComboEntry	= isset($conf['firstcomboentry']) 	? $conf['firstcomboentry'] 	: '';
		$label				= isset($conf['label']) 			? $conf['label']			: '';
		$entry2remove		= isset($conf['entry2remove'])		? $conf['entry2remove']		: array();
		$linkfield			= isset($conf['linkfield']) 		? $conf['linkfield'] 		: '';
		$linktarget			= isset($conf['linktarget'])		? $conf['linktarget']		: '';
		$targetpageid		= isset($conf['targetpageid'])		? $conf['targetpageid']		: '';
		$tablePID			= isset($conf['tablepid'])			? $conf['tablepid']			: -1;
		 
		$template			= $this->getTemplateContent('listView');
		
		if($elementtype == 'list') {
			$templateSelctor 	= $this->cObj->getSubpart($template,'###LIST_SELECTOR###');
		} else {
			$templateSelctor 	= $this->cObj->getSubpart($template,'###COMBO_SELECTOR###');
		}
		//t3lib_div::debug($templateSelctor,'$templateSelctor=');
		
		$templateItem 		= $this->cObj->getSubpart($templateSelctor,'###SELECTOR_ITEM###');

		// Overrule
		$arryLinks['showuid']	= '';
		$arryLinks['sword'] 	= '';
		
		$elements		= array();
		$markerArray 	= array(); 
		$result 		= $this->getDataFromForeignTable(null,$tablename,$fieldname,true,$tablePID);
		$keyfield		= $fieldname;
		
		//t3lib_div::debug($result,'$result=');
		
		// Wenn es mehrere feldnamen gibt dann ist der erste Feldname
		// das Schlüsselfeld (der Text der angezeigt wird)
		$fields			= split(',',$fieldname);
		if(is_array($fields)) $keyfield = $fields[0];
		
		foreach($result as $key=>$realvalue) {
			if(is_array($realvalue)) $value = $realvalue[$keyfield];
			else $value = $realvalue;
			
			if(in_array($value,$entry2remove)) continue;
			
			$arryLinks['filterfield'] 					= $filterfield;
			$arryLinks['filterid'] 						= $key;
			
			$markerArray['###UID###'] 					= $key;
			$markerArray['###VALUE###'] 				= $value;
			$markerArray['###SELECTOR_ITEM_LINK###']	= '<a href="#"></a>';
			
			if(is_array($realvalue) && $linkfield != '' && isset($realvalue[$linkfield]) && $realvalue[$linkfield] != '') {
				$markerArray['###SELECTOR_ITEM_LINK###'] 	= $this->local_cObj->getTypoLink($value,'http://' . $realvalue[$linkfield],array(),$linktarget);
			} elseif($targetpageid != '') {
				$linkconf=array();
				$linkconf['useCacheHash'] 		= $this->pi_USER_INT_obj ? 0 : $this->allowCaching;
				$linkconf['no_cache'] 			= $this->pi_USER_INT_obj ? 0 : !$this->allowCaching;
				$linkconf['parameter'] 			= intval($targetpageid) ? intval($targetpageid) : ($this->pi_tmpPageId ? $this->pi_tmpPageId : $GLOBALS['TSFE']->id);
				
				// Uploadfolder gibt den richtigen Wert zurück
				$linkconf['additionalParams'] 	= $this->conf['parent.']['addParams'] . t3lib_div::implodeArrayForUrl(get_class($this),$arryLinks,'',1) . $this->pi_moreParams;

               $markerArray['###SELECTOR_ITEM_LINK###'] = $this->cObj->typoLink($value, $linkconf);			
				//$markerArray['###SELECTOR_ITEM_LINK###'] 	= $this->pi_linkTP($value,$arryLinks,1,intval($targetpageid));
			} else {
				$markerArray['###SELECTOR_ITEM_LINK###'] 	= $this->pi_linkTP_keepPIvars($value,$arryLinks,$this->allowCaching);
			}
			
			if(preg_match('#href="([^"]*)"#',$markerArray['###SELECTOR_ITEM_LINK###'],$match)) {
				$markerArray['###SELECTOR_ITEM_LINK_URL###'] = $match[1];
			}

			$markerArray['###SELECTED###']				= '';
			if(isset($this->piVars['filterfield']) && 
				isset($this->piVars['filterid']) && 
				$this->piVars['filterfield'] == $filterfield &&
				$this->piVars['filterid'] == $key)  {
				
				$markerArray['###SELECTED###']			= 'selected';
				} 
				
			$elements[] = $this->cObj->substituteMarkerArray($templateItem,$markerArray);
		}
		
		$markerArray 	= array();
		$markerArray['###SELECTOR_NAME###'] = 'selector-' . str_replace('_','-',$tablename);
		$markerArray['###LABEL###'] = $this->getLLabel($label,$label);

		$arryLinksToRemove['filterfield'] 	= '';
		$arryLinksToRemove['filterid'] 		= '';		
		$markerArray['###FIRSTCOMBOENTRY###'] = $this->getLLabel($firstComboEntry,$firstComboEntry);
		$markerArray['###FIRSTCOMBOENTRY_LINK###'] 	= $this->pi_linkTP_keepPIvars($markerArray['###FIRSTCOMBOENTRY###'],$arryLinksToRemove,$this->allowCaching);
		if(preg_match('#href="([^"]*)"#',$markerArray['###FIRSTCOMBOENTRY_LINK###'],$match)) {
			$markerArray['###FIRSTCOMBOENTRY_LINK_URL###'] = $match[1];
		}
		
		$templateSelctor = $this->cObj->substituteMarkerArray($templateSelctor,$markerArray);
				 
		$content = $this->cObj->substituteSubpart($templateSelctor,'###SELECTOR_ITEM###',implode('',$elements));
		//t3lib_div::debug($content,'$content=');

		return $content;		
   		
   	}   	 
         
	function createSubTableLinkWidget($tablename,$fieldname,$filterfield,$elementtype,$firstComboEntry = '',$label = '',$entry2remove = array(),$linkfield='',$linktarget='',$targetpageid='') {

		$conf['tablename'] 		= $tablename;
		$conf['fieldname'] 		= $fieldname;
		$conf['filterfield'] 	= $filterfield;
		$conf['elementtype'] 	= $elementtype;
		$conf['firstcomboentry'] = $firstComboEntry;
		$conf['label'] 			= $label;
		$conf['entry2remove'] 	= $entry2remove;
		$conf['linkfield'] 		= $linkfield;
		$conf['linktarget'] 	= $linktarget;
		$conf['targetpageid'] 	= $targetpageid;
	
	return $this->createSubTableLinkWidgetFromArray($conf);
	}
	
	/**
	 * Creates an alternative Search-Box with CSS-Styles
	 * 
	 * Returns a Search box, sending search words to piVars "sword" and 
	 * setting the "no_cache" parameter as well in the form. Submits the search request to the current REQUEST_URI
	 * 
	 * 
	 * @param	[string]	$tableParams - Attributes for the table tag which is wrapped around the table cells containing the search box
	 *  
	 * @return	[string]	Output HTML, wrapped in ttags with a class attribute 
	 * 
	 */
	function alternateSearchBox($divParams='')     {
                         // Search box design:
                         
	
		$content = '
 
			<!-- List search box: -->
			<div'.$this->pi_classParam('searchbox').'>
				<form action="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" method="post" style="margin: 0 0 0 0;">
					<'.trim('div '.$divParams).'>
						<div ' . $this->pi_classParam('searchbox-label') . '>
							' . $this->getLLabel('label.searchbox') . '
						</div>
						<div ' . $this->pi_classParam('searchbox-sword-container') . '>
							<input type="text" name="'.$this->prefixId.'[sword]" value="'.htmlspecialchars($this->piVars['sword']).'"'.$this->pi_classParam('searchbox-sword').' />
						</div>
						<div ' . $this->pi_classParam('searchbox-button-container') . '>
							<input type="submit" value="'.$this->pi_getLL('pi_list_searchBox_search','Search',TRUE).'"'.$this->pi_classParam('searchbox-button').' />'.
							'<input type="hidden" name="no_cache" value="1" />'.
							'<input type="hidden" name="'.$this->prefixId.'[pointer]" value="" />'.
						'</div>
					</div>
				</form>
			</div>';
 
		return $content;
        }
	
       function setDebug($debug = true) {
       		$temp = $this->_debug;
       		$this->_debug = $debug;
       		return $temp;
       }  
       
       function isDebug() {
       		return ($this->_debug);
       }
    /*
	 * Reformats the PIVar-Keys to LowerCase
	 * 
	 * @return	[void]	 
     *      
     */  
	function reformatPIVarsKey() {
			foreach($this->piVars as $key => $value) {
				$this->piVars[strtolower($key)] = $value;
			}
	
	}
	
	/**
	 * Displays the Browservars (piVars, _POST and _GET)
	 */
	function debugShowBrowserVars($checkDebugSettings = true) {
		if($this->isDebug() || $checkDebugSettings == false) {
			
			t3lib_div::debug("- piVars -------------------",1);
			foreach($this->piVars as $key => $value) {
				t3lib_div::debug($key . ': ' . $value,1);
			    }
			    
			echo "<br />";
			t3lib_div::debug("- _POST (Form) -------------------",1);
			foreach(t3lib_div::_POST() as $key => $value) {
				t3lib_div::debug($key . ': ' . $value,1);
			}
	
			echo "<br />";
			t3lib_div::debug("- _GET (CMDLine) -------------------",1);
			foreach(t3lib_div::_GET() as $key => $value) {
				t3lib_div::debug($key . ': ' . $value,1);
			}
			
			t3lib_div::debug("-----------------------",1);		
		}
	}
	/**
	 * Returns the Current PID
	 */
	function getPIDToStoreRecord() {
		if(isset($this->conf['PIDtoStoreRecord'])) {
			return $this->conf['PIDtoStoreRecord'];
		}
		return (isset($this->cObj->data['pid']) ? $this->cObj->data['pid'] : 0); 
	}
	
	/**
	 * Simple Way of validating a eMail-Address
	 */
	function isValidEmail($email){
	    return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email);
	}

	/**
	 * Sends a mail..
	 * 
	 * @param 	[string] 	$recipient - eMail-Address of receiver
	 * @param	[string]	$subject - Subject of the Mail
	 * @param	[string]	$from - Sender of eMail
	 * @param	[string]	$content - Content of Mail
	 * 
	 * @return [void]
	 */
	function sendInfoMail($recipient,$subject,$from,$content) {
		$mail = t3lib_div::makeInstance('t3lib_htmlmail');
		$mail->start();
		$mail->useBase64();
		$mail->charset = 'iso-8859-1';
		
		$mail->plain_text_header = "Content-Type: text/plain; charset=iso-8859-1\nContent-Transfer-Encoding: quoted-printable";
		$mail->html_text_header = "Content-Type: text/html; charset=iso-8859-1\nContent-Transfer-Encoding: quoted-printable";
				
		$mail->subject = $subject;
		$mail->from_email = $from;
		$mail->from_name = $mail->from_email;
		$mail->organisation = $mail->from_name;
		$mail->replyto_email = $mail->from_email;
		$mail->replyto_name = $mail->from_name;
		
		$mail->setRecipient($recipient);
		$mail->setPlain($content);
		$mail->setContent($content);
		$mail->setHeaders();
		
		$mail->sendTheMail();
	}
/*
 -------------	
         function pi_linkTP($str,$urlParameters=array(),$cache=0,$altPageId=0)   {
                 $conf=array();
                 $conf['useCacheHash'] = $this->pi_USER_INT_obj ? 0 : $cache;
                 $conf['no_cache'] = $this->pi_USER_INT_obj ? 0 : !$cache;
                 $conf['parameter'] = $altPageId ? $altPageId : ($this->pi_tmpPageId ? $this->pi_tmpPageId : $GLOBALS['TSFE']->id);
                 $conf['additionalParams'] = $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParameters,'',1).$this->pi_moreParams;
 
                 return $this->cObj->typoLink($str, $conf);
         }
 
         function pi_linkTP_keepPIvars($str,$overrulePIvars=array(),$cache=0,$clearAnyway=0,$altPageId=0)        {
                 if (is_array($this->piVars) && is_array($overrulePIvars) && !$clearAnyway)      {
                         $piVars = $this->piVars;
                         unset($piVars['DATA']);
                         $overrulePIvars = t3lib_div::array_merge_recursive_overrule($piVars,$overrulePIvars);
                         if ($this->pi_autoCacheEn)      {
                                 $cache = $this->pi_autoCache($overrulePIvars);
                         }
                 }
                 $res = $this->pi_linkTP($str,Array($this->prefixId=>$overrulePIvars),$cache,$altPageId);
                 return $res;
         }	
-------------	
*/
}

?>