<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once 'base.php';

function domainCreate($userid, $grpid, $bgrp)
	{
	global $babBody;
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
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->groupsname = bab_translate("Groups");
			$this->inmailserver = bab_translate("Incoming mail server");
			$this->outmailserver = bab_translate("Outgoing mail server");
			$this->inportserver = bab_translate("Incoming mail server port");
			$this->outportserver = bab_translate("Outgoing mail server port");
			$this->accessmethod = bab_translate("Access method");
			$this->add = bab_translate("Add Domain");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->bgrp = $bgrp;
			$this->count = count($grpid);
			if( $this->count == 1 && $grpid[0] == 1)
				$this->count = 0;
			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id='".$babDB->db_escape_string($this->idgrp[$i])."'";
				$res = $babDB->db_query($req);
				$this->arrgroups = $babDB->db_fetch_array($res);
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
	$babBody->babecho(	bab_printTemplate($temp,"maildoms.html", "domaincreate"));
	}

function domainsList($userid, $grpid, $bgrp)
	{
	global $babBody;
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
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->group = bab_translate("Access");
			$this->idgrp = $grpid;
			$this->bgrp = $bgrp;
			$this->count = count($grpid);
			$this->userid = $userid;
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			if( $bgrp == "y" && $userid == 0)
				{
				if( $babBody->currentAdmGroup == 0 )
					$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."'";
				else
					$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
				$this->resadm = $babDB->db_query($req);
				$this->countadm = $babDB->db_num_rows($this->resadm);
				}
			else if( $bgrp == "y" && $userid != 0)
				{
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
				$this->resadm = $babDB->db_query($req);
				$this->countadm = $babDB->db_num_rows($this->resadm);
			
				$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_GROUPS_TBL." where bgroup='Y' and ".BAB_GROUPS_TBL.".manager='".$babDB->db_escape_string($BAB_SESS_USERID)."' and owner=".BAB_GROUPS_TBL.".id";
				$this->resgrp = $babDB->db_query($req);
				$this->countgrp = $babDB->db_num_rows($this->resgrp);
				}
			else
				{
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
				$this->resadm = $babDB->db_query($req);
				$this->countadm = $babDB->db_num_rows($this->resadm);
				
				$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_USERS_GROUPS_TBL." where bgroup='Y' and ".BAB_USERS_GROUPS_TBL.".id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and owner=".BAB_USERS_GROUPS_TBL.".id_group";
				$this->resgrp = $babDB->db_query($req);
				$this->countgrp = $babDB->db_num_rows($this->resgrp);
				
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
				$this->resusr = $babDB->db_query($req);
				$this->countusr = $babDB->db_num_rows($this->resusr);
				}
			}
			
		function getnextadm()
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->countadm)
				{
				$this->arr = $babDB->db_fetch_array($this->resadm);
				if( $this->arr['owner'] == 1 && $this->arr['bgroup'] == "Y")
					{
					if( $this->arr['id_dgowner'] == 0 )
						$this->groupname = bab_translate("Registered users");
					else
						$this->groupname = bab_getGroupName($this->arr['id_dgowner']);
					}
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['owner'] && $this->arr['id_dgowner'] == $babBody->currentAdmGroup)
						{
						$this->burl = 1;
						$this->url = $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=y";
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
			global $BAB_SESS_USERID, $babDB;
			static $m = 0;
			if( $m < $this->countgrp)
				{
				$this->arr = $babDB->db_fetch_array($this->resgrp);
				if( $this->arr['owner'] != 1 && $this->arr['bgroup'] == "Y")
					$this->groupname = bab_getGroupName($this->arr['owner']);
				else
					$this->groupname = "";
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['owner'])
						{
						$this->burl = 1;
						$this->url = $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=y";
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
			global $BAB_SESS_USERID, $babDB;
			static $k = 0;
			if( $k < $this->countusr)
				{
				$this->arr = $babDB->db_fetch_array($this->resusr);
				$this->groupname = "";
				$this->burl = 0;
				if( $this->arr['owner'] == $BAB_SESS_USERID)
					{
					$this->burl = 1;
					$this->url = $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$this->arr['id']."&userid=".$this->userid."&bgrp=n";
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
	$babBody->babecho(	bab_printTemplate($temp, "maildoms.html", "domainslist"));
	}


function addDomain($bgrp, $userid, $groups, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name"). " !!";
		return;
		}

	if( empty($inmailserver))
		{
		$babBody->msgerror = bab_translate("You must provide an incoming mail server"). " !!";
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
			$babBody->msgerror = bab_translate("You must select at least one group"). " !!";
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

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from ".BAB_MAIL_DOMAINS_TBL." where 
			name='".$babDB->db_escape_string($name)."' 
			and owner='".$babDB->db_escape_string($groups[$i])."' 
			and bgroup='".$babDB->db_escape_string($bgroup)."'";
			
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("ERROR: This domain already exists");
			}
		else
			{
			$iddgowner = 0;
			if( $groups[$i] == 1 )
				$iddgowner = $babBody->currentAdmGroup;
			$query = "INSERT into ".BAB_MAIL_DOMAINS_TBL." 
			(name, description, access, inserver, inport, outserver, outport, bgroup, owner, id_dgowner) 
				VALUES ";

			$query .= "(
				'" . $babDB->db_escape_string($name). "', 
				'" . $babDB->db_escape_string($description). "', 
				'" . $babDB->db_escape_string($accessmethod). "', 
				'" . $babDB->db_escape_string($inmailserver). "', 
				'" . $babDB->db_escape_string($inportserver). "', 
				'" . $babDB->db_escape_string($outmailserver). "', 
				'" . $babDB->db_escape_string($outportserver). "', 
				'" . $babDB->db_escape_string($bgroup). "', 
				'" . $babDB->db_escape_string($groups[$i]). "', 
				'" . $babDB->db_escape_string($iddgowner). "'
				)";

			$babDB->db_query($query);
			}
		}
	}


/* main */
if( !isset($idx))
	$idx = "list";

$grpid = array();
if( !isset($userid))
	return;

if( $bgrp == "y")
{
	if( $userid == 0 )
		{
		if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['mails'] != 'Y' )
			{
			$babBody->msgerror = bab_translate("Access denied");
			return;
			}
		bab_siteMap::setPosition('bab','AdminMail');
		array_push($grpid, 1);
		}
	else
		{
		$req = "select * from ".BAB_GROUPS_TBL." where manager='".$babDB->db_escape_string($userid)."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			while( $arr = $babDB->db_fetch_array($res))
				array_push($grpid, $arr['id']);
			}
		}

	if( count($grpid) == 0 )
		{
		$babBody->msgerror = bab_translate("Access denied");
		return;
		}
}

if( isset($adddom) && $adddom == "add")
	{
	if (!isset($groups))
		$groups = array();
	
	if (!isset($outmailserver))
		$outmailserver='';

	if (!isset($outportserver))
		$outportserver='';

	addDomain($bgrp, $userid, $groups, $dname, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver);
	}

switch($idx)
	{
	case "create":
		domainCreate($userid, $grpid, $bgrp);
		if( $bgrp == "y")
		{
			if( $userid == 0)
			$babBody->title = bab_translate("Create a global mail domain");
			else
				$babBody->title = bab_translate("Create a group mail domain");
		}
		else
		{
			$babBody->title = bab_translate("Create a private mail domain");
		}
		if( $bgrp != "y")
			$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("list", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=create&userid=".$userid."&bgrp=".$bgrp);
		break;

	case "list":
	default:
		domainsList($userid, $grpid, $bgrp);
		if( $bgrp == "y")
		{
			if( $userid == 0)
			$babBody->title = bab_translate("Global domains list");
			else
				$babBody->title = bab_translate("Group domains list");
		}
		else
		{
			$babBody->title = bab_translate("Available domains list");
		}
		if( $bgrp != "y")
			$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("list", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$babBody->addItemMenu("create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=create&userid=".$userid."&bgrp=".$bgrp);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>
