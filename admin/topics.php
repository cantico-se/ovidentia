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
include $babInstallPath."utilit/topincl.php";

function addCategory($cat, $ncat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict)
	{
	global $babBody;
	class temp
		{
		var $title;
		var $description;
		var $approver;
		var $add;
		var $msie;
		var $idcat;
		var $db;
		var $count;
		var $res;
		var $selected;
		var $topcat;
		var $modcom;
		var $notiftxt;
		var $yes;
		var $no;
		var $langLabel;
		var $langValue;
		var $langselected;
		var $langFiles;
		var $countLangFiles;
		var $arttmpltxt;
		var $arttmplval;
		var $arttmplid;
		var $arttmplselected;
		var $arttmpl;
		var $atid;
		var $arrarttmpl;
		var $countarttmpl;
		var $disptmpltxt;
		var $disptmplval;
		var $disptmplid;
		var $disptmplselected;
		var $disptmpl;
		var $disptid;
		var $arrdisptmpl;
		var $countdisptmpl;
		var $restrictysel;
		var $restrictnsel;
		var $restricttxt;

		function temp($cat, $ncat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict)
			{
			global $babBody;
			$this->topcat = bab_translate("Topic category");
			$this->title = bab_translate("Topic");
			$this->desctitle = bab_translate("Description");
			$this->approver = bab_translate("Topic manager");
			$this->modcom = bab_translate("Approbation schema for comments");
			$this->modart = bab_translate("Approbation schema for articles");
			$this->notiftxt = bab_translate("Notify group members by mail");
			$this->arttmpltxt = bab_translate("Article's model");
			$this->disptmpltxt = bab_translate("Display template");
			$this->restricttxt = bab_translate("Articles's authors can restrict access to articles");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->none = bab_translate("None");
			$this->tgval = "topics";
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->item = "";
			$this->cat = $cat;
			if(empty($description))
				$this->description = "";
			else
				$this->description = $description;
			if(empty($category))
				$this->category = "";
			else
				$this->category = $category;
			if(empty($managerid))
				{
				$this->managerid = "";
				$this->managerval = "";
				}
			else
				{
				$this->managerid = $managerid;
				$this->managerval = bab_getUserName($managerid);
				}
			if(empty($sacom))
				$this->sacom = 0;
			else
				$this->sacom = $sacom;
			if(empty($saart))
				$this->saart = 0;
			else
				$this->saart = $saart;

			if(empty($atid))
				$this->atid = "";
			else
				$this->atid = $atid;

			if(empty($disptid))
				$this->disptid = "";
			else
				$this->disptid = $disptid;

			if(empty($bnotif))
				$bnotif = "N";

			if( $bnotif == "N")
				{
				$this->notifnsel = "selected";
				$this->notifysel = "";
				}
			else
				{
				$this->notifnsel = "";
				$this->notifysel = "selected";
				}

			if(empty($restrict))
				$restrict = "N";

			if( $restrict == "N")
				{
				$this->restrictnsel = "selected";
				$this->restrictysel = "";
				}
			else
				{
				$this->restrictnsel = "";
				$this->restrictysel = "selected";
				}

			if(empty($ncat))
				$this->ncat = $cat;
			else
				$this->ncat = $ncat;
			$this->bdel = false;
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->idcat = $cat;
			$this->db = $GLOBALS['babDB'];

			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->sares = $this->db->db_query($req);
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $this->db->db_num_rows($this->sares);
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";

			$file = "articlestemplate.html";
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
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrarttmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrarttmpl ))
							$this->arrarttmpl[] = substr($arr[$i], 5);
					}
				}
			$this->countarttmpl = count($this->arrarttmpl);

			$file = "topicsdisplay.html";
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
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrdisptmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrdisptmpl ))
							$this->arrdisptmpl[] = substr($arr[$i], 5);
					}
				}
			$this->countdisptmpl = count($this->arrdisptmpl);
			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->toptitle = $this->arr['title'];
				$this->topid = $this->arr['id'];
				if( $this->arr['id'] == $this->ncat )
					$this->topselected = "selected";
				else
					$this->topselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextschapp()
			{
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if( $this->said == $this->saart )
					$this->saartsel = "selected";
				else
					$this->saartsel = "";
				$this->sacomsel = "";
				$i++;
				return true;
				}
			else
				{
				if( $this->sacount > 0 )
					$this->db->db_data_seek($this->sares, 0);
				$i = 0;
				return false;
				}
			}
			
		function getnextlang()
			{
			static $i = 0;
			if($i < $this->countLangFiles)
				{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $GLOBALS['babLanguage'])
					{
					$this->langselected = 'selected';
					}
				else
					{
					$this->langselected = '';
					}
				$i++;
				return true;
				}
			return false;
			}

		function getnextarttmpl()
			{
			static $i = 0;
			if($i < $this->countarttmpl)
				{
				$this->arttmplid = $this->arrarttmpl[$i];
				$this->arttmplval = $this->arrarttmpl[$i];
				if( $this->arttmplid == $this->atid )
					$this->arttmplselected = "selected";
				else
					$this->arttmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		function getnextdisptmpl()
			{
			static $i = 0;
			if($i < $this->countdisptmpl)
				{
				$this->disptmplid = $this->arrdisptmpl[$i];
				$this->disptmplval = $this->arrdisptmpl[$i];
				if( $this->disptmplid == $this->disptid )
					$this->disptmplselected = "selected";
				else
					$this->disptmplselected = "";
				$i++;
				return true;
				}
			return false;
			}
		}

	$temp = new temp($cat, $ncat, $category, $description, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorycreate"));
	}

function listCategories($cat)
	{
	global $babBody;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $select;
		var $approver;
		var $urlcategory;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $idcat;
		var $groups;
		var $comments;
		var $submit;
		var $urlgroups;
		var $urlcomments;
		var $urlsubmit;

		function temp($cat)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->groups = bab_translate("View");
			$this->comments = bab_translate("Comment");
			$this->submit = bab_translate("Submit");
			$this->articles = bab_translate("Article") ."(s)";
			$this->db = $GLOBALS['babDB'];
			$req = "select id_topcat from ".BAB_TOPCAT_ORDER_TBL." where type='2' and id_parent='".$cat."' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->idcat = $cat;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->select = "checked";
				else
					$this->select = "";
				$arr = $this->db->db_fetch_array($this->res);
					
				$this->arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$arr['id_topcat']."'"));
				$this->urlgroups = $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->urlcomments = $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->urlsubmit = $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->arr['description'] = $this->arr['description'];;
				$this->urlcategory = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->namecategory = $this->arr['category'];
				$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['id_approver']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->approver = bab_composeUserName($arr2['firstname'], $arr2['lastname']);
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='Y' and archive='N'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$this->arr['id']."&cat=".$this->idcat;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorylist"));
	return $temp->count;
	}

function saveCategory($category, $description, $cat, $sacom, $saart, $managerid, $bnotif, $lang, $atid, $disptid, $restrict)
	{
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a category !!");
		return false;
		}

	if( empty($managerid))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide topic manager !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$category = addslashes($category);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_TBL." where category='".$category."' and id_cat='".$cat."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This topic already exists");
		return false;
		}

	$query = "insert into ".BAB_TOPICS_TBL." (id_approver, category, description, id_cat, idsaart, idsacom, notify, lang, article_tmpl, display_tmpl, restrict_access) values ('" .$managerid. "', '" . $category. "', '" . $description. "', '" . $cat. "', '" . $saart. "', '" . $sacom. "', '" . $bnotif. "', '" .$lang. "', '" .$atid. "', '" .$disptid. "', '" .$restrict. "')";
	$db->db_query($query);
	$id = $db->db_insert_id();

	$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$cat."'");
	$arr = $db->db_fetch_array($res);
	if( isset($arr[0]))
		$ord = $arr[0] + 1;
	else
		$ord = 1;
	$db->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$id. "', '2', '" . $ord. "', '".$cat."')");
	
	return true;
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if(!isset($idx))
	{
	$idx = "list";
	}


if( isset($add) )
	{
	if(!saveCategory($category, $topdesc, $ncat, $sacom, $saart, $managerid, $bnotif, $lang, $atid, $disptid, $restrict))
		$idx = "addtopic";
	else
		{
		$cat = $ncat;
		}
	}

if( !isset($idp))
{
	list($idp) = $babDB->db_fetch_row($babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'"));
}

switch($idx)
	{
	case "addtopic":
		$babBody->title = bab_translate("Create new topic");
		addCategory($cat, $ncat, $category, $topdesc, $managerid, $saart, $sacom, $bnotif, $atid, $disptid, $restrict);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("addtopic", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
		break;

	default:
	case "list":
		$catname = bab_getTopicCategoryTitle($cat);
		$babBody->title = bab_translate("List of all topics"). " [ " . $catname . " ]";
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		if( listCategories($cat) > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
			}
		else
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
			exit;
			}

		$babBody->addItemMenu("addtopic", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
