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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';

function mailspool() {
	global $babBody;
	class temp
		{
		var $altbg = true;

		function temp()
			{
			$this->t_mail_subject = bab_translate("Mail subject");
			$this->t_mail_date = bab_translate("Date");
			$this->t_send = bab_translate("Send");
			$this->t_delete = bab_translate("Delete");
			$this->checkall = bab_translate("Check all");
			$this->uncheckall = bab_translate("Uncheck all");

			$this->db = $GLOBALS['babDB'];
			

			$this->res = $this->db->db_query("
			SELECT 
				id,
				mail_subject, 
				UNIX_TIMESTAMP(mail_date) mail_date,
				error_msg 
				FROM ".BAB_MAIL_SPOOLER_TBL." 
				ORDER BY mail_date DESC
				");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			if ($arr = $this->db->db_fetch_assoc($this->res)) {
				$this->id = $arr['id'];
				$this->mail_subject = bab_toHtml($arr['mail_subject']);
				$this->mail_date = bab_toHtml(bab_longDate($arr['mail_date']));
				$this->error_msg = bab_toHtml($arr['error_msg']);
				return true;
			}
			return false;
		}
	}

	$temp = new temp();
	$babBody->babecho(bab_printTemplate($temp, "mailspool.html", "list"));
}



function addRecipient(&$mail, $type, $arr) {
	$function = 'mail'.$type;

	foreach($arr as $recipient) {
		if (isset($recipient[1])) { /* name of recipient */
			/* Add email only if it's not empty */
			if (!empty($recipient[0])) {
				$mail->$function($recipient[0], $recipient[1]);
			}
		} else {
			/* Add email only if it's not empty */
			if (!empty($recipient[0])) {
				$mail->$function($recipient[0]);
			}
		}
	}
}


function deleteEmail($arr) {
	
	global $babDB;
	
	$data = unserialize($arr['mail_data']);
		
	foreach($data['files'] as $file) {
		if (is_file($file[0]) && is_writable($file[0])) {
			unlink($file[0]);
		}
	}
	
	$babDB->db_query('
		DELETE FROM '.BAB_MAIL_SPOOLER_TBL.' WHERE id='.$babDB->quote($arr['id']).'
	');
}


function send_checked_mail() {

	include_once $GLOBALS['babInstallPath']."utilit/mailincl.php";

	$db = $GLOBALS['babDB'];
	$mail = bab_rp('mail', false);
	if ($mail) {

		$mail_obj = bab_mail();

		if (!$mail_obj) {
			$GLOBALS['babBody']->msgerror = bab_translate("Mail is not configured");
			return false;
		}

		$res = $db->db_query("
			SELECT * FROM ".BAB_MAIL_SPOOLER_TBL." WHERE id IN('".implode("','",$mail)."')
		");

		while ($arr = $db->db_fetch_assoc($res)) {
			
			$mail_obj->clearAllRecipients();
			$mail_obj->clearReplyTo();
			
			/* $arr['mail_data'] was serialized : 
			 * $data = array(
					'from'		=> array($this->mail->From, $this->mail->FromName),
					'sender'	=> $this->mail->Sender,
					'to'		=> $this->mailTo,
					'cc'		=> $this->mailCc,
					'bcc'		=> $this->mailBcc,
					'files'		=> $this->attachements
				); 
				$data = serialize($data);
			 */
			$data = unserialize($arr['mail_data']);

			if (isset($data['from'])) {
				if (isset($data['from'][1])) {
					$mail_obj->mailFrom($data['from'][0], $data['from'][1]);
				} else {
					$mail_obj->mailFrom($data['from'][0]);
				}
			}

			$mail_obj->mailSender($data['sender']);

			addRecipient($mail_obj, 'To', $data['to']);
			addRecipient($mail_obj, 'Cc', $data['cc']);
			addRecipient($mail_obj, 'Bcc', $data['bcc']);

			$mail_obj->mailSubject($arr['mail_subject']);
			$mail_obj->mailBody($arr['body'], $arr['format']);
			$mail_obj->mailAltBody($arr['altbody']);

			foreach($data['files'] as $file) {
				if (is_file($file[0]))
				{
					$mail_obj->mailFileAttach($file[0], $file[1], $file[2]);
				}
			}
			
			
			
			
			if (!$mail_obj->send()) {
				$GLOBALS['babBody']->msgerror = bab_translate("Mail server error");
			}
			
			$mail_obj->mailClearAttachments();
			
			deleteEmail($arr);
			
		}
	}
}



function delete_checked_mail() {
	global $babDB;

	$mail = bab_rp('mail', false);
	if ($mail) {
	
		$res = $babDB->db_query('
			SELECT * FROM '.BAB_MAIL_SPOOLER_TBL.' WHERE id IN('.$babDB->quote($mail).')
		');
		
		while ($arr = $babDB->db_fetch_assoc($res)) {
			deleteEmail($arr);
		}

		
	}
}



/* main */

if( !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$item = bab_rp('item', 1); /* id site */

if (isset($_POST['send'])) {
	send_checked_mail();
}

if (isset($_POST['delete'])) {
	delete_checked_mail();
}


switch($idx)
	{

	case "list":
		mailspool();
		$babBody->title = bab_translate("Undelivered mails");
		$babBody->addItemMenu("modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=site&idx=menu2&item=".$item);
		$babBody->addItemMenu("list", bab_translate("Mails"), $GLOBALS['babUrlScript']."?tg=mailspool&idx=list");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
