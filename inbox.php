<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/mailincl.php";

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

		function temp($accid, $criteria, $reverse, $start)
			{
			global $babBody, $BAB_SESS_USERID, $BAB_HASH_VAR;
			$this->reverse = $reverse;
			$this->criteria = $criteria;
			$this->accid = $accid;
			$this->count = 0;
			$this->start = $start;
			$this->burl = 0;

			$this->viewthis = bab_translate("View this account");
			$this->fromname = bab_translate("From");
			$this->subjectname = bab_translate("Subject");
			$this->datename = bab_translate("Date");
			$this->uncheckall = bab_translate("Uncheck all mails");
			$this->checkall = bab_translate("Check all mails");
			$this->deletealt = bab_translate("Delete");
			$this->compose = bab_translate("Compose");

			if( $reverse )
				{
				$reverse = 0;
				switch ($criteria)
					{
					case SORTFROM:
						$this->fromname .= " v";
						break;
					case SORTARRIVAL:
						$this->datename .= " v";
						break;
					case SORTSUBJECT:
						$this->subjectname .= " v";
						break;
					}
				}
			else
				{
				$reverse = 1;
				switch ($criteria)
					{
					case SORTFROM:
						$this->fromname .= " ^";
						break;
					case SORTARRIVAL:
						$this->datename .= " ^";
						break;
					case SORTSUBJECT:
						$this->subjectname .= " ^";
						break;
					}
				}
			$this->fromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTFROM."&reverse=".$reverse;
			$this->subjecturl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTSUBJECT."&reverse=".$reverse;
			$this->dateurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".SORTARRIVAL."&reverse=".$reverse;

			$this->mailcount = 0;
			$this->db = $GLOBALS['babDB'];
			if( empty($accid))
				{
				$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and prefered='Y'";
				$res = $this->db->db_query($req);
				if( !$res || $this->db->db_num_rows($res) == 0 )
					{
					$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."'";
					}
				}
			else
				$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where id='".$accid."' and owner='".$BAB_SESS_USERID."'";
			$this->composeurl = $GLOBALS['babUrlScript']."?tg=mail&idx=compose&criteria=".$criteria."&reverse=".$reverse;

			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0 )
				{
				$arr = $this->db->db_fetch_array($res);
				$this->maxrows = $arr['maxrows'];
				$this->mailboxname = $arr['account'];
				if( empty($accid))
					{
					$this->accid = $arr['id'];
					}
				else
					$this->accid = $accid;
				$this->composeurl .= "&accid=".$this->accid;
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
				$res2 = $this->db->db_query($req);
				if( $res2 && $this->db->db_num_rows($res2) > 0 )
					{
					$arr2 = $this->db->db_fetch_array($res2);
					$cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
					$this->mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
					if(!$this->mbox)
						{
						$babBody->msgerror = bab_translate("ERROR"). " : ". imap_last_error();
						}
					else
						{
						$this->msguid = imap_sort($this->mbox, $this->criteria, $this->reverse, SE_UID | SE_NOPREFETCH); 
						$this->count = sizeof($this->msguid);
						if( $this->count > 0 && $this->maxrows > 0)
							{
							$this->nbparts = intval($this->count / $this->maxrows);
							if( $this->nbparts != 0  && $this->nbparts * $this->maxrows < $this->count)
								$this->nbparts++;

							$this->mailcount = ($this->start + $this->maxrows > $this->count )? $this->count - $this->start+1: $this->maxrows;
							}
						else
							{
							$this->mailcount = $this->count;
							$this->nbparts = 0;
							$this->start = 1;
							}
						}
					}
				}
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."'";
			$this->resacc = $this->db->db_query($req);
			$this->countacc = $this->db->db_num_rows($this->resacc);
			}

		function getnextpart()
			{
			static $k = 0;
			if( $k < $this->nbparts)
				{
				$start = ($k*$this->maxrows)+1;
				if( $start == $this->start )
					$this->burl = 1;
				else
					$this->burl = 0;
				$this->partname = "[".$start."-";
				if((($k+1)*$this->maxrows) > $this->count)
					$this->partname .= $this->count;
				else
					$this->partname .= (($k+1)*$this->maxrows);
				$this->partname .= "]";
				$this->parturl = $GLOBALS['babUrlScript']."?tg=inbox&idx=list&accid=".$this->accid."&criteria=".$this->criteria."&reverse=".$this->reverse."&start=".$start;
				$k++;
				return true;
				}
			else
				return false;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->mailcount)
				{
				$this->msgid = $this->msguid[$this->start-1 + $i];
				$headinfo = imap_header($this->mbox, imap_msgno($this->mbox, $this->msgid));
				if( empty($headinfo->from[0]->personal))
					$this->msgfromurlname = $headinfo->from[0]->mailbox ."@". $headinfo->from[0]->host;
				else
					$this->msgfromurlname = $headinfo->from[0]->personal;
				$arr = imap_mime_header_decode($this->msgfromurlname);
				$this->msgfromurlname = htmlentities($arr[0]->text);
				$this->msgfromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=view&accid=".$this->accid."&msg=".$this->msgid."&criteria=".$this->criteria."&reverse=".$this->reverse;
				//$this->msgfromurl = $GLOBALS['babUrlScript']."?tg=inbox&idx=view&accid=".$this->accid."&msg=".$this->msgid."&criteria=".$this->criteria."&reverse=".$this->reverse;
				$arr = imap_mime_header_decode($headinfo->subject);
				$this->msgsubjecturlname = htmlentities($arr[0]->text);
				$this->msgsubjecturl = $this->msgfromurl;

				$this->msgdate = bab_strftime($headinfo->udate);

				$fh = imap_fetchheader($this->mbox, $this->msgid, FT_UID);

				$this->attachment = 0;
				$this->priority = 0;
				$reg = "/Content-Type:\s+([^;]*)/s";
				if( preg_match($reg, $fh, $m) && !empty($m[1]))
					{
					$reg = "/([^\/]*)\/(.*)/s";
					if( preg_match($reg, $m[1], $m))
						{
                        //echo "m1 = ". $m[1]."m2 = ". $m[2]."<br>";
						if( strtolower($m[1]) != "text" && strtolower($m[2]) != "alternative")
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
			static $k=0;
			if( $k < $this->countacc)
				{
				$arr = $this->db->db_fetch_array($this->resacc);
				$this->accountname = $arr['account'];
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

		var $replyname;
		var $replyaname;
		var $forwardname;
		var $replyurl;
		var $replyaurl;
		var $forwardurl;

		function temp($accid, $msg, $criteria, $reverse, $start)
			{
			global $babBody, $BAB_HASH_VAR;
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

			$db = $GLOBALS['babDB'];
			$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where id='".$accid."'";
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
					$this->mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
					if(!$this->mbox)
						{
						$babBody->msgerror = bab_translate("ERROR"). " : ". imap_last_error();
						}
					else
						{
						$headinfo = imap_header($this->mbox, imap_msgno($this->mbox, $msg));
						$arr = $headinfo->from;
						for($i=0; $i < count($arr); $i++)
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$this->fromval .= $mhc[0]->text . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
							$this->arrfrom[] = array( $mhc[0]->text, $arr[$i]->mailbox . "@" . $arr[$i]->host);
							}

						$arr = $headinfo->to;
						for($i=0; $i < count($arr); $i++)
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$this->toval .= $mhc[0]->text . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
							$this->arrto[] = array( $mhc[0]->text, $arr[$i]->mailbox . "@" . $arr[$i]->host);
							}

						$arr = $headinfo->cc;
						for($i=0; $i < count($arr); $i++)
							{
							$mhc = imap_mime_header_decode($arr[$i]->personal);
							$this->ccval .= $mhc[0]->text . " &lt;" . $arr[$i]->mailbox . "@" . $arr[$i]->host . "&gt;<br>";
							$this->arrcc[] = array( $mhc[0]->text, $arr[$i]->mailbox . "@" . $arr[$i]->host);
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

			}

		function getnextattachment()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->attachmenturl = $GLOBALS['babUrlScript']."?tg=inbox&idx=attach&accid=".$this->accid."&msg=".$this->msg."&part=".$this->attachment[$i]['part_number']."&mime=".strtolower($this->attachment[$i]['mime_type']."&enc=".$this->attachment[$i]['encoding']."&file=".$this->attachment[$i]['filename']);
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
					$firstn = $arr[0];
					$lastn = $arr[1];
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
					$firstn = $arr[0];
					$lastn = $arr[1];
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
					$firstn = $arr[0];
					$lastn = $arr[1];
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
					$disp = strtoupper ($structure->disposition);
					if ( $disp == "ATTACHMENT")
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
													,"filename" => urlencode (htmlentities ($filename))
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
						$this->get_attachment($msg_number, $sub_structure, $prefix . ($index + 1));
						} 
					} 
					
				}
			}
		}
	$temp = new temp($accid, $msg, $criteria, $reverse, $start);
	echo bab_printTemplate($temp,"inbox.html", "mailview");
	}

function get_cid_part($mbox, $msg_number, $cid, $structure = false, $part_number = false) 
	{
	if(!$structure) 
		{
		$structure = imap_fetchstructure($mbox, $msg_number); 
		}

	if($structure) 
		{ 
		if ($cid == $structure->id)
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


function showPart($accid, $msg, $cid)
	{
		global $BAB_HASH_VAR;
	$db = $GLOBALS['babDB'];
	$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res)> 0)
		{
		$arr = $db->db_fetch_array($res);
		$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
		$res2 = $db->db_query($req);
		if( $res2 && $db->db_num_rows($res2)> 0)
			{
			$arr2 = $db->db_fetch_array($res2);
			$cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
			$mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
			if($mbox)
				{
				$data = get_cid_part ($mbox, $msg, "<" . $cid . ">");
				imap_close ($mbox);
				header ("Content-Type: " . strtolower ($data[1])); 
				echo $data[0];
				exit;
				}
			else
				return;
			}
		}
	}

function getAttachment($accid, $msg, $part, $mime, $enc, $file)
	{
	global $BAB_SESS_USERID, $BAB_HASH_VAR;

	$db = $GLOBALS['babDB'];
	$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res)> 0)
		{
		$arr = $db->db_fetch_array($res);
		$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
		$res2 = $db->db_query($req);
		if( $res2 && $db->db_num_rows($res2)> 0)
			{
			$arr2 = $db->db_fetch_array($res2);
			$cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
			$mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
			if($mbox)
				{
            	$structure = imap_fetchstructure($mbox, $msg, FT_UID);
				$text = imap_fetchbody($mbox, $msg, $part);
				imap_close($mbox);
				/*
            	if (eregi("([0-9\.]*)\.([0-9\.]*)", $part, $m))
                    {
                    $idx = $m[2] - 1;
                    }
                else
                    {
                    $idx = 0;
                    }

                if( $idx )
                    {
            		$msgpart = $structure->parts[$idx];
                    }
                else
                    {
            		$msgpart = $structure->parts[$part - 1];
                    }
                
                $filename = "unknown";
                for ($i=0; $i < count($msgpart->dparameters); $i++)
                    {
                    if (eregi("filename", $msgpart->dparameters[$i]->attribute))
                        {
                        $filename = $msgpart->dparameters[$i]->value;
                        }
                    }
				*/
				if( $enc == 3)
					$text =  imap_base64 ($text);
				else if ($enc == 4)
					$text = imap_qprint ($text);
				header("Content-Type: " . $mime);
				header("Content-Disposition: attachment; filename=\"".$file."\"");
				echo $text;
				exit;
				}
			else
				return;
			}
		}
	}

function deleteMails($item, $accid, $criteria, $reverse)
	{
	global $BAB_SESS_USERID, $BAB_HASH_VAR;

	$db = $GLOBALS['babDB'];
	$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$accid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res)> 0)
		{
		$arr = $db->db_fetch_array($res);
		$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
		$res2 = $db->db_query($req);
		if( $res2 && $db->db_num_rows($res2)> 0)
			{
			$arr2 = $db->db_fetch_array($res2);
			$cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
			$mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
			if($mbox)
				{
				for($i=0; $i < count($item); $i++)
					imap_delete($mbox, $item[$i], FT_UID);
				imap_expunge($mbox);
				imap_close($mbox);
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
				}
			else
				return;
			}
		}
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if( !isset($criteria))
	$criteria=SORTARRIVAL;

if( !isset($accid))
	$accid="";

if( !isset($reverse))
	$reverse=1;

if( !isset($start) || empty($start))
	$start=1;

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
		showPart($msg, $cid);
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
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		$babBody->addItemMenu("refresh", bab_translate("Refresh"), $GLOBALS['babUrlScript']."?tg=inbox&accid=".$accid."&criteria=".$criteria."&reverse=".$reverse);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>