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
		return $arr['name'];
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
				$this->url = $GLOBALS['babUrl']."index.php?tg=site&idx=modify&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
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


function siteCreate($name, $description, $siteemail)
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
        var $arrfiles = array();

        var $count;
        var $langval;

        var $arrdir = array();
		var $skin;

        var $scount;
        var $siteval;

		function temp($name, $description, $siteemail)
			{

			$this->name = babTranslate("Site name");
			$this->description = babTranslate("Description");
			$this->lang = babTranslate("Lang");
			$this->skin = babTranslate("Skin");
			$this->siteemail = babTranslate("Email site");
			$this->create = babTranslate("Create");

			$this->nameval = $name == ""? $GLOBALS['babSiteName']: $name;
			$this->descriptionval = $description == ""? "": $description;
			$this->langval = $lang == ""? $GLOBALS['babLanguage']: $lang;
			$this->siteemailval = $siteemail == ""? $GLOBALS['babAdminEmail']: $siteemail;

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
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($name, $description, $siteemail);
	$body->babecho(	babPrintTemplate($temp,"sites.html", "sitecreate"));
	}



function siteSave($name, $description, $lang, $siteemail, $skin)
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
		$query = "insert into sites (name, description, lang, adminemail, skin) VALUES ('" .$name. "', '" . $description. "', '" . $lang. "', '" . $siteemail. "', '" . $skin."')";
		$db->db_query($query);
		}
	return true;
	}


/* main */
if( isset($create))
	{
	if(!siteSave($name, $description, $lang, $siteemail, $skin))
		$idx = "create";
	}

if( !isset($idx))
	$idx = "list";


switch($idx)
	{
	case "create":
		$body->title = babTranslate("Create site");
		siteCreate($name, $description, $siteemail);
		$body->addItemMenu("list", babTranslate("Sites"),$GLOBALS['babUrl']."index.php?tg=sites&idx=list");
		$body->addItemMenu("create", babTranslate("Create"),$GLOBALS['babUrl']."index.php?tg=sites&idx=create");
		break;
	case "list":
	default:
		$body->title = babTranslate("Sites list");
		if( sitesList() > 0 )
			{
			$body->addItemMenu("list", babTranslate("Sites"),$GLOBALS['babUrl']."index.php?tg=sites&idx=list");
			}
		else
			$body->title = babTranslate("There is no site");

		$body->addItemMenu("create", babTranslate("Create"),$GLOBALS['babUrl']."index.php?tg=sites&idx=create");
		break;
	}

$body->setCurrentItemMenu($idx);


?>