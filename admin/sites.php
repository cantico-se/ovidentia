<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function getSiteName($id)
	{
	$db = new db_mysql();
	$query = "select * from sites where id='$id'";
	$res = $db->db_query($query);
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

function sitesList()
	{
	global $body;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $lang;
		var $email;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->name = babTranslate("Site name");
			$this->description = babTranslate("Description");
			$this->lang = babTranslate("Lang");
			$this->email = babTranslate("Email");
			$this->db = new db_mysql();
			$req = "select * from sites";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=site&idx=modify&item=".$this->arr[id];
				$this->urlname = $this->arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "sites.html", "siteslist"));
	return $temp->count;
	}


function siteCreate($name, $description, $lang, $siteemail)
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $lang;
		var $langval;
		var $siteemail;
		var $siteemailval;
		var $create;

		function temp($name, $description, $lang, $siteemail)
			{

			$this->name = babTranslate("Site name");
			$this->description = babTranslate("Description");
			$this->lang = babTranslate("Lang");
			$this->siteemail = babTranslate("Email site");
			$this->create = babTranslate("Create");

			$this->nameval = $name == ""? $GLOBALS[babSiteName]: $name;
			$this->descriptionval = $description == ""? "": $description;
			$this->langval = $lang == ""? $GLOBALS[babLanguage]: $lang;
			$this->siteemailval = $siteemail == ""? $GLOBALS[babAdminEmail]: $siteemail;
			}
		}

	$temp = new temp($name, $description, $lang, $siteemail);
	$body->babecho(	babPrintTemplate($temp,"sites.html", "sitecreate"));
	}



function siteSave($name, $description, $lang, $siteemail)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return false;
		}

	$db = new db_mysql();
	$query = "select * from sites where name='$name'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This site already exists");
		return false;
		}
	else
		{
		$query = "insert into sites (name, description, lang, adminemail) VALUES ('" .$name. "', '" . $description. "', '" . $lang. "', '" . $siteemail."')";
		$db->db_query($query);
		}
	return true;
	}


/* main */
if( isset($create))
	{
	if(!siteSave($name, $description, $lang, $siteemail))
		$idx = "create";
	}

if( !isset($idx))
	$idx = "list";


switch($idx)
	{
	case "create":
		$body->title = babTranslate("Create site");
		siteCreate($name, $description, $lang, $siteemail);
		$body->addItemMenu("list", babTranslate("Sites"),$GLOBALS[babUrl]."index.php?tg=sites&idx=list");
		$body->addItemMenu("create", babTranslate("Create"),$GLOBALS[babUrl]."index.php?tg=sites&idx=create");
		break;
	case "list":
	default:
		$body->title = babTranslate("Sites list");
		if( sitesList() > 0 )
			{
			$body->addItemMenu("list", babTranslate("Sites"),$GLOBALS[babUrl]."index.php?tg=sites&idx=list");
			}
		else
			$body->title = babTranslate("There is no site");

		$body->addItemMenu("create", babTranslate("Create"),$GLOBALS[babUrl]."index.php?tg=sites&idx=create");
		break;
	}

$body->setCurrentItemMenu($idx);


?>