<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";

function contactCreate($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste)
	{
	class temp
		{
		var $firstname;
		var $lastname;
		var $email;
		var $compagny;
		var $hometel;
		var $mobiletel;
		var $businesstel;
		var $businessfax;
		var $jobtitle;
		var $businessaddress;
		var $homeaddress;
		var $firstnameval;
		var $lastnameval;
		var $emailval;
		var $compagnyval;
		var $hometelval;
		var $mobiletelval;
		var $businesstelval;
		var $businessfaxval;
		var $jobtitleval;
		var $businessaddressval;
		var $homeaddressval;
		var $addcontactval;
		var $id;
		var $what;
		var $cancel;
		var $babCss;

		function temp($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste)
			{
			global $msgerror;
			$this->id = $id;
			$this->bliste = $bliste;
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->hometel = bab_translate("Home Tel");
			$this->mobiletel = bab_translate("Mobile Tel");
			$this->businesstel = bab_translate("Business Tel");
			$this->businessfax = bab_translate("Business Fax");
			$this->jobtitle = bab_translate("Job Title");
			$this->businessaddress = bab_translate("Business Address");
			$this->homeaddress = bab_translate("Home Address");
			$this->cancel = bab_translate("Cancel");
			$this->msgerror = $msgerror;
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			if( empty($id))
				{
				$this->addcontact = bab_translate("Add Contact");
				$this->what = "add";
				$this->id = "";
				}
			else
				{
				$this->addcontact = bab_translate("Update Contact");
				$this->what = "update";
				}

			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->emailval = $email != ""? $email: "";
			$this->compagnyval = $compagny != ""? $compagny: "";
			$this->hometelval = $hometel != ""? $hometel: "";
			$this->mobiletelval = $mobiletel != ""? $mobiletel: "";
			$this->businesstelval = $businesstel != ""? $businesstel: "";
			$this->businessfaxval = $businessfax != ""? $businessfax: "";
			$this->jobtitleval = $jobtitle != ""? $jobtitle: "";
			$this->businessaddressval = $baddress != ""? $baddress: "";
			$this->homeaddressval = $haddress != ""? $haddress: "";
			}
		}

	$temp = new temp($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste);
	echo bab_printTemplate($temp,"contact.html", "contactcreate");
	}

function contactUnload($pos, $bliste)
	{
	class temp
		{
		var $babCss;
		var $message;
		var $close;
		var $url;
		var $bliste;

		function temp($pos, $bliste)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->message = bab_translate("Your contacts list has been updated");
			$this->close = bab_translate("Close");
			$this->url = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=".$pos;
			$this->bliste = $bliste;
			}
		}

	$temp = new temp($pos, $bliste);
	echo bab_printTemplate($temp,"contact.html", "contactunload");
	}

function contactUpdate($id)
{
	global $bliste, $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_CONTACTS_TBL." where id='$id' and owner='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		contactCreate($id, $arr['firstname'], $arr['lastname'], $arr['email'], $arr['compagny'], $arr['hometel'], $arr['mobiletel'], $arr['businesstel'], $arr['businessfax'], $arr['jobtitle'], $arr['businessaddress'], $arr['homeaddress'], $bliste);
		}
}

function addContact( $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $msgerror, $BAB_SESS_USERID;
	if( empty($firstname))
		{
		$msgerror = bab_translate("ERROR: You must provide a first name");
		return false;
		}
	if( empty($email) || !bab_isEmailValid($email) )
		{
		$msgerror = bab_translate("ERROR: You must provide a valid email address");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$hash."' and owner='".$BAB_SESS_USERID."'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$msgerror = bab_translate("ERROR: This contact already exists");
		return false;
		}
	$req = "insert into ".BAB_CONTACTS_TBL." (owner, firstname, lastname, hashname, email, compagny, hometel, mobiletel, businesstel, businessfax, jobtitle, businessaddress, homeaddress) VALUES ('". $BAB_SESS_USERID. "','" . $firstname. "','". $lastname. "','". $hash. "','" . $email. "','" . $compagny. "','" . $hometel. "','" . $mobiletel. "','" . $businesstel. "','" . $businessfax. "','" . $jobtitle. "','" . $baddress. "','" . $haddress. "')";
	$res = $db->db_query($req);	
	return true;
}


function updateContact( $id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $msgerror, $BAB_SESS_USERID;
	if( empty($firstname))
		{
		$msgerror = bab_translate("ERROR: You must provide a first name");
		return false;
		}
	if( empty($email) || !bab_isEmailValid($email) )
		{
		$msgerror = bab_translate("ERROR: You must provide a valid email address");
		return false;
		}

	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$hash."' and owner='".$BAB_SESS_USERID."' and id!='".$id."'";	
	$db = $GLOBALS['babDB'];
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$msgerror = bab_translate("ERROR: This contact already exists");
		return false;
		}

	$req = "update ".BAB_CONTACTS_TBL." set owner='$BAB_SESS_USERID', firstname='$firstname', lastname='$lastname', hashname='$hash',email='$email', compagny='$compagny', hometel='$hometel', mobiletel='$mobiletel', businesstel='$businesstel', businessfax='$businessfax', jobtitle='$jobtitle', businessaddress='$baddress', homeaddress='$haddress' where id='$id'";
	$res = $db->db_query($req);	
	return true;
}

/* main */
if( !isset($idx))
	$idx = "create";
$msgerror = "";

if( isset($addcontact))
	{
	if( $addcontact == "add")
		{
		if(!addContact($firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress))
			$idx = "create";
		else
			{
			$idx = "unload";
			$pos = strtoupper(substr($firstname, 0, 1));
			}
		}
	else if ($addcontact == "update")
		{
		if(!updateContact($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress))
			$idx = "create";
		else
			{
			$idx = "unload";
			$pos = strtoupper(substr($firstname, 0, 1));
			}
		}
	}

switch($idx)
	{
	case "unload":
		contactUnload($pos, $bliste);
		break;
	case "modify":
		//$msgerror = bab_translate("Modify contact");
		contactUpdate($item);
		break;
	case "create":
	default:
		//$msgerror = bab_translate("Create contact");
		contactCreate($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste);
		break;
	}
?>