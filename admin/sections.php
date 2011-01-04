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

function getSectionName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function sectionsList()
	{
	global $babBody;
	class temp
		{
		var $title;
		var $urltitle;
		var $url;
		var $description;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $counta;
		var $resa;
		var $countcat;
		var $rescat;
		var $secchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $idvalue;
		var $access;
		var $accessurl;
		var $groups;
		var $opttxt;
		var $altbg = true;
		var $sections = array();

		function temp()
			{
			global $babBody;
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->access = bab_translate("Rights");
			$this->groups = bab_translate("View");
			$this->opttxt = bab_translate("Optional(fem)");
			$this->db = &$GLOBALS['babDB'];
			

			$req = "select * from ".BAB_SECTIONS_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$res = $this->db->db_query($req);
			$this->processres($res, '-2');

			/* don't get Administrator section */
			if( $babBody->currentAdmGroup == 0 )
				{
				$resa = $this->db->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id > '1'");
				$this->processres($resa, '-1');
				}
			else
				$this->counta = 0;

			$rescat = $this->db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");
			$this->processres($rescat, '-3');
			
			bab_sort::ksort($this->sections, bab_sort::CASE_INSENSITIVE);
			}
			
		function processres($res, $suffix) {
			while ($arr = $this->db->db_fetch_array($res)) {
				$arr['title'] = bab_translate($arr['title']);
				$arr['description'] = bab_translate($arr['description']);
				$idvalue = $arr['id'].$suffix;
				
				$opt = '5-1' != $idvalue;
				
				if( $arr['enabled'] == "N")
					$secchecked = "checked";
				else
					$secchecked = "";
				if( $arr['optional'] == "Y")
					$optchecked = "checked";
				else
					$optchecked = "";
					
				if ('-2' === $suffix) {
					$url 		= bab_toHtml($GLOBALS['babUrlScript'].'?tg=section&idx=Modify&item='.$arr['id']);
					$accessurl 	= bab_toHtml($GLOBALS['babUrlScript'].'?tg=section&idx=Groups&item='.$arr['id']);
				} else {
					$url 		= false;
					$accessurl 	= false;
				}
					
					
				$this->sections[mb_strtolower($arr['title']).$idvalue] = array(
					'title' 		=> $arr['title'],
					'description' 	=> $arr['description'],
					'idvalue'		=> $idvalue,
					'opt'			=> $opt,
					'secchecked'	=> $secchecked,
					'optchecked'	=> $optchecked,
					'url'			=> $url,
					'accessurl'		=> $accessurl
				);
			}
		}
			
		function getnext() {
			if (list(,$arr) = each($this->sections)) {
				
				$this->altbg 		= !$this->altbg;
				$this->title 		= $arr['title'];
				$this->description 	= $arr['description'];
				$this->idvalue 		= $arr['idvalue'];
				$this->opt			= $arr['opt'];
				$this->secchecked 	= $arr['secchecked'];
				$this->optchecked 	= $arr['optchecked'];
				$this->url 			= $arr['url'];
				$this->accessurl 	= $arr['accessurl'];
				
				return true;
			}
			
			return false;
		}
		

		
	}

	$temp = new temp();
	if(0 === count($temp->sections))
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		exit;
		}
	$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionslist"));
	return count($temp->sections);
	}

function sectionsOrder()
	{
	global $babBody;
	class temp
		{
		var $id;
		var $arr = array();
		var $db;
		var $count = 0;
		var $res;
		var $moveup;
		var $movedown;
		var $arrleft = array();
		var $arright = array();

		function temp()
			{
			global $babBody;

			$this->listleftsectxt = "----------------- ". bab_translate("Left sections") . " -----------------";
			$this->listrightsectxt = "----------------- ". bab_translate("Right sections") . " -----------------";
			$this->update = bab_translate("Update");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->db = &$GLOBALS['babDB'];

			$this->resleft = $this->db->db_query("select * from ".BAB_SECTIONS_ORDER_TBL." order by ordering asc");
			while ( $arr = $this->db->db_fetch_array($this->resleft))
				{
				switch( $arr['type'] )
					{
					case "1":
						if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						break;
					case "3":
						$rr = $this->db->db_fetch_array($this->db->db_query("select id, id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$arr['id_section']."'"));
						if( $babBody->currentAdmGroup == 0  && $babBody->isSuperAdmin)
						{
							if( $rr['id_dgowner'] == 0 )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
							else
							{
							if( $arr['position'] == 0 && !in_array($rr['id_dgowner']."-0", $this->arrleft))
								{
								$this->arrleft[] = $rr['id_dgowner']."-0";
								}
							else if( $arr['position'] == 1 && !in_array($rr['id_dgowner']."-1", $this->arright) )
								{
								$this->arright[] = $rr['id_dgowner']."-1";
								}
							}

						}
						else if( $babBody->currentAdmGroup == $rr['id_dgowner'] )
						{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							
						}
						break;
					case "4":
						if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						break;
					default:
						$rr = $this->db->db_fetch_array($this->db->db_query("select id, id_dgowner from ".BAB_SECTIONS_TBL." where id ='".$arr['id_section']."'"));
						if( $babBody->currentAdmGroup == 0  && $babBody->isSuperAdmin)
						{
							if( $rr['id_dgowner'] == 0 )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
							else
							{
							if( $arr['position'] == 0 && !in_array($rr['id_dgowner']."-0", $this->arrleft))
								{
								$this->arrleft[] = $rr['id_dgowner']."-0";
								}
							else if( $arr['position'] == 1 && !in_array($rr['id_dgowner']."-1", $this->arright) )
								{
								$this->arright[] = $rr['id_dgowner']."-1";
								}
							}

						}
						else if( $babBody->currentAdmGroup == $rr['id_dgowner'] )
						{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							
						}
						break;
					}
				}
			$this->countleft = count($this->arrleft);
			$this->countright = count($this->arright);
			}


		function getDelegationName($id)
			{
			$res = $this->db->db_query("SELECT name FROM ".BAB_DG_GROUPS_TBL." WHERE id='".$id."'");
			list($name) = $this->db->db_fetch_array($res);

			return $name;
			}

		function getnextsecleft()
			{
			static $i = 0;
			if( $i < $this->countleft)
				{
				$rr = explode('-',$this->arrleft[$i]);
				if( count($rr) > 1 )
					{
					$this->listleftsecval = "[[".$this->getDelegationName($rr[0])."(". bab_translate("Left").")]]";
					$this->secid = $this->arrleft[$i];
					}
				else
					{
					$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_SECTIONS_ORDER_TBL." where id='".$rr[0]."'"));
					switch( $arr['type'] )
						{
						case "1":
							$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id ='".$arr['id_section']."'";
							break;
						case "3":
							$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$arr['id_section']."'";
							break;
						case "4":
							$req = "select * from ".BAB_ADDONS_TBL." where id ='".$arr['id_section']."'";
							break;
						default:
							$req = "select * from ".BAB_SECTIONS_TBL." where id ='".$arr['id_section']."'";
							break;
						}
					$res2 = $this->db->db_query($req);
					$arr2 = $this->db->db_fetch_array($res2);
					if( $arr['type'] == "1" )
						$this->listleftsecval = bab_translate($arr2['title']);
					else
						$this->listleftsecval = $arr2['title'];
					$this->secid = $this->arrleft[$i];
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextsecright()
			{
			static $j = 0;
			if( $j < $this->countright)
				{
				$rr = explode('-',$this->arright[$j]);
				if( count($rr) > 1)
					{
					$this->listrightsecval = "[[".$this->getDelegationName($rr[0])."(". bab_translate("Right").")]]";
					$this->secid = $this->arright[$j];
					}
				else
					{
					$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_SECTIONS_ORDER_TBL." where id='".$rr[0]."'"));
					switch( $arr['type'] )
						{
						case "1":
							$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id ='".$arr['id_section']."'";
							break;
						case "3":
							$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$arr['id_section']."'";
							break;
						case "4":
							$req = "select * from ".BAB_ADDONS_TBL." where id ='".$arr['id_section']."'";
							break;
						default:
							$req = "select * from ".BAB_SECTIONS_TBL." where id ='".$arr['id_section']."'";
							break;
						}
					$res2 = $this->db->db_query($req);
					$arr2 = $this->db->db_fetch_array($res2);
					if( $arr['type'] == "1" )
						$this->listrightsecval = bab_translate($arr2['title']);
					else
						$this->listrightsecval = $arr2['title'];
					$this->secid = $this->arright[$j];
					}
				$j++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionordering"));
	return $temp->count;
	}

function sectionCreate($jscript)
	{
	global $babBody;
	class temp
		{
		var $title;
		var $description;
		var $position;
		var $content;
		var $create;
		var $left;
		var $right;
		var $script;
		var $jscript;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;
		var $arrtmpl;
		var $counttmpl;
		var $templatetxt;
		var $optionaltxt;
		var $yes;
		var $no;
		var $nselected;
		var $yselected;

		function temp($jscript)
			{
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->position = bab_translate("Position");
			$this->content = bab_translate("Content");
			$this->create = bab_translate("Create");
			$this->left = bab_translate("Left");
			$this->right = bab_translate("Right");
			$this->script = bab_translate("PHP script");
			$this->templatetxt = bab_translate("Template");
			$this->optionaltxt = bab_translate("Optional(fem)");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->jscript = $jscript;
			if( $jscript == 0)
				{
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_section');
				$this->editor = $editor->getEditor();
				}
			else
				$this->editor = false;
			
			$this->nselected = 'selected';
			$this->yselected = '';

			$file = "sectiontemplate.html";
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
			}
			
		function getnexttemplate()
			{
			static $i = 0;
			if($i < $this->counttmpl)
				{
				$this->templateid = $this->arrtmpl[$i];
				$i++;
				return true;
				}
			return false;
			}


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
			}

		}

	$temp = new temp($jscript);
	$babBody->babecho(	bab_printTemplate($temp,"sections.html", "sectionscreate"));
	}



function sectionSave($title, $pos, $desc, $script, $js, $template, $lang, $opt)
	{
	global $babBody;
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title !!");
		return;
		}

	if( $js == 1) {
		$content = bab_rp('content');
		$contentFormat = 'html';
	} else {
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor('bab_section');
		$content = $editor->getContent();
		$contentFormat = $editor->getFormat();
	}

	$db = &$GLOBALS['babDB'];

	$desc = $db->db_escape_string($desc);
	$content = $db->db_escape_string($content);
	$title = $db->db_escape_string($title);
	$template = $db->db_escape_string($template);

	
	$query = "select * from ".BAB_SECTIONS_TBL." where title='$title'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This section already exists");

		}
	else
		{
		$php = "N";

		if( $js == 1)
			$js = "Y";
		else
			$js = "N";
		$query = "insert into ".BAB_SECTIONS_TBL." (title, position, description, content, content_format, script, jscript, template, lang, id_dgowner, optional) VALUES ('" .$title. "', '" . $pos. "', '" . $desc. "', '" . $content. "', '" . $contentFormat. "', '" . $php. "', '" . $js."', '". $template."', '" .$lang."', '" .$babBody->currentAdmGroup."', '" .$opt. "')";
		$db->db_query($query);
		$id = $db->db_insert_id();
		if( $babBody->currentAdmGroup == 0 )
			$db->db_query("insert into ".BAB_SECTIONS_GROUPS_TBL." (id_object, id_group) values ('". $id. "', '3')");
		else
			$db->db_query("insert into ".BAB_SECTIONS_GROUPS_TBL." (id_object, id_group) values ('". $id. "', '".$babBody->currentAdmGroup."')");
		$res = $db->db_query("select max(so.ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_SECTIONS_TBL." s where so.position='".$pos."' and  so.type='2' and so.id_section=s.id and s.id_dgowner='".$babBody->currentAdmGroup."'");
		$arr = $db->db_fetch_array($res);
		if( empty($arr[0]))
			{
			$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='".$pos."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			if( empty($arr[0]))
				$arr[0] = 0;
			}

		$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='".$pos."' and ordering > '".$arr[0]."'");
		$db->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$id. "', '" . $pos. "', '2', '" . ($arr[0]+1). "')");
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$id);
		exit;
		}
	}


function saveSectionsOrder($listleft, $listright)
	{
		global $babBody;

		$db = $GLOBALS['babDB'];

		if( $babBody->currentAdmGroup == 0 )
		{
			for( $k = 0; $k < 2; $k++ )
			{
				$pos = 0;
				$tab = func_get_arg($k);

				for( $i = 0; $i < count($tab); $i++)
				{
					$rr = explode('-',  $tab[$i]);
					if( count($rr) == 1 )
					{
						$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set position='".$k."', ordering='".($pos+1)."' where id='".$tab[$i]."'");
						$arr = $db->db_fetch_array($db->db_query("select id, type from ".BAB_SECTIONS_ORDER_TBL." where id='".$tab[$i]."'"));
						if( $arr['type'] == "2")
							{
							$db->db_query("update ".BAB_SECTIONS_TBL." set position='".$k."' where id='".$tab[$i]."'");
							}
						$pos++;
					}
					else
					{
						$res = $db->db_query("select distinct so.* from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_SECTIONS_TBL." s, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='".$rr[1]."' and (( so.id_section=s.id and type='2' and s.id_dgowner='".$rr[0]."') or ( so.id_section=tc.id and type='3' and tc.id_dgowner='".$rr[0]."')) order by so.ordering asc");
						while($arr = $db->db_fetch_array($res))
						{
							$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set position='".$k."', ordering='".($pos+1)."' where id='".$arr['id']."'");
							if( $arr['type'] == "2")
								{
								$db->db_query("update ".BAB_SECTIONS_TBL." set position='".$k."' where id='".$tab[$i]."'");
								}
							$pos++;
						}

					}
				}
			}
		}
		else
		{
			$apos = array();

			$arridsec = array();

			$arrtot = array_merge($listleft, $listright);

			if( count($arrtot) > 0 )
			{
				for( $k = 0; $k < 2; $k++ )
				{
					$res = $db->db_query("select id from ".BAB_SECTIONS_ORDER_TBL." where position='".$k."' order by ordering asc");
					$i = 0;
					while($arr = $db->db_fetch_array($res))
					{
						if( in_array($arr['id'], $arrtot) )
						{
							if(!isset($apos[$k]))
								$apos[$k] = $i;
						}
						else
							{
							$arridsec[$k][] = $arr['id'];
							$i++;
							}
					}

					if( !isset($apos[$k]))
						$apos[$k] = $i;
				}

				
				for( $k = 0; $k < 2; $k++ )
				{
					$tab = func_get_arg($k);
					if( count($arridsec[$k]) > 0 )
						$arrs = array_merge(array_slice($arridsec[$k], 0, $apos[$k]), $tab, array_slice($arridsec[$k], $apos[$k]));
					else
						$arrs = $tab;

					$pos = 0;
					for( $i = 0; $i < count($arrs); $i++)
					{
						$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set position='".$k."', ordering='".($pos+1)."' where id='".$arrs[$i]."'");
						$arr = $db->db_fetch_array($db->db_query("select id, type from ".BAB_SECTIONS_ORDER_TBL." where id='".$arrs[$i]."'"));
						if( $arr['type'] == "2")
							{
							$db->db_query("update ".BAB_SECTIONS_TBL." set position='".$k."' where id='".$arrs[$i]."'");
							}
						$pos++;
					}
				}
			}
		}
	}

function disableSections($sections, $sectopt)
	{
	global $babBody;

	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_SECTIONS_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-2", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		if( count($sectopt) > 0 && in_array($row['id']."-2", $sectopt))
			$optional = "Y";
		else
			$optional = "N";

		$req = "update ".BAB_SECTIONS_TBL." set enabled='".$enabled."', optional='".$optional."' where id='".$row['id']."'";
		$db->db_query($req);
		}

	if( $babBody->currentAdmGroup == 0 )
		{
		$req = "select id from ".BAB_PRIVATE_SECTIONS_TBL."";
		$res = $db->db_query($req);
		while( $row = $db->db_fetch_array($res))
			{
			if( count($sections) > 0 && in_array($row['id']."-1", $sections))
				$enabled = "N";
			else
				$enabled = "Y";

			if( count($sectopt) > 0 && in_array($row['id']."-1", $sectopt))
				$optional = "Y";
			else
				$optional = "N";

			$req = "update ".BAB_PRIVATE_SECTIONS_TBL." set enabled='".$enabled."', optional='".$optional."' where id='".$row['id']."'";
			$db->db_query($req);
			}
		}

	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($sections) > 0 && in_array($row['id']."-3", $sections))
			$enabled = "N";
		else
			$enabled = "Y";

		if( count($sectopt) > 0 && in_array($row['id']."-3", $sectopt))
			$optional = "Y";
		else
			$optional = "N";
		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."', optional='".$optional."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['sections'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($create))
	{
	if (!isset($script))
		$script = '';
	sectionSave($title, $position, $description, $script, $js, $template, $lang, $opt);
	}

if( isset($update))
	{
	if( $update == "order" )
		{
		if ( !isset($listleft))  { $listleft= array(); }
		if ( !isset($listright)) { $listright= array(); }
		saveSectionsOrder($listleft, $listright);
		}
	else if( $update == "disable" )
		{
		if( !isset($sections)) { $sections= array();}
		if( !isset($sectopt)) { $sectopt= array();}
		disableSections($sections, $sectopt);
		}
	}

if( !isset($idx))
	$idx = "List";


switch($idx)
	{
	case "Order":
		$babBody->title = bab_translate("Sections order");
		sectionsOrder();
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
			$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");

		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(javascript)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;

	case "ch":
		$babBody->title = bab_translate("Create section");
		sectionCreate(0);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(javascript)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;

	case "cj":
		$babBody->title = bab_translate("Create section");
		sectionCreate(1);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		
		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(javascript)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;

	case "List":
	default:
		$babBody->title = bab_translate("Sections list");
		if( sectionsList() == 0 )
			$babBody->title = bab_translate("There is no section");

		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");

		$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		$babBody->addItemMenu("cj", bab_translate("Create")."(javascript)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminSections');
?>
