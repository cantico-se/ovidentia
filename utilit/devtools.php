<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/

/**
* @internal SEC1 PR 16/02/2007 FULL
*/


include_once "base.php";

// Used in addons from 5.4.1
class bab_myAddonSection
{
function bab_myAddonSection()
	{
	$this->elements_types = array('list','strong','text','script');
	$this->elements = array();
	}

function addElement($type)
	{
	if (in_array($type,$this->elements_types))
		{
		$id = count($this->elements);
		$this->elements[] = array(0 => $type, 1 => array());
		return $id;
		}
	else 
		{
		trigger_error("Parameter of addElement function must be part of : ".implode(',',$this->elements_types), E_USER_ERROR);
		return false;
		}
	}

function removeElement($id)
	{
	if (isset($this->elements[$id])) 
		{
		unset($this->elements[$id]);
		return true;
		}
	else return false;
	}

function pushHtmlData($id,$str, $attrib = false)
	{
	$strattrib = '';
	if (is_array($attrib))
		{
		foreach ($attrib as $key => $value)
			$strattrib .= $key.'="'.htmlentities($value).'" ';
		}
	$this->elements[$id][1][] = array(0 => $str, 1 => $strattrib);
	}

function getnextelement()
	{
	return count($this->elements) > 0 ? list($this->id,list($this->type, $this->html)) = each($this->elements) : false;
	}

function getnextitem()
	{
	return isset($this->html) ? list($null, list($this->str, $this->attrib)) = each($this->html) : false;
	}

function getHtml()
	{
	return bab_printTemplate($this,"insections.html", "myaddonsection");
	}

}



// Used in addons from 5.4.2
function bab_isAddonInstalled($name = '')
{
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	if (empty($name)) {
		$name = $GLOBALS['babAddonFolder'];
	}
		
	$addons = bab_addonsInfos::getRows();
		
	foreach ($addons as $value) {
	
		if ($value['title'] == $name) {
			return bab_isAddonAccessValid($value['id']);
		}
	}
	

	return false;
}

// Used in addons from 5.4.2
function bab_tableAutoRecord($table)
{
global $babDB;
$res = $babDB->db_query("DESCRIBE ".$babDB->db_escape_string($table));
$update = false;
$cols = array();
$values = array();
while ( $arr = $babDB->db_fetch_array($res))
	{
	if ($arr['Extra'] == 'auto_increment' && !empty($_POST[$arr['Field']]))
		{
		$indexcol = $arr['Field'];
		$update = true;
		}

	if (isset($_POST[$arr['Field']]))
		{
		$cols[] = $arr['Field'];
		$values[] = $_POST[$arr['Field']];
		}
	}


if (count($cols) > 0)
	{
	if ($update)
		{
		$ud = array();
		foreach ($cols as $k => $col)
			{
			$ud[] = $col."='".$babDB->db_escape_string($values[$k])."'";
			}

		$babDB->db_query("UPDATE ".$babDB->db_escape_string($table)." SET ".implode(',',$ud)." WHERE ".$babDB->db_escape_string($indexcol)."='".$babDB->db_escape_string($_POST[$indexcol])."'");
		return $_POST[$indexcol];
		}
	else
		{
		$babDB->db_query("INSERT INTO ".$babDB->db_escape_string($table)." (".implode(',',$cols).") VALUES (".$babDB->quote($values).")");
		return $babDB->db_insert_id();
		}
	}
return false;
}







class bab_synchronizeSql
	{
	var $fileContent = '';
	var $tables = array();
	var $create = array();
	var $insert = array();
	var $return = array();

	/*

	return array( table => action )

	action == 0 : nothing done on the table
	action == 1 : table has been created
	action == 2 : fields in the table has been updated

	*/

	function bab_synchronizeSql($file)
		{
		$this->file = $file;
		$this->db = &$GLOBALS['babDB'];

		if ($this->getFileContent())
			{
			if ($this->getCreateQueries())
				{
				unset($this->fileContent);
				$this->showTables();
				$this->checkTables();
				}
			}
		}

	function getFileContent()
		{
		$f = @fopen($this->file,'r');
		if ($f === false)
			{
			trigger_error('There is an error into synchronizeSql function, can\'t read sql dump file '.$this->file);
			return false;
			}
		while (!feof($f)) 
			{
			$this->fileContent .= fread($f, 1024);
			}
		fclose($f);
		return true;
		}

	function getCreateQueries()
		{
		if (preg_match_all("/CREATE\s+TABLE\s+`(.*?)`\s+\((.*?)\;/s", $this->fileContent, $m))
			{
			for ($k = 0; $k < count($m[1]); $k++ )
				{
				$l = (strlen(strrchr($m[2][$k],')'))*-1);
				$fields = substr($m[2][$k],0,$l);


				$field = array();
				$keys = array();

				preg_match_all("/(.*?)[\s|\(]`(.*?)`.*/", $fields, $n);
				for ($l = 0; $l < count($n[2]); $l++ )
					{
					$key = trim($n[1][$l]);
					$f = $n[2][$l];
					
					if ('PRIMARY KEY' === $key) {
						$f = 'PRIMARY';
					}

					if (!empty($key))
						{
						$keys[$f] = trim(trim($n[0][$l]),",");
						}
					else
						{
						$field[$f] = str_replace("`$f`",'',$n[0][$l]);
						$field[$f] = trim(trim($field[$f]),",");
						}
					}

				$this->create[$m[1][$k]] = array(
								'create' => $m[0][$k],
								'fields' => $field,
								'keys' => $keys
								);
				
				}
			}
		else
			{
			trigger_error('can\'t fetch file content, no CREATE TABLE found in file : '.$this->file);
			return false;
			}

		return true;
		}


	function showTables()
		{
		
		global $babDB;
		
		$tables_in_files = array_keys($this->create);
		

		foreach ($tables_in_files as $table)
			{
			
			$this->tables[$table] = array();
			

			$req = '
					SELECT 
						`COLUMN_NAME` `Field`,
						`COLUMN_TYPE` `Type`,
						`IS_NULLABLE` `Null`,
						`COLUMN_KEY` `Key`,
						`COLUMN_DEFAULT` `Default`,
						`EXTRA` `Extra`,
						`COLUMN_COMMENT` `Comment`
						
					FROM 
						INFORMATION_SCHEMA.COLUMNS
					WHERE 
						table_name = '.$babDB->quote($table).' 
						AND table_schema = '.$babDB->quote($GLOBALS['babDBName']).' 
				';
			
			
			$res2 = $babDB->db_queryWem($req);
			
			if (!$res2) {
				// alternate methode if no inforamtion schema
			
				$res2 = $babDB->db_query("SHOW COLUMNS FROM ".$babDB->db_escape_string($table));
			}
			
			while ($arr = $babDB->db_fetch_assoc($res2))
				{
				$this->tables[$table][$arr['Field']] = $arr;
				}
			}
		}

	function checkTables()
		{
		
		include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';
		
		foreach($this->create as $table => $arr)
			{
			if (bab_isTable($table))
				{
				if ($this->checkFields($table))
					$this->return[$table] = 2;
				else
					$this->return[$table] = 0;
				}
			else
				{
				$this->db->db_query(trim($this->create[$table]['create']," ;"));
				$this->return[$table] = 1;
				}
			}
		}
		
		
	function getTableKeysDetail($tablename) {
		global $babDB;
		
		$res = $babDB->db_query('SHOW INDEX FROM '.$babDB->backTick($tablename));
		$fields = array();
		$key_type = array();
		$non_unique = array();
		while ($arr = $babDB->db_fetch_assoc($res)) {
		
			if ('PRIMARY' === $arr['Key_name']) {
				if (isset($fields['PRIMARY'])) {
					$fields['PRIMARY'][] = $babDB->backTick($arr['Column_name']);
				} else {
					$fields['PRIMARY'] = array($babDB->backTick($arr['Column_name']));
				}

				$key_type['PRIMARY'] = 'PRI';

			} else {
		
				if (isset($fields[$arr['Key_name']])) {
					$fields[$arr['Key_name']][] = $babDB->backTick($arr['Column_name']);
				} else {
					$fields[$arr['Key_name']] = array($babDB->backTick($arr['Column_name']));
				}
				

				if ($arr['Non_unique']) {
					$key_type[$arr['Key_name']] = 'MUL';
				} else {
					$key_type[$arr['Key_name']] = 'UNI';
				}
			}
			
			
			
			
		}
		
		
		$keys = array();
		foreach($key_type as $Key_name => $type) {
		
			switch($type) {
				case 'PRI':
					$colname = trim($fields[$Key_name][0],'`');
					$keys[$Key_name] = 'PRIMARY KEY ('.implode(', ', $fields[$Key_name]).')';
					break;
				
				case 'MUL':
					$keys[$Key_name] = 'KEY '.$babDB->backTick($Key_name).' ('.implode(', ', $fields[$Key_name]).')';
					break;
					
				case 'UNI':
					$keys[$Key_name] = 'UNIQUE KEY '.$babDB->backTick($Key_name).' ('.implode(', ', $fields[$Key_name]).')';
					break;
			}
		}
		
		return $keys;
	}
	
	
	/**
	 * Add key on table
	 *
	 */
	function addKey($table, $key, $keydetail) {
		global $babDB;
		$babDB->db_queryWem('ALTER TABLE '.$babDB->backTick($table).' ADD '.$keydetail);
	}
	


	function checkFields($table)
		{
		$return = false;


		foreach($this->create[$table]['fields'] as $field => $options)
			{
			if (isset($this->tables[$table][$field]))
				{
				if ($this->checkOptions($table, $field))
					$return = true;
				}
			else
				{
				$this->db->db_query("ALTER TABLE ".$this->db->backTick($table)." ADD ".$this->db->backTick($field)." ".$options);
				$return = true;
				}
			}
			
		$keys = $this->getTableKeysDetail($table);
		
			
		foreach($this->create[$table]['keys'] as $key => $keydetail) 
			{
			
			
			if (isset($keys[$key]))
				{
				if ($this->checkKeyDetail($table, $key, $keys[$key], $keydetail)) {
					$return = true;
					}
				}
			else
				{
				$this->addKey($table, $key, $keydetail);
				$return = true;
				}
			}

		foreach($this->tables[$table] as $field => $arr)
			{
			if (!isset($this->create[$table]['fields'][$field]))
				{
				$this->db->db_query("ALTER TABLE ".$this->db->backTick($table)." DROP ".$this->db->backTick($field));
				$return = true;
				}
			}

		return $return;
		}
		
		
		
	function trimall($str) {
	  	return strtolower(str_replace(array(' ', "\t", "\n", "\r", "\0", "\x0B"), '', $str));
	}
		
		
		
		
		

	function checkOptions($table, $field)
		{
		$option_file = $this->create[$table]['fields'][$field];
		
		$null = $this->tables[$table][$field]['Null'] != 'YES' ? ' NOT NULL' : '';
		$default = $this->tables[$table][$field]['Default'] != '' || false !== strpos($this->tables[$table][$field]['Type'],'char') ? " default '".$this->tables[$table][$field]['Default']."'" : '';
		$extra = !empty($this->tables[$table][$field]['Extra']) ? ' '.$this->tables[$table][$field]['Extra'] : '';
		$comment = !empty($this->tables[$table][$field]['Comment']) ? " COMMENT '".$this->tables[$table][$field]['Comment']."'" : '';
		$option_table = $this->tables[$table][$field]['Type'].$null.$default.$extra.$comment;
		
		$old = $this->trimall($option_table);
		$new = $this->trimall($option_file);
		
		
		if ($old != $new)
			{
			$this->db->db_query("ALTER TABLE `".$this->db->db_escape_string($table)."` CHANGE `".$this->db->db_escape_string($field)."` `".$this->db->db_escape_string($field)."` ".$option_file);
			return true;
			}

		return false;
		}
		
		
	
		
	/**
	 * verify key
	 * if key is different, drop and create
	 */
	function checkKeyDetail($table, $key_name, $existing_key, $new_key) {
		
		$old = $this->trimall($existing_key);
		$new = $this->trimall($new_key);
		
		if ($old != $new) {
			
			global $babDB;
			$babDB->db_query('ALTER TABLE '.$babDB->backTick($table).' DROP KEY '.$babDB->backTick($key_name));
			$this->addKey($table, $key_name, $new_key);
		}
	}

	
	function isWorkedTable($table)
		{
		return isset($this->return[$table]);
		}

	function isCreatedTable($table)
		{
		return 1 == $this->return[$table];
		}

	function isModifiedTable($table)
		{
		return 2 == $this->return[$table];
		}

	function isUnmodifiedTable($table)
		{
		return 0 == $this->return[$table];
		}
	}

/**
 * Get sql file
 * @param	array			$tables
 * @param	false|string	[$file]
 * @return 	boolean|string
 */
function bab_export_tables($tables, $file = false)
	{
	include_once $GLOBALS['babInstallPath']."utilit/sqlincl.php";

	$bab_sqlExport =  new bab_sqlExport($tables);
	$dump = $bab_sqlExport->exportString();

	if (!$file) return $dump;
	
	if (is_writable($file)) {
		$handle = fopen($file, 'w+');
		fwrite($handle, $dump);
		fclose($handle);
		return true;
		}

	return false;
	}



/**
 * Exec sql file with semi-columns separated queries
 * Used by addons
 * @param	string	$file
 * @return 	boolean
 * @since	6.4.94
 */
function bab_execSqlFile($file) {
	
	global $babDB;
	$content = '';
	
	$fp=fopen($file,"rb");
	if ($fp) {
		while (!feof($fp)) {
			$content .= fread($fp, 8192);
		}
		
		fclose($fp);
	}
	
	
	if (!$content) {
		return false;
	}
	
	// match sql query, split with ; but ignore ; in content strings
	$reg = "/\w(?:[^';]*'(?:[^']|\\\\'|'')*')*[^';]*;/";
	if (preg_match_all($reg, $content, $m)) {
		for ($k = 0; $k < count($m[0]); $k++ ) {
			$query = trim($m[0][$k]);
			if (!empty($query)) {
				if (!$babDB->db_query($query)) {
					return false;
				}
			}
		}	
		return true;
	}
	return false;
}




function bab_f_getDebug() {

	global $babBody;

	class bab_f_getDebugCls {
		var $messages;
		var $message;
		
		var $nb_messages = 0;
		var $t_messages;
		
		function bab_f_getDebugCls() {
			$this->messages = $GLOBALS['bab_debug_messages'];
			$this->nb_messages += count($this->messages);
			$this->t_messages = bab_translate('Messages');
			$this->t_categories = bab_translate('Categories');
			$this->t_all_categories = bab_translate('All');
			
			$this->categories = array();
			foreach ($this->messages as $message) {
				$category = $message['category'];
				$this->categories[$message['category']] = $message['category'];
			}
			reset($this->messages);
			reset($this->categories);
		}

		function color_query(&$str) {

			if (preg_match('/UPDATE|INSERT|SELECT|DELETE/', $str)) {

				$str = preg_replace("/(\(|\)|=|\<|\>)/","<span style=\"color:blue\">\\1</span>",$str);
				$str = preg_replace("/(form_tbl_\d{4})/","<span style=\"color:green\">\\1</span>",$str);
				$str = preg_replace("/(UPDATE|SET|INSERT|INTO|VALUES|SELECT|ORDER BY|GROUP BY|ASC|DESC|LEFT JOIN|ON|WHERE|FROM|AND|OR|MIN|IN|LIKE|CONCAT|SUM|MAX|UNIX_TIMESTAMP|MONTH|DAY|YEAR)/","<span style=\"color:red\">\\1</span>",$str);
				$str = preg_replace("/('(\w|%|\s)+')/","<span style=\"color:orange\">\\1</span>",$str);
				$str = preg_replace("/(CASE|WHEN|THEN|END)/","<span style=\"color:blue\">\\1</span>",$str);
			}
		}

		function getNextMessage() {
			if (list(, $this->message) = each($this->messages)) {
				//$this->text = htmlspecialchars($this->text);
				$this->message['file'] = basename($this->message['file']);
				$this->color_query($this->message['text']);
				return true;
			}
			reset($this->messages);
			return false;
		}

		function getNextCategory() {
			if (list(, $this->category) = each($this->categories)) {
				return true;
			}
			reset($this->categories);
			return false;
		}
	}

	$babBody->addStyleSheet('debug.css');

	if (isset($GLOBALS['babBodyPopup'])) {
		$GLOBALS['babBodyPopup']->addStyleSheet('debug.css');
	}

	$temp = new bab_f_getDebugCls();


	if (defined('BAB_DEBUG_SEND_TO')) {

		include_once $GLOBALS['babInstallPath']."utilit/mailincl.php";

		$mail = bab_mail();
		if( $mail != false ) {
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			$mail->mailSubject(bab_translate("Ovidentia debug informations"));
			$mail->mailBody(bab_printTemplate($temp, 'devtools.html', 'debug_mail'), "html");
			$mail->mailTo(BAB_DEBUG_SEND_TO);
			$mail->send();
		}
	}

	return bab_printTemplate($temp, 'devtools.html', 'debug');
}


/**
 * Print a backtrace
 * Need error_reporting set with E_NOTICE
 * @param	boolean		[$echo]		display or send to bab_debug
 */
function bab_debug_print_backtrace($echo = false)
{
    
    $error_reporting = (int) ini_get('error_reporting');
    
    if (E_NOTICE !== ($error_reporting & E_NOTICE)) {
    	return;
    }
    
    // Get backtrace
    static $uniqueId = 0;
	$uniqueId++;
    $backtrace = debug_backtrace();

    // Unset call to debug_print_backtrace
    array_shift($backtrace);
    
    // Iterate backtrace
    $calls = array();
    foreach ($backtrace as $i => $call) {
    	if (isset($call['file']) && isset($call['line'])) {
        	$location = $call['file'] . ':' . $call['line'];
        } else {
        	$location = '';
        }
        $function = (isset($call['class'])) ?
            '<b>' . $call['class'] . '</b>.<b>' . $call['function'] . '</b>':
            '<b>' . $call['function'] . '</b>';
       
        $params = '';
        if (isset($call['args'])) {
			$nbParam = 0;
			foreach ($call['args'] as $param)
			{
				ob_start();
				print_r($param);
				$param_str = ob_get_contents();
				ob_end_clean();


				if ($nbParam > 0)
					$params .= ', ';
				$spanId = 'babParam_' . $uniqueId . '_' . $i . '_' . $nbParam;

				if(is_object($param))
				{
					$param = get_class($param);
				}

				$params .= '<span title="' . htmlEntities('[' . $param . ']') . '" style="cursor: pointer" onclick="s=document.getElementById(\'' . $spanId . '\'); s.style.display==\'none\'?s.style.display=\'\':s.style.display=\'none\'">[+]</span>'
						.  '<div style="display: none; background-color: #EEEECC" id="' . $spanId . '">' . htmlEntities('[' . $param_str . ']') . '</div>';
				$nbParam++;
			}
        }

        $calls[] = '#' . $i . ' ' . $function . '(' . $params . ') <i>called at</i> [' . $location . ']';
    }

	$display = implode("\n", $calls);

	if (function_exists('bab_isUserAdministrator')) {
		if ($echo && bab_isUserAdministrator()) {
			echo '<pre>'.$display.'</pre>';
		} else {
			bab_debug($display);
		}
	}
	
}

?>