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
* @internal SEC1 PR 2006-12-12 FULL
*/


include_once "base.php";
class bab_database
{
var $db_type;
var $db_die_on_fail;

function bab_database($die = false, $dbtype = "mysql")
	{
	$this->db_die_on_fail = $die;
	$this->db_type = $dbtype;
	}

function db_print_error($text) {
	if (function_exists('bab_isUserAdministrator') && bab_isUserAdministrator()) {
		include_once $GLOBALS['babInstallPath'].'utilit/devtools.php';
		bab_debug_print_backtrace(true);
	}
	
	
	$str = "<h2>" . $text . "</h2>\n";
	$str .= "<p><b>Database Error: ";
	switch($this->db_type )
		{
		case "mysql":
		default:
			$str .= mysql_error();
			break;
		}
	$str .= "</b></p>\n";

	
	if ($this->db_die_on_fail)
		{
		$error_reporting = (int) ini_get('error_reporting');
		if (E_USER_ERROR === ($error_reporting & E_USER_ERROR)) {
			echo $str;
		}

		echo "<p>This script cannot continue, terminating.";
		die();
		}
	else
		return $str;
	}


function db_connect($host, $login, $password, $dbname)
    {
	switch( $this->db_type )
		{
		case "mysql":
		default:
			$dblink = mysql_connect($host, $login, $password);
			if( $dblink )
				{
				$res = mysql_select_db($dbname, $dblink);
				if( $res == false )
					{
					if (is_file('install.php')) {
					die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
					} else {
					$this->db_print_error("Cannot select database : " . $dbname);
					}
					return $res;
					}
				}
			else
				{
				if (is_file('install.php')) {
					die('Welcome to Ovidentia.<br />To install this distribution, launch the <a href="install.php">install.php</a>.');
					} else {
					$this->db_print_error( "Cannot connect to database : " . $dbName);
					}
				}
			break;
		}
    return $dblink;
    }

function db_close($id)
    {
	$res = false;

	switch($this->db_type )
		{
		case "mysql":
		default:
			$res = mysql_close($id);
			break;
		}
	return $res;
    }

function db_create_db($dbname, $id)
    {
	$res = false;

	switch($this->db_type )
		{
		case "mysql":
		default:
			$res = mysql_create_db($dbname, $id);
			break;
		}
	return $res;
    }

function db_drop_db($dbname, $id)
    {
	$res = false;

	switch($this->db_type )
		{
		case "mysql":
		default:
			$res = mysql_drop_db($dbname, $id);
			break;
		}
	return $res;
    }

function db_query($id, $query)
    {
	$res = false;

	switch($this->db_type )
		{
		case "mysql":
		default:
		
			$res = mysql_query($query, $id);
			if (!$res)
				{
				$this->db_print_error("Can't execute query : <br><pre>" . htmlspecialchars($query) . "</pre>");
				}
			break;
		}
	return $res;
    }

function db_num_rows($result)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			if ($result)
				return mysql_num_rows($result);
			else
				return 0;
			break;
		}
	}

function db_fetch_array($result)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_fetch_array($result);
			break;
		}
	}

function db_fetch_assoc($result)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_fetch_assoc($result);
			break;
		}
	}

function db_fetch_row($result)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_fetch_row($result);
			break;
		}
	}

function db_result($result, $row, $field)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_result($result, $row, $field);
			break;
		}
	}

function db_affected_rows($id)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_affected_rows($id);
			break;
		}
	}

function db_insert_id($id)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_insert_id($id);
			break;
		}
	}

function db_data_seek($res, $row)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_data_seek($res, $row);
			break;
		}
	}

function db_escape_string($str)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_escape_string($str);
			break;
		}
	}

function db_free_result($result)
    {
	switch($this->db_type )
		{
		case "mysql":
		default:
			return mysql_free_result($result);
			break;
		}
	}


} /* end of class bab_database */

class babDatabase extends bab_database
{
	function babDatabase()
		{
		$this->bab_database(true);
		}

	function db_connect()
		{
		static $idlink = false;
		if( $idlink == false)
			{
			$idlink = parent::db_connect($GLOBALS['babDBHost'], $GLOBALS['babDBLogin'], $GLOBALS['babDBPasswd'], $GLOBALS['babDBName']);
			}
		return $idlink;
		}

	function db_close()
		{
		return parent::db_close($this->db_connect());
		}

	function db_create_db($dbname)
		{
		return parent::db_create_db($dbname, $this->db_connect());
		}

	function db_drop_db($dbname)
		{
		return parent::db_drop_db($dbname, $this->db_connect());
		}

	function db_query($query)
		{
		return parent::db_query($this->db_connect(), $query);
		}

	function db_num_rows($result)
		{
		return parent::db_num_rows($result);
		}

	function db_fetch_array($result)
		{
		return parent::db_fetch_array($result);
		}

	function db_fetch_assoc($result)
		{
		return parent::db_fetch_assoc($result);
		}

	function db_fetch_row($result)
		{
		return parent::db_fetch_row($result);
		}

	function db_result($result, $row, $field)
		{
		return parent::db_result($result, $row, $field);
		}

	function db_affected_rows()
		{
		return parent::db_affected_rows($this->db_connect());
		}

	function db_insert_id()
		{
		return parent::db_insert_id($this->db_connect());
		}

	function db_data_seek($res, $row)
		{
		return parent::db_data_seek($res, $row);
		}

	function db_escape_string($str)
		{
		return parent::db_escape_string($str);
		//return parent::db_real_escape_string($str, $this->db_connect());
		}

	/**
	 * Special chars for LIKE query
	 * @param string $str
	 */
	function db_escape_like($str)
		{
		$str = str_replace('%','\%',$str);
		$str = str_replace('?','\?',$str);
		return parent::db_escape_string($str);
		}

	
	function quote($param) 
		{
			if (is_array($param)) {
				foreach($param as $key => $value) {
					$param[$key] = $this->db_escape_string($value);
				}
				
				return "'".implode("','",$param)."'";
			} else {
				return "'".parent::db_escape_string($param)."'";
			}
		}

	function db_free_result($result)
		{
		return parent::db_free_result($result);
		}
}


?>
