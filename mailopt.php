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
require_once dirname(__FILE__).'/utilit/registerglobals.php';

function getDomainName($id)
	{
	global $babDB;
	$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function getAccountAccount($id)
	{
	global $babDB;
	$req = "select login from ".BAB_MAIL_ACCOUNTS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['login'];
		}
	else
		{
		return "";
		}
	}

function accountsList()
	{
	global $babBody;
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
			global $BAB_SESS_USERID, $babDB;
			$this->account_name = bab_translate("Account name");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->login = bab_translate("Login name");
			$this->domname = bab_translate("Domain");
			$this->count = 0;
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}
			
		function getnext()
			{
			global $BAB_SESS_USERID, $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->domnameval = getDomainName($this->arr['domain']);
				$this->url = $GLOBALS['babUrlScript']."?tg=mailopt&idx=modacc&item=".$this->arr['id'];
				if( $this->arr['prefered'] == "Y")
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
	$babBody->babecho(	bab_printTemplate($temp, "mailopt.html", "accountslist"));
	}

function accountCreate()
	{
	global $babBody;
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
			global $babDB, $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = bab_translate("User Name");
			$this->account_name = bab_translate("Account name");
			$this->email = bab_translate("Email");
			$this->login = bab_translate("Login name");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->domain = bab_translate("Domain");
			$this->prefaccount = bab_translate("Prefered account");
			$this->prefformat = bab_translate("Use WYSIWYG editor");
			$this->maxrows = bab_translate("Messages to display per screen");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->plain = bab_translate("Plain text");
			$this->html = bab_translate("Html");
			$this->addacc = bab_translate("Add Account");
			$this->username = $BAB_SESS_USER;
			$this->useremail = $BAB_SESS_EMAIL;
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
			$this->resadm = $babDB->db_query($req);
			$this->countadm = $babDB->db_num_rows($this->resadm);

			$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_USERS_GROUPS_TBL." where bgroup='Y' and ".BAB_USERS_GROUPS_TBL.".id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and owner=".BAB_USERS_GROUPS_TBL.".id_group";
			$this->resgrp = $babDB->db_query($req);
			$this->countgrp = $babDB->db_num_rows($this->resgrp);
			
			$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$this->resusr = $babDB->db_query($req);
			$this->countusr = $babDB->db_num_rows($this->resusr);
			}

		function getnextadm()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countadm)
				{
				$arr = $babDB->db_fetch_array($this->resadm);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextgrp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextusr()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $babDB->db_fetch_array($this->resusr);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"mailopt.html", "accountcreate"));
	}

function accountModify($item)
	{
	global $babBody;
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
			global $babDB, $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = bab_translate("User Name");
			$this->account_name = bab_translate("Account name");
			$this->login = bab_translate("Login name");
			$this->email = bab_translate("Email");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->domain = bab_translate("Domain");
			$this->prefaccount = bab_translate("Prefered account");
			$this->prefformat = bab_translate("Use WYSIWYG editor");
			$this->maxrows = bab_translate("Default number of messages to display per screen");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->plain = bab_translate("Plain text");
			$this->html = bab_translate("Html");
			$this->modacc = bab_translate("Modify Account");
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$this->item = 0;
			$this->domselect = "";
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and id='".$babDB->db_escape_string($item)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				{
				$this->arr = $babDB->db_fetch_array($res);
				if( $this->arr['prefered'] == "Y")
					$this->accselect = "selected";
				else
					$this->accselect = "";

				if( $this->arr['format'] == "plain")
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
			
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
				$this->resadm = $babDB->db_query($req);
				$this->countadm = $babDB->db_num_rows($this->resadm);

				$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_USERS_GROUPS_TBL." where bgroup='Y' and ".BAB_USERS_GROUPS_TBL.".id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."' and owner=".BAB_USERS_GROUPS_TBL.".id_group";
				$this->resgrp = $babDB->db_query($req);
				$this->countgrp = $babDB->db_num_rows($this->resgrp);
				
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' AND bgroup='N'";
				$this->resusr = $babDB->db_query($req);
				$this->countusr = $babDB->db_num_rows($this->resusr);
				}
			}

		function getnextadm()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countadm)
				{
				$arr = $babDB->db_fetch_array($this->resadm);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				if( $arr['id'] == $this->arr['domain'])
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
			global $babDB;
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				if( $arr['id'] == $this->arr['domain'])
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
			global $babDB;
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $babDB->db_fetch_array($this->resusr);
				$this->domid = $arr['id'];
				$this->domname = $arr['name'];
				if( $arr['id'] == $this->arr['domain'])
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
	$babBody->babecho(	bab_printTemplate($temp,"mailopt.html", "accountmodify"));
	}

function accountDelete($item)
	{
	global $babBody;

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
			$this->message = bab_translate("Are you sure you want to delete this mail account");
			$this->title = getAccountAccount($item) /* :o) */;
			$this->warning = bab_translate("WARNING: This operation will delete the account and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc&item=".$item."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=mailopt&idx=modacc&item=".$item;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($item);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function signaturesList()
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $editurl;
		var $editname;
		var $delname;

		function temp()
			{
            global $babDB, $BAB_SESS_USERID;
			$req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->editname = bab_translate("Edit");
			$this->delname = bab_translate("Delete");
			}

		function getnext()
			{
			global $babDB;
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
                if( $this->arr['html'] == "Y")
                    $this->content = $this->arr['text'];
                else
                    $this->content = nl2br($this->arr['text']);
				$this->editurl = $GLOBALS['babUrlScript']."?tg=mailopt&idx=modsig&sigid=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=mailopt&idx=delsig&sigid=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"mailopt.html", "signatureslist"));
	return $temp->count;
	}

function signatureAdd($signature, $name, $html)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $signature;
		var $html;
		var $yes;
		var $no;
		var $add;
		var $signatureval;
		var $nameval;
		var $htmlselected;
		var $textselected;
		var $bhtml;

		function temp($signature, $name, $html)
			{
			$this->name = bab_translate("Name");
			$this->signature = bab_translate("Signature");
			$this->html = bab_translate("Html");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->signatureval = $signature != ""? $signature: "";
			$this->nameval = $name != ""? $name: "";
			$this->onchange = "";
			if( empty($html)) {
				$html = "Y";	
			}
			
			if( $html == "Y")
				{
				$this->htmlselected = "selected";
				$this->textselected = "";
				}
			else
				{
				if( $html == "N" )
					$this->msie = 0;
				$this->htmlselected = "";
				$this->textselected = "selected";
				}

			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_mail_signature');
			$editor->setContent($this->signatureval);
			$editor->setFormat('html');
			$this->editor = $editor->getEditor();
			}
		}

	$temp = new temp($signature, $name, $html);
	$babBody->babecho(	bab_printTemplate($temp,"mailopt.html", "signaturecreate"));
	}

function signatureModify($sigid, $signature, $name, $html)
	{
	global $babBody;
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
		var $bhtml;
		var $signatureval;
		var $nameval;
		var $id;

		function temp($sigid, $signature, $name, $html)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->signature = bab_translate("Signature");
			$this->html = bab_translate("Html");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Modify");
			$this->id = $sigid;
			if( empty($html))
				{
	            $req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where id='".$babDB->db_escape_string($sigid)."'";
				$res = $babDB->db_query($req);
				$this->arr = $babDB->db_fetch_array($res);
				$this->signatureval = $this->arr['text'];
				$this->nameval = $this->arr['name'];
				$html = $this->arr['html'];
				}
			else
				{
				$this->signatureval = $signature;
				$this->nameval = $name;
				}

			if(( mb_strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				{
				$this->bhtml = 1;
				}
			else
				{
				$this->bhtml = 0;
				}

			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_mail_signature');
			$editor->setContent($this->signatureval);
			$editor->setFormat('html');
			$this->editor = $editor->getEditor();

            if( $html == "Y")
                {
                $this->noselected = "";
                $this->yesselected = "selected";
                }
            else
                {
 				if( $html == "N" )
					$this->msie = 0;
				$this->noselected = "selected";
                $this->yesselected = "";
                }
			}
		}

	$temp = new temp($sigid,$signature, $name, $html);
	$babBody->babecho(	bab_printTemplate($temp,"mailopt.html", "signaturemodify"));
	}

function addAccount($account_name,$fullname, $email, $login, $password1, $password2, $domain, $prefacc, $maxrows, $prefformat)
	{
	global $babBody, $babDB, $BAB_SESS_USERID, $BAB_HASH_VAR;
	if( empty($account_name) || empty($login) || empty($password1) || empty($password2))
		{
		$babBody->msgerror = bab_translate("ERROR: You must complete all required fields !!");
		return;
		}
	if( $password1 != $password2)
		{
		$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
		return;
		}
	/*
	if ( !bab_isEmailValid($email))
		{
		$babBody->msgerror = bab_translate("ERROR: Your email is not valid !!");
		return;
		}
    ALTER TABLE ".BAB_MAIL_ACCOUNTS_TBL." ADD format VARCHAR (6) DEFAULT 'plain' not null 
	*/

	$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where account_name='".$babDB->db_escape_string($account_name)."' and domain='".$babDB->db_escape_string($domain)."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This email account already exists !!");
		return;
		}

	if( $prefacc == "Y" )
		{
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set prefered='N' where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
		$res = $babDB->db_query($req);
		}



	$req = "insert into ".BAB_MAIL_ACCOUNTS_TBL." 
	(account_name, name, email, password, login, domain, owner, maxrows, prefered, format) 
		values ";	

	$req .= "(
		'".$babDB->db_escape_string($account_name)."',
		'".$babDB->db_escape_string($fullname)."', 
		'".$babDB->db_escape_string($email)."', 
		ENCODE(\"".$babDB->db_escape_string($password1)."\",\"".$babDB->db_escape_string($BAB_HASH_VAR)."\"), 
		'".$babDB->db_escape_string($login)."', 
		'".$babDB->db_escape_string($domain)."', 
		'".$babDB->db_escape_string($BAB_SESS_USERID)."', 
		'".$babDB->db_escape_string($maxrows)."', 
		'".$babDB->db_escape_string($prefacc)."', 
		'".$babDB->db_escape_string($prefformat)."'
	)";	

	$res = $babDB->db_query($req);

}

function modifyAccount($account_name, $fullname, $email, $login, $password1, $password2, $domain, $item, $prefacc, $maxrows, $prefformat)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	if( empty($account_name) || empty($login))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide account field !!");
		return;
		}

	if( !empty($password1) || !empty($password2))
		{
		if( $password1 != $password2)
			{
			$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
			return;
			}
		}
	
	if( $prefacc == "Y" )
		{
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set prefered='N' where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
		$res = $babDB->db_query($req);
		}


	if( empty($password1) ) {
		$req = "
		UPDATE ".BAB_MAIL_ACCOUNTS_TBL." SET 
			account_name='".$babDB->db_escape_string($account_name)."', 
			name='".$babDB->db_escape_string($fullname)."', 
			email='".$babDB->db_escape_string($email)."', 
			login='".$babDB->db_escape_string($login)."', 
			domain='".$babDB->db_escape_string($domain)."', 
			prefered='".$babDB->db_escape_string($prefacc)."', 
			maxrows='".$babDB->db_escape_string($maxrows)."', 
			format='".$babDB->db_escape_string($prefformat)."' 
		WHERE 
			id='".$babDB->db_escape_string($item)."' 
			AND owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	}
	else {
		$req = "UPDATE ".BAB_MAIL_ACCOUNTS_TBL." set 
		account_name='".$babDB->db_escape_string($account_name)."', 
			name='".$babDB->db_escape_string($fullname)."', 
			email='".$babDB->db_escape_string($email)."', 
			password=ENCODE(\"".$babDB->db_escape_string($password1)."\",\"".$babDB->db_escape_string($GLOBALS['BAB_HASH_VAR'])."\"),  
			login='".$babDB->db_escape_string($login)."', 
			domain='".$babDB->db_escape_string($domain)."', 
			prefered='".$babDB->db_escape_string($prefacc)."', 
			format='".$babDB->db_escape_string($prefformat)."', 
			maxrows='".$babDB->db_escape_string($maxrows)."' 
			where id='".$babDB->db_escape_string($item)."' 
			and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	}
	$res = $babDB->db_query($req);

}

function confirmDeleteAccount($item)
{
	global $babDB, $BAB_SESS_USERID;

	$req = "delete from ".BAB_MAIL_ACCOUNTS_TBL." where id='".$babDB->db_escape_string($item)."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);	

}

function addSignature($name, $html)
{
	global $babBody, $babDB, $BAB_SESS_USERID;
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
	if ('Y' === $html) {
		$editor = new bab_contentEditor('bab_mail_signature');
		$signature = $editor->getContent();
	} else {
		$signature = bab_pp('signature');
	}
	
	
	if( empty($signature))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide signature !!");
		return;
		}
	$req = "insert into ".BAB_MAIL_SIGNATURES_TBL." (name, text, html, owner) values ";	
	$req .= "('".$babDB->db_escape_string($name)."', '".$babDB->db_escape_string($signature)."', '".$babDB->db_escape_string($html)."', '".$babDB->db_escape_string($BAB_SESS_USERID)."')";	
	$res = $babDB->db_query($req);
	if( empty($name))
		{
    	$id = $babDB->db_insert_id();
		$req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='signature".$babDB->db_escape_string($id)."' where id='".$babDB->db_escape_string($id)."'";	
    	$res = $babDB->db_query($req);
		}
}

function modifySignature($name,  $html, $sigid)
{
	global $babBody, $babDB, $BAB_SESS_USERID;
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
	$editor = new bab_contentEditor('bab_mail_signature');
	$signature = $editor->getContent();
	
	
	if( empty($signature))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide signature !!");
		return;
		}
	if( !empty($name))
	    $req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='".$babDB->db_escape_string($name)."', text='".$babDB->db_escape_string($signature)."', html='".$babDB->db_escape_string($html)."' where id='".$babDB->db_escape_string($sigid)."' and owner ='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
    else
	    $req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='signature".$babDB->db_escape_string($sigid)."', text='".$babDB->db_escape_string($signature)."', html='".$babDB->db_escape_string($html)."' where id='".$babDB->db_escape_string($sigid)."' and owner ='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);
}

function deleteSignature($sigid)
{
	global $babDB, $BAB_SESS_USERID;
    $req = "delete from ".BAB_MAIL_SIGNATURES_TBL." where id='".$babDB->db_escape_string($sigid)."' and owner ='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);

}
/* main */
if(!isset($idx))
	{
	$idx = "listacc";
	}

if( !isset($signame ))
	$signame = "";

if( !isset($signature ))
	$signature = "";

if( isset($addacc) && $addacc == "add" && $BAB_SESS_USERID != '')
	addAccount($account_name, $fullname, $email, $login, $password1, $password2, $domain, $prefacc, $maxrows, $prefformat);

if( isset($modacc) && $modacc == "modify" && $BAB_SESS_USERID != '')
	modifyAccount($account_name, $fullname, $email, $login, $password1, $password2, $domain, $item, $prefacc, $maxrows, $prefformat);

if( isset($action) && $action == "Yes" && $BAB_SESS_USERID != '')
	{
	confirmDeleteAccount($item);
	}

if( isset($addsig) && $addsig == "add" && $BAB_SESS_USERID != '')
	{
	addSignature($signame, $html);
	}

if( isset($modsig) && $modsig == "modify" && $BAB_SESS_USERID != '')
	{
	modifySignature($signame, $html, $sigid);
	}

switch($idx)
	{

	case "delacc":
		$babBody->title = bab_translate("Delete account");
		$bemail = bab_mailAccessLevel();
		accountDelete($item);
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("addacc", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addacc");
		$babBody->addItemMenu("modacc", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=modacc&item=".$item);
		$babBody->addItemMenu("delacc", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=delacc&item=".$item);
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

	case "addacc":
		$babBody->title = bab_translate("Create an e-mail account");
		$bemail = bab_mailAccessLevel();
		accountCreate();
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("addacc", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addacc");
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;
	
	case "modacc":
		$babBody->title = bab_translate("Modify account");
		$bemail = bab_mailAccessLevel();
		accountModify($item);
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("addacc", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addacc");
		$babBody->addItemMenu("modacc", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=modacc");
		$babBody->addItemMenu("delacc", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=delacc&item=".$item);
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

	case "modsig":
		$babBody->title = bab_translate("Modify Signature");
		$bemail = bab_mailAccessLevel();
		$html = bab_pp('html', '');
		signatureModify($sigid,$signature, $signame, $html);
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("listsig", bab_translate("Signatures"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listsig");
		$babBody->addItemMenu("addsig", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addsig");
		$babBody->addItemMenu("modsig", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=modsig");
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;


    case "addsig":
		$babBody->title = bab_translate("Add Signature");
		$bemail = bab_mailAccessLevel();
		if (!isset($html))
			$html = '';
		signatureAdd($signature, $signame, $html);
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("listsig", bab_translate("Signatures"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listsig");
		$babBody->addItemMenu("addsig", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addsig");
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

    case "delsig":
        deleteSignature($sigid);
        /* no break */
    case "listsig":
		$babBody->title = bab_translate("Signatures");
		$bemail = bab_mailAccessLevel();
		signaturesList();
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("listsig", bab_translate("Signatures"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listsig");
		$babBody->addItemMenu("addsig", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addsig");
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;

    default:
	case "listacc":
		$babBody->title = bab_translate("Mail options");
		$bemail = bab_mailAccessLevel();
		accountsList();
		$babBody->addItemMenu("listacc", bab_translate("Accounts"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("addacc", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=addacc");
		$babBody->addItemMenu("listsig", bab_translate("Signatures"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listsig");
		if( $bemail == 1 || $bemail == 2)
			$babBody->addItemMenu("listpd", bab_translate("User's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=n");
		if( $bemail == 2 || $bemail == 3)
			$babBody->addItemMenu("listdg", bab_translate("Group's Domains"), $GLOBALS['babUrlScript']."?tg=maildoms&idx=list&userid=".$BAB_SESS_USERID."&bgrp=y");
		break;
	}
if( empty($babBody->msgerror))
	$babBody->setCurrentItemMenu($idx);

?>