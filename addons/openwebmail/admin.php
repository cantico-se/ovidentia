<?php
include $babAddonPhpPath."owincl.php";
/* main */
if( !isset($idx ))
	$idx = "admcf";

if( isset($mod) && $mod == "owcfg")
{
	$db = $GLOBALS['babDB'];
	$db->db_query("update ".ADDON_OW_CONFIGURATION_TBL." set fvalue='".$owurl."' where foption='owurl'");
}

function configOW()
{
	global $babBody;
	class temp
		{

		function temp()
			{
			global $babBody;
			$this->owurltxt = ow_translate("Openwebmail url");
			$this->modify = ow_translate("Modify");
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".ADDON_OW_CONFIGURATION_TBL." where foption='owurl'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$this->owurl = $arr['fvalue'];
				}
			else
				{
				$this->owurl = "";
				}
			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "adminowconf"));
}

switch($idx)
	{
	case "admcf":
	default:
		$babBody->title = ow_translate("Openwebmail configuration");
		configOW();
		$babBody->addItemMenu("admcf", ow_translate("Configuration"), $GLOBALS['babAddonUrl']."admin&idx=admcf");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>