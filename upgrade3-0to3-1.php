<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.0 to 3.1 */
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

return $ret;
}

?>