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
include_once $babInstallPath.'utilit/statutil.php';
include_once $babInstallPath.'utilit/uiutil.php';

function summaryDelegatList($col, $order)
	{
	global $babBody;
	class summaryDelegatListCls extends summaryBaseCls
		{

		var $delegitemdesc;
		var $groupname;
		var $delegname;
		var $grouptxt;
		var $delegtxt;
		var $res;
		var $count;
		var $altbg = true;
		var $arr = array();
		var $total;
		var $delegitem;

		function summaryDelegatListCls($col, $order)
			{
			global $babDB;
			$this->summaryBaseCls();
			$this->grouptxt = bab_translate("Group");
			$this->delegtxt = bab_translate("Delegation");
			
			$res = $babDB->db_query("select dg.*,g.lf,g.lr from ".BAB_GROUPS_TBL." g, ".BAB_DG_GROUPS_TBL." dg where g.id=dg.id_group ORDER BY dg.name");

			$this->babDG = array(	array("groups", bab_translate("Groups")),
				array("sections", bab_translate("Sections")),
				array("topcats", bab_translate("Topics categories")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("directories", bab_translate("Directories")),
				array("folders", bab_translate("Folders")),
				array("orgcharts", bab_translate("Charts"))
				);

			$this->oldorder = mb_strtolower($order);
			$this->sortord = $this->oldorder == "asc"? "desc": "asc";
			$this->sortcol = $col;

			$this->arrinfo = array();
			while( $arr = $babDB->db_fetch_array($res))
				{
				$tmparr = array();
				$tmparr['dgname'] = $arr['name'];

				list($tmparr['groups']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_GROUPS_TBL." where nb_set>='0' AND lf>'".$babDB->db_escape_string($arr['lf'])."' AND lr<'".$babDB->db_escape_string($arr['lr'])."'"));
				list($tmparr['sections']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_SECTIONS_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['topcats']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['faqs']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FAQCAT_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['forums']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FORUMS_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['directories']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['folders']) = $babDB->db_fetch_row($babDB->db_query("select count(*) from ".BAB_FM_FOLDERS_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				list($tmparr['orgcharts']) = $babDB->db_fetch_row($babDB->db_query("select count(*) from ".BAB_ORG_CHARTS_TBL." where id_dgowner = '".$babDB->db_escape_string($arr['id'])."'"));
				$this->arrinfo[] = $tmparr;
				}

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordgr = $GLOBALS['babUrlScript']."?tg=stat&idx=delegat&order=".($col == 'dgname'? $this->sortord: $this->oldorder)."&col=dgname";
			$this->current = 0;
			}

		function getnext()
			{
			global $babDB;
			static $k = 0;
			if( $k < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->delegname = $this->arrinfo[$k]['dgname'];
				$k++;
				$this->current = $k - 1;
				return true;
				}
			else
				{
				return false;
				}
			}

		function isNumeric($col)
			{

			switch( $col )
				{
				case 'groups':
				case 'sections':
				case 'topcats':
				case 'faqs':
				case 'forums':
				case 'folders':
				case 'directories':
				case 'orgcharts':
					return true;
				default:
					return false;
				}
			}

		function getnextdg()
			{
			static $i = 0;
			if( $i < count($this->babDG))
				{
				$this->urlord = $GLOBALS['babUrlScript']."?tg=stat&idx=delegat&order=".($this->sortcol == $this->babDG[$i][0]? $this->sortord: $this->oldorder)."&col=".$this->babDG[$i][0];
				$this->delegitemdesc = $this->babDG[$i][1];
				$this->delegitem = $this->babDG[$i][0];
				$this->total = $this->arrinfo[$this->current][$this->delegitem];
				if( $this->sortcol == $this->delegitem )
					{
					$this->bsorturl = true;
					}
				else
					{
					$this->bsorturl = false;
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

	$temp = new summaryDelegatListCls($col, $order);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = $temp->grouptxt." (".$temp->delegtxt.")";

		while( $temp->getnextdg())
			{
			$output .= $GLOBALS['exportchr']. $temp->delegitemdesc;
			}
		$output .= "\n";

		while($temp->getnext())
			{
			$output .= $temp->groupname." (".$temp->delegname.")";
			while( $temp->getnextdg())
				{
				$output .= $GLOBALS['exportchr'].$temp->total;
				}
			$output .= "\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(bab_printTemplate($temp, "statboard.html", "summarydelegationlist"));
		}
	}

function summarySections($col, $order)
	{
	global $babBody;
	class summarySectionsCls extends summaryBaseCls
		{
		var $altbg = true;

		function summarySectionsCls($col, $order)
			{
			global $babBody, $babDB;
			$this->summaryBaseCls();
			$this->sectiontxt = bab_translate("Section");
			$this->delegattxt = bab_translate("Delegation");
			$this->usagetxt = bab_translate("Usage");

			$order = mb_strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
	

			list($this->utotal) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_USERS_TBL.""));
			
			$req = "select st.*, dg.name as dgname from ".BAB_SECTIONS_TBL." st left join ".BAB_DG_GROUPS_TBL." dg on st.id_dgowner=dg.id where st.optional='Y'";
			$ressec = $babDB->db_query($req);

			$this->arrinfo = array();
			while($arr = $babDB->db_fetch_array($ressec))
				{
				$upercent = "0";
				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $babDB->db_fetch_row($babDB->db_query("select count(sst.id_user) from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='2' and sst.id_section='".$babDB->db_escape_string($arr['id'])."' and sst.hidden='Y'"));
					$groups = array();
					if( $totalh != 0 )
						{
						$res = $babDB->db_query("select * from ".BAB_SECTIONS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id'])."'");
						while( $row = $babDB->db_fetch_array($res))
							{
							switch($row['id_group'])
								{
								case "0": // everybody
								case "1": // users
									if( $this->utotal <= $totalh  )
										{
										$upercent = 0;
										}
									else
										{
										$upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
										}
									break;
								case "2": // guests
									$upercent = 100;
									break;
								default:  //groups
									$groups[] = $row['id_group'];
									break;
								}
							}

						if( count($groups) > 0 )
							{
							list($total) = $babDB->db_fetch_row($babDB->db_query("select distinct count(id_object) from ".BAB_USERS_GROUPS_TBL." where id_group in (".$babDB->quote($groups).")"));
							if( $total <= $totalh )
								{
								$upercent = 0;
								}
							else
								{
								$upercent = round((($total - $totalh)*100)/$total,2);
								}
							}
						}
					}
			
				$tmparr = array();
				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = $arr['dgname'];
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;
				}

			/* don't get Administrator section */
			$res = $babDB->db_query("select pst.* from ".BAB_PRIVATE_SECTIONS_TBL." pst where pst.optional='Y' and pst.id > '1'");
			while($arr = $babDB->db_fetch_array($res))
				{
				$upercent = 0;
				$tmparr = array();
				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $babDB->db_fetch_row($babDB->db_query("select count(sst.id_user) as totalh from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='1' and sst.id_section='".$babDB->db_escape_string($arr['id'])."' and sst.hidden='Y'"));
					if( $totalh != 0 )
						{
						if( $this->utotal <= $totalh )
							{
							$upercent = 0;
							}
						else
							{
							$upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
							}
						}
					}

				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = '';
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;
				}

			$arrtopcat = array();
			$res1 = $babDB->db_query("select id, id_cat from ".BAB_TOPICS_TBL."");
			while( $arr = $babDB->db_fetch_array($res1))
				{
				$arrtopcat[$arr['id_cat']] = array();
				$res = $babDB->db_query("select * from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id'])."'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( count($arrtopcat[$arr['id_cat']]) == 0 || !in_array($row['id_group'], $arrtopcat[$arr['id_cat']]))
						{
						$arrtopcat[$arr['id_cat']][] = $row['id_group'];
						}
					}
				}

			$req = "select tct.*, dg.name as dgname  from ".BAB_TOPICS_CATEGORIES_TBL." tct left join ".BAB_DG_GROUPS_TBL." dg on tct.id_dgowner=dg.id where tct.optional='Y'";
			$rescat = $babDB->db_query($req);


			require_once dirname(__FILE__).'/utilit/artapi.php';
			$topcats = bab_getArticleCategories();
			
			while( $arr = $babDB->db_fetch_array($rescat) )
				{
				$upercent = 0;
				$cat = $arr['id'];
				if( !isset($arrtopcat[$cat]))
					{
					$arrtopcat[$cat] = array();;
					}
				
				while( $topcats[$cat]['parent'] != 0 )
					{
					for( $i = 0; $i < count($arrtopcat[$arr['id']]); $i++ )
						{
						if( count($arrtopcat[$arr['id']]) == 0 || !in_array($arrtopcat[$arr['id']][$i], $arrtopcat[$arr['id']]))
							{
							$arrtopcat[$arr['id']][] = $arrtopcat[$arr['id']][$i];
							}
						}
					$cat = $topcats[$cat]['parent'];
					}

				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $babDB->db_fetch_row($babDB->db_query("select count(sst.id_user) as totalh from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='3' and sst.id_section='".$babDB->db_escape_string($arr['id'])."' and sst.hidden='Y'"));

					if( $totalh != 0  && count($arrtopcat[$arr['id']]) > 0 )
						{
						if( in_array(0, $arrtopcat[$arr['id']]) || in_array(1, $arrtopcat[$arr['id']]) || in_array(2, $arrtopcat[$arr['id']]))
							{
							if( $this->utotal <= $totalh )
								{
								$upercent = 0;
								}
							else
								{
								$upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
								}
							}
						else
							{
							list($total) = $babDB->db_fetch_row($babDB->db_query("select distinct count(id_object) from ".BAB_USERS_GROUPS_TBL." where id_group in (".$babDB->quote($arrtopcat[$arr['id']]).")"));
							if( $total <= $totalh )
								{
								$upercent = 0;
								}
							else
								{
								$upercent = round((($total - $totalh)*100)/$total,2);
								}
							}
						}
					}

				$tmparr = array();
				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = $arr['dgname'];
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;				
				}

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);
			$this->urlordsec = $GLOBALS['babUrlScript']."?tg=stat&idx=sections&order=".($col == 'section'? $this->sortord: $order)."&col=section";
			$this->urlorddesc = $GLOBALS['babUrlScript']."?tg=stat&idx=sections&order=".($col == 'dgname'? $this->sortord: $order)."&col=dgname";
			$this->urlordusage = $GLOBALS['babUrlScript']."?tg=stat&idx=sections&order=".($col == 'usage'? $this->sortord: $order)."&col=usage";
			}

		function isNumeric($col)
			{
			switch( $col )
				{
				case 'usage':
					return true;
				default:
					return false;
				}
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->sectionname = $this->arrinfo[$i]['section'];
				$this->delegation = isset($this->arrinfo[$i]['dgname'])? $this->arrinfo[$i]['dgname']: '';
				$this->total = $this->arrinfo[$i]['usage'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new summarySectionsCls($col, $order);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = $temp->sectiontxt.$GLOBALS['exportchr'].$temp->delegattxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->sectionname.$GLOBALS['exportchr'].$temp->delegation.$GLOBALS['exportchr'].$temp->total."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(	bab_printTemplate($temp, "statboard.html", "sectionssummary"));
		}
	}


function summaryUsers()
{
	global $babBody;
	class summaryUsersCls
		{
		var $altbg = true;

		function summaryUsersCls()
			{
			global $babDB, $babBody;
			$this->arrinfo = array();

			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(id) total FROM ".BAB_USERS_TBL));
			$this->arrinfo[] = array(bab_translate("Registered users"), $arr['total']);
			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(id) total FROM ".BAB_USERS_TBL." where is_confirmed='0'"));
			$this->arrinfo[] = array(bab_translate("Unconfirmed users"), $arr['total']);
			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(id) total FROM ".BAB_USERS_TBL." where disabled='1'"));
			$this->arrinfo[] = array(bab_translate("Accounts disabled"), $arr['total']);
			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(distinct id) total FROM ".BAB_USERS_LOG_TBL." where id_user!='0'"));
			$this->arrinfo[] = array(bab_translate("Online registered users"), $arr['total']);
			$arr = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(distinct id) total FROM ".BAB_USERS_LOG_TBL." where id_user='0'"));
			$this->arrinfo[] = array(bab_translate("Online anonymous users"), $arr['total']);
			$this->count = count($this->arrinfo);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->itemtxt = $this->arrinfo[$i][0];
				$this->total = $this->arrinfo[$i][1];
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	
	$temp = new summaryUsersCls();
	$babBody->babecho(	bab_printTemplate($temp, "statboard.html", "userssummary"));
}
?>