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

		function temp()
			{
			global $babBody;
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->access = bab_translate("Access");
			$this->groups = bab_translate("View");
			$this->opttxt = bab_translate("Optional");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SECTIONS_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			/* don't get Administrator section */
			if( $babBody->currentAdmGroup == 0 )
				{
				$this->resa = $this->db->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id > '1'");
				$this->counta = $this->db->db_num_rows($this->resa);
				}
			else
				$this->counta = 0;

			$this->rescat = $this->db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");
			$this->countcat = $this->db->db_num_rows($this->rescat);
			}

		function getnextp()
			{
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->arr = $this->db->db_fetch_array($this->resa);
				$this->arr['title'] = bab_translate($this->arr['title']);
				$this->arr['description'] = bab_translate($this->arr['description']);
				$this->idvalue = $this->arr['id']."-1";
				if( $this->arr['enabled'] == "N")
					$this->secchecked = "checked";
				else
					$this->secchecked = "";
				if( $this->arr['optional'] == "Y")
					$this->optchecked = "checked";
				else
					$this->optchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countcat)
				{
				$this->arr = $this->db->db_fetch_array($this->rescat);
				$this->arr['title'] = $this->arr['title'];
				$this->arr['description'] = $this->arr['description'];
				$this->idvalue = $this->arr['id']."-3";
				if( $this->arr['enabled'] == "N")
					$this->secchecked = "checked";
				else
					$this->secchecked = "";
				if( $this->arr['optional'] == "Y")
					$this->optchecked = "checked";
				else
					$this->optchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$this->arr['id'];
				$this->accessurl = $GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$this->arr['id'];
				$this->idvalue = $this->arr['id']."-2";
				if( $this->arr['enabled'] == "N")
					$this->secchecked = "checked";
				else
					$this->secchecked = "";
				if( $this->arr['optional'] == "Y")
					$this->optchecked = "checked";
				else
					$this->optchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	if( $temp->count == 0 && $temp->counta == 0 && $temp->countcat == 0)
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		exit;
		}
	$babBody->babecho(	bab_printTemplate($temp, "sections.html", "sectionslist"));
	return $temp->count + $temp->countcat + $temp->counta;
	}

function sectionsOrder()
	{
	global $babBody;
	class temp
		{
		var $id;
		var $arr = array();
		var $db;
		var $count;
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
			$this->db = $GLOBALS['babDB'];

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
						if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 && $rr['id_dgowner'] == 0)
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						else if( $babBody->currentAdmGroup == $rr['id_dgowner'] )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						else if( $babBody->isSuperAdmin && ($babBody->currentAdmGroup != $rr['id_dgowner']) )
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
						if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 && $rr['id_dgowner'] == 0)
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						else if( $babBody->currentAdmGroup == $rr['id_dgowner'] )
							{
							if( $arr['position'] == 0 )
								$this->arrleft[] = $arr['id'];
							else
								$this->arright[] = $arr['id'];
							}
						else if( $babBody->isSuperAdmin && ($babBody->currentAdmGroup != $rr['id_dgowner']) )
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
						break;
					}
				}
			$this->countleft = count($this->arrleft);
			$this->countright = count($this->arright);
			}

		function getnextsecleft()
			{
			static $i = 0;
			if( $i < $this->countleft)
				{
				$rr = explode('-',$this->arrleft[$i]);
				if( count($rr) > 1 )
					{
					$this->listleftsecval = "[[".bab_getGroupName($rr[0])."(". bab_translate("Left").")]]";
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
					$this->listrightsecval = "[[".bab_getGroupName($rr[0])."(". bab_translate("Right").")]]";
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
		var $msie;
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
			$this->optionaltxt = bab_translate("Optional");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->jscript = $jscript;
			if(( $jscript == 0 && strtolower(bab_browserAgent()) == "msie") && (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
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



function sectionSave($title, $pos, $desc, $content, $script, $js, $template, $lang, $opt)
	{
	global $babBody;
	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title !!");
		return;
		}

	$content = bab_stripDomainName($content);
	if( !bab_isMagicQuotesGpcOn())
		{
		$desc = addslashes($desc);
		$content = addslashes($content);
		$title = addslashes($title);
		$template = addslashes($template);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SECTIONS_TBL." where title='$title'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This section already exists");
		}
	else
		{
		
		if( $script == "Y")
			$php = "Y";
		else
			$php = "N";

		if( $js == 1)
			$js = "Y";
		else
			$js = "N";
		$query = "insert into ".BAB_SECTIONS_TBL." (title, position, description, content, script, jscript, template, lang, id_dgowner, optional) VALUES ('" .$title. "', '" . $pos. "', '" . $desc. "', '" . bab_stripDomainName($content). "', '" . $php. "', '" . $js."', '". $template."', '" .$lang."', '" .$babBody->currentAdmGroup."', '" .$opt. "')";
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
				$pos = 1;
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

			for( $k = 0; $k < 2; $k++ )
			{
				$res = $db->db_query("select min(so.ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_SECTIONS_TBL." s, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='".$k."' and (( so.id_section=s.id and type='2' and s.id_dgowner='".$babBody->currentAdmGroup."') or ( so.id_section=tc.id and type='3' and tc.id_dgowner='".$babBody->currentAdmGroup."')) order by so.ordering asc");
				$arr = $db->db_fetch_array($res);
				if( isset($arr[0]) )
				{
					$apos[$k] = $arr[0];

				}
				else
				{
					$res = $db->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='".$k."'");
					$arr = $db->db_fetch_array($res);
					if( empty($arr[0]))
						$apos[$k] = 1;
					else
						$apos[$k] = $arr[0]+1;
				}

			}

			$db->db_query("create temporary table bab_sec_ord select * from ".BAB_SECTIONS_ORDER_TBL." where 0");
			$db->db_query("alter table bab_sec_ord add unique (id)");
			$db->db_query("insert into bab_sec_ord select distinct so.* from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_SECTIONS_TBL." s, ".BAB_TOPICS_CATEGORIES_TBL." tc where ( so.id_section=s.id and type='2' and s.id_dgowner='".$babBody->currentAdmGroup."') or ( so.id_section=tc.id and type='3' and tc.id_dgowner='".$babBody->currentAdmGroup."') order by so.ordering asc");

			$res = $db->db_query("select id from bab_sec_ord");
			while($arr = $db->db_fetch_array($res))
				$db->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id='".$arr['id']."'");	


			for( $k = 0; $k < 2; $k++ )
			{
				$ord = 1;
				$res = $db->db_query("select id from ".BAB_SECTIONS_ORDER_TBL." where position='".$k."'");
				while($arr = $db->db_fetch_array($res))
					{
					$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering='".$ord."' where id='".$arr['id']."'");
					$ord += 1;
					}
			}


			for( $k = 0; $k < 2; $k++ )
			{
				$tab = func_get_arg($k);
				for( $i = 0; $i < count($tab); $i++)
				{
				$res = $db->db_query("select * from bab_sec_ord where id='".$tab[$i]."'");
				if( $res && $db->db_num_rows($res) > 0 )
					{
					$arr = $db->db_fetch_array($res);
					$db->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='".$k."' and ordering >= '".$apos[$k]."'");
					$db->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$arr['id_section']. "', '" . $k. "', '".$arr['type']."', '" . ($apos[$k]). "')");
					$apos[$k] += 1;
					if( $arr['type'] == "2")
						{
						$db->db_query("update ".BAB_SECTIONS_TBL." set position='".$k."' where id='".$db->db_insert_id()."'");
						}
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
	sectionSave($title, $position, $description, $content, $script, $js, $template, $lang, $opt);
	}

if( isset($update))
	{
	if( $update == "order" )
		saveSectionsOrder($listleft, $listright);
	else if( $update == "disable" )
		disableSections($sections, $sectopt);
	}

if( !isset($idx))
	$idx = "List";


if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
	$msie = 1;
else
	$msie = 0;

switch($idx)
	{
	case "Order":
		$babBody->title = bab_translate("Sections order");
		sectionsOrder();
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
			$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		if( $msie )
			{
			$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
			$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
			}
		else
			$babBody->addItemMenu("ch", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		break;
	case "ch":
		$babBody->title = bab_translate("Create section");
		sectionCreate(0);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		if( $msie )
			{
			$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
			$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
			}
		else
			$babBody->addItemMenu("ch", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		break;
	case "cj":
		$babBody->title = bab_translate("Create section");
		sectionCreate(1);
		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		if( $msie )
			{
			$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
			$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
			}
		else
			$babBody->addItemMenu("ch", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		break;
	case "List":
	default:
		$babBody->title = bab_translate("Sections list");
		if( sectionsList() == 0 )
			$babBody->title = bab_translate("There is no section");

		$babBody->addItemMenu("List", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sections&idx=List");
		$babBody->addItemMenu("Order", bab_translate("Order"),$GLOBALS['babUrlScript']."?tg=sections&idx=Order");
		if( $msie )
			{
			$babBody->addItemMenu("ch", bab_translate("Create")."(html)",$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
			$babBody->addItemMenu("cj", bab_translate("Create")."(script)",$GLOBALS['babUrlScript']."?tg=sections&idx=cj");
			}
		else
			$babBody->addItemMenu("ch", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sections&idx=ch");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
