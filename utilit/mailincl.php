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
include_once "base.php";
include_once $babInstallPath."utilit/class.phpmailer.php";
include_once $babInstallPath."utilit/class.smtp.php";

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
				else $prefix = '';
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

class babMailTemplate
	{
	var $mailcontent;
	function babMailTemplate($msg)
		{
		$this->mailcontent = $msg;
		}
	}

class babMail
{
	var $mail;

	function babMail()
	{
		$this->mail = new phpmailer();
		$this->mail->PluginDir = $GLOBALS['babInstallPath']."utilit/";
		$this->mail->From = $GLOBALS['babAdminEmail'];
		$this->mail->FromName = $GLOBALS['babAdminName'];
		$this->mail->SetLanguage('en', $GLOBALS['babInstallPath']."utilit/");
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
		//$this->mail->AltBody = $babAltBody;
		if( $format == "plain" )
			$this->mail->IsHTML(false);
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

	function mailTemplate($msg)
	{
		$mtmpl = new babMailTemplate($msg);
		return bab_printTemplate($mtmpl,"mailtemplate.html", "default");
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
	global $babBody;

	if( empty($babBody->babsite['mailfunc']))
		return false;

	$mail = false;
	switch($babBody->babsite['mailfunc'])
	{
		case "mail":
			$mail = new babMail();
			$mail->mail->IsMail();
			break;
		case "sendmail":
			$mail = new babMail();
			$mail->mail->IsSendmail();
			$mail->mail->Sendmail = $babBody->babsite['smtpserver'];
			break;
		case "smtp":
			$mail = new babMail();
			$mail->mail->IsSMTP();
			$mail->mail->Host = $babBody->babsite['smtpserver'];
			$mail->mail->Port = $babBody->babsite['smtpport'];
			if( $babBody->babsite['smtpuser'] != "" ||  $babBody->babsite['smtppass'] != "")
				{
				$mail->mail->SMTPAuth = true;;
				$mail->mail->Username = $babBody->babsite['smtpuser'];
				$mail->mail->Password = $babBody->babsite['smtppass'];
				}
			break;
	}
	return $mail;
}

?>
