<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

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
		var $style;
		var $babUrl;
		var $sitename;

		function temp($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste)
			{
			global $msgerror;
			$this->id = $id;
			$this->bliste = $bliste;
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$this->firstname = babTranslate("First Name");
			$this->lastname = babTranslate("Last Name");
			$this->email = babTranslate("Email");
			$this->compagny = babTranslate("Compagny");
			$this->hometel = babTranslate("Home Tel");
			$this->mobiletel = babTranslate("Mobile Tel");
			$this->businesstel = babTranslate("Business Tel");
			$this->businessfax = babTranslate("Business Fax");
			$this->jobtitle = babTranslate("Job Title");
			$this->businessaddress = babTranslate("Business Address");
			$this->homeaddress = babTranslate("Home Address");
			$this->cancel = babTranslate("Cancel");
			$this->msgerror = $msgerror;
			if( empty($id))
				{
				$this->addcontact = babTranslate("Add Contact");
				$this->what = "add";
				$this->id = "";
				}
			else
				{
				$this->addcontact = babTranslate("Update Contact");
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
	echo babPrintTemplate($temp,"contact.html", "contactcreate");
	}

function contactUnload($pos, $bliste)
	{
	class temp
		{
		var $style;
		var $babUrl;
		var $sitename;
		var $message;
		var $close;
		var $url;
		var $bliste;

		function temp($pos, $bliste)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$this->message = babTranslate("Your contacts list has been updated");
			$this->close = babTranslate("Close");
			$this->url = $GLOBALS[babUrl]."index.php?tg=contacts&idx=list&pos=".$pos;
			$this->bliste = $bliste;
			}
		}

	$temp = new temp($pos, $bliste);
	echo babPrintTemplate($temp,"contact.html", "contactunload");
	}

function contactUpdate($id)
{
	global $bliste, $BAB_SESS_USERID;
	$db = new db_mysql();
	$req = "select * from contacts where id='$id' and owner='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		contactCreate($id, $arr[firstname], $arr[lastname], $arr[email], $arr[compagny], $arr[hometel], $arr[mobiletel], $arr[businesstel], $arr[businessfax], $arr[jobtitle], $arr[businessaddress], $arr[homeaddress], $bliste);
		}
}

function addContact( $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $msgerror, $BAB_SESS_USERID;
	if( empty($firstname))
		{
		$msgerror = babTranslate("ERROR: You must provide a first name");
		return false;
		}
	if( empty($email) || !isEmailValid($email) )
		{
		$msgerror = babTranslate("ERROR: You must provide a valid email address");
		return false;
		}

	$db = new db_mysql();
	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$req = "select * from contacts where hashname='".$hash."' and owner='".$BAB_SESS_USERID."'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$msgerror = babTranslate("ERROR: This contact already exists");
		return false;
		}
	$req = "insert into contacts (owner, firstname, lastname, hashname, email, compagny, hometel, mobiletel, businesstel, businessfax, jobtitle, businessaddress, homeaddress) VALUES ('". $BAB_SESS_USERID. "','" . $firstname. "','". $lastname. "','". $hash. "','" . $email. "','" . $compagny. "','" . $hometel. "','" . $mobiletel. "','" . $businesstel. "','" . $businessfax. "','" . $jobtitle. "','" . $baddress. "','" . $haddress. "')";
	$res = $db->db_query($req);	
	return true;
}


function updateContact( $id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress)
	{
	global $msgerror, $BAB_SESS_USERID;
	if( empty($firstname))
		{
		$msgerror = babTranslate("ERROR: You must provide a first name");
		return false;
		}
	if( empty($email) || !isEmailValid($email) )
		{
		$msgerror = babTranslate("ERROR: You must provide a valid email address");
		return false;
		}

	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$req = "select * from contacts where hashname='".$hash."' and owner='".$BAB_SESS_USERID."' and id!='".$id."'";	
	$db = new db_mysql();
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$msgerror = babTranslate("ERROR: This contact already exists");
		return false;
		}

	$req = "update contacts set owner='$BAB_SESS_USERID', firstname='$firstname', lastname='$lastname', hashname='$hash',email='$email', compagny='$compagny', hometel='$hometel', mobiletel='$mobiletel', businesstel='$businesstel', businessfax='$businessfax', jobtitle='$jobtitle', businessaddress='$baddress', homeaddress='$haddress' where id='$id'";
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
		//$msgerror = babTranslate("Modify contact");
		contactUpdate($item);
		break;
	case "create":
	default:
		//$msgerror = babTranslate("Create contact");
		contactCreate($id, $firstname, $lastname, $email, $compagny, $hometel, $mobiletel, $businesstel, $businessfax, $jobtitle, $baddress, $haddress, $bliste);
		break;
	}
?>