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
include $babInstallPath."utilit/topincl.php";

function topcatModify($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid topic category !!");
		return;
		}
	class tempa
		{
		var $name;
		var $description;
		var $no;
		var $yes;
		var $noselected;
		var $yesselected;
		var $modify;
		var $delete;

		var $db;
		var $arr = array();
		var $res;

		var $arrtmpl;
		var $counttmpl;
		var $templatetxt;
		var $templateval;
		var $templateid;
		var $tmplselected;
		var $templateid;
		var $disptmpltxt;
		var $topcattxt;
		var $topcatid;
		var $topcatval;
		var $nonetxt;

		function tempa($id)
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Section enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->modify = bab_translate("Modify");
			$this->delete = bab_translate("Delete");
			$this->templatetxt = bab_translate("Section template");
			$this->disptmpltxt = bab_translate("Display template");
			$this->topcattxt = bab_translate("Topics category parent");
			$this->nonetxt = "--- ".bab_translate("None")." ---";
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['title'] = htmlentities($this->arr['title']);
			$this->arr['description'] = htmlentities($this->arr['description']);
			if( $this->arr['enabled'] == "Y")
				{
				$this->noselected = "";
				$this->yesselected = "selected";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}

			$file = "topicssection.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$this->arrtmpl = $tpl->getTemplates($filepath);
				}
			$this->counttmpl = count($this->arrtmpl);

			$file = "topcatdisplay.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$this->arrdisptmpl = $tpl->getTemplates($filepath);
				}
			$this->countdisptmpl = count($this->arrdisptmpl);

			$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id != '".$id."' order by title asc");
			$this->count = $babDB->db_num_rows($this->res);

			}

		function getnexttemplate()
			{
			static $i = 0;
			if($i < $this->counttmpl)
				{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
				if( $this->templateid == $this->arr['template'])
					$this->tmplselected = "selected";
				else
					$this->tmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		function getnextdisptemplate()
			{
			static $i = 0;
			if($i < $this->countdisptmpl)
				{
				$this->templateid = $this->arrdisptmpl[$i];
				$this->templateval = $this->arrdisptmpl[$i];
				if( $this->templateid == $this->arr['display_tmpl'])
					$this->tmplselected = "selected";
				else
					$this->tmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		function getnexttopcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->topcatid = $arr['id'];
				$this->topcatval = $arr['title'];
				if( $this->topcatid == $this->arr['id_parent'])
					$this->tmplselected = "selected";
				else
					$this->tmplselected = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new tempa($id);
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatmodify"));
	}


function topcatDelete($id)
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

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this topic category");
			$this->title = bab_getTopicCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&group=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$id."'"));
	if( $r['total'] > 0 )
		{
		$babBody->msgerror = bab_translate("To delete topic category, you must delete topics before");
		return false;
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	return true;
	}

function modifyTopcat($oldname, $name, $description, $benabled, $id, $template, $disptmpl, $topcatid)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$name = addslashes($name);
		$description = addslashes($description);
		$template = addslashes($template);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$name."' and id!='".$id."'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This topic category doesn't exist");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$id."'"));
		$query = "update ".BAB_TOPICS_CATEGORIES_TBL." set title='".$name."', description='".$description."', enabled='".$benabled."', template='".$template."', display_tmpl='".$disptmpl."', id_parent='".$topcatid."' where id='".$id."'";
		$db->db_query($query);
		if( $arr['id_parent'] != $topcatid )
			{
			$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$topcatid."'");
			$arr = $db->db_fetch_array($res);
			if( isset($arr[0]))
				$ord = $arr[0] + 1;
			else
				$ord = 1;
			$db->db_query("update ".BAB_TOPCAT_ORDER_TBL." set id_parent='".$topcatid."', ordering='".$ord."' where id_topcat='".$id."' and type='1'");
			}

		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$topcatid);
	}


function confirmDeleteTopcat($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	$idp = bab_deleteTopicCategory($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	{
	if( isset($submit))
		modifyTopcat($oldname, $title, $description, $benabled, $item, $template, $disptmpl, $topcatid);
	else if( isset($catdel))
		$idx = "Delete";
	}

if( isset($action) && $action == "Yes")
	{
	if($idx == "Delete")
		{
		confirmDeleteTopcat($group);
		}
	}

switch($idx)
	{
	case "Delete":
		if(topcatDelete($item))
			{
			$babBody->title = bab_translate("Delete topic category");
			$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$item);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&item=".$item);
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$item);
			break;
			}
		/* no break; */
		$idx = "Modify";
	case "Modify":
	default:
		topcatModify($item);
		$babBody->title = bab_translate("Modify topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$item."&idp=".$idp);
		$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>