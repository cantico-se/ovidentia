<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function getDomainName($id)
	{
	$db = new db_mysql();
	$query = "select * from mail_domains where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[name];
		}
	else
		{
		return "";
		}
	}

function domainModify($userid, $id, $bgrp)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("You must choose a valid domain !!");
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->inmailserver = babTranslate("Incoming mail server");
			$this->outmailserver = babTranslate("Outgoing mail server");
			$this->inportserver = babTranslate("Incoming mail server port");
			$this->outportserver = babTranslate("Outgoing mail server port");
			$this->accessmethod = babTranslate("Access method");
			$this->modify = babTranslate("Modify mail domain");
			$this->bgrp = $bgrp;
			$this->db = new db_mysql();
			$req = "select * from mail_domains where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( strtolower($this->arr[access]) == "pop3")
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
	$body->babecho(	babPrintTemplate($temp,"maildoms.html", "domainmodify"));
	}

function domainDelete($userid, $id, $bgrp)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete this mail domain");
			$this->title = getDomainName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the domain with all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=maildom&idx=del&domain=".$id."&action=Yes&userid=".$userid."&bgrp=".$bgrp;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=maildom&idx=modify&item=".$id."&userid=".$userid."&bgrp=".$bgrp;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($userid,$id, $bgrp);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}


function modifyDomain($bgrp, $userid, $oldname, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver, $id)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name !!");
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

	$db = new db_mysql();
	$query = "select * from mail_domains where id='$id'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("Mail domain doesn't exist");
		}
	else
		{
		$query = "update mail_domains set name='$name', description='$description', access='$accessmethod', inserver='$inmailserver', inport='$inportserver', outserver='$outmailserver', outport='$outportserver' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
	}

function confirmDeleteDomain($userid, $id, $bgrp)
	{
	$db = new db_mysql();

	// delete category
	$req = "delete from mail_domains where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
	}

/* main */
if( !isset($idx))
	$idx = "modify";

if( isset($modify) && $modify == "moddom")
	modifyDomain($bgrp, $userid, $oldname, $name, $description, $accessmethod, $inmailserver, $inportserver, $outmailserver, $outportserver, $item);

if( isset($action) && $action == "Yes")
	{
	confirmDeleteDomain($userid,$domain, $bgrp);
	}

$grpid = array();
if(!isset($userid))
	return;
if(  $userid == 0 )
	{
	if( !isUserAdministrator())
		{
		return;
		}
	}
else
	{
	$db = new db_mysql();
	$req = "select * from groups where manager='".$userid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		}
	else
		{
		$bgrp = "n";
		}
	}

switch($idx)
	{
	case "del":
		domainDelete($userid, $item, $bgrp);
		$body->title = babTranslate("Delete doamain mail");
		$body->addItemMenu("list", babTranslate("Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$body->addItemMenu("modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=maildom&idx=modify&item=".$item."&bgrp=".$bgrp);
		$body->addItemMenu("del", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=maildom&idx=del&item=".$item."&userid=".$userid."&bgrp=".$bgrp);
		break;
	case "modify":
	default:
		domainModify($userid, $item, $bgrp);
		$body->title = getDomainName($item) . " ". babTranslate("mail domain");
		$body->addItemMenu("list", babTranslate("Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$userid."&bgrp=".$bgrp);
		$body->addItemMenu("modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=maildom&idx=modify&item=".$item."&bgrp=".$bgrp);
		$body->addItemMenu("del", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=maildom&idx=del&item=".$item."&userid=".$userid."&bgrp=".$bgrp);
		break;
	}

$body->setCurrentItemMenu($idx);

?>