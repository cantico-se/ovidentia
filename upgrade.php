<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/************************************************************************
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

$req = "ALTER TABLE ".BAB_GROUPS_TBL." ADD filenotify ENUM('N','Y') NOT NULL AFTER moderate";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_COMMENTS_TBL."</b> table failed !<br>";
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

$ret = upgrade340betas($beta);
if( !empty($ret))
	return $ret;

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

		$db->db_query("insert into ".BAB_FM_FOLDERS_TBL." (id, folder, manager, idsa, filenotify, active) values ('".$row['id']."', '".$row['name']."', '".$row['manager']."', '".$idfa."', '".$row['filenotify']."', '".$row['gstorage']."')");
		$fid = $db->db_insert_id();

		$db->db_query("insert into ".BAB_FMDOWNLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");
		$db->db_query("insert into ".BAB_FMUPLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");

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
		$db->db_query("insert into ".BAB_FM_FOLDERS_TBL." (id, folder, manager, idsa, filenotify, active) values ('".$row['id']."', '".$row['name']."', '0', '0', '".$row['filenotify']."', '".$row['gstorage']."')");
		$fid = $db->db_insert_id();

		$db->db_query("insert into ".BAB_FMDOWNLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");
		$db->db_query("insert into ".BAB_FMUPLOAD_GROUPS_TBL." ( id_object, id_group) values ('".$fid."', '".$row['id']."')");

		$db->db_query("update ".BAB_FILES_TBL." set confirmed='Y' where bgroup='Y' and id_owner='".$row['id']."' and confirmed='N'");
		}
	}

$req = "ALTER TABLE ".BAB_GROUPS_TBL." DROP gstorage, DROP filenotify, DROP moderate";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_GROUPS_TBL."</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE ".BAB_TOPICS_TBL." ADD notify ENUM('N','Y') NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>".BAB_TOPICS_TBL."</b> table failed !<br>";
	return $ret;
	}

return $ret;
}

function upgrade341betas(&$beta)
{
$ret = "";
$db = $GLOBALS['babDB'];

/* 341beta -> 341 */
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
	$beta = "beta2";
	}
return $ret;
}
?>
