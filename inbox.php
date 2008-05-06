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
include_once $babInstallPath.'utilit/inboxincl.php';

define('MAX_MSGROWS', 10);

function listMails($accid, $criteria, $reverse, $start)
	{
	global $babBody;

	class temp
		{
		var $fromurl;
		var $fromname;
		var $subjecturl;
		var $subjectname;
		var $datename;
		var $dateurl;
		var $msgfromurl;
		var $msgfromurlname;
		var $msgsubjecturl;
		var $msgsubjecturlname;
		var $msgdate;
		var $mbox;
		var $count;
		var $uid;
		var $criteria;
		var $reverse;
		var $accid;
		var $db;
		var $countacc;
		var $resacc;
		var $viewthis;
		var $msgid;
		var $start;
		var $burl;
		var $uncheckall;
		var $checkall;
		var $maxrows;
		var $mailboxname;
		var $bunseen;
		var $access;
		var $nbparts = 0;
		var $altbg = true;

		function temp($accid, $criteria, $reverse, $start)
			{
			global $babBody, $babDB, $BAB_SESS_USERID, $BAB_HASH_VAR;
			$this->reverse = $reverse;
			$this->criteria = $criteria;
			$this->accid = $accid;
			$this->count = 0;
			$this->start = $start;
			$this->burl = 0;
			$this->access = "pop3";

			$this->viewthis = bab_translate("View this account");
			$this->fromname = bab_translate("From");
			$this->subjectname = bab_translate("Subject");
			$this->datename = bab_translate("Date");
			$this->uncheckall = bab_translate("Uncheck all mails");
			$this->checkall = bab_translate("Check all mails");
			$this->delete_checked = bab_translate("Delete checked items");
			$this->compose = bab_translate("Compose");

			$this->reverse = $reverse;
			$reverse = !$reverse ? 1 : 0;

			$this->criteria = $criteria;

			$this->fromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTFROM."&reverse=".$reverse;
			$this->subjecturl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTSUBJECT."&reverse=".$reverse;
			$this->dateurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTARRIVAL."&reverse=".$reverse;

			$this->mailcount = 0;
			$this->composeurl = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&criteria=".$criteria."&reverse=".$reverse;

			
			$arr = bab_getMailAccount($accid);

			if($arr)
				{
				$this->maxrows = $arr['maxrows'];
				$this->mailboxname = $arr['account_name'];
				if( empty($accid))
					{
					$this->accid = $arr['id'];
					}
				else
					$this->accid = $accid;
				$this->composeurl .= "&accid=".$this->accid;
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($arr['domain'])."'";
				$res2 = $babDB->db_query($req);
				if( $res2 && $babDB->db_num_rows($res2) > 0 )
					{
					$arr2 = $babDB->db_fetch_array($res2);
					$this->access = $arr2['access'];

					$this->mbox = bab_getMailBox($accid);
					if($this->mbox)
						{
						$this->msguid = imap_sort($this->mbox, $this->criteria, $this->reverse, SE_UID | SE_NOPREFETCH); 
						$this->count = sizeof($this->msguid);
						if( $this->count > 0 && $this->maxrows > 0)
							{
							$this->mailcount = ($this->start + $this->maxrows > $this->count )? $this->count - $this->start+1: $this->maxrows;
							}
						else
							{
							$this->mailcount = $this->count;
							$this->start = 1;
							}
						}
					}
				}
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$this->resacc = $babDB->db_query($req);
			$this->countacc = $babDB->db_num_rows($this->resacc);

			if ($this->countacc == 0)
				{
				$GLOBALS['babBody']->msgerror = bab_translate("No mail account");
				}
				
				
			include_once $GLOBALS['babInstallPath'].'utilit/pagesincl.php';	
			$this->pagination = bab_generatePaginationString($this->count, $this->maxrows, $this->start, 'start');
				
				
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->mailcount)
				{
				$this->altbg = !$this->altbg;
				$this->msgid = $this->msguid[$this->start-1 + $i];
				$headinfo = imap_header($this->mbox, imap_msgno($this->mbox, $this->msgid));
				
				if (isset($headinfo->from)) {
					if( empty($headinfo->from[0]->personal)) {
						$this->msgfromurlname = $headinfo->from[0]->mailbox ."@". $headinfo->from[0]->host;
						}
					else {
						$this->msgfromurlname = $headinfo->from[0]->personal;
						}
				} else {
					$this->msgfromurlname = '';
				}

				$arr = imap_mime_header_decode($this->msgfromurlname);
				$this->msgfromurlname = '';
				
				foreach($arr as $obj)
					$this->msgfromurlname .= $obj->text;

				$this->msgfromurlname = htmlentities($this->msgfromurlname);

				$this->msgfromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=view&accid=".$this->accid."&msg=".$this->msgid."&criteria=".$this->criteria."&reverse=".$this->reverse;
				//$this->msgfromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=view&accid=".$this->accid."&msg=".$this->msgid."&criteria=".$this->criteria."&reverse=".$this->reverse;
				$arr = imap_mime_header_decode($headinfo->subject);
				$this->msgsubjecturlname = htmlentities($arr[0]->text);
				$this->msgsubjecturl = $this->msgfromurl;

				$this->msgdate = bab_shortDate($headinfo->udate);

				$fh = imap_fetchheader($this->mbox, $this->msgid, FT_UID);

				if( $this->access == "imap" && ($headinfo->Unseen == 'U' || $headinfo->Recent == 'N'))
					$this->bunseen = true;
				else
					$this->bunseen = false;
					
				$this->attachment = 0;
				$this->priority = 0;
				$reg = "/Content-Type:\s+([^ ;\n\t]*)/s";
				if( preg_match($reg, $fh, $m) && !empty($m[1]))
					{
					$reg = "/([^\/]*)\/(.*)/s";
					if( preg_match($reg, $m[1], $m))
						{
						if( !empty($m[2]) && strtolower($m[1]) != "text" && strtolower($m[2]) != "alternative")
							{
							$this->attachment = 1;
							}
						}
					}

				$reg = "/X-Priority:\s+([^ (]*)/s";
				if( preg_match($reg, $fh, $m) && !empty($m[1]))
					{
					if( $m[1] == "1")
						{
						$this->priority = 1;
						}
					}

				$i++;
				return true;
				}
			else
				{
				if( $this->mbox )
					imap_close($this->mbox);
				return false;
				}
			}

		function getnextacc()
			{
			global $babDB;
			static $k=0;
			if( $k < $this->countacc)
				{
				$arr = $babDB->db_fetch_array($this->resacc);
				$this->accountname = $arr['account_name'];
				$this->accountid = $arr['id'];
				if( $this->accountid == $this->accid)
					$this->selected = "selected";
				else
					$this->selected = "";
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		}
	$temp = new temp($accid, $criteria, $reverse, $start);
	$babBody->babecho(	bab_printTemplate($temp,"inbox.html", "maillist"));
	return array('count'=> $temp->count, 'accid' => $temp->accid, 'mailbox' => $temp->mailboxname);
	}

function viewMail($accid, $msg, $criteria, $reverse, $start)
	{
	global $babBody;

	class temp
		{
		var $fromname;
		var $subjectname;
		var $datename;
		var $toname;
		var $ccname;
		var $msg;
		var $mbox;
		var $msgbody;
		var $attachment = array();
		var $accid;
		var $arrto = array();
		var $arrfrom = array();
		var $arrcc = array();
		var $addurl;
		var $addname;
		var $addcontact;
		var $babCss;
		var $babMeta;
		var $criteria;
		var $reverse;
		var $start;
		var $toval;

		var $replyname;
		var $replyaname;
		var $forwardname;
		var $replyurl;
		var $replyaurl;
		var $forwardurl;

		function temp($accid, $msg, $criteria, $reverse, $start)
			{
			global $babBody, $babDB, $BAB_HASH_VAR;
			$this->fromname = bab_translate("From");
			$this->subjectname = bab_translate("Subject");
			$this->toname = bab_translate("To");
			$this->ccname = bab_translate("Cc");
			$this->datename = bab_translate("Date");
			$this->attachmentname = bab_translate("Attachments");
			$this->addcontact = bab_translate("Add to contacts");
			$this->replyname = bab_translate("Reply");
			$this->replyaname = bab_translate("Reply to all");
			$this->forwardname = bab_translate("Forward");
			$this->criteria = $criteria;
			$this->reverse = $reverse;
			$this->start = $start;
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");

			$this->replyurl = $GLOBALS['babUrlScript']."?tg=mail&idx=reply&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse."&idreply=".$msg;	$this->replyaurl = $GLOBALS['babUrlScript']."?tg=mail&idx=replyall&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse."&idreply=".$msg."&all=1";
			$this->forwardurl = $GLOBALS['babUrlScript']."?tg=mail&idx=forward&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse."&idreply=".$msg."&all=1&fw=1";

			$this->msg = $msg;
			$this->accid = $accid;
			$this->ccval = "";

			$arr = bab_getMailAccount($accid);
			if( $arr )
				{

				$this->mbox = bab_getMailBox($accid);
				if(!$this->mbox)
					{
					$babBody->msgerror = bab_translate("ERROR"). " : ". imap_last_error();
					}
				else
					{
					$msg = imap_msgno($this->mbox, $msg); 
					$headinfo = imap_header($this->mbox, $msg); 
					$arr = $headinfo->from;
					$this->fromval = '';
					for($i=0; $i < count($arr); $i++)
						{
						if( isset($arr[$i]->personal))
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$mhtext = $mhc[0]->text;
							}
						else
							{
							$mhtext ='';
							}
						$this->fromval .= $mhtext . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
						$this->arrfrom[] = array( $mhtext, $arr[$i]->mailbox . "@" . $arr[$i]->host);
						}

					$arr = isset($headinfo->to) ? $headinfo->to : array();
					$this->toval = '';
					for($i=0; $i < count($arr); $i++)
						{
						if( isset($arr[$i]->personal))
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$mhtext = $mhc[0]->text;
							}
						else
							{
							$mhtext ='';
							}
						$this->toval .= $mhtext . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
						$this->arrto[] = array( $mhtext, $arr[$i]->mailbox . "@" . $arr[$i]->host);
						}

					$arr = isset($headinfo->cc) ? $headinfo->cc : array();
					for($i=0; $i < count($arr); $i++)
						{
						if( isset($arr[$i]->personal))
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$mhtext = $mhc[0]->text;
							}
						else
							{
							$mhtext ='';
							}
						$this->ccval .= $mhtext . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
						$this->arrcc[] = array( $mhtext, $arr[$i]->mailbox . "@" . $arr[$i]->host);
						}

					$mhc = imap_mime_header_decode($headinfo->subject);
					if(empty($mhc[0]->text))
						$this->subjectval = "(".bab_translate("none").")";
					else
						$this->subjectval = htmlentities($mhc[0]->text);
					$this->dateval = bab_strftime($headinfo->udate);

					$this->msgbody = bab_getMimePart($this->mbox, $msg, "TEXT/HTML");
					if(!$this->msgbody)
						{
						$this->msgbody = bab_getMimePart($this->mbox, $msg, "TEXT/PLAIN");
						$this->msgbody= nl2br(htmlentities ( $this->msgbody));
						$this->msgbody = eregi_replace( "((http|https|mailto|ftp):(\/\/)?[^[:space:]<>]{1,})", "<a target='blank' href='\\1'>\\1</a>",$this->msgbody); 
						}
					else
						{
						$this->msgbody = eregi_replace("(src|background)=(['\"])cid:([^'\">]*)(['\"])", "src=\\2".$GLOBALS['babPhpSelf']."?tg=inbox&accid=".$this->accid."&idx=getpart&msg=$msg&cid=\\3\\4", $this->msgbody);
						}
					$this->get_attachment($msg);
					$this->count = count($this->attachment);
					imap_close($this->mbox);
					}
				}

			}

		function getnextattachment()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->attachmenturl = $GLOBALS['babUrlScript']."?tg=inbox&idx=attach&accid=".$this->accid."&msg=".$this->msg."&part=".$this->attachment[$i]['part_number']."&mime=".strtolower($this->attachment[$i]['mime_type']."&enc=".$this->attachment[$i]['encoding']."&file=".urlencode (htmlentities ($this->attachment[$i]['filename'])));
				$this->attachmentval = $this->attachment[$i]['filename'];
				$i++;
				return true;
				}		
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextto()
			{
			static $i = 0;
			if( $i < count($this->arrto))
				{
				$arr = explode(" ", $this->arrto[$i][0]);
				if( count($arr) > 2)
					{
					$lastn = array_pop($arr);
					$firstn = implode( " ", $arr);
					}
				else
					{
					$firstn = isset($arr[0])? $arr[0]: '';
					$lastn = isset($arr[1])? $arr[1]: '';
					}
				$this->addurl = $GLOBALS['babUrlScript']."?tg=contact&idx=create&firstname=".$firstn."&lastname=".$lastn."&email=".$this->arrto[$i][1]."&bliste=0";
				$this->addname = $this->arrto[$i][0]. " &lt;" . $this->arrto[$i][1] . "&gt;";
				$i++;
				return true;
				}		
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextfrom()
			{
			static $i = 0;
			if( $i < count($this->arrfrom))
				{
				$arr = explode(" ", $this->arrfrom[$i][0]);
				if( count($arr) > 2)
					{
					$lastn = array_pop($arr);
					$firstn = implode( " ", $arr);
					}
				else
					{
					$firstn = isset($arr[0]) ? $arr[0] : '';
					$lastn = isset($arr[1]) ? $arr[1] : '';
					}
				$this->addurl = $GLOBALS['babUrlScript']."?tg=contact&idx=create&firstname=".$firstn."&lastname=".$lastn."&email=".$this->arrfrom[$i][1]."&bliste=0";
				$this->addname = $this->arrfrom[$i][0]. " &lt;" . $this->arrfrom[$i][1] . "&gt;";
				$i++;
				return true;
				}		
			else
				{
				$i = 0;
				return false;
				}
			}
		function getnextcc()
			{
			static $i = 0;
			if( $i < count($this->arrcc))
				{
				$arr = explode(" ", $this->arrcc[$i][0]);
				if( count($arr) > 2)
					{
					$lastn = array_pop($arr);
					$firstn = implode( " ", $arr);
					}
				else
					{
					$firstn = isset($arr[0])? $arr[0]: '';
					$lastn = isset($arr[1])? $arr[1]: '';
					}
				$this->addurl = $GLOBALS['babUrlScript']."?tg=contact&idx=create&firstname=".$firstn."&lastname=".$lastn."&email=".$this->arrcc[$i][1]."&bliste=0";
				$this->addname = $this->arrcc[$i][0]. " &lt;" . $this->arrcc[$i][1] . "&gt;";
				$i++;
				return true;
				}		
			else
				{
				$i = 0;
				return false;
				}
			}

		function get_attachment($msg_number, $structure = false, $part_number = false) 
			{ 
			if(!$structure) 
				{
				$structure = imap_fetchstructure($this->mbox, $msg_number); 
				}

			if($structure) 
				{ 
				if($structure->type != 1)
					{
					$disp = isset($structure->disposition) ? strtoupper($structure->disposition) : '';
					if ( $disp == "ATTACHMENT" || ($disp == "INLINE" && !isset($structure->id)) )
						{
						if ($structure->ifdparameters)
							{
							while (list ($Name, $Disposition) = each ($structure->dparameters))
								{
								if (strtoupper ($Disposition->attribute) == "FILENAME" )
									{
									$filename = $Disposition->value;
									}
								}
							}

						if ($structure->ifdparameters)
							{
							while (list ($Name, $Disposition) = each ($structure->dparameters))
								{
								if (strtoupper ($Disposition->attribute) == "NAME" )
									{
									$filename = $Disposition->value;
									}
								}
							}

						if (!is_string($filename))
							{
							$filename = "untitled." . strtolower($structure->subtype);
							}
						/*
						if( !$part_number )
							$part_number = "1";
						*/
						$this->attachment[] = array( "part_number" => urlencode (htmlentities ($part_number))
													,"filename" => $filename
													,"encoding" => urlencode (htmlentities ($structure->encoding))
													,"mime_type" => urlencode (htmlentities (bab_getMimeType($structure->type, $structure->subtype))));
						}
					}
				else
					{ 
					while(list($index, $sub_structure) = each($structure->parts)) 
						{ 
						if($part_number) 
							{ 
							$prefix = $part_number . '.';
							}
						else
							$prefix = '';
						$this->get_attachment($msg_number, $sub_structure, $prefix . ($index + 1));
						} 
					} 
					
				}
			}
		}
	$temp = new temp($accid, $msg, $criteria, $reverse, $start);
	echo bab_printTemplate($temp,"inbox.html", "mailview");
	}



/**
 * Get the text part of the message with mime type
 *
 * @param	ressource	$mbox			An IMAP stream returned by imap_open().
 * @param	int			$msg_number		The message number 
 * @param	int			$cid			Identification string for the message part
 * @param	boolean		[$structure]	Used internaly for the sub structures
 * @param	boolean		[$part_number]	Used internaly for the sub structures
 *
 * @return	array
 */
function get_cid_part($mbox, $msg_number, $cid, $structure = false, $part_number = false) 
	{
	if(!$structure) 
		{
		$structure = imap_fetchstructure($mbox, $msg_number); 
		}

	if($structure) 
		{ 
		if (isset($structure->id) && $cid == $structure->id)
			{

			if(!$part_number)
				{
				$part_number = "1"; 
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

			return array($text, bab_getMimeType ($structure->type, $structure->subtype)); 
			}
		if($structure->type == 1) /* multipart */ 
			{ 
			$prefix = '';
			while(list($index, $sub_structure) = each($structure->parts)) 
				{ 
				if($part_number) 
					{ 
					$prefix = $part_number . '.';
					}
				$data = get_cid_part($mbox, $msg_number, $cid, $sub_structure, $prefix . ($index + 1)); 
				if($data) 
					{
					return $data; 
					} 
				} 
			} 
		} 
	return false; 
	}


/**
 * Print the text part of a message with the header
 *
 * @param	int		$accid	The mail account ID
 * @param	int		$msg	The message number 
 * @param	string	$cid	Identification string for the message part
 *
 */
function showPart($accid, $msg, $cid)
	{
	$mbox = bab_getMailBox($accid);
	if($mbox)
		{
		$data = get_cid_part ($mbox, $msg, "<" . $cid . ">");
		imap_close ($mbox);
		header ("Content-Type: " . strtolower ($data[1])); 
		echo $data[0];
		exit;
		}
	}


/**
 * Print an attachement part of a message with header
 *
 * @param	int		$accid	The mail account ID
 * @param	int		$msg	The message number 
 * @param	int		$part	The part number. It is a string of integers delimited by period which index into a body part list as per the IMAP4 specification 
 * @param	string	$mime	mime type for attachement
 * @param	string	$file	filename for attachement
 *
 */
function getAttachment($accid, $msg, $part, $mime, $enc, $file)
	{
	$mbox = bab_getMailBox($accid);
	if($mbox)
		{
		$structure = imap_fetchstructure($mbox, $msg, FT_UID);
		$text = imap_fetchbody($mbox, $msg, $part, FT_UID);
		imap_close($mbox);
		
		if( $enc == 3)
			$text =  imap_base64 ($text);
		else if ($enc == 4)
			$text = imap_qprint ($text);

		if( strtolower(bab_browserAgent()) == "msie")
			header('Cache-Control: public');

		header("Content-Type: " . $mime);
		header("Content-Disposition: attachment; filename=\"".$file."\"");
		echo $text;
		exit;
		}
	}

function deleteMails($item, $accid, $criteria, $reverse)
	{
	$mbox = bab_getMailBox($accid);
	if($mbox)
		{
		for($i=0; $i < count($item); $i++)
			imap_delete($mbox, $item[$i], FT_UID);
		imap_expunge($mbox);
		imap_close($mbox);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		}
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if (!function_exists('imap_open'))
	{
	$babBody->msgerror = bab_translate("Server must have imap functions on");
	return;
	}


$criteria = bab_rp('criteria',SORTARRIVAL);
$accid = bab_rp('accid');
$reverse = bab_rp('reverse',1);
$start = bab_rp('start',1);
if(empty($start)) {
	$start=1;
}

if( isset($viewacc) && $viewacc == "view")
{
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse."&start=".$start);
}

switch($idx)
	{
	case "attach":
		getAttachment($accid, $msg, $part, $mime, $enc, $file);
		exit;
		break;

	case "getpart":
		showPart($accid,$msg, $cid);
		break;

	case "delete":
		deleteMails($item, $accid, $criteria, $reverse);
		break;

	case "deletem":
		deleteMails(array(0 =>$msg), $accid, $criteria, $reverse);
		break;

	case "view":
		$babBody->title = bab_translate("Email");
		viewMail($accid, $msg, $criteria, $reverse, $start);
		exit;
		break;

	default:
	case "refresh":
		$idx = "list";
		/* no break */
	case "list":
		$nbm = listMails($accid, $criteria, $reverse, $start);
	    $babBody->title = $nbm['mailbox']. " : ". $nbm['count']." ".bab_translate("Message")."(s)";
		$accid = $nbm['accid'];
		$babBody->addItemMenu("list", bab_translate("Inbox"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$babBody->addItemMenu("refresh", bab_translate("Refresh"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>