<?php

function nbronline()
	{
	global $babBody;
	class c_nbronline
		{
		
		var $nbrloggedsession;
		var $db;
		var $res;
		var $res1;
		var $ip;
		var $whoonline;
		var $anonyme_ip;
		var $private_ip;
		var $perso_ip;
		var $nbranonymoussession;
		var $Email;
		//Traduction
		var $LoggedSession;
		var $AnonymousSession;
		var $FirstnameLastname;
		var $PrivateIP;
		var $PrublicIP;
		var $NotAvailable;
				
		function c_nbronline()
			{
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from bab_users_log where id_user > 0");
			$this->nbrloggedsession = $this->db->db_num_rows($this->res);
			$this->res1 = $this->db->db_query("select * from bab_users_log where id_user = 0");
			$this->nbranonymoussession = $this->db->db_num_rows($this->res1);
			//Traduction
			$this->LoggedSession = bab_translate('Logged Session');
			$this->AnonymousSession = bab_translate('Anonymous Session');
			$this->FirstnameLastname = bab_translate('Firstname Lastname');
			$this->PrivateIP = bab_translate('Private IP');
			$this->PublicIP = bab_translate('Public IP');
			$this->NotAvailable = bab_translate('Not Available');
		}
		

		
		function getnext()
			{
			static $i = 0;
			if( $i < $this->nbrloggedsession)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->id = $arr['id_user'];
				$this->ip = $arr['remote_addr'];
				$this->perso_ip = $arr['forwarded_for'];
				$this->whoonline = bab_getUserName ($this->id);
				$this->Email = bab_getUserEmail ($this->id);
				$i++;
				return true;
				}
			else
				return false;
			}
		function getnext1()
			{
			static $i = 0;
			if( $i < $this->nbranonymoussession)
				{
				$arr = $this->db->db_fetch_array($this->res1);
				$this->anonyme_ip = $arr['remote_addr'];
				$this->private_ip = $arr['forwarded_for'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$c_nbronline = new c_nbronline();
	$babBody->babecho(	bab_printTemplate($c_nbronline,$GLOBALS['babAddonHtmlPath']."main.html", "nbronline"));
	}


// main

if ( !isset($idx))
		$idx ="online";

		
switch($idx)
{	
	
	case "online":
	default:
		$babBody->title = bab_translate('User\'s online');
		nbronline();
		$babBody->addItemMenu("online", bab_translate('Online'),  $GLOBALS['babAddonUrl']."main&idx=online");
		break;

}
$babBody->setCurrentItemMenu($idx);
		
?>