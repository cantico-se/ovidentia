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
	var $mailTo = array();
	var $mailCc = array();
	var $mailBcc = array();
	var $attachements = array();
	var $format;
	var $sent_status;


	function babMail()
	{
		include_once $GLOBALS['babInstallPath']."utilit/class.phpmailer.php";
		include_once $GLOBALS['babInstallPath']."utilit/class.smtp.php";
		
		$this->mail = new phpmailer();
		$this->mail->PluginDir = $GLOBALS['babInstallPath']."utilit/";
		$this->mail->From = $GLOBALS['babAdminEmail'];
		$this->mail->FromName = $GLOBALS['babAdminName'];
		$this->mail->Sender = $GLOBALS['babAdminEmail'];
		$this->mail->SetLanguage('en', $GLOBALS['babInstallPath']."utilit/");
	}

	function mailFrom($email, $name='')
	{
		$this->mail->From = $email;
		$this->mail->FromName = $name;
	}

	function mailTo($email, $name="")
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddAddress($email, $name);
			$this->mailTo[] = array($email, $name);
		}
	}

	function clearTo()
	{
		$this->mail->ClearAddresses();
		$this->mailTo = array();
	}

	function mailCc($email, $name="")
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddCC($email, $name);
			$this->mailCc[] = array($email, $name);
		}
	}
	
	function clearCc()
	{
		$this->mail->ClearCcs();
		$this->mailCc = array();
	}
	
	function mailBcc($email, $name="")
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddBCC($email, $name);
			$this->mailBcc[] = array($email, $name);
		}
	}

	function clearBcc()
	{
		$this->mail->ClearBccs();
		$this->mailBcc = array();
	}

	function mailReplyTo($email, $name="")
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddReplyTo($email, $name);
			$this->replyTo[] = array($email, $name);
		}
	}

	function clearReplyTo()
	{
		$this->mail->clearReplyTos();
		$this->replyTo = array();
	}
	
	function mailSender($email)
	{
		$this->mail->Sender = $email;
	}

	function clearAllRecipients()
	{
		$this->mail->clearAllRecipients();
		$this->mailTo = array();
		$this->mailCc = array();
		$this->mailBcc = array();
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
		$this->format = $format;
		$this->mail->Body = $babBody;
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
		$this->attachements[] = array($fname, $realname, $type);
	}

	function mailClearAttachments()
	{
		$this->mail->ClearAttachments();
	}

	function send()
	{
		static $send_immediately = NULL;
		
		if (NULL === $send_immediately) {
		
			$reg = bab_getRegistryInstance();
			$reg->changeDirectory('/bab/mail_spooler/');
			$send_immediately = $reg->getValue('send_immediately');
			if (NULL === $send_immediately) {
				$reg->setKeyValue('send_immediately', true);
				$send_immediately = true;
			}
		}
		
		
		if (true === $send_immediately) {
			$this->sent_status = $this->mail->Send();
			if (!$this->sent_status) {
				$this->recordMail();
			}
		} else {
			$this->sent_status = false;
			$this->recordMail();
		}
		
		return $this->sent_status; 
	}

	function mailTemplate($msg)
	{
		$mtmpl = new babMailTemplate($msg);
		return bab_printTemplate($mtmpl,"mailtemplate.html", "default");
	}

	function ErrorInfo()
	{
		return empty($this->mail->ErrorInfo) ? false : $this->mail->ErrorInfo;
	}

	function addMail(&$mail, $list) {
		foreach($list as $arr) {
			$mail[] = $arr[0];
		}
	}

	/**
	 * Record a mail in the database
	 */
	function recordMail() {

		if ($this->attachements) {

			$dir = $GLOBALS['babUploadPath'].'/mail/';
			if (!is_dir($dir)) {
				bab_mkdir($dir);
			}

			foreach($this->attachements as $k => $arr) {
				$newname = $dir.md5(uniqid(rand(), true));
				if (is_file($arr[0])) {
					copy($arr[0], $newname);
					$this->attachements[$k][0] = $newname;
				}
			}
		}

		$recipients = array();
		$this->addMail($recipients, $this->mailTo);
		$this->addMail($recipients, $this->mailCc);
		$this->addMail($recipients, $this->mailBcc);

		$recipients = implode(', ',$recipients);

		$data = array(
				'from'		=> array($this->mail->From, $this->mail->FromName),
				'sender'	=> $this->mail->Sender,
				'to'		=> $this->mailTo,
				'cc'		=> $this->mailCc,
				'bcc'		=> $this->mailBcc,
				'files'		=> $this->attachements
			);

		$data = serialize($data);

		$sent_status = $this->sent_status ? 1 : 0;

		$mail_hash = md5($this->mail->Subject.$this->mail->Body.$data);

		$db = $GLOBALS['babDB'];

		$res = $db->db_query("SELECT COUNT(*) FROM ".BAB_MAIL_SPOOLER_TBL." WHERE mail_hash='".$db->db_escape_string($mail_hash)."'");
		list($n) = $db->db_fetch_array($res);

		if (0 < $n) {
			return;
		}

		$db->db_query("INSERT INTO ".BAB_MAIL_SPOOLER_TBL." 
				( mail_hash, mail_subject, body, altbody, format, recipients, mail_data, sent_status, error_msg, mail_date ) 
			VALUES 
				(
					'".$db->db_escape_string($mail_hash)."',
					'".$db->db_escape_string($this->mail->Subject)."', 
					'".$db->db_escape_string($this->mail->Body)."', 
					'".$db->db_escape_string($this->mail->AltBody)."',
					'".$db->db_escape_string($this->format)."',
					'".$db->db_escape_string($recipients)."',
					'".$db->db_escape_string($data)."', 
					'".$db->db_escape_string($sent_status)."', 
					'".$db->db_escape_string($this->mail->ErrorInfo)."', 
					NOW()
				)
			");
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
				$mail->mail->SMTPAuth = true;
				$mail->mail->Username = $babBody->babsite['smtpuser'];
				$mail->mail->Password = $babBody->babsite['smtppass'];
				}
			break;
	}
	return $mail;
}

?>
