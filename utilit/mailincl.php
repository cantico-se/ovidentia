<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/class.phpmailer.php";
include $babInstallPath."utilit/class.smtp.php";

function bab_getMimeType($type, $subtype)
	{ 
	$primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
	if($subtype) 
		{ 
		return $primary_mime_type[(int) $type] . '/' . $subtype; 
		}
	return "TEXT/PLAIN";
	} 

// function by cleong@organic.com
function bab_getMimePart($mbox, $msg_number, $mime_type, $structure = false, $part_number = false) 
{
	if(!$structure) 
		{
		$structure = imap_fetchstructure($mbox, $msg_number); 
		}

	if($structure) 
		{ 
		if($mime_type == bab_getMimeType($structure->type, $structure->subtype)) 
			{
			if(!$part_number) 
				{ 
				$part_number = "1"; 
				}


			if ($structure->ifdisposition)
				{
				if (strtolower ($structure->disposition) == "attachment")
					{
					return false;
					}
				}

			$text = imap_fetchbody($mbox, $msg_number, $part_number); 
			if($structure->encoding == 3) 
				{ 
				return imap_base64($text); 
				} 
			else if($structure->encoding == 4) 
				{ 
				return imap_qprint($text); 
				} 
			else 
				{ 
				return $text; 
				} 
			}
			
		if($structure->type == 1) /* multipart */ 
			{ 
			while(list($index, $sub_structure) = each($structure->parts)) 
				{ 
				if($part_number) 
					{ 
					$prefix = $part_number . '.';
					}
				$data = bab_getMimePart($mbox, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1)); 
				if($data) 
					{
					return $data; 
					} 
				} 
			} 
		} 
	return false; 
} 

class babMail
{
	var $mail;

	function babMail()
	{
		$this->mail = new phpmailer();
		$this->mail->PluginDir = $GLOBALS['babInstallPath']."utilit/";
		$this->mail->From = $GLOBALS['babAdminEmail'];
		$this->mail->FromName = bab_translate("Ovidentia Administrator");
	}

	function mailFrom($email, $name='')
	{
		$this->mail->From = $email;
		$this->mail->FromName = $name;
	}

	function mailTo($email, $name="")
	{
		$this->mail->AddAddress($email, $name);
	}

	function clearTo()
	{
		$this->mail->ClearAddresses();
	}

	function mailCc($email, $name="")
	{
		$this->mail->AddCC($email, $name);
	}
	
	function clearCc()
	{
		$this->mail->ClearCcs();
	}
	
	function mailBcc($email, $name="")
	{
		$this->mail->AddBCC($email, $name);
	}

	function clearBcc()
	{
		$this->mail->ClearBccs();
	}

	function mailReplyTo($email, $name="")
	{
		$this->mail->AddReplyTo($email, $name);
	}

	function clearReplyTo()
	{
		$this->mail->clearReplyTos();
	}
	
	function clearAllRecipients()
	{
		$this->mail->clearAllRecipients();
	}

	function mailSubject($subject)
	{
		$this->mail->Subject = $subject;
	}

	function setPriority($priority)
	{
		$this->mail->Priority = $priority;
	}

	function setSmtpServer($server, $port)
	{
		$this->mail->Host = $server;
		$this->mail->Port = $port;
	}

	function mailBody($babBody, $format="plain")
	{
		$this->mail->Body = $babBody;
		$this->mail->AltBody = $babAltBody;
		if( $format == "plain" )
			$this->mail->IsHTML(true);
		else
			$this->mail->IsHTML(true);
	}

	function mailAltBody($babAltBody)
	{
		$this->mail->AltBody = $babAltBody;
	}

	function mailFileAttach( $fname, $realname, $type )
	{
		$this->mail->AddAttachment($fname, $realname);
	}

	function send()
	{
		return $this->mail->Send();
	}
}


class babMailSmtp extends babMail
{

	function babMailSmtp($server, $port)
	{
		$this->babMail();
		$this->mail->Host = $server;
		$this->mail->Port = $port;
	}
}

function bab_mail()
{
	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
	if( empty($arr['mailfunc']))
		return false;

	$mail = false;
	switch($arr['mailfunc'])
	{
		case "mail":
			$mail = new babMail();
			$mail->mail->IsMail();
			break;
		case "sendmail":
			$mail = new babMail();
			$mail->mail->IsSendmail();
			$mail->mail->Sendmail = $arr['smtpserver'];
			break;
		case "smtp":
			$mail = new babMail();
			$mail->mail->IsSMTP();
			$mail->mail->Host = $arr['smtpserver'];
			$mail->mail->Port = $arr['smtpport'];
			break;
	}
	return $mail;
}

?>
