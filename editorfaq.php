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

function browse($cat,$cb)
	{
	global $babBody, $babDB;

	class temp
		{
	
		var $db;
		var $count;
		var $res;

		function temp($cat,$cb)
			{
			$this->db = $GLOBALS['babDB'];
			$this->cb = "".$cb;
			
			if ($cat != 0 )
				{
				$req = "select * from ".BAB_FAQQR_TBL." where idcat='$cat'";
				$this->q = true;
				}
			else
				{
				$req = "select * from ".BAB_FAQCAT_TBL;
				$this->q = false;
				}
			$this->res = $this->db->db_query($req);	
			$this->count = $this->db->db_num_rows($this->res);
			$this->target_txt = bab_translate("popup");
			}

		function getnext()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->display = true;
				$arr = $this->db->db_fetch_array($this->res);
				if ($this->q)
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
						{
						$this->title = addslashes(str_replace("\""," ",strip_tags($arr['question'])));
						$this->titledisp = strip_tags($arr['question']);
						$tmp = str_replace("\n"," ",substr(strip_tags(bab_replace($arr['response']))."...", 0, 600));
						$tmp = stripslashes(str_replace("\""," ",$tmp));
						$this->resp = str_replace("\r"," ",$tmp);
						$this->faqid = $arr['id'];
						$this->backlink = $GLOBALS['babUrlScript']."?tg=editorfaq&idx=browse";
						}
					else
						$this->display = false;
					}
				else
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
						{
						$this->displink = false;
						$test = $this->db->db_query("select id from ".BAB_FAQQR_TBL." where idcat='".$arr['id']."'");
						$n = $this->db->db_num_rows($test);
						if ($n > 0)
							$this->displink = true;
						$this->title = $arr['category'];
						$tmp = str_replace("\n"," ",addslashes(substr(strip_tags(bab_replace($arr['description']))."...", 0, 400)));
						$tmp = stripslashes(str_replace("\""," ",$tmp));
						$this->desc = str_replace("\r"," ",$tmp);
						$this->url = $GLOBALS['babUrlScript']."?tg=editorfaq&idx=browse&cat=".$arr['id'];
						}
					else
						$this->display = false;
					}
					
				$i++;
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new temp($cat,$cb);
	echo bab_printTemplate($temp,"editorfaq.html", "editorfaq");
	}

if(!isset($cat))
	{
	$cat = 0;
	}

if(!isset($cb))
	{
	$cb = "EditorOnInsertFaq";
	}

switch($idx)
	{
	default:
	case "browse":
		browse($cat,$cb);
		exit;
	}
?>