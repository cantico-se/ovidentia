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
	- Upload/Download
	- Search contacts
	- Search in Files
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

$req = "ALTER TABLE sites ADD skin CHAR(255) NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>sites</b> table failed !<br>";
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

$req = "ALTER TABLE groups ADD gstorage ENUM('N','Y') NOT NULL, ADD ustorage ENUM('N','Y') NOT NULL;";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>groups</b> table failed !<br>";
	return $ret;
	}

$req = "ALTER TABLE sections ADD jscript ENUM('N','Y') NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>sections</b> table failed !<br>";
	return $ret;
	}


$req = "CREATE TABLE files (";
$req .= " id int(11) unsigned NOT NULL auto_increment,";
$req .= " name varchar(255) NOT NULL default '',";
$req .= " description tinytext NOT NULL,";
$req .= " keywords tinytext NOT NULL,";
$req .= " path tinytext NOT NULL,";
$req .= " id_owner int(11) unsigned NOT NULL default '0',";
$req .= " bgroup enum('N','Y') NOT NULL default 'N',";
$req .= " link int(11) unsigned NOT NULL default '0',";
$req .= " readonly enum('N','Y') NOT NULL default 'N',";
$req .= " state char(1) NOT NULL default '',";
$req .= " created datetime default NULL,";
$req .= " author int(11) unsigned NOT NULL default '0',";
$req .= " modified datetime default NULL,";
$req .= " modifiedby int(11) unsigned NOT NULL default '0',";
$req .= " confirmed enum('N','Y') NOT NULL default 'N',";
$req .= " PRIMARY KEY  (id)";
$req .= " );";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>files</b> table failed !<br>";
	return $ret;
	}

$req .= "CREATE TABLE mime_types (";
$req .= " ext VARCHAR(10) NOT NULL, ";
$req .= " mimetype TINYTEXT NOT NULL,";
$req .= " PRIMARY KEY (ext)";
$req .= " ); ";

$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>mime_types</b> table failed !<br>";
	return $ret;
	}

$db->db_query("INSERT INTO mime_types VALUES ('ai', 'application/postscript')";
$db->db_query("INSERT INTO mime_types VALUES ('asc', 'text/plain')";
$db->db_query("INSERT INTO mime_types VALUES ('au', 'audio/basic')";
$db->db_query("INSERT INTO mime_types VALUES ('avi', 'video/x-msvideo')";
$db->db_query("INSERT INTO mime_types VALUES ('bin', 'application/octet-stream')";
$db->db_query("INSERT INTO mime_types VALUES ('bmp', 'image/bmp')";
$db->db_query("INSERT INTO mime_types VALUES ('class', 'application/octet-stream')";
$db->db_query("INSERT INTO mime_types VALUES ('css', 'text/css')";
$db->db_query("INSERT INTO mime_types VALUES ('doc', 'application/msword')";
$db->db_query("INSERT INTO mime_types VALUES ('dvi', 'application/x-dvi')";
$db->db_query("INSERT INTO mime_types VALUES ('exe', 'application/octet-stream')";
$db->db_query("INSERT INTO mime_types VALUES ('gif', 'image/gif')";
$db->db_query("INSERT INTO mime_types VALUES ('htm', 'text/html')";
$db->db_query("INSERT INTO mime_types VALUES ('html', 'text/html')";
$db->db_query("INSERT INTO mime_types VALUES ('jpe', 'image/jpeg')";
$db->db_query("INSERT INTO mime_types VALUES ('jpeg', 'image/jpeg')";
$db->db_query("INSERT INTO mime_types VALUES ('jpg', 'image/jpeg')";
$db->db_query("INSERT INTO mime_types VALUES ('js', 'application/x-javascript')";
$db->db_query("INSERT INTO mime_types VALUES ('mid', 'audio/midi')";
$db->db_query("INSERT INTO mime_types VALUES ('midi', 'audio/midi')";
$db->db_query("INSERT INTO mime_types VALUES ('mp3', 'audio/mpeg')";
$db->db_query("INSERT INTO mime_types VALUES ('mpeg', 'video/mpeg')";
$db->db_query("INSERT INTO mime_types VALUES ('png', 'image/png');
$db->db_query("INSERT INTO mime_types VALUES ('ppt', 'application/vnd.ms-powerpoint')";
$db->db_query("INSERT INTO mime_types VALUES ('ps', 'application/postscript')";
$db->db_query("INSERT INTO mime_types VALUES ('rtf', 'text/rtf')";
$db->db_query("INSERT INTO mime_types VALUES ('tar', 'application/x-tar')";
$db->db_query("INSERT INTO mime_types VALUES ('txt', 'text/plain')";
$db->db_query("INSERT INTO mime_types VALUES ('wav', 'audio/x-wav')";
$db->db_query("INSERT INTO mime_types VALUES ('xls', 'application/vnd.ms-excel')";
$db->db_query("INSERT INTO mime_types VALUES ('xml', 'text/xml')";
$db->db_query("INSERT INTO mime_types VALUES ('zip', 'application/zip')";

return $ret;
}
?>