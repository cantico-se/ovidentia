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
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/treeincl.php";
include_once $babInstallPath."utilit/imgincl.php";

function isUserManager()
	{
	global $faqinfo, $BAB_SESS_USERID;
	if( $BAB_SESS_USERID == $faqinfo['id_manager'])
		{
		return true;
		}
	return false;
	}

function listCategories()
	{
	global $babBody;
	$arrid = array();
	class temp
		{
	
		var $arr = array();
		var $arrid = array();
		var $db;
		var $count;
		var $res;
		var $urlcategory;
		var $namecategory;

		function temp($arrid)
			{
			$this->db = $GLOBALS['babDB'];
			$this->count = count($arrid);
			$this->arrid = $arrid;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_FAQCAT_TBL." where id='".$this->arrid[$i]."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->arr = $this->db->db_fetch_array($res);
					$this->arr['description'] = $this->arr['description'];
					$this->urlcategory = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$this->arr['id'];
					$this->namecategory = $this->arr['category'];
					}
				$i++;
				return true;
				}		
			else
				return false;
			}
		}
		
	$db = $GLOBALS['babDB'];

	$langFilterValue = $GLOBALS['babLangFilter']->getFilterAsInt();
	switch($langFilterValue)
		{
		case 2:
			$req = "select * from ".BAB_FAQCAT_TBL." where lang='".$GLOBALS['babLanguage']."' or lang='*' or lang = ''";
			if ($GLOBALS['babApplyLanguageFilter'] == 'loose')
				$req.= " or id_manager = '" .$GLOBALS['BAB_SESS_USERID']. "'";
			break;
		case 1:
			$req = "select * from ".BAB_FAQCAT_TBL." where lang like '". substr($GLOBALS['babLanguage'], 0, 2) ."%' or lang='*' or lang = ''";
			if ($GLOBALS['babApplyLanguageFilter'] == 'loose')
				$req.= " or id_manager = '" .$GLOBALS['BAB_SESS_USERID']. "'";
			break;
		case 0:
		default:
			$req = "select * from ".BAB_FAQCAT_TBL;
			break;
		}
	$res = $db->db_query($req);

	while( $row = $db->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			array_push($arrid, $row['id']);
			}
		}

	$temp = new temp($arrid);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "categorylist"));

	return count($arrid);
	}


function FaqTableOfContents($idcat)
	{
	global $babBody;
	class temp
		{
		var $idcat;

		function temp($idcat)
			{
			global $babDB, $faqinfo;
			$this->idcat = $idcat;
			$this->faqname = $faqinfo['category'];
			$this->contentsname = bab_translate("CONTENTS");
			$this->modifytxt = bab_translate("Modify");
			$this->babTree  = new bab_arraytree(BAB_FAQ_TREES_TBL, $idcat, "");
			$this->arr = array();
			reset($this->babTree->nodes);
			$this->maxlevel = 0;
			while( $row=each($this->babTree->nodes) ) 
				{
				$this->arr[$row[1]['id']] = $row[1]['lf'];
				if( $row[1]['level'] > $this->maxlevel )
					{
					$this->maxlevel = $row[1]['level'];
					}
				}
			asort($this->arr);
			reset($this->arr);
			$this->arr = array_keys($this->arr);
			$this->maxlevel += 1;
			$this->padarr = array();

			if( isUserManager())
				{
				$this->update = true;
				}
			else
				{
				$this->update = false;
				}

			$this->res = $babDB->db_query("select fst.* from ".BAB_FAQ_SUBCAT_TBL." fst LEFT JOIN ".BAB_FAQ_TREES_TBL." ftt on ftt.id=fst.id_node where id_cat='".$this->idcat."' and ftt.id_user='".$this->idcat."' order by ftt.lf asc");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextpad()
			{
			global $babDB;
			static $i = 0;
			if( $i < count($this->padarr) -1)
				{
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}

		function getnextchild()
			{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
				{
				$row = $babDB->db_fetch_array($this->res);

				if( $faqinfo['id_root'] == $row['id_node'] )
					{
					$this->subcatname = '';
					$this->burl = false;
					}
				else
					{
					$this->subcatname = $row['name'];
					$this->burl = true;
					}

				if ( count($this->padarr) > 0 )
					{ 
					while ($this->babTree->getRightValue($this->padarr[count($this->padarr)-1]) < $this->babTree->getRightValue($row['id_node']))
					   { 
					   array_pop($this->padarr);
					   } 
					} 
				$this->padarr[] = $row['id_node'];
				if($this->arr[0] == $row['id_node'])
					{
					$this->first = 1;
					if (count($this->arr) == 1)
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}
				else
					{
					$this->first = 0;
					if( $this->babTree->getLastChild($this->babTree->getParentId($row['id_node'])) == $row['id_node'] )
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}
				$this->subcaturl = $GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$this->idcat."&idscat=".$row['id'];
				$this->modifycurl = $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyC&item=".$this->idcat."&ids=".$row['id'];
				$this->resq = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where idcat='".$this->idcat."' and id_subcat='".$row['id']."'");
				$this->countq = $babDB->db_num_rows($this->resq);
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextquestion()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countq)
				{
				$row = $babDB->db_fetch_array($this->resq);
				$this->idq = $row['id'];
				$this->questionname = $row['question'];
				$this->modifyqurl = $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$this->idcat."&idscat=".$row['id_subcat']."&idq=".$row['id'];
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$this->idcat."&idscat=".$row['id_subcat']."&idq=".$row['id'];
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}


		}
	$temp = new temp($idcat);
	$babBody->babecho(bab_printTemplate($temp,"faq.html", "tableofcontents"));
	return true;
	}


function FaqPrintContents($idcat)
	{
	global $babBody;
	class temp
		{
		var $idcat;

		function temp($idcat)
			{
			global $babDB, $faqinfo;
			$this->idcat = $idcat;
			$this->faqname = $faqinfo['category'];
			$this->contentsname = bab_translate("CONTENTS");
			$this->return = bab_translate("Go to Top");
			$this->babTree  = new bab_arraytree(BAB_FAQ_TREES_TBL, $idcat, "");
			$this->arr = array();
			reset($this->babTree->nodes);
			$this->maxlevel = 0;
			while( $row=each($this->babTree->nodes) ) 
				{
				$this->arr[$row[1]['id']] = $row[1]['lf'];
				if( $row[1]['level'] > $this->maxlevel )
					{
					$this->maxlevel = $row[1]['level'];
					}
				}
			asort($this->arr);
			reset($this->arr);
			$this->arr = array_keys($this->arr);
			$this->maxlevel += 1;
			$this->padarr = array();

			if( isUserManager())
				{
				$this->update = true;
				}
			else
				{
				$this->update = false;
				}

			$this->res = $babDB->db_query("select fst.* from ".BAB_FAQ_SUBCAT_TBL." fst LEFT JOIN ".BAB_FAQ_TREES_TBL." ftt on ftt.id=fst.id_node where id_cat='".$this->idcat."' and ftt.id_user='".$this->idcat."' order by ftt.lf asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->arrquestions = array();
			$this->bresponse = 0;
			}

		function getnextpad()
			{
			global $babDB;
			static $i = 0;
			if( $i < count($this->padarr) -1)
				{
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}

		function getnextchild()
			{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
				{
				$row = $babDB->db_fetch_array($this->res);

				if( $faqinfo['id_root'] == $row['id_node'] )
					{
					$this->subcatname = '';
					}
				else
					{
					$this->subcatname = $row['name'];
					}

				if ( count($this->padarr) > 0 )
					{ 
					while ($this->babTree->getRightValue($this->padarr[count($this->padarr)-1]) < $this->babTree->getRightValue($row['id_node']))
					   { 
					   array_pop($this->padarr);
					   } 
					} 
				$this->padarr[] = $row['id_node'];
				if($this->arr[0] == $row['id_node'])
					{
					$this->first = 1;
					if (count($this->arr) == 1)
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}
				else
					{
					$this->first = 0;
					if( $this->babTree->getLastChild($this->babTree->getParentId($row['id_node'])) == $row['id_node'] )
						{
						$this->leaf = 1;
						}
					else
						{
						$this->leaf = 0;
						}
					}
				$this->resq = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where idcat='".$this->idcat."' and id_subcat='".$row['id']."'");
				$this->countq = $babDB->db_num_rows($this->resq);
				$i++;
				return true;
				}
			else
				{
				if( $this->count > 0 )
					$babDB->db_data_seek($this->res, 0);
				$this->bresponse = 1;
				$i = 0;
				return false;
				}
			}

		function getnextquestion()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countq)
				{
				$row = $babDB->db_fetch_array($this->resq);
				$this->idq = $row['id'];
				$this->questionname = $row['question'];
				if( $this->bresponse )
					{
					$this->response = bab_replace($row['response']);
					}
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$this->idcat."&idscat=".$row['id_subcat']."&idq=".$row['id'];
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}


		}
	$temp = new temp($idcat);
	echo bab_printTemplate($temp,"faqprint.html", "contents");
	return true;
	}

function listSubCategoryQuestions($idcat, $idscat)
	{
	global $babBody;
	class temp
		{
		
		var $arr1 = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $index=0;
		var $return;
		var $indexquestions;
		var $sitename;
		var $urlsite;

		function temp($idcat, $idscat)
			{
			global $faqinfo;
			$this->return = bab_translate("Go to Top");
			$this->db = $GLOBALS['babDB'];
			$this->faqname = $faqinfo['category'];
			list($this->subcatname) = $this->db->db_fetch_row($this->db->db_query("select name from ".BAB_FAQ_SUBCAT_TBL." where id='".$idscat."'"));
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='".$idcat."' and id_subcat='".$idscat."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->question = $arr['question'];
				$this->idq = $arr['id'];
				$GLOBALS['babWebStat']->addFaqsQuestion($arr['id']);
				$i++;
				$this->index++;
				return true;
				}
			else
				{
				if( $this->count > 0 )
					{
					$this->db->db_data_seek($this->res, 0);
					}
				$this->index = 0;
				return false;
				}
			}
		
		function getnextbis()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->question = $arr['question'];
				$this->response = bab_replace($arr['response']);
				$this->idq = $arr['id'];
				$i++;
				$this->index++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcat, $idscat);
	$babBody->babecho(bab_printTemplate($temp,"faq.html", "subcatquestions"));
	}



function viewQuestion($idcat, $idscat, $id)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $return;
		var $returnurl;

		function temp($idcat, $idscat, $id)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['response'] = bab_replace($this->arr['response']);
			$this->returnurl = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$idcat."&idscat=".$idscat;
			$this->return = bab_translate("Return to Questions");
			}

		}

	$temp = new temp($idcat, $idscat, $id);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "viewquestion"));
	return true;
	}

function viewPopupQuestion($id)
	{
	global $babBody;

	class temp
		{
	
		var $arr = array();
		var $db;
		var $res;
		var $more;
		var $close;


		function temp($id)
			{
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='".$id."'";
			$res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($res);
			if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $this->arr['idcat']) || isUserManager())
				{
				$GLOBALS['babWebStat']->addFaqsQuestion($id);
				$this->arr['response'] = bab_replace($this->arr['response']);
				}
			else
				{
				$this->arr['question'] = '';
				$this->arr['response'] = bab_replace("Access denied");
				}
			}
		}
	
	$temp = new temp($id);
	echo bab_printTemplate($temp,"faq.html", "popupquestion");
	}

function faqPrint($idcat, $idscat)
	{
	global $babBody;
	class temp
		{
		
		var $arr1 = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $index=0;
		var $return;
		var $indexquestions;
		var $sitename;
		var $urlsite;

		function temp($idcat, $idscat)
			{
			global $faqinfo;
			$this->return = bab_translate("Go to Top");
			$this->indexquestions = bab_translate("Index of questions");
			$this->db = $GLOBALS['babDB'];
			$this->faqname = $faqinfo['category'];
			if( !empty($idscat) )
				{
				list($this->subcatname) = $this->db->db_fetch_row($this->db->db_query("select name from ".BAB_FAQ_SUBCAT_TBL." where id='".$idscat."'"));
				}
			else
				{
				$this->subcatname = '';
				list($idscat) = $this->db->db_fetch_row($this->db->db_query("select id from ".BAB_FAQ_SUBCAT_TBL." where id_node='".$faqinfo['id_root']."'"));
				}
			$req = "select * from ".BAB_FAQQR_TBL." where idcat='".$idcat."' and id_subcat='".$idscat."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->idq = $arr['id'];
				$this->question = $arr['question'];
				$GLOBALS['babWebStat']->addFaqsQuestion($arr['id']);
				$i++;
				$this->index++;
				return true;
				}
			else
				{
				if( $this->count > 0 )
					{
					$this->db->db_data_seek($this->res, 0);
					}
				$this->index = 0;
				return false;
				}
			}
		
		function getnextbis()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->idq = $arr['id'];
				$this->question = $arr['question'];
				$this->response = bab_replace($arr['response']);
				$i++;
				$this->index++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcat, $idscat);
	echo bab_printTemplate($temp,"faqprint.html", "subcategory");
	}


function addQuestion($idcat, $idscat)
	{
	global $babBody;
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;
		var $msie;

		function temp($idcat, $idscat)
			{
			global $babDB;
			$this->subcattxt = bab_translate("Sub category");
			$this->question = bab_translate("Question");
			$this->response = bab_translate("Response");
			$this->add = bab_translate("Add");
			$this->idcat = $idcat;
			$this->idscat = $idscat;
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$this->idcat."'");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextsubcat()
			{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idsubcat = $arr['id'];
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
					{
					$this->subcatname = $faqinfo['category'];
					}
				if( $this->idsubcat == $this->idscat)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcat, $idscat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestioncreate"));
	}

function addSubCategory($idcat, $idscat)
	{
	global $babBody;
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;
		var $msie;

		function temp($idcat, $idscat)
			{
			global $babDB;
			$this->subcat = bab_translate("Sub category");
			$this->add = bab_translate("Add");
			$this->idcat = $idcat;
			$this->idscat = $idscat;
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$this->idcat."'");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextsubcat()
			{
			global $babDB, $faqinfo;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idsubcat = $arr['id'];
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
					{
					$this->subcatname = $faqinfo['category'];
					}
				if( $this->idsubcat == $this->idscat)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($idcat, $idscat);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admsubcatcreate"));
	}



function modifyQuestion($item, $idscat, $idq)
	{
	global $babBody;
	if( !isset($idq))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid question !!");
		return;
		}
	class temp
		{
		var $questiontxt;
		var $responsetxt;
		var $add;
		var $idcat;

		var $db;
		var $arr = array();
		var $res;
		var $msie;

		function temp($idcat, $idscat, $idq)
			{
			$this->questiontxt = bab_translate("Question");
			$this->responsetxt = bab_translate("Response");
			$this->subcattxt = bab_translate("Sub category");
			$this->add = bab_translate("Update Question");
			$this->idcat = $idcat;
			$this->idscat = $idscat;
			$this->idq = $idq;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='".$idq."'";
			$res = $this->db->db_query($req);
			$arr = $this->db->db_fetch_array($res);
			$this->question = htmlentities($arr['question']);
			$this->response = htmlentities($arr['response']);
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;

			$this->res = $this->db->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$this->idcat."'");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnextsubcat()
			{
			global $faqinfo;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->idsubcat = $arr['id'];
				$this->subcatname = $arr['name'];
				if( empty($this->subcatname))
					{
					$this->subcatname = $faqinfo['category'];
					}
				if( $this->idsubcat == $this->idscat )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($item, $idscat, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"faq.html", "admquestionmodify"));
	}

function deleteQuestion($item, $idq)
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

		function temp($item, $idq)
			{
			$this->message = bab_translate("Are you sure you want to delete this question");
			$this->title = "";
			$this->warning = bab_translate("WARNING: This operation will delete question and its response"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idq=".$idq."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idq=".$idq;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($item, $idq);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function modifySubCategory($idcat, $idscat, $ids)
	{
	global $babBody;
	class temp
		{
		var $question;
		var $response;
		var $add;
		var $idcat;
		var $msie;
		var $del;

		function temp($idcat, $idscat, $ids)
			{
			global $babDB;
			$this->subcat = bab_translate("Sub category");
			$this->add = bab_translate("Modify");
			$this->idcat = $idcat;
			$this->idscat = $idscat;
			$this->ids = $ids;
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id='".$ids."'"));
			$this->subcatval = $arr['name'];
			$this->bdelete = false;
			list($countq) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FAQQR_TBL." where idcat='".$idcat."' and id_subcat='".$ids."'"));
			if( !$countq )
				{
				$babTree = new bab_arraytree(BAB_FAQ_TREES_TBL, $idcat);
				if( !$babTree->hasChildren($arr['id_node']))
					{
					$this->bdelete = true;
					$this->del = bab_translate("Delete");
					}
				}		
			}
		}

	$temp = new temp($idcat, $idscat, $ids);
	$babBody->babecho( bab_printTemplate($temp,"faq.html", "admsubcatmodify"));
	}


function saveQuestion($item, $idscat, $question, $response)
	{
	global $faqinfo;

	if( empty($question) || empty($response))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
		}
	if( bab_isMagicQuotesGpcOn())
		{
		$question = stripslashes(bab_stripDomainName($question));
		$response = stripslashes(bab_stripDomainName($response));
		}

	$db = $GLOBALS['babDB'];

	if( empty($idscat))
		{
		$idscat = $faqinfo['id_root'];
		}

	$query = "insert into ".BAB_FAQQR_TBL." (idcat, id_subcat, question) values ('" .$item. "', '" .$idscat. "', '" .addslashes($question). "')";
	$db->db_query($query);
	$id = $db->db_insert_id();

	$ar = array();
	$response = imagesReplace($response, $id."_faq_", $ar);

	$query = "update ".BAB_FAQQR_TBL." set response='".addslashes(bab_stripDomainName($response))."' where id='".$id."'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
	}

function saveSubCategory($item, $idscat, $subcat)
	{
	global $faqinfo;

	if( empty($subcat) )
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}
	if( bab_isMagicQuotesGpcOn())
		{
		$subcat = stripslashes(bab_stripDomainName($subcat));
		}

	$db = $GLOBALS['babDB'];

	if( empty($idscat))
		{
		$idscat = $faqinfo['id_root'];
		}

	$babTree = new bab_dbtree(BAB_FAQ_TREES_TBL, $item);
	list($idnode) = $db->db_fetch_array($db->db_query("select id_node from ".BAB_FAQ_SUBCAT_TBL." where id='".$idscat."'"));
	$idnode = $babTree->add($idnode);

	$query = "insert into ".BAB_FAQ_SUBCAT_TBL." (id_cat, id_node, name) values ('" .$item. "', '" .$idnode. "', '" .addslashes($subcat). "')";
	$db->db_query($query);
	}

function updateSubCategory($item, $idscat, $ids, $subcat)
	{
	global $faqinfo;

	if( empty($subcat) )
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}
	if( bab_isMagicQuotesGpcOn())
		{
		$subcat = stripslashes(bab_stripDomainName($subcat));
		}

	$db = $GLOBALS['babDB'];

	$query = "update ".BAB_FAQ_SUBCAT_TBL." set name='".addslashes($subcat)."' where id='".$ids."'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
	}

function deleteSubCategory($item, $idscat, $ids)
	{
	global $faqinfo;

	$db = $GLOBALS['babDB'];

	$query = "delete from ".BAB_FAQ_SUBCAT_TBL." where id='".$ids."'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
	}

function updateQuestion($idq, $newidscat, $question, $response)
	{
	if( empty($question) || empty($response))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide question and response !!");
		return;
		}

	if( bab_isMagicQuotesGpcOn())
		{
		$question = stripslashes(bab_stripDomainName($question));
		$response = stripslashes(bab_stripDomainName($response));
		}

	$ar = array();
	$response = imagesReplace($response, $idq."_faq_", $ar);

	$db = $GLOBALS['babDB'];
	$query = "update ".BAB_FAQQR_TBL." set question='".addslashes($question)."', response='".addslashes(bab_stripDomainName($response))."', id_subcat='".$newidscat."' where id = '".$idq."'";
	$db->db_query($query);

	}

function confirmDeleteQuestion($item, $idq)
	{
	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select response from ".BAB_FAQQR_TBL." where id='".$idq."'"));
	deleteImages($arr['response'], $idq, "faq");
	$req = "delete from ".BAB_FAQQR_TBL." where id = '".$idq."'";
	$res = $db->db_query($req);
	}


/* main */
if(isset($item))
	{
	$faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$item."'"));
	}
if(!isset($idx))
	{
	$idx = "Categories";
	}

if(!isset($idscat))
	{
	$idscat = 0;
	}

if( isUserManager() )
{
if( isset($addquestion))
	{
	saveQuestion($item, $newidscat, $question, $response);
	}
else if( isset($updatequestion))
	{
	updateQuestion($idq, $newidscat, $question, $response);
	}
else if( isset($action) && $action == "Yes")
	{
	confirmDeleteQuestion($item, $idq);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
	}
else if( isset($addsc) && $addsc == "addscat")
	{
	saveSubCategory($item, $newidscat, $subcat);
	}
else if( isset($modsc) && $modsc == "modscat")
	{
	if( isset($bdel))
		{
		deleteSubCategory($item, $idscat, $ids);
		}
	else
		{
		updateSubCategory($item, $idscat, $ids, $subcat);
		}
	}
}

switch($idx)
	{
	case "questions":
		$babBody->title = bab_translate("Contents");
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			{
			$GLOBALS['babWebStat']->addFaq($item);
			FaqTableOfContents($item);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("questions", bab_translate("Contents"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item);
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			if( isUserManager())
				{
				$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item);
				$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item);
				}
			}
		break;

	case "listq":
		$babBody->title = $faqinfo['category'];
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			{
			$GLOBALS['babWebStat']->addFaq($item);
			listSubCategoryQuestions($item, $idscat);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("questions", bab_translate("Contents"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			$babBody->addItemMenu("listq", bab_translate("Questions"),$GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$item."&idscat=".$idscat);
			if( isUserManager())
				{
				$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item."&idscat=".$idscat);
				$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item."&idscat=".$idscat);
				}
			}
		break;

	case "viewpq":
		viewPopupQuestion($idq);
		exit;

	case "viewq":
		$babBody->title = $faqinfo['category'];
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
			{
			viewQuestion($item, $idscat, $idq);
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			$babBody->addItemMenu("questions", bab_translate("Contents"),$GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("Print Friendly", bab_translate("Print Friendly"),$GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenuAttributes("Print Friendly", "target=_blank");
			if( isUserManager())
				$babBody->addItemMenu("ModifyQ", bab_translate("Edit"),$GLOBALS['babUrlScript']."?tg=faq&idx=ModifyQ&item=".$item."&idscat=".$idscat."&idq=".$idq);
			}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete question");
		if( isUserManager())
			{
			deleteQuestion($item, $idq);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=$item&idq=$idq");
			}
		break;

	case "addq":
		$babBody->title = bab_translate("Add question");
		if( isUserManager())
			{
			if( !isset($idscat)) { $idscat=0;}
			addQuestion($item, $idscat);
			$babBody->addItemMenu("questions", bab_translate("Contents"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item);
			$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item);
			}
		break;

	case "addsc":
		$babBody->title = bab_translate("Add sub category");
		if( isUserManager())
			{
			if( !isset($idscat)) { $idscat=0;}
			addSubCategory($item, $idscat);
			$babBody->addItemMenu("questions", bab_translate("Contents"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item);
			$babBody->addItemMenu("addq", bab_translate("Add Question"), $GLOBALS['babUrlScript']."?tg=faq&idx=addq&item=".$item);
			$babBody->addItemMenu("addsc", bab_translate("Add sub category"), $GLOBALS['babUrlScript']."?tg=faq&idx=addsc&item=".$item);
			}
		break;

	case "ModifyQ":
		$babBody->title = bab_translate("Modify question");
		if( isUserManager())
			{
			modifyQuestion($item, $idscat, $idq);
			$babBody->addItemMenu("questions", bab_translate("Contents"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=faq&idx=Delete&item=$item"."&idscat=".$idscat."&idq=".$idq);
			}
		break;

	case "ModifyC":
		$babBody->title = bab_translate("Modify subcategory");
		if( isUserManager())
			{
			modifySubCategory($item, $idscat, $ids);
			$babBody->addItemMenu("questions", bab_translate("Contents"), $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$item."&idscat=".$idscat);
			$babBody->addItemMenu("ModifyC", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=faq&idx=ModifyC&item=$item"."&idscat=".$idscat);
			}
		break;

	case "Print":
		if( bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $item))
		{
			if( empty($idscat))
				{
				FaqPrintContents($item);
				}
			else
				{
				faqPrint($item, $idscat);
				}
		}
		exit();
		break;

	default:
	case "Categories":
		$babBody->title = bab_translate("List of all faqs");
		if( listCategories() > 0 )
			{
			$babBody->addItemMenu("Categories", bab_translate("Categories"),$GLOBALS['babUrlScript']."?tg=faq&idx=Categories");
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>