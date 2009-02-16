<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2006 Mike Mitterer (mike.mitterer@bitcon.at)
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
 * This class logs data to a file withing the DOCROOT 
 * (normaly fileadmin/log/damuserinfo.log_<year><month>.csv)
 *
 * @author	Mike Mitterer <mike.mitterer@bitcon.at>
 */
 
define('MMLOG_OK',0);
define('MMLOG_INFO',1);
define('MMLOG_NOTICE',2);
define('MMLOG_WARNING',3);
define('MMLOG_FATAL_ERROR',4);

if(! defined(PATH_site)) define('PATH_site', $_SERVER['DOCUMENT_ROOT'] . '/');

class mmlib_log
	{
	var $_strLogFileName;
	var $_strLogFolder;
	var $_aSeverity 			= null;
	var $_fLogFolderOK		= false;
	var $_strOutputFormat	= null;
	
	/**
	 * Constructor
	 *
	 * @param	[string]		$strFileName: Only the Filename - not the full path!
	 *
	 * @return	[void]
	 */
	function mmlib_log($strFileName = 'damuserinfo.log.csv')
		{
		$this->init($strFileName);
		}
		
	/**
	 * Make the Initialisation after t3lib_div::makeInstance
	 *
	 * @param	[string]		$strFileName: Only the Filename - not the full path!
	 *
	 * @return	[void]
	 */
	function init($strFileName = 'damuserinfo.log.csv')
		{
		$this->_strLogFileName 						= basename($strFileName);
		$this->_strLogFolder 							= PATH_site . 'fileadmin/log/';
		$this->_aSeverity[0]							= 'OK';
		$this->_aSeverity[1]							= 'Info';
		$this->_aSeverity[2]							= 'Notice';
		$this->_aSeverity[3]							= 'Warning';
		$this->_aSeverity[4]							= 'Fatal error';
		
		$this->_fAddDatePostfixToFilename	= true;	
		}
		
	/**
	 * Logdaten werden in ein File geschrieben
	 *
	 * @param	[string]		$msg: Message (in english).
	 * @param	[string]		$extKey: Extension key (from which extension you are calling the log)
	 * @param	[integer]		$severity: Severity: 1 is info, 2 is notice, 3 is warning, 4 is fatal error, 0 is "OK" message
	 * @param	[array]			$dataVar: dditional data you want to pass to the logger.
	 *
	 * @return	[void]
	 */
	function logMessage($msg,$extKey,$severity=0,$dataVar = null)
		{
		$msgSeverity 	= $this->_aSeverity[t3lib_div::intInRange($severity,0,4,0)];
		$msgDate 			= 
		$aContent 		= array(strftime('%b %d %H:%M:%S',time()),
													$extKey,
													$msgSeverity,
													$msg,
													);
		
		if(is_array($dataVar))
			{
			foreach($dataVar as $key => $value)
				{
				$aContent[] = $value;
				}
			}
		$this->_addToLogFile($this->_strLogFileName,$aContent);
		}
		
	/**
	 * Specify the outputformat for the log-string.
	 * If $formatstring is null then alle the data are separated by a semicolon (;)
	 *
	 * @param	[string]		$formatstring: The same format-specification as for sprintf
	 *
	 * @return	[void]	
	 */
	function setOutputFormatString($formatstring = null)
		{
		$this->_strOutputFormat	= $formatstring;
		}
		
	/**
	 * Write content to file. Shows a die-Message if the file ist not writeable
	 *
	 * @param	[string]		$filename: full path to Log-File
	 * @param	[array]			$content: All the data for one line in the log-File
	 * @param	[boolean]		$addNewLine: TRUE if a \n should be added at the end of the line 
	 *
	 * @return	[boolean]	
	 */
	function _addToLogFile($filename,$aContent,$addNewLine = true)
		{
		$filename = $this->_getRealLogFileName($filename);
		
		// If there is no folder - create one
		if(!$this->_fLogFolderOK)
			{
			$this->_fLogFolderOK = $this->_makeValidLogFile($this->_strLogFolder,basename($filename));
			}

		if($this->_strOutputFormat == null) $content = implode(';',$aContent);
		else $content = sprintf($this->_strOutputFormat,$aContent);  // You can specify your own format
					
		if(is_file($filename) && is_writeable($filename))
			{
			$fh = fopen($filename,'ab');
			if($fh)
				{
				fwrite($fh,$content . ($addNewLine ? "\n" : ''));
				fclose($fh);
				
				t3lib_div::fixPermissions($filename);   // Change the permissions of the file
				
				return true;
				}
			}
		else die('The LOGFile:' . $filename . ' is not valid or writeable...');
		
		return false;
		}
	
	/**
	 * Adds the full path to the filename.
	 * Full path means the path in the doc-root where the log file should be 
	 * placed. (normaly fileadmin/log/)
	 *
	 * If _fAddDatePostfixToFilename then the logfilename is extended with the
	 * current year and current month
	 *
	 * @param	[string]		$filename: Log-Filename
	 *
	 * @return	[string]	The Filename including the path withing the Doc-Root
	 */
	function _getRealLogFileName($filename)
		{
		$aFileName = t3lib_div::split_fileref($filename);
		
		if($this->_fAddDatePostfixToFilename) $aFileName['filebody'] .= strftime('_%y%m',time()); 

		return escapeshellcmd($this->_strLogFolder . $aFileName['filebody'] . '.' . $aFileName['realFileext']);				
		}


	/**
	 * Creates a directory if necessary and makes a new file if necessary 
	 *
	 * @param	[string]		$folder: Path for the Log-File
	 * @param	[string]		$logfile: Fildname for the Log-File
	 *
	 * @return	[void]	
	 */
	function _makeValidLogFile($folder,$logfile)
		{
		$realfilename = $folder . $logfile;
		
		if(is_file($realfilename) && is_writeable($realfilename)) return true;
		
		if(!is_dir($folder))
			{
			if(!t3lib_div::mkdir($folder)) die('Was not able to create the folder ' . $folder);
			}
			
		if(!is_file($realfilename))
			{
			if(!touch($realfilename)) die('Was not able to create the file ' . $realfilename); 
			t3lib_div::fixPermissions($realfilename);   // Change the permissions of the file
			}
			
 		if(!is_writeable($realfilename)) die('You dont have write-permission to this file: ' . $realfilename); 
 		
 		return true;
		}
	}
	
?>