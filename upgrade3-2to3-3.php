<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.2 to 3.3 */
/************************************************************************
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

return $ret;
}
?>