<?php
define("ADDON_WR_WORKSLIST_TBL", "wr_workslist");
define("ADDON_WR_WORKSTYPES_TBL", "wr_workstypes");
define("ADDON_WR_WORKSUSERS_GROUPS_TBL", "wr_worksusers_groups");
define("ADDON_WR_WORKSAGENTS_GROUPS_TBL", "wr_worksagents_groups");
define("ADDON_WR_WORKSOTHERS_GROUPS_TBL", "wr_worksothers_groups");
define("ADDON_WR_TASKSLIST_TBL", "wr_taskslist");

define("WR_WAITING", 0);
define("WR_PLANNED", 1);
define("WR_STARTED", 2);
define("WR_FINISHED", 3);
define("WR_PENDING", 4);
define("WR_CANCELED", 5);


define("WR_ACCESS_USER", 0x01);
define("WR_ACCESS_AGENT", 0x02);
define("WR_ACCESS_SUPER", 0x04);
define("WR_ACCESS_MAN", 0x08);


$wr_array_status = array("En attente","Plannifié","En cours","Terminé","Suspendu","Annulé");

function wr_translate($str)
	{
		return bab_translate($str, $GLOBALS['babAddonFolder']);
	}

function wr_getWorkName($id)
	{
	global $babDB;
	$res = $babDB->db_query("select name from ".ADDON_WR_WORKSLIST_TBL." where id='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

?>