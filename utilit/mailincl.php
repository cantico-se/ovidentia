<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
define('CRLF', "\r\n");

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
	var $bhead = array();
	var $fromemail;
	var $fromname;
	var $to = array();
	var $cc = array();
	var $bcc = array();
	var $toname = array();
	var $ccname = array();
	var $bccname = array();
	var $subject;
	var $parts = array();
	var $add_headers;
	var $message;
	var $babBody;
	var $format;

	function addHeadder($arg, $val)
	{
		if( !empty($arg))
		{
			$this->bhead[] = array($arg, $val);
		}
	}

	function mailFrom($email, $name='')
	{
		$this->fromemail = $email;
		$this->fromname = $name;
	}

	function mailTo($email, $name="")
	{
		$this->to[] = $email;
		if(!empty($name))
			$this->toname[] = $name . " " . "<".$email.">";
		else
			$this->toname[] = $email;
	}

	function mailCc($email, $name="")
	{
		$this->cc[] = $email;
		if(!empty($name))
			$this->ccname[] = $name . " " . "<".$email.">";
		else
			$this->ccname[] = $email;
	}
	
	function mailBcc($email, $name="")
	{
		$this->bcc[] = $email;
		if(!empty($name))
			$this->bccname[] = $name . " " . "<".$email.">";
		else
			$this->bccname[] = $email;
	}

	function mailSubject($subject)
	{
		$this->subject = $subject;
	}

	function mailBody($babBody, $format="plain")
	{
		$this->body = $babBody;
		$this->format = $format;
	}

	function mailFileAttach( $fname, $realname, $type )
	{
		if (!file_exists($fname) || !is_uploaded_file($fname))
			{
			echo "file does'nt exist";
			return; 
			}

		$fp = fopen($fname, "r");
		if (!$fp)
			{
			echo "Cannot open the file";
			return;
			}
	
		$data = fread($fp, filesize($fname));

		if (eregi("([^ ]*)/([^ ]*)", $type, $regs))
			{
			$mime1 = $regs[1];
			$mime2 = $regs[2];
			}

		switch (strtolower($mime1))
			{
			case "video":
			case "image":
			case "audio":
			case "application":
				$encoding = "base64";
				$data = base64_encode($data);
				break;
			case "text":
				$encoding = "quoted_printable";
				$data = imap_8bit($data);
				break;
			case "message":
			case "multipart": 
			default:
				$encoding = "";
				break;
			}

		$msg = chunk_split($data);
		$this->parts[] = array(	'contenttype' => $type,
								'encoding' => $encoding,
								'charset' => "",
								'disposition' => "attachment;".CRLF."\tfilename=\"".$realname."\"",
								'description' => "",
								'name' => $realname,
								'data' => $msg );

	}

	function mailBuildPart($part)
	{
		$msgpart = "";
		$msgpart = "Content-Type: " . $part['contenttype'].";";
		if( !empty($part['charset']))
		{
			$msgpart .= "charset= " . $part['charset'];
		}

		if( !empty($part['name']))
		{
			$msgpart .= CRLF."\tname=\"" . $part['name']. "\"";
		}

		$msgpart .= CRLF. "Content-Transfer-Encoding: " . $part['encoding'];
		if( !empty($part['description']))
		{
			$msgpart .= CRLF. "Content-Description: " . $part['description'];
		}

		if( !empty($part['disposition']))
		{
			$msgpart .= CRLF. "Content-Disposition: " . $part['disposition'];
		}

		$msgpart .= CRLF. CRLF. $part['data']. CRLF;
		return $msgpart;
	}	

	function mailBuildMessage()
	{
		$mail = "";
		$boundary = 'NP'.md5(uniqid(time()));

		if( empty($this->format))
			$format = "text/plain";
		else
			$format = "text/".$this->format;

		$nbparts = sizeof( $this->parts);
		if( $nbparts > 0 || $this->format == "html")
		{
			$this->add_headers .= 'MIME-Version: 1.0' . CRLF;
            if( $nbparts > 0 )
			    $this->add_headers .= 'Content-Type: multipart/mixed;'.CRLF;
            else
			    $this->add_headers .= 'Content-Type: multipart/alternative;'.CRLF;
			$this->add_headers .= "\tboundary=\"$boundary\"" . CRLF;
			$mail .= CRLF.'This is a MIME encoded message';

			if( !empty($this->body))
			{
				$bodypart = array( 'contenttype' => $format,
									'encoding' => 'quoted_printable',
									'charset' => 'iso-8859-1',
									'disposition' => '',
									'description' => '',
									'name' => '',
									'data' => $this->body );
				$mail .= CRLF.CRLF."--". $boundary. CRLF. $this->mailBuildPart($bodypart).CRLF;

			}
			for($i = 0; $i < $nbparts; $i++)
				$mail .= "--". $boundary. CRLF. $this->mailBuildPart($this->parts[$i]).CRLF;

			$mail .= "--". $boundary. "--".CRLF;
		}
		else if( !empty($this->body))
		{
			$mail = $this->body.CRLF.CRLF;
		}
		return $mail;
	}

	function mailBuild()
	{
		$this->add_headers= "";
		if( !empty($this->fromemail))
			{
			if( !empty($this->fromname))
				$from = sprintf("%s <%s>", $this->fromname, $this->fromemail);
			else
				$from = $this->fromemail;
			$this->add_headers .= "From: " . $from . CRLF;
			}
		$this->add_headers .= "To: " . join(", ", $this->toname) . CRLF;
		if( count($this->cc) > 0)
			$this->add_headers .= "Cc: " . join(", ", $this->ccname) . CRLF;
		if( count($this->bcc) > 0)
			$this->add_headers .= "Bcc: " . join(", ", $this->bccname) . CRLF;
		if( !empty($this->subject))
			$this->add_headers .= "Subject: ".$this->subject . CRLF;
		else
			$this->add_headers .= "Subject: (No Subject)".$this->subject . CRLF;
        $counth = count($this->bhead);
		if( $counth > 0)
		{
			for( $i = 0; $i < $counth; $i++)
			{
				$this->add_headers .= $this->bhead[$i][0] . ": " . $this->bhead[$i][1] . CRLF;
			}
		}
		$this->message .= $this->mailBuildMessage();
	}

	function send()
	{
        $this->mailBuild();
		return mail(join(', ', $this->to), $this->subject, $this->message, $this->add_headers);
	}
}


class babMailSmtp extends babMail
{
	var $server;
	var $port;
	var $smtp;

	function babMailSmtp($server, $port)
	{
		$this->server = $server;
		$this->port = $port;
	}

	function open()
	{
		$this->smtp = fsockopen($this->server, $this->port); 
        if ($this->smtp < 0)
			return 0; 
        $line = fgets($this->smtp, 1024);
		if( substr($line, 0, 1) != 2)
			return 0;

		fputs($this->smtp,"HELO ".$this->server.CRLF);
        $line = fgets($this->smtp, 1024);
		if( substr($line, 0, 1) != 2)
			return 0;

		return $this->smtp;
	}

	function close()
	{
		fclose($this->smtp);
	}

	function send()
	{
		if( !$this->open())
		{
			return 0;
		}

		$data = "MAIL FROM: <".$this->fromemail.">".CRLF;
		fputs($this->smtp,$data);
        $line = fgets($this->smtp, 1024);
		if( substr($line, 0, 1) != 2)
		{
			$this->close();
			return 0;
		}

		for($i=0; $i < count($this->to); $i++)
			{
				$data = "RCPT TO: <".$this->to[$i].">".CRLF;
				fputs($this->smtp, $data);
				$line = fgets($this->smtp, 1024);
				if( substr($line, 0, 1) != 2)
				{
					$this->close();
					return 0;
				}
			}

		for($i=0; $i < count($this->cc); $i++)
			{
				$data = "RCPT TO: <".$this->cc[$i].">".CRLF;
				fputs($this->smtp, $data);
				$line = fgets($this->smtp, 1024);
				if( substr($line, 0, 1) != 2)
				{
					$this->close();
					return 0;
				}
			}

		for($i=0; $i < count($this->bcc); $i++)
			{
				$data = "RCPT TO: <".$this->bcc[$i].">".CRLF;
				fputs($this->smtp, $data);
				$line = fgets($this->smtp, 1024);
				if( substr($line, 0, 1) != 2)
				{
					$this->close();
					return 0;
				}
			}

		fputs($this->smtp,"DATA".CRLF);
        $line = fgets($this->smtp, 1024);
		if( substr($line, 0, 1) != 3)
		{
			$this->close();
			return 0;
		}

		$this->mailBuild();
		fputs($this->smtp, $this->add_headers.CRLF.CRLF.$this->message);
		fputs($this->smtp, CRLF.".".CRLF);
        $line = fgets($this->smtp, 1024);
		if( substr($line, 0, 1) != 2)
			return 0;
		fputs($this->smtp, "QUIT".CRLF);
        $line = fgets($this->smtp, 1024);
		$this->close();
		return 1;
	}

}


class babMailInfo extends babMail
{
    var $file;
    var $section;
    var $mailtitle;

    function babMailInfo($title, $file, $section="")
        {
        $this->file = $file;
        $this->section = $section;
        $this->mailtitle = $title;
        }

    function send()
        {
        $msg = bab_printTemplate($this,$this->file, $this->section);
        $this->mailBody($msg, "html");
        $this->mailBuild();
		return mail(join(', ', $this->to), $this->subject, $this->message, $this->add_headers);
        }

}
?>