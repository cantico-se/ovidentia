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

$babLimit = 20;

function highlightWord( $w, $text)
{
	return preg_replace("/(\s*>[^<]*|\s+)(".$w.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
}

function searchKeyword($pat, $what)
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

		function tempb($pat, $what)
			{
			global $babSearchItems;
			$this->search = bab_translate("Search");
			$this->all = bab_translate("All");
			$this->in = bab_translate("in");
			$this->pat = $pat;
			$this->what = stripslashes($what);

			foreach ($babSearchItems as $key => $value)
				{
				if( substr_count($pat, $key))
					$this->arr[] = $key;
				}
			$this->count = count($this->arr);
			}

		function getnextitem()
			{
			global $babSearchItems;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->itemvalue = $this->arr[$i];
				$this->itemname = bab_translate($babSearchItems[$this->arr[$i]]);
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$tempb = new tempb($pat, $what);
	$babBody->babecho(	bab_printTemplate($tempb,"search.html", "search"));
	}

function startSearch($pat, $item, $what, $pos)
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

		function temp($pat, $item, $what, $pos)
			{
			global $BAB_SESS_USERID, $babLimit, $babBody;

			$this->db = $GLOBALS['babDB'];
			$this->search = bab_translate("Search");
			$this->arttitle = bab_translate("Articles");
			$this->comtitle = bab_translate("Comments");
			$this->fortitle = bab_translate("Posts");
			$this->faqtitle = bab_translate("Faq");
			$this->nottitle = bab_translate("Notes");
			$this->filtitle = bab_translate("Files");
			$this->contitle = bab_translate("Contacts");
			$this->dirtitle = bab_translate("Directories");
			$this->next = bab_translate( "Next" );

			//$this->like = "not regexp '<.*".$what."[^>]*'";
			if( !bab_isMagicQuotesGpcOn())
				$this->like = "like '%".addslashes($what)."%'";
			else
				$this->like = "like '%".$what."%'";
			$this->what = urlencode(addslashes($what));
			$this->countart = 0;
			$this->countfor = 0;
			$this->countnot = 0;
			$this->countfaq = 0;
			$this->countcom = 0;
			$this->countfil = 0;
			$this->countcon = 0;
			$this->countdir = 0;
			$this->counttot = false;
			if( empty($item) || $item == "a")
				{
				$req = "create temporary table artresults select id, id_topic, title from ".BAB_ARTICLES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table artresults add unique (id)";
				$this->db->db_query($req);

				$req = "create temporary table comresults select id, id_article, id_topic, subject from ".BAB_COMMENTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table comresults add unique (id)";
				$this->db->db_query($req);

				for( $i = 0; $i < count($babBody->topview); $i++ )
					{
					$req = "insert into artresults select id, id_topic, title from ".BAB_ARTICLES_TBL." where (title ".$this->like." or head ".$this->like." or body ".$this->like.") and confirmed='Y' and id_topic='".$babBody->topview[$i]."'";
					$this->db->db_query($req);

					$req = "insert into comresults select id, id_article, id_topic, subject from ".BAB_COMMENTS_TBL." where (subject ".$this->like." or message ".$this->like.") and confirmed='Y' and id_topic='".$babBody->topview[$i]."'";
					$this->db->db_query($req);
					}

				$req = "select count(*) from artresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$req = "select * from artresults limit ".$pos.", ".$babLimit;
				$this->resart = $this->db->db_query($req);
				$this->countart = $this->db->db_num_rows($this->resart);
				if( !$this->counttot && $this->countart > 0 )
					$this->counttot = true;

				if( $pos + $babLimit < $nbrows )
					{
					$this->artpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->artnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->artpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->artnext = 0;
					}

				$req = "select count(*) from comresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->compage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->comnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->compage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->comnext = 0;
					}

				$req = "select * from comresults limit ".$pos.", ".$babLimit;
				$this->rescom = $this->db->db_query($req);
				$this->countcom = $this->db->db_num_rows($this->rescom);
				if( !$this->counttot && $this->countcom > 0 )
					$this->counttot = true;
				}

			if( empty($item) || $item == "b")
				{
				$req = "create temporary table forresults select id, id_thread, subject from ".BAB_POSTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table forresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FORUMS_TBL."";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
						{
						$req = "select id from ".BAB_THREADS_TBL." where forum='".$row['id']."'";
						$res2 = $this->db->db_query($req);
						while( $r = $this->db->db_fetch_array($res2))
							{
							$req = "insert into forresults select id, id_thread, subject from ".BAB_POSTS_TBL." where (subject ".$this->like." or message ".$this->like.") and confirmed='Y' and id_thread='".$r['id']."'";
							$this->db->db_query($req);
							}
						}
					}

				$req = "select count(*) from forresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->forpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->fornext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->forpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->fornext = 0;
					}

				$req = "select * from forresults limit ".$pos.", ".$babLimit;
				$this->resfor = $this->db->db_query($req);
				$this->countfor = $this->db->db_num_rows($this->resfor);
				if( !$this->counttot && $this->countfor > 0 )
					$this->counttot = true;
				}

			if( empty($item) || $item == "c")
				{
				$req = "create temporary table faqresults select * from ".BAB_FAQQR_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FAQCAT_TBL."";
				$res = $this->db->db_query($req);
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
						{
						$req = "insert into faqresults select * from ".BAB_FAQQR_TBL." where (question ".$this->like." or response ".$this->like.") and idcat='".$row['id']."'";
						$this->db->db_query($req);
						}
					}

				$req = "select count(*) from faqresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->faqpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->faqnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->faqpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->faqnext = 0;
					}

				$req = "select * from faqresults limit ".$pos.", ".$babLimit;
				$this->resfaq = $this->db->db_query($req);
				$this->countfaq = $this->db->db_num_rows($this->resfaq);
				if( !$this->counttot && $this->countfaq > 0 )
					$this->counttot = true;
				}
			
			if( empty($item) || $item == "e")
				{
				$req = "create temporary table filresults select * from ".BAB_FILES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table filresults add unique (id)";
				$this->db->db_query($req);
				bab_fileManagerAccessLevel();
				$private = false;
				for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
					{
					if( $babBody->aclfm['down'][$i] == 1 || $babBody->aclfm['ma'][$i] == 1)
						{
						$req = "insert into filresults select * from ".BAB_FILES_TBL." where (name ".$this->like." or description ".$this->like." or keywords ".$this->like.") and id_owner='".$babBody->aclfm['id'][$i]."' and bgroup='Y' and state='' and confirmed='Y'";
						$this->db->db_query($req);						
						}
					}

				if( $babBody->ustorage)
					{
					$req = "insert into filresults select * from ".BAB_FILES_TBL." where (name ".$this->like." or description ".$this->like." or keywords ".$this->like.") and id_owner='".$BAB_SESS_USERID."' and bgroup='N' and state='' and confirmed='Y'";
					$this->db->db_query($req);						
					}

				$req = "select count(*) from filresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->filpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->filnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->filpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->filnext = 0;
					}

				$req = "select * from filresults limit ".$pos.", ".$babLimit;
				$this->resfil = $this->db->db_query($req);
				$this->countfil = $this->db->db_num_rows($this->resfil);
				if( !$this->counttot && $this->countfil > 0 )
					$this->counttot = true;
				}

			if( (empty($item) || $item == "d") && !empty($BAB_SESS_USERID))
				{
				$req = "select count(*) from ".BAB_NOTES_TBL." where content ".$this->like." and id_user='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->notpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->notnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->notpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->notnext = 0;
					}

				$req = "select * from ".BAB_NOTES_TBL." where content ".$this->like." and id_user='".$BAB_SESS_USERID."' limit ".$pos.", ".$babLimit;
				$this->resnot = $this->db->db_query($req);
				$this->countnot = $this->db->db_num_rows($this->resnot);
				if( !$this->counttot && $this->countnot > 0 )
					$this->counttot = true;
				}

			if( empty($item) || $item == "f")
				{
				$req = "create temporary table conresults select * from ".BAB_CONTACTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table conresults add unique (id)";
				$this->db->db_query($req);
				$req = "insert into conresults select * from ".BAB_CONTACTS_TBL." where (firstname ".$this->like." or lastname ".$this->like." or email ".$this->like." or compagny ".$this->like." or jobtitle ".$this->like." or businessaddress ".$this->like." or homeaddress ".$this->like.") and owner='".$BAB_SESS_USERID."' order by lastname, firstname asc";
				$this->db->db_query($req);

				$req = "select count(*) from conresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				if( $pos + $babLimit < $nbrows )
					{
					$this->conpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->connext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->conpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->connext = 0;
					}

				$req = "select * from conresults limit ".$pos.", ".$babLimit;
				$this->rescon = $this->db->db_query($req);
				$this->countcon = $this->db->db_num_rows($this->rescon);
				if( !$this->counttot && $this->countcon > 0 )
					$this->counttot = true;
				}

			if( empty($item) || $item == "g")
				{
				$likedir = "( sn ".$this->like." or givenname ".$this->like." or mn ".$this->like.")";
				$req = "create temporary table dirresults select * from ".BAB_DBDIR_ENTRIES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table dirresults add unique (id)";
				$this->db->db_query($req);
				$req = "";
				$res = $this->db->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
				while( $row = $this->db->db_fetch_array($res))
					{
					$diradd = false;
					if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
						{	
						if( $row['id_group'] > 0 )
							{
							list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
							if( $bdir == 'Y' )
								$diradd = true;		
							}
						else
							$diradd= true;

						if( $diradd )
							{
							if( $row['id_group'] > 1 )
								{
								$req = "select ".BAB_DBDIR_ENTRIES_TBL.".* from ".BAB_DBDIR_ENTRIES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_DBDIR_ENTRIES_TBL.".id_user and ".BAB_DBDIR_ENTRIES_TBL.".id_directory='0' and ".$likedir." order by sn asc";
								}
							else
								{
								$req = "select * from ".BAB_DBDIR_ENTRIES_TBL." where ".$likedir." and  ".BAB_DBDIR_ENTRIES_TBL.".id_directory='".($row['id_group'] != 0? 0: $row['id'])."' order by sn asc";
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

				if( $pos + $babLimit < $nbrows )
					{
					$this->dirpage = ($pos + 1) . "-". $babLimit. " / " . $nbrows . " ";
					$this->dirnext = $GLOBALS['babUrlScript']."?tg=search&idx=find&item=".$item."&pos=".( $pos + $babLimit)."&pat=".$pat."&what=".$this->what;
					}
				else
					{
					$this->dirpage = ($pos + 1) . "-". $nbrows. " / " . $nbrows . " ";
					$this->dirnext = 0;
					}

				$req = "select * from dirresults limit ".$pos.", ".$babLimit;
				$this->resdir = $this->db->db_query($req);
				$this->countdir = $this->db->db_num_rows($this->resdir);
				if( !$this->counttot && $this->countdir > 0 )
					$this->counttot = true;
				}

			if( !$this->counttot)
				{
				$babBody->msgerror = bab_translate("Search result empty");
				}
			}

		function getnextdir()
			{
			static $i = 0;
			if( $i < $this->countdir)
				{
				$arr = $this->db->db_fetch_array($this->resdir);
				$this->dir = bab_composeUserName($arr['givenname'], $arr['sn']);
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

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->article = $arr['title'];
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id']."&w=".$this->what;
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
				$this->com = $arr['subject'];
				$this->comurl = $GLOBALS['babUrlScript']."?tg=search&idx=ac&idt=".$arr['id_topic']."&ida=".$arr['id_article']."&idc=".$arr['id']."&w=".$this->what;
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
				$this->post = $arr['subject'];
				$this->posturl = $GLOBALS['babUrlScript']."?tg=search&idx=b&idt=".$arr['id_thread']."&idp=".$arr['id']."&w=".$this->what;
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
				$this->question = $arr['question'];
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=search&idx=c&idc=".$arr['idcat']."&idq=".$arr['id']."&w=".$this->what;
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
				$this->file = $arr['name'];
				if( !empty($arr['description']))
					$this->filedesc = "( ".$arr['description']." )";
				else
					$this->filedesc = "";
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
				$this->content = highlightWord( $this->what, bab_replace($arr['content']));
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new temp($pat, $item, $what, $pos);
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
	
			$this->head = highlightWord( $w, bab_replace($arr['head']));
			$this->content = highlightWord( $w, bab_replace($arr['body']));
			$this->title = highlightWord( $w, $arr['title']);
			$this->topic =bab_getCategoryTitle($arr['id_topic']);
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
					$this->rootpath = bab_getGroupName($this->arr['id_owner']);
				else
					$this->rootpath = "";
				$this->path = $this->rootpath."/".$this->arr['path'];
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


if( !isset($pos))
	$pos = 0;

if( !isset($what))
	$what = "";

if( !isset($idx))
	$idx = "";

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
		searchKeyword($pat, $what);
		startSearch($pat, $item, $what, $pos);
		break;

	default:
		$babBody->title = bab_translate("Search");
		searchKeyword($pat, $what);
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>

