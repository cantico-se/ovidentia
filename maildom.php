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
require_once dirname(__FILE__).'/utilit/registerglobals.php';

function getDomainName($id)
	{
	global $babDB;
	$query = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
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

function domainModify($userid, $id, $bgrp)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("You must choose a valid domain !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $bgcolor;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $userid;

		function temp($userid, $id, $bgrp)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->inmailserver = bab_translate("Incoming mail server");
			$this->outmailserver = bab_translate("Outgoing mail server");
			$this->inportserver = bab_translate("Incoming mail server port");
			$this->outportserver = bab_translate("Outgoing mail server port");
			$this->accessmethod = bab_translate("Access method");
			$this->modify = bab_translate("Modify mail domain");
			$this->bgrp = $bgrp;
			$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($id)."'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			if( mb_strtolower($this->arr['access']) == "pop3")
				{
				$this->popselected = "selected";
				$this->imapselected = "";
				}
			else
				{
				$this->popselected = "";
				$this->imapselected = "selected";
				}
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id, $bgrp);
	$babBody->babecho(	bab_printTemplate($temp,"maildoms.html", "domainmodify"));
	}

function domainDelete($userid, $id, $bgrp)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($userid,$id, $bgrp)
			{
			$this->message = bab_translate("Are you sure you want to delete this mail domain");
			$this->title = getDomainName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the domain with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=maildom&idx=del&domain=".$id."&action=Yes&userid=".$userid."&bgrp=".$bgrp;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$id."&userid=".$userid."&bgrp=".$bgrp;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($userid,$id, $bgrp);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function modifyDomain($bgrp, $userid, $oldname, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver, $id)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name !!");
		return;
		}

	if( empty($inmailserver))
		{
		$babBody->msgerror = bab_translate("You must provide an incoming mail server"). " !!";
		return;
		}
/*
	if( empty($outmailserver))
		{
		$babBody->msgerror = bab_translate("You must provide an outgoing mail server"). " !!";
		return;
		}
*/
	if( empty($inportserver) || !is_numeric($inportserver))
		$inportserver = 110;

	if( empty($outportserver) || !is_numeric($outportserver))
		$outportserver = 25;

	$query = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($id)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("Mail domain doesn't exist");
		}
	else
		{
		$query = "UPDATE ".BAB_MAIL_DOMAINS_TBL." SET 
			name='".$babDB->db_escape_string($name)."', 
			description='".$babDB->db_escape_string($description)."', 
			access='".$babDB->db_escape_string($accessmethod)."', 
			inserver='".$babDB->db_escape_string($inmailserver)."', 
			inport='".$babDB->db_escape_string($inportserver)."', 
			outserver='".$babDB->db_escape_string($outmailserver)."', 
			outport='".$babDB->db_escape_string($outportserver)."' 
		where id='".$babDB->db_escape_string($id)."'";

		$babDB->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
	}

function confirmDeleteDomain($userid, $id, $bgrp)
	{
	global $babDB;

	// delete category
	$req = "delete from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
	}

/* main */
if( !isset($idx))
	$idx = "modify";

if(!isset($userid))
	return;
if(  $userid == 0 )
	{
	if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['mails'] != 'Y' )
		{
		$babBody->msgerror = bab_translate("Access denied");
		return;
		}
	}
else
	{
	$req = "select * from ".BAB_GROUPS_TBL." where manager='".$babDB->db_escape_string($userid)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		}
	else
		{
		$bgrp = "n";
		}
	}

if( isset($modify) && $modify == "moddom")
	{
	if (!isset($oldname))
		$oldname = '';

	if (!isset($outmailserver))
		$outmailserver = '';

		if (!isset($outportserver))
		$outportserver = '';

	modifyDomain($bgrp, $userid, $oldname, $dname, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver, $item);
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteDomain($userid,$domain, $bgrp);
	}

switch($idx)
	{
	case "del":
		domainDelete($userid, $item, $bgrp);
		$babBody->title = bab_translate("Delete doamain mail");
		$babBody->addItemMenu("list", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$item."&bgrp=".$bgrp);
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=maildom&idx=del&item=".$item."&userid=".$userid."&bgrp=".$bgrp);
		break;
	case "modify":
	default:
		domainModify($userid, $item, $bgrp);
		$babBody->title = getDomainName($item) . " ". bab_translate("mail domain");
		$babBody->addItemMenu("list", bab_translate("Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=maildom&idx=modify&item=".$item."&bgrp=".$bgrp);
		$babBody->addItemMenu("del", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=maildom&idx=del&item=".$item."&userid=".$userid."&bgrp=".$bgrp);
		break;
	}

$babBody->setCurrentItemMenu($idx);
 
?>