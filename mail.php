<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/mailincl.php";

function getContactId( $name )
	{
	$replace = array( " " => "", "-" => "");
	$db = $GLOBALS['babDB'];
	$hash = md5(strtolower(strtr($name, $replace)));
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$hash."'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}

function addAddress( $val, $to, &$class)
{
	if( !empty($val))
	{
		$db = $GLOBALS['babDB'];
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
				$id = bab_getUserId(substr($addr, 0, -3));
				if( $id < 1) // it's a group
				{
					$idgrp = bab_isMemberOfGroup(substr($addr, 0, -3));
					if( $idgrp > 0 )
					{
					$req = "select p1.firstname, p1.lastname, p1.email from ".BAB_USERS_TBL." as p1, ".BAB_USERS_GROUPS_TBL." as p2 where p2.id_group='".$idgrp."' and p1.id=p2.id_object";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						while( $arr = $db->db_fetch_array($res))
							{
							$class->$to($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
							}
						}
					}
				}
				else // it's user
				{
					$req = "select * from ".BAB_USERS_TBL." where id='".$id."'";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$class->$to($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
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
					$req = "select * from ".BAB_CONTACTS_TBL." where id='".$id."'";
					$res = $db->db_query($req);
					if( $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$class->$to($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
						}
				}
			}
		}
	}
}


function composeMail($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid, $error)
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
		var $msie;
		var $bhtml;
		var $msgerror;


		function temp($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid, $error)
			{
			global $BAB_SESS_USERID,$BAB_SESS_USER,$BAB_SESS_EMAIL;
			$this->psigid = $psigid;
			$this->msgerror = $error;
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
				if( bab_isMagicQuotesGpcOn())
					{
					$this->messageval = stripslashes($pmsg);
					}
				else
					$this->messageval = $pmsg;
				if( $pformat == "html")
					$this->messageval = nl2br($this->messageval);
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
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				{
				$this->bhtml = 1;
				$this->msie = 1;
				}
			else
				{
				$this->bhtml = 0;
				$this->msie = 0;
				}
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->fromval = "\"".$arr['name']."\" &lt;".$arr['email']."&gt;";
                if( empty($pformat))
                    $pformat = $arr['format'];
                $req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where owner='".$BAB_SESS_USERID."'";
                $this->ressig = $this->db->db_query($req);
                $this->countsig = $this->db->db_num_rows($this->ressig);
				}
			else
				{
				$this->fromval = "\"".$BAB_SESS_USER."\" &lt;".$BAB_SESS_EMAIL."&gt;";
				}
			if( $pformat == "plain")
				{
				$this->msie = 0;
				$this->plainselect = "selected";
				$this->htmlselect = "";
				}
			else
				{
				$this->htmlselect = "selected";
				$this->plainselect = "";
				}
            if( $psigid == 0 || empty($psigid)) 
                $this->defaultselected = "selected";
            else
                $this->defaultselected = "";
			$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$BAB_SESS_USERID."' order by lastname asc";
			$this->rescl = $this->db->db_query($req);
			$this->countcl = $this->db->db_num_rows($this->rescl);
			}

        function getnextsig()
            {
			static $i = 0;
			if( $i < $this->countsig)
				{
				$arr = $this->db->db_fetch_array($this->ressig);
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
	
	$temp = new temp($accid, $criteria, $reverse, $pto, $pcc, $pbcc, $psubject, $pfiles, $pformat, $pmsg, $psigid, $error);
	//$babBody->babecho(	bab_printTemplate($temp,"mail.html", "mailcompose"));
	echo bab_printTemplate($temp,"mail.html", "mailcompose");
	}

function createMail($accid, $to, $cc, $bcc, $subject, $message, $files, $files_name, $files_type,$criteria, $reverse, $format, $sigid)
	{
	global $babBody, $BAB_SESS_USERID;
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

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr2 = $db->db_fetch_array($res);
			if( !empty($arr2['outserver']))
				$mail->setSmtpServer($arr2['outserver'], $arr2['outport']);
			addAddress($to, "mailTo", $mail);
			$mail->mailFrom($arr['email'], $arr['name']);
			if( bab_isMagicQuotesGpcOn())
				{
				$message = stripslashes($message);
				$subject = stripslashes($subject);
				}
            if( $sigid != 0)
                {
                $req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where id='".$sigid."' and owner='".$BAB_SESS_USERID."'";
                $res = $db->db_query($req);
                if( $res && $db->db_num_rows($res) > 0)
                    {
                    $arr = $db->db_fetch_array($res);
                    $message .= $arr['text'];
                    }

                }
			$mail->mailBody($message, $format);
			$mail->mailSubject($subject);
			if(!empty($cc))
				{
				addAddress($cc, "mailCc", $mail);
				}

			if(!empty($bcc))
				{
				addAddress($bcc, "mailBcc", $mail);
				}

			for($i=0; $i < count($files); $i++)
				if( !empty($files_name[$i]))
					$mail->mailFileAttach($files[$i], $files_name[$i], $files_type[$i]);
			if(!$mail->send())
				{
				$babBody->msgerror = bab_translate("Error occured when sending email !!");
				}
			else
				{
				return true;
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("Invalid mail domain !!");
			return false;
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("Invalid mail account !!");
		return false;
		}
	}


function mailReply($accid, $criteria, $reverse, $idreply, $all, $fw)
    {
    global $babBody, $BAB_SESS_USERID, $BAB_HASH_VAR;
    $CRLF = "\r\n";
	$db = $GLOBALS['babDB'];
	$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
    if( $res && $db->db_num_rows($res) > 0 )
        {
        $arr = $db->db_fetch_array($res);
        $req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
        $res2 = $db->db_query($req);
        if( $res2 && $db->db_num_rows($res2) > 0 )
            {
            $arr2 = $db->db_fetch_array($res2);
            $cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
            $mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
            if(!$mbox)
                {
                $babBody->msgerror = bab_translate("ERROR"). " : ". imap_last_error();
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

                $msgbody .= bab_getMimePart($mbox, $idreply, "TEXT/HTML");
                if(!$msgbody)
                    {
					$format = "plain";
                    $msgbody = bab_getMimePart($mbox, $idreply, "TEXT/PLAIN");
                    $msgbody = eregi_replace( "((http|https|mailto|ftp):(\/\/)?[^[:space:]<>]{1,})", "<a href='\\1'>\\1</a>",$msgbody); 
                    }
                else
                    {
					$format = "html";
                    $msgbody = eregi_replace("(src|background)=(['\"])cid:([^'\">]*)(['\"])", "src=\\2".$GLOBALS['babPhpSelf']."?tg=inbox&accid=".$accid."&idx=getpart&msg=$msg&cid=\\3\\4", $msgbody);
                    }
				$messageval = $CRLF.$CRLF.$CRLF.$CRLF."------".bab_translate("Original Message")."------".$CRLF;
                $messageval .= "From: ".$fromorg.$CRLF;
                $messageval .= "Sent: ".bab_strftime($headinfo->udate).$CRLF;
                $messageval .= "To: ".$toorg.$CRLF;
                if( !empty($ccorg))
                    $messageval .= "Cc: ".$ccorg.$CRLF;
                $messageval .= $msgbody;
                imap_close($mbox);
                }
            }
        }
    composeMail($accid, $criteria, $reverse, trim($toval), trim($ccval), "", $subjectval, array(), $format, $messageval, 0, "");
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
if(!isset($idx))
	{
	$idx = "compose";
	}

if( !isset( $to ))
	$to = "";

if( !isset( $cc ))
	$cc = "";

if( !isset( $bcc ))
	$bcc = "";

if( !isset( $subject ))
	$subject = "";

if( !isset( $format ))
	$format = "";

if( !isset( $message ))
	$message = "";

if( !isset( $sigid ))
	$sigid = "";

if( isset($compose) && $compose == "message")
	{
	if(!createMail($accid, $to, $cc, $bcc, $subject, $message, $files, $files_name, $files_type,$criteria, $reverse, $format, $sigid))
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
		mailReply($accid, $criteria, $reverse, $idreply, $all, $fw);
		break;

	default:
	case "compose":
		$babBody->title = bab_translate("Email");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$babBody->addItemMenu("compose", bab_translate("Compose"), $GLOBALS['babUrlScript']."?tg=inbox&idx=compose&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
	    composeMail($accid, $criteria, $reverse, $to, $cc, $bcc, $subject, /*$files_name*/array(), $format, $message, $sigid, $babBody->msgerror);
		break;
	}

exit;
$babBody->setCurrentItemMenu($idx);

?>
