<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getSiteName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where id='$id'";
	$res = $db->db_query($query);
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

function siteModify($id)
	{

	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $lang;
		var $skin;
		var $langsite;
		var $skinsite;
		var $siteemail;
		var $siteemailval;
		var $create;
	
		var $id;
		var $arr = array();
		var $db;
		var $res;

        var $langselected;
        var $siteselected;
        var $arrfiles = array();
        var $arrdir = array();

		var $registration;
		var $confirmation;
		var $yes;
		var $no;
		var $yselected;
		var $nselected;
		function temp($id)
			{
			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->skin = bab_translate("Skin");
			$this->siteemail = bab_translate("Email site");
			$this->create = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->delete = bab_translate("Delete");
			$this->confirmation = bab_translate("Send email confirmation")."?";
			$this->registration = bab_translate("Activate Registration")."?";
			$this->helpconfirmation = "( ".bab_translate("Only valid if registration is actif")." )";
			$this->id = $id;

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SITES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->nameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$this->langsite = $arr['lang'];
				$this->skinsite = $arr['skin'];
				$this->siteemailval = $arr['adminemail'];
				if( $arr['registration'] == "Y")
					{
					$this->nregister = "";
					$this->yregister = "selected";
					}
				else
					{
					$this->yregister = "";
					$this->nregister = "selected";
					}

				if( $arr['email_confirm'] == "Y")
					{
					$this->nconfirm = "";
					$this->yconfirm = "selected";
					}
				else
					{
					$this->yconfirm = "";
					$this->nconfirm = "selected";
					}
				}
			$h = opendir($GLOBALS['babInstallPath']."lang/"); 
            while ( $file = readdir($h))
                { 
                if ($file != "." && $file != "..")
                    {
                    if( eregi("lang-([^.]*)", $file, $regs))
                        {
                        if( $file == "lang-".$regs[1].".xml")
                            $this->arrfiles[] = $regs[1]; 
                        }
                    } 
                }
            closedir($h);
			$this->count = count($this->arrfiles);

			$h = opendir($GLOBALS['babInstallPath']."skins/"); 
            while ( $file = readdir($h))
                { 
                if ($file != "." && $file != "..")
                    {
					if( is_dir($GLOBALS['babInstallPath']."skins/".$file))
						$this->arrdir[] = $file; 
                    } 
                }
            closedir($h);
            $this->scount = count($this->arrdir);
			}
		
		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langval = $this->arrfiles[$i];
                if( $this->langsite == $this->langval )
                    $this->langselected = "selected";
                else
                    $this->langselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextskin()
			{
			static $i = 0;
			if( $i < $this->scount)
				{
                $this->skinval = $this->arrdir[$i];
                if( $this->skinsite == $this->skinval )
                    $this->skinselected = "selected";
                else
                    $this->skinselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "sitemodify"));
	}

function siteHomePage0($id)
	{

	global $babBody;
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
			$this->title = bab_translate("Unregistered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = $id;

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='2' and id_site='$id' and ordering='0'";
			$this->reshome0 = $this->db->db_query($req);
			$this->counthome0 = $this->db->db_num_rows($this->reshome0);
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='2' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage0 = $this->db->db_query($req);
			$this->countpage0 = $this->db->db_num_rows($this->respage0);
			}

		function getnexthome0()
			{
			static $i = 0;
			if( $i < $this->counthome0 )
				{
				$arr = $this->db->db_fetch_array($this->reshome0 );
				$this->home0id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->home0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home0val = $arr['title'];
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
				$this->page0id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->page0id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page0val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp0($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "sitehomepage0"));
	}

function siteHomePage1($id)
	{

	global $babBody;
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
			$this->title = bab_translate("Registered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = $id;

			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='1' and id_site='$id' and ordering='0'";
			$this->reshome1 = $this->db->db_query($req);
			$this->counthome1 = $this->db->db_num_rows($this->reshome1);
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='1' and id_site='$id' and ordering!='0' order by ordering asc";
			$this->respage1 = $this->db->db_query($req);
			$this->countpage1 = $this->db->db_num_rows($this->respage1);
			}

		function getnexthome1()
			{
			static $i = 0;
			if( $i < $this->counthome1 )
				{
				$arr = $this->db->db_fetch_array($this->reshome1 );
				$this->home1id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->home1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->home1val = $arr['title'];
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
				$this->page1id = $arr['id_article'];
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$this->page1id."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				$this->page1val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp0 = new temp1($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "sitehomepage1"));
	}

function sectionDelete($id)
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
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this site");
			$this->title = getSiteName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the site and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=site&idx=Delete&site=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function siteUpdate($id, $name, $description, $lang, $siteemail, $skin, $register, $confirm)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if(!get_cfg_var("magic_quotes_gpc"))
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where name='$name' and id!='$id'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This site already exists");
		return false;
		}
	else
		{
		$query = "update ".BAB_SITES_TBL." set name='$name', description='$description', lang='$lang', adminemail='$siteemail', skin='$skin', registration='$register', email_confirm='$confirm' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function confirmDeleteSite($id)
	{
	$db = $GLOBALS['babDB'];

	// delete site
	$req = "delete from ".BAB_SITES_TBL." where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateHomePage0($item, $listpage0)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$item."' and id_group='2'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage0); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='2' and id_site='".$item."' and id_article='".$listpage0[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}

function siteUpdateHomePage1($item, $listpage1)
	{
	$db = $GLOBALS['babDB'];
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$item."' and id_group='1'";
	$res = $db->db_query($req);

	for($i=0; $i < count($listpage1); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='1' and id_site='".$item."' and id_article='".$listpage1[$i]."'";
		$res = $db->db_query($req);
		}
	return true;
	}

/* main */
if( isset($modify))
	{
	if( !empty($Submit))
		{
		if(!siteUpdate($item, $name, $description, $lang, $siteemail, $skin, $register, $confirm))
			$idx = "modify";
		}
	else if( !empty($delete))
		{
		$idx = "Delete";
		}
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
	case "hpriv":
		$babBody->title = bab_translate("Registered users home page for site").": ".getSiteName($item);
		siteHomePage1($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		break;

	case "hpub":
		$babBody->title = bab_translate("Unregistered users home page for site").": ".getSiteName($item);
		siteHomePage0($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		break;

	case "Delete":
		$babBody->title = getSiteName($item);
		sectionDelete($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=site&idx=Delete&item=".$item);
		break;
	default:
	case "modify":
		$babBody->title = getSiteName($item);
		siteModify($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>