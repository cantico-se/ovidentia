<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.1 to 3.2 */
/************************************************************************
	- Delete reference section from section_states
	- Section activation
	- Added number of hits for downladed files
	- Remove files owned by a group or a user when we delete a group or a user
	- When we want to delete a faq category, we have a warning caused by /r in text translation.
	- Added access to groups from sections/topics/Faq/Forum list
	- After section creation, it's implicitly visible by Administrator groups members
	- Make more room to CT/CC/BCC/SUBJECT fileds in mail function
	- Access to thread is valid only if there is a confirmed post or user is moderator
	- Now section icon is in config.html
	- Added $CONTACT(firstname, lastname)
	- Changed event modification: now description is static text
	- Moved section bullet/icon to config.html
************************************************************************/
function upgrade()
{
$ret = "";
$db = new db_mysql();

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
?>