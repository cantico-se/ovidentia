<?php
define("ADDON_DIRECTORIES_TBL", "ad_directories");
define("ADDON_FIELDS_TBL", "ad_fields");
define("ADDON_DIRECTORIES_FIELDS_TBL", "ad_directories_fields");
define("ADDON_DBENTRIES_TBL", "ad_dbentries");
define("ADDON_DIRVIEW_GROUPS_TBL", "ad_dirview_groups");
define("ADDON_DIRUPDATE_GROUPS_TBL", "ad_dirupdate_groups");
define("ADDON_DIRADD_GROUPS_TBL", "ad_diradd_groups");

function getDirectoryName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select name from ".ADDON_DIRECTORIES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function ad_translate($str)
	{
		return bab_translate($str, $GLOBALS['babAddonFolder']);
	}
?>