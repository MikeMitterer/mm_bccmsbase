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
 * This class adds some ENCRYPTEN-Helpers
 *
 * @author	Mike Mitterer <mike.mitterer@bitcon.at>
 */

class mmlib_crypt
	{
	var $_confuser					= '27';	// to confuse some hackerst ;-) (not very much...)

	/**
	 * ROT13 Encrypten (very simple)
	 * To avoid (a bit) the change of the data, I added a md5 encrypten as a third 
	 * block. In the decrypten function this md5 hash is rechecked again.
	 *
	 * @param	[array]		$arrayData: The array should be like this array('user' => 'demo');
	 *
	 * @return	[string]	The encrypted und urlencoded String
	 */
	function encryptData($arrayData)
		{
		$encryptedString 	= '';
		
		if(!is_array($arrayData)) $arrayBase[] = $arrayData;
		else $arrayBase = $arrayData;
		
		foreach($arrayBase as $key => $value)
			{
			$encryptedString .= str_rot13($key) . '~|~' . str_rot13($value) .	'~|~' . md5($key . $value . $this->_confuser) . '#|#';
			}
		return urlencode($encryptedString);
		}
		
	/**
	 * Decrypt the Data from the encryptData Function.
	 *
	 * @param	[string]		$encryptedString: urlencoded string. 
	 *
	 * @return	[string]	The encrypted und urlencoded String
	 */
	function decryptData($encryptedString)
		{
		$arrayDataReturn	= null;
		$baseString 			= urldecode($encryptedString);
		$arrayDataLine		= explode('#|#',$baseString);	// Breakes blocks apart (Block = key ~|~ value ~|~ md5 hash)
		
		if(!is_array($arrayDataLine)) return null;
		
		foreach($arrayDataLine as $value) {
			$arrayTemp = explode('~|~',$value);	// The Block is split in 3 sub-blocks
			if(!is_array($arrayTemp) || count($arrayTemp) != 3) continue;
			
			list($key,$value,$md5hash) = $arrayTemp;
			$key = str_rot13($key);
			$value = str_rot13($value);
			
			if($md5hash == md5($key . $value . $this->_confuser)){
				$arrayDataReturn[$key] = $value;
				}
			else {
				//debug($key . ' V:' . $value,1);
			}
		}
			
		return $arrayDataReturn;
	}
		
	}
?>