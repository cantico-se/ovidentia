<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.1 to 3.2 */
/************************************************************************
	- Section activation
	- Added number of hits for downladed files
	- Remove files owned by a group or a user when we delete a group or a user
	- When we want to delete a faq category, we have a warning caused by /r in text translation.
************************************************************************/
include "config.php";

function upgrade()
{
$ret = "";
$db = new db_mysql();

$req = "AALTER TABLE files ADD hits INT(11) UNSIGNED NOT NULL";
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

return $ret;
}
?>