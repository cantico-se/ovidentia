<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function domainCreate($userid, $grpid, $bgrp)
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $accessmethod;
		var $inmailserver;
		var $outmailserver;
		var $inportserver;
		var $outportserver;

		var $groupsname;
		var $idgrp;
		var $count;
		var $add;
		var $db;
		var $arrgroups = array();
		var $userid;

		function temp($userid, $grpid, $bgrp)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->groupsname = babTranslate("Groups");
			$this->inmailserver = babTranslate("Incoming mail server");
			$this->outmailserver = babTranslate("Outgoing mail server");
			$this->inportserver = babTranslate("Incoming mail server port");
			$this->outportserver = babTranslate("Outgoing mail server port");
			$this->accessmethod = babTranslate("Access method");
			$this->add = babTranslate("Add Domain");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->bgrp = $bgrp;
			$this->count = count($grpid);
			if( $this->count == 1 && $grpid[0] == 1)
				$this->count = 0;
			$this->db = new db_mysql();
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from groups where id='".$this->idgrp[$i]."'";
				$res = $this->db->db_query($req);
				$this->arrgroups = $this->db->db_fetch_array($res);
				if( $i == 0 )
					$this->arrgroups['select'] = "selected";
				else
					$this->arrgroups['select'] = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($userid, $grpid, $bgrp);
	$body->babecho(	babPrintTemplate($temp,"maildoms.html", "domaincreate"));
	}

function domainsList($userid, $grpid, $bgrp)
	{
	global $body;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $idgrp = array();
		var $group;
		var $groupname;
				
		var $arr = array();
		var $db;
		var $count;
		var $countadm;
		var $countgrp;
		var $countusr;
		var $resadm;
		var $resgrp;
		var $resusr;

		var $userid;

		function temp($grpid, $userid, $bgrp)
			{
			global $BAB_SESS_USERID;
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->group = babTranslate("Access");
			$this->idgrp = $grpid;
			$this->bgrp = $bgrp;
			$this->count = count($grpid);
			$this->db = new db_mysql();
			$this->userid = $userid;
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			if( $bgrp == "y" && $userid == 0)
				{
				$req = "select * from mail_domains where bgroup='Y' and owner='1'";
				$this->resadm = $this->db->db_query($req);
				$this->countadm = $this->db->db_num_rows($this->resadm);
				}
			else if( $bgrp == "y" && $userid != 0)
				{
				$req = "select * from mail_domains where bgroup='Y' and owner='1'";
				$this->resadm = $this->db->db_query($req);
				$this->countadm = $this->db->db_num_rows($this->resadm);

				$req = "select mail_domains.* from mail_domains join groups where bgroup='Y' and groups.manager='".$BAB_SESS_USERID."' and owner=groups.id";
				$this->resgrp = $this->db->db_query($req);
				$this->countgrp = $this->db->db_num_rows($this->resgrp);
				}
			else
				{
				$req = "select * from mail_domains where bgroup='Y' and owner='1'";
				$this->resadm = $this->db->db_query($req);
				$this->countadm = $this->db->db_num_rows($this->resadm);

				$req = "select mail_domains.* from mail_domains join users_groups where bgroup='Y' and users_groups.id_object='".$BAB_SESS_USERID."' and owner=users_groups.id_group";
				$this->resgrp = $this->db->db_query($req);
				$this->countgrp = $this->db->db_num_rows($this->resgrp);
				
				$req = "select * from mail_domains where owner='".$BAB_SESS_USERID."'";
				$this->resusr = $this->db->db_query($req);
				$this->countusr = $this->db->db_num_rows($this->resusr);
				}
			}
			
		function getnextadm()
			{
			global $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->countadm)
				{
				$this->arr = $this->db->db_fetch_array($this->resadm);
				if( $this->arr['owner'] == 1 && $this->arr['bgroup'] == "Y")
					$this->groupname = babTranslate("Registered users");
				else
					$this->groupname = "";
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['owner'])
						{
						$this->burl = 1;
						$this->url = $GLOBALS['babUrl']."index.php?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=y";
						break;
						}
					}

				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		function getnextgrp()
			{
			global $BAB_SESS_USERID;
			static $m = 0;
			if( $m < $this->countgrp)
				{
				$this->arr = $this->db->db_fetch_array($this->resgrp);
				if( $this->arr['owner'] != 1 && $this->arr['bgroup'] == "Y")
					$this->groupname = getGroupName($this->arr['owner']);
				else
					$this->groupname = "";
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['owner'])
						{
						$this->burl = 1;
						$this->url = $GLOBALS['babUrl']."index.php?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=y";
						break;
						}
					}
				$this->urlname = $this->arr['name'];
				$m++;
				return true;
				}
			else
				{
				$m = 0;
				return false;
				}

			}
		function getnextusr()
			{
			global $BAB_SESS_USERID;
			static $k = 0;
			if( $k < $this->countusr)
				{
				$this->arr = $this->db->db_fetch_array($this->resusr);
				$this->groupname = "";
				$this->burl = 0;
				if( $this->arr['owner'] == $BAB_SESS_USERID)
					{
					$this->burl = 1;
					$this->url = $GLOBALS['babUrl']."index.php?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=n";
					}

				$this->urlname = $this->arr['name'];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}

			}
		}

	$temp = new temp($grpid, $userid, $bgrp);
	$body->babecho(	babPrintTemplate($temp, "maildoms.html", "domainslist"));
	}


function addDomain($bgrp, $userid, $groups, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name"). " !!";
		return;
		}

	if( empty($inmailserver))
		{
		$body->msgerror = babTranslate("You must provide an incoming mail server"). " !!";
		return;
		}

	if( empty($outmailserver))
		{
		$body->msgerror = babTranslate("You must provide an outgoing mail server"). " !!";
		return;
		}

	if( empty($inportserver) || !is_numeric($inportserver))
		$inportserver = 110;

	if( empty($outportserver) || !is_numeric($outportserver))
		$outportserver = 25;

	$count = count ( $groups );
	if( $bgrp == "y")
		{
		if( $userid == 0 && $count == 0)
			{
			$groups = array();
			array_push($groups, 1);
			$count = 1;
			}

		if( $count == 0 )
			{
			$body->msgerror = babTranslate("You must select at least one group"). " !!";
			return;
			}
		$bgroup = "Y";
		}
	else
		{
		$bgroup = "N";
		$groups = array();
		array_push($groups, $userid);
		$count = 1;
		}
	$db = new db_mysql();

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from mail_domains where name='$name' and owner='".$groups[$i]."' and bgroup='".$bgroup."'";	
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$body->msgerror = babTranslate("ERROR: This domain already exists");
			}
		else
			{
			$query = "insert into mail_domains (name, description, access, inserver, inport, outserver, outport, bgroup, owner) VALUES ";
			$query .= "('" .$name. "', '" . $description. "', '" . $accessmethod. "', '" . $inmailserver. "', '" . $inportserver. "', '" . $outmailserver. "', '" . $outportserver. "', '". $bgroup. "', '" . $groups[$i]. "')";
			$db->db_query($query);
			}
		}
	}


/* main */
if( !isset($idx))
	$idx = "list";

if( isset($adddom) && $adddom == "add")
	addDomain($bgrp, $userid, $groups, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver);

$grpid = array();
if( !isset($userid))
	return;

if( $bgrp == "y")
{
	if( $userid == 0 )
		{
		if( !isUserAdministrator())
			{
			return;
			}
		array_push($grpid, 1);
		}
	else
		{
		$db = new db_mysql();
		$req = "select * from groups where manager='".$userid."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			while( $arr = $db->db_fetch_array($res))
				array_push($grpid, $arr['id']);
			}
		}
}

switch($idx)
	{
	case "create":
		domainCreate($userid, $grpid, $bgrp);
		if( $bgrp == "y")
		{
			if( $userid == 0)
				$body->title = babTranslate("Create a global mail domain");
			else
				$body->title = babTranslate("Create a group mail domain");
		}
		else
		{
			$body->title = babTranslate("Create a private mail domain");
		}
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS['babUrl']."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("list", babTranslate("Domains"), $GLOBALS['babUrl']."index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$body->addItemMenu("create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=maildoms&idx=create&userid=".$userid."&bgrp=".$bgrp);
		break;

	case "list":
	default:
		domainsList($userid, $grpid, $bgrp);
		if( $bgrp == "y")
		{
			if( $userid == 0)
				$body->title = babTranslate("Global domains list");
			else
				$body->title = babTranslate("Group domains list");
		}
		else
		{
			$body->title = babTranslate("Private domains list");
		}
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS['babUrl']."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("list", babTranslate("Domains"), $GLOBALS['babUrl']."index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$body->addItemMenu("create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=maildoms&idx=create&userid=".$userid."&bgrp=".$bgrp);
		break;
	}

$body->setCurrentItemMenu($idx);

?>