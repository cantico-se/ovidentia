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
	$arr_ini = @parse_ini_file( $GLOBALS['babInstallPath'].$name.'/addonini.php');
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

// Used in addons from 5.4.2
function bab_sqlAutoCreateTables($file)
	{
	$fileContent = '';
	$f = fopen($file,'r');
	if ($f === false)
		{
		trigger_error('bab_sqlAutoCreateTables : can\'t read sql dump file : '.$file, E_USER_ERROR);
		return false;
		}
	while (!feof($f)) 
		{
		$fileContent .= fread($f, 1024);
		}

	$reg = "/((CREATE\s+TABLE).*?)\;/s";
	if (preg_match_all($reg, $fileContent, $m))
		{
		$db = & $GLOBALS['babDB'];
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$query = $m[1][$k];
			
			if (preg_match("/(CREATE\s+TABLE)\s+`?(.*?)`?\s+\(/", $query, $matches))
				{
				$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".$matches[2]."'"));
				if ( $arr[0] != $matches[2] && !$db->db_query($query))
					{
					trigger_error('bab_sqlAutoCreateTables : There is an error into sql dump file at query : <p>'.nl2br($query).'</p>', E_USER_ERROR);
					return false;
					}
				}
			else
				{
				trigger_error('bab_sqlAutoCreateTables : can\'t find table name in this query : <p>'.nl2br($query).'</p>', E_USER_ERROR);
				return false;
				}
			
			}
		return true;
		}
	else
		{
		trigger_error('bab_sqlAutoCreateTables : can\'t fetch file content : '.$file, E_USER_ERROR);
		return false;
		}
	}


?>