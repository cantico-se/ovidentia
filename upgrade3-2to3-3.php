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
/* upgrade from 3.2 to 3.3 */
/************************************************************************
- support of addons
- Addded prefered style sheet
- Added $ARTICLE(topic, article)
- Added $FAQ(faq, question)
- Users can now email forum contributors
- Add: Administrator can configure site to accept or not registration.
  And if registration is accepted, confirmation email can be sent or not
- Sorted files in Files Manager
- babMaxRows is no longer used from config.php. Value in forum defintion is used
- Now, when you reply you can view post or comment or read article on witch you reply
- Added possibilty to non moderate comments on topics
- Article archivage
************************************************************************/
function upgrade()
{
$ret = "";
$db = $GLOBALS['babDB'];

$res = $db->db_query("show tables");
if( !$res)
	{
	$ret = "Alteration of <b>tables</b>failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("ALTER TABLE ".$arr[0]." RENAME bab_".$arr[0]);
	}


$req = "ALTER TABLE ".BAB_USERS_TBL." ADD style TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD registration ENUM('Y','N') NOT NULL, ADD email_confirm ENUM('Y','N') NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_TBL." ADD lastlog DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_TBL." ADD datelog DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("select id from ".BAB_USERS_TBL);
while( $arr = $db->db_fetch_array($res))
	{
	$res2 = $db->db_query("select lastlog, datelog from ".BAB_USERS_LOG_TBL." where id_user='".$arr['id']."'");
	if( $res2 && $db->db_num_rows($res2) > 0)
		{
		$rr = $db->db_fetch_array($res2);
		$db->db_query("update ".BAB_USERS_TBL." set lastlog='".$rr['lastlog']."', datelog='".$rr['datelog']."' where id='".$arr['id']."'");
		}
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." DROP lastlog";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." DROP datelog";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." DROP islogged";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." ADD sessid tinytext NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." CHANGE dateact dateact TIMESTAMP(14) DEFAULT '0000-00-00 00:00:00' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." ADD remote_addr varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." ADD forwarded_for varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD mod_com ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD archive ENUM('N','Y') NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_ADDONS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "title varchar(255) NOT NULL default '',";
$req .= "enabled enum('Y','N') NOT NULL default 'Y',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ADDONS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_ADDONS_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ADDONS_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}
return $ret;
}
?>