<?php
include $babAddonPhpPath."owincl.php";

function noaccOW()
{

	global $babBody;
	class temp
		{

		function temp()
			{
			$this->content = ow_translate("Access denied");
			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "accessdenied");
}

function userConfigOW()
{

	global $babBody;
	class temp
		{

		function temp()
			{
			global $babBody;
			$this->loginname = ow_translate("Login name");
			$this->password = ow_translate("Password");
			$this->repassword = ow_translate("Re-type password");
			$this->popup = ow_translate("Open in new window ?");
			$this->yes = ow_translate("Yes");
			$this->no = ow_translate("No");
			$this->modify = ow_translate("Modify");
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select * from ".ADDON_OW_USERS_TBL." where  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$this->loginnameval = $arr['loginname'];
				if( $arr['popup'] == "Y" )
					{
					$this->popysel = "selected";
					$this->popnsel = "";
					}
				else
					{
					$this->popysel = "";
					$this->popnsel = "selected";
					}
				}
			else
				{
				$this->popysel = "selected";
				$this->popnsel = "";
				$this->loginnameval = "";
				}
			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "userconfig"));
}

function openPopupOW()
{
	global $babBody;
	class temp
		{

		function temp()
			{
			$db = $GLOBALS['babDB'];
			$res = $db->db_query("select *, DECODE(password, '".$GLOBALS['BAB_HASH_VAR']."') as owpass from ".ADDON_OW_USERS_TBL." where  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			if( !empty($GLOBALS['BAB_SESS_USERID']) && $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$arr2 = $db->db_fetch_array($db->db_query("select * from ".ADDON_OW_CONFIGURATION_TBL." where foption='owurl'"));
				$this->owurl = $arr2['fvalue']."openwebmail.pl?loginname=".$arr['loginname']."&password=".$arr['owpass'];
				}
			else
				{
				$this->owurl = $GLOBALS['babAddonUrl']."main&idx=noacc";
				}

			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "openpopup");
}

function openOW()
{
	global $babBody;
	class tempa
		{

		function tempa($loginname, $password, $popup, $owurl)
			{
				$this->popup = $popup;
				$this->mail = ow_translate("Mail");
				if( $popup == "Y")
					$this->owurl = $GLOBALS['babAddonUrl']."main&idx=openp";
				else
					$this->owurl = $owurl."openwebmail.pl?loginname=".$loginname."&password=".$password;
			}
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select *, DECODE(password, '".$GLOBALS['BAB_HASH_VAR']."') as owpass from ".ADDON_OW_USERS_TBL." where  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	if( $res && $db->db_num_rows($res) > 0 )
	{
		$arr = $db->db_fetch_array($res);
		$arr2 = $db->db_fetch_array($db->db_query("select * from ".ADDON_OW_CONFIGURATION_TBL." where foption='owurl'"));
		$temp = new tempa($arr['loginname'], $arr['owpass'], $arr['popup'], $arr2['fvalue']);
		$babBody->babecho( bab_printTemplate($temp,$GLOBALS['babAddonHtmlPath']."main.html", "openow"));
	}
	else
	{
		Header("Location: ". $GLOBALS['babAddonUrl']."main&idx=uconfc");
	}
}

function saveUserConfigOW($loginname, $password, $repassword, $popup )
{
	global $babBody;

	if( empty($loginname))
		{
		$babBody->msgerror = ow_translate("You must provide login name"). " !!";
		return false;
		}
	
	if( empty($password))
		{
		$babBody->msgerror = ow_translate("You must provide password"). " !!";
		return false;
		}

	if( $password != $repassword)
		{
		$babBody->msgerror = ow_translate("Passwords not match !!");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_user from ".ADDON_OW_USERS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	if( $res && $db->db_num_rows($res) > 0 )
	{
		$req = "update ".ADDON_OW_USERS_TBL." set loginname='".$loginname."', password=ENCODE('".$password."','".$GLOBALS['BAB_HASH_VAR']."'), popup='".$popup."' where id_user='".$GLOBALS['BAB_SESS_USERID']."'";
	}
	else
	{
	$req = "insert into ".ADDON_OW_USERS_TBL." (id_user, loginname, password, popup) values ";	
	$req .= "('".$GLOBALS['BAB_SESS_USERID']."', '".$loginname."', ENCODE('".$password."','".$GLOBALS['BAB_HASH_VAR']."'), '".$popup."')";	
	}
	$res = $db->db_query($req);
	return true;
}



if( isset($mod) && $mod == "ucmod")
	{
	if( saveUserConfigOW($loginname, $password, $repassword, $popup ))
		$idx = "open";
	}

switch($idx)
	{
	case "noacc":
		noaccOW();
		exit;
		break;
	case "openp":
		openPopupOW();
		exit();
		break;

	case "uconfc":
		$babBody->title = ow_translate("Mail configuration");
		userConfigOW();
		$babBody->addItemMenu("mail", ow_translate("Mail"), $GLOBALS['babAddonUrl']."main&idx=open");
		$babBody->addItemMenu("uconfc", ow_translate("Configuration"), $GLOBALS['babAddonUrl']."main&idx=uconfc");
		break;
	case "open":
	default:
		$babBody->title = "";
		openOW();
		$babBody->addItemMenu("mail", ow_translate("Mail"), $GLOBALS['babAddonUrl']."main&idx=open");
		$babBody->addItemMenu("uconfc", ow_translate("Configuration"), $GLOBALS['babAddonUrl']."main&idx=uconfc");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>