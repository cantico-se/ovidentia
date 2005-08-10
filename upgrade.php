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
function upgrade310to320()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE files ADD hits INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>files</b> table failed !<br>";
	return $ret;
	}
	
$req = "ALTER TABLE sections ADD enabled ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>sections</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE private_sections ADD enabled ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>private_sections</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE topics ADD id_cat INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>topics</b> table failed !<br>";
	return $ret;
	}

$req = "select id, private from sections_order";
$res = $db->db_query($req);

$req = "ALTER TABLE sections_order CHANGE private type SMALLINT(2) UNSIGNED NOT NULL ";
$res1 = $db->db_query($req);
if( !$res1)
	{
	$ret = "Alteration of <b>sections_order</b> table failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['private'] == "Y")
		$db->db_query("update sections_order set type='1' where id='".$arr['id']."'");
	else
		$db->db_query("update sections_order set type='2' where id='".$arr['id']."'");
	}

$req = "select id, private from sections_states";
$res = $db->db_query($req);

$req = "ALTER TABLE sections_states CHANGE private type SMALLINT(2) UNSIGNED NOT NULL ";
$res1 = $db->db_query($req);
if( !$res1)
	{
	$ret = "Alteration of <b>sections_states</b> table failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['private'] == "Y")
		$db->db_query("update sections_states set type='1' where id='".$arr['id']."'");
	else
		$db->db_query("update sections_states set type='2' where id='".$arr['id']."'");
	}

$req = "CREATE TABLE topics_categories (";
$req .= "id int(11) unsigned NOT NULL auto_increment, ";
$req .= "title varchar(60), ";
$req .= "description varchar(200), ";
$req .= "enabled enum('Y','N') DEFAULT 'Y' NOT NULL, ";
$req .= "PRIMARY KEY (id) ";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>topics_categories</b> table failed !<br>";
	return $ret;
	}

$req = "INSERT INTO topics_categories VALUES ('1', 'Default category', 'Default category', 'Y')";
$res = $db->db_query($req);
$id = $db->db_insert_id();

$req = "update topics set id_cat='1'";
$res = $db->db_query($req);

$req = "select max(ordering) from sections_order where position='0'";
$res = $db->db_query($req);
$arr = $db->db_fetch_array($res);
$req = "insert into sections_order (id_section, position, type, ordering) VALUES ('" .$id. "', '0', '3', '" . ($arr[0]+1). "')";
$db->db_query($req);

return $ret;
}


function upgrade320to330()
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

function upgrade330to331()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_INI_TBL." (";
$req .= "foption char(255) NOT NULL default '',";
$req .= "fvalue char(255) NOT NULL default '',";
$req .= "UNIQUE KEY foption (foption)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_INI_TBL."</b> table failed !<br>";
	return $false;
	}

$ar = explode(".", $GLOBALS['babVersion']);
$db->db_query("INSERT INTO ".BAB_INI_TBL." VALUES ('ver_major', '".$ar[0]."')");
$db->db_query("INSERT INTO ".BAB_INI_TBL." VALUES ('ver_minor', '".$ar[1]."')");
$db->db_query("INSERT INTO ".BAB_INI_TBL." VALUES ('ver_build', '0')");
}

function upgrade331to332()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD mailfunc varchar(20) NOT NULL default 'mail'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD smtpserver varchar(255) NOT NULL default ''";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD smtpport varchar(20) NOT NULL default '25'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD style VARCHAR(255) NOT NULL AFTER skin";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
$res=$db->db_query($req);
if( $res && $db->db_num_rows($res) == 1 )
	{
	$arr = $db->db_fetch_array($res);
	if( $arr['skin'] == "" )
		{
		if( empty($GLOBALS['babSkin']) )
			$req = "update ".BAB_SITES_TBL." set skin='".$GLOBALS['babSkin']."' where id='".$arr['id']."'";
		else
			$req = "update ".BAB_SITES_TBL." set skin='ovidentia' where id='".$arr['id']."'";
		$res = $db->db_query($req);
		}

	if( empty($GLOBALS['babStyle']) )
		$req = "update ".BAB_SITES_TBL." set style='ovidentia.css' where id='".$arr['id']."'";
	else
		$req = "update ".BAB_SITES_TBL." set style='".$GLOBALS['babStyle']."' where id='".$arr['id']."'";
	$res = $db->db_query($req);
	}


$req = "select id, bwrite from ".BAB_CALACCESS_USERS_TBL;
$res = $db->db_query($req);

$req = "ALTER TABLE ".BAB_CALACCESS_USERS_TBL." CHANGE bwrite bwrite SMALLINT(2) UNSIGNED NOT NULL ";
$res1 = $db->db_query($req);
if( !$res1)
	{
	$ret = "Alteration of <b>".BAB_CALACCESS_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['bwrite'] == "Y")
		$db->db_query("update ".BAB_CALACCESS_USERS_TBL." set bwrite='1' where id='".$arr['id']."'");
	else
		$db->db_query("update ".BAB_CALACCESS_USERS_TBL." set bwrite='0' where id='".$arr['id']."'");
	}

$req = "ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD id_creator INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_EVENTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD moderate ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FORUMS_TBL." ADD notification ENUM('N','Y') DEFAULT 'N' NOT NULL AFTER moderation";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade332to333()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_IMAGES_TEMP_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "id_owner int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_IMAGES_TEMP_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD imgsize INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade333to340()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD filenotify ENUM('N','Y') NOT NULL AFTER moderate";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD ordering smallint(6) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL;
$res = $db->db_query($req);
while($row = $db->db_fetch_array($res))
	{
	$ord = 0;
	$res2 = $db->db_query("select * from ".BAB_TOPICS_TBL." where id_cat='".$row['id']."'");
	while($row2 = $db->db_fetch_array($res2))
		{
		$db->db_query("update ".BAB_TOPICS_TBL." set ordering='".$ord."' where id='".$row2['id']."'");
		$ord++;
		}
	}

$req = "ALTER TABLE ".BAB_FORUMS_TBL." ADD ordering smallint(6) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FORUMS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "select * from ".BAB_FORUMS_TBL;
$res = $db->db_query($req);
$ord = 0;
while($row = $db->db_fetch_array($res))
	{
	$db->db_query("update ".BAB_FORUMS_TBL." set ordering='".$ord."' where id='".$row['id']."'");
	$ord++;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD notes ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD contacts ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD idgroup INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$ret = upgrade333to340bis();

return $ret;
}
//xxxxxxxxxxxxxxx

function upgrade333to340bis()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD idsaart INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD idsacom INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD idfai INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "update ".BAB_ARTICLES_TBL." set confirmed='Y' where archive='Y'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "ALTER TABLE ".BAB_COMMENTS_TBL." ADD idfai INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_COMMENTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FLOW_APPROVERS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description tinytext NOT NULL,";
$req .= "formula tinytext NOT NULL,";
$req .= "forder enum('N','Y') NOT NULL default 'N',";
$req .= "refcount int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FLOW_APPROVERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FA_INSTANCES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "idsch int(11) unsigned NOT NULL default '0',";
$req .= "extra tinytext NOT NULL,";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FA_INSTANCES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FAR_INSTANCES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "idschi int(11) unsigned NOT NULL default '0',";
$req .= "iduser int(11) NOT NULL default '0',";
$req .= "result char(1) NOT NULL default '',";
$req .= "notified enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FAR_INSTANCES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "select * from ".BAB_TOPICS_TBL;
$res = $db->db_query($req);
while($row = $db->db_fetch_array($res))
	{
	$res2 = $db->db_query("select * from ".BAB_USERS_TBL." where id='".$row['id_approver']."'");
	if( $res2 && $db->db_num_rows($res2) > 0 )
		{
		$res2 = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where formula='".$row['id_approver']."'");
		if( !$res2 || $db->db_num_rows($res2) == 0 )
			{
			$req = "insert into ".BAB_FLOW_APPROVERS_TBL." (name, description, formula, forder) VALUES ('" .bab_getUserName($row['id_approver']). "', '', '" .  $row['id_approver']. "', 'N')";
			$db->db_query($req);
			$idfa = $db->db_insert_id();
			$refcount = 0;
			}
		else
			{
			$arr = $db->db_fetch_array($res2);
			$idfa = $arr['id'];
			$refcount = $arr['refcount'];
			}

		$db->db_query("update ".BAB_TOPICS_TBL." set idsaart='".$idfa."' where id='".$row['id']."'");

		$res2 = $db->db_query("select * from ".BAB_ARTICLES_TBL." where confirmed='N' and id_topic='".$row['id']."'");
		while($arr = $db->db_fetch_array($res2))
			{
			$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra) VALUES ('".$idfa."', 'art-".$arr['id']."')");
			$idfaia = $db->db_insert_id();
			$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".++$refcount."' where id='".$idfa."'");
			$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser, notified) VALUES ('".$idfaia."', '".$row['id_approver']."', 'Y')");
			$db->db_query("update ".BAB_ARTICLES_TBL." set idfai='".$idfaia."' where id='".$arr['id']."'");
			}

		if( $row['mod_com'] == "Y")
			{
			$db->db_query("update ".BAB_TOPICS_TBL." set idsacom='".$idfa."' where id='".$row['id']."'");

			$res2 = $db->db_query("select * from ".BAB_COMMENTS_TBL." where confirmed='N' and id_topic='".$row['id']."'");
			while($arr = $db->db_fetch_array($res2))
				{
				$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra) VALUES ('".$idfa."', 'com-".$arr['id']."')");
				$idfaic = $db->db_insert_id();
				$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".++$refcount."' where id='".$idfa."'");
				$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser, notified) VALUES ('".$idfaic."', '".$row['id_approver']."', 'Y')");
				$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='".$idfaic."' where id='".$arr['id']."'");
				}
			}
		}
	else
		{
		$db->db_query("update ".BAB_TOPICS_TBL." set id_approver='0' where id='".$row['id']."'");
		}	
	}

$req = "ALTER TABLE ".BAB_CALOPTIONS_TBL." CHANGE viewcat ampm ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CALOPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_CALOPTIONS_TBL." ADD elapstime TINYINT(2) UNSIGNED DEFAULT '30' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CALOPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade340betas(&$beta)
{
$ret = "";
$db = $GLOBALS['babDB'];

/* 340beta -> 340beta2 */
$res = $db->db_query("SHOW COLUMNS from ".BAB_GROUPS_TBL." like 'filenotify'");
if( !$res || $db->db_num_rows($res) == 0 )
	{
	$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD filenotify ENUM('N','Y') NOT NULL AFTER moderate";
	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_COMMENTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	$beta = "beta2";
	}
return $ret;
}

function upgrade340to341()
{
$ret = "";
$db = $GLOBALS['babDB'];
$beta = "";

list($prod) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_prod'"));
list($major) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_major'"));
list($minor) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_minor'"));
list($build) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_build'"));

if( $prod == 'G' && $major == '3' && $minor == '4' && $build == '0')
	{
	$ret = upgrade333to340bis();
	if( !empty($ret))
		return $ret;
	}
else
	{

	$ret = upgrade340betas($beta);
	if( !empty($ret))
		return $ret;

	$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD notify ENUM('N','Y') NOT NULL";
	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
		return $ret;
		}

	}

$req = "CREATE TABLE ".BAB_FM_FOLDERS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "folder char(255) NOT NULL default '',";
$req .= "manager int(11) unsigned NOT NULL default '0',";
$req .= "idsa int(11) unsigned NOT NULL default '0',";
$req .= "filenotify enum('N','Y') NOT NULL default 'N',";
$req .= "active enum('Y','N') NOT NULL default 'Y',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FILES_TBL." ADD idfai INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FMUPLOAD_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FMUPLOAD_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FMDOWNLOAD_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FMDOWNLOAD_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FMUPDATE_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FMUPDATE_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "select * from ".BAB_GROUPS_TBL;
$res = $db->db_query($req);
while($row = $db->db_fetch_array($res))
	{
	if( $row['id'] == 1 || $row['id'] == 2 )
		{
		$rs = $db->db_query("select ".BAB_USERS_TBL.".id from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group='3' and ".BAB_USERS_TBL.".id = ".BAB_USERS_GROUPS_TBL.".id_object and ".BAB_USERS_TBL.".disabled ='0' order by ".BAB_USERS_TBL.".id asc limit 0,1");
		$rrr = $db->db_fetch_array($rs);
		$row['manager'] = $rrr['id'];
		if( $row['id'] == 1)
			$row['name'] = "Registered users";
		else if( $row['id'] == 2)
			$row['name'] = "Unregistered users";
		}

	$res2 = $db->db_query("select * from ".BAB_USERS_TBL." where id='".$row['manager']."'");
	if( $row['manager'] != 0 && $res2 && $db->db_num_rows($res2) > 0 )
		{
		$res2 = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where formula='".$row['manager']."'");
		if( !$res2 || $db->db_num_rows($res2) == 0 )
			{
			$req = "insert into ".BAB_FLOW_APPROVERS_TBL." (name, description, formula, forder) VALUES ('" .bab_getUserName($row['manager']). "', '', '" .  $row['manager']. "', 'N')";
			$db->db_query($req);
			$idfa = $db->db_insert_id();
			$refcount = 0;
			}
		else
			{
			$arr = $db->db_fetch_array($res2);
			$idfa = $arr['id'];
			$refcount = $arr['refcount'];
			}

		$db->db_query("insert into ".BAB_FM_FOLDERS_TBL." (id, folder, manager, idsa, filenotify, active) values ('".$row['id']."', '".addslashes($row['name'])."', '".$row['manager']."', '".$idfa."', '".$row['filenotify']."', '".$row['gstorage']."')");
		$fid = $db->db_insert_id();

		$res2 = $db->db_query("select id from ".BAB_FILES_TBL." where confirmed='N' and bgroup='Y' and id_owner='".$row['id']."'");
		while($arr = $db->db_fetch_array($res2))
			{
			$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra) VALUES ('".$idfa."', 'fil-".$arr['id']."')");
			$idfaia = $db->db_insert_id();
			$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".++$refcount."' where id='".$idfa."'");
			$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser, notified) VALUES ('".$idfaia."', '".$row['manager']."', 'Y')");
			$db->db_query("update ".BAB_FILES_TBL." set idfai='".$idfaia."' where id='".$arr['id']."'");
			}
		}
	else
		{
		$db->db_query("insert into ".BAB_FM_FOLDERS_TBL." (id, folder, manager, idsa, filenotify, active) values ('".$row['id']."', '".addslashes($row['name'])."', '0', '0', '".$row['filenotify']."', '".$row['gstorage']."')");
		$fid = $db->db_insert_id();

		$db->db_query("update ".BAB_FILES_TBL." set confirmed='Y' where bgroup='Y' and id_owner='".$row['id']."' and confirmed='N'");
		}

    if( $row['id'] == 2 )
        {
        $db->db_query("insert into ".BAB_FMDOWNLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '0')");
        $db->db_query("insert into ".BAB_FMUPLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '3')");
        }
    else
        {
        $db->db_query("insert into ".BAB_FMDOWNLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");
        $db->db_query("insert into ".BAB_FMUPLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");
        }
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." DROP gstorage, DROP filenotify, DROP moderate";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade341to342()
{

$ret = "";
$db = $GLOBALS['babDB'];

list($prod) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_prod'"));
list($major) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_major'"));
list($minor) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_minor'"));
list($build) = $db->db_fetch_row($db->db_query("select fvalue from ".BAB_INI_TBL." where foption='ver_build'"));

if( $prod == 'G' && $major == '3' && $minor == '4' && $build == '0')
	$dummy = 0;
else
	{
	$res = $db->db_query("SHOW COLUMNS from ".BAB_TOPICS_TBL." like 'notify'");
	if( !$res || $db->db_num_rows($res) == 0 )
		{
		$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD notify ENUM('N','Y') NOT NULL";
		$res = $db->db_query($req);
		if( !$res)
			{
			$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
			return $ret;
			}
		}

	$db->db_query("INSERT INTO ".BAB_INI_TBL." VALUES ('ver_prod', 'E')");

	$db->db_query("ALTER TABLE ".BAB_ADDONS_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_ADDONS_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_CALACCESS_USERS_TBL." ADD INDEX(id_cal)");
	$db->db_query("ALTER TABLE ".BAB_CALACCESS_USERS_TBL." ADD INDEX(id_user)");

	$db->db_query("ALTER TABLE ".BAB_FAQCAT_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_FAQCAT_GROUPS_TBL." ADD INDEX(id_group)");


	$db->db_query("ALTER TABLE ".BAB_FORUMSPOST_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_FORUMSPOST_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_FORUMSREPLY_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_FORUMSREPLY_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_FORUMSVIEW_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_FORUMSVIEW_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_SECTIONS_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_SECTIONS_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_TOPICSCOM_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_TOPICSCOM_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_TOPICSSUB_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_TOPICSSUB_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_TOPICSVIEW_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_TOPICSVIEW_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_USERS_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_USERS_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_VACATIONSMAN_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_VACATIONSMAN_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_VACATIONSVIEW_GROUPS_TBL." ADD INDEX(id_object)");
	$db->db_query("ALTER TABLE ".BAB_VACATIONSVIEW_GROUPS_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." ADD INDEX(id_topic)");  
	$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." ADD INDEX(date)");  

	$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD INDEX(id_cal)");
	$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD INDEX(start_date)");
	$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD INDEX(end_date)");

	$db->db_query("ALTER TABLE ".BAB_CALENDAR_TBL." ADD INDEX(owner)");
	$db->db_query("ALTER TABLE ".BAB_CALENDAR_TBL." ADD INDEX(type)");

	$db->db_query("ALTER TABLE ".BAB_CALOPTIONS_TBL." ADD INDEX(id_user)");


	$db->db_query("ALTER TABLE ".BAB_CATEGORIESCAL_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_COMMENTS_TBL." ADD INDEX(id_article)");
	$db->db_query("ALTER TABLE ".BAB_COMMENTS_TBL." ADD INDEX(id_topic)");
	$db->db_query("ALTER TABLE ".BAB_COMMENTS_TBL." ADD INDEX(date)");


	$db->db_query("ALTER TABLE ".BAB_CONTACTS_TBL." ADD INDEX(owner)");
	$db->db_query("ALTER TABLE ".BAB_CONTACTS_TBL." ADD INDEX(firstname)");
	$db->db_query("ALTER TABLE ".BAB_CONTACTS_TBL." ADD INDEX(lastname)");

	$db->db_query("ALTER TABLE ".BAB_FILES_TBL." ADD INDEX(id_owner)");
	$db->db_query("ALTER TABLE ".BAB_FILES_TBL." ADD INDEX(name)");

	$db->db_query("ALTER TABLE ".BAB_GROUPS_TBL." ADD INDEX(manager)");

	$db->db_query("ALTER TABLE ".BAB_HOMEPAGES_TBL." ADD INDEX(id_site)");
	$db->db_query("ALTER TABLE ".BAB_HOMEPAGES_TBL." ADD INDEX(id_group)");
	$db->db_query("ALTER TABLE ".BAB_HOMEPAGES_TBL." ADD INDEX(ordering)");

	$db->db_query("ALTER TABLE ".BAB_MAIL_ACCOUNTS_TBL." ADD INDEX(owner)");

	$db->db_query("ALTER TABLE ".BAB_MAIL_DOMAINS_TBL." ADD INDEX(owner)");

	$db->db_query("ALTER TABLE ".BAB_MAIL_SIGNATURES_TBL." ADD INDEX(owner)");


	$db->db_query("ALTER TABLE ".BAB_NOTES_TBL." ADD INDEX(id_user)");

	$db->db_query("ALTER TABLE ".BAB_POSTS_TBL." ADD INDEX(id_thread)");
	$db->db_query("ALTER TABLE ".BAB_POSTS_TBL." ADD INDEX(id_parent)");
	$db->db_query("ALTER TABLE ".BAB_POSTS_TBL." ADD INDEX(date)");

	$db->db_query("ALTER TABLE ".BAB_RESOURCESCAL_TBL." ADD INDEX(id_group)");

	$db->db_query("ALTER TABLE ".BAB_SECTIONS_ORDER_TBL." ADD INDEX(id_section)");
	$db->db_query("ALTER TABLE ".BAB_SECTIONS_ORDER_TBL." ADD INDEX(type)");
	$db->db_query("ALTER TABLE ".BAB_SECTIONS_ORDER_TBL." ADD INDEX(ordering)");


	$db->db_query("ALTER TABLE ".BAB_SECTIONS_STATES_TBL." ADD INDEX(id_section)");
	$db->db_query("ALTER TABLE ".BAB_SECTIONS_STATES_TBL." ADD INDEX(type)");
	$db->db_query("ALTER TABLE ".BAB_SECTIONS_STATES_TBL." ADD INDEX(id_user)");

	$db->db_query("ALTER TABLE ".BAB_SITES_TBL." ADD INDEX(name)");

	$db->db_query("ALTER TABLE ".BAB_THREADS_TBL." ADD INDEX(forum)");
	$db->db_query("ALTER TABLE ".BAB_THREADS_TBL." ADD INDEX(date)");

	$db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." ADD INDEX(id_approver)");
	$db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." ADD INDEX(id_cat)");
	$db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." ADD INDEX(ordering)");

	$db->db_query("ALTER TABLE ".BAB_USERS_TBL." ADD INDEX(nickname)");
	$db->db_query("ALTER TABLE ".BAB_USERS_TBL." ADD INDEX(firstname)");
	$db->db_query("ALTER TABLE ".BAB_USERS_TBL." ADD INDEX(lastname)");
	$db->db_query("ALTER TABLE ".BAB_USERS_TBL." ADD INDEX(hashname)");

	$db->db_query("ALTER TABLE ".BAB_USERS_LOG_TBL." ADD INDEX(id_user)");

	$db->db_query("ALTER TABLE ".BAB_VACATIONS_TBL." ADD INDEX(userid)");
	$db->db_query("ALTER TABLE ".BAB_VACATIONS_TBL." ADD INDEX(datebegin)");
	$db->db_query("ALTER TABLE ".BAB_VACATIONS_TBL." ADD INDEX(dateend)");
	$db->db_query("ALTER TABLE ".BAB_VACATIONS_TBL." ADD INDEX(type)");
	}

$db->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD INDEX(folder)");

$db->db_query("ALTER TABLE ".BAB_FA_INSTANCES_TBL." ADD INDEX(idsch)");

$db->db_query("ALTER TABLE ".BAB_FMDOWNLOAD_GROUPS_TBL." ADD INDEX(id_object)");
$db->db_query("ALTER TABLE ".BAB_FMDOWNLOAD_GROUPS_TBL." ADD INDEX(id_group)");

$db->db_query("ALTER TABLE ".BAB_FMUPDATE_GROUPS_TBL." ADD INDEX(id_object)");
$db->db_query("ALTER TABLE ".BAB_FMUPDATE_GROUPS_TBL." ADD INDEX(id_group)");

$db->db_query("ALTER TABLE ".BAB_FMUPLOAD_GROUPS_TBL." ADD INDEX(id_object)");
$db->db_query("ALTER TABLE ".BAB_FMUPLOAD_GROUPS_TBL." ADD INDEX(id_group)");

return $ret;
}

function upgrade342to343()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD `directory` ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$db->db_query("update ".BAB_GROUPS_TBL." set directory='Y' where id='1'");

$req = "CREATE TABLE ".BAB_LDAP_DIRECTORIES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "host tinytext NOT NULL,";
$req .= "basedn text NOT NULL,";
$req .= "userdn text NOT NULL,";
$req .= "password tinyblob NOT NULL,";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_LDAP_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DB_DIRECTORIES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_LDAPDIRVIEW_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_LDAPDIRVIEW_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIRVIEW_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIRVIEW_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIRADD_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIRADD_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIRUPDATE_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "id_group int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "PRIMARY KEY (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIRUPDATE_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_DBDIR_FIELDS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "x_name varchar(255) NOT NULL default '',";
$req .= "description tinytext NOT NULL,";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY name (name)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_FIELDS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIR_FIELDSEXTRA_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_directory int(11) unsigned NOT NULL default '0',";
$req .= "id_field int(11) unsigned NOT NULL default '0',";
$req .= "default_value text NOT NULL,";
$req .= "modifiable enum('N','Y') NOT NULL default 'N',";
$req .= "required enum('N','Y') NOT NULL default 'N',";
$req .= "multilignes enum('N','Y') NOT NULL default 'N',";
$req .= "ordering int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_directory (id_directory)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_FIELDSEXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_DBDIR_ENTRIES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "cn varchar(255) NOT NULL default '',";
$req .= "sn varchar(255) NOT NULL default '',";
$req .= "mn varchar(255) NOT NULL default '',";
$req .= "givenname varchar(255) NOT NULL default '',";
$req .= "jpegphoto varchar(255) NOT NULL default '',";
$req .= "email text NOT NULL,";
$req .= "btel varchar(255) NOT NULL default '',";
$req .= "mobile varchar(255) NOT NULL default '',";
$req .= "htel varchar(255) NOT NULL default '',";
$req .= "bfax varchar(255) NOT NULL default '',";
$req .= "title varchar(255) NOT NULL default '',";
$req .= "departmentnumber varchar(255) NOT NULL default '',";
$req .= "organisationname varchar(255) NOT NULL default '',";
$req .= "bstreetaddress text NOT NULL,";
$req .= "bcity varchar(255) NOT NULL default '',";
$req .= "bpostalcode varchar(10) NOT NULL default '',";
$req .= "bstate varchar(255) NOT NULL default '',";
$req .= "bcountry varchar(255) NOT NULL default '',";
$req .= "hstreetaddress text NOT NULL,";
$req .= "hcity varchar(255) NOT NULL default '',";
$req .= "hpostalcode varchar(10) NOT NULL default '',";
$req .= "hstate varchar(255) NOT NULL default '',";
$req .= "hcountry varchar(255) NOT NULL default '',";
$req .= "user1 text NOT NULL,";
$req .= "user2 text NOT NULL,";
$req .= "user3 text NOT NULL,";
$req .= "photo_data longblob NOT NULL,";
$req .= "photo_type varchar(20) NOT NULL default '',";
$req .= "id_directory int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY sn (sn),";
$req .= "KEY mn (mn),";
$req .= "KEY givenname (givenname),";
$req .= "KEY id_directory (id_directory)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_ENTRIES_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("SHOW tables like 'ad_directories'");
if( $res && $db->db_num_rows($res) > 0 )
	{
	$req = "insert into ".BAB_LDAP_DIRECTORIES_TBL." (id, name, description, host, basedn, userdn, password) select id, name, description, host, basedn, userdn, password from ad_directories where ldap='Y'";
	$res = $db->db_query($req);

	$req = "insert into ".BAB_DB_DIRECTORIES_TBL." (id, name, description) select id, name, description from ad_directories where ldap='N'";
	$res = $db->db_query($req);

	$req = "select id, ldap from ad_directories";
	$res = $db->db_query($req);

	while( $arr = $db->db_fetch_array($res))
		{
		$req = "select * from ad_dirview_groups where id_object='".$arr['id']."'";
		$res2 = $db->db_query($req);
		while( $arr2 = $db->db_fetch_array($res2))
			{
			if( $arr['ldap'] == 'Y')
				$db->db_query("insert into ".BAB_LDAPDIRVIEW_GROUPS_TBL." ( id_object, id_group) values ('".$arr2['id_object']."', '".$arr2['id_group']."')");
			else
				$db->db_query("insert into ".BAB_DBDIRVIEW_GROUPS_TBL." ( id_object, id_group) values ('".$arr2['id_object']."', '".$arr2['id_group']."')");
			}
		}


	$req = "insert into ".BAB_DBDIRADD_GROUPS_TBL." (id_object, id_group) select id_object, id_group from ad_diradd_groups";
	$res = $db->db_query($req);


	$req = "insert into ".BAB_DBDIRUPDATE_GROUPS_TBL." (id_object, id_group) select id_object, id_group from ad_dirupdate_groups";
	$res = $db->db_query($req);


	$req = "insert into ".BAB_DBDIR_FIELDSEXTRA_TBL." select * from ad_directories_fields";
	$res = $db->db_query($req);


	$req = "insert into ".BAB_DBDIR_ENTRIES_TBL." select * from ad_dbentries";
	$res = $db->db_query($req);
	}


$db->db_query("INSERT INTO bab_dbdir_fields VALUES (1, 'cn', 'cn', 'Common Name')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (2, 'sn', 'sn', 'Last Name')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (3, 'mn', '', 'Middle Name')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (4, 'givenname', 'givenname', 'First Name')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (5, 'jpegphoto', 'jpegphoto', 'Photo')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (6, 'email', 'mail', 'E-mail Address')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (7, 'btel', 'telephonenumber', 'Business Phone')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (8, 'mobile', 'mobile', 'Mobile Phone')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (9, 'htel', 'homephone', 'Home Phone')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (10, 'bfax', 'facsimiletelephonenumber', 'Business Fax')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (11, 'title', 'title', 'Title')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (12, 'departmentnumber', 'departmentnumber', 'Department')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (13, 'organisationname', 'o', 'Company')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (14, 'bstreetaddress', 'street', 'Business Street')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (15, 'bcity', 'l', 'Business City')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (16, 'bpostalcode', 'postalcode', 'Business Postal Code')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (17, 'bstate', 'st', 'Business State')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (18, 'bcountry', 'st', 'Business Country')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (19, 'hstreetaddress', 'homepostaladdress', 'Home Street')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (20, 'hcity', '', 'Home City')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (21, 'hpostalcode', '', 'Home Postal Code')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (22, 'hstate', '', 'Home State')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (23, 'hcountry', '', 'Home Country')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (24, 'user1', '', 'User 1')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (25, 'user2', '', 'User 2')");
$db->db_query("INSERT INTO bab_dbdir_fields VALUES (26, 'user3', '', 'User 3')");


/* id_directory = '0' means entry is owned by Ovidentia directory */
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 1, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 2, '', 'Y', 'Y', 'N', 1)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 3, '', 'Y', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 4, '', 'Y', 'Y', 'N', 2)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 5, '', 'Y', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 6, '', 'Y', 'Y', 'N', 3)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 7, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 8, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 9, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 10, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 11, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 12, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 13, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 14, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 15, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 16, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 17, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 18, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 19, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 20, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 21, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 22, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 23, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 24, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 25, '', 'N', 'N', 'N', 0)");
$db->db_query("INSERT INTO ".BAB_DBDIR_FIELDSEXTRA_TBL." (id_directory, id_field, default_value, modifiable, required, multilignes, ordering) VALUES (0, 26, '', 'N', 'N', 'N', 0)");

$req = "insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, id_group) values ('Ovidentia', 'Ovidentia directory', '1')";
$res = $db->db_query($req);
$iddir = $db->db_insert_id();

$req = "ALTER TABLE ".BAB_DBDIR_ENTRIES_TBL." ADD id_user INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DBDIR_ENTRIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "select * from ".BAB_USERS_TBL."";
$res = $db->db_query($req);
/* id_directory = '0' means entry is owned by Ovidentia directory */
while($arr = $db->db_fetch_array($res))
	{
	$req = "insert into ".BAB_DBDIR_ENTRIES_TBL." (sn, givenname, email, id_directory, id_user) values ('".addslashes($arr['lastname'])."', '".addslashes($arr['firstname'])."', '".addslashes($arr['email'])."', '0', '".$arr['id']."')";
	$db->db_query($req);
	}


$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `smtpuser` varchar(20) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `smtppassword` tinyblob NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_COLL_TYPES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_coll int(11) unsigned NOT NULL default '0',";
$req .= "id_type int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_coll (id_coll),";
$req .= "KEY id_type (id_type)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_COLL_TYPES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_COLLECTIONS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(25) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_COLLECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_ENTRIES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "date_begin date NOT NULL default '0000-00-00',";
$req .= "date_end date NOT NULL default '0000-00-00',";
$req .= "day_begin tinyint(3) unsigned NOT NULL default '0',";
$req .= "day_end tinyint(3) unsigned NOT NULL default '0',";
$req .= "idfai int(11) unsigned NOT NULL default '0',";
$req .= "comment tinytext NOT NULL,";
$req .= "date date NOT NULL default '0000-00-00',";
$req .= "status char(1) NOT NULL default '',";
$req .= "comment2 tinytext NOT NULL,";
$req .= "id_approver int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY date (date),";
$req .= "KEY id_user (id_user),";
$req .= "KEY idfai (idfai),";
$req .= "KEY date_begin (date_begin),";
$req .= "KEY date_end (date_end)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_ENTRIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_ENTRIES_ELEM_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_entry int(11) unsigned NOT NULL default '0',";
$req .= "id_type int(11) unsigned NOT NULL default '0',";
$req .= "quantity decimal(3,1) NOT NULL default '0.0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_entry (id_entry),";
$req .= "KEY id_type (id_type)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_ENTRIES_ELEM_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_MANAGERS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_user (id_user)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_MANAGERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_PERSONNEL_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "id_coll int(11) unsigned NOT NULL default '0',";
$req .= "id_sa int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_user (id_user),";
$req .= "KEY id_coll (id_coll),";
$req .= "KEY id_sa (id_sa)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_PERSONNEL_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_RIGHTS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_creditor int(11) unsigned NOT NULL default '0',";
$req .= "date_entry date NOT NULL default '0000-00-00',";
$req .= "date_begin date NOT NULL default '0000-00-00',";
$req .= "date_end date NOT NULL default '0000-00-00',";
$req .= "quantity tinyint(3) unsigned NOT NULL default '0',";
$req .= "id_type int(11) unsigned NOT NULL default '0',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "active enum('Y','N') NOT NULL default 'Y',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_type (id_type),";
$req .= "KEY date_entry (date_entry)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_TYPES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(20) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "quantity decimal(3,1) NOT NULL default '0.0',";
$req .= "maxdays decimal(3,1) NOT NULL default '0.0',";
$req .= "mindays decimal(3,1) NOT NULL default '0.0',";
$req .= "defaultdays decimal(3,1) NOT NULL default '0.0',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_TYPES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_VAC_USERS_RIGHTS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "id_right int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_user (id_user),";
$req .= "KEY id_right (id_right)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_USERS_RIGHTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." DROP vacation";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_CALOPTIONS_TBL." ADD defaultview tinyint(3) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CALOPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_CALOPTIONS_TBL." ADD defaultviewweek tinyint(3) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CALOPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD hash char(34) NOT NULL default ''";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_EVENTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "update ".BAB_INI_TBL." set fvalue='E' where foption='ver_prod'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Update of <b>".BAB_INI_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}


function upgrade343to400()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_VAC_USERS_RIGHTS_TBL." ADD quantity char(5) NOT NULL default ''";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_VAC_USERS_RIGHTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SECTIONS_TBL." ADD template varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD template varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD COLUMN langfilter INTEGER DEFAULT 0";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_USERS_TBL." ADD COLUMN langfilter INTEGER DEFAULT 0";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD lang VARCHAR( 10 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_COMMENTS_TBL." ADD lang VARCHAR( 10 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_COMMENTS_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD lang VARCHAR( 10 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_SECTIONS_TBL." ADD lang VARCHAR( 10 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_FAQCAT_TBL." ADD lang VARCHAR( 10 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FAQCAT_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade400to401()
{
$ret = "";
$db = $GLOBALS['babDB'];

/* missing in 4.0.0 sql script babinstall.sql */
$res = $db->db_query("SHOW COLUMNS from ".BAB_FILES_TBL." like 'idfai'");
if( !$res || $db->db_num_rows($res) == 0 )
	{
	$req = "ALTER TABLE ".BAB_FILES_TBL." ADD idfai INT(11) UNSIGNED NOT NULL";
	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}
return $ret;
}

function upgrade401to402()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_FM_FIELDS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_folder int(11) unsigned NOT NULL default '0',";
$req .= "name char(255) NOT NULL default '',";
$req .= "defaultval char(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_folder (id_folder)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FM_FIELDS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FM_FIELDSVAL_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_field int(11) unsigned NOT NULL default '0',";
$req .= "id_file int(11) unsigned NOT NULL default '0',";
$req .= "fvalue char(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_file (id_file),";
$req .= "KEY id_field (id_field)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FM_FIELDSVAL_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FM_FILESVER_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_file int(11) unsigned NOT NULL default '0',";
$req .= "date datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "author int(11) unsigned NOT NULL default '0',";
$req .= "ver_major smallint(5) unsigned NOT NULL default '1',";
$req .= "ver_minor smallint(5) unsigned NOT NULL default '0',";
$req .= "comment tinytext NOT NULL,";
$req .= "idfai int(11) unsigned NOT NULL default '0',";
$req .= "confirmed enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_file (id_file)";
$req .= ");"; 

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FM_FILESVER_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FM_FILESLOG_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_file int(11) unsigned NOT NULL default '0',";
$req .= "date datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "author int(11) unsigned NOT NULL default '0',";
$req .= "action smallint(5) unsigned NOT NULL default '0',";
$req .= "comment tinytext NOT NULL,";
$req .= "version varchar(10) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_file (id_file)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FM_FILESLOG_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD version ENUM('Y','N') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FILES_TBL." ADD edit INT(11) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FILES_TBL." ADD ver_major smallint(5) unsigned NOT NULL default '1'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FILES_TBL." ADD ver_minor smallint(5) unsigned NOT NULL default '0'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FILES_TBL." ADD ver_comment tinytext NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}


function upgrade402to403()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_DG_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name char(255) NOT NULL default '',";
$req .= "description char(255) NOT NULL default '',";
$req .= "groups enum('N','Y') NOT NULL default 'N',";
$req .= "sections enum('N','Y') NOT NULL default 'N',";
$req .= "articles enum('N','Y') NOT NULL default 'N',";
$req .= "faqs enum('N','Y') NOT NULL default 'N',";
$req .= "forums enum('N','Y') NOT NULL default 'N',";
$req .= "calendars enum('N','Y') NOT NULL default 'N',";
$req .= "mails enum('N','Y') NOT NULL default 'N',";
$req .= "directories enum('N','Y') NOT NULL default 'N',";
$req .= "approbations enum('N','Y') NOT NULL default 'N',";
$req .= "filemanager enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DG_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DG_USERS_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DG_USERS_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD id_dggroup INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD INDEX ( id_dggroup )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SECTIONS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SECTIONS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FLOW_APPROVERS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FLOW_APPROVERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FLOW_APPROVERS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FLOW_APPROVERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FORUMS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FORUMS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FORUMS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FORUMS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FAQCAT_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FAQCAT_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FAQCAT_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FAQCAT_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FM_FOLDERS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FM_FOLDERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_DB_DIRECTORIES_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_DB_DIRECTORIES_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_LDAP_DIRECTORIES_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_LDAP_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_LDAP_DIRECTORIES_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_LDAP_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_MAIL_DOMAINS_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_MAIL_DOMAINS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_MAIL_DOMAINS_TBL." ADD INDEX ( id_dgowner )" ;
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_MAIL_DOMAINS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." ADD id_dggroup INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade403to404()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_SECTIONS_TBL." ADD optional ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_PRIVATE_SECTIONS_TBL." ADD optional ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_PRIVATE_SECTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD optional ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SECTIONS_STATES_TBL." ADD hidden ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SECTIONS_STATES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_TOPCAT_ORDER_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_topcat int(11) unsigned NOT NULL default '0',";
$req .= "type smallint(2) unsigned NOT NULL default '0',";
$req .= "id_parent int(11) unsigned NOT NULL default '0',";
$req .= "ordering smallint(2) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_topcat (id_topcat),";
$req .= "KEY id_parent (id_parent)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_TOPCAT_ORDER_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD id_parent INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_CATEGORIES_TBL." ADD display_tmpl varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_CATEGORIES_TBL."</b> table failed !<br>";
	return $ret;
	}


$pos = 1;
$res = $db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='0'");
while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, id_parent, ordering) values ('".$arr['id']."', '1', '0', '".$pos."')");
	$pos++;
	}

$res = $db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL."");
while( $arr = $db->db_fetch_array($res))
	{
	$pos = 1;
	$res2 = $db->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$arr['id']."'");
	while( $rr = $db->db_fetch_array($res2))
		{
		$db->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, id_parent, ordering) values ('".$rr['id']."', '1', '".$arr['id']."', '".$pos."')");
		$pos++;
		}

	$res2 = $db->db_query("select id from ".BAB_TOPICS_TBL." where id_cat='".$arr['id']."' order by ordering asc");
	while( $rr = $db->db_fetch_array($res2))
		{
		$db->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, id_parent, ordering) values ('".$rr['id']."', '2', '".$arr['id']."', '".$pos."')");
		$pos++;
		}
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." DROP ordering";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD article_tmpl varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD display_tmpl varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." CHANGE name name varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." CHANGE description description varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." CHANGE head head MEDIUMTEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." CHANGE smtpuser smtpuser varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}
return $ret;
}

function upgrade404to405()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_DB_DIRECTORIES_TBL." ADD user_update ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DB_DIRECTORIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD pcalendar ENUM('Y','N') DEFAULT 'Y' NOT NULL AFTER directory";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade405to406()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD restrict_access ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD restriction varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `total_diskspace` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `user_diskspace` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `folder_diskspace` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `maxfilesize` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `uploadpath` VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `babslogan` VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `remember_login` ENUM('Y','N') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `change_password` ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `change_nickname` ENUM('Y','N') DEFAULT 'Y' NOT NULL ";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `name_order` ENUM('F L','L F') DEFAULT 'F L' NOT NULL ";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade406to407()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_ADDONS_TBL." ADD `version` varchar(127) DEFAULT '' NOT NULL ";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ADDONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "SELECT * FROM ".BAB_ADDONS_TBL;
$res = $db->db_query($req);
while ($arr = $db->db_fetch_array($res))
	{
	if (is_file($GLOBALS['babInstallPath']."addons/".$arr['title']."/addonini.php"))
		{
		$arr_ini = @parse_ini_file( $GLOBALS['babInstallPath']."addons/".$arr['title']."/addonini.php");
		$req = "update ".BAB_ADDONS_TBL." set version='".$arr_ini['version']."' where id='".$arr['id']."'";
		$res = $db->db_query($req);
		}
	}

return $ret;
}

function upgrade407to408()
{
$ret = "";
$db = $GLOBALS['babDB'];
 

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD adminname VARCHAR( 255 ) NOT NULL AFTER adminemail";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$db->db_query("update ".BAB_SITES_TBL." set adminname='Ovidentia Administrator'");

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `email_password` ENUM('Y','N') DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE `bab_mail_accounts` ADD `account_name` VARCHAR( 255 ) NOT NULL AFTER `id`";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "SELECT id,account FROM `bab_mail_accounts`";
$res = $db->db_query($req);
while ($arr = $db->db_fetch_array($res))
	 $db->db_query("UPDATE `bab_mail_accounts` SET `account_name` = '".$arr['account']."' WHERE `id` = '".$arr['id']."' LIMIT 1");

$req = "ALTER TABLE `bab_mail_accounts` CHANGE `account` `login` VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}


return $ret;
}


function upgrade408to409()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_ORG_CHARTS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "isprimary enum('N','Y') NOT NULL default 'N',";
$req .= "edit enum('N','Y') NOT NULL default 'N',";
$req .= "edit_author int(11) unsigned NOT NULL default '0',";
$req .= "edit_date datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "id_dgowner int(11) unsigned NOT NULL default '0',";
$req .= "id_directory int(11) unsigned NOT NULL default '0',";
$req .= "type smallint(5) unsigned NOT NULL default '0',";
$req .= "id_first_node int(11) unsigned NOT NULL default '0',";
$req .= "id_closed_nodes text NOT NULL,";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_dgowner (id_dgowner),";
$req .= "KEY id_directory (id_directory)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ORG_CHARTS_TBL."</b> table failed !<br>";
	return $ret;
	}

list($iddir) = $db->db_fetch_row($db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));
$req = "INSERT INTO ".BAB_ORG_CHARTS_TBL." VALUES (1, 'Ovidentia', 'Ovidentia organizational chart', 'Y', 'N', 0, '0000-00-00 00:00:00', 0, ".$iddir.", 0, 0, '')";
$db->db_query($req);

$req = "CREATE TABLE ".BAB_OCUPDATE_GROUPS_TBL." (";
$req .= "id tinyint(10) NOT NULL auto_increment,";
$req .= "id_object tinyint(10) NOT NULL default '0',";
$req .= "id_group tinyint(10) NOT NULL default '0',";
$req .= "UNIQUE KEY id (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OCUPDATE_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_OCVIEW_GROUPS_TBL." (";
$req .= "id tinyint(10) NOT NULL auto_increment,";
$req .= "id_object tinyint(10) NOT NULL default '0',";
$req .= "id_group tinyint(10) NOT NULL default '0',";
$req .= "UNIQUE KEY id (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OCVIEW_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_OC_ENTITIES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "id_oc int(11) unsigned NOT NULL default '0',";
$req .= "id_node int(11) unsigned NOT NULL default '0',";
$req .= "e_note text NOT NULL,";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_oc (id_oc),";
$req .= "KEY id_node (id_node),";
$req .= "KEY id_group (id_group)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OC_ENTITIES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_OC_ROLES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(225) NOT NULL default '',";
$req .= "description tinytext NOT NULL,";
$req .= "id_oc int(11) unsigned NOT NULL default '0',";
$req .= "id_entity int(11) NOT NULL default '0',";
$req .= "type tinyint(3) unsigned NOT NULL default '0',";
$req .= "cardinality enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_oc (id_oc),";
$req .= "KEY id_entity (id_entity)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OC_ROLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_OC_ROLES_USERS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_role int(11) unsigned NOT NULL default '0',";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "isprimary enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_role (id_role),";
$req .= "KEY id_user (id_user),";
$req .= "KEY isprimary (isprimary)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OC_ROLES_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_OC_TREES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "lf int(11) unsigned NOT NULL default '0',";
$req .= "lr int(11) unsigned NOT NULL default '0',";
$req .= "id_parent int(11) unsigned NOT NULL default '0',";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "info_user varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY lf (lf),";
$req .= "KEY lr (lr),";
$req .= "KEY id_parent (id_parent),";
$req .= "KEY id_user (id_user),";
$req .= "KEY info_user (info_user)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_OC_TREES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FLOW_APPROVERS_TBL." ADD satype tinyint(3) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FLOW_APPROVERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FA_INSTANCES_TBL." ADD iduser INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FA_INSTANCES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD id_ocentity INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FAQ_SUBCAT_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_cat int(11) unsigned NOT NULL default '0',";
$req .= "name text NOT NULL,";
$req .= "id_node int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_cat (id_cat,id_node),";
$req .= "KEY id_node (id_node)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FAQ_SUBCAT_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_FAQ_TREES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "lf int(11) unsigned NOT NULL default '0',";
$req .= "lr int(11) unsigned NOT NULL default '0',";
$req .= "id_parent int(11) unsigned NOT NULL default '0',";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "info_user varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY lf (lf),";
$req .= "KEY lr (lr),";
$req .= "KEY id_parent (id_parent),";
$req .= "KEY id_user (id_user),";
$req .= "KEY info_user (info_user)";
$req .= ")";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FAQ_TREES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FAQCAT_TBL." ADD id_root INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FAQCAT_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_FAQQR_TBL." ADD id_subcat INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FAQQR_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("select id from ".BAB_FAQCAT_TBL);
while($arr = $db->db_fetch_array($res))
{
	$db->db_query("insert into ".BAB_FAQ_TREES_TBL." (lf, lr, id_parent, id_user, info_user) values ('1', '2', '0', '".$arr['id']."','')");
	$idnode = $db->db_insert_id();
	$db->db_query("insert into ".BAB_FAQ_SUBCAT_TBL." (id_cat, name, id_node) values ('".$arr['id']."','', '".$idnode."')");
	$idscat = $db->db_insert_id();
	$db->db_query("update ".BAB_FAQQR_TBL." set id_subcat='".$idscat."' where idcat='".$arr['id']."'");
	$db->db_query("update ".BAB_FAQCAT_TBL." set id_root='".$idscat."' where id='".$arr['id']."'");
}

$res = $db->db_query("ALTER TABLE ".BAB_USERS_LOG_TBL." ADD `cnx_try` INT( 2 ) UNSIGNED NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade409to410()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_LDAP_SITES_FIELDS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "x_name varchar(255) NOT NULL default '',";
$req .= "id_site int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (`id`),";
$req .= "KEY `name` (`name`),";
$req .= "KEY `id_site` (`id_site`)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_LDAP_SITES_FIELDS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD ldap_allowadmincnx ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD ldap_passwordtype ENUM('text','md5','unix','sha') DEFAULT 'text' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD ldap_attribute TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD ldap_searchdn TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `ldap_password` tinyblob NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `ldap_userdn` TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `ldap_basedn` TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `ldap_host` tinytext NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `authentification` smallint(5) unsigned NOT NULL default '0'";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$resf = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
$res = $db->db_query("select id from ".BAB_SITES_TBL."");
while( $arr = $db->db_fetch_array($res))
	{
	while( $row = $db->db_fetch_array($resf))
		{
		$db->db_query("insert into ".BAB_LDAP_SITES_FIELDS_TBL." (name, x_name, id_site) values ('".$row['name']."','','".$arr['id']."')");
		}
	$db->db_data_seek($resf, 0 );
	}
return $ret;
}

function upgrade410to500()
{
$ret = "";
$db = $GLOBALS['babDB'];

$db->db_query("ALTER TABLE ".BAB_OC_ROLES_USERS_TBL." DROP INDEX id_role");
$db->db_query("ALTER TABLE ".BAB_OC_ROLES_USERS_TBL." ADD INDEX ( id_role )");
$db->db_query("ALTER TABLE ".BAB_OC_ROLES_USERS_TBL." ADD INDEX ( id_user )");
$db->db_query("ALTER TABLE ".BAB_OC_ROLES_USERS_TBL." ADD INDEX ( isprimary )");

$db->db_query("ALTER TABLE ".BAB_OC_ROLES_TBL." ADD INDEX ( type )");

$db->db_query("ALTER TABLE ".BAB_OCVIEW_GROUPS_TBL." CHANGE id id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT");
$db->db_query("ALTER TABLE ".BAB_OCVIEW_GROUPS_TBL." CHANGE id_object id_object INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
$db->db_query("ALTER TABLE ".BAB_OCVIEW_GROUPS_TBL." CHANGE id_group id_group INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
$db->db_query("ALTER TABLE ".BAB_OCVIEW_GROUPS_TBL." DROP INDEX id , ADD PRIMARY KEY ( id )");

$db->db_query("ALTER TABLE ".BAB_OCUPDATE_GROUPS_TBL." CHANGE id id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT");
$db->db_query("ALTER TABLE ".BAB_OCUPDATE_GROUPS_TBL." CHANGE id_object id_object INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
$db->db_query("ALTER TABLE ".BAB_OCUPDATE_GROUPS_TBL." CHANGE id_group id_group INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");
$db->db_query("ALTER TABLE ".BAB_OCUPDATE_GROUPS_TBL." DROP INDEX id , ADD PRIMARY KEY ( id )");

$req = "CREATE TABLE ".BAB_ART_DRAFTS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_author int(11) unsigned NOT NULL default '0',";
$req .= "date_creation datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "date_modification datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "date_submission datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "date_publication datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "date_archiving datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "title tinytext NOT NULL,";
$req .= "head mediumtext NOT NULL,";
$req .= "body longtext NOT NULL,";
$req .= "lang varchar(10) NOT NULL default '',";
$req .= "trash enum('N','Y') NOT NULL default 'N',";
$req .= "id_topic int(11) unsigned NOT NULL default '0',";
$req .= "restriction varchar(255) NOT NULL default '',";
$req .= "hpage_private enum('N','Y') NOT NULL default 'N',";
$req .= "hpage_public enum('N','Y') NOT NULL default 'N',";
$req .= "notify_members enum('Y','N') NOT NULL default 'N',";
$req .= "idfai int(11) unsigned NOT NULL default '0',";
$req .= "result smallint(5) unsigned NOT NULL default '0',";
$req .= "id_article int(11) unsigned NOT NULL default '0',";
$req .= "id_anonymous int(11) unsigned NOT NULL default '0',";
$req .= "approbation enum('0','1','2') NOT NULL default '0',";
$req .= "update_datemodif enum('Y','N') NOT NULL default 'Y',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_topic (id_topic),";
$req .= "KEY id_author (id_author),";
$req .= "KEY trash (trash),";
$req .= "KEY result (result)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ART_DRAFTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_ART_DRAFTS_FILES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_draft int(11) unsigned NOT NULL default '0',";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_draft (id_draft)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ART_DRAFTS_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_ART_DRAFTS_NOTES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_draft int(11) unsigned NOT NULL default '0',";
$req .= "content text NOT NULL,";
$req .= "id_author int(11) unsigned NOT NULL default '0',";
$req .= "date_note datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_draft (id_draft),";
$req .= "KEY id_author (id_author)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ART_DRAFTS_NOTES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_ART_FILES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_article int(11) unsigned NOT NULL default '0',";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_article (id_article)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ART_FILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_ART_LOG_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_article int(11) unsigned NOT NULL default '0',";
$req .= "id_author int(11) unsigned NOT NULL default '0',";
$req .= "date_log datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "action_log enum('lock','unlock','commit','refused','accepted') NOT NULL default 'lock',";
$req .= "art_log text NOT NULL,";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_article (id_article),";
$req .= "KEY id_author (id_author)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_ART_LOG_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_TOPICSMAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_TOPICSMAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_TOPICSMOD_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_TOPICSMOD_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD allow_hpages ENUM('Y','N') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD allow_pubdates ENUM('Y','N') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD allow_attachments ENUM('Y','N') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD allow_update ENUM('0','1','2') DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD max_articles tinyint(3) UNSIGNED DEFAULT '10' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD allow_manupdate ENUM('0','1','2') DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD idsa_update INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." DROP mod_com";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("select id, category, id_approver from ".BAB_TOPICS_TBL."");
$arrusersgroups = array();
while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['id_approver'] != 0 )
		{
		if( !isset($arrusersgroups[$arr['id_approver']]) )
			{
			$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_approver']."'");
			$rr = $db->db_fetch_array($res2);
			if( $res2 && $db->db_num_rows($res2) > 0 )
				{
				$grpname = "OVT_".$rr['firstname']."_".$rr['lastname'];
				$description = bab_translate("Topics manager");
				$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '0', 'N', 'N', 'N','0')");
				$id = $db->db_insert_id();
				$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id_approver']."','".$id."')");
				$arrusersgroups[$arr['id_approver']] = $id;
				$req = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'N', '2')";
				$db->db_query($req);
				}
			}
		if( isset($arrusersgroups[$arr['id_approver']]) )
			{
			$db->db_query("insert into ".BAB_TOPICSMAN_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['id_approver']]."')");
			$db->db_query("insert into ".BAB_TOPICSMOD_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['id_approver']]."')");
			$db->db_query("insert into ".BAB_TOPICSSUB_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['id_approver']]."')");
			}
		}
	}

$db->db_query("update ".BAB_TOPICS_TBL." set allow_manupdate='1'");
$db->db_query("ALTER TABLE ".BAB_TOPICS_TBL." DROP id_approver");

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD date_archiving DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD date_modification DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD ordering INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." ADD id_modifiedby INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_ARTICLES_TBL." CHANGE date_pub date_publication DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}

$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." ADD INDEX ( id_author )");
$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." ADD INDEX ( date_publication )");
$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." ADD INDEX ( date_archiving )");

$res = $db->db_query("select * from ".BAB_ARTICLES_TBL." where idfai!='0' and confirmed='N'");
if( $res && $db->db_num_rows($res) > 0 )
	{
	include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
	while( $arr = $db->db_fetch_array($res))
		{
		$db->db_query("insert into ".BAB_ART_DRAFTS_TBL." (id_author, id_topic, id_article, idfai, result, date_submission, date_creation, id_anonymous) values ('" .$arr['id_author']. "', '".$arr['id_topic']. "', '0', '".$arr['idfai']."', '".BAB_ART_STATUS_WAIT."', '".$arr['date']."', '".$arr['date']."', '0')");
		$id = $db->db_insert_id();
		$head = imagesUpdateLink($arr['head'], $arr['id']."_art_", $id."_draft_" );
		$body = imagesUpdateLink($arr['body'], $arr['id']."_art_", $id."_draft_" );		
		$db->db_query("update ".BAB_ART_DRAFTS_TBL." set head='".addslashes($head)."', body='".addslashes($body)."', title='".addslashes($arr['title'])."', lang='".$arr['lang']."' where id='".$id."'");
		$db->db_query("delete from ".BAB_ARTICLES_TBL." where id='".$arr['id']."'");
		}
	}

$db->db_query("update ".BAB_ARTICLES_TBL." set date_publication=date");
$db->db_query("update ".BAB_ARTICLES_TBL." set date_modification=date");
$db->db_query("update ".BAB_ARTICLES_TBL." set id_modifiedby=id_author");

$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." DROP confirmed");
$db->db_query("ALTER TABLE ".BAB_ARTICLES_TBL." DROP idfai");

$db->db_query("ALTER TABLE ".BAB_COMMENTS_TBL." ADD INDEX ( idfai )");


$req = "CREATE TABLE ".BAB_FORUMSMAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_FORUMSMAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("select id, moderator from ".BAB_FORUMS_TBL."");
$arrusersgroups = array();
while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['moderator'] != 0 )
		{
		if( !isset($arrusersgroups[$arr['moderator']])) 
			{
			$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['moderator']."'");
			$rr = $db->db_fetch_array($res2);
			if( $res2 && $db->db_num_rows($res2) > 0 )
				{
				$grpname = "OVF_".$rr['firstname']."_".$rr['lastname'];
				$description = bab_translate("Forums manager");
				$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '0', 'N', 'N', 'N','0')");
				$id = $db->db_insert_id();
				$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['moderator']."','".$id."')");
				$arrusersgroups[$arr['moderator']] = $id;
				$req = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'N', '2')";
				$db->db_query($req);
				}
			}
		if( isset($arrusersgroups[$arr['moderator']])) 
			{
			$db->db_query("insert into ".BAB_FORUMSMAN_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['moderator']]."')");
			}
		}
	}

$db->db_query("ALTER TABLE ".BAB_FORUMS_TBL." DROP moderator");

/* INTRA */
$db->db_query("ALTER TABLE `bab_sites` ADD display_disclaimer ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL AFTER registration");

$req = "CREATE TABLE ".BAB_SITES_FIELDS_REGISTRATION_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_site tinyint(2) unsigned NOT NULL default '0',";
$req .= "id_field int(11) unsigned NOT NULL default '0',";
$req .= "registration enum('N','Y') NOT NULL default 'N',";
$req .= "required enum('N','Y') NOT NULL default 'N',";
$req .= "multilignes enum('N','Y') NOT NULL default 'N',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_site (id_site)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_SITES_FIELDS_REGISTRATION_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_SITES_DISCLAIMERS_TBL." (";
$req .= "id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,";
$req .= "id_site TINYINT( 2 ) UNSIGNED NOT NULL ,";
$req .= "disclaimer_text LONGTEXT NOT NULL ,";
$req .= "PRIMARY KEY ( id ) ,";
$req .= "KEY id_site (id_site)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_SITES_DISCLAIMERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("select id from ".BAB_SITES_TBL."");
while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("INSERT INTO ".BAB_SITES_DISCLAIMERS_TBL." (id_site, disclaimer_text ) VALUES (".$arr['id'].", '')");

	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 1, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 2, 'Y', 'Y', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 3, 'Y', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 4, 'Y', 'Y', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 5, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 6, 'Y', 'Y', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 7, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 8, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 9, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 10, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 11, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 12, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 13, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 14, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 15, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 16, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 17, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 18, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 19, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 20, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 21, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 22, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 23, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 24, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 25, 'N', 'N', 'N')");
	$db->db_query("INSERT INTO ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes ) VALUES (".$arr['id'].", 26, 'N', 'N', 'N')");
	}

$req = "CREATE TABLE ".BAB_PROFILES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "multiplicity enum('Y','N') NOT NULL default 'Y',";
$req .= "inscription enum('N','Y') NOT NULL default 'N',";
$req .= "id_dgowner int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_dgowner (id_dgowner)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_PROFILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_PROFILES_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_PROFILES_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_PROFILES_GROUPSSET_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_PROFILES_GROUPSSET_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_SITES_HPMAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_SITES_HPMAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$arr= $db->db_fetch_array($db->db_query("select id from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
$db->db_query("INSERT INTO ".BAB_SITES_HPMAN_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."', '3')");


$subdir = trim(substr($_SERVER['SCRIPT_NAME'],0,strlen($_SERVER['SCRIPT_NAME'])-strlen(strrchr($_SERVER['SCRIPT_NAME'],'/'))),'/');
if (substr($_SERVER['DOCUMENT_ROOT'],-1) == '/')
	$subdir = $_SERVER['DOCUMENT_ROOT'].$subdir.'/skins/';
else
	$subdir = $_SERVER['DOCUMENT_ROOT'].'/'.$subdir.'/skins/';
if (is_dir($subdir))
	{
	if ($dh = opendir($subdir)) {
       while (($file = readdir($dh)) !== false) 
		   {
		   if (is_dir($subdir.$file) && is_file($subdir.$file.'/templates/topicsdisplay.html'))
				{
				rename($subdir.$file.'/templates/topicsdisplay.html',$subdir.$file.'/templates/topicsdisplay.html.old');
				}
			}
		closedir($dh);
		}
	}

$req = "ALTER TABLE `".BAB_SITES_TBL."` CHANGE `remember_login` `remember_login` ENUM( 'Y', 'N', 'L' ) DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade500to501()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_PROFILES_TBL." ADD `required` ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL AFTER inscription";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_PROFILES_TBL."</b> table failed !<br>";
	return $ret;
	}
return $ret;
}

function upgrade501to502()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_DG_GROUPS_TBL." ADD orgchart ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DG_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade502to503()
{
$ret = "";
$db = $GLOBALS['babDB'];

return $ret;
}

function upgrade503to510()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_USERS_TBL." ADD date_shortformat VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_TBL." ADD date_longformat VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_USERS_TBL." ADD time_format VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD date_shortformat VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD date_longformat VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD time_format VARCHAR( 255 ) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIR_FIELDSVALUES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_fieldextra int(11) unsigned NOT NULL default '0',";
$req .= "field_value varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_fieldextra (id_fieldextra)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_FIELDSVALUES_TBL."</b> table failed !<br>";
	return $ret;
	}

$arrids = array();
$res = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where default_value!=''");
while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("insert into ".BAB_DBDIR_FIELDSVALUES_TBL." (id_fieldextra, field_value) values ('".$arr['id']."', '".$arr['default_value']."')");
	$id = $db->db_insert_id();
	$arrids[$arr['id']] = $id;
	}

$req = "ALTER TABLE ".BAB_DBDIR_FIELDSEXTRA_TBL." CHANGE default_value default_value INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DBDIR_FIELDSEXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_DBDIR_FIELDSEXTRA_TBL." ADD list_ordering INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DBDIR_FIELDSEXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_DBDIR_FIELDSEXTRA_TBL." ADD multi_values ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL ";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DBDIR_FIELDSEXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_DBDIR_FIELDSEXTRA_TBL." ADD disabled ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL AFTER multilignes";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_DBDIR_FIELDSEXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}

foreach($arrids as $key => $value )
	{
	$db->db_query("update ".BAB_DBDIR_FIELDSEXTRA_TBL." set default_value='".$value."' where id='".$key."'");
	}

$req = "CREATE TABLE ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_directory int(11) unsigned NOT NULL default '0',";
$req .= "name varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_directory (id_directory)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_FIELDS_DIRECTORY_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_fieldx int(11) unsigned NOT NULL default '0',";
$req .= "id_entry int(11) unsigned NOT NULL default '0',";
$req .= "field_value varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_fieldx (id_fieldx),";
$req .= "KEY id_entry (id_entry)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_DBDIR_ENTRIES_EXTRA_TBL."</b> table failed !<br>";
	return $ret;
	}

$resf = $db->db_query("select lsft.*, dft.id as idfx from ".BAB_LDAP_SITES_FIELDS_TBL." lsft left join ".BAB_DBDIR_FIELDS_TBL." dft on lsft.name=dft.name");

$req = "ALTER TABLE ".BAB_LDAP_SITES_FIELDS_TBL." CHANGE `name` id_field INT( 11 ) UNSIGNED NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_LDAP_SITES_FIELDS_TBL."</b> table failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($resf))
	{
	$db->db_query("update ".BAB_LDAP_SITES_FIELDS_TBL." set id_field='".$arr['idfx']."' where id='".$arr['id']."'");
	}

return $ret;
}

function upgrade510to520()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_STATSMAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATSMAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_EVENTS_TBL." (";
$req .= "id bigint(20) unsigned NOT NULL auto_increment,";
$req .= "evt_id_site tinyint(2) unsigned NOT NULL default '0',";
$req .= "evt_time datetime NOT NULL default '0000-00-00 00:00:00',";
$req .= "evt_tg varchar(255) NOT NULL default '',";
$req .= "evt_referer varchar(255) NOT NULL default '',";
$req .= "evt_ip varchar(15) NOT NULL default '',";
$req .= "evt_host varchar(255) NOT NULL default '',";
$req .= "evt_client varchar(255) NOT NULL default '',";
$req .= "evt_url varchar(255) NOT NULL default '',";
$req .= "evt_session_id varchar(32) NOT NULL default '',";
$req .= "evt_iduser int(11) unsigned NOT NULL default '0',";
$req .= "evt_info text NOT NULL,";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_EVENTS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade520to530()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_CAL_PUBLIC_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "name varchar(60) NOT NULL default '',";
$req .= "description varchar(255) NOT NULL default '',";
$req .= "id_dgowner int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_dgowner (id_dgowner)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_PUBLIC_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_CAL_PUB_VIEW_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_PUB_VIEW_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_CAL_PUB_MAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_PUB_MAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_CAL_PUB_GRP_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_PUB_GRP_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_CAL_RES_VIEW_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_RES_VIEW_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_CAL_RES_MAN_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_RES_MAN_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_CAL_RES_GRP_GROUPS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_object int(11) unsigned NOT NULL default '0',";
$req .= "id_group int(11) unsigned NOT NULL default '0',";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_object (id_object),";
$req .= "KEY id_group (id_group)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_RES_GRP_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_CAL_USER_OPTIONS_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_user int(11) unsigned NOT NULL default '0',";
$req .= "startday tinyint(4) NOT NULL default '0',";
$req .= "allday enum('Y','N') NOT NULL default 'Y',";
$req .= "week_numbers enum('N','Y') NOT NULL default 'N',";
$req .= "usebgcolor enum('Y','N') NOT NULL default 'Y',";
$req .= "elapstime tinyint(2) unsigned NOT NULL default '30',";
$req .= "defaultview tinyint(3) NOT NULL default '0',";
$req .= "work_days varchar(20) NOT NULL default '',";
$req .= "start_time time,";
$req .= "end_time time,";
$req .= "user_calendarids text NOT NULL,";
$req .= "PRIMARY KEY  (id),";
$req .= "KEY id_user (id_user)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_USER_OPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("select * from ".BAB_CALENDAR_TBL."");
while( $arr = $db->db_fetch_array($res))
	{
	switch($arr['type'])
		{
		case '1': // user
			break;
		case '2': // group
			list($countevt) = $db->db_fetch_row($db->db_query("select count(id) from ".BAB_CAL_EVENTS_TBL." where id_cal='".$arr['id']."'"));
			if( $countevt > 0 )
			{
			list($grpname) = $db->db_fetch_row($db->db_query("select name from ".BAB_GROUPS_TBL." where id='".$arr['owner']."'"));
			$db->db_query("insert into ".BAB_CAL_PUBLIC_TBL." (name, description) values ('".addslashes($grpname)."','')");
			$idcal = $db->db_insert_id();
			$db->db_query("update ".BAB_CALENDAR_TBL." set owner='".$idcal."' where id='".$arr['id']."'");
			$db->db_query("insert into ".BAB_CAL_PUB_VIEW_GROUPS_TBL." ( id_object, id_group ) values ('".$idcal."','".$arr['owner']."')");
			if( $arr['owner'] == 1 || $arr['owner'] == 2 )
				{
				$db->db_query("insert into ".BAB_CAL_PUB_MAN_GROUPS_TBL." ( id_object, id_group ) values ('".$arr['id']."','3')");
				}
			else
				{
				$db->db_query("insert into ".BAB_CAL_PUB_MAN_GROUPS_TBL." ( id_object, id_group ) values ('".$arr['id']."','".$arr['owner']."')");
				}

			if( $arr['owner'] != 2 )
				{
				$db->db_query("insert into ".BAB_CAL_PUB_GRP_GROUPS_TBL." ( id_object, id_group ) values ('".$idcal."','".$arr['owner']."')");
				}
			}
			else
			{
				$db->db_query("delete from ".BAB_CALENDAR_TBL." where id='".$arr['id']."'");
			}
			break;
		case '3': // resource
			$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_RESOURCESCAL_TBL." where id='".$arr['owner']."'"));
			$db->db_query("insert into ".BAB_CAL_RES_VIEW_GROUPS_TBL." ( id_object, id_group ) values ('".$arr['id']."','".$arr['owner']."')");
			if( $arr['owner'] == 1 || $arr['owner'] == 2 )
				{
				$db->db_query("insert into ".BAB_CAL_RES_MAN_GROUPS_TBL." ( id_object, id_group ) values ('".$arr['id']."','3')");
				}
			else
				{
				$db->db_query("insert into ".BAB_CAL_PUB_MAN_GROUPS_TBL." ( id_object, id_group ) values ('".$arr['id']."','".$arr['owner']."')");
				}
			break;
		}
	}

$db->db_query("ALTER TABLE ".BAB_RESOURCESCAL_TBL." RENAME ".BAB_CAL_RESOURCES_TBL."");
$db->db_query("ALTER TABLE ".BAB_CAL_RESOURCES_TBL." DROP id_group");
$db->db_query("ALTER TABLE ".BAB_CAL_RESOURCES_TBL." ADD id_dgowner INT( 11 ) UNSIGNED NOT NULL");
$db->db_query("ALTER TABLE ".BAB_CAL_RESOURCES_TBL." ADD INDEX ( `id_dgowner` )");

$db->db_query("ALTER TABLE ".BAB_CATEGORIESCAL_TBL." RENAME ".BAB_CAL_CATEGORIES_TBL."");
$db->db_query("ALTER TABLE ".BAB_CAL_CATEGORIES_TBL." DROP id_group");

$res = $db->db_query("select * from ".BAB_CALOPTIONS_TBL."");
while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("insert into ".BAB_CAL_USER_OPTIONS_TBL." ( id_user, startday, allday, start_time, end_time, usebgcolor, elapstime, defaultview, work_days, week_numbers) values ('".$arr['id_user']."', '".$arr['startday']."', '".$arr['allday']."', '08:00:00', '18:00:00', '".$arr['usebgcolor']."', '".$arr['elapstime']."', '".$arr['defaultview']."', '1,2,3,4,5', 'N')");
	}

$db->db_query("DROP table ".BAB_CALOPTIONS_TBL."");


$req = "CREATE TABLE ".BAB_CAL_EVENTS_OWNERS_TBL." (";
$req .= "id_event int(10) unsigned NOT NULL default '0',";
$req .= "id_cal int(10) unsigned NOT NULL default '0',";
$req .= "status tinyint(3) unsigned NOT NULL default '0',";
$req .= "KEY id_event (id_event,id_cal,status))";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_CAL_EVENTS_OWNERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("SELECT id, id_cal, CONCAT(start_date,' ',start_time) start_date, CONCAT(end_date,' ',end_time) end_date FROM ".BAB_CAL_EVENTS_TBL."");

$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." CHANGE `start_date` `start_date` DATETIME NOT NULL , CHANGE `end_date` `end_date` DATETIME NOT NULL");

while( $arr = $db->db_fetch_array($res))
	{
	$db->db_query("UPDATE ".BAB_CAL_EVENTS_TBL." SET start_date='".$arr['start_date']."', end_date='".$arr['end_date']."' WHERE id='".$arr['id']."'");

	$db->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal) VALUES ('".$arr['id']."','".$arr['id_cal']."')");
	}

$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD `color` VARCHAR( 8 ) DEFAULT '' NOT NULL");

$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD `bprivate` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL , ADD `block` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL , ADD `bfree` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL");

$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." DROP `start_time` ,DROP `end_time`");

$db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." DROP `id_cal`");

return $ret;
}

function upgrade530to531()
{
$ret = "";
$db = $GLOBALS['babDB'];

return $ret;
}

function upgrade531to540()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "CREATE TABLE ".BAB_STATS_ADDONS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_addon char(255) NOT NULL default '',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_addon (st_addon)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_ADDONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_ARTICLES_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_article_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_article_id (st_article_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_ARTICLES_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_STATS_ARTICLES_REF_TBL." (";
$req .= "st_article_id int(11) unsigned NOT NULL default '0',";
$req .= "st_module_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_article_id (st_article_id),";
$req .= "KEY st_module_id (st_module_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_ARTICLES_REF_TBL."</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE ".BAB_STATS_FAQQRS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_faqqr_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_faqqr_id (st_faqqr_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_FAQQRS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_FAQS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_faq_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_faq_id (st_faq_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_FAQS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_FMFILES_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_fmfile_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_fmfile_id (st_fmfile_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_FMFILES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_FMFOLDERS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_folder_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_folder_id (st_folder_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_FMFOLDERS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_FORUMS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_forum_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_forum_id (st_forum_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_FORUMS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_IMODULES_TBL." (";
$req .= "id tinyint(3) unsigned NOT NULL auto_increment,";
$req .= "module_name varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_IMODULES_TBL."</b> table failed !<br>";
	return $ret;
	}

$db->db_query("INSERT INTO bab_stats_imodules VALUES (1, 'Others')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (2, 'Articles')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (3, 'Forums')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (4, 'Files')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (5, 'Faqs')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (6, 'Private home page')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (7, 'Public home page')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (8, 'Agenda')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (9, 'Summary page')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (10, 'Directories')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (11, 'Search')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (12, 'Charts')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (13, 'Notes')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (14, 'Contacts')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (15, 'Administration')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (16, 'Vacation')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (17, 'Mail')");
$db->db_query("INSERT INTO bab_stats_imodules VALUES (18, 'Add-ons')");

$req = "CREATE TABLE ".BAB_STATS_IPAGES_TBL." (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "page_url text NOT NULL,";
$req .= "page_name varchar(255) NOT NULL default '',";
$req .= "PRIMARY KEY  (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_IPAGES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_MODULES_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_module_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_module_id (st_module_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_MODULES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_OVML_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_ovml_file char(255) NOT NULL default '',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_ovml_file (st_ovml_file),";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_OVML_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_PAGES_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_page_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_page_id (st_page_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_PAGES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_POSTS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_post_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_post_id (st_post_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_POSTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_SEARCH_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_word char(255) NOT NULL default '',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_word (st_word),";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_SEARCH_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_THREADS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_thread_id int(11) unsigned NOT NULL default '0',";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour),";
$req .= "KEY st_thread_id (st_thread_id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_THREADS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE ".BAB_STATS_XLINKS_TBL." (";
$req .= "st_date date NOT NULL default '0000-00-00',";
$req .= "st_hour tinyint(3) unsigned NOT NULL default '0',";
$req .= "st_xlink_url text NOT NULL,";
$req .= "st_hits int(11) unsigned NOT NULL default '0',";
$req .= "KEY st_date (st_date),";
$req .= "KEY st_hour (st_hour)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>".BAB_STATS_XLINKS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `stat_update_time` DATETIME NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade540to541()
{
$ret = "";
$db = $GLOBALS['babDB'];

return $ret;
}

function upgrade541to542()
{
$ret = "";
$db = &$GLOBALS['babDB'];

$res = $db->db_query("ALTER TABLE `".BAB_FORUMS_TBL."` CHANGE `description` `description` TEXT NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_FORUMS_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("ALTER TABLE `".BAB_ADDONS_TBL."` ADD `installed` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL");
$res = $db->db_query("ALTER TABLE `".BAB_ADDONS_TBL."` ADD INDEX ( `installed` )");
$res = $db->db_query("ALTER TABLE `".BAB_ADDONS_TBL."` ADD INDEX ( `enabled` )");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_ADDONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("SELECT * FROM ".BAB_ADDONS_TBL."");
while($arr = $db->db_fetch_array($res))
	{
	$arr_ini = @parse_ini_file( 'addons/'.$arr['title'].'/addonini.php');
	if( !empty($arr_ini['version']) && $arr_ini['version'] != $arr['version'])
		{
		$yesno = 'N';
		}
	else
		{
		$yesno = 'Y';
		}
	$db->db_query("UPDATE `".BAB_ADDONS_TBL."` SET installed='".$yesno."' WHERE id='".$arr['id']."'");
	}

if( !$res)
	{
	$ret = "Upgrade of <b>".BAB_ADDONS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}


function upgrade542to543()
{
	$ret = "";
	$db = &$GLOBALS['babDB'];
	$res = $db->db_query("CREATE TABLE `".BAB_FORUMSFILES_GROUPS_TBL."` (
						  `id` int(11) unsigned NOT NULL auto_increment,
						  `id_object` int(11) unsigned NOT NULL default '0',
						  `id_group` int(11) unsigned NOT NULL default '0',
						  PRIMARY KEY  (`id`),
						  KEY `id_object` (`id_object`),
						  KEY `id_group` (`id_group`)
						) TYPE=MyISAM");


	if( !$res)
	{
	$ret = "Upgrade of <b>".BAB_FORUMSFILES_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;

}

function upgrade543to544()
{
$ret = "";
$db = $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_VAC_TYPES_TBL." ADD `color` VARCHAR( 6 ) NOT NULL ";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_VAC_TYPES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_VAC_TYPES_TBL." ADD `cbalance` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_VAC_TYPES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD `cbalance` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("ALTER TABLE `".BAB_CAL_USER_OPTIONS_TBL."` CHANGE `work_days` `dispdays` VARCHAR( 20 ) NOT NULL");
$res = $db->db_query("ALTER TABLE `".BAB_CAL_USER_OPTIONS_TBL."` ADD `workdays` VARCHAR( 20 ) NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_USER_OPTIONS_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD `workdays` VARCHAR( 20 ) DEFAULT '1,2,3,4,5' NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("ALTER TABLE `".BAB_SITES_TBL."` ADD `user_workdays` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("CREATE TABLE `".BAB_SITES_NONWORKING_CONFIG_TBL."` (
					  `id` int(10) unsigned NOT NULL auto_increment,
					  `id_site` int(10) unsigned NOT NULL default '0',
					  `nw_type` smallint(5) unsigned NOT NULL default '0',
					  `nw_day` varchar(64) NOT NULL default '',
					  PRIMARY KEY  (`id`),
					  KEY `id_site` (`id_site`,`nw_type`)
					)");

if( !$res)
	{
	$ret = "Creation of <b>".BAB_SITES_NONWORKING_CONFIG_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("CREATE TABLE `".BAB_SITES_NONWORKING_DAYS_TBL."` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
					`id_site` INT UNSIGNED NOT NULL ,
					`nw_day` DATE NOT NULL ,
					`nw_type` VARCHAR( 64 ) NOT NULL ,
					PRIMARY KEY ( `id` ) ,
					INDEX ( `id_site` )
					)");


if( !$res)
	{
	$ret = "Creation of <b>".BAB_SITES_NONWORKING_DAYS_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("CREATE TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` (
					`id` int(10) unsigned NOT NULL auto_increment,
					  `id_right` int(10) unsigned NOT NULL default '0',
					  `period_start` date NOT NULL default '0000-00-00',
					  `period_end` date NOT NULL default '0000-00-00',
					  `trigger_nbdays_min` float NOT NULL default '0',
					  `trigger_nbdays_max` float NOT NULL default '0',
					  `trigger_inperiod` tinyint(4) NOT NULL default '0',
					  `right_inperiod` tinyint(4) NOT NULL default '0',
					  PRIMARY KEY  (`id`),
					  KEY `id_right` (`id_right`,`period_start`,`period_end`)
					) ");

if( !$res)
	{
	$ret = "Creation of <b>".BAB_VAC_RIGHTS_RULES_TBL."</b> table failed !<br>";
	return $ret;
	}


$res = $db->db_query("CREATE TABLE ".BAB_USERS_UNAVAILABILITY_TBL." (
					id_user int(11) unsigned NOT NULL default '0',
					  start_date date NOT NULL default '0000-00-00',
					  end_date date NOT NULL default '0000-00-00',
					  id_substitute int(11) NOT NULL default '0',
					  KEY id_user (id_user,id_substitute)
					)");

if( !$res)
	{
	$ret = "Creation of <b>".BAB_USERS_UNAVAILABILITY_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." DROP ldap_basedn, DROP ldap_userdn, DROP ldap_password, DROP ldap_passwordtype";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `ldap_domainname` VARCHAR( 255 ) NOT NULL AFTER `ldap_host`";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}



function upgrade544to545()
{
$ret = "";
$db = & $GLOBALS['babDB'];
$res = $db->db_query("ALTER TABLE `".BAB_SITES_NONWORKING_CONFIG_TBL."` ADD `nw_text` VARCHAR( 128 ) NOT NULL");
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_NONWORKING_CONFIG_TBL."</b> table failed !<br>";
	return $ret;
	}

$res = $db->db_query("select id, email_confirm from ".BAB_SITES_TBL."");

$req = "ALTER TABLE ".BAB_SITES_TBL." CHANGE `email_confirm` `email_confirm` TINYINT( 2 ) UNSIGNED DEFAULT '0' NOT NULL ";
$res2 = $db->db_query($req);
if( !$res2)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

while( $arr = $db->db_fetch_array($res))
	{
	if( $arr['email_confirm'] == 'Y')
		{
		$ec = 0;
		}
	else
		{
		$ec = 1;
		}
	$db->db_query("update ".BAB_SITES_TBL." set email_confirm='".$ec."' where id='".$arr['id']."'");	
	}

return $ret;
}

function upgrade545to546()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_CAL_PUBLIC_TBL." ADD `idsa` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_PUBLIC_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_CAL_RESOURCES_TBL." ADD `idsa` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_RESOURCES_TBL."</b> table failed !<br>";
	return $ret;
	}
 
$req = "ALTER TABLE ".BAB_CAL_EVENTS_OWNERS_TBL." ADD `idfai` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_CAL_EVENTS_OWNERS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade546to550()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade550to551()
{
$ret = "";
$db = & $GLOBALS['babDB'];

list($n) = $db->db_fetch_array($db->db_query("SELECT COUNT(*) FROM ".BAB_MIME_TYPES_TBL." WHERE ext='pdf'"));
if ($n == 0)
	{
	$db->db_query("INSERT INTO ".BAB_MIME_TYPES_TBL." (ext, mimetype) VALUES ('pdf','application/pdf')");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE `".BAB_VAC_RIGHTS_RULES_TBL."` trigger_type"));
if ( $arr[0] != 'trigger_type' )
	{
	$req = "ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` ADD `trigger_type` INT UNSIGNED NOT NULL AFTER `trigger_nbdays_max`";
	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Alteration of <strong>".BAB_VAC_RIGHTS_RULES_TBL."</strong> table failed !<br />";
		return $ret;
		}
	 
	$req = "ALTER TABLE `".BAB_VAC_RIGHTS_RULES_TBL."` ADD INDEX ( `trigger_type` )";
	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Alteration of <strong>".BAB_VAC_RIGHTS_RULES_TBL."</strong> table failed !<br />";
		return $ret;
		}
	}

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD `stat_log` ENUM('N','Y') DEFAULT 'N' NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

$db->db_query("update ".BAB_SITES_TBL." set stat_log='Y'");

$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (19, 'Login / Registration')");
$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (20, 'User options')");
$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (21, 'Workflow approbations')");
$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (22, 'Ovidentia Editor')");
$db->db_query("INSERT INTO ".BAB_STATS_IMODULES_TBL." VALUES (23, 'OvML')");

return $ret;
}


function upgrade551to552()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_SITES_TBL." ADD ldap_encryptiontype varchar(255) NOT NULL AFTER ldap_allowadmincnx";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_SITES_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade552to553()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$req = "ALTER TABLE ".BAB_USERS_LOG_TBL." ADD cpw varchar(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_USERS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade553to554()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_CAL_EVENTS_TBL." location"));
if ( $arr[0] != 'location' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_CAL_EVENTS_TBL." ADD location VARCHAR(255) NOT NULL AFTER description");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_CAL_EVENTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_EVENTS_NOTES_TBL."'"));
if ( $arr[0] != BAB_CAL_EVENTS_NOTES_TBL )
	{
	$res = $db->db_query("CREATE TABLE ".BAB_CAL_EVENTS_NOTES_TBL." (
					id_event int(10) unsigned NOT NULL default '0',
					id_user int(10) unsigned NOT NULL default '0',
					note text NOT NULL,
					UNIQUE KEY id_event (id_event,id_user)
					)");


	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_EVENTS_NOTES_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_EVENTS_REMINDERS_TBL."'"));
if ( $arr[0] != BAB_CAL_EVENTS_REMINDERS_TBL )
	{
	$res = $db->db_query("CREATE TABLE ".BAB_CAL_EVENTS_REMINDERS_TBL." (
						  id_event int(11) unsigned NOT NULL default '0',
						  id_user int(11) unsigned NOT NULL default '0',
						  day smallint(3) NOT NULL default '0',
						  hour smallint(2) NOT NULL default '0',
						  minute smallint(2) NOT NULL default '0',
						  bemail enum('N','Y') NOT NULL default 'N',
						  processed enum('N','Y') NOT NULL default 'N',
						  KEY id_event (id_event,id_user)
						)");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_EVENTS_REMINDERS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_FMMANAGERS_GROUPS_TBL."'"));
if ( $arr[0] != BAB_FMMANAGERS_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_FMMANAGERS_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_FMMANAGERS_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("select id, manager, id_dgowner from ".BAB_FM_FOLDERS_TBL."");
	$arrusersgroups = array();
	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['manager'] != 0 )
			{
			if( !isset($arrusersgroups[$arr['manager']])) 
				{
				$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['manager']."'");
				$rr = $db->db_fetch_array($res2);
				if( $res2 && $db->db_num_rows($res2) > 0 )
					{
					$grpname = "OVFM_".$rr['firstname']."_".$rr['lastname'];
					$description = bab_translate("Folder manager");
					$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '".$arr['id_dgowner']."', 'N', 'N', 'N','0')");
					$id = $db->db_insert_id();
					$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['manager']."','".$id."')");
					$arrusersgroups[$arr['manager']] = $id;
					}
				}
			if( isset($arrusersgroups[$arr['manager']])) 
				{
				$db->db_query("insert into ".BAB_FMMANAGERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['manager']]."')");
				}
			}
		}

	$db->db_query("ALTER TABLE ".BAB_FM_FOLDERS_TBL." DROP manager");
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_FAQMANAGERS_GROUPS_TBL."'"));
if ( $arr[0] != BAB_FAQMANAGERS_GROUPS_TBL )
	{
	$res = $db->db_query("
		CREATE TABLE `".BAB_FAQMANAGERS_GROUPS_TBL."` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `id_object` int(11) unsigned NOT NULL default '0',
			  `id_group` int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `id_object` (`id_object`),
			  KEY `id_group` (`id_group`)
			)
		");
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_FAQMANAGERS_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}

	$res = $db->db_query("select id, id_manager, id_dgowner from ".BAB_FAQCAT_TBL."");
	$arrusersgroups = array();
	while( $arr = $db->db_fetch_array($res))
		{
		if( $arr['id_manager'] != 0 )
			{
			if( !isset($arrusersgroups[$arr['id_manager']])) 
				{
				$res2 = $db->db_query("select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_manager']."'");
				$rr = $db->db_fetch_array($res2);
				if( $res2 && $db->db_num_rows($res2) > 0 )
					{
					$grpname = "OVFAQ_".$rr['firstname']."_".$rr['lastname'];
					$description = bab_translate("Faq manager");
					$db->db_query("insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, notes, contacts, pcalendar, id_dgowner) VALUES ('" .$grpname. "', '" . $description. "', 'N', '0', '".$arr['id_dgowner']."', 'N', 'N', 'N','0')");
					$id = $db->db_insert_id();
					$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id_manager']."','".$id."')");
					$arrusersgroups[$arr['id_manager']] = $id;

					}
				}
			if( isset($arrusersgroups[$arr['id_manager']])) 
				{
				$db->db_query("insert into ".BAB_FAQMANAGERS_GROUPS_TBL." (id_object, id_group) values ('".$arr['id']."','".$arrusersgroups[$arr['id_manager']]."')");
				}
			}
		}

	$db->db_query("ALTER TABLE ".BAB_FAQCAT_TBL." DROP id_manager");
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_COLLECTIONS_TBL." id_cat"));
if ( $arr[0] != 'id_cat' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_COLLECTIONS_TBL." ADD id_cat INT(11) UNSIGNED NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_COLLECTIONS_TBL."</b> table failed !<br>";
		return $ret;
		}
	$db->db_query("ALTER TABLE ".BAB_VAC_COLLECTIONS_TBL." ADD INDEX ( `id_cat` )");
	}

return $ret;
}

function upgrade554to555()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_begin_valid"));
if ( $arr[0] != 'date_begin_valid' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_begin_valid DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_end_valid"));
if ( $arr[0] != 'date_end_valid' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_end_valid DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_end_fixed"));
if ( $arr[0] != 'date_end_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_end_fixed DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." date_begin_fixed"));
if ( $arr[0] != 'date_begin_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD date_begin_fixed DATE NOT NULL");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." day_begin_fixed"));
if ( $arr[0] != 'day_begin_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD day_begin_fixed tinyint(3) unsigned NOT NULL default '0'");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_VAC_RIGHTS_TBL." day_end_fixed"));
if ( $arr[0] != 'day_end_fixed' )
	{
	$res = $db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." ADD day_end_fixed tinyint(3) unsigned NOT NULL default '0'");
	if( !$res)
		{
		$ret = "Alteration of <b>".BAB_VAC_RIGHTS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

$db->db_query("ALTER TABLE ".BAB_VAC_RIGHTS_TBL." CHANGE `quantity` `quantity` DECIMAL( 3, 1 ) UNSIGNED DEFAULT '0' NOT NULL"); 
return $ret;
}

function upgrade555to556()
{
$ret = "";
$db = & $GLOBALS['babDB'];


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_SITES_EDITOR_TBL."'"));
if ( $arr[0] != BAB_SITES_EDITOR_TBL )
	{
	$res = $db->db_query("
		CREATE TABLE `".BAB_SITES_EDITOR_TBL."` (
			  `id` int(10) unsigned NOT NULL default '0',
			  `id_site` int(10) unsigned NOT NULL default '0',
			  `use_editor` tinyint(3) unsigned NOT NULL default '1',
			  `filter_html` tinyint(3) unsigned NOT NULL default '0',
			  `tags` text NOT NULL,
			  `attributes` text NOT NULL,
			  `verify_href` tinyint(3) unsigned NOT NULL default '0',
			  `bitstring` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `id_site` (`id_site`)
			)
		");

	if( !$res)
		{
		$ret = "Creation of <b>".BAB_SITES_EDITOR_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}

function upgrade556to557()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade557to558()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade558to559()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_CAL_RES_ADD_GROUPS_TBL."'"));
if ( $arr[0] != BAB_CAL_RES_ADD_GROUPS_TBL )
	{
	$req = "CREATE TABLE ".BAB_CAL_RES_ADD_GROUPS_TBL." (";
	$req .= "id int(11) unsigned NOT NULL auto_increment,";
	$req .= "id_object int(11) unsigned NOT NULL default '0',";
	$req .= "id_group int(11) unsigned NOT NULL default '0',";
	$req .= "PRIMARY KEY  (id),";
	$req .= "KEY id_object (id_object),";
	$req .= "KEY id_group (id_group)";
	$req .= ");";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_CAL_RES_ADD_GROUPS_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}

function upgrade559to560()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade560to561()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade561to562()
{
$ret = "";
$db = & $GLOBALS['babDB'];

return $ret;
}

function upgrade562to563()
{
$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_VAC_PLANNING_TBL."'"));
if ( $arr[0] != BAB_VAC_PLANNING_TBL )
	{
	$req = "CREATE TABLE `".BAB_VAC_PLANNING_TBL."` (
		  `id_entity` int(10) unsigned NOT NULL default '0',
		  `id_user` int(10) unsigned NOT NULL default '0',
		  KEY `id_user` (`id_user`)
		)";

	$res = $db->db_query($req);
	if( !$res)
		{
		$ret = "Creation of <b>".BAB_VAC_PLANNING_TBL."</b> table failed !<br>";
		return $ret;
		}
	}

return $ret;
}



function upgrade562to564()
{
$objDelegat = array(
	BAB_SECTIONS_TBL, 
	BAB_TOPICS_CATEGORIES_TBL,
	BAB_FLOW_APPROVERS_TBL,
	BAB_FORUMS_TBL,
	BAB_FAQCAT_TBL,
	BAB_FM_FOLDERS_TBL,
	BAB_LDAP_DIRECTORIES_TBL,
	BAB_DB_DIRECTORIES_TBL,
	BAB_ORG_CHARTS_TBL
	);


$ret = "";
$db = & $GLOBALS['babDB'];

$arr = $db->db_fetch_array($db->db_query("DESCRIBE ".BAB_DG_GROUPS_TBL." id_group"));
if ($arr[0] != 'id_group')
	{
	$db->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD `id_group` INT( 10 ) UNSIGNED");
	$db->db_query("ALTER TABLE `".BAB_DG_GROUPS_TBL."` ADD INDEX ( `id_group` )");

	$db->db_query("
		CREATE TABLE `".BAB_DG_ADMIN_TBL."` (
		`id_user` INT UNSIGNED NOT NULL ,
		`id_dg` INT UNSIGNED NOT NULL ,
		INDEX ( `id_user` )
		)");

	$res = $db->db_query("SELECT id, id_dggroup, name FROM ".BAB_GROUPS_TBL." WHERE id_dggroup>'0'");
	while ($arr = $db->db_fetch_array($res))
		{
		$current = $db->db_fetch_array($db->db_query("SELECT id_group FROM ".BAB_DG_GROUPS_TBL." WHERE id='".$arr['id_dggroup']."'"));
		if ($current['id_group'] == 0)
			{
			$db->db_query("UPDATE `".BAB_DG_GROUPS_TBL."` SET id_group='".$arr['id']."' WHERE id='".$arr['id_dggroup']."'");
			$id = $arr['id_dggroup'];
			}
		else
			{
			$db->db_query("INSERT INTO `".BAB_DG_GROUPS_TBL."` (name, description, groups, sections, articles, faqs, forums, calendars, mails, directories, approbations, filemanager, orgchart, id_group) VALUES (
				'".$current['name'].' - '.$arr['name']."', 
				'".$current['description']."',
				'".$current['groups']."',
				'".$current['sections']."', 
				'".$current['articles']."', 
				'".$current['faqs']."', 
				'".$current['forums']."', 
				'".$current['calendars']."', 
				'".$current['mails']."', 
				'".$current['directories']."', 
				'".$current['approbations']."', 
				'".$current['filemanager']."', 
				'".$current['orgchart']."', 
				'".$arr['id']."'
				)");

			$id = $db->db_insert_id();
			}

		$res = $db->db_query("SELECT id_object FROM ".BAB_DG_USERS_GROUPS_TBL." WHERE id_group='".$id."'");
		while ($row = $db->db_fetch_array($res))
			{
			$db->db_query("INSERT INTO ".BAB_DG_ADMIN_TBL." (id_user, id_dg) VALUES ('".$row['id_object']."','".$id."')");
			}

		$db->db_query("DROP table ".BAB_DG_USERS_GROUPS_TBL."");

		foreach($objDelegat as $table)
			{
			$db->db_query("UPDATE `".$table."` SET id_dgowner='".$id."' WHERE id_dgowner='".$arr['id']."'");
			}
		}

	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` DROP `id_dggroup`");
	$db->db_query("ALTER TABLE `".BAB_GROUPS_TBL."` DROP `id_dgowner`");
	$db->db_query("ALTER TABLE `".BAB_USERS_LOG_TBL."` CHANGE `id_dggroup` `id_dg` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL");

	}

$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_GROUPS_SET_TBL."'"));
if ( $arr[0] != BAB_GROUPS_SET_TBL )
	{
	$db->db_query("
			CREATE TABLE `".BAB_GROUPS_SET_TBL."` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `name` varchar(128) NOT NULL default '',
		  `description` varchar(255) NOT NULL default '',
		  `nb_groups` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		)");
	}


$arr = $db->db_fetch_array($db->db_query("SHOW TABLES LIKE '".BAB_GROUPS_SET_ASSOC_TBL."'"));
if ( $arr[0] != BAB_GROUPS_SET_ASSOC_TBL )
	{
	$db->db_query("
			CREATE TABLE `".BAB_GROUPS_SET_ASSOC_TBL."` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `id_group` int(10) unsigned NOT NULL default '0',
		  `id_set` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  KEY `id_group` (`id_group`,`id_set`)
		)");
	}

return $ret;
}

?>