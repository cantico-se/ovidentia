<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function getDomainName($id)
	{
	$db = new db_mysql();
	$req = "select * from mail_domains where id='$id'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[name];
		}
	else
		{
		return "";
		}
	}

function getAccountAccount($id)
	{
	$db = new db_mysql();
	$req = "select * from mail_accounts where id='$id'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[account];
		}
	else
		{
		return "";
		}
	}

function accountsList()
	{
	global $body;
	class temp
		{
		var $name;
		var $url;
		var $domname;
		var $email;
		var $accname;
		var $prefered;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			global $BAB_SESS_USERID;
			$this->name = babTranslate("Name");
			$this->email = babTranslate("Email");
			$this->accname = babTranslate("Account");
			$this->domname = babTranslate("Domain");
			$this->db = new db_mysql();
			$this->count = 0;
			$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}
			
		function getnext()
			{
			global $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->domnameval = getDomainName($this->arr[domain]);
				$this->url = $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modacc&item=".$this->arr[id];
				if( $this->arr[prefered] == "Y")
					$this->prefered = "P";
				else
					$this->prefered = "";
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

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "mailopt.html", "accountslist"));
	}

function accountCreate()
	{
	global $body;
	class temp
		{
		var $fullname;
		var $account;
		var $email;
		var $domain;
		var $password;
		var $repassword;
		var $addacc;
		var $prefaccount;
		var $prefformat;
		var $maxrows;
		var $yes;
		var $no;
		var $plain;
		var $html;

		var $username;
		var $useremail;

		var $db;
		var $resadm;
		var $countadm;
		var $resgrp;
		var $countgrp;
		var $resusr;
		var $countusr;
		var $domname;
		var $domid;

		function temp()
			{
			global $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = babTranslate("User Name");
			$this->account = babTranslate("Account");
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->repassword = babTranslate("Retype Password");
			$this->domain = babTranslate("Domain");
			$this->prefaccount = babTranslate("Prefered account");
			$this->prefformat = babTranslate("Prefered format");
			$this->maxrows = babTranslate("Default number of messages to display per screen");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->plain = babTranslate("Plain text");
			$this->html = babTranslate("Html");
			$this->addacc = babTranslate("Add Account");
			$this->username = $BAB_SESS_USER;
			$this->useremail = $BAB_SESS_EMAIL;
			$this->db = new db_mysql();
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$req = "select * from mail_domains where bgroup='Y' and owner='1'";
			$this->resadm = $this->db->db_query($req);
			$this->countadm = $this->db->db_num_rows($this->resadm);

			$req = "select mail_domains.* from mail_domains join users_groups where bgroup='Y' and users_groups.id_object='".$BAB_SESS_USERID."' and owner=users_groups.id_group";
			$this->resgrp = $this->db->db_query($req);
			$this->countgrp = $this->db->db_num_rows($this->resgrp);
			
			$req = "select * from mail_domains where owner='".$BAB_SESS_USERID."'";
			$this->resusr = $this->db->db_query($req);
			$this->countusr = $this->db->db_num_rows($this->resusr);
			}

		function getnextadm()
			{
			static $i = 0;
			if( $i < $this->countadm)
				{
				$arr = $this->db->db_fetch_array($this->resadm);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextgrp()
			{
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextusr()
			{
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $this->db->db_fetch_array($this->resusr);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"mailopt.html", "accountcreate"));
	}

function accountModify($item)
	{
	global $body;
	class temp
		{
		var $domselect;
		var $fullname;
		var $account;
		var $email;
		var $domain;
		var $password;
		var $repassword;
		var $modacc;
		var $prefaccount;
		var $prefformat;
		var $maxrows;
		var $yes;
		var $no;
		var $accselect;
		var $htmlselect;
		var $plainselect;
		var $plain;
		var $html;

		var $username;
		var $useremail;

		var $db;
		var $resadm;
		var $countadm;
		var $resgrp;
		var $countgrp;
		var $resusr;
		var $countusr;
		var $domname;
		var $domid;
		var $arr = array();


		function temp($item)
			{
			global $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = babTranslate("User Name");
			$this->account = babTranslate("Account");
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->repassword = babTranslate("Retype Password");
			$this->domain = babTranslate("Domain");
			$this->prefaccount = babTranslate("Prefered account");
			$this->prefformat = babTranslate("Prefered format");
			$this->maxrows = babTranslate("Default number of messages to display per screen");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->plain = babTranslate("Plain text");
			$this->html = babTranslate("Html");
			$this->modacc = babTranslate("Modify Account");
			$this->db = new db_mysql();
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$this->item = 0;
			$this->domselect = "";
			$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."' and id='".$item."'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				if( $this->arr[prefered] == "Y")
					$this->accselect = "selected";
				else
					$this->accselect = "";

				if( $this->arr[format] == "plain")
                    {
					$this->plainselect = "selected";
					$this->htmlselect = "";
                    }
				else
                    {
					$this->htmlselect = "selected";
					$this->plainselect = "";
                    }

                $this->item = $item;
			
				$req = "select * from mail_domains where bgroup='Y' and owner='1'";
				$this->resadm = $this->db->db_query($req);
				$this->countadm = $this->db->db_num_rows($this->resadm);

				$req = "select mail_domains.* from mail_domains join users_groups where bgroup='Y' and users_groups.id_object='".$BAB_SESS_USERID."' and owner=users_groups.id_group";
				$this->resgrp = $this->db->db_query($req);
				$this->countgrp = $this->db->db_num_rows($this->resgrp);
				
				$req = "select * from mail_domains where owner='".$BAB_SESS_USERID."'";
				$this->resusr = $this->db->db_query($req);
				$this->countusr = $this->db->db_num_rows($this->resusr);
				}
			}

		function getnextadm()
			{
			static $i = 0;
			if( $i < $this->countadm)
				{
				$arr = $this->db->db_fetch_array($this->resadm);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				if( $arr[id] == $this->arr[domain])
					$this->domselect = "selected";
				else
					$this->domselect = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		function getnextgrp()
			{
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				if( $arr[id] == $this->arr[domain])
					$this->domselect = "selected";
				else
					$this->domselect = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		function getnextusr()
			{
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $this->db->db_fetch_array($this->resusr);
				$this->domid = $arr[id];
				$this->domname = $arr[name];
				if( $arr[id] == $this->arr[domain])
					$this->domselect = "selected";
				else
					$this->domselect = "";
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

	$temp = new temp($item);
	$body->babecho(	babPrintTemplate($temp,"mailopt.html", "accountmodify"));
	}

function accountDelete($item)
	{
	global $body;

	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function temp($item)
			{
			$this->message = babTranslate("Are you sure you want to delete this mail account");
			$this->title = getAccountAccount($item) /* :o) */;
			$this->warning = babTranslate("WARNING: This operation will delete the account and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc&item=".$item."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modacc&item=".$item;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($item);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function signaturesList()
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
        var $arr = array();
		var $editurl;
		var $editname;
		var $delname;

		function temp()
			{
            global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$req = "select * from mail_signatures where owner='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->editname = babTranslate("Edit");
			$this->delname = babTranslate("Delete");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
                if( $this->arr[html] == "Y")
                    $this->content = $this->arr[text];
                else
                    $this->content = nl2br($this->arr[text]);
				$this->editurl = $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modsig&sigid=".$this->arr[id];
				$this->delurl = $GLOBALS[babUrl]."index.php?tg=mailopt&idx=delsig&sigid=".$this->arr[id];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"mailopt.html", "signatureslist"));
	return $temp->count;
	}

function signatureAdd()
	{
	global $body;
	class temp
		{
		var $name;
		var $signature;
		var $html;
		var $yes;
		var $no;
		var $add;

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->signature = babTranslate("Signature");
			$this->html = babTranslate("Html");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->add = babTranslate("Add");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"mailopt.html", "signaturecreate"));
	}

function signatureModify($sigid)
	{
	global $body;
	class temp
		{
		var $name;
		var $signature;
		var $html;
		var $yes;
		var $no;
		var $add;
        var $noselected;
        var $yesselected;

		function temp($sigid)
			{
			$this->name = babTranslate("Name");
			$this->signature = babTranslate("Signature");
			$this->html = babTranslate("Html");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->add = babTranslate("Modify");
            $db = new db_mysql();
            $req = "select * from mail_signatures where id='".$sigid."'";
        	$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
            if( $this->arr[html] == "Y")
                {
                $this->noselected = "";
                $this->yesselected = "selected";
                }
            else
                {
                $this->noselected = "selected";
                $this->yesselected = "";
                }
			}
		}

	$temp = new temp($sigid);
	$body->babecho(	babPrintTemplate($temp,"mailopt.html", "signaturemodify"));
	}

function addAccount($fullname, $email, $account, $password1, $password2, $domain, $prefacc, $maxrows, $prefformat)
	{
	global $body, $BAB_SESS_USERID;
	if( empty($account) || empty($password1) || empty($password2))
		{
		$body->msgerror = babTranslate("ERROR: You must complete all required fields !!");
		return;
		}
	if( $password1 != $password2)
		{
		$body->msgerror = babTranslate("ERROR: Passwords not match !!");
		return;
		}
	/*
	if ( !isEmailValid($email))
		{
		$body->msgerror = babTranslate("ERROR: Your email is not valid !!");
		return;
		}
    ALTER TABLE mail_accounts ADD format VARCHAR (6) DEFAULT 'plain' not null 
	*/

	$db = new db_mysql();
	$req = "select * from mail_accounts where account='$account' and domain='$domain'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This email account already exists !!");
		return;
		}

	if( $prefered == "Y" )
		{
		$req = "update mail_accounts set prefered='N' where owner='".$BAB_SESS_USERID."'";	
		$res = $db->db_query($req);
		}
	$req = "insert into mail_accounts (name, email, password, account, domain, owner, maxrows, prefered, format) values ";	
	$req .= "('".$fullname."', '".$email."', '".$password1."', '".$account."', '".$domain."', '".$BAB_SESS_USERID."', '".$maxrows."', '".$prefered."', '".$prefformat."')";	
	$res = $db->db_query($req);

}

function modifyAccount($fullname, $email, $account, $password1, $password2, $domain, $item, $prefacc, $maxrows, $prefformat)
	{
	global $body, $BAB_SESS_USERID;
	if( empty($account))
		{
		$body->msgerror = babTranslate("ERROR: You must provide account field !!");
		return;
		}

	if( !empty($password1) || !empty($password2))
		{
		if( $password1 != $password2)
			{
			$body->msgerror = babTranslate("ERROR: Passwords not match !!");
			return;
			}
		}
	
	/*
	if ( !isEmailValid($email))
		{
		$body->msgerror = babTranslate("ERROR: Your email is not valid !!");
		return;
		}
	*/
	$db = new db_mysql();
	if( $prefacc == "Y" )
		{
		$req = "update mail_accounts set prefered='N' where owner='".$BAB_SESS_USERID."'";	
		$res = $db->db_query($req);
		}
	if( empty($password1) )
		$req = "update mail_accounts set name='$fullname', email='$email', account='$account', domain='$domain', prefered='$prefacc', maxrows='$maxrows', format='$prefformat' where id='$item'";
	else
		$req = "update mail_accounts set name='$fullname', email='$email', password='$password1', account='$account', domain='$domain', prefered='$prefacc', format='$prefformat', maxrows='$maxrows' where id='$item'";
	$res = $db->db_query($req);

}

function confirmDeleteAccount($item)
{
	$db = new db_mysql();

	$req = "delete from mail_accounts where id='$item'";
	$res = $db->db_query($req);	

}

function addSignature($name, $signature, $html)
{
	global $body, $BAB_SESS_USERID;
	if( empty($signature))
		{
		$body->msgerror = babTranslate("ERROR: You must provide signature !!");
		return;
		}
	$db = new db_mysql();
	$req = "insert into mail_signatures (name, text, html, owner) values ";	
	$req .= "('".$name."', '".$signature."', '".$html."', '".$BAB_SESS_USERID."')";	
	$res = $db->db_query($req);
	if( empty($name))
		{
    	$id = $db->db_insert_id();
		$req = "update mail_signatures set name='signature".$id."' where id='".$id."'";	
    	$res = $db->db_query($req);
		}
}

function modifySignature($name, $signature, $html, $sigid)
{
	global $body, $BAB_SESS_USERID;
	if( empty($signature))
		{
		$body->msgerror = babTranslate("ERROR: You must provide signature !!");
		return;
		}
	$db = new db_mysql();
	if( !empty($name))
	    $req = "update mail_signatures set name='".$name."', text='".$signature."', html='".$html."' where id='".$sigid."'";
    else
	    $req = "update mail_signatures set name='signature".$sigid."', text='".$signature."', html='".$html."' where id='".$sigid."'";
	$res = $db->db_query($req);
}

function deleteSignature($sigid)
{
	global $body;
	$db = new db_mysql();
    $req = "delete from mail_signatures where id='".$sigid."'";
	$res = $db->db_query($req);

}
/* main */
if(!isset($idx))
	{
	$idx = "listacc";
	}

if( isset($addacc) && $addacc == "add")
	addAccount($fullname, $email, $account, $password1, $password2, $domain, $prefacc, $maxrows, $prefformat);

if( isset($modacc) && $modacc == "modify")
	modifyAccount($fullname, $email, $account, $password1, $password2, $domain, $item, $prefacc, $maxrows, $prefformat);

if( isset($action) && $action == "Yes")
	{
	confirmDeleteAccount($item);
	}

if( isset($addsig) && $addsig == "add")
	{
	addSignature($name, $signature, $html);
	}

if( isset($modsig) && $modsig == "modify")
	{
	modifySignature($name, $signature, $html, $sigid);
	}

switch($idx)
	{

	case "delacc":
		$body->title = babTranslate("Delete account");
		$bemail = mailAccessLevel();
		accountDelete($item);
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("addacc", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addacc");
		$body->addItemMenu("modacc", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modacc&item=".$item);
		$body->addItemMenu("delacc", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=delacc&item=".$item);
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

	case "addacc":
		$body->title = babTranslate("Add account");
		$bemail = mailAccessLevel();
		accountCreate();
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("addacc", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addacc");
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;
	
	case "modacc":
		$body->title = babTranslate("Modify account");
		$bemail = mailAccessLevel();
		accountModify($item);
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("addacc", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addacc");
		$body->addItemMenu("modacc", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modacc");
		$body->addItemMenu("delacc", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=delacc&item=".$item);
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

	case "modsig":
		$body->title = babTranslate("Modify Signature");
		$bemail = mailAccessLevel();
		signatureModify($sigid);
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("listsig", babTranslate("Signatures"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listsig");
		$body->addItemMenu("addsig", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addsig");
		$body->addItemMenu("modsig", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=modsig");
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;


    case "addsig":
		$body->title = babTranslate("Add Signature");
		$bemail = mailAccessLevel();
		signatureAdd();
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("listsig", babTranslate("Signatures"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listsig");
		$body->addItemMenu("addsig", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addsig");
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

    case "delsig":
        deleteSignature($sigid);
        /* no break */
    case "listsig":
		$body->title = babTranslate("Signatures");
		$bemail = mailAccessLevel();
		signaturesList();
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("listsig", babTranslate("Signatures"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listsig");
		$body->addItemMenu("addsig", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addsig");
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

    default:
	case "listacc":
		$body->title = babTranslate("Mail options");
		$bemail = mailAccessLevel();
		accountsList();
		$body->addItemMenu("listacc", babTranslate("Accounts"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		$body->addItemMenu("addacc", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=addacc");
		$body->addItemMenu("listsig", babTranslate("Signatures"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listsig");
		if( $bemail == 1 || $bemail == 2)
			$body->addItemMenu("listpd", babTranslate("User's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$body->addItemMenu("listdg", babTranslate("Group's Domains"), $GLOBALS[babUrl]."index.php?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;
	}
if( empty($body->msgerror))
	$body->setCurrentItemMenu($idx);

?>