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
include $babInstallPath."utilit/forumincl.php";
include $babInstallPath."utilit/fileincl.php";
include $babInstallPath."utilit/calincl.php";

$babLimit = 5;
$navbaritems = 10;
define ("FIELDS_TO_SEARCH", 3);

function highlightWord( $w, $text)
{
	$arr = explode(" ",urldecode($w));
	foreach($arr as $mot)
		{
		$mot_he = htmlentities($mot);
		$text = preg_replace("/(\s*>[^<]*|\s+)(".$mot.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
		if ($mot != $mot_he)
			$text = preg_replace("/(\s*>[^<]*|\s+)(".$mot_he.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
		}
	return $text;
}

function put_text($txt,$limit=60,$limitmot=30)
{
	if (strlen($txt) > $limit)
		$out = substr(strip_tags($txt),0,$limit)."...";
	else
		$out = strip_tags($txt);
	$arr = explode(" ",$out);
	foreach($arr as $key => $mot)
		$arr[$key] = substr($mot,0,$limitmot);
return bab_replace(implode(" ",$arr));
}

function he($tbl,$str,$not="")
	{
	if ($not == "NOT") $op = "AND";
	else $op =  "OR";
	$tmp = htmlentities($str);
	if ($tmp != $str)
		return " ".$op." ".$tbl.$not." like '%".$tmp."%'";
	}

function finder($req2,$tablename,$option = "OR",$req1="")
{
if (trim($req1) != "") 
	$like = $tablename." like '%".$req1."%'".he($tablename,$req1);

if( !bab_isMagicQuotesGpcOn())
	$req2 = addslashes($req2);

if (trim($req2) != "") 
	{
	$tb = explode(" ",trim($req2));
	switch ($option)
		{
		case "NOT":
			foreach($tb as $key => $mot)
				{
				if (trim($req1) == "" && $key==0)
					$like = $tablename." like '%".$mot."%'";
				else
					$like .= " AND ".$tablename." NOT like '%".$mot."%'".he($tablename,$mot," NOT");
				}
		break;
		case "OR":
		case "AND":
		default:
			foreach($tb as $key => $mot)
				{
				$he = he($tablename,$mot);
				if (trim($req1) == "" && $key==0)
					$like = $tablename." like '%".$mot."%'".$he;
				else if ($he != "" && $option == "AND")
					$like .= " AND (".$tablename." like '%".$mot."%'".$he.")";
				else
					$like .= " ".$option." ".$tablename." like '%".$mot."%'".$he;
				}
		break;
		}
	}
	return $like;
}


function returnCategoriesHierarchy($topics)
	{
	$article_path = new categoriesHierarchy($topics);
	$out = bab_printTemplate($article_path,"search.html", "article_path");
	return $out;
	}


function searchKeyword($item , $option = "OR")
	{
	global $babBody;

	class tempb
		{
		var $search;
		var $all;
		var $in;
		var $update;
		var $itemvalue;
		var $itemname;
		var $arr = array();
		var $sfaq;
		var $sart;
		var $snot;
		var $sfor;
		var $sfil;
		var $what;

		function tempb($item ,$option )
			{
			$this->db = $GLOBALS['babDB'];
			global  $babBody,$babSearchItems;
			$this->fields = $GLOBALS['HTTP_POST_VARS'];
			
			$this->search = bab_translate("Search");
			$this->all = bab_translate("All");
			$this->in = bab_translate("in");
			$this->what = stripslashes($this->fields['what']);
			$this->fields['what'] = stripslashes($this->fields['what']);
			$this->fields['what2'] = stripslashes($this->fields['what2']);
			$this->reset = bab_translate("Reset");
			$this->Topic = bab_translate("Topic");
			$this->Author = bab_translate("Author");
			$this->Date = bab_translate("Date");
			$this->option_or = bab_translate("or");
			$this->option_and = bab_translate("and");
			$this->option_not = bab_translate("exclude");
			$this->switch = bab_translate("Advanced search"); 

			$this->before = bab_translate("before date");
			$this->after = bab_translate("after date");
			$this->beforelink = $GLOBALS['babUrlScript']."?tg=month&callback=beforeJs&ymin=100&ymax=10&month=".date(m)."&year=".date(Y);;
			$this->afterlink = $GLOBALS['babUrlScript']."?tg=month&callback=afterJs&ymin=100&ymax=10&month=".date(m)."&year=".date(Y);;

			$this->or_selected = "";
			$this->and_selected = "";
			$this->not_selected = "";

			switch ($option)
				{
				case "OR": $this->or_selected = "selected"; break;
				case "AND": $this->and_selected = "selected"; break;
				case "NOT": $this->not_selected = "selected"; break;
				}
			foreach ($babSearchItems as $key => $value)
					$this->arr[] = $key;

			$this->count = count($this->arr);

			$this->el_to_init = Array ( 'a_author','a_dd','a_mm','a_yyyy','before','after','before_display' ,'after_display','before_memo','after_memo','what2','what','advenced');

			for ($i =0 ;$i < FIELDS_TO_SEARCH ; $i++)
				$this->el_to_init[] = "dirfield_".$i;

			if (($this->fields['idx'] != "find")||((!isset($this->fields['a_mm']))&&(!isset($this->fields['g_mm'])))) 
				{
				foreach($this->el_to_init as  $value)
					if (! isset($this->fields[$value])) $this->fields[$value] = "";
				}

			$req = "select id,category from ".BAB_TOPICS_TBL." order by category";
			$this->restopics = $this->db->db_query($req);
			$this->counttopics = $this->db->db_num_rows($this->restopics);

			$req = "select D.id, D.name, D.id_group id_group from ".BAB_DB_DIRECTORIES_TBL." D,".BAB_GROUPS_TBL." G where D.id_group = '0' OR (D.id_group = G.id AND G.directory='Y') order by D.name";
			$this->resdirs = $this->db->db_query($req);
			$i = 0;
			while ($arr = $this->db->db_fetch_array($this->resdirs) )
				{
				if ($arr['name'] != $this->dirarr[$i-1]['name'] && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id']))
					{
					$this->dirarr[$i] = $arr;
					$i++;
					}
				}
			$this->countdirs = $i;

			$req = "select name,description from ".BAB_DBDIR_FIELDS_TBL." WHERE name != 'jpegphoto' order by description";
			$this->resfields = $this->db->db_query($req);
			$this->countfields = $this->db->db_num_rows($this->resfields);
			$i = 0;
			while ($arr = $this->db->db_fetch_array($this->resfields))
				{
				$this->tbln[$i] = $arr['name'];
				$this->tbld[$i] = bab_translate($arr['description']);
				$i++;
				}
			$fliped = array_flip($this->tbld);
			ksort($fliped);
			$i = 0;
			foreach($fliped as $value => $key)
				{
				$this->tblfields[$i]['name'] = $this->tbln[$key];
				$this->tblfields[$i]['description'] = $value;
				$i++;
				}

			$this->rescal = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
			$this->countcal = count($this->rescal);

			$this->acces['a'] = true;
			$this->acces['b'] = false;
			$this->acces['c'] = false;
			$this->acces['d'] = false;
			$this->acces['e'] = false;
			$this->acces['f'] = $GLOBALS['BAB_SESS_LOGGED'] && bab_contactsAccess();
			$this->acces['g'] = $this->countdirs;
			$this->acces['h'] = bab_calendarAccess();
			$res = $this->db->db_query("select id from ".BAB_FORUMS_TBL);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
					{
					$this->acces['b'] = true;
					break;
					}
				}

			$res = $this->db->db_query("select id from ".BAB_FAQCAT_TBL);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
					{
					$this->acces['c'] = true;
					break;
					}
				}
			$res = $this->db->db_query("select count(g.id) from ".BAB_GROUPS_TBL." g, ".BAB_USERS_GROUPS_TBL." u WHERE g.notes='Y' AND u.id_object='".$GLOBALS['BAB_SESS_USERID']."' AND u.id_group=g.id");
			$row = $this->db->db_fetch_array($res);
				{
				if($row[0] > 0)
					$this->acces['d'] = true;
				}
			bab_fileManagerAccessLevel();
			if( $babBody->ustorage || count($babBody->aclfm) > 0 )
				{
				$this->acces['e'] = true;
				}
			}

		function getnextitem()
			{
			global $babSearchItems;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->itemvalue = $this->arr[$i];
				$this->itemname = $babSearchItems[$this->arr[$i]];
				$this->perm = $this->acces[$this->arr[$i]];
				$i++;
				return true;
				}
			else
				return false;
			}
		
		function getnexttopic() 
			{
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$arr = $this->db->db_fetch_array($this->restopics);
				$this->topicid = $arr['id'];
				$this->topictitle = put_text($arr['category'],30);
				$this->selected = bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id']);
				$i++;
				return true;
				}
			else
				return false;
			} 
		
		function getnextdir() 
			{
			static $l = 0;
			if( $l < $this->countdirs)
				{
                $arr = $this->dirarr[$l];
				$this->topicid = $arr['id'];
				$this->topictitle = put_text($arr['name'],30);
				$req = "SELECT DISTINCT(f.name) name FROM ".BAB_DBDIR_FIELDSEXTRA_TBL." e LEFT JOIN ".BAB_DBDIR_FIELDS_TBL." f ON f.id = e.id_field AND f.name!='jpegphoto' WHERE e.ordering!=0  AND e.id_directory='".(($arr['id_group']==0) ? $arr['id'] : 0)." GROUP BY f.name ORDER BY e.ordering'";
				$this->resfieldsfromdir = $this->db->db_query($req);
				$this->countfieldsfromdir = $this->db->db_num_rows($this->resfieldsfromdir);				
				$l++;
				return true;
				}
			else
				{
				$l = 0;
				return false;
				}
			}
		
		function selectname() 
			{
			static $i = 0;
			if( $i < $this->countfields)
				{
				$this->selindex = $i;
                $arr = $this->tblfields[$i];
				$this->name = $arr['name'];
				$this->description = $arr['description'];
				$this->descriptionJs = addslashes($arr['description']);
				$this->fieldvalue = "".$this->fields[$arr['name']];
				if ( $this->fields['dirselect_'.$this->j] == $arr['name'])
					$this->selected = "selected";
				else	
					$this->selected = false;
				$i++;
				return true;
				}
			else
				{$i = 0;
				return false;}
			}

		function getnextfield()
			{
			static $k = 0;
			if( $k < $this->countfieldsfromdir)
				{
				$arr = $this->db->db_fetch_array($this->resfieldsfromdir);
				$this->fieldnamefromdir = $arr['name'] ;
				$this->fieldindex = $k;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;}
			}

		function getnextfieldtosearch() 
			{
			static $j = 0;
			if( $j < FIELDS_TO_SEARCH)
				{
				$this->fieldcounter = $j;
				$this->j = $j;
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;}
			}

		function getnextcal() 
			{
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->calendarid = $this->rescal[$i]['idcal'];
				$this->caltitle = put_text($this->rescal[$i]['name'],30);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$tempb = new tempb($item ,$option);
	$babBody->babecho(	bab_printTemplate($tempb,"search.html", "search"));
	}

class temp_nav
		{
		function temp_nav($babLimit,$nbrows,$navitem,$navpos)
			{
			global $navbaritems;
			$this->navbaritems = $navbaritems;
			$this->babLimit = $babLimit;
			$this->nbrows = $nbrows;
			$this->navitem = $navitem;
			$this->navpos = $navpos;
			if (($navpos+$babLimit) > $nbrows ) $this->navposend = $navpos+($nbrows - $navpos);
			else $this->navposend = $navpos+$babLimit;
			$this->results = bab_translate("Results");
			$this->pages = bab_translate("Pages");
			$this->to = bab_translate("To");
			$this->from = bab_translate("From");
			$this->countpages = ceil($nbrows/$babLimit);
			
			
			if ( $navpos <= 0 ) $this->previous = false;
			else 
				{ 
				$this->previous = bab_translate("Previous");
				$this->urlprev ="javascript:navsearch(".($this->navpos - $this->babLimit).",'".$this->navitem."')"; 
				}

			if ( $navpos + $babLimit >= $nbrows ) $this->next = false;
			else 
				{
				$this->next = bab_translate("Next");
				$this->urlnext = "javascript:navsearch(".($this->navpos + $this->babLimit).",'".$this->navitem."')"; 
				}
			
			$this->count = ceil($nbrows/$babLimit);
			if ( $this->count > $this->navbaritems )
				$this->count = $this->navbaritems;

			if ((ceil($this->navpos/$babLimit) - ($this->navbaritems/2)) < 0 ) $this->start = 0;
			else $this->start = ceil($this->navpos/$babLimit) - ($this->navbaritems/2);
			$this->page = $this->start + 1;
			}
		function getnext()
			{
			static $i = 0;
			if( $i < $this->count && $this->page < $this->countpages)
				{
				$this->page = $this->start + $i + 1;
				$this->urlpage = "javascript:navsearch(".$this->babLimit*($this->start+$i).",'".$this->navitem."')"; 
				if ( (ceil($this->navpos/$this->babLimit) == $this->start + $i) ) 
	 				$this->selected = true;
				else
					$this->selected = false;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}
		}

function navbar($babLimit,$nbrows,$navitem,$navpos)
	{
	$temp = new temp_nav($babLimit,$nbrows,$navitem,$navpos);
	return bab_printTemplate($temp,"search.html","navbar");
	}

function startSearch( $item, $what, $order, $option ,$navitem, $navpos )
	{
	global $babBody;
	
	class temp
		{
		var $what;
		var $search;
		var $db;
		var $arttitle;
		var $comtitle;
		var $fortitle;
		var $faqtitle;
		var $nottitle;
		var $filtitle;
		var $contitle;
		var $dirtitle;
		var $countart;
		var $countfor;
		var $countnot;
		var $countfaq;
		var $countcom;
		var $countfil;
		var $countcon;
		var $countdir;
		var $counttot;

		function temp( $item, $what, $order, $option ,$navitem ,$navpos )
			{
			global $BAB_SESS_USERID, $babLimit, $babBody;
			
			$this->db = $GLOBALS['babDB'];
			$this->search = bab_translate("Search");
			$this->arttitle = bab_translate("Articles");
			$this->comtitle = bab_translate("Comments");
			$this->fortitle = bab_translate("Forums");
			$this->faqtitle = bab_translate("Faq");
			$this->nottitle = bab_translate("Notes");
			$this->filtitle = bab_translate("Files");
			$this->contitle = bab_translate("Contacts");
			$this->dirtitle = bab_translate("Directories");
			$this->agebigtitle = bab_translate("Calendar");
			$this->total = bab_translate("Number of results in research");
			$this->popup = bab_translate("Popup");
			$this->lastname= bab_translate("Lastname");
			$this->firstname= bab_translate("Firstname");
			$this->email = bab_translate("Email");
			$this->company= bab_translate("Company");
			$this->filename=bab_translate("Filename");
			$this->description=bab_translate("Description");
			$this->folder=bab_translate("Folder");
			$this->author=bab_translate("Author");
			$this->datem=bab_translate("Last update");
			$this->directory=bab_translate("Site directory");
			$this->department=bab_translate("Department");

			$this->fields = $GLOBALS['HTTP_POST_VARS'];

			if( !bab_isMagicQuotesGpcOn())
				{
				$this->like2 = addslashes($what);
				$this->like = addslashes($this->fields['what2']);
				}
			else
				{
				$this->like2 = $what;
				$this->like = $this->fields['what2'];
				}
			
			$this->what = urlencode(addslashes($what." ".$this->fields['what2']));
			$this->countart = 0;
			$this->countfor = 0;
			$this->countnot = 0;
			$this->countfaq = 0;
			$this->countcom = 0;
			$this->countfil = 0;
			$this->countcon = 0;
			$this->countdir = 0;
			$this->countage = 0;
			$this->counttot = false;
			$this->navitem = $navitem;
			$this->navpos = $navpos;
			

// ---------------------------------------- SEARCH ARTICLES AND ARTICLES COMMENTS ---------
			if( empty($item) || $item == "a")
				{
				$req = "create temporary table artresults SELECT a.id, a.id_topic, a.title, a.head, a.body, a.restriction, T.category topic, concat( U.lastname, ' ', U.firstname ) author,a.id_author, 'yyyy-mm-dd' date from ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T, ".BAB_USERS_TBL." U where a.id_topic = T.id AND a.id_author = U.id AND 0";
				$this->db->db_query($req);
				$req = "alter table artresults add unique (id)";
				$this->db->db_query($req);

				$req = "create temporary table comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, DATE_FORMAT(C.date, '%d-%m-%Y') date, name,email, a.title arttitle, a.body,a.restriction, T.category topic from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id and 0";
				$this->db->db_query($req);
				$req = "alter table comresults add unique (id)";
				$this->db->db_query($req); 

				if (trim($this->fields['a_author']) != "")
					{
					$crit_art = " and (".finder($this->fields['a_author'],"concat(U.lastname,U.firstname)",$option).")";
					$crit_com = " and (".finder($this->fields['a_author'],"name",$option).")";
					}

				if (trim($this->fields['a_topic']) != "")
					{
					$crit_art .= " and id_topic = ".$this->fields['a_topic'];
					$crit_com .= " and C.id_topic = ".$this->fields['a_topic'];
					}

				if (trim($this->fields['after']) != "")
					{
					$crit_art .= " and a.date >= '".$this->fields['after']."'";
					$crit_com .= " and C.date >= '".$this->fields['after']."'";
					}
				if (trim($this->fields['before']) != "")
					{
					$crit_art .= " and a.date <= '".$this->fields['before']."'";
					$crit_com .= " and C.date <= '".$this->fields['before']."'";
					}

				$inart = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and id_topic in (".implode($babBody->topview,",").")" : "";
				$incom = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and C.id_topic in (".implode($babBody->topview,",").")" : "";

				if ($this->like || $this->like2)
					$reqsup = "and (".finder($this->like,"title",$option,$this->like2)." or ".finder($this->like,"head",$option,$this->like2)." or ".finder($this->like,"body",$option,$this->like2).")";
				
				$req = "insert into artresults SELECT a.id, a.id_topic, a.title title,a.head, LEFT(a.body,100) body, a.restriction, T.category topic, concat( U.lastname, ' ', U.firstname ) author,a.id_author, DATE_FORMAT(a.date, '%d-%m-%Y') date  from ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T, ".BAB_USERS_TBL." U where a.id_topic = T.id AND a.id_author = U.id ".$reqsup." and confirmed='Y' ".$inart." ".$crit_art." order by $order ";
				$this->db->db_query($req);

				$res = $this->db->db_query("select id, restriction from artresults where restriction!=''");
				while( $rr = $this->db->db_fetch_array($res))
					{
					if( !bab_articleAccessByRestriction($rr['restriction']))
						$this->db->db_query("delete from artresults where id='".$rr['id']."'");
					}

				if ($this->like || $this->like2)
					$reqsup = "and (".finder($this->like,"subject",$option,$this->like2)." or ".finder($this->like,"message",$option,$this->like2).")";

				$req = "insert into comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, DATE_FORMAT(C.date, '%d-%m-%Y') date, name author,email,  a.title arttitle,LEFT(a.body,100) body, a.restriction, T.category topic  from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id ".$reqsup." and C.confirmed='Y' ".$incom." ".$crit_com." order by $order ";

				$this->db->db_query($req);
				$res = $this->db->db_query("select id, restriction from comresults where restriction!=''");
				while( $rr = $this->db->db_fetch_array($res))
					{
					if( !bab_articleAccessByRestriction($rr['restriction']))
						$this->db->db_query("delete from comresults where id='".$rr['id']."'");
					}

				$req = "select count(*) from artresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$this->nbresult += $nbrows;

				if ($navitem != "a") $navpos=0;
				$req = "select * from artresults limit ".$navpos.", ".$babLimit;

				$this->resart = $this->db->db_query($req);
				$this->countart = $this->db->db_num_rows($this->resart);
				if( !$this->counttot && $this->countart > 0 )
					$this->counttot = true;
				$this->navbar_a = navbar($babLimit,$nbrows,"a",$navpos);

				$navpos=$this->navpos;
				if ($navitem != "ac") $navpos=0;
				$req = "select count(*) from comresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$this->navbar_ac = navbar($babLimit,$nbrows,"ac",$navpos);
				$req = "select * from comresults limit ".$navpos.", ".$babLimit;

				$this->rescom = $this->db->db_query($req);
				$this->countcom = $this->db->db_num_rows($this->rescom);
				if( !$this->counttot && $this->countcom > 0 )
					$this->counttot = true;
				}

				$this->nbresult += $nbrows;

			// ------------------------------------------------- POSTS
			if( empty($item) || $item == "b")
				{
				$req = "create temporary table forresults select id, id_thread, subject topic, id id_topic, subject title,message, author, 'dd-mm-yyyy' date from ".BAB_POSTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table forresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FORUMS_TBL."";
				$res = $this->db->db_query($req);
				$idthreads = "";
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
						{
						$req = "select id from ".BAB_THREADS_TBL." where forum='".$row['id']."'";
						$res2 = $this->db->db_query($req);
						while( $r = $this->db->db_fetch_array($res2))
							{
							$idthreads .= $r['id'].",";
							}
						}
					}
				$temp1 = finder($this->like,"b.subject",$option,$this->like2);
				$temp2 = finder($this->like,"b.message",$option,$this->like2);
				if ($temp1 != "" && $temp2 != "")
					$plus = "( ".$temp1." or ".$temp2.") and";
				else $plus = "";
				if ($idthreads != "") { 
					$req = "insert into forresults select b.id, b.id_thread, F.name topic, F.id id_topic, b.subject title,b.message, author, DATE_FORMAT(b.date, '%d-%m-%Y') date from ".BAB_POSTS_TBL." b, ".BAB_THREADS_TBL." T, ".BAB_FORUMS_TBL." F where b.id_thread=T.id and T.forum=F.id and ".$plus." b.confirmed='Y' and b.id_thread IN (".substr($idthreads,0,-1).") order by ".$order;
					$this->db->db_query($req);
				}

				$req = "select count(*) from forresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "b") $navpos = 0;
				$this->navbar_b = navbar($babLimit,$nbrows,"b",$navpos);
			
				$req = "select * from forresults limit ".$navpos.", ".$babLimit;
				$this->resfor = $this->db->db_query($req);
				$this->countfor = $this->db->db_num_rows($this->resfor);
				if( !$this->counttot && $this->countfor > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ---------------------------------------------- FAQ
			if( empty($item) || $item == "c")
				{
				$req = "create temporary table faqresults select id, idcat, question title, response, question topic,question id_manager, question author from ".BAB_FAQQR_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FAQCAT_TBL."";
				$res = $this->db->db_query($req);

				$idcat = "";
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
						{
						$idcat .= $row['id'].",";
						}
					}
				$temp1 = finder($this->like,"question",$option,$this->like2);
				$temp2 = finder($this->like,"response",$option,$this->like2);
				if ($temp1 != "" && $temp2 != "")
					$plus = "( ".$temp1." or ".$temp2.") and";
				else $plus = "";

				if ($idcat != "") {
					$req = "insert into faqresults select T.id, idcat, question title, response, category topic,C.id_manager, concat( U.lastname, ' ', U.firstname ) author from ".BAB_FAQQR_TBL." T, ".BAB_FAQCAT_TBL." C, ".BAB_USERS_TBL." U where idcat=C.id and C.id_manager=U.id and ".$plus." idcat in (".substr($idcat,0,-1).") order by ".$order;
					$this->db->db_query($req);
				}


				$req = "select count(*) from faqresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "c") $navpos = 0;
				$this->navbar_c = navbar($babLimit,$nbrows,"c",$navpos);

				$req = "select * from faqresults limit ".$navpos.", ".$babLimit;
				$this->resfaq = $this->db->db_query($req);
				$this->countfaq = $this->db->db_num_rows($this->resfaq);
				if( !$this->counttot && $this->countfaq > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------------------------------------------ PERSONAL NOTES
			if( (empty($item) || $item == "d") && !empty($BAB_SESS_USERID))
				{
				$plus = "";
				$plus = finder($this->like,"content",$option,$this->like2);
				if ($plus != "") $plus .= " and";
				$req = "select count(*) from ".BAB_NOTES_TBL." where ".$plus." id_user='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "d") $navpos = 0;
				$this->navbar_d = navbar($babLimit,$nbrows,"d",$navpos);
				
				$plus = "";
				$plus = finder($this->like,"content",$option,$this->like2);
				if ($plus != "") $plus .= " and";
				$req = "select id, content, DATE_FORMAT(date, '%d-%m-%Y') date from ".BAB_NOTES_TBL." where ".$plus." id_user='".$BAB_SESS_USERID."' limit ".$navpos.", ".$babLimit;
				$this->resnot = $this->db->db_query($req);
				$this->countnot = $this->db->db_num_rows($this->resnot);
				if( !$this->counttot && $this->countnot > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------- FILES
			if( empty($item) || $item == "e")
				{
				$req = "create temporary table filresults select id, name title, id_owner,description, 'dd-mm-yyyy' datec, 'dd-mm-yyyy' datem, path, bgroup, name author, path folder from ".BAB_FILES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table filresults add unique (id)";
				$this->db->db_query($req);
				bab_fileManagerAccessLevel();
				$private = false;
				// open wide
				$idfile = "";
				$grpfiles = " and F.bgroup='Y' ";

				for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
					{
					if( $babBody->aclfm['down'][$i] == 1 || $babBody->aclfm['ma'][$i] == 1)
						{
						$idfile .= $babBody->aclfm['id'][$i].',';						
						}
					}

				if( $babBody->ustorage)
					{
					$idfile .= $BAB_SESS_USERID.',';
					$grpfiles = "";
					}

				$plus = "";
				$temp1 = finder($this->like,"F.name",$option,$this->like2);
				$temp2 = finder($this->like,"description",$option,$this->like2);
				$temp3 = finder($this->like,"keywords",$option,$this->like2);
				$temp4 = finder($this->like,"F.path",$option,$this->like2);
				$temp5 = finder($this->like,"R.folder",$option,$this->like2);
				$temp6 = finder($this->like,"M.fvalue",$option,$this->like2);

				if ($temp1 != "" && $temp2 != "" && $temp3 != "" && $temp4 != "" && $temp5 != "")
					$plus = "( ".$temp1." or ".$temp2." or ".$temp3." or ".$temp4." or ".$temp5." ) and ";
				else $plus = "";

                if ($idfile != "") 
					{
					$req = "insert into filresults select F.id, F.name title, F.id_owner, description, DATE_FORMAT(created, '%d-%m-%Y') datec, DATE_FORMAT(modified, '%d-%m-%Y') datem, path, bgroup, concat( U.lastname, ' ', U.firstname ) author, folder from ".BAB_FILES_TBL." F, ".BAB_USERS_TBL." U, ".BAB_FM_FOLDERS_TBL." R where F.author=U.id and (F.id_owner=R.id OR F.bgroup='N') and ".$plus." F.id_owner in (".substr($idfile,0,-1).") ". $grpfiles ." and state='' and confirmed='Y' order by ".$order;
                    $this->db->db_query($req);
					
					if ($temp6 != "")
						{
						$req = "insert into filresults select F.id, F.name title, F.id_owner, description, DATE_FORMAT(created, '%d-%m-%Y') datec, DATE_FORMAT(modified, '%d-%m-%Y') datem, path, bgroup, concat( U.lastname, ' ', U.firstname ) author, folder from ".BAB_FILES_TBL." F, ".BAB_USERS_TBL." U, ".BAB_FM_FOLDERS_TBL." R, ".BAB_FM_FIELDSVAL_TBL." M where ".$temp6." and M.id_file=F.id AND F.author=U.id and (F.id_owner=R.id OR F.bgroup='N') and F.id_owner in (".substr($idfile,0,-1).") ". $grpfiles ." and state='' and confirmed='Y' order by ".$order;
						$this->db->db_query($req);
						}
                    }


				$req = "select count(*) from filresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$navpos = $this->navpos;
				if ($navitem != "e") $navpos = 0;
				$this->navbar_e = navbar($babLimit,$nbrows,"e",$navpos);

				$req = "select * from filresults limit ".$navpos.", ".$babLimit;
				$this->resfil = $this->db->db_query($req);
				$this->countfil = $this->db->db_num_rows($this->resfil);
				if( !$this->counttot && $this->countfil > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
			
			// ------------------------------------------------ PERSONAL CONTACTS
			if( empty($item) || $item == "f")
				{
				$req = "create temporary table conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table conresults add unique (id)";
				$this->db->db_query($req);

				$plus = "";
				$temp1 = finder($this->like,"firstname",$option,$this->like2);
				$temp2 = finder($this->like,"lastname",$option,$this->like2);
				$temp3 = finder($this->like,"email",$option,$this->like2);
				$temp4 = finder($this->like,"compagny",$option,$this->like2);
				$temp5 = finder($this->like,"jobtitle",$option,$this->like2);
				$temp6 = finder($this->like,"businessaddress",$option,$this->like2);
				$temp7 = finder($this->like,"homeaddress",$option,$this->like2);

				if ($temp1 != "" && $temp2 != "" && $temp3 != "" && $temp4 != "" && $temp5 != "" && $temp6 != "" && $temp7 != "")
					$plus = "( ".$temp1." or ".$temp2." or ".$temp3." or ".$temp4." or ".$temp5." or ".$temp6." or ".$temp7.") and";
				else $plus = "";

				$req = "insert into conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where ".$plus." owner='".$BAB_SESS_USERID."' order by ".$order;
				$this->db->db_query($req);

				$req = "select count(*) from conresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "f") $navpos = 0;
				$this->navbar_f = navbar($babLimit,$nbrows,"f",$navpos);

				$req = "select * from conresults limit ".$navpos.", ".$babLimit;
				$this->rescon = $this->db->db_query($req);
				$this->countcon = $this->db->db_num_rows($this->rescon);
				if( !$this->counttot && $this->countcon > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
			// --------------------------------------------- DIRECTORIES
			if( empty($item) || $item == "g")
				{

				$id_directory = $this->fields['g_directory'];
				$crit_fields = "";
				
				for($i = 0 ; $i < FIELDS_TO_SEARCH ; $i++)
					{
					eval("\$dirselect = \$this->fields[dirselect_$i];");
					eval("\$dirfield = \$this->fields[dirfield_$i];");
					if ($dirfield !="") 
						$crit_fields .= " and ".finder($dirfield,$dirselect);
					}
				if ($this->like || $this->like2)
					{
					$likedir = "(".finder($this->like,"cn",$option,$this->like2).
								" or ".finder($this->like,"sn",$option,$this->like2).
								" or ".finder($this->like,"mn",$option,$this->like2).
								" or ".finder($this->like,"givenname",$option,$this->like2).
								" or ".finder($this->like,"email",$option,$this->like2).
								" or ".finder($this->like,"btel",$option,$this->like2).
								" or ".finder($this->like,"mobile",$option,$this->like2).
								" or ".finder($this->like,"htel",$option,$this->like2).
								" or ".finder($this->like,"bfax",$option,$this->like2).
								" or ".finder($this->like,"title",$option,$this->like2).
								" or ".finder($this->like,"departmentnumber",$option,$this->like2).
								" or ".finder($this->like,"organisationname",$option,$this->like2).
								" or ".finder($this->like,"bstreetaddress",$option,$this->like2).
								" or ".finder($this->like,"bcity",$option,$this->like2).
								" or ".finder($this->like,"bpostalcode",$option,$this->like2).
								" or ".finder($this->like,"bstate",$option,$this->like2).
								" or ".finder($this->like,"bcountry",$option,$this->like2).
								" or ".finder($this->like,"hstreetaddress",$option,$this->like2).
								" or ".finder($this->like,"hcity",$option,$this->like2).
								" or ".finder($this->like,"hpostalcode",$option,$this->like2).
								" or ".finder($this->like,"hstate",$option,$this->like2).
								" or ".finder($this->like,"hcountry",$option,$this->like2).
								" or ".finder($this->like,"user1",$option,$this->like2).
								" or ".finder($this->like,"user2",$option,$this->like2).
								" or ".finder($this->like,"user3",$option,$this->like2).
								") and ";
					}
				$req = "create temporary table dirresults select *,sn name from ".BAB_DBDIR_ENTRIES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table dirresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id,id_group, name from ".BAB_DB_DIRECTORIES_TBL;
				if (trim($id_directory) != "") $req .= " where id='".$id_directory."'";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					$diradd = false;
					if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
						{
						if( $row['id_group'] > 0 )
							{
							list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where  id='".$row['id_group']."'"));
							if( $bdir == 'Y' )
								$diradd = true;		
							}
						else
							$diradd= true;

						if( $diradd )
							{
							if( $row['id_group'] > 1 )
								{
								$req = "select g.*, '".$row['name']."' name from ".BAB_DBDIR_ENTRIES_TBL." g , ".BAB_USERS_GROUPS_TBL." UG where  ".$likedir." UG.id_group='".$row['id_group']."' and UG.id_object=g.id_user and g.id_directory='0' ".$crit_fields." order by sn asc,givenname asc";
								}
							else
								{
								$req = "select g.*,'".$row['name']."' name from ".BAB_DBDIR_ENTRIES_TBL." g where ".$likedir." id_directory='".($row['id_group'] != 0? 0: $row['id'])."' ".$crit_fields." order by sn asc,givenname asc";
								}
							}

						if( $diradd && !empty($req))
							{
							$req = "insert into dirresults ".$req;
							$this->db->db_query($req);
							}

						}

					}

				$req = "select count(*) from dirresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "g") $navpos = 0;
				$this->navbar_g = navbar($babLimit,$nbrows,"g",$navpos);
				$tmp = explode(" ",$order);
				if (in_array("title",$tmp)) $order_tmp = "sn ASC, givenname ASC";
				$req = "select * from dirresults order by ".$order_tmp." limit ".$navpos.", ".$babLimit;
				$this->resdir = $this->db->db_query($req);
				$this->countdir = $this->db->db_num_rows($this->resdir);
				if( !$this->counttot && $this->countdir > 0 )
					$this->counttot = true;
				$this->nbresult += $nbrows;
				}
				
		
		// --------------------------------------------- AGENDA

		if( empty($item) || $item == "h")
				{
				if (trim($this->fields['after']) != "")
					{
					$crit_date = " and h.start_date >= '".$this->fields['after']."'";
					}
				if (trim($this->fields['before']) != "")
					{
					$crit_date .= " and h.end_date <= '".$this->fields['before']."'";
					}
				if (trim($this->fields['h_calendar']) != "")
					{
					$select_idcal = " and h.id_cal = '".$this->fields['h_calendar']."'";
					}

				$req = "create temporary table ageresults select h.id id,h.title, h.description,'yyyy-mm-dd' start_date, h.start_time ,'yyyy-mm-dd' end_date,h.end_time, h.id_cal owner,h.id_cal type ,h.id_cal id_cal, C.name categorie, C.description catdesc from ".BAB_CAL_EVENTS_TBL." h, ".BAB_CATEGORIESCAL_TBL." C,".BAB_CALENDAR_TBL." A where 0";
				$this->db->db_query($req);
				$req = "alter table ageresults add unique (id)";
				$this->db->db_query($req);

				$av_cal = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
				foreach($av_cal as $value)
					$list_id_cal .= $value['idcal'].",";

				if ($this->like || $this->like2)
					$reqsup = "(".finder($this->like,"h.title",$option,$this->like2)." or ".finder($this->like,"h.description",$option,$this->like2)." or ".finder($this->like,"C.description",$option,$this->like2)." or ".finder($this->like,"C.name",$option,$this->like2).") and";
				else $reqsup = "";

				if ($list_id_cal != "") 
					{
					$req = "insert into ageresults select h.id id,h.title title, h.description description,DATE_FORMAT(h.start_date,'%d-%m-%Y') start_date, h.start_time start_time ,DATE_FORMAT(h.end_date,'%d-%m-%Y') end_date,h.end_time end_time, A.owner owner,A.type type,h.id_cal id_cal, C.name categorie, C.description catdesc from ".BAB_CAL_EVENTS_TBL." h, ".BAB_CATEGORIESCAL_TBL." C,".BAB_CALENDAR_TBL." A where ".$reqsup." C.id=h.id_cat and A.id=h.id_cal".$crit_date." and h.id_cal in(".substr($list_id_cal,0,-1).")".$select_idcal." order by ".$order;
					$this->db->db_query($req);
					}

				$req = "select count(*) from ageresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "h") $navpos = 0;
				$this->navbar_h = navbar($babLimit,$nbrows,"h",$navpos);

				$req = "select * from ageresults limit ".$navpos.", ".$babLimit;
				$this->resage = $this->db->db_query($req);
				$this->countage = $this->db->db_num_rows($this->resage);
				if( !$this->counttot && $this->countage > 0 )
					$this->counttot = true;
				$this->nbresult += $nbrows;
				}
				
				if( !$this->counttot)
				{
				$babBody->msgerror = bab_translate("Search result empty");
				}
			}

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->article = put_text($arr['title']);
				$this->artdate = $arr['date'];
				$this->artauthor = $arr['author'];
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->arttopicid = $arr['id_topic'];
				$this->articleurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id']."&w=".$this->what;
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					$this->urlok = true;
				else
					$this->urlok = false;
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id'];
				$this->authormail = bab_getUserEmail($arr['id_author']);
				$this->intro = put_text($arr['head'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists artresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->artdate = $arr['date'];
				$this->artauthor = $arr['name'];
				$this->authormail = $arr['email'];
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->article = put_text($arr['arttitle']);
				$this->arttopicid = $arr['id_topic'];
				$this->com = put_text($arr['subject']);
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					$this->urlok = true;
				else
					$this->urlok = false;
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id_article'];
				$this->articleurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id_article']."&w=".$this->what;
				$this->comurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$arr['id_topic']."&article=".$arr['id_article'];
				$this->comurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=ac&idt=".$arr['id_topic']."&ida=".$arr['id_article']."&idc=".$arr['id']."&w=".$this->what;
				$this->intro = put_text($arr['message'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists comresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextfor()
			{
			static $i = 0;
			if( $i < $this->countfor)
				{
				$arr = $this->db->db_fetch_array($this->resfor);
				$this->post = put_text($arr['title']);
				$this->postauthor = $arr['author'];
				$this->postdate = $arr['date'];
				$this->forum = put_text($arr['topic']);
				$this->forumurl = $GLOBALS['babUrlScript']."?tg=threads&forum=".$arr['id_topic'];
				$this->intro = put_text($arr['message'],300);
				$this->posturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['id_topic']."&thread=".$arr['id_thread']."&post=".$arr['id']."&flat=0";
				$this->posturlpop = $GLOBALS['babUrlScript']."?tg=search&idx=b&idt=".$arr['id_thread']."&idp=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists forresults";
				$this->db->db_query($req);
				return false;
				}
			}
		function getnextfaq()
			{
			static $i = 0;
			if( $i < $this->countfaq)
				{
				$arr = $this->db->db_fetch_array($this->resfaq);
				$this->question = put_text($arr['title']);
				$this->faqtopic = put_text($arr['topic']);
				$this->faqauthor = $arr['author'];
				$this->faqauthormail = bab_getUserEmail($arr['id_manager']);
				$this->faqtopicid = $arr['idcat'];
				$this->topicurl = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['idcat'];
				$this->questionurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=c&idc=".$arr['idcat']."&idq=".$arr['id']."&w=".$this->what;
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".$arr['id'];
				$this->intro = put_text($arr['response'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists faqresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextfil()
			{
			static $i = 0;
			if( $i < $this->countfil)
				{
				$arr = $this->db->db_fetch_array($this->resfil);
				$this->file = put_text($arr['title']);
				$this->update = $arr['datem'];
				$this->created = $arr['datec'];
                $this->artauthor = $arr['author'];
				$this->filedesc = put_text($arr['description']);
				$this->path = $arr['path'];
				if ($arr['bgroup'] == 'N')
					$this->arttopic = $arr['path'];
				else
					$this->arttopic = $arr['folder']."/".$arr['path'];
				$this->arttopicid = $arr['id_owner'];
				$this->bgroup = $arr['bgroup'];
				$this->filedesc = $arr['description'];
				$this->fileurl = $GLOBALS['babUrlScript']."?tg=search&idx=e&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists filresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcon()
			{
			static $i = 0;
			if( $i < $this->countcon)
				{
				$arr = $this->db->db_fetch_array($this->rescon);
				$this->fullname = bab_composeUserName( $arr['firstname'], $arr['lastname']);
				$this->confirstname = $arr['firstname'];
				$this->conlastname = put_text($arr['title']);
				$this->conemail = $arr['email'];
				$this->concompany = $arr['compagny'];
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=search&idx=f&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists conresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextnot()
			{
			static $i = 0;
			if( $i < $this->countnot)
				{
				$arr = $this->db->db_fetch_array($this->resnot);
				$this->content = put_text($arr['content'],400);
				$this->notauthor = $GLOBALS['BAB_SESS_USER'];
				$this->notauthormail = bab_getUserEmail($GLOBALS['BAB_SESS_USERID']);
                $this->notdate = $arr['date'];
				$this->read_more = bab_translate("Edit");
				$this->noteurl = $GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextdir()
			{
			static $i = 0;
			if( $i < $this->countdir)
				{
				$arr = $this->db->db_fetch_array($this->resdir);
				foreach ($arr as $key => $value)
					$arr[$key] = stripslashes($value);
				$this->dir= $arr ;
				$this->dirurl = $GLOBALS['babUrlScript']."?tg=search&idx=g&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists dirresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextage()
			{
			static $i = 0;
			if( $i < $this->countage)
				{
				$arr = $this->db->db_fetch_array($this->resage);
				$this->agetitle = put_text($arr['title']);
				$this->agedescription = put_text($arr['description'],400);
				$this->agestart_date = $arr['start_date']." - ".$arr['start_time'];
				$this->ageend_date = $arr['end_date']." - ".$arr['end_time'];
				switch ($arr['type'])
					{
					case 1:
						$this->agecreator = bab_getUserName($arr['owner']);
						$this->agecreatormail = bab_getUserEmail($arr['owner']);
						break;
					case 2:
						$this->agecreator = bab_getGroupName($arr['owner']);
						break;
					case 3:
						break;
					}
				$this->agecat = "".$arr['categorie'];
				$this->agecatdesc = "".put_text($arr['catdesc'],200);
				$ex = explode("-",$arr['start_date']);
				$this->ageurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$ex[0]."&month=".$ex[1]."&year=".$ex[2]."&calid=".$arr['id_cal']."&evtid=".$arr['id']."&view=viewm";
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists ageresults";
				$this->db->db_query($req);
				return false;
				}
			}



		}

	$temp = new temp($item, $what, $order, $option,$navitem,$navpos);
	$babBody->babecho(	bab_printTemplate($temp,"search.html", "searchresult"));
	}

function viewArticle($article, $w)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $head;
		var $title;
		var $topic;
		var $babCss;

		function temp($article, $w)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='Y'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
	
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && bab_articleAccessByRestriction($arr['restriction']))
				{
				$this->head = highlightWord( $w, bab_replace($arr['head']));
				$this->content = highlightWord( $w, bab_replace($arr['body']));
				$this->title = highlightWord( $w, $arr['title']);
				$this->topic =bab_getCategoryTitle($arr['id_topic']);
				}
			else
				{
				$this->head = '';
				$this->content = bab_translate("Access denied");
				$this->title = '';
				$this->topic ='';
				}
			}
		}
	
	$temp = new temp($article, $w);
	echo bab_printTemplate($temp,"search.html", "viewart");
	}

function viewComment($topics, $article, $com, $w)
	{
	global $babBody;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $babCss;

		function ctp($topics, $article, $com, $w)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babUrl = $GLOBALS['babUrl'];
			$this->sitename = $GLOBALS['babSiteName'];
			$this->title = bab_getArticleTitle($article);
			$this->subject = bab_translate("Subject");
			$this->by = bab_translate("By");
			$this->date = bab_translate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			$this->arr['subject'] = highlightWord( $w, bab_replace($this->arr['subject']));
			$this->arr['message'] = highlightWord( $w, bab_replace($this->arr['message']));
			}
		}

	$ctp = new ctp($topics, $article, $com, $w);
	echo bab_printTemplate($ctp,"search.html", "viewcom");
	}

function viewPost($thread, $post, $w)
	{
	global $babBody;

	class temp
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $babCss;

		function temp($thread, $post, $w)
			{
			$db = $GLOBALS['babDB'];
			$req = "select forum from ".BAB_THREADS_TBL." where id='".$thread."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->title = bab_getForumName($arr['forum']);
			$req = "select * from ".BAB_POSTS_TBL." where id='".$post."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->postdate = bab_strftime(bab_mktime($arr['date']));
			$this->postauthor = $arr['author'];
			$this->postsubject = highlightWord( $w, bab_replace($arr['subject']));
			$this->postmessage = highlightWord( $w, bab_replace($arr['message']));
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			}
		}
	
	$temp = new temp($thread, $post, $w);
	echo bab_printTemplate($temp,"search.html", "viewfor");
	}

function viewQuestion($idcat, $id, $w)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;

		function temp($idcat, $id, $w)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['question'] = highlightWord( $w, bab_replace($this->arr['question']));
			$this->arr['response'] = highlightWord( $w, bab_replace($this->arr['response']));
			$req = "select category from ".BAB_FAQCAT_TBL." where id='$idcat'";
			$a = $this->db->db_fetch_array($this->db->db_query($req));
			$this->title = highlightWord( $w,  $a['category']);
			}

		}

	$temp = new temp($idcat, $id, $w);
	echo bab_printTemplate($temp,"search.html", "viewfaq");
	return true;
	}

function viewFile($id, $w)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;
		var $description;
		var $keywords;
		var $modified;
		var $postedby;
		var $modifiedtxt;
		var $postedbytxt;
		var $createdtxt;
		var $created;
		var $modifiedbytxt;
		var $modifiedby;
		var $sizetxt;
		var $size;
		var $download;
		var $geturl;

		function temp($id, $w)
			{
			$this->description = bab_translate("Description");
			$this->keywords = bab_translate("Keywords");
			$this->modifiedtxt = bab_translate("Modified");
			$this->createdtxt = bab_translate("Created");
			$this->postedbytxt = bab_translate("Posted by");
			$this->modifiedbytxt = bab_translate("Modified by");
			$this->download = bab_translate("Download");
			$this->sizetxt = bab_translate("Size");
			$this->pathtxt = bab_translate("Path");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FILES_TBL." where id='$id' and state='' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$access = bab_isAccessFileValid($this->arr['bgroup'], $this->arr['id_owner']);
			if( $access )
				{
				$this->title = $this->arr['name'];
				$this->arr['description'] = highlightWord( $w, $this->arr['description']);
				$this->arr['keywords'] = highlightWord( $w, $this->arr['keywords']);
				$this->modified = date("d/m/Y H:i", bab_mktime($this->arr['modified']));
				$this->created = date("d/m/Y H:i", bab_mktime($this->arr['created']));
				$this->postedby = bab_getUserName($this->arr['author']);
				$this->modifiedby = bab_getUserName($this->arr['modifiedby']);
				$this->geturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']);
				if( $this->arr['bgroup'] == "Y")
					$fstat = stat($GLOBALS['babUploadPath']."/G".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				else
					$fstat = stat($GLOBALS['babUploadPath']."/U".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				$this->size = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
				if( $this->arr['bgroup'] == "Y")
					$this->rootpath = bab_getFolderName($this->arr['id_owner']);
				else
					$this->rootpath = "";
				$this->path = $this->rootpath."/".$this->arr['path'];
				if( $this->arr['bgroup'] == 'Y' )
					{
					$this->resff = $this->db->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$this->arr['id_owner']."'");
					$this->countff = $this->db->db_num_rows($this->resff);
					}
				else
					$this->countff = 0;
				}
			else
				{
				$this->title = bab_translate("Access denied");
				$this->arr['description'] = "";
				$this->arr['keywords'] = "";
				$this->created = "";
				$this->modifiedby = "";
				$this->modified = "";
				$this->postedby = "";
				$this->geturl = "";
				$this->countff = 0;
				}
			}

		function getnextfield()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countff)
				{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_translate($arr['name']);
				$this->fieldval = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr['id']."' and id_file='".$this->arr['id']."'");
				if( $res && $babDB->db_num_rows($res) > 0)
					{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}
		}

	$temp = new temp($id, $w);
	echo bab_printTemplate($temp,"search.html", "viewfil");
	return true;
	}


function viewContact($id, $what)
	{
	class temp
		{
		var $firstname;
		var $lastname;
		var $email;
		var $compagny;
		var $hometel;
		var $mobiletel;
		var $businesstel;
		var $businessfax;
		var $jobtitle;
		var $businessaddress;
		var $homeaddress;
		var $firstnameval;
		var $lastnameval;
		var $emailval;
		var $compagnyval;
		var $hometelval;
		var $mobiletelval;
		var $businesstelval;
		var $businessfaxval;
		var $jobtitleval;
		var $businessaddressval;
		var $homeaddressval;
		var $addcontactval;
		var $cancel;
		var $babCss;
		var $msgerror;

		function temp($id, $what)
			{
			global $BAB_SESS_USERID;
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->hometel = bab_translate("Home Tel");
			$this->mobiletel = bab_translate("Mobile Tel");
			$this->businesstel = bab_translate("Business Tel");
			$this->businessfax = bab_translate("Business Fax");
			$this->jobtitle = bab_translate("Job Title");
			$this->businessaddress = bab_translate("Business Address");
			$this->homeaddress = bab_translate("Home Address");
			$this->cancel = bab_translate("Cancel");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->msgerror = "";

			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CONTACTS_TBL." where id='".$id."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			if( !empty($BAB_SESS_USERID) && $arr['owner'] == $BAB_SESS_USERID )
				{
				$this->firstnameval = $arr['firstname'];
				$this->lastnameval = $arr['lastname'];
				$this->emailval = $arr['email'];
				$this->compagnyval = $arr['compagny'];
				$this->hometelval = $arr['hometel'];
				$this->mobiletelval = $arr['mobiletel'];
				$this->businesstelval = $arr['businesstel'];
				$this->businessfaxval = $arr['businessfax'];
				$this->jobtitleval = $arr['jobtitle'];
				$this->businessaddressval = $arr['businessaddress'];
				$this->homeaddressval = $arr['homeaddress'];
				}
			else
				{
				$this->msgerror = bab_translate("You don't have access to this contact");
				$this->firstnameval = "";
				$this->lastnameval = "";
				$this->emailval = "";
				$this->compagnyval = "";
				$this->hometelval = "";
				$this->mobiletelval = "";
				$this->businesstelval = "";
				$this->businessfaxval = "";
				$this->jobtitleval = "";
				$this->businessaddressval = "";
				$this->homeaddressval = "";
				}
			}
		}

	$temp = new temp($id, $what);
	echo bab_printTemplate($temp,"search.html", "viewcon");
	}

function viewDirectoryUser($id, $what)
{
	global $babBody;

	class temp
		{

		function temp($id, $what)
			{
			$this->db = $GLOBALS['babDB'];
			
			$res = $this->db->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." where id='".$id."'");
			$this->showph = false;
			$this->count = 0;
			$access = false;
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$this->arr = $this->db->db_fetch_array($res);
				if( $this->arr['id_directory'] == 0 )
					{
					$res = $this->db->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
					while( $row = $this->db->db_fetch_array($res))
						{
						list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
						if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
							{
							if( $row['id_group'] == 1 && $GLOBALS['BAB_SESS_USERID'] != "" )
								{
								$access = true;
								break;
								}
							$res2 = $this->db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$this->arr['id_user']."' and id_group='".$row['id_group']."'");
							if( $res2 && $this->db->db_num_rows($res2) > 0 )
								{
								$access = true;
								break;
								}
							}

						}
					}
				else if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->arr['id_directory']))
					$access = true;

				if( $access )
					{
					$this->name = $this->arr['givenname']. " ". $this->arr['sn'];
					if( $this->arr['plen'] > 0 )
						$this->showph = true;

					$this->urlimg = $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->arr['id_directory']."&idu=".$id;
					$this->res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where name !='jpegphoto'");

					if( $this->res && $this->db->db_num_rows($this->res) > 0)
						$this->count = $this->db->db_num_rows($this->res);
					}
				}
			else
				{
				$this->name = "";
				$this->urlimg = "";
				}
			}
		
		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $this->arr[$arr['name']];
				if( strlen($this->arr[$arr['name']]) > 0 )
					$this->bfieldv = true;
				else
					$this->bfieldv = false;
				$i++;
				return true;
				}
			else
				return false;
			}

		}

	$temp = new temp($id, $what);
	echo bab_printTemplate($temp, "search.html", "viewdircontact");
}

if( !isset($what))
	$what = "";

if( !isset($idx))
	$idx = "";

if ((!isset($navpos)) || ($navpos == ""))
	$navpos = 0;

if((!isset($field)) || ($field==""))
	$field = " title ";

if((!isset($order)) || ($order == ""))
	$order = " ASC";

if((!isset($pat)) || ($pat == ""))
	$pat = $GLOBALS['babSearchUrl'];



switch($idx)
	{
	case "a":
		viewArticle($id, $w);
		exit;
		break;

	case "ac":
		viewComment($idt, $ida, $idc, $w);
		exit;
		break;

	case "b":
		viewPost($idt, $idp, $w);
		exit;
		break;

	case "c":
		viewQuestion($idc, $idq, $w);
		exit;
		break;

	case "e":
		viewFile($id, $w);
		exit;
		break;

	case "f":
		viewContact($id, $w);
		exit;
		break;

	case "g":
		viewDirectoryUser($id, $w);
		exit;
		break;

	case "find":
		$babBody->title = bab_translate("Search");
		searchKeyword( $item , $option);
		$order = str_replace (","," ".$order." ,",$field)." ".$order;
		startSearch( $item, $what, $order, $option,$navitem,$navpos);
		break;

	default:
		$babBody->title = bab_translate("Search");
		searchKeyword( $item ,$option);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>