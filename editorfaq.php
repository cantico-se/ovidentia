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
include_once 'base.php';
include_once $babInstallPath.'utilit/topincl.php';

function browse($cat)
	{
	global $babBody, $babDB;

	class temp
		{
	
		var $db;
		var $count;
		var $res;

		function temp($cat)
			{
			global $babDB;
			
			if ($cat != 0 )
				{
				$req = "select * from ".BAB_FAQQR_TBL." where idcat='".$babDB->db_escape_string($cat)."'";
				$this->q = true;
				}
			else
				{
				$req = "select * from ".BAB_FAQCAT_TBL.' order by category asc';
				$this->q = false;
				}
			$this->res = $babDB->db_query($req);	
			$this->count = $babDB->db_num_rows($this->res);
			$this->target_txt = bab_translate("popup");
			$this->backlink = bab_toHtml($GLOBALS['babUrlScript']."?tg=editorfaq&idx=browse");
			}

		function getnext()
			{
			global $babBody, $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->display = true;
				$arr = $babDB->db_fetch_array($this->res);
				if ($this->q)
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
						{
						$this->title = bab_toHtml($arr['question'], BAB_HTML_JS);
						$this->titledisp = bab_toHtml($arr['question']);
						$this->resp = '';
						$this->faqid = $arr['id'];
						
						}
					else
						$this->display = false;
					}
				else
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
						{
						$this->title = bab_toHtml($arr['category']);
						$this->desc = '';
						$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=editorfaq&idx=browse&cat=".$arr['id']);
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
		
	global $babBody;
	
	$babBody->setTitle(bab_translate('Faqs'));
	$babBody->addStyleSheet('text_toolbar.css');
	
	$temp = new temp($cat);
	$babBody->babPopup(bab_printTemplate($temp,"editorfaq.html", "editorfaq"));
	}

if(!isset($cat))
	{
	$cat = 0;
	}




$cat = 	bab_rp('cat', 0);
$idx = bab_rp('idx', 'browse');

switch($idx)
	{
	default:
	case "browse":
		browse($cat);
		exit;
	}
?>