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
/* upgrade from 2.3 to 3.0 */
include "config.php";

function upgrade()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE caloptions (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT, ";
$req .= "id_user INT (11) UNSIGNED not null, ";
$req .= "startday TINYINT DEFAULT '0' not null,"; 
$req .= "allday ENUM ('Y','N') not null, ";
$req .= "viewcat ENUM ('Y','N') not null, ";
$req .= "usebgcolor ENUM ('Y','N') not null,"; 
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>caloptions</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE mail_domains (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "name VARCHAR (254) not null,";
$req .= "description VARCHAR (224) not null,";
$req .= "outserver VARCHAR (224) not null,";
$req .= "inserver VARCHAR (224) not null,";
$req .= "outport VARCHAR (5) not null,";
$req .= "inport VARCHAR (5) not null,";
$req .= "access VARCHAR (10) not null,";
$req .= "bgroup enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "owner INT (11) UNSIGNED not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>mail_domains</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE mail_accounts (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "name VARCHAR (255) not null,";
$req .= "email VARCHAR (255) not null,";
$req .= "account VARCHAR (255) not null,";
$req .= "password BLOB not null,";
$req .= "domain INT (11) UNSIGNED not null,";
$req .= "owner INT (11) UNSIGNED not null,";
$req .= "maxrows TINYINT (2) not null,";
$req .= "prefered enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "format VARCHAR (5) DEFAULT 'plain' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>mail_accounts</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE mail_signatures (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "name varchar(255) NOT NULL,";
$req .= "owner INT (11) UNSIGNED not null,";
$req .= "html enum('Y','N') DEFAULT 'N' NOT NULL,";
$req .= "text TEXT not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>mail_signatures</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE contacts (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "category int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "owner int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "firstname char(60) NOT NULL,";
$req .= "lastname char(60) NOT NULL,";
$req .= "hashname char(32) NOT NULL,";
$req .= "email text NOT NULL,";
$req .= "compagny char(255) NOT NULL,";
$req .= "hometel char(255) NOT NULL,";
$req .= "mobiletel char(255) NOT NULL,";
$req .= "businesstel char(255) NOT NULL,";
$req .= "businessfax char(255) NOT NULL,";
$req .= "jobtitle char(255) NOT NULL,";
$req .= "businessaddress text NOT NULL,";
$req .= "homeaddress text NOT NULL,";
$req .= "PRIMARY KEY (id),";
$req .= "KEY hashname (hashname),";
$req .= "KEY id (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>contacts</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE sites (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name char(30) NOT NULL,";
$req .= "description char(100) NOT NULL,";
$req .= "lang char(10) NOT NULL,";
$req .= "adminemail char(255) NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>sites</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE homepages (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "id_article INT (11) UNSIGNED not null,";
$req .= "id_site INT (11) UNSIGNED not null,";
$req .= "id_group INT (11) UNSIGNED not null,";
$req .= "status ENUM ('N', 'Y') not null,";
$req .= "ordering INT (11) UNSIGNED not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>homepages</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE groups ADD mail ENUM ('N','Y') not null AFTER vacation";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>groups</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE users ADD lang VARCHAR (10) not null";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>users</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE users CHANGE name nickname CHAR (255)";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>users</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE users ADD firstname CHAR (60) not null AFTER nickname , ADD lastname CHAR (60) not null AFTER firstname";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>users</b> table failed !<br>";
	return $ret;
	}


$req = "select * from users";
$res1 = $db->db_query($req);
while( $arr = $db->db_fetch_array($res1))
	{

	$tab = explode(" ", $arr[fullname]);
	if( count($tab) > 2)
		{
		$lastname = array_pop($tab);
		$firstname = implode( " ", $tab);
		}
	else
		{
		$firstname = $tab[0];
		$lastname = $tab[1];
		}

	$req = "update users set nickname='".$arr[email]."', firstname='".$firstname."', lastname='".$lastname."' where id='".$arr[id]."'";
	$res = $db->db_query($req);
	}

$req = "ALTER TABLE users CHANGE fullname hashname CHAR (32)";
$res1 = $db->db_query($req);
if( !$res1)
	{
	$ret = "Alteration of <b>users</b> table failed !<br>";
	return $ret;
	}

$replace = array( " " => "", "-" => "");

$req = "select * from users";
$res1 = $db->db_query($req);
while( $arr = $db->db_fetch_array($res1))
	{
	$hash = md5(mb_strtolower(strtr($arr[firstname].$arr[lastname], $replace)));
	$req = "update users set hashname='".$hash."' where id='".$arr[id]."'";
	$res = $db->db_query($req);
	}

$req = "ALTER TABLE posts ADD id_parent INT (11) UNSIGNED not null AFTER id_thread";
$res1 = $db->db_query($req);
if( !$res1)
	{
	$ret = "Alteration of <b>posts</b> table failed !<br>";
	return $ret;
	}

$req = "select * from threads";
$res1 = $db->db_query($req);
while( $arr = $db->db_fetch_array($res1))
	{
	$req = "select * from posts where id='".$arr[post]."'";
	$res2 = $db->db_query($req);
	$arr2 = $db->db_fetch_array($res2);

	$req = "select * from posts where id_thread='".$arr[id]."' and id!='".$arr[post]."'";
	$res3 = $db->db_query($req);
	while( $arr3 = $db->db_fetch_array($res3))
		{
		if( empty($arr3[subject]))			
			$req = "update posts set id_parent='".$arr[post]."', subject='"."RE:".addslashes($arr2[subject])."' where id_thread='".$arr[id]."' and id!='".$arr[post]."'";
		else
			$req = "update posts set id_parent='".$arr[post]."' where id_thread='".$arr[id]."' and id!='".$arr[post]."'";
		$res = $db->db_query($req);
		}
	}
return $ret;
}

?>