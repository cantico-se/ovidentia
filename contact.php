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
	global $bliste, $babDB, $BAB_SESS_USERID;
	$req = "select * from ".BAB_CONTACTS_TBL." where id='$id' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		contactCreate($id, $arr['firstname'], $arr['lastname'], $arr['email'], $arr['compagny'], $arr['hometel'], $arr['mobiletel'], $arr['businesstel'], $arr['businessfax'], $arr['jobtitle'], $arr['businessaddress'], $arr['homeaddress'], $bliste);
		}
}

function addContact( $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $babDB, $msgerror, $BAB_SESS_USERID;
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
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$babDB->db_escape_string($hash)."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$msgerror = bab_translate("ERROR: This contact already exists");
		return false;
		}
	$req = "insert into ".BAB_CONTACTS_TBL." (owner, firstname, lastname, hashname, email, compagny, hometel, mobiletel, businesstel, businessfax, jobtitle, businessaddress, homeaddress) VALUES ('". $babDB->db_escape_string($BAB_SESS_USERID). "','" . $babDB->db_escape_string($firstname). "','". $babDB->db_escape_string($lastname). "','". $babDB->db_escape_string($hash). "','" . $babDB->db_escape_string($email). "','" . $babDB->db_escape_string($compagny). "','" . $babDB->db_escape_string($hometel). "','" . $babDB->db_escape_string($mobiletel). "','" . $babDB->db_escape_string($businesstel). "','" . $babDB->db_escape_string($businessfax). "','" . $babDB->db_escape_string($jobtitle). "','" . $babDB->db_escape_string($baddress). "','" . $babDB->db_escape_string($haddress). "')";
	$res = $babDB->db_query($req);	
	return true;
}


function updateContact( $id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $babDB, $msgerror, $BAB_SESS_USERID;
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
	$req = "select * from ".BAB_CONTACTS_TBL." where hashname='".$babDB->db_escape_string($hash)."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and id!='".$babDB->db_escape_string($id)."'";	
	$res = $babDB->db_query($req);
	if( $babDB->db_num_rows($res) > 0)
		{
		$msgerror = bab_translate("ERROR: This contact already exists");
		return false;
		}

	$req = "update ".BAB_CONTACTS_TBL." set owner='".$babDB->db_escape_string($BAB_SESS_USERID)."', firstname='".$babDB->db_escape_string($firstname)."', lastname='".$babDB->db_escape_string($lastname)."', hashname='".$babDB->db_escape_string($hash)."',email='".$babDB->db_escape_string($email)."', compagny='".$babDB->db_escape_string($compagny)."', hometel='".$babDB->db_escape_string($hometel)."', mobiletel='".$babDB->db_escape_string($mobiletel)."', businesstel='".$babDB->db_escape_string($businesstel)."', businessfax='".$babDB->db_escape_string($businessfax)."', jobtitle='".$babDB->db_escape_string($jobtitle)."', businessaddress='".$babDB->db_escape_string($baddress)."', homeaddress='".$babDB->db_escape_string($haddress)."' where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($req);	
	return true;
}

/* main */
if( !isset($idx))
	$idx = "create";
$msgerror = "";

if( $BAB_SESS_USERID != '' )
{
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
}
else
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
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
		
		$id				= isset($_REQUEST['id'])			? $_REQUEST['id'] : '';
		$firstname		= isset($_REQUEST['firstname'])		? $_REQUEST['firstname'] : '';
		$lastname		= isset($_REQUEST['lastname'])		? $_REQUEST['lastname'] : '';
		$email			= isset($_REQUEST['email'])			? $_REQUEST['email'] : '';
		$compagny		= isset($_REQUEST['compagny'])		? $_REQUEST['compagny'] : '';
		$hometel		= isset($_REQUEST['hometel'])		? $_REQUEST['hometel'] : '';
		$mobiletel		= isset($_REQUEST['mobiletel'])		? $_REQUEST['mobiletel'] : '';
		$businesstel	= isset($_REQUEST['businesstel'])	? $_REQUEST['businesstel'] : '';
		$businessfax	= isset($_REQUEST['businessfax'])	? $_REQUEST['businessfax'] : '';
		$jobtitle		= isset($_REQUEST['jobtitle'])		? $_REQUEST['jobtitle'] : '';
		$baddress		= isset($_REQUEST['baddress'])		? $_REQUEST['baddress'] : '';
		$haddress		= isset($_REQUEST['haddress'])		? $_REQUEST['haddress'] : '';
		
		contactCreate($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $_REQUEST['bliste']);
		break;
	}
?>