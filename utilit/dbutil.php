<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/


class db_mysql
{
var $babDBHost;
var $babDBLogin;
var $baDBPasswd;
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
    return $this->idlink;
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

} /* end of class db_mysql */

?>
