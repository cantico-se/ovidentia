<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/*
class db_mysql
{
var $babDBHost;
var $babDBLogin;
var $babDBPasswd;
var $babDBName;
var $idlink;
var $db_die_on_fail;

function db_mysql()
	{
	global $babDBHost;
	global $babDBLogin;
	global $babDBPasswd;
	global $babDBName;

	$this->babDBHost = $babDBHost;
	$this->babDBLogin = $babDBLogin;
	$this->babDBPasswd= $babDBPasswd;
	$this->babDBName = $babDBName;
	$this->db_die_on_fail = false;
	$this->idlink = false;
	}

function db_print_error($text)
    {
    print("<h2>" . $text . "</h2>\n");
    print("<p><b>Database Error: " . mysql_error() . "</b></p>\n");
	if ($this->db_die_on_fail)
        {
		echo "<p>This script cannot continue, terminating.";
		die();
	    }
    }

function db_connect()
    {
	static $idlink = false;
	if( $idlink != false )
		return $idlink;
	$this->idlink = mysql_connect($this->babDBHost, $this->babDBLogin, $this->babDBPasswd);
    if( $this->idlink == false )
        {
        $txt = "Cannot connect to database : " . $this->babDBName;
		//$txt .= " with: (" . $this->babDBHost . "/" .  $this->babDBLogin . "/" .  this->babDBPasswd . ")";
        $this->db_print_error($txt, mysql_error());
        return false;
        }

    $result = mysql_select_db($this->babDBName, $this->idlink);
    if( $result == false )
        {
        $txt = "Cannot select database : " . $this->babDBName;
        $this->db_print_error($txt, mysql_error());
        return $result;
        }
    return $idlink = $this->idlink;
    }

function db_query($query)
    {
	$res = false;
	if( $this->idlink == false)
		$this->idlink= $this->db_connect();

	if( $this->idlink )
		{
		$res = mysql_query($query, $this->idlink);
		if (! $res )
			{
			$txt = "Can't execute query : <br><pre>" . htmlspecialchars($query) . "</pre>";
			$this->db_print_error($txt, mysql_error());
			}
		}
	return $res;
    }

function db_num_rows($result)
    {
	if ($result)
		{
		return mysql_num_rows($result);
		}
	else
		{
		return 0;
		}
	}
function db_fetch_array($result)
    {
	return mysql_fetch_array($result);
	}

function db_fetch_row($result)
    {
	return mysql_fetch_row($result);
	}

function db_result($result, $row, $filed)
    {
	return mysql_result($result, $row, $field);
	}

function db_affected_rows()
    {
	return mysql_affected_rows($this->idlink);
	}

function db_insert_id()
    {
	return mysql_insert_id($this->idlink);
	}

function db_data_seek($res, $row)
    {
	return mysql_data_seek($res, $row);
	}

} 

*/

class bab_database
{
var $db_type;
var $db_die_on_fail;

function bab_database($die = false, $dbtype = "mysql")
	{
	$this->db_die_on_fail = $die;
	$this->db_type = $dbtype;
	}

function db_print_error($text)
    {
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
		echo $str;
		echo "<p>This script cannot continue, terminating.";
		die();
	    }
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
					$this->db_print_error("Cannot select database : " . $dbname);
					return $res;
					}
				}
			else
				{
				$this->db_print_error( "Cannot connect to database : " . $dbName);
				}
			break;
		}
    return $dblink;
    }

function db_query($id, $query)
    {
	$res = false;

	switch($this->db_type )
		{
		case "mysql":
		default:
			$res = mysql_query($query, $id);
			if (!$res )
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

} /* end of class bab_database */

class babDatabase extends bab_database
{
	function babDatabase()
		{
		$this->bab_database();
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

}


?>
