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

class babLDAP
{
	var	$ldap_die_on_fail;
	var $host;
	var $port;
	var $basedn;
	var $binddn;
	var $bindpw;


	function babLDAP($host, $port = "", $basedn = "" , $binddn = "", $bindpw = "", $die = false)
	{
		$this->ldap_die_on_fail = $die;
		$this->host = $host;
		$this->port = $port;
		$this->basedn = $basedn;
		$this->binddn = $binddn;
		$this->bindpw = $bindpw;
	}

	function print_error($text)
    {
		$str = "<h2>" . $text . "</h2>\n";
		$str .= "<p><b>Ldap Error: ";
		if( $this->idlink != false )
			{
			$str .= ldap_err2str(ldap_errno($this->idlink));
			}
		$str .= "</b></p>\n";
		if ($this->ldap_die_on_fail)
			{
			echo $str;
			echo "<p>This script cannot continue, terminating.";
			die();
			}
		return $str;
    }

	function connect()
	{
		if( !isset($this->port) || empty($this->port))
			$this->idlink = ldap_connect($this->host);
		else
			$this->idlink = ldap_connect($this->host, $this->port);

		if( $this->idlink === false )
			{
			$this->print_error("Cannot connect to ldap server : " . $this->host);
			return false;
			}
		if(ldap_bind($this->idlink, $this->binddn, $this->bindpw))
			return $this->idlink;
		else
			{
			$this->print_error("Cannot bind to : " . $this->binddn);
			return false;
			}
	}

	function close()
	{
		return ldap_close($this->idlink);
	}

	function search($filter, $attributes = array(), $attronly = 0, $sizelimit = 0)
	{
		$res = ldap_search($this->idlink, $this->basedn, $filter, $attributes, $attronly, $sizelimit);
		if( $res === false )
			{
			$this->print_error("Search failed : " . $this->basedn ." - ". $filter);
			return false;
			}
		else
			{
			$arr = ldap_get_entries($this->idlink, $res);
			if( $arr === false )
				{
				return false;
				}
			return $arr;
			}
	}

	function read($basedn, $filter, $attributes = array(), $attronly = 0, $sizelimit = 0)
	{
		$res = ldap_read($this->idlink, $basedn, $filter, $attributes, $attronly, $sizelimit);
		if( $res === false )
			{
			$this->print_error("Read failed : " . $basedn ." - ". $filter);
			return false;
			}
		else
			{
			return $res;
			}
	}

	function first_entry($ri)
	{
		$res = ldap_first_entry($this->idlink, $ri);
		if( $res === false )
			{
			$this->print_error("First entry failed");
			return false;
			}
		else
			{
			return $res;
			}
	}

	function get_values_len($re, $attr)
	{
		$res = ldap_get_values_len($this->idlink, $re, $attr);
		if( $res === false )
			{
			$this->print_error("get values len failed");
			return false;
			}
		else
			{
			return $res;
			}
	}

}

?>