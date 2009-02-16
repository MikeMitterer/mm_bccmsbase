<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Mike Mitterer (office@bitcon.at)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * ZIPs a File
 *
 * @author		Mike Mitterer <office@bitcon.at>
 */


if (!defined ('PATH_typo3conf')) 	die ('The configuration path was not properly defined!');

define('PATH_bclib', PATH_typo3conf . 'ext/mm_bccmsbase/lib/');

require_once(PATH_t3lib.'class.t3lib_db.php');
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');

require_once(PATH_t3lib . 'class.t3lib_userauth.php');
require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');
require_once(PATH_t3lib . 'class.t3lib_extfilefunc.php');

require_once(PATH_t3lib . 'class.t3lib_cs.php');
require_once(PATH_tslib . 'class.tslib_fe.php');
require_once(PATH_tslib . 'class.tslib_feuserauth.php');

require_once(PATH_bclib . 'class.mmlib_filehandling.php');
require_once(PATH_bclib . 'class.mmlib_log.php');
require_once(PATH_bclib . 'class.mmlib_crypt.php');

/**
 * Script Class, generating the page output.
 * Instantiated in the bottom of this script.
 *
 * @author	Mike Mitterer <office@bitcon.at>
 */

class SC_bclib_zipdamfile {
	var $content;								// Page content accumulated here.
	var	$log;										// Instance of mmlib_log
	var $aAllowedFileFormats;		// These formats are allowed
	var $damid;
	var $src;
	var $filemd5;
	var $target;
	var $aDataFromURL;
//	var $userauth;
	
		// Parameters loaded into these internal variables:

	/**
	 * Init function, setting the input vars in the global space.
	 *
	 * @return	void
	 */
	function init()	
		{
		if(false) {
			debug("----- Start Sys-Info -----",1);
			debug("TYPO3_OS: " . TYPO3_OS,1);
			debug("TYPO3_MODE: " . TYPO3_MODE,1);
			debug("PATH_thisScript: " . PATH_thisScript,1);
			debug("PATH_site: " . PATH_site,1);
			debug("PATH_t3lib: " . PATH_t3lib,1);
			debug("----- End Sys-Info ------",1);
		}
		
		// Loading internal vars with the GET/POST parameters from outside:
		/*
		$this->damid 	= t3lib_div::_GP('damid');
		$this->src 		= t3lib_div::_GP('src');
		$this->target 	= t3lib_div::_GP('target');
		*/
		
		$this->id 	= t3lib_div::_GP('id');
		if (!$this->id)	die('Parameter Error: No ID given.');
		//$this->id was $this->data
		
		// to transfer the Userdata with a cookie made some Problems on the Main-Server
		// so - these data are comming with the normal params
		//$crypt = new mmlib_crypt();
		//$this->aDataFromURL = $crypt->decryptData($this->data);
global $TYPO3_CONF_VARS;
		// Need to connect to database, because this is used by UserAuth.
		$GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
		
//-----
		$queryID = $GLOBALS['TYPO3_DB']->quoteStr($this->id,'tx_mmdamfilelist_additionalinfo');
		$queryID = str_replace(' ','',$queryID);
		
		$SQL['select']			= '*';
		$SQL['local_table']		= 'tx_mmdamfilelist_additionalinfo';
		$SQL['where']			= "uniqueid='" . $queryID . "'";
		$SQL['group_by']		= ''; 
		$SQL['order_by']		= '';
		$SQL['limit']			= '';
	
		$showLastQuery = false;
		if($showLastQuery) {
			$GLOBALS['TYPO3_DB']->debugOutput = true;
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		}
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$SQL['select'],
				$SQL['local_table'],
				$SQL['where'],             
				$SQL['group_by'],
				$SQL['order_by'],
				$SQL['limit']
				);	
		if($showLastQuery) t3lib_div::debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"lastBuiltQuery=");

		$record = null;
		if($res && ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$this->aDataFromURL = $record;
			if($showLastQuery) {
			t3lib_div::debug($record,"record=");
			t3lib_div::debug($this->aDataFromURL,"this->aDataFromURL=");
			}
		}		
		
//-----		
//		$this->userauth = t3lib_div::makeInstance('tslib_feUserAuth');
		//if(!isset($GLOBALS["TSFE"]->fe_user->user))		$this->userauth->start();

		
		// !!!!!!!!! VEEERRRRYYY Important - if not set, the start function does not knwo which user the init
//		$this->userauth->name = 'fe_typo_user';
		//$this->userauth->start();
		// start does noth work here!!!!
//		$this->userauth->id = isset($_COOKIE['fe_typo_user']) ? stripslashes($_COOKIE['fe_typo_user']) : '';
//		$this->userauth->fetchSessionData();
		
//		$this->aDataFromURL = $this->userauth->getKey("ses","zipdata_" . $this->id);
		if (!$res || $record == null )	die('Parameter Error: Wrong ID - no data found with this ID');
		
		$this->damid 	= $record['damid'];
		$this->src 		= $record['src'];
		$this->target 	= $record['target'];
		$this->filemd5 	= $record['filemd5'];
		
		// $this->aAllowedFileFormats = array('gif','jpg','jpeg','tif','pdf','doc','xls','zip','jar','exe','sit');
		//$this->aAllowedFileFormats = split(',',$this->aDataFromURL['valid_extensions']);
		
		//debug($this->aDataFromURL);
		
		// ***********************
		// Check parameters
		// ***********************
			// If no file-param is given, we must exit
		if (!$this->damid)					die('Parameter Error: No DAM-id given.');
		if (!$this->src)					die('Parameter Error: No SRC-File given.');
		if (!$this->target)					die('Parameter Error: No TARGET-File given.');
		//if (!$this->aAllowedFileFormats)	die('Parameter Error: No VALID_EXTENSIONS given.');
		
		$this->log = t3lib_div::makeInstance('mmlib_log');
		$this->log->init();
		}

	/**
	 * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
	 * Accumulates the content in $this->content
	 *
	 * @return	void
	 */
	function main()	
		{
		if(!isset($this->damid) || !isset($this->src) || !isset($this->target))
			{
			return;
			}

		$filehandler = t3lib_div::makeInstance('mmlib_FileHandling');
		
		$uploadFolder 		= 'uploads/tx_mmdamfilelist/';
		$fileToDownload 	= $uploadFolder . $this->src;
		$aSourceFile		= t3lib_div::split_fileref($this->src);
		$aTargetFile		= t3lib_div::split_fileref($this->target);

		// Make shure that the extension is the same like the hash-sourcefile
		$realSourceFile		= $aTargetFile['filebody'] . '.' . $aSourceFile['realFileext']; 

		// Fileformat must be allowed
		//if(!in_array($aSourceFile['fileext'],$this->aAllowedFileFormats)) die('Extension ' . $aSourceFile['fileext'] . ' not allowed...');
		//if(!in_array($aTargetFile['fileext'],$this->aAllowedFileFormats)) die('Extension ' . $aTargetFile['fileext'] . ' not allowed...');

		$logCategory = 'no category found';
		$DAMData = $this->queryForDAMData($this->damid);
		if($DAMData != null) $logCategory = $DAMData['category'];
		/*
		debug($DAMData);
		debug($aSourceFile);
		debug($aTargetFile);
		debug($this->aDataFromURL);
		*/
		
		$fileToDownloadDAM 	= $DAMData['filepath'] . $DAMData['filename'];
		$filemd5DAM			= md5_file(PATH_site . $fileToDownloadDAM);
		
		if((!is_file(PATH_site . $fileToDownloadDAM)) || 
			$filemd5DAM != $this->filemd5) {
			/*
			debug(PATH_site . $fileToDownloadDAM,1);
			debug($DAMData,1);
			debug($filemd5DAM,1);
			debug($this->aDataFromURL,1);
			*/
			die("Wrong file request!");
		}
		
		$copyResult	= $fileToDownloadDAM;
		//Correct filename from hasch to real filenam		
		//$copyResult = $filehandler->copyFile($uploadFolder . $this->src,$uploadFolder . $realSourceFile);
		//if($copyResult == false) return;
		
		$fileToDownload = $copyResult;

		// isZIPFile only checks for the extension
		$zipResult = null;
		if($filehandler->isZIPFile($this->target) == true)
			{
			$zipResult = $filehandler->zipFile($copyResult);
			if($zipResult == false) {
				die("Could not ZIP the file, maybe the zip-Programm ist not installed. (must be /usr/bin/zip)");
				return;
			}
			
			$fileToDownload = $zipResult;
			//$filehandler->removeFile($copyResult);
			}
			
		$this->writeLog('Try to download',$realSourceFile,$logCategory);
		if($filehandler->downloadFile($fileToDownload))
			{
			$this->writeLog('File downloaded',basename($fileToDownload),$logCategory);
			//$filehandler->removeFile($fileToDownload);
			}
			
		if($zipResult != null)	$filehandler->removeFile($zipResult);
		}

	/**
	 * Write the log-data to the logfile.
	 * If there is a cookie set with the current user - then the user ist
	 * logged too.
	 *
	 * @param	[string]		$message: Message (in english).
	 * @param	[string]		$filename: the filename which is handled
	 *
	 * @return	[void]
	 */
	function writeLog($message,$filename,$category)
		{
		$aLogData['file'] = $filename;
		$aLogData['category']	= $category;
		$aLogData['user']	= 'no login';

		if($this->aDataFromURL != null && is_array($this->aDataFromURL))
			{
			foreach($this->aDataFromURL as $key => $value)
				{
				$aLogData[$key]	= $value;
				}
			}
		
		// If someone knows a better way to give the username to
		// zip.php - please let me know...
		// The cookie ist set in class.mmlib_log.php
		if(is_array($_COOKIE) && isset($_COOKIE['user'])) 
			{
			if(isset($_COOKIE['user']))
				{
				$aLogData['user']	= $_COOKIE['user'];
				}
			else foreach($_COOKIE as $key => $value) $aLogData[$key] = $value;
			}
		$this->log->logMessage($message,'zip.php',MMLOG_INFO,$aLogData);
		}
		
	/**
	 * Looks in the the DAM Table for some data ($fieldsToSelect in this function) and
	 * returns the data in an array.
	 *
	 * @param	[string]		$damid: The record ID
	 *
	 * @return	[array]		Data-Array if everything is OK, null if the query failed
	 */
	function queryForDAMData($damid)
		{
		$GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
		
		// Prepare the MM Query - necessary because of the category name (title)
		$local_table 	= 'tx_dam'; 
		$mm_table 		= 'tx_dam_mm_cat'; 
		$foreign_table 	= 'tx_dam_cat';

		if($foreign_table == $local_table) {
			$foreign_table_as = $foreign_table . uniqid('_join');
			}
				
		$mmWhere = $local_table ? $local_table . '.uid=' . $mm_table . '.uid_local' : '';
		$mmWhere.= ($local_table AND $foreign_table) ? ' AND ' : '';
		$mmWhere.= $foreign_table ? ($foreign_table_as ? $foreign_table_as : $foreign_table).'.uid='.$mm_table.'.uid_foreign' : '';
	
		// These are the files wich will be returned by this function
		$fieldsToSelect = array(
														//'tx_dam.*',
														'tx_dam.uid',
														'tx_dam.file_name as filename',
														'tx_dam.file_path as filepath',
														'tx_dam_mm_cat.uid_local',
														'tx_dam_mm_cat.uid_foreign',
														'tx_dam_cat.uid as catid',
														'tx_dam.category as category_orig',
														'tx_dam_cat.title as category'
														 );
														 
		//$query['SELECT'] 				= 'tx_dam.*,tx_dam_mm_cat.uid_foreign,tx_dam_cat.uid as catid,tx_dam_cat.title as cattitle';
		$query['SELECT'] 				= implode(',',$fieldsToSelect);
		$query['FROM'] 					= $local_table . ',' . $mm_table . ',' . $foreign_table;
		$query['WHERE'] 				= '(' . $mmWhere . ' OR tx_dam.category=0 '.')' . " AND tx_dam.uid='" . $damid . "'";
		$queryParts['GROUPBY'] 	= '';
		$queryParts['ORDERBY'] 	= '';
		$queryParts['LIMIT'] 		= '';
	

		
		//debug($GLOBALS['TYPO3_DB']->SELECTquery($query['SELECT'],$query['FROM'],$query['WHERE'],$queryParts['GROUPBY'],$queryParts['ORDERBY'],$queryParts['LIMIT']));

		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($query);
	
		if($res && ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if($record['category_orig'] == 0) $record['category'] = 'no category defined';
			/*			
			debug('---------------------------',1);
			debug($record);
			debug('---------------------------',1);
			*/
			return $record;
			}
		// else debug(mysql_error());
		
		return null;
		}
	
	/**
	 * Just ad dummy function - copy from showpic.php
	 *
	 * @return	void
	 */
	function printContent()	
		{
		echo $this->content;
		}
}

// Include extension?
//if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/showpic.php'])	{
//	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/showpic.php']);
//}

// Make instance:
$SZIP = t3lib_div::makeInstance('SC_bclib_zipdamfile');
$SZIP->init();
$SZIP->main();
$SZIP->printContent();

/*
This part was in the main function.
I tried to get the current user without setting a cookie - 
I found no way!!!!
*/
//		$temp_TSFEclassName=t3lib_div::makeInstanceClassName('tslib_fe');
//		$GLOBALS["TSFE"] = new $temp_TSFEclassName($TYPO3_CONF_VARS,$temp_publish_id,0);
//		$GLOBALS["TSFE"]->connectToDB();

//		$objUserAuth 	= t3lib_div::makeInstance('tslib_feUserAuth');	
		
//		$objUserAuth->global_database = TYPO3_DB;
//		debug($_GET);
//		debug($_POST);
//		debug($_COOKIE);
//		debug($GLOBALS["TSFE"]);
//		if(!isset($GLOBALS["TSFE"]->fe_user->user))		$objUserAuth->start(); // Checks the UserID wicht is comming in by a cookie
		//debug($GLOBALS["TSFE"]->fe_user->fetchUserSession());
		
		//debug($GLOBALS['TYPO3_DB']);
		//$GLOBALS["TSFE"]->initFEuser();
//$objUserAuth->checkAuthentication();

//		debug($objUserAuth);
//		debug($objUserAuth->fetchUserSession());

?>