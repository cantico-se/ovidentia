<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/mailincl.php";

define('CRLF', "\r\n");

function getContactId( $name )
	{
	$replace = array( " " => "", "-" => "");
	$db = new db_mysql();
	$hash = md5(strtolower(strtr($name, $replace)));
	$req = "select * from contacts where hashname='".$hash."'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[id];
		}
	else
		return 0;
	}

function addAddress( $val, $to, &$class)
{
	if( !empty($val))
	{
		$db = new db_mysql();
		$tmp = explode(",", $val);
		for($i=0; $i<count($tmp); $i++)
		{
			$addr = trim($tmp[$i]);
			if( eregi("(.*@.*\..*)", $addr, $res))
			{
				$class->$to($addr,$addr);
			}
			else if( strtolower(substr($addr, -3)) == "(g)")
			{

				eregi("([^(]*)", $addr, $res);
				$addr = $res[1];
				$id = getUserId($addr);
				if( $id < 1) // it's a group
				{
					$idgrp = isMemberOf($addr);
					if( $idgrp > 0 )
					{
					$req = "select p1.firstname, p1.lastname, p1.email from users as p1, users_groups as p2 where p2.id_group='".$idgrp."' and p1.id=p2.id_object";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						while( $arr = $db->db_fetch_array($res))
							{
							$class->$to($arr[email], composeName($arr[firstname], $arr[lastname]));
							}
						}
					}
				}
				else // it's user
				{
					$req = "select * from users where id='".$id."'";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$class->$to($arr[email], composeName($arr[firstname], $arr[lastname]));
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
					$req = "select * from contacts where id='".$id."'";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$class->$to($arr[email], composeName($arr[firstname], $arr[lastname]));
						}
				}
			}
		}
	}
}


function composeMail($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid)
	{
	global $body;

	class temp
		{
		var $accid;
		var $criteria;
		var $reverse;
		var $send;
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

		function temp($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid)
			{
			global $BAB_SESS_USERID,$BAB_SESS_USER,$BAB_SESS_EMAIL;
			$this->psigid = $psigid;
			$this->toval = "";
			if( !empty($pto))
				$this->toval = $pto;
			$this->ccval = "";
			if( !empty($pcc))
				$this->ccval = $pcc;
			$this->bccval = "";
			if( !empty($pbcc))
				$this->bccval = $pbcc;
			$this->subjectval = "";
			if( !empty($psubject))
				$this->subjectval = $psubject;
			$this->messageval = "";
			if( !empty($pmsg))
				{
				if(get_cfg_var("magic_quotes_gpc"))
					{
					$this->messageval = stripslashes($pmsg);
					}
				else
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
			$this->criteria = SORTARRIVAL;
			if( !empty($criteria))
				$this->criteria = $criteria;
			$this->reverse = 1;
			if( !empty($reverse))
				$this->reverse = $reverse;
			$this->send = babTranslate("Send");
			$this->from = babTranslate("From");
			$this->to = babTranslate("To");
			$this->cc = babTranslate("Cc");
			$this->bcc = babTranslate("Bcc");
			$this->subject = babTranslate("Subject");
			$this->attachments = babTranslate("Attachments");
			$this->format = babTranslate("Format");
			$this->plain = babTranslate("Plain text");
			$this->html = babTranslate("Html");
            $this->selectsig = "-- ".babTranslate("Select signature"). " --";
			$this->none = "-- ".babTranslate("Select destinataire"). " --";
			$this->urlto = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=address&idx=list')";
			$this->db = new db_mysql();
			$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->fromval = "\"".$arr[name]."\" &lt;".$arr[email]."&gt;";
                if( empty($pformat))
                    $pformat = $arr[format];
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
                $req = "select * from mail_signatures where owner='".$BAB_SESS_USERID."'";
                $this->ressig = $this->db->db_query($req);
                $this->countsig = $this->db->db_num_rows($this->ressig);
				}
			else
				{
				$this->fromval = "\"".$BAB_SESS_USER."\" &lt;".$BAB_SESS_EMAIL."&gt;";
				}
            if( $psigid == 0 || empty($psigid)) 
                $this->defaultselected = "selected";
            else
                $this->defaultselected = "";
			$req = "select * from contacts where owner='".$BAB_SESS_USERID."' order by lastname asc";
			$this->rescl = $this->db->db_query($req);
			$this->countcl = $this->db->db_num_rows($this->rescl);
			}

        function getnextsig()
            {
			static $i = 0;
			if( $i < $this->countsig)
				{
				$arr = $this->db->db_fetch_array($this->ressig);
                $this->signame = $arr[name];
                if($arr[html] == "Y")
                    $this->signame = $arr[name] . " ( html )";
                $this->sigid = $arr[id];
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
	$body->babecho(	babPrintTemplate($temp,"mail.html", "mailcompose"));
	}

function createMail($accid, $to, $cc, $bcc, $subject, $message, $files, $files_name, $files_type,$criteria, $reverse, $format, $sigid)
	{
	global $body, $BAB_SESS_USERID;
	if( empty($to))
		{
		$body->msgerror = babTranslate("You must fill to field !!");
		return;
		}
	if( empty($subject))
		{
		$body->msgerror = babTranslate("You must fill subject field !!");
		return;
		}
	if( empty($message))
		{
		$body->msgerror = babTranslate("You must fill message field !!");
		return;
		}

	$db = new db_mysql();
	$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$req = "select * from mail_domains where id='".$arr[domain]."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr2 = $db->db_fetch_array($res);
			if( !empty($arr2[outserver]))
				$mime = new babMailSmtp($arr2[outserver], $arr2[outport]);
			else
				$mime = new babMail();
			addAddress($to, "mailTo", $mime);
			$mime->mailFrom($arr[email], $arr[name]);
			if(get_cfg_var("magic_quotes_gpc"))
				{
				$message = stripslashes($message);
				$subject = stripslashes($subject);
				}
            if( $sigid != 0)
                {
                $req = "select * from mail_signatures where id='".$sigid."' and owner='".$BAB_SESS_USERID."'";
                $res = $db->db_query($req);
                if( $res && $db->db_num_rows($res) > 0)
                    {
                    $arr = $db->db_fetch_array($res);
                    $message .= $arr[text];
                    }

                }
			$mime->mailBody($message, $format);
			$mime->mailSubject($subject);
			if(!empty($cc))
				{
				addAddress($to, "mailCc", $mime);
				}

			if(!empty($bcc))
				{
				addAddress($to, "mailBcc", $mime);
				}

			for($i=0; $i < count($files); $i++)
				if( !empty($files_name[$i]))
					$mime->mailFileAttach($files[$i], $files_name[$i], $files_type[$i]);
			if(!$mime->send())
				{
				$body->msgerror = babTranslate("Error occured when sending email !!");
				}
			else
				{
				Header("Location: index.php?tg=inbox&idx=list&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
				}
			}
		else
			{
			$body->msgerror = babTranslate("Invalid mail domain !!");
			return;
			}
		}
	else
		{
		$body->msgerror = babTranslate("Invalid mail account !!");
		return;
		}
	}


function mailReply($accid, $criteria, $reverse, $idreply, $all, $fw)
    {
    global $body, $BAB_SESS_USERID, $BAB_HASH_VAR;
	$db = new db_mysql();
	$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from mail_accounts where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
    if( $res && $db->db_num_rows($res) > 0 )
        {
        $arr = $db->db_fetch_array($res);
        $req = "select * from mail_domains where id='".$arr[domain]."'";
        $res2 = $db->db_query($req);
        if( $res2 && $db->db_num_rows($res2) > 0 )
            {
            $arr2 = $db->db_fetch_array($res2);
            $cnxstring = "{".$arr2[inserver]."/".$arr2[access].":".$arr2[inport]."}INBOX";
            $mbox = @imap_open($cnxstring, $arr[account], $arr[accpass]);
            if(!$mbox)
                {
                $body->msgerror = babTranslate("ERROR"). " : ". imap_last_error();
                }
            else
                {
                $headinfo = imap_header($mbox, imap_msgno($mbox, $idreply));
                $arr = $headinfo->from;
                $toval = "";
                for($i=0; $i < count($arr); $i++)
                    {
                    $mhc = imap_mime_header_decode($arr[$i]->personal);
                    $fromorg .= $mhc[0]->text . " [" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "] ";
                    if( $fw != 1)
                        $toval .= $arr[$i]->mailbox . "@" . $arr[$i]->host." ";
                    }
                $toorg = "";
                if( $fw != 1)
                    {
                    $arr = $headinfo->to;
                    for($i=0; $i < count($arr); $i++)
                        {
                        $mhc = imap_mime_header_decode($arr[$i]->personal);
                        $toorg .= $mhc[0]->text." ";

                        if( $all == 1)
                            $toval .= $arr[$i]->mailbox . "@" . $arr[$i]->host." ";
                        }

                    $arr = $headinfo->cc;
                    $ccorg = "";
                    $ccval = "";
                    for($i=0; $i < count($arr); $i++)
                        {
                        $mhc = imap_mime_header_decode($arr[$i]->personal);
                        $ccorg .= $mhc[0]->text . " ";

                        if( $all == 1)
                            $ccval .= $arr[$i]->mailbox . "@" . $arr[$i]->host." ";
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

                $msgbody .= get_part($mbox, $idreply, "TEXT/HTML");
                if(!$msgbody)
                    {
                    $msgbody = get_part($mbox, $idreply, "TEXT/PLAIN");
                    $msgbody = eregi_replace( "((http|https|mailto|ftp):(\/\/)?[^[:space:]<>]{1,})", "<a href='\\1'>\\1</a>",$msgbody); 
                    }
                else
                    {
                    $msgbody = eregi_replace("(src|background)=(['\"])cid:([^'\">]*)(['\"])", "src=\\2index.php?tg=inbox&accid=".$accid."&idx=getpart&msg=$msg&cid=\\3\\4", $msgbody);
                    }
                $messageval = CRLF.CRLF.CRLF.CRLF."------".babTranslate("Original Message")."------".CRLF;
                $messageval .= "From: ".$fromorg.CRLF;
                $messageval .= "Sent: ".bab_strftime($headinfo->udate).CRLF;
                $messageval .= "To: ".$toorg.CRLF;
                if( !empty($ccorg))
                    $messageval .= "Cc: ".$ccorg.CRLF;
                $messageval .= $msgbody;
                imap_close($mbox);
                }
            }
        }
    composeMail($accid, $criteria, $reverse, trim($toval), trim($ccval), "", $subjectval, array(), $arr[format], $messageval, 0);
	}

/* main */
if(!isset($idx))
	{
	$idx = "compose";
	}


if( isset($compose) && $compose == "message")
	{
	createMail($accid, $to, $cc, $bcc, $subject, $message, $files, $files_name, $files_type,$criteria, $reverse, $format, $sigid);
	$idx = "compose";
	}

switch($idx)
	{
	case "reply":
	case "replyall":
	case "forward":
		$body->title = babTranslate("Email");
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$body->addItemMenu("compose", babTranslate("Compose"), $GLOBALS[babUrl]."index.php?tg=inbox&idx=compose&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		mailReply($accid, $criteria, $reverse, $idreply, $all, $fw);
		break;

	default:
	case "compose":
		$body->title = babTranslate("Email");
		$body->addItemMenu("list", babTranslate("List"), $GLOBALS[babUrl]."index.php?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$body->addItemMenu("compose", babTranslate("Compose"), $GLOBALS[babUrl]."index.php?tg=inbox&idx=compose&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
	    composeMail($accid, $criteria, $reverse, $to, $cc, $bcc, $subject, /*$files_name*/array(), $format, $message, $sigid);
		break;
	}
$body->setCurrentItemMenu($idx);

?>