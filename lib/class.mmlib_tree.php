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
 * Generates a Tree out of a DB-Table (for example ts_dam_cat)
 *
 * @author	Mike Mitterer <mike.mitterer@bitcon.at>
 */


/*
 * links: Menü + tooltips
 * 	Menü: (in Verwendung) http://www.drweb.de/leseproben/klappmenu.shtml (Suckerfish Dropdown)
 * 		http://www.alistapart.com/articles/dropdowns/
 * 
 * 		http://css.fractatulum.net/sample/menu3format.htm
 * 		http://barrierefrei.e-workers.de/workshops/menues/index.html
 * 		http://www.howtocreate.co.uk/tutorials/testMenu.html
 * 		http://www.seoconsultants.com/css/menus/tutorial/
 * 
 *  Listen:
 * 		http://css.maxdesign.com.au/listutorial/horizontal_master.htm
 * 
 *  CSS-Tooltips:
 * 		http://www.communitymx.com/content/article.cfm?page=2&cid=4E2C0
 * 
 */
class mmlib_tree {
	var $table				= 'tx_dam_cat';
	var $parent_field		= 'parent_id';
	var $mmlib_extfrontend	= null;
	var	$template			= null;
	var $cObj				= null;
	var $childIDs			= null;
	
	/**
	 * Baseinitialisation
	 * Adds TSLIB-Base Object to this class
	 *
	 * @param	[object]		$mmlib_extfrontend: Baseobject, initialized through the main function
	 * @return	[void]
	 */
	function init($mmlib_extfrontend,$table = 'tx_dam_cat',$parentfieldname = 'parent_id') {
		$this->mmlib_extfrontend 	= $mmlib_extfrontend;
		$this->cObj 				= $mmlib_extfrontend->cObj;
		$this->template 			= $this->mmlib_extfrontend->getTemplateContent('catView');
		
		$this->setTable($table);
		$this->setParentFieldName($parentfieldname);
	}
	
	function setTable($table) {
		$this->table = $table;
	}
	
	function setParentFieldName($parentfieldname) {
		$this->parent_field = $parentfieldname;
	}
	
	/**
	 * Iterates through the Table-Tree and adds the 
	 * main Templatepart
	 *
	 * @param	[integer]	$uid: Start-UID in the Table
	 * @param	[integer]	$deep: Current nesting deep
	 * @return	[string]	Content produced by this function - normaly a list
	 */
	function getCategoriesTreeView($uid = 0,$recordParent = null,$deep = 0) {
		$content = '';
		
		$content = $this->iterateThroughTable($uid,$recordParent,$deep);
		
		$content = $this->cObj->substituteSubpart($this->template,'###LIST###',$content);
		
		return $content;
	}
	
	function getAllChildIDs($uid = 0) {
		$childIDs = array();
		
		$this->iterateThroughChilds($uid,$childIDs);
		//t3lib_div::debug($childIDs,1);
		
		return $childIDs;
	}
	
	/**
	 * Iterates through the Table-Tree
	 *
	 * @param	[integer]	$uid: Start-UID in the Table
	 * @param	[integer]	$deep: Current nesting deep
	 * @return	[string]	Content produced by this function - normaly a list
	 */
	function iterateThroughTable($uid = 0,$recordParent = null,$deep = 0) {
		$content 		= '';
	
		$SQL = $this->generateSQLStatement($uid);
	
		//$SQL['select'] 		= 'count(*)';
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$SQL['select'],
			$SQL['local_table'],
			$SQL['where'],             
			$SQL['group_by'],
			$SQL['order_by'],
			$SQL['limit']
			);	
		
		if($res == null) return;
		
		//t3lib_div::debug($res,1);
		//t3lib_div::debug($SQL,1);
		
		$deep++;
		
		
		$templateList = $this->cObj->getSubpart($this->template,'###LIST###');
		$templateItem = $this->cObj->getSubpart($this->template,'###LISTITEM###');
		
		$linktext = '';
		while(($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$markerArray 	= $this->fillMarkerArray($record,$deep);

			//t3lib_div::debug(str_repeat('--',2 * $deep));
			//t3lib_div::debug($deep . ' - ' . $record['uid'] . ',' . $record['parent_id'] . ',' . $record['title'],1);
			//$content .= '<li>' . $record['title'] . "</li>\n";
			
			$lineContent = $this->cObj->substituteMarkerArray($templateItem,$markerArray);

			// Recursive call to this function
			$subContent = $this->iterateThroughTable($record['uid'],$record,$deep);
			
			// If there is a subcontent then the current line is replaced by this subcontent
			if($subContent != '') {
				//$content .= '<li><ul>' . $subContent . '</ul></li>';
				$content .= $subContent;
			} else {
				$content .= $lineContent;
			}
		}
		
		if($content  != '') {
			// Sonst ist der erste Level nocheinmal mit einem LI Element versehen
			if($deep != 1) {
				$markerArray 	= $this->fillMarkerArray($recordParent,$deep);
				$templateList = $this->cObj->substituteMarkerArray($templateList,$markerArray);
	
				$content = $this->cObj->substituteSubpart($templateList,'###LISTITEM###',$content);
			} 
		}
	
	return $content;
	}
	
	/**
	 * Generates the base-SQL-statement for one child level
	 *
	 * @param	[integer]	$uid: Start-UID in the table
	 * @return	[string]	SQL Statement
	 */
	function generateSQLStatement($uid) {
		$WHERE['enable_fields']		= $this->mmlib_extfrontend->cObj->enableFields($this->table);
		$WHERE['parent']			= "AND $this->parent_field='$uid'";
		
		$SQL['limit']				= '';
		$SQL['select'] 				= "$this->table.*";
		$SQL['local_table']			= "$this->table";
		$SQL['group_by']			= '';
		$SQL['order_by']			= "$this->table.sorting";
		$SQL['where']				= $this->mmlib_extfrontend->implodeWithoutBlankPiece('AND ',$WHERE);
		
		//t3lib_div::debug($SQL,1);
		
		return $SQL;
	}
	
	/**
	 * Returns all Child and subChilds for a given ID
	 *
	 * @param	[integer]	$uid: Start-UID in the table
	 * @param	[integer]	$deep: Current nesting deep
	 * @param	[array]		$childIDs: Array which will be filled with the child-ids
	 * @return	[array]		All ChildIDs, null if there is no child
	 */
	function iterateThroughChilds($uid = 0,&$childIDs = array()) {
		$SQL = $this->generateSQLStatement($uid);
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$SQL['select'],
			$SQL['local_table'],
			$SQL['where'],             
			$SQL['group_by'],
			$SQL['order_by'],
			$SQL['limit']
			);	
		
		if($res == null) return;
		
		//t3lib_div::debug($res,1);
		//t3lib_div::debug($SQL,1);
		
		$deep++;
		while(($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			$childIDs[] = $record['uid'];
			// Recursive call to this function
			$this->iterateThroughChilds($record['uid'],$childIDs);
		}
	}
		
	/**
	 * The marker-Array is filled with the Data from the current Record
	 * If the current record is null the main-values are filled with blanks
	 *
	 * @param	[array]		$record: Current recordset - can be null
	 * @param	[integer]	$deep: Current nesting 
	 * @return	[array]		MarkerArray for filling the template
	 */
	function fillMarkerArray($record,$deep) {
		$markerArray 	= array();

		$markerArray['###HREF_BEGIN###']	= '';
		$markerArray['###HREF_END###']		= '';
		$markerArray['###TITLE###']			= '';
		$markerArray['###LEVEL###'] 		= $deep;
		$markerArray['###DEEP###'] 			= $deep;
		
		if($record != null) {
			$linktext		= $record['title'];
			$categoryid		= $record['uid'];
			$lineContent	= '';
			$subContent		= '';
			$description	= isset($record['description']) ? $record['description'] : '';
			
			foreach($record as $key=>$value) {
				$markerArray['###' . strtoupper($key) . '###'] = $value;
			}
			
	 		$conf			= array();
	 		$conf['title'] 	= $description;
	 		/* 
	 		$link			= $this->mmlib_extfrontend->pi_linkTP($linktext,
	 							array('mode' => 'category:' . $categoryid),
	 							$this->mmlib_extfrontend->allowCaching);
			*/
	 		$link			= $this->mmlib_extfrontend->linkTPkeepPIvars(
	 							$linktext,
	 							array('mode' => 'category:' . $categoryid,'viewmode' => 'cattree'),
	 							$this->mmlib_extfrontend->allowCaching,1,0,$conf);
	 							
			$href		= explode('>' . $linktext . '<',$link);
	
			$markerArray['###HREF_BEGIN###']	= $href[0] . '>';
			$markerArray['###HREF_END###']		= '<' . $href[1];
		}
		
		return $markerArray;
	}
}

?>