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
include $babInstallPath."admin/acl.php";

function getFaqName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FAQCAT_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}

function addCategory()
	{
	global $babBody;
	class temp
		{
		var $category;
		var $description;
		var $Manager;
		var $add;
		var $msie;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;

		function temp()
			{
			$this->category = bab_translate("FAQ Name");
			$this->description = bab_translate("Description");
			$this->manager = bab_translate("Manager");
			$this->add = bab_translate("Add");
			$this->langLabel = bab_translate('Language');
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);

			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->item = "";
			$this->managerval = "";
			$this->managerid = "";
			$this->bdel = false;
			$this->tgval = "admfaqs";
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->faqname = "";
			$this->faqdesc = "";
			} // function temp
			
		function getnextlang()
			{
				static $i = 0;
				if($i < $this->countLangFiles)
					{
						$this->langValue = $this->langFiles[$i];
						if($this->langValue == $GLOBALS['babLanguage'])
							{
								$this->langSelected = 'selected';
			}
						else
							{
								$this->langSelected = '';
		}
						$i++;
						return true;
					}
				return false;
			} // function getnextlang
		} // class temp

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorycreate"));
	}

function listCategories()
	{
	global $babBody;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;
		var $urlcategory;
		var $namecategory;
		var $access;
		var $accessurl;

		function temp()
			{
			global $babBody;

			$this->access = bab_translate("Access");
			$this->db = $GLOBALS['babDB'];
			$langFilterValue = $GLOBALS['babLangFilter']->getFilterAsInt();
			if(($GLOBALS['babApplyLanguageFilter'] == 'loose') and bab_isUserAdministrator()) $langFilterValue = 0;
			switch($langFilterValue)
			{
				case 2:
					$req = "select * from ".BAB_FAQCAT_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and (lang='".$GLOBALS['babLanguage']."' or lang='*' or lang = ''";
					if ($GLOBALS['babApplyLanguageFilter'] == 'loose')
						$req.= " or id_manager = '" .$GLOBALS['BAB_SESS_USERID']. "'";
					$req .= ")";
					break;
				case 1:
					$req = "select * from ".BAB_FAQCAT_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and (lang like '". substr($GLOBALS['babLanguage'], 0, 2) ."%' or lang='*' or lang = ''";
					if ($GLOBALS['babApplyLanguageFilter'] == 'loose')
						$req.= " or id_manager = '" .$GLOBALS['BAB_SESS_USERID']. "'";
					$req .= ")";
					break;
				case 0:
				default:
					$req = "select * from ".BAB_FAQCAT_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			}

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->checked = "checked";
				else
					$this->checked = "";
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->arr['description'] = $this->arr['description'];
				$this->urlcategory = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$this->arr['id'];
				$this->accessurl = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$this->arr['id'];
				$this->namecategory = $this->arr['category'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorylist"));
	return $temp->count;
	}


function saveCategory($category, $description, $managerid, $lang)
	{
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a FAQ !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$category = addslashes($category);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FAQCAT_TBL." where category='".$category."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This FAQ already exists");
		return;
		}
	$query = "insert into ".BAB_FAQCAT_TBL." (id_manager, category, description, lang, id_dgowner) values ('" .$managerid. "', '" .$category. "', '" . $description. "', '" .$lang. "', '" .$babBody->currentAdmGroup. "')";
	$db->db_query($query);

	}  // saveCategory

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['faqs'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "Categories";
	}

if( isset($add))
	{
	saveCategory($category, $description, $managerid, $lang);
	}

switch($idx)
	{
	case "Add":
		$babBody->title = bab_translate("Add a new faq");
		addCategory();
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Add");
		break;

	default:
	case "Categories":
		$babBody->title = bab_translate("List of all faqs");
		if( listCategories() > 0 )
			{
			$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
			}
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Add");

		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
