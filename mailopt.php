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

function getDomainName($id)
	{
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='$id'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function getAccountAccount($id)
	{
	$db = $GLOBALS['babDB'];
	$req = "select login from ".BAB_MAIL_ACCOUNTS_TBL." where id='$id'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
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
			global $BAB_SESS_USERID;
			$this->account_name = bab_translate("Account name");
			$this->name = bab_translate("Name");
			$this->email = bab_translate("Email");
			$this->login = bab_translate("Login name");
			$this->domname = bab_translate("Domain");
			$this->db = $GLOBALS['babDB'];
			$this->count = 0;
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."'";
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
			global $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = bab_translate("User Name");
			$this->account_name = bab_translate("Account name");
			$this->email = bab_translate("Email");
			$this->login = bab_translate("Login name");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->domain = bab_translate("Domain");
			$this->prefaccount = bab_translate("Prefered account");
			$this->prefformat = bab_translate("Prefered format");
			$this->maxrows = bab_translate("Messages to display per screen");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->plain = bab_translate("Plain text");
			$this->html = bab_translate("Html");
			$this->addacc = bab_translate("Add Account");
			$this->username = $BAB_SESS_USER;
			$this->useremail = $BAB_SESS_EMAIL;
			$this->db = $GLOBALS['babDB'];
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where bgroup='Y' and owner='1'";
			$this->resadm = $this->db->db_query($req);
			$this->countadm = $this->db->db_num_rows($this->resadm);

			$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_USERS_GROUPS_TBL." where bgroup='Y' and ".BAB_USERS_GROUPS_TBL.".id_object='".$BAB_SESS_USERID."' and owner=".BAB_USERS_GROUPS_TBL.".id_group";
			$this->resgrp = $this->db->db_query($req);
			$this->countgrp = $this->db->db_num_rows($this->resgrp);
			
			$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where owner='".$BAB_SESS_USERID."'";
			$this->resusr = $this->db->db_query($req);
			$this->countusr = $this->db->db_num_rows($this->resusr);
			}

		function getnextadm()
			{
			static $i = 0;
			if( $i < $this->countadm)
				{
				$arr = $this->db->db_fetch_array($this->resadm);
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
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
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
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $this->db->db_fetch_array($this->resusr);
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
			global $BAB_SESS_USERID, $BAB_SESS_EMAIL, $BAB_SESS_USER;
			$this->fullname = bab_translate("User Name");
			$this->account_name = bab_translate("Account name");
			$this->login = bab_translate("Login name");
			$this->email = bab_translate("Email");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->domain = bab_translate("Domain");
			$this->prefaccount = bab_translate("Prefered account");
			$this->prefformat = bab_translate("Prefered format");
			$this->maxrows = bab_translate("Default number of messages to display per screen");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->plain = bab_translate("Plain text");
			$this->html = bab_translate("Html");
			$this->modacc = bab_translate("Modify Account");
			$this->db = $GLOBALS['babDB'];
			$this->countadm = 0;
			$this->countgrp = 0;
			$this->countusr = 0;
			$this->item = 0;
			$this->domselect = "";
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."' and id='".$item."'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
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
				$this->resadm = $this->db->db_query($req);
				$this->countadm = $this->db->db_num_rows($this->resadm);

				$req = "select ".BAB_MAIL_DOMAINS_TBL.".* from ".BAB_MAIL_DOMAINS_TBL." join ".BAB_USERS_GROUPS_TBL." where bgroup='Y' and ".BAB_USERS_GROUPS_TBL.".id_object='".$BAB_SESS_USERID."' and owner=".BAB_USERS_GROUPS_TBL.".id_group";
				$this->resgrp = $this->db->db_query($req);
				$this->countgrp = $this->db->db_num_rows($this->resgrp);
				
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where owner='".$BAB_SESS_USERID."'";
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
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
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
			static $i = 0;
			if( $i < $this->countusr)
				{
				$arr = $this->db->db_fetch_array($this->resusr);
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
            global $BAB_SESS_USERID;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where owner='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->editname = bab_translate("Edit");
			$this->delname = bab_translate("Delete");
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
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
		var $msie;
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
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				{
				if( empty($html))
					$html = "Y";
				$this->bhtml = 1;
				$this->msie = 1;
				}
			else
				{
				$this->bhtml = 0;
				$this->msie = 0;
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
		var $msie;
		var $bhtml;
		var $signatureval;
		var $nameval;
		var $id;

		function temp($sigid, $signature, $name, $html)
			{
			$this->name = bab_translate("Name");
			$this->signature = bab_translate("Signature");
			$this->html = bab_translate("Html");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Modify");
			$this->id = $sigid;
			if( empty($html))
				{
	            $db = $GLOBALS['babDB'];
	            $req = "select * from ".BAB_MAIL_SIGNATURES_TBL." where id='".$sigid."'";
				$res = $db->db_query($req);
				$this->arr = $db->db_fetch_array($res);
				$this->signatureval = $this->arr['text'];
				$this->nameval = $this->arr['name'];
				$html = $this->arr['html'];
				}
			else
				{
				$this->signatureval = $signature;
				$this->nameval = $name;
				}

			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				{
				$this->bhtml = 1;
				$this->msie = 1;
				}
			else
				{
				$this->bhtml = 0;
				$this->msie = 0;
				}
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
	global $babBody, $BAB_SESS_USERID, $BAB_HASH_VAR;
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

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where account_name='$account_name' and domain='$domain' and owner='".$BAB_SESS_USERID."'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This email account already exists !!");
		return;
		}

	if( $prefered == "Y" )
		{
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set prefered='N' where owner='".$BAB_SESS_USERID."'";	
		$res = $db->db_query($req);
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$fullname = addslashes($fullname);
		}

	$req = "insert into ".BAB_MAIL_ACCOUNTS_TBL." (account_name, name, email, password, login, domain, owner, maxrows, prefered, format) values ";	
	$req .= "('".$account_name."','".$fullname."', '".$email."', ENCODE(\"".$password1."\",\"".$BAB_HASH_VAR."\"), '".$login."', '".$domain."', '".$BAB_SESS_USERID."', '".$maxrows."', '".$prefered."', '".$prefformat."')";	
	$res = $db->db_query($req);

}

function modifyAccount($account_name, $fullname, $email, $login, $password1, $password2, $domain, $item, $prefacc, $maxrows, $prefformat)
	{
	global $babBody, $BAB_SESS_USERID;
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
	
	$db = $GLOBALS['babDB'];
	if( $prefacc == "Y" )
		{
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set prefered='N' where owner='".$BAB_SESS_USERID."'";	
		$res = $db->db_query($req);
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$fullname = addslashes($fullname);
		}

	if( empty($password1) )
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set account_name='$account_name', name='$fullname', email='$email', login='$login', domain='$domain', prefered='$prefacc', maxrows='$maxrows', format='$prefformat' where id='$item' and owner='".$BAB_SESS_USERID."'";
	else
		$req = "update ".BAB_MAIL_ACCOUNTS_TBL." set account_name='$account_name', name='$fullname', email='$email', password=ENCODE(\"".$password1."\",\"".$GLOBALS['BAB_HASH_VAR']."\"),  login='$login', domain='$domain', prefered='$prefacc', format='$prefformat', maxrows='$maxrows' where id='$item' and owner='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);

}

function confirmDeleteAccount($item)
{
	global $BAB_SESS_USERID;

	$db = $GLOBALS['babDB'];

	$req = "delete from ".BAB_MAIL_ACCOUNTS_TBL." where id='$item' and owner='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);	

}

function addSignature($name, $signature, $html)
{
	global $babBody, $BAB_SESS_USERID;
	if( empty($signature))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide signature !!");
		return;
		}
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_MAIL_SIGNATURES_TBL." (name, text, html, owner) values ";	
	$req .= "('".$name."', '".$signature."', '".$html."', '".$BAB_SESS_USERID."')";	
	$res = $db->db_query($req);
	if( empty($name))
		{
    	$id = $db->db_insert_id();
		$req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='signature".$id."' where id='".$id."'";	
    	$res = $db->db_query($req);
		}
}

function modifySignature($name, $signature, $html, $sigid)
{
	global $babBody, $BAB_SESS_USERID;
	if( empty($signature))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide signature !!");
		return;
		}
	$db = $GLOBALS['babDB'];
	if( !empty($name))
	    $req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='".$name."', text='".$signature."', html='".$html."' where id='".$sigid."' and owner ='".$BAB_SESS_USERID."'";
    else
	    $req = "update ".BAB_MAIL_SIGNATURES_TBL." set name='signature".$sigid."', text='".$signature."', html='".$html."' where id='".$sigid."' and owner ='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
}

function deleteSignature($sigid)
{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
    $req = "delete from ".BAB_MAIL_SIGNATURES_TBL." where id='".$sigid."' and owner ='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);

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
	addSignature($signame, $signature, $html);
	}

if( isset($modsig) && $modsig == "modify" && $BAB_SESS_USERID != '')
	{
	modifySignature($signame, $signature, $html, $sigid);
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