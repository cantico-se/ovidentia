<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 2.2 to 2.3 */

function updateCSS()
{
	$filename = "config.php";

	$file = @fopen($filename, "r");
	$txt = fread($file, filesize($filename));
	fclose($file);
	$reg = "/babStyle\s*=\s*\"([^ ]*)\"/s";
	$res = preg_match($reg, $txt, $match);

	if( $res)
	{
	$filecss = "styles/".$match[1];
	$file = fopen($filecss, "a");

$out = <<<EOD
.BabMonthDayBgnd {
	BACKGROUND-COLOR: #BBBBBB; COLOR: black
}

.BabActifMonthDayBgnd {
	BACKGROUND-COLOR: #FFCC99; COLOR: black
}

.BabCurrentMonthDayBgnd {
	BACKGROUND-COLOR: #FFFFFF; COLOR: black
}
EOD;
	fputs($file, $out);
	fclose($file);
	}
}

function upgrade()
{
$ret = "";

$db = new db_mysql();

$req = "ALTER TABLE groups ADD manager INT (11) UNSIGNED not null AFTER vacation";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>groups</b> table failed !<br>";
	return $ret;
	}
$req = "ALTER TABLE users_log ADD lastlog DATETIME not null AFTER datelog";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>users_log</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE categoriescal (";
$req .= "id TINYINT (2) UNSIGNED not null AUTO_INCREMENT,";
$req .= "name VARCHAR (60) not null,";
$req .= "description VARCHAR (255) not null,";
$req .= "bgcolor VARCHAR (6) not null,";
$req .= "id_group INT (11) UNSIGNED not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>categoriescal</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE resourcescal (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "name VARCHAR (60) not null,";
$req .= "description VARCHAR (255) not null,";
$req .= "id_group INT (11) UNSIGNED not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")"; 
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>resourcescal</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE cal_events (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "id_cal INT (11) UNSIGNED not null,";
$req .= "title VARCHAR (255) not null,";
$req .= "description TEXT not null,";
$req .= "start_date DATE not null,";
$req .= "start_time TIME not null,";
$req .= "end_date DATE not null,";
$req .= "end_time TIME not null,";
$req .= "id_cat INT (11) UNSIGNED not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>cal_events</b> table failed !<br>";
	return $ret;
	}

$req = "CREATE TABLE calendar (";
$req .= "id int(11) unsigned NOT NULL auto_increment,";
$req .= "owner int(11) unsigned DEFAULT '0' NOT NULL,";
$req .= "actif enum('Y','N') DEFAULT 'Y' NOT NULL,";
$req .= "type TINYINT (2) not null,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>calendar</b> table failed !<br>";
	return $ret;
	}

/*
$req = "INSERT INTO calendar VALUES ( '1', '1', 'Y', '1')";
$res = $db->db_query($req);
$req = "INSERT INTO calendar VALUES ( '2', '1', 'Y', '2')";
$res = $db->db_query($req);
$req = "INSERT INTO calendar VALUES ( '3', '2', 'N', '2')";
$res = $db->db_query($req);
$req = "INSERT INTO calendar VALUES ( '4', '3', 'Y', '2')";
$res = $db->db_query($req);
*/

$req = "CREATE TABLE calaccess_users (";
$req .= "id INT (11) UNSIGNED not null AUTO_INCREMENT,";
$req .= "id_cal INT (11) UNSIGNED not null,";
$req .= "id_user INT (11) UNSIGNED not null,";
$req .= "bwrite enum('N','Y') DEFAULT 'N' NOT NULL,";
$req .= "PRIMARY KEY (id)";
$req .= ")";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Creation of <b>calaccess_users</b> table failed !<br>";
	return $ret;
	}

$req = "select * from users";
$res1 = $db->db_query($req);
while( $arr = $db->db_fetch_array($res1))
	{
	$req = "insert into calendar (owner, actif, type) values ('".$arr[id]."', 'Y', '1')";
	$res = $db->db_query($req);
	}

$req = "select * from groups";
$res1 = $db->db_query($req);
while( $arr = $db->db_fetch_array($res1))
	{
	if( $arr[id] == 2)
		$req = "insert into calendar (owner, actif, type) values ('".$arr[id]."', 'N', '2')";
	else
		$req = "insert into calendar (owner, actif, type) values ('".$arr[id]."', 'Y', '2')";
	$res = $db->db_query($req);
	}

updateCSS();
return $ret;
}

?>