<?php

class mmlib_FileHandling {
	
	// Parameters loaded into these internal variables:

	/**
	 * Init function, setting the input vars in the global space.
	 *
	 * @return	void
	 */
	function init()	
		{
		// Loading internal vars with the GET/POST parameters from outside:
		}

	function copyFile($src,$target)
		{
		$target 				= escapeshellcmd($target);
		$src 					= escapeshellcmd($src);
		$copyCommand			= "cp";
		$aSourceFileName 		= t3lib_div::split_fileref($src);			
		$aTargetFileName 		= t3lib_div::split_fileref($target);	

		if(!is_file(PATH_site . $src)) die ("Sourcefile ($src) does not exist... (Target: $target)");
		if(!is_dir(PATH_site . $aTargetFileName['path'])) die ($aTargetFileName['path'] . ' is not a valid directory...');
		
		$fullCopyCommand = $copyCommand . ' ' . PATH_site . $this->quoteFileName($src) . 
			' ' . PATH_site . $this->quoteFileName($target);

		$aTemp = array();
		exec($fullCopyCommand,$aTemp,$returnValue);
		if($returnValue != 0) return false;
		
		return $target;
		}
	
	/*
	ToDo - Check of /usr/bin/zip ist installed (Windows-Version????)
	*/	
	function zipFile($filename,$fAddTimePostfix = true)
		{
		$zipCommand					= "/usr/bin/zip -j";
		$filename					= escapeshellcmd($filename);
		$aFileName 					= t3lib_div::split_fileref($filename);	
		$timePostfix				= $fAddTimePostfix ? strftime("_%y%m%d_%H%M%S",time()) : '';
		//$zipFileName				= $aFileName['path'] . $aFileName['filebody'] . $timePostfix . '.zip';
		$zipFileName				= 'typo3temp/' . $this->correctFileBody($aFileName['filebody'],true) . $timePostfix . '.zip';
		
		//debug(PATH_site);
		if(!is_file(PATH_site . $filename)) die ("Sourcefile ($src) does not exist...");
		if(!is_dir(PATH_site . $aFileName['path'])) die ($aFileName['path'] . ' is not a valid directory...');

		$fullZIPCommand = $zipCommand . ' ' . PATH_site . $this->quoteFileName($zipFileName) . 
			' ' . PATH_site . $this->quoteFileName($filename);
		//debug($fullZIPCommand);
		
		$aTemp = array();
		exec($fullZIPCommand,$aTemp,$returnValue);
		if($returnValue != 0) {
			t3lib_div::debug($fullZIPCommand,'$fullZIPCommand');
			return false;
		}
		
		return $zipFileName;
	}

	/**
	 * Puts double quote's around the filename
	 *
	 * @param	[string]		$filename: Filename or path which should be quoted
	 *
	 * @return	[string]	The $filename with quotes - or if the file in the filename was empty, it returns the original filename
	 */
	function quoteFileName($filename)
		{
		$aFileName 	= t3lib_div::split_fileref($filename);
		$aFileName['file'] = str_replace('"','',$aFileName['file']);
		
		if(strlen($aFileName['file']) == 0)	return $filename;
		
		return '"' . $aFileName['path'] . $aFileName['file'] . '"';
		}
		
	/**
	 * Replaces ., +, blanks aso. with an underscore
	 *
	 * @param	[string]		$filename: Filename which should be modified
	 *
	 * @return	[string]	The modified filename
	 */
	function correctFileBody($filename,$removeUmlaute = false)
		{
		$aNotAllowed = array( ' ', '+', '/', '\'', '(', ')', '.' );
		$aUmlaute = array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß');
		
		$aClear = array_merge($aNotAllowed,$aUmlaute);
		
		//return preg_replace("#[^a-zA-Z0-9_]#",'',$filename);
		
		return str_replace($aClear,'_',$filename);
		}
		
	function removeFile($filename)
		{
		return unlink(PATH_site . $filename);	
		}
				
	function downloadFile($origFilename,$FileNameToShow = null)
		{
		$origFilename 			= escapeshellcmd($origFilename);	
		if($FileNameToShow 	== null) $FileNameToShow = $origFilename;
		$FileNameToShow 		= escapeshellcmd($FileNameToShow);	
		$aFileNameToShow		= t3lib_div::split_fileref($FileNameToShow);
		$FileNameToShow 		= $aFileNameToShow['filebody'] . '.' . $aFileNameToShow['fileext'];
			
		header("Content-Type: application/octet-stream; name=\"$FileNameToShow\"");
		header("Content-Disposition: attachment; filename=\"$FileNameToShow\"");
		header("Content-Type: application/force-download");
		header("Content-Type: application/download"); // for IE
		header("Content-transfer-encoding: binary");
		header("Content-Length: " . filesize(PATH_site . $origFilename));
		//header("Pragma: no-cache");
		header("Expires: 0");
		
		return readfile(PATH_site . $origFilename);
		}

	function isZIPFile($filename)
		{
		$aFileName 		= t3lib_div::split_fileref($filename);			
		return (strtolower($aFileName['realFileext']) == 'zip');
		}		
		
}
?>