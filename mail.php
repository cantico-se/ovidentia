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
include_once $babInstallPath.'utilit/mailincl.php';

function getContactId( $name )
	{
	global $babDB;
	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($name, $replace)));
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$babDB->db_escape_string($hash)."'";	
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}

function addAddress( $val, $to, &$adarr)
{
	global $babDB;
	if( !empty($val))
	{
		$tmp = explode(",", $val);
		for($i=0; $i<count($tmp); $i++)
		{
			$addr = trim($tmp[$i]);
			if( eregi("(.*@.*\..*)", $addr, $res))
			{
				$adarr[$to][] = array($addr,$addr);
			}
			else if( strtolower(substr($addr, -3)) == "(g)")
			{
				$id = bab_getUserId(substr($addr, 0, -3));
				if( $id < 1) // it's a group
				{
					$idgrp = bab_isMemberOfGroup(substr($addr, 0, -3));
					if( $idgrp > 0 )
					{
					$req = "select p1.firstname, p1.lastname, p1.email from ".BAB_USERS_TBL." as p1, ".BAB_USERS_GROUPS_TBL." as p2 where p2.id_group='".$babDB->db_escape_string($idgrp)."' and p1.id=p2.id_object";
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0)
						{
						while( $arr = $babDB->db_fetch_array($res))
							{
							$adarr[$to][] = array($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
							}
						}
					}
				}
				else // it's user
				{
					$req = "select * from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'";
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0)
						{
						$arr = $babDB->db_fetch_array($res);
						$adarr[$to][] = array($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
						}
				}
			}
			else
			{
				$id = getContactId($addr);
				if( $id < 1) // it's a ditribution list
				{
				}
				else // it's contact
				{
					$req = "select * from ".BAB_CONTACTS_TBL." where id='".$babDB->db_escape_string($id)."'";
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0)
						{
						$arr = $babDB->db_fetch_array($res);
						$adarr[$to][] = array($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
						}
				}
			}
		}
	}
}


function composeMail($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid)
	{
	global $babBody;

	class temp
		{
		var $accid;
		var $criteria;
		var $reverse;
		var $send;
		var $cancel;
		var $from;
		var $to;
		var $cc;
		var $bcc;
		var $message;
		var $attachments;
		var $fromval;
		var $db;
		var $toval;
		var $ccval;
		var $bccval;
		var $subjectval;
		var $messageval;
		var $format;
		var $plain;
		var $html;
		var $htmlselect;
		var $plainselect;
		var $selectsig;
        var $file1;
        var $file2;
        var $file3;
		var $countcl;
		var $rescl;
		var $none;
		var $urlto;
		var $bhtml;
		var $msgerror;


		function temp($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid)
			{
			global $babDB, $BAB_SESS_USERID,$BAB_SESS_USER,$BAB_SESS_EMAIL;
			$this->psigid = $psigid;
			$this->toval = "";
			if( !empty($pto)) {
				if (is_array($pto)) {
					$pto = implode(',', array_unique($pto));
				}
				$this->toval = $pto;
			}
			$this->ccval = "";
			if( !empty($pcc)) {
				if (is_array($pcc)) {
					$pcc = implode(',', array_unique($pcc));
				}
				$this->ccval = $pcc;
			}
			$this->bccval = "";
			if( !empty($pbcc)) {
				if (is_array($pbcc)) {
					$pbcc = implode(',', array_unique($pbcc));
				}
				$this->bccval = $pbcc;
			}
			$this->subjectval = "";
			if( !empty($psubject))
				$this->subjectval = $psubject;
			$this->messageval = "";
			if( !empty($pmsg))
				{
				$this->messageval = $pmsg;
				
				}
			$this->file1 = "";
			if( !empty($pfiles[0]))
				$this->file1 = $pfiles[0];
			$this->file2 = "";
			if( !empty($pfiles[1]))
				$this->file2 = $pfiles[1];
			$this->file3 = "";
			if( !empty($pfiles[2]))
				$this->file3 = $pfiles[2];
			$this->accid = $accid;
			$this->criteria = 'SORTARRIVAL';
			if( !empty($criteria))
				$this->criteria = $criteria;
			$this->reverse = 1;
			if( !empty($reverse))
				$this->reverse = $reverse;
			$this->send = bab_translate("Send");
			$this->cancel = bab_translate("Cancel");
			$this->from = bab_translate("From");
			$this->to = bab_translate("To");
			$this->cc = bab_translate("Cc");
			$this->bcc = bab_translate("Bcc");
			$this->subject = bab_translate("Subject");
			$this->attachments = bab_translate("Attachments");
			$this->format = bab_translate("Format");
			$this->plain = bab_translate("Plain text");
			$this->html = bab_translate("Html");
            $this->selectsig = "-- ".bab_translate("Select signature"). " --";
			$this->none = "-- ".bab_translate("Select destinataire"). " --";
			$this->urlto = $GLOBALS['babUrlScript']."?tg=address&idx=list";
			

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			

			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and id='".$babDB->db_escape_string($accid)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->fromval = "\"".$arr['name']."\" &lt;".$arr['email']."&gt;";

				// si format dans le compte est a html, on utiliser le WYSIWYG
				
				if ('html' === $arr['format']) {
					$pformat = 'html';
					  
					$editor = new bab_contentEditor('bab_mail_message');
					$postedcontent = $editor->getContent();
					
					if (empty($postedcontent)) {
						$editor->setContent($this->messageval);
					}
					$editor->setFormat('html');
					$this->editor = $editor->getEditor();
				} else {
				
					$this->editor =false;
				
					if( $pformat == "plain")
						{
						$this->plainselect = "selected";
						$this->htmlselect = "";
					}
					else
						{
						$this->htmlselect = "selected";
						$this->plainselect = "";
					}
				}

                  
                $req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
                $this->ressig = $babDB->db_query($req);
                $this->countsig = $babDB->db_num_rows($this->ressig);
				}
			else
				{
				$this->fromval = "\"".$BAB_SESS_USER."\" &lt;".$BAB_SESS_EMAIL."&gt;";
				}
			
            if( $psigid == 0 || empty($psigid)) 
                $this->defaultselected = "selected";
            else
                $this->defaultselected = "";
			$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' order by lastname asc";
			$this->rescl = $babDB->db_query($req);
			$this->countcl = $babDB->db_num_rows($this->rescl);
			}

        function getnextsig()
            {
			global $babDB;
			static $i = 0;
			if( $i < $this->countsig)
				{
				$arr = $babDB->db_fetch_array($this->ressig);
                $this->signame = $arr['name'];
                if($arr['html'] == "Y")
                    $this->signame = $arr['name'] . " ( html )";
                $this->sigid = $arr['id'];
				if( $this->sigid == $this->psigid )
					$this->sigselected = "selected";
				else
					$this->sigselected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

            }

		}
	
	$temp = new temp($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid);
	$babBody->babPopup(	bab_printTemplate($temp,"mail.html", "mailcompose"));
	}

function createMail($accid, $to, $cc, $bcc, $subject, $criteria, $reverse, $format, $sigid)
	{

	global $babBody, $babDB, $BAB_SESS_USERID;
	if( empty($to))
		{
		$babBody->msgerror = bab_translate("You must fill to field !!");
		return false;
		}
	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("You must fill subject field !!");
		return false;
		}
		
	include_once $GLOBALS['babInstallPath']."utilit/inboxincl.php";	
	$account = bab_getMailAccount($accid);
		
		
	if( $account['format'] === 'html' )
		{
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";	
		$editor = new bab_contentEditor('bab_mail_message');
		$message = $editor->getContent();
		$format = 'html';
		}
	else
		{
		$message = bab_pp('message');
		
		}
		
		
		
	if( empty($message))
		{
		$babBody->msgerror = bab_translate("You must fill message field !!");
		return false;
		}

	$mail = bab_mail();
	if( $mail == false )
		{
		$babBody->msgerror = bab_translate("Sending error( Mail sending disabled )");
		return false;
		}

	$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and id='".$babDB->db_escape_string($accid)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$adarr = array();
		$adarr['to'] = array();
		$adarr['cc'] = array();
		$adarr['bcc'] = array();
		$arr = $babDB->db_fetch_array($res);
		addAddress($to, "to", $adarr);
		if(!empty($cc))
			{
			addAddress($cc, "cc", $adarr);
			}

		if(!empty($bcc))
			{
			addAddress($bcc, "bcc", $adarr);
			}

		$mail->mailFrom($arr['email'], $arr['name']);


		if( $sigid != 0)
			{
			$req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where id='".$babDB->db_escape_string($sigid)."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				{
				$arr = $babDB->db_fetch_array($res);
				$message .= $arr['text'];
				}

			}
		$mail->mailBody($message, $format);
		$mail->mailSubject($subject);

		for($i=0; $i < count($_FILES['files']['name']); $i++)
			{
			if( !empty($_FILES['files']['name'][$i]) && $_FILES['files']['name'][$i] !== 'none' )
				{
				$mail->mailFileAttach($_FILES['files']['tmp_name'][$i], $_FILES['files']['name'][$i], $_FILES['files']['type'][$i]);
				}
			}

		$nto = count($adarr['to']);
		$ncc = count($adarr['cc']);
		$nbcc = count($adarr['bcc']);


		$countto = 0;
		$countcc = 0;
		$countbcc = 0;
		while( $nto || $ncc || $nbcc )
			{

			if( $nto > 0 )
				{
				$mail->mailTo($adarr['to'][$nto-1][0], $adarr['to'][$nto-1][1]);
				$nto--;
				$countto++;
				}

			if( $ncc > 0 )
				{
				$mail->mailCc($adarr['cc'][$ncc-1][0], $adarr['cc'][$ncc-1][1]);
				$ncc--;
				$countcc++;
				}

			if( $nbcc > 0 )
				{
				$mail->mailBcc($adarr['bcc'][$nbcc-1][0], $adarr['bcc'][$nbcc-1][1]);
				$nbcc--;
				$countbcc++;
				}

			if( $countto == 25 || $countcc == 25 || $countbcc == 25)
				{
				if(!$mail->send())
					{
					$babBody->msgerror = bab_translate("Error occured when sending email !!");
					return false;
					}
				$mail->clearBcc();
				$mail->clearTo();
				$mail->clearCc();
				$countto = 0;
				$countcc = 0;
				$countbcc = 0;
				}

			}

		if( $countto || $countcc || $countbcc )
			{
			if(!$mail->send())
				{
				$babBody->msgerror = bab_translate("Error occured when sending email !!");
				return false;
				}
			$mail->clearBcc();
			$mail->clearTo();
			$mail->clearCc();
			$countto = 0;
			$countcc = 0;
			$countbcc = 0;
			}

		return true;
		}
	else
		{
		$babBody->msgerror = bab_translate("Invalid mail account !!");
		return false;
		}
	}


function mailReply($accid, $criteria, $reverse, $idreply, $all, $fw)
    {
	include_once $GLOBALS['babInstallPath'].'utilit/inboxincl.php';
    $CRLF = "\r\n";

	$mbox = bab_getMailBox($accid);
	if($mbox)
		{
		$idreply = imap_msgno($mbox, $idreply);
		$headinfo = imap_header($mbox, $idreply);
		$arr = $headinfo->from;
		$toval = "";
		$fromorg = '';
		for($i=0; $i < count($arr); $i++)
			{
			if( isset($arr[$i]->personal))
				{
				$mhc = imap_mime_header_decode($arr[$i]->personal);
				$fromorg .= $mhc[0]->text;
				}
			$fromorg .= " [" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "] ";

			if( $fw != 1)
				$toval .= $arr[$i]->mailbox . "@" . $arr[$i]->host.", ";
			}
		$toorg = "";
		if( $fw != 1)
			{
			$arr = $headinfo->to;
			for($i=0; $i < count($arr); $i++)
				{
				if (isset($arr[$i]->personal))
					{
					$mhc =  imap_mime_header_decode($arr[$i]->personal);
					$toorg .= $mhc[0]->text." ";
					}
				$toorg .= "[".$arr[$i]->mailbox . "@" . $arr[$i]->host."] ";

				if( $all == 1)
					$toval .= $arr[$i]->mailbox . "@" . $arr[$i]->host.", ";
				}

			$arr = isset($headinfo->cc) ? $headinfo->cc : array();
			$ccorg = "";
			$ccval = "";
			for($i=0; $i < count($arr); $i++)
				{
				if( isset($arr[$i]->personal))
					{
					$mhc = imap_mime_header_decode($arr[$i]->personal);
					$ccorg .= $mhc[0]->text . " ";
					}
				$ccorg .= "[".$arr[$i]->mailbox . "@" . $arr[$i]->host."] ";

				if( $all == 1)
					$ccval .= $arr[$i]->mailbox . "@" . $arr[$i]->host.", ";
				}
			$re = "RE:";
			}
		else
			$re = "FW:";

		$mhc = imap_mime_header_decode($headinfo->subject);
		if(empty($mhc[0]->text))
			$subjectval = $re;
		else
			$subjectval = $re." ".$mhc[0]->text;

		$msgbody = bab_getMimePart($mbox, $idreply, "TEXT/HTML");
		if(!$msgbody)
			{
			$format = "plain";
			$msgbody = bab_getMimePart($mbox, $idreply, "TEXT/PLAIN");
			$msgbody = eregi_replace( "((http|https|mailto|ftp):(\/\/)?[^[:space:]<>]{1,})", "<a href='\\1'>\\1</a>",$msgbody); 
			}
		else
			{
			$format = "html";
			$msgbody = eregi_replace("(src|background)=(['\"])cid:([^'\">]*)(['\"])", "src=\\2".$GLOBALS['babPhpSelf']."?tg=inbox&accid=".$accid."&idx=getpart&msg=$idreply&cid=\\3\\4", $msgbody);
			}
		$messageval = $CRLF.$CRLF.$CRLF.$CRLF."------".bab_translate("Original Message")."------".$CRLF;
		$messageval .= "From: ".$fromorg.$CRLF;
		$messageval .= "Sent: ".bab_strftime($headinfo->udate).$CRLF;
		$messageval .= "To: ".$toorg.$CRLF;
		if( !empty($ccorg))
			$messageval .= "Cc: ".$ccorg.$CRLF;
			
		// if WYSIWYG message is HTML
		$account = bab_getMailAccount($accid);
		if ('html' === $account['format']) {
		
			$messageval = bab_toHtml($messageval, BAB_HTML_ALL);
			$messageval .= $msgbody;
		} else {
		
			// TODO: il est possible d'améliorer ici la convertion de html vers texte brut!
			$messageval .= strip_tags($msgbody);
		}
			
			
		
		imap_close($mbox);
		}

	if (!isset($toval)) $toval = '';
	if (!isset($ccval)) $ccval = '';
	if (!isset($subjectval)) $subjectval = '';
	if (!isset($format)) $format = 'plain';
	if (!isset($messageval)) $messageval = '';
	
    composeMail($accid, $criteria, $reverse, trim($toval,', '), trim($ccval,', '), "", $subjectval, array(), $format, $messageval, 0);
	}

function mailUnload()
	{
	class temp
		{
		var $babCss;
		var $message;
		var $close;
		var $url;

		function temp()
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->message = bab_translate("Your message has been sent");
			$this->close = bab_translate("Close");
			}
		}

	$temp = new temp();
	echo bab_printTemplate($temp,"mail.html", "mailunload");
	}

/* main */

$idx = bab_rp('idx', 'compose');
$accid = bab_rp('accid','');
$sigid = bab_rp('sigid', '');
$criteria = bab_rp('criteria', '');
$reverse = bab_rp('reverse', '');
$to = bab_pp('to', '');
$cc = bab_pp('cc', '');
$bcc = bab_pp('bcc', '');
$subject = bab_pp('subject', '');
$format = bab_pp('format', 'plain');



if( "message" === bab_pp('compose'))
	{
	    
	if(!createMail($accid, $to, $cc, $bcc, $subject, $criteria, $reverse, $format, $sigid))
		$idx = "compose";
	else
		$idx = "unload";
	}

switch($idx)
	{
	case "unload":
		mailUnload();
		break;
	case "reply":
	case "replyall":
	case "forward":
		$babBody->title = bab_translate("Email");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$babBody->addItemMenu("compose", bab_translate("Compose"), $GLOBALS['babUrlScript']."?tg=inbox&idx=compose&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		if (!isset($all)) $all = '';
		if (!isset($fw)) $fw = '';
		
		mailReply($accid, $criteria, $reverse, $idreply, $all, $fw);
		break;

	default:
	case "compose":
		$babBody->title = bab_translate("Email");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$babBody->addItemMenu("compose", bab_translate("Compose"), $GLOBALS['babUrlScript']."?tg=inbox&idx=compose&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
	    composeMail($accid, $criteria, $reverse, $to, $cc, $bcc, $subject, /*$files_name*/array(), $format, $message, $sigid);
		break;
	}

exit;
$babBody->setCurrentItemMenu($idx);

?>