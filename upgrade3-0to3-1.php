<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.0 to 3.1 */
/************************************************************************
	- Section ordering
	- Open/Close section
	- Search
	- Add new articles, comments, posts to 
************************************************************************/
include "config.php";

function upgrade()
{
$ret = "";
$db = new db_mysql();

$req = "ALTER TABLE articles CHANGE body body LONGTEXT not null";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>articles</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE private_sections (";
$req .= "id smallint(6) unsigned NOT NULL auto_increment,";
$req .= "position enum('0','1') DEFAULT '0' NOT NULL,";
$req .= "title varchar(60),";
$req .= "description varchar(200),";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>private_sections</b> table failed !<br>";
	return $ret;
	}

$req = "INSERT INTO private_sections VALUES ('1', '0', 'Administration', 'This section is for Administration')";
$res = $db->db_query($req);

$req = "INSERT INTO private_sections VALUES ('2', '1', 'Month', 'This section lists month days')";
$res = $db->db_query($req);

$req = "INSERT INTO private_sections VALUES ('3', '0', 'Topics', 'This section lists topics')";
$res = $db->db_query($req);

$req = "INSERT INTO private_sections VALUES ('4', '0', 'Forums', 'This section lists forums')";
$res = $db->db_query($req);

$req = "INSERT INTO private_sections VALUES ('5', '1', 'User\'s section', 'This section is for User')";
$res = $db->db_query($req);


$req = "CREATE TABLE sections_order (";
$req .= "id smallint(6) unsigned NOT NULL auto_increment,";
$req .= "id_section smallint(6) unsigned NOT NULL,";
$req .= "position enum('0','1') DEFAULT '0' NOT NULL,";
$req .= "private enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "ordering smallint(6) unsigned NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>sections_order</b> table failed !<br>";
	return $ret;
	}

$req = "select * from private_sections where position='0' order by id asc";
$res = $db->db_query($req);

$left = 1;
$right = 1;
while( $arr = $db->db_fetch_array($res))
	{
	$req = "insert into sections_order ( id_section, position, private, ordering) values ('".$arr[id]."', '".$arr[position]."', 'Y', '".$left."')";
	$db->db_query($req);
	$left++;
	}

$req = "select * from sections where position='0' order by id asc";
$res = $db->db_query($req);

while( $arr = $db->db_fetch_array($res))
	{
	$req = "insert into sections_order ( id_section, position, private, ordering) values ('".$arr[id]."', '".$arr[position]."', 'N', '".$left."')";
	$db->db_query($req);
	$left++;
	}

$req = "select * from private_sections where position='1' order by id asc";
$res = $db->db_query($req);

while( $arr = $db->db_fetch_array($res))
	{
	$req = "insert into sections_order ( id_section, position, private, ordering) values ('".$arr[id]."', '".$arr[position]."', 'Y', '".$right."')";
	$db->db_query($req);
	$right++;
	}

$req = "select * from sections where position='1' order by id asc";
$res = $db->db_query($req);

while( $arr = $db->db_fetch_array($res))
	{
	$req = "insert into sections_order ( id_section, position, private, ordering) values ('".$arr[id]."', '".$arr[position]."', 'N', '".$right."')";
	$db->db_query($req);
	$right++;
	}

$req = "CREATE TABLE sections_states (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "id_section smallint(6) unsigned NOT NULL,";
$req .= "closed enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "private enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "id_user int(11) unsigned NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ");";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>sections_states</b> table failed !<br>";
	return $ret;
	}

return $ret;
}
?>