<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

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

function siteModify($id)
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
	
		var $id;
		var $arr = array();
		var $db;
		var $res;

		function temp($id)
			{
			$this->name = babTranslate("Site name");
			$this->description = babTranslate("Description");
			$this->lang = babTranslate("Lang");
			$this->siteemail = babTranslate("Email site");
			$this->create = babTranslate("Modify");
			$this->id = $id;

			$this->db = new db_mysql();
			$req = "select * from sites where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->nameval = $arr[name];
				$this->descriptionval = $arr[description];
				$this->langval = $arr[lang];
				$this->siteemailval = $arr[adminemail];
				}
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp, "sites.html", "sitemodify"));
	}

function siteHomePage0($id)
	{

	global $body;
	class temp0
		{
		var $create;
	
		var $moveup;
		var $movedown;

		var $id;
		var $arr = array();
		var $db;
		var $res;

		var $listhometxt;
		var $listpagetxt;
		var $title;

		function temp0($id)
			{
			$this->title = babTranslate("Unregistered users home page");
			$this->listhometxt = babTranslate("---- Proposed Home articles ----");
			$this->listpagetxt = babTranslate("---- Home page articles ----");
			$this->moveup = babTranslate("Move Up");
			$this->movedown = babTranslate("Move Down");
			$this->create = babTranslate("Modify");
			$this->id = $id;

			$this->db = new db_mysql();
			$req = "select * from homepages where id_group='2' and id_site='$id' and ordering='0'";
			$this->reshome0 = $this->db->db_query($req);
			$this->counthome0 = $this->db->db_num_rows($this->reshome0);
			$req = "select * from homepages where id_group='2' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage0 = $this->db->db_query($req);
			$this->countpage0 = $this->db->db_num_rows($this->respage0);
			}

		function getnexthome0()
			{
			static $i = 0;
			if( $i < $this->counthome0 )
				{
				$arr = $this->db->db_fetch_array($this->reshome0 );
				$this->home0id = $arr[id_article];
				$req = "select * from articles where id='".$this->home0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home0val = $arr[title];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage0()
			{
			static $k = 0;
			if( $k < $this->countpage0 )
				{
				$arr = $this->db->db_fetch_array($this->respage0 );
				$this->page0id = $arr[id_article];
				$req = "select * from articles where id='".$this->page0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page0val = $arr[title];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp0($id);
	$body->babecho(	babPrintTemplate($temp0, "sites.html", "sitehomepage0"));
	}

function siteHomePage1($id)
	{

	global $body;
	class temp1
		{
		var $create;
	
		var $moveup;
		var $movedown;

		var $id;
		var $arr = array();
		var $db;
		var $res;

		var $listhometxt;
		var $listpagetxt;
		var $title;

		function temp1($id)
			{
			$this->title = babTranslate("Registered users home page");
			$this->listhometxt = babTranslate("---- Proposed Home articles ----");
			$this->listpagetxt = babTranslate("---- Home page articles ----");
			$this->moveup = babTranslate("Move Up");
			$this->movedown = babTranslate("Move Down");
			$this->create = babTranslate("Modify");
			$this->id = $id;

			$this->db = new db_mysql();
			$req = "select * from homepages where id_group='1' and id_site='$id' and ordering='0'";
			$this->reshome1 = $this->db->db_query($req);
			$this->counthome1 = $this->db->db_num_rows($this->reshome1);
			$req = "select * from homepages where id_group='1' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage1 = $this->db->db_query($req);
			$this->countpage1 = $this->db->db_num_rows($this->respage1);
			}

		function getnexthome1()
			{
			static $i = 0;
			if( $i < $this->counthome1 )
				{
				$arr = $this->db->db_fetch_array($this->reshome1 );
				$this->home1id = $arr[id_article];
				$req = "select * from articles where id='".$this->home1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home1val = $arr[title];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage1()
			{
			static $k = 0;
			if( $k < $this->countpage1 )
				{
				$arr = $this->db->db_fetch_array($this->respage1 );
				$this->page1id = $arr[id_article];
				$req = "select * from articles where id='".$this->page1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page1val = $arr[title];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp1($id);
	$body->babecho(	babPrintTemplate($temp0, "sites.html", "sitehomepage1"));
	}

function sectionDelete($id)
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
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = babTranslate("Are you sure you want to delete this site");
			$this->title = getSiteName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the site and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=site&idx=Delete&site=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=site&idx=modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function siteUpdate($id, $name, $description, $lang, $siteemail)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return false;
		}

	$db = new db_mysql();
	$query = "select * from sites where name='$name' and id!='$id'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This site already exists");
		return false;
		}
	else
		{
		$query = "update sites set name='$name', description='$description', lang='$lang', adminemail='$siteemail' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=sites&idx=list");
	}

function confirmDeleteSite($id)
	{
	$db = new db_mysql();

	// delete site
	$req = "delete from sites where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=sites&idx=list");
	}

function siteUpdateHomePage0($item, $listpage0)
	{
	$db = new db_mysql();
	$req = "update homepages set ordering='0' where id_site='".$item."' and id_group='2'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage0); $i++)
		{
		$req = "update homepages set ordering='".($i + 1)."' where id_group='2' and id_site='".$item."' and id_article='".$listpage0[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}

function siteUpdateHomePage1($item, $listpage1)
	{
	$db = new db_mysql();
	$req = "update homepages set ordering='0' where id_site='".$item."' and id_group='1'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage1); $i++)
		{
		$req = "update homepages set ordering='".($i + 1)."' where id_group='1' and id_site='".$item."' and id_article='".$listpage1[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}

/* main */
if( isset($modify))
	{
	if(!siteUpdate($item, $name, $description, $lang, $siteemail))
		$idx = "modify";
	}

if( isset($update) )
	{
	if( $update == "homepage0" )
		{
		if(!siteUpdateHomePage0($item, $listpage0))
			$idx = "modify";
		}
	else if( $update == "homepage1" )
		{
		if(!siteUpdateHomePage1($item, $listpage1))
			$idx = "modify";
		}
	}

if( !isset($idx))
	$idx = "modify";

if( isset($action) && $action == "Yes")
	{
	confirmDeleteSite($site);
	}

switch($idx)
	{
	case "Delete":
		$body->title = getSiteName($item);
		sectionDelete($item);
		$body->addItemMenu("List", babTranslate("Sites"),$GLOBALS[babUrl]."index.php?tg=sites&idx=list");
		$body->addItemMenu("modify", babTranslate("Modify"),$GLOBALS[babUrl]."index.php?tg=site&idx=modify&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS[babUrl]."index.php?tg=site&idx=Delete&item=".$item);
		break;
	default:
	case "modify":
		$body->title = getSiteName($item);
		siteModify($item);
		siteHomePage0($item);
		siteHomePage1($item);
		$body->addItemMenu("List", babTranslate("Sites"),$GLOBALS[babUrl]."index.php?tg=sites&idx=list");
		$body->addItemMenu("modify", babTranslate("Modify"),$GLOBALS[babUrl]."index.php?tg=site&idx=modify&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS[babUrl]."index.php?tg=site&idx=Delete&item=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);


?>