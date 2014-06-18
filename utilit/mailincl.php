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
require_once dirname(__FILE__).'/eventincl.php';

function bab_getMimeType($type, $subtype)
	{ 
	$primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER");
	if($subtype) 
		{ 
		return $primary_mime_type[(int) $type] . '/' . $subtype; 
		}
	return "TEXT/PLAIN";
	} 

/**
 * get mime part, decode content to ovidentia charset
 * @param unknown_type $mbox
 * @param int $msg_number
 * @param string $mime_type
 * @param object $structure
 * @param string $part_number
 * @return string
 */
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
				if (mb_strtolower ($structure->disposition) == "attachment")
					{
					return false;
					}
				}

			$text = imap_fetchbody($mbox, $msg_number, $part_number); 
			if($structure->encoding == 3) 
				{ 
				$text = imap_base64($text); 
				} 
			else if($structure->encoding == 4) 
				{ 
				$text = imap_qprint($text); 
				}
				
			// get encoding from structure
			
			foreach($structure->parameters as $param)
				{
					if ('CHARSET' === $param->attribute)
					{
						return bab_getStringAccordingToDataBase($text, mb_strtoupper($param->value));
					}
				}
			
			return $text;
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
	var $sContent;
	
	function babMailTemplate($msg)
		{
		$this->mailcontent	= $msg;
		$this->sContent		= 'text/html; charset=' . bab_charset::getIso();
		}
	}



/**
 * Mail event
 * Main mail event object to transport mail informations
 * 
 * 
 * @see bab_eventBeforeMailSent
 * @see bab_eventAfterMailSent
 */ 
class bab_eventMail extends bab_event {
	
	
	
	/**
	 * 
	 * @var array
	 */ 
	public $from = null;		// array($this->mail->From, $this->mail->FromName),
	
	/**
	 * 
	 * @var string
	 */ 
	public $sender = null;
	
	/**
	 * List of recipient for TO field
	 * @var array
	 */ 
	public $to = null;
	
	
	/**
	 * List of recipient for CC field
	 * @var array
	 */ 
	public $cc = null;
	
	
	
	/**
	 * List of recipient for BCC field
	 * @var array
	 */ 
	public $bcc = null;
	
	
	
	/**
	 * List of mail attachements
	 * @var array
	 */ 
	public $attachements = null;
	
	
	
	/**
	 * List of mail image inline attachements
	 * @var array
	 */ 
	public $imageattachements = null;
	
	
	/**
	 * @var string
	 */ 
	public $subject = null;
	
	/**
	 * @var string
	 */ 
	public $body = null;
	
	
	/**
	 * @var string
	 */
	public $altBody = null;
	
	/**
	 * @var string
	 */
	public $format = null;
	
	/**
	 * Unique identifier of email used in mailspooler
	 * @var string
	 */
	public $hash = null;
	
	
	public function setMailInfos(babMail $babMail) {
		
		$this->from 		= array($babMail->mail->From, $babMail->mail->FromName);
		$this->sender 		= $babMail->mail->Sender;
		
		$this->to 			= $babMail->mailTo;
		$this->cc 			= $babMail->mailCc;
		$this->bcc 			= $babMail->mailBcc;
		$this->attachements = $babMail->attachements;
		$this->imageattachements = $babMail->imageattachements;
		
		$this->subject		= $babMail->mail->Subject;
		$this->body			= $babMail->mail->Body;
		
		$this->altBody		= $babMail->mail->AltBody;
		$this->format		= $babMail->format;
		
		$this->hash			= $babMail->hash;
	}

}


/**
 * Event fired before mail sent
 * this event allow to cancel an email
 */ 
class bab_eventBeforeMailSent extends bab_eventMail {
	
	/**
	 * continue to next operation
	 * @var bool
	 */ 
	private $propagation_status = true;
	
	/**
	 * @see bab_eventBeforeMailSent::cancel()
	 * @var bool
	 */ 
	public $return_value = null;
	
	/**
	 * Cancel the sending 
	 * The message will not be sent and will not be recorded as a mail not sent in the list
	 * 
	 * @see babMail::send()
	 * 
	 * @param	bool	$returnvalue	when the message is canceled, 
	 * 									the send method of the babMail object will return false by default
	 * 									this parameter can be set to true to "simulate" a correct mailing
	 * 
	 * @return bab_eventBeforeMailSent
	 */ 
	public function cancel($returnvalue = false) {
		$this->propagation_status = false;
		$this->return_value = $returnvalue;
		return $this;
	}
	
	/**
	 * @return bool
	 */ 
	public function sendAllowed() {
		return $this->propagation_status;
	}
}


/**
 * Event fired after mail sent
 * this event allow to get the sent status
 */ 
class bab_eventAfterMailSent extends bab_eventMail {
	
	/**
	 * 
	 * @var bool
	 */
	public $sent_status	= null;
	
	/**
	 * error message from server or null if no error or no message
	 * @var string
	 */
	public $ErrorInfo = null;
	
	/**
	 * Commplete SMTP trace if available
	 * @var string
	 */
	public $smtp_trace = null;
}




include_once $GLOBALS['babInstallPath'].'utilit/class.phpmailer.php';
include_once $GLOBALS['babInstallPath'].'utilit/class.smtp.php';

class bab_PHPMailer extends PHPMailer
{

	/**
	 * A copy of the smtp_trace get using output buffering
	 * @var string
	 */
	public $smtp_trace = '';
	
	
	/**
	 * Set after send, uniq ID used for Message-Id header
	 * @var string
	 */
	public $uniq_id = null;


	/**
	 * (non-PHPdoc)
	 * @see PHPMailer::CreateHeader()
	 */
	public function CreateHeader() {

		$result = parent::CreateHeader();

		$this->uniq_id = substr($this->boundary[1], strlen('b1_'));

		return $result;
	}


	/**
	 * (non-PHPdoc)
	 * @see PHPMailer::SmtpSend()
	 */
	protected function SmtpSend($header, $body) {
		
		ob_start();
		
		
		try {
			$result = parent::SmtpSend($header, $body);
		} catch (phpmailerException $e) {
			$_bab_message = $this->Lang('data_not_accepted');
			$_bab_smtperror = $this->smtp->getError();
			 
			if (isset($_bab_smtperror['error']))
			{
				$_bab_message .= ' / '.$_bab_smtperror['error'];
			}
			 
			if (isset($_bab_smtperror['smtp_code']))
			{
				$_bab_message .= ' / '.$_bab_smtperror['smtp_code'];
			}
			 
			if (isset($_bab_smtperror['smtp_msg']))
			{
				$_bab_message .= ' / '.$_bab_smtperror['smtp_msg'];
			}
			
			throw new phpmailerException($_bab_message, self::STOP_CRITICAL);
		}
		
		
		$this->smtp_trace = ob_get_contents();
		ob_end_clean();
		
		
		return $result;
	}
}





/**
 * Class API used to send mail via php mailer and ovidentia configuration
 * 
 */ 
class babMail
{
	public $mail;
	public $mailTo = array();
	public $mailCc = array();
	public $mailBcc = array();
	public $attachements = array();
	public $imageattachements = array();
	public $format;
	public $sent_status;

	/**
	 * unique identifier of email
	 * @var string
	 */
	public $hash;

	public function __construct()
	{

		
		$this->mail = new bab_PHPMailer();
		$this->mail->Timeout = 60; // Timout modification for slower SMTP servers
		$this->mail->CharSet = bab_charset::getIso();
		$this->mail->PluginDir = $GLOBALS['babInstallPath'].'utilit/';
		$this->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		$this->mailSender($GLOBALS['babAdminEmail']);
		$this->mail->SetLanguage('en', $GLOBALS['babInstallPath'].'utilit/');

	}
	

	

	public function mailFrom($email, $name = '')
	{
		$this->mail->From = $email;
		$this->mail->FromName = $name;
	}

	/**
	 * Adds a recipient (TO) to the email message.
	 * 
	 * @param string	$email			The email address of the recipient.
	 * @param string	$name			The (optional) name of the recipient.
	 */
	public function mailTo($email, $name = '')
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddAddress($email, $name);
			$this->mailTo[] = array($email, $name);
		}
	}

	/**
	 * Removes all currently added recipients (TO) for the email message.
	 */
	public function clearTo()
	{
		$this->mail->ClearAddresses();
		$this->mailTo = array();
	}

	/**
	 * Adds a recipient (CC) to the email message.
	 * 
	 * @param string	$email			The email address of the recipient.
	 * @param string	$name			The (optional) name of the recipient.
	 */
	public function mailCc($email, $name = '')
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddCC($email, $name);
			$this->mailCc[] = array($email, $name);
		}
	}


	/**
	 * Removes all currently added recipients (CC) for the email message.
	 */
	public function clearCc()
	{
		$this->mail->ClearCcs();
		$this->mailCc = array();
	}


	/**
	 * Adds a recipient (BCC) to the email message.
	 * 
	 * @param string	$email			The email address of the recipient.
	 * @param string	$name			The (optional) name of the recipient.
	 */
	public function mailBcc($email, $name = '')
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddBCC($email, $name);
			$this->mailBcc[] = array($email, $name);
		}
	}

	/**
	 * Removes all currently added recipients (BCC) for the email message.
	 */
	public function clearBcc()
	{
		$this->mail->ClearBccs();
		$this->mailBcc = array();
	}


	/**
	 * Removes all currently added recipients (TO, CC and BCC) for the email message.
	 */
	public function clearAllRecipients()
	{
		$this->mail->clearAllRecipients();
		$this->mailTo = array();
		$this->mailCc = array();
		$this->mailBcc = array();
	}


    /**
     * Adds a "Reply-to" address.  
     * @param string	$email			The reply-to address.
     * @param string	$name			Optional name of reply-to.
     */
 	public function mailReplyTo($email, $name = '')
	{
		/* Add email only if it's not empty */
		if (!empty($email)) {
			$this->mail->AddReplyTo($email, $name);
			$this->replyTo[] = array($email, $name);
		}
	}


	/**
	 * Removes all currently added reply-to addresses for the email message.
	 */
	public function clearReplyTo()
	{
		$this->mail->clearReplyTos();
		$this->replyTo = array();
	}


	/**
	 * Sets the Sender email address (Return-Path) of the message.  If not empty,
     * will be sent via -f to sendmail or as 'MAIL FROM' in smtp mode.
     */
	public function mailSender($email)
	{
		$this->mail->Sender = $email;
	}


	/**
	 * Requests a read receipt for the email (i.e if the recipient has a compliant email reader,
	 * he will be prompted to acknowledge the receipt of the message).
	 * 
	 * @param string $email		The email address where the reading confirmation will be sent.
	 */
	public function confirmReadingTo($email)
	{
		$this->mail->ConfirmReadingTo = $email;
	}


	/**
	 * Sets the subject of the message.
	 * 
	 * @param string $subject
	 */
	public function mailSubject($subject)
	{
		$this->mail->Subject = $subject;
	}


	/**
	 * Sets the email priority (1 = High, 3 = Normal, 5 = low).
	 * 
	 * @param int	$priority
	 */
	public function setPriority($priority)
	{
		$this->mail->Priority = $priority;
	}


	public function setSmtpServer($server, $port)
	{
		$this->mail->Host = $server;
		$this->mail->Port = $port;
	}


	/**
	 * Sets the Body of the message.  This can be either an HTML or text body.
	 * 
	 * @param string	$body
	 * @param string	$format
	 */
	public function mailBody($body, $format = 'plain')
	{
		$this->format = $format;
		$this->mail->Body = $body;
		if( $format == 'plain' )
			$this->mail->IsHTML(false);
		else
			$this->mail->IsHTML(true);
	}


	/**
	 * Sets the text-only body of the message.  This automatically sets the
     * email to multipart/alternative.  This body can be read by mail
     * clients that do not have HTML email capability such as mutt. Clients
     * that can read HTML will view the normal Body.
     * 
     * @param string	$altBody
     */
	public function mailAltBody($altBody)
	{
		$this->mail->AltBody = $altBody;
	}


	/**
	 * Adds an attachment from a path on the filesystem.
     * Returns false if the file could not be found
     * or accessed.
	 *
	 * @param string	$path		Path to the attachment.
	 * @param string	$realname	Overrides the attachment name.
	 * @param string	$type		File extension (MIME) type.
	 * 
	 * @return bool
	 */
	public function mailFileAttach($path, $realname, $type)
	{
		$result = $this->mail->AddAttachment($path, $realname);
		$this->attachements[] = array($path, $realname, $type);
		return $result;
	}


	/**
	 * Adds an attachment from a string.
	 *
	 * @param string	$content	The content to be attachmed.
	 * @param string	$realname	The attachment name.
	 * @param string	$type		File extension (MIME) type.
	 */
	public function mailStringAttach($path, $realname, $type)
	{
		$result = $this->mail->AddStringAttachment($path, $realname, 'base64', $type);
		$this->attachements[] = array('', $realname, $type);
	}


	/**
	 *  Add an embedded attachment from a file.
	 *
	 * @param string	$path	
	 * @param string	$cid	
	 * @param string	$name
	 * @param string	$encoding
	 * @param string	$type
	 */
	public function mailEmbeddedImage($path, $cid, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		$result = $this->mail->AddEmbeddedImage($path, $cid, $name, $encoding, $type);
		$this->imageattachements[] = array($path, $cid, $name, $encoding, $type);
	}


	public function mailClearAttachments()
	{
		$this->mail->ClearAttachments();
	}
	
	/**
	 * Add custom header
	 * 
	 * @param	string	$name
	 * @param	string	$value
	 */
	public function AddCustomHeader($name, $value)
	{
		$this->mail->AddCustomHeader($name, $value);
	}
	
	

	/**
	 * Send message
	 * @return	bool
	 */ 
	public function send()
	{
		$this->mail->ErrorInfo = '';

		$event = new bab_eventBeforeMailSent;
		$event->setMailInfos($this);
		
		bab_fireEvent($event);
		
		if (!$event->sendAllowed()) {
			return $event->return_value;
		}
		
		$this->sent_status = $this->mail->Send();
		
		$event = new bab_eventAfterMailSent;
		$event->setMailInfos($this);
		$event->sent_status = $this->sent_status;
		$event->ErrorInfo = empty($this->mail->ErrorInfo) ? null : $this->mail->ErrorInfo;
		$event->smtp_trace = $this->mail->smtp_trace;
		
		
		
		bab_fireEvent($event);
		
		$this->hash = null;

		return $this->sent_status; 
	}
	
	
	/**
	 * Get the Message-ID header value after mail sent
	 * @return string
	 */
	public function getMessageId()
	{
		if (!empty($this->mail->Hostname)) {
      		$hostname = $this->Hostname;
		} elseif (isset($_SERVER['SERVER_NAME'])) {
			$hostname = $_SERVER['SERVER_NAME'];
    	} else {
			$hostname = 'localhost.localdomain';
    	}
    	
    	return sprintf("<%s@%s>", $this->mail->uniq_id, $hostname);
	}


	public function mailTemplate($msg)
	{
		$mtmpl = new babMailTemplate($msg);
		return bab_printTemplate($mtmpl, 'mailtemplate.html', 'default');
	}


	/**
	 * Get error message of last send() method call
	 * or false if the mail has been sent successfully
	 * @return string
	 */
	public function ErrorInfo()
	{
		return empty($this->mail->ErrorInfo) ? false : $this->mail->ErrorInfo;
	}


	private function addMail(&$mail, $list) {
		foreach($list as $arr) {
			$mail[] = $arr[0];
		}
	}


}




/**
 * 
 *
 */
class babMailSmtp extends babMail
{
	private $smtp_conn;
	
	
	public function __construct()
	{
		parent::__construct();
		$this->mail->IsSMTP();

		/**
		 * To enable SMTP trace
		 */
		$this->mail->SMTPDebug = 2;
		
	}
	
	/**
	 * Authenticate SMTP connexion
	 * @param string $smtpuser
	 * @param string $smtppass
	 */
	public function setAuthenticated($smtpuser, $smtppass)
	{
		$this->mail->SMTPAuth = true;
		$this->mail->Username = $smtpuser;
		$this->mail->Password = $smtppass;
		
		$host = $this->mail->Host;
		$port = $this->mail->Port;
		$ssl = ($this->mail->SMTPSecure == 'ssl') ? '1' : '0';
		
		require_once dirname(__FILE__).'/session.class.php';
		$session = bab_getInstance('bab_Session');
		/*@var $session bab_Session */
		
		$property = 'bab_smtp_auth_type_'.md5($host.$port.$ssl);
		if (!isset($session->$property))
		{
			$arr = $this->getSmtpAuthTypes($host, $port);
			if (is_array($arr) && count($arr) > 0)
			{
				$session->$property = $arr;
			}
		}
		
		$server_allowed = isset($session->$property) ? $session->$property : array('LOGIN');
		$client_allowed = array('LOGIN', 'PLAIN', 'CRAM-MD5');
		
		$allowed = array_intersect($server_allowed, $client_allowed);

		$this->mail->AuthType = reset($allowed);
	}
	
	
	
	/**
	 * Fetch supported auth types from SMTP server
	 * return null if connexion failed or if timed out
	 * return false if auth types cant be found
	 * 
	 * @param string $server
	 * @param int $port
	 * 
	 * @return array
	 */
	private function getSmtpAuthTypes($server, $port)
	{
		$CRLF = "\r\n";
		$ssl = ($this->mail->SMTPSecure == 'ssl');
		
		$this->smtp_conn = fsockopen(
				($ssl ? 'ssl://':'').$server,  // the host of the server
				$port,    // the port to use
				$errno,   // error number if any
				$errstr,  // error message if any
				10);   	  // give up on connexion after 10 secs
		
		if(empty($this->smtp_conn)) {
			return null;
		}
		
		stream_set_timeout($this->smtp_conn, 10); // give up on read after 10 sec
		
		if (isset($_SERVER['SERVER_NAME'])) {
			$from = $_SERVER['SERVER_NAME'];
		} else {
			$from = 'localhost.localdomain';
		}
		
		$this->write('EHLO ' . $from . $CRLF);
		$data = $this->read();
		$code = substr($data, 0, 3);
		if ($code != 250) {
			$this->write('HELO ' . $from . $CRLF);
			$data = $this->read();
			$code = substr($data, 0, 3);
			if($code != 250) {
				fclose($this->smtp_conn);
				return false;
			}
		}

		fclose($this->smtp_conn);

		if (preg_match('/^250-AUTH[\s=](.+)$/m', $data, $m))
		{
			return explode(' ', trim($m[1]));
		}
		
		return false;
	}
	
	
	private function write($data)
	{
		// echo ">". $data."<br />";
		
		return fwrite($this->smtp_conn, $data);
	}
	
	private function read()
	{
		
		$data = '';
		while(is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
			$str = fgets($this->smtp_conn, 515);
			$data .= $str;
			
			// if 4th character is a space, we are done reading, break the loop
			if(substr($str, 3, 1) == ' ') {
				break;
			}
		
			// Timed-out
			$info = stream_get_meta_data($this->smtp_conn);
			if ($info['timed_out']) {
				break;
			}
		}
		
		// echo "<". $data."<br />";
		
		return $data;
	}
	
}

/**
 * Instanciate a new babMail object initialized accordingly to the site
 * configuration.
 * 
 * @return babMail
 */
function bab_mail()
{
	global $babBody;

	if( empty($babBody->babsite['mailfunc']))
		return false;

	$mail = false;
	switch($babBody->babsite['mailfunc'])
	{
		case 'mail':
			$mail = new babMail();
			$mail->mail->IsMail();
			break;
		case 'sendmail':
			$mail = new babMail();
			$mail->mail->IsSendmail();
			$mail->mail->Sendmail = $babBody->babsite['smtpserver'];
			break;
		case 'smtp':
			$mail = new babMailSmtp();
			
			$mail->mail->Host = $babBody->babsite['smtpserver'];
			$mail->mail->Port = $babBody->babsite['smtpport'];
			$mail->mail->SMTPSecure = $babBody->babsite['smtpsecurity'];

			if( $babBody->babsite['smtpuser'] != '' ||  $babBody->babsite['smtppass'] != '')
				{
				$mail->setAuthenticated($babBody->babsite['smtpuser'], $babBody->babsite['smtppass']);
				}
			break;
	}
	return $mail;
}
