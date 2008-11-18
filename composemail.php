<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $babInstallPath.'utilit/mailincl.php';



/**
 * Returns an indexed array with information about the user email account.
 *
 * @param int	$userId
 * 
 * @return array		( 'name' => xxx, 'email' => xxx, 'format' => xxx, '
 */
function getUserEmailAccountInfo($userId = null)
{
	global $babDB;

	if (!isset($userId)) {
		$userId = $GLOBALS['BAB_SESS_USERID'];
	}

	$sql = '
			SELECT * FROM ' . BAB_MAIL_ACCOUNTS_TBL . '
			WHERE owner = ' . $babDB->quote($userId);
//			AND id = ' . $babDB->quote($accid);

	$accounts = $babDB->db_query($sql);

	if ($accounts && ($account = $babDB->db_fetch_assoc($accounts))) {
		
		$this->fromval = "\"".$account['name']."\" &lt;".$account['email']."&gt;";

		if ('html' === $account['format']) {
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
	} else {
		$account = array(
						'name' => $GLOBALS['BAB_SESS_USER'],
						'email' => $GLOBALS['BAB_SESS_EMAIL'],
						'format' => 'plain'
					);
	}
	
	return $account;
}



/**
 * Returns a status page showing the status list.
 *
 * @param array $statusList
 * @return string		The html content of the page.
 */
function bab_emailStatusPage($statusList)
{

	class StatusPageTemplate extends bab_Template
	{
		var $statusList;

		function StatusPageTemplate($statusList)
		{
			$this->statusList = $statusList;
			
			$this->t_mail_sent = bab_translate("Mail sent");
			$this->t_mail_error = bab_translate("Mail error");
		}
		

		function nextStatus()
		{
			if (list(,$this->status) = each($this->statusList)) {
				return true;
			}
			reset($this->statusList);
			return false;
		}
	}


	$statusPageTemplate = new StatusPageTemplate($statusList);
	return bab_printTemplate($statusPageTemplate, 'mail.html', 'statusPage');
}



/**
 * Returns a mail message editor form.
 *
 * @param array		$recipients
 * @param string	$subject
 * @param string	$format			'plain' or 'html'
 * 
 * @return string		The html content of the mail editor form.
 */
function bab_composeEmailEditor($recipients = array(), $subject = '', $message = '', $format = 'plain')
{
	class EmailEditorTemplate extends bab_Template
	{
		var $send;
		var $cancel;
		var $from;
		var $to;
		var $cc;
		var $bcc;
		var $message;
		var $attachments;
		var $fromval;
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
		var $countcl;
		var $rescl;
		var $none;
		var $urlto;
		var $bhtml;
		var $msgerror;


		function EmailEditorTemplate($recipients = array(), $subject = '', $message = '', $format = 'plain')
		{
			global $babDB;

			$this->toval = implode(', ', array_unique($recipients));
			$this->ccval = '';
			$this->bccval = '';
			$this->subjectval = $subject;
			$this->messageval = $message;			
			$this->format = $format;
			
			$this->popup = bab_rp('popup', null);

			$this->t_send = bab_translate("Send");
			$this->t_cancel = bab_translate("Cancel");
			$this->t_from = bab_translate("From");
			$this->t_to = bab_translate("To");
			$this->t_subject = bab_translate("Subject");
			$this->t_message = bab_translate("Message:");
			
			$this->to_field_rows = 1 + count($recipients) / 10;
			
			$account = getUserEmailAccountInfo();
			$this->fromval = '"' . $account['name'] . '" &lt;' . $account['email'] . '&gt;';

			if ($format == 'html') {
				include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
				$editor = new bab_contentEditor('message');
				$editor->setParameters(array('height' => '200'));
				$postedcontent = $editor->getContent();
				
				if (empty($postedcontent)) {
					$editor->setContent($this->messageval);
				}
				$editor->setFormat('html');
				$this->editor = $editor->getEditor();
			}

		}

	}

	$emailEditorTemplate = new EmailEditorTemplate($recipients, $subject, $message, $format);
	return bab_printTemplate($emailEditorTemplate, 'mail.html', 'composeMailEditor');
}

	
include_once $GLOBALS['babInstallPath'] . 'utilit/mailincl.php';





define('BAB_MAIL_DISPATCH_OK',		0);
define('BAB_MAIL_DISPATCH_ERROR',	1);


class bab_MailDispatcher
{
	var $mail;
	var $nbRecipientsByMail;
	
	var $lb;
	var $stack;
	var $debug;
	var $log;
	var $status;
	
	function bab_MailDispatcher()
	{
		$this->mail = bab_mail();

		if (!$this->mail) {
			$GLOBALS['babBody']->addError(bab_translate(""));
			return false;
		}

		if ($GLOBALS['BAB_SESS_LOGGED']) {
			$this->setSender($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
		}

		$this->nbRecipientsByMail = 1;
		$this->lb = "\n";
		$this->stack = array();
		$this->debug = array();
		$this->status = array();
		$this->log = '';
	}



	/**
	 * Sets sender information
	 * 
	 * @param unknown_type $emailAddress
	 * @param unknown_type $name
	 */
	function setSender($emailAddress, $name)
	{
		if (!$this->mail) {
			return false;
		}

		$this->mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
	}



	/**
	 * Defines the data of the email to send.
	 *
	 * @param string	$subject
	 * @param string	$message
	 * @param string	$link
	 * @param string	$linklabel
	 * @param int		$from				User id of sender
	 * 
	 * @return bool
	 */
	function setData($subject, $message, $link = false, $linklabel = '', $from = false)
	{
		if (!$this->mail) {
			return false;
		}

		if (false !== $from) {
			$this->mail->mailFrom(bab_getUserEmail($from), bab_getUserName($from));
		}

		$this->mail->mailSubject($subject);

		$this->message_txt = $message;

		$this->message_html = '<div align="left">' . bab_toHtml($message, BAB_HTML_ALL) . '</div>';
		if (false !== $link) {
			$this->link = true;
			$this->link_txt = $link;
			$this->link_html = bab_toHtml($link);
			$this->linklabel_txt = $linklabel;
			$this->linklabel_html = bab_toHtml($linklabel);
		} else {
			$this->link = false;
		}

		$html = $this->mail->mailTemplate($this->message_html);
//		$html = bab_printTemplate($this, $GLOBALS['babAddonHtmlPath'].'email.html', 'html');
//		$text = bab_printTemplate($this, $GLOBALS['babAddonHtmlPath'].'email.html', 'text');
		$this->mail->mailBody($html, 'text/html');
		$this->mail->mailAltBody($this->message_txt);
		
		return true;
	}



	function attachFile($fname, $realname, $type)
	{
		$this->mail->mailFileAttach($fname, $realname, $type);
	}


	/**
	 * Checks the validity of an email address.
	 *
	 * @static
	 * @param string $email
	 * @return bool
	 */
	function emailAddressIsValid($email)
	{
		return (strpos($email, '@') !== false);
	}



	/**
	 * Defines the list of recipients of the email.
	 *
	 * @param array		$recipients
	 * @param string	$type			'mailTo' or 'mailBcc'
	 * @return bool
	 */
	function mailDestArray($recipients, $type)
	{
		if (!$this->mail
			|| !in_array($type, array('mailTo','mailBcc'))
			|| !is_array($recipients)
			|| count($recipients) == 0) {
			return false;
		}

		$keys = array_keys($recipients);
		foreach ($keys as $key) {
			if (!self::emailAddressIsValid($recipients[$key])) {
				unset($recipients[$key]);
			} else {
				$recipients[$key] = trim($recipients[$key]);
			}
		}

		if (isset($this->stack[$type])) {
			$this->stack[$type] = array_merge($this->stack[$type], $recipients);
		} else {
			$this->stack[$type] = $recipients;
		}
		
		$this->stack[$type] = array_unique($this->stack[$type]);
		
		return true;
	}


	function mail_pop($type)
	{
		for ($i = 0; $i < $this->nbRecipientsByMail; $i++) {
			$mail = array_pop($this->stack[$type]);
			if (!$mail && $i == 0) {
				return false;
			}
			if (!empty($mail)) {
				$this->mail->$type($mail);
			}
		}
		return true;
	}

	function get_gust_recipients()
	{
		$this->mail->clearAllRecipients();
		$out = false;
		$types = array_keys($this->stack);
		foreach ($types as $type) {
			if ($this->mail_pop($type)) {
				$out = true;
			}
		}
		return $out;
	}



	/**
	 * Sends the prepared emails.
	 *
	 * @return bool
	 */
	function send()
	{
		if (!$this->mail) {
			return false;
		}
		$result = true;
		while ($this->get_gust_recipients()) {
			$retry = 0;
			while (true !== $this->mail->send() && $retry < 5) {
				$retry++;
			}

			$dest = $this->mail->mailTo[0];
			if ($retry < 5) {
				$errorStatus = BAB_MAIL_DISPATCH_OK;
			} else {
				$errorStatus = BAB_MAIL_DISPATCH_ERROR;
				$result = false;
			}
			$this->status[] = array('status' => $errorStatus, 'dest' => $dest[0], 'error' => $this->mail->ErrorInfo());
		}

		$this->stack = array();
		return $result;
	}
}



/* main */

$idx = bab_rp('idx', 'compose');
$to = bab_rp('to', array());
$cc = bab_pp('cc', array());
$bcc = bab_pp('bcc', array());
$subject = bab_pp('subject', '');
$message = bab_pp('message', '');
$format = bab_pp('format', 'plain');
$popup = bab_rp('popup', null);


$babBody->title = bab_translate("Send a message by email");
		
if (!bab_userIsloggedin()) {
	$babBody->addError(bab_translate("You must be logged in"));
	if (isset($popup)) {
		$babBody->babPopup('.');
    	die;
	}
}



switch ($idx)
{
	case 'send':
		$mailToRecipients = explode(',', bab_rp('to', ''));
		$mailBccRecipients = explode(',', bab_rp('bcc', ''));
		
		$mail = new bab_MailDispatcher();
		$mail->setData($subject, $message);
		$mail->mailDestArray($mailToRecipients, 'mailTo');
		$mail->mailDestArray($mailBccRecipients, 'mailBcc');
		$mailStatus = $mail->send();
		if ($mailStatus) {
			$babBody->title = bab_translate("Email message sent successfully");
		} else {
			$babBody->title = bab_translate("Problems to send message");
		}

		$statusPage = bab_emailStatusPage($mail->status);

		if (isset($popup)) {
			$babBody->babPopup($statusPage);
	    	die;
		} else {
	    	$babBody->babEcho($statusPage);
		}
		break;

	default:
	case 'compose':
		if (isset($popup)) {
			$babBody->babPopup(bab_composeEmailEditor($to, $subject, $message, $format));
	    	die;
		} else {
	    	$babBody->babEcho(bab_composeEmailEditor($to, $subject, $message, $format));
		}
		break;

}
