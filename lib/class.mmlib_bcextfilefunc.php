<?php
/*
Needs a
require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');
require_once(PATH_t3lib . 'class.t3lib_extfilefunc.php');
in the base class
*/

class mmlib_extFileFunctions extends t3lib_extFileFunctions	
	{
	var $zipPath = '';
	
	function start($fileCmds)
		{
		t3lib_extFileFunctions::start($fileCmds);
	
		$this->zipPath = (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['zip_path']) ?  $GLOBALS['TYPO3_CONF_VARS']['BE']['zip_path'] : $this->unzipPath);
		}
	
	function processData()	{
		if (!$this->isInit) return FALSE;
		
		t3lib_extFileFunctions::processData();
		
		if (is_array($this->fileCmdMap))	
			{
			// Traverse each set of actions
			foreach($this->fileCmdMap as $action => $actionData)	
				{
				// Traverse all action data. More than one file might be affected at the same time.
				if (is_array($actionData))	
					{
					foreach($actionData as $cmdArr)	
						{
						// Clear file stats
						clearstatcache();

						// All other commands are already processed in t3lib_extFileFunctions 
						switch ($action)	
							{
							case 'zip':
								$this->func_zip($cmdArr);
							break;
							}
						}
					}
				}
			}
		
		}	
		
	/**
	 * ZIP file (action=???)
	 * This is permitted only if the user has fullAccess or if the file resides
	 *
	 * @param	array		$cmds['data'] is the source-file. $cmds['target'] is the target path. If not set we'll default to the same directory as the file is in. $cmds['target_filename'] is the target_filename for the zip file
	 * @return	boolean		Returns true on success
	 */
	function func_zip($cmds)	{
		if (!$this->isInit || $this->dont_use_exec_commands) return FALSE;

		$theFile = $cmds['data'];
		if (@is_file($theFile))	{
			$fI = t3lib_div::split_fileref($theFile);
			if (!isset($cmds['target']))	{
				$cmds['target'] = $fI['path'];
			}
			if (!isset($cmds['target_filename']))	{
				$cmds['target_filename'] = $fI['file'] . '.zip';
			}
		
			$theDest = $this->is_directory($cmds['target']);	// Clean up destination directory
			if ($theDest)	{
				if ($this->actionPerms['unzipFile'])	{
					if ($fI['fileext'] !='zip')	{
						if ($this->checkIfFullAccess($theDest)) {
							if ($this->checkPathAgainstMounts($theFile) && $this->checkPathAgainstMounts($theDest.'/'))	{
									// No way to do this under windows.
								$cmd = $this->zipPath.'zip "'.$theFile.'" "' . $theDest . $cmds['target_filename'] . '"';
								exec($cmd);
								$this->writelog(7,0,1,'Zipping file "%s" in "%s"',Array($theFile,$theDest . $cmds['target_filename']));
								return TRUE;
							} else $this->writelog(7,1,100,'File "%s" or destination "%s" was not within your mountpoints!',Array($theFile,$theDest));
						} else $this->writelog(7,1,101,'You don\'t have full access to the destination directory "%s"!',Array($theDest));
					} else $this->writelog(7,1,102,'It is not allowed to zip a ZIP file','');
				} else $this->writelog(7,1,103,'You are not allowed to unzip files','');
			} else $this->writelog(7,2,104,'Destination "%s" was not a directory',Array($cmds['target']));
		} else $this->writelog(7,2,105,'The file "%s" did not exist!',Array($theFile));
	}

	}
?>