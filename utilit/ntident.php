<?
function NTuserLogin($nickname)
	{
	global $babBody;
	$sql="select * from ".BAB_USERS_TBL." where nickname='$nickname'";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$_SESSION['BAB_SESS_NTREGISTER'] = false;
		$GLOBALS['BAB_SESS_NTREGISTER'] = false;
		return false;
		} 
	else 
		{
		$arr = $db->db_fetch_array($result);
		if( $arr['disabled'] == '1')
			{
			$_SESSION['BAB_SESS_NTREGISTER'] = false;
			$GLOBALS['BAB_SESS_NTREGISTER'] = false;
			return false;
			}
		if ($arr['is_confirmed'] == '1')
			{
			if( isset($_SESSION))
				{
				$_SESSION['BAB_SESS_NTREGISTER'] = true;
				$_SESSION['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$_SESSION['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$_SESSION['BAB_SESS_EMAIL'] = $arr['email'];
				$_SESSION['BAB_SESS_USERID'] = $arr['id'];
				$_SESSION['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				
				$GLOBALS['BAB_SESS_NTREGISTER'] = true;
				$GLOBALS['BAB_SESS_NICKNAME'] = $_SESSION['BAB_SESS_NICKNAME'];
				$GLOBALS['BAB_SESS_USER'] = $_SESSION['BAB_SESS_USER'];
				$GLOBALS['BAB_SESS_EMAIL'] = $_SESSION['BAB_SESS_EMAIL'];
				$GLOBALS['BAB_SESS_USERID'] = $_SESSION['BAB_SESS_USERID'];
				$GLOBALS['BAB_SESS_HASHID'] = $_SESSION['BAB_SESS_HASHID'];
				}
			else
				{
				$GLOBALS['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$GLOBALS['BAB_SESS_EMAIL'] = $arr['email'];
				$GLOBALS['BAB_SESS_USERID'] = $arr['id'];
				$GLOBALS['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				}
			return true;
			}
		else
			{
			$_SESSION['BAB_SESS_NTREGISTER'] = false;
			$GLOBALS['BAB_SESS_NTREGISTER'] = false;
			return false;
			}
		}
	}

if (!session_is_registered('BAB_SESS_NTREGISTER'))
	{
	if (!isset($ntident)) setcookie("ntident","connexion");
	session_register("BAB_SESS_NTREGISTER");
	$GLOBALS['BAB_SESS_NTREGISTER'] = true;
	header("location:".$GLOBALS['babUrl'].$GLOBALS['babPhpSelf']);
	}

if (isset($NTidUser) && $_SESSION['BAB_SESS_NTREGISTER'] && $ntident == "connexion")
	{
	if (NTuserLogin($NTidUser))
		{
		$GLOBALS['BAB_SESS_NTREGISTER'] = false;
		setcookie("ntident","ok");
		$db = $GLOBALS['babDB'];
		$res=$db->db_query("select datelog from ".BAB_USERS_TBL." where id='".$BAB_SESS_USERID."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update ".BAB_USERS_TBL." set datelog=now(), lastlog='".$arr['datelog']."' where id='".$BAB_SESS_USERID."'");
			}

		$res=$db->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='0' and sessid='".session_id()."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$BAB_SESS_USERID."' where id='".$arr['id']."'");
			}
		}
	}



if( $GLOBALS['BAB_SESS_NTREGISTER'] && $ntident == "connexion" )
	{
	$babBody->script .= 
		'//-->
	</script>
	<script type="text/javascript">
	<!--
	var WshShell = 0;
	var strLoggedUser
	function loggedUser()
	{
		WshShell = new ActiveXObject("WScript.Network");
		query = \'\' + this.location;
		if ( query.indexOf(\'?\') != -1 && query.indexOf(\'NTidUser\') == -1)
			{window.location.href = query+"&NTidUser="+escape(WshShell.Username);}
		else if (query.indexOf(\'NTidUser\') == -1)
			{window.location.href = "'.$GLOBALS['babPhpSelf'].'?NTidUser="+escape(WshShell.Username);}
	}
	loggedUser();';
	}
?>