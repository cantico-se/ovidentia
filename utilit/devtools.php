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
		trigger_error("Parameter of addElement fuction must be part of : ".implode(',',$this->elements_types), E_USER_ERROR);
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
			$strattrib .= $key.'="'.$value.'" ';
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
if (empty($name))
	$name = $GLOBALS['babAddonFolder'];
foreach ($GLOBALS['babBody']->babaddons as $value)
	if ($value['title'] == $name)
		{
		$version_base = $value['version'];
		break;
		}

if (isset($version_base))
	{
	$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$name.'/addonini.php');
	if ($arr_ini['version'] == $version_base)
		return true;
	}
return false;
}

// Used in addons from 5.4.2
function bab_tableAutoRecord($table)
{
$db = &$GLOBALS['babDB'];
$res = $db->db_query("DESCRIBE ".$table);
$update = false;
$cols = array();
$values = array();
while ( $arr = $db->db_fetch_array($res))
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
			$ud[] = $col."='".$values[$k]."'";
			}

		$db->db_query("UPDATE ".$table." SET ".implode(',',$ud)." WHERE ".$indexcol."='".$_POST[$indexcol]."'");
		return $_POST[$indexcol];
		}
	else
		{
		$db->db_query("INSERT INTO ".$table." (".implode(',',$cols).") VALUES ('".implode('\',\'',$values)."')");
		return $db->db_insert_id();
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
			trigger_error('can\'t fetch file content : '.$this->file);
			return false;
			}

		return true;
		}


	function showTables()
		{
		$res = $this->db->db_query("SHOW TABLES");
		while (list($table) = $this->db->db_fetch_array($res))
			{
			$this->tables[$table] = array();
			$res2 = $this->db->db_query("SHOW COLUMNS FROM ".$table);
			while ($arr = $this->db->db_fetch_assoc($res2))
				{
				$this->tables[$table][$arr['Field']] = $arr;
				}
			}

		}

	function checkTables()
		{
		foreach($this->create as $table => $arr)
			{
			if (isset($this->tables[$table]))
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
				$this->db->db_query("ALTER TABLE `".$table."` ADD `".$field."` ".$options);
				$return = true;
				}
			}

		foreach($this->tables[$table] as $field => $arr)
			{
			if (!isset($this->create[$table]['fields'][$field]))
				{
				$this->db->db_query("ALTER TABLE `".$table."` DROP `".$field."`");
				$return = true;
				}
			}

		return $return;
		}

	function checkOptions($table, $field)
		{
		$option_file = $this->create[$table]['fields'][$field];
		$null = $this->tables[$table][$field]['Null'] != 'YES' ? ' NOT NULL' : '';
		$default = $this->tables[$table][$field]['Default'] != '' || false !== strpos($this->tables[$table][$field]['Type'],'char') ? " default '".$this->tables[$table][$field]['Default']."'" : '';
		$extra = !empty($this->tables[$table][$field]['Extra']) ? ' '.$this->tables[$table][$field]['Extra'] : '';
		$option_table = $this->tables[$table][$field]['Type'].$null.$default.$extra;

		
		if ($option_file !== $option_table)
			{
			$this->db->db_query("ALTER TABLE `".$table."` CHANGE `".$field."` `".$field."` ".$option_file);
			return true;
			}

		return false;
		}
	}

	

?>