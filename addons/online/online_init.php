<?php

function online_getAdminSectionMenus(&$url, &$text)
{
	static $i=0;
	if(!$i && !empty($GLOBALS['BAB_SESS_USERID']))
	{
		$url = $GLOBALS['babAddonUrl']."main";
		$text = bab_translate('Online');
		$i++;
		return true;
	}
}

function online_getUserSectionMenus(&$url, &$text)
{
//	static $nbMenus=0;
//	if( !$nbMenus && !empty($GLOBALS['BAB_SESS_USERID']))
//	{
//		$url = $GLOBALS['babAddonUrl']."main"";
//		$text = "Online";
//		$nbMenus++;
//		return true;
//	}
	return false;
}

function online_onUserCreate( $id )
{
}

function online_onUserDelete( $id )
{
	
}

function online_onSectionCreate( &$title, &$content)
{
	

		class session
			{
			
			var $db;
			var $logged;
			var $anonymous;
			var $Total;
			var $url;
			var $Admin;
			//Traduction
			var $ActiveSession;
			var $Logged;
			var $Anonymous;

		
			
			function session()
				{
				$this->db = $GLOBALS['babDB'];
				$this->res = $this->db->db_query("select * from bab_users_log where id_user > 0");
				$this->logged =$this->db->db_num_rows($this->res);

				$this->res1 = $this->db->db_query("select * from bab_users_log where id_user = 0");
				$this->anonymous =$this->db->db_num_rows($this->res1);
			
				$this->Total = $this->anonymous + $this->logged;
				$this->url = $GLOBALS['babAddonUrl']."main";
				$this->Admin = bab_isUserAdministrator();
				//Traduction
				$this->ActiveSession = bab_translate('Active Session');
				$this->Logged = bab_translate('Logged');
				$this->Anonymous = bab_translate('Anonymous');
				}
			}
	
		$session = new session();
		$content= bab_printTemplate( $session,$GLOBALS['babAddonHtmlPath']."main.html", "session");
		//Traduction
		$title = bab_translate('Online')." (".$session->Total.")";
		return true;


	
	
}


?>
