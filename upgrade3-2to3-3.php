<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
/* upgrade from 3.2 to 3.3 */
/************************************************************************
- support of addons
- Addded prefered style sheet
- Added $ARTICLE(topic, article)
- Added $FAQ(faq, question)
************************************************************************/
function upgrade()
{
$ret = "";
$db = $GLOBALS['babDB'];
/*
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
*/

$req = "ALTER TABLE users ADD style TEXT NOT NULL";
$res = $db->db_query($req);
if( !$res)
	{
	$ret = "Alteration of <b>users</b> table failed !<br>";
	return $ret;
	}

return $ret;
}
?>