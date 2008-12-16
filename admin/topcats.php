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
include_once $babInstallPath."admin/acl.php";
include_once $babInstallPath."utilit/topincl.php";
require_once $babInstallPath . 'utilit/tree.php';


class bab_AdmArticleTreeView extends bab_ArticleTreeView
{
	function bab_AdmArticleTreeView($sId)
	{
		parent::bab_ArticleTreeView($sId);
	}

	function onElementAppended(&$oElement, $sIdParent)
	{
		global $babBody;

		if('categoryroot' == $oElement->_type)
		{
			if( !$babBody->currentAdmGroup )
			{
			$sAddCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Create&idp=0';
			$oElement->addAction(
				'addCateg', bab_toHtml(bab_translate("Create a topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_category.png', $sAddCategUrl, '');
			}
				
			$sOrderUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Order&idp=0';
			$oElement->addAction(
				'order', bab_toHtml(bab_translate("Order")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/z-a.gif', $sOrderUrl, '');
		}
		else if('category' == $oElement->_type)
		{
			$iIdParent = $this->getId($sIdParent);
			$iId = $this->getId($oElement->_id);
			
			$sAddCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Create&idp=' . $iId;
			$oElement->addAction(
				'addCateg', bab_toHtml(bab_translate("Create a topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_category.png', $sAddCategUrl, '');
			
			$sDelCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=Delete&catdel=dummy&item=' . $iId . '&idp=' . $iIdParent;
			$oElement->addAction(
				'delCateg', bab_toHtml(bab_translate("Delete topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', $sDelCategUrl, '');
				
			$sAddTopicUrl = $GLOBALS['babUrlScript'] . '?tg=topics&idx=addtopic&cat=' . $iId;
			$oElement->addAction(
				'addTopic', bab_toHtml(bab_translate("Create new topic")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_topic.png', $sAddTopicUrl, '');
				
			$sOrderUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Order&idp=' . $iId;
			$oElement->addAction(
				'order', bab_toHtml(bab_translate("Order")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/z-a.gif', $sOrderUrl, '');


			if('N' == $this->_datas['enabled'])
			{
				$sEnableDisableUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&update=enable&iIdTopCat=' . $iId;
				$oElement->addAction(
					'enableDisable', bab_toHtml(bab_translate("Activate the section")), 
					$GLOBALS['babSkinPath'] . 'images/Puces/action_success.gif', $sEnableDisableUrl, '');
			}
			else 
			{
				$sEnableDisableUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&update=disable&iIdTopCat=' . $iId;
				$oElement->addAction(
					'enableDisable', bab_toHtml(bab_translate("Desactivate the section")), 
					$GLOBALS['babSkinPath'] . 'images/Puces/action_fail.gif', $sEnableDisableUrl, '');
			}

			$sRightUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=rights&item=' . $iId;
			$oElement->addAction(
				'right', bab_toHtml(bab_translate("Default rights")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/access.png', $sRightUrl, '');


			$oElement->setLink($GLOBALS['babUrlScript'] . '?tg=topcat&idx=Modify&item=' . $iId . '&idp=' . $iIdParent);
		}
		else if('topic' == $oElement->_type)
		{
			$iIdParent = $this->getId($sIdParent);
			$iId = $this->getId($oElement->_id);
			
			$sDelTopicUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=Delete&topdel=dummy&item=' . $iId . '&cat=' . $iIdParent;
			$oElement->addAction(
				'delCateg', bab_toHtml(bab_translate("Delete the topic")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', $sDelTopicUrl, '');
			
			$sRightUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=rights&item=' . $iId . '&cat=' . $iIdParent;
			$oElement->addAction(
				'right', bab_toHtml(bab_translate("Rights")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/access.png', $sRightUrl, '');

			$oElement->setLink($GLOBALS['babUrlScript'] . '?tg=topic&idx=Modify&item=' . $iId . '&cat=' . $iIdParent);
		}

	}
	
	function getId($sId)
	{
		static $iIdIdx = 1;
		if(!is_null($sId))
		{
			$aExploded = explode(BAB_TREE_VIEW_ID_SEPARATOR, $sId);
			if(count($aExploded) == 2)
			{
				return $aExploded[$iIdIdx];
			}
		}
		return 0;
	}
}
	

function topcatCreate($idp)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $enabled;
		var $no;
		var $yes;
		var $add;
		var $arrtmpl;
		var $counttmpl;
		var $arrdisptmpl;
		var $countdisptmpl;
		var $templatetxt;
		var $templateval;
		var $templateid;
		var $disptmpltxt;
		var $topcattxt;
		var $topcatid;
		var $topcatval;
		var $nonetxt;
		var $idp;
		var $selected;

		function temp($idp)
			{
			global $babBody, $babDB;

			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Section enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add");
			$this->templatetxt = bab_translate("Section template");
			$this->disptmpltxt = bab_translate("Display template");
			$this->topcattxt = bab_translate("Topics category parent");
			$this->nonetxt = "--- ".bab_translate("None")." ---";
			$this->idp = $idp;
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

			$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");

			$this->arrtopcats = array();
			if( $babBody->isSuperAdmin)
				{
				$this->arrtopcats[] = array( 'id'=> 0, 'title' => $this->nonetxt);
				}
			while( $arr = $babDB->db_fetch_array($res ))
				{
				$this->arrtopcats[] = array( 'id'=> $arr['id'], 'title' => $arr['title']);
				}
			$this->topcatscount = count($this->arrtopcats);

			}

		function getnexttemplate()
			{
			static $i = 0;
			if($i < $this->counttmpl)
				{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
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
				$i++;
				return true;
				}
			return false;
			}

		function getnexttopcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->topcatscount)
				{
				$arr = $this->arrtopcats[$i];
				$this->topcatid = $arr['id'];
				$this->topcatval = $arr['title'];
				if( $this->idp == $this->topcatid)
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($idp);
	$babBody->babecho( bab_printTemplate($temp,"topcats.html", "topcatcreate"));
	}

function topcatsList($idp)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $catchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $topcount;
		var $topcounturl;
		var $topics;
		var $topcats;
		var $topcatcount;
		var $topcatcounturl;
		var $arrparents = array();
		var $countparents;
		var $parentval;
		var $parenturl;
		var $burl;
		var $altbg = true;

		function temp($idp)
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Section disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->topics = bab_translate("Number of topics");
			$this->topcats = bab_translate("Number of topics categories");
			$this->db = $GLOBALS['babDB'];
			$req = "select c.* 
				FROM 
					".BAB_TOPICS_CATEGORIES_TBL." c, 
					".BAB_TOPCAT_ORDER_TBL." o 
				WHERE 
					id_dgowner=".$this->db->quote($babBody->currentAdmGroup)." 
					AND c.id=o.id_topcat 
					AND c.id_parent=".$this->db->quote($idp)." 
					AND type='1' 
				ORDER BY o.ordering
				";
			
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->idp = $idp;

			if( $idp != 0)
				{
				$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$idp."'");
				while($arr = $this->db->db_fetch_array($res))
					{
					if( $arr['id_parent'] == 0 )
						break;
					$this->arrparents[] = $arr['id_parent'];
					$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$arr['id_parent']."'");
					}
				$this->arrparents[] = 0;
				$this->arrparents = array_reverse($this->arrparents);	
				$this->arrparents[] = $idp;
				}
			$this->countparents = count($this->arrparents);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$this->arr['id']."&idp=".$this->idp;
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$this->arr['id']."'"));
				$this->topcatcount = $r['total'];
				$this->topcatcounturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countparents)
				{
				if( $this->arrparents[$i] == 0 )
					$this->parentval = bab_translate("Top");
				else
					$this->parentval = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arrparents[$i];
				if( $i == $this->countparents - 1 )
					$this->burl = false;
				else
					$this->burl = true;
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "topcats.html", "topcatslist"));
	return $temp->count;
	}


function orderTopcat($idp)
	{
	global $babBody;
	class temp
		{		

		var $sorta;
		var $sortd;
		var $idp;

		function temp($idp)
			{
			global $babBody, $BAB_SESS_USERID;
			if( $idp == 0 )
				$catname = bab_translate("Top");
			else
				$catname = bab_getTopicCategoryTitle($idp);

			$this->idp = $idp;
			$this->catname = "---- ".$catname." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			if( $idp == 0 && $babBody->isSuperAdmin )
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='0' order by ordering asc";
			else
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$idp."' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['type'] == 1)
					$this->topicval = bab_getTopicCategoryTitle($arr['id_topcat']);
				else if( $arr['type'] == 2)
					$this->topicval = bab_getCategoryTitle($arr['id_topcat']);
				else
					$this->topicval = "";
					
				$this->topicval = bab_toHtml($this->topicval);
					
				$this->topicid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatorder"));
	return $temp->count;
	}

function addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( $babBody->currentAdmGroup && $topcatid == 0 )
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}
	return bab_addTopicsCategory($name, $description, $benabled, $template, $disptmpl, $topcatid, $babBody->currentAdmGroup);

	}

function disableTopcats($topcats, $idp)
	{
	global $babBody, $babDB;
	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id_parent='".$idp."'";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($topcats) > 0 && in_array($row['id'], $topcats))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$babDB->db_query($req);
		}
	}

function disableEnableTopcat($iIdTopCat, $sEnable)
{
	global $babBody, $babDB;

	if('Y' == $sEnable || 'N' == $sEnable)
	{
		$sQuery = 'update ' . BAB_TOPICS_CATEGORIES_TBL . ' set enabled = ' . $babDB->quote($sEnable) . ' where id = ' . $babDB->quote($iIdTopCat);
		$babDB->db_query($sQuery);
	}
}

function saveOrderTopcats($idp, $listtopcats)
	{
	global $babBody, $babDB;
	
	for($i=0; $i < count($listtopcats); $i++)
		{
		$babDB->db_query("update ".BAB_TOPCAT_ORDER_TBL." set ordering='".($i+1)."' where id='".$listtopcats[$i]."'");
		}
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "List";

if( !isset($idp))
	$idp = 0;

if( isset($add))
	{
	addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid);
	$idp = $topcatid;
	}
elseif( isset($update))
	{
	if( $update == 'disable' || $update == 'enable' )
	{
		disableEnableTopcat($iIdTopCat, (($update == 'enable') ? 'Y' : 'N'));
	}
	if( $update == "order" )
		{
		saveOrderTopcats($idp, $listtopcats);
		}
	}

switch($idx)
	{
	case "Order":
		orderTopcat($idp);
		$babBody->title = bab_translate("Order a topic category");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
		break;
	case "Create":
		topcatCreate($idp);
		$babBody->title = bab_translate("Create a topic category");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		break;
	case "List":
	default:
		$babBody->title = bab_translate("Categories and topics");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$oArtTV = new bab_AdmArticleTreeView('oArtTV');
		$oArtTV->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES | BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS | 
			BAB_TREE_VIEW_MEMORIZE_OPEN_NODES | BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE | BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS);
		$oArtTV->setAction('');
		$oArtTV->order();
		$oArtTV->sort();
		$babBody->babecho($oArtTV->printTemplate());
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>