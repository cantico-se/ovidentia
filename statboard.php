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
include_once $babInstallPath."utilit/uiutil.php";

class summaryBaseCls
{
	function summaryBaseCls()
	{
		$this->sorttxt = bab_translate("Sort");
	}

	function isNumeric($col)
	{
		return false;
	}

	function compare($a, $b)
		{
		$r = 0;
		if( $this->isNumeric($this->sortcol))
			{
			if( $a[$this->sortcol]  < $b[$this->sortcol] )
				{
				$r = -1;
				}
			elseif( $a[$this->sortcol]  > $b[$this->sortcol] )
				{
				$r = 1;
				}
			else
				{
				$r = 0;
				}
			}
		else
			{
			$r = strnatcmp($a[$this->sortcol],$b[$this->sortcol]);
			}

		if ($this->sortord == "desc")
			{
			$r = $r * -1;
			}
		return $r;
		}

}

function displaySummaryPanel($idx)
{
	global $babBody;
	class displaySummaryPanelCls
		{
		var $altbg = true;
		var $itemarray = array();

		function displaySummaryPanelCls($idx)
			{
			$this->current = $idx;
			$this->itemarray[] = array('idx' => 'users', 'item' => bab_translate("Users"), 'url' => $GLOBALS['babUrlScript']."?tg=statboard&idx=users");
			$this->itemarray[] = array('idx' => 'fm', 'item' => bab_translate("File manager"), 'url' => $GLOBALS['babUrlScript']."?tg=statboard&idx=fm");
			$this->itemarray[] = array('idx' => 'sections', 'item' => bab_translate("Optional sections"), 'url' => $GLOBALS['babUrlScript']."?tg=statboard&idx=sections");
			$this->itemarray[] = array('idx' => 'delegat', 'item' => bab_translate("Delegation"), 'url' => $GLOBALS['babUrlScript']."?tg=statboard&idx=delegat");
			$this->count = count($this->itemarray);
			}

		function getnextitem($idx)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->itemurltxt = $this->itemarray[$i]['item'];
				$this->itemurl = $this->itemarray[$i]['url'];
				if( $this->current == $this->itemarray[$i]['idx'] )
					{
					$this->disabled = true;
					}
				else
					{
					$this->disabled = false;
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}
	$temp = new displaySummaryPanelCls($idx);
	$babBody->babecho(bab_printTemplate($temp, "statboard.html", "summarypanel"));
}


function summaryFolders($col, $order)
	{
	global $babBody;
	class summaryFoldersCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryFoldersCls($col, $order)
			{
			global $babBody, $babDB;
			$this->summaryBaseCls();
			$this->fullname = bab_translate("Folders");
			$this->dgtxt = bab_translate("Delegation");
			$this->diskspacetxt = bab_translate("Disk space");
			$this->filestxt = bab_translate("Files");
			$this->versionstxt = bab_translate("Versions");
			$this->kilooctet = " ".bab_translate("Kb");
			$res = $babDB->db_query("select fft.*, dgt.name as dgname, count(ft.id) as files from ".BAB_FM_FOLDERS_TBL." fft left join ".BAB_GROUPS_TBL." gt on fft.id_dgowner=gt.id left join ".BAB_DG_GROUPS_TBL." dgt on gt.id_dggroup=dgt.id left join ".BAB_FILES_TBL." ft on ft.id_owner=fft.id group by fft.id");		
			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->nbfilest = 0;
			$this->nbfilesvt = 0;
			$this->diskspacet = 0;
			$this->arrinfo = array();
			while($arr = $babDB->db_fetch_array($res))
				{
				$tmparr = array();
				$tmparr['id'] = $arr['id'];
				$tmparr['folder'] = $arr['folder'];
				$tmparr['dgname'] = isset($arr['dgname'])? $arr['dgname']: '';
				$tmparr['files'] = isset($arr['files'])? $arr['files']: 0;
				$pathx = bab_getUploadFullPath("Y", $arr['id']);
				$tmparr['diskspace'] = getDirSize($pathx);
				$rr = $babDB->db_fetch_array($babDB->db_query("SELECT count( ffvt.id ) as total FROM bab_fm_filesver ffvt LEFT  JOIN bab_files ft ON ffvt.id_file = ft.id WHERE ft.id_owner =  '".$arr['id']."' AND ft.bgroup =  'Y'"));
				$tmparr['versions'] = isset($rr['total'])? $rr['total']: 0;
				$this->arrinfo[] = $tmparr;
				$this->nbfilest += $tmparr['files'];
				$this->nbfilesvt += $tmparr['versions'];
				$this->diskspacet += $tmparr['diskspace'];
				}

			$h = opendir($GLOBALS['babUploadPath']);
			$size = 0;
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_dir($GLOBALS['babUploadPath']."/".$f))
						{
						if( $f{0} == 'U' && is_numeric(substr($f, 1)))
							{
							$size += getDirSize($GLOBALS['babUploadPath']."/".$f);
							}
						}
					}
				}

			$tmparr = array();
			$tmparr['id'] = 0;
			$tmparr['folder'] = bab_translate("Personal Folders");
			$tmparr['dgname'] = '';
			$tmparr['diskspace'] = $size;
			$rr = $babDB->db_fetch_array($babDB->db_query("SELECT count( ft.id ) as total FROM bab_files ft WHERE ft.bgroup =  'N'"));
			$tmparr['files'] = isset($rr['total'])? $rr['total']: 0;
			$tmparr['versions'] = 0;
			$this->arrinfo[] = $tmparr;
			$this->nbfilest += $tmparr['files'];
			$this->nbfilesvt += $tmparr['versions'];
			$this->diskspacet += $tmparr['diskspace'];

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordfn = $GLOBALS['babUrlScript']."?tg=statboard&idx=fm&order=".($col == 'folder'? $this->sortord: $order)."&col=folder";
			$this->urlorddg = $GLOBALS['babUrlScript']."?tg=statboard&idx=fm&order=".($col == 'dgname'? $this->sortord: $order)."&col=dgname";
			$this->urlordds = $GLOBALS['babUrlScript']."?tg=statboard&idx=fm&order=".($col == 'diskspace'? $this->sortord: $order)."&col=diskspace";
			$this->urlordf = $GLOBALS['babUrlScript']."?tg=statboard&idx=fm&order=".($col == 'files'? $this->sortord: $order)."&col=files";
			$this->urlordfv = $GLOBALS['babUrlScript']."?tg=statboard&idx=fm&order=".($col == 'versions'? $this->sortord: $order)."&col=versions";
			$this->diskspacettxt = bab_formatSizeFile($this->diskspacet).$this->kilooctet;
			$this->diskspacetpc = round(($this->diskspacet*100)/$GLOBALS['babMaxTotalSize'],2);
			}

		function isNumeric($col)
			{
			switch( $this->sortcol )
				{
				case 'diskspace':
					return true;
				default:
					return false;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->fid = $this->arrinfo[$i]['id'];
				if( $this->fid )
					{
					$this->url = false;
					}
				else
					{
					$this->url = $GLOBALS['babUrlScript']."?tg=statboard&idx=sumdp&fid=".$this->fid;
					}
				$this->urlname = $this->arrinfo[$i]['folder'];//."--".$this->fid;
				$this->dgname = $this->arrinfo[$i]['dgname'];
				$this->nbfiles = $this->arrinfo[$i]['files'];
				$this->nbfilesv = $this->arrinfo[$i]['versions'];
				$this->diskspace = bab_formatSizeFile($this->arrinfo[$i]['diskspace']).$this->kilooctet;
				$this->diskspacepc = $this->diskspacet > 0 ? round(($this->arrinfo[$i]['diskspace']*100)/$this->diskspacet,2): 0;
				$this->nbfilespc = $this->nbfilest > 0 ? round(($this->arrinfo[$i]['files']*100)/$this->nbfilest,2): 0;
				$this->nbfilesvpc = $this->nbfilesvt > 0 ? round(($this->arrinfo[$i]['versions']*100)/$this->nbfilesvt,2): 0;
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new summaryFoldersCls($col, $order);
	$babBody->babecho(	bab_printTemplate($temp, "statboard.html", "summaryfolderlist"));
	return $temp->count;
	}


function showPersonalFoldersDetail()
{
	global $babBodyPopup;
	class showPersonalFoldersDetailCls
		{
		var $altbg = true;
		var $fullnametxt;
		var $diskspacetxt;
		var $kilooctet;
		var $arrinfo;
		var $fullname;
		var $diskspace;

		function showPersonalFoldersDetailCls()
			{
			global $babBodyPopup, $babBody, $babDB;
			$this->fullnametxt = bab_translate("User");
			$this->diskspacetxt = bab_translate("Disk space");
			$this->kilooctet = " ".bab_translate("Kb");

			$this->arrinfo = array();

			$h = opendir($GLOBALS['babUploadPath']);
			$size = 0;
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_dir($GLOBALS['babUploadPath']."/".$f))
						{
						if( $f{0} == 'U' && is_numeric(substr($f, 1)))
							{
							$size = getDirSize($GLOBALS['babUploadPath']."/".$f);
							if( $size > 0 )
								{
								$this->arrinfo[substr($f, 1)] = $size;
								}
							}
						}
					}
				}
			arsort($this->arrinfo, SORT_REGULAR);
			$this->count= count($this->arrinfo);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				list($key, $val) = each($this->arrinfo);
				$this->fullname = bab_getUserName($key);
				$this->diskspace = bab_formatSizeFile($val).$this->kilooctet;
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}
	$temp = new showPersonalFoldersDetailCls();
	$babBodyPopup->babecho(bab_printTemplate($temp, "statboard.html", "personalfoldersdetail"));
}


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
			$res = $babDB->db_query("select gt.id as idgroup, gt.name as grpname, dgt.* from ".BAB_GROUPS_TBL." gt left join ".BAB_DG_GROUPS_TBL." dgt on dgt.id=gt.id_dggroup where gt.id_dggroup != '0' group by gt.id");
			$this->babDG = array(	array("groups", bab_translate("Groups")),
				array("sections", bab_translate("Sections")),
				array("topcats", bab_translate("Topics categories")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("directories", bab_translate("Directories")),
				array("folders", bab_translate("Folders")),
				array("orgcharts", bab_translate("Charts"))
				);

			$this->oldorder = strtolower($order);
			$this->sortord = $this->oldorder == "asc"? "desc": "asc";
			$this->sortcol = $col;

			$this->arrinfo = array();
			while( $arr = $babDB->db_fetch_array($res))
				{
				$tmparr = array();
				$tmparr['dgname'] = $arr['name'];
				$tmparr['grpname'] = $arr['grpname'];
				list($tmparr['groups']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_GROUPS_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['sections']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_SECTIONS_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['topcats']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['faqs']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FAQCAT_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['forums']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_FORUMS_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['directories']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['folders']) = $babDB->db_fetch_row($babDB->db_query("select count(*) from ".BAB_FM_FOLDERS_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				list($tmparr['orgcharts']) = $babDB->db_fetch_row($babDB->db_query("select count(*) from ".BAB_ORG_CHARTS_TBL." where id_dgowner = '".$arr['idgroup']."'"));
				$this->arrinfo[] = $tmparr;
				}

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordgr = $GLOBALS['babUrlScript']."?tg=statboard&idx=delegat&order=".($col == 'grpname'? $this->sortord: $this->oldorder)."&col=grpname";
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
				$this->groupname = $this->arrinfo[$k]['grpname'];
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
				$this->urlord = $GLOBALS['babUrlScript']."?tg=statboard&idx=delegat&order=".($this->sortcol == $this->babDG[$i][0]? $this->sortord: $this->oldorder)."&col=".$this->babDG[$i][0];
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
	$babBody->babecho(bab_printTemplate($temp, "statboard.html", "summarydelegationlist"));
	}

function summarySections($col, $order)
	{
	global $babBody;
	class summarySectionsCls extends summaryBaseCls
		{
		var $altbg = true;

		function summarySectionsCls($col, $order)
			{
			global $babBody;
			$this->summaryBaseCls();
			$this->sectiontxt = bab_translate("Section");
			$this->delegattxt = bab_translate("Delegation");
			$this->usagetxt = bab_translate("Usage");

			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
	
			$this->db = $GLOBALS['babDB'];

			list($this->utotal) = $this->db->db_fetch_row($this->db->db_query("select count(id) from ".BAB_USERS_TBL.""));
			$req = "select st.*, dgt.name as dgname from ".BAB_SECTIONS_TBL." st left join ".BAB_GROUPS_TBL." gt on st.id_dgowner=gt.id left join ".BAB_DG_GROUPS_TBL." dgt on gt.id_dggroup=dgt.id where st.optional='Y'";
			$ressec = $this->db->db_query($req);

			$this->arrinfo = array();
			while($arr = $this->db->db_fetch_array($ressec))
				{
				$upercent = "0";
				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $this->db->db_fetch_row($this->db->db_query("select count(sst.id_user) from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='2' and sst.id_section='".$arr['id']."' and sst.hidden='Y'"));
					$groups = array();
					$res = $this->db->db_query("select * from ".BAB_SECTIONS_GROUPS_TBL." where id_object='".$arr['id']."'");
					while( $row = $this->db->db_fetch_array($res))
						{
						switch($row['id_group'])
							{
							case "0": // everybody
							case "1": // users
							case "2": // guests
								$upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
								break;
							default:  //groups
								$groups[] = $row['id_group'];
								break;
							}
						}

					if( count($groups) > 0 )
						{
						list($total) = $this->db->db_fetch_row($this->db->db_query("select distinct count(id_object) from ".BAB_USERS_GROUPS_TBL." where id_group in (".implode(',', $groups).")"));

						$upercent = round((($total - $totalh)*100)/$total,2);
						}
					}
			
				$tmparr = array();
				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = $arr['dgname'];
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;
				}

			/* don't get Administrator section */
			$res = $this->db->db_query("select pst.* from ".BAB_PRIVATE_SECTIONS_TBL." pst where pst.optional='Y' and pst.id > '1'");
			while($arr = $this->db->db_fetch_array($res))
				{
				$tmparr = array();
				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $this->db->db_fetch_row($this->db->db_query("select count(sst.id_user) as totalh from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='1' and sst.id_section='".$arr['id']."' and sst.hidden='Y'"));
					$upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
					}
				else
					{
					$upercent = 0;
					}
				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = '';
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;
				}

			$arrtopcat = array();
			$res1 = $this->db->db_query("select id, id_cat from ".BAB_TOPICS_TBL."");
			while( $arr = $this->db->db_fetch_array($res1))
				{
				$arrtopcat[$arr['id_cat']] = array();
				$res = $this->db->db_query("select * from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='".$arr['id']."'");
				while( $row = $this->db->db_fetch_array($res))
					{
					if( count($arrtopcat[$arr['id_cat']]) == 0 || !in_array($row['id_group'], $arrtopcat[$arr['id_cat']]))
						{
						$arrtopcat[$arr['id_cat']][] = $row['id_group'];
						}
					}
				}

			$req = "select tct.*, dgt.name as dgname  from ".BAB_TOPICS_CATEGORIES_TBL." tct left join ".BAB_GROUPS_TBL." gt on tct.id_dgowner=gt.id left join ".BAB_DG_GROUPS_TBL." dgt on gt.id_dggroup=dgt.id where tct.optional='Y'";
			$rescat = $this->db->db_query($req);

			while( $arr = $this->db->db_fetch_array($rescat) )
				{
				$cat = $arr['id'];
				if( !isset($arrtopcat[$cat]))
					{
					$arrtopcat[$cat] = array();;
					}
				while( $babBody->topcats[$cat]['parent'] != 0 )
					{
					for( $i = 0; $i < count($arrtopcat[$arr['id']]); $i++ )
						{
						if( count($arrtopcat[$arr['id']]) == 0 || !in_array($arrtopcat[$arr['id']][$i], $arrtopcat[$arr['id']]))
							{
							$arrtopcat[$arr['id']][] = $arrtopcat[$arr['id']][$i];
							}
						}
					$cat = $babBody->topcats[$cat]['parent'];
					}

				if( $arr['enabled'] == "Y")
					{
					list($totalh) = $this->db->db_fetch_row($this->db->db_query("select count(sst.id_user) as totalh from ".BAB_SECTIONS_STATES_TBL." sst where sst.type='3' and sst.id_section='".$arr['id']."' and sst.hidden='Y'"));

					if( count($arrtopcat[$arr['id']]) > 0 )
						{
						if( in_array(0, $arrtopcat[$arr['id']]) || in_array(1, $arrtopcat[$arr['id']]) || in_array(2, $arrtopcat[$arr['id']]))
							{
							$this->upercent = round((($this->utotal - $totalh)*100)/$this->utotal,2);
							}
						else
							{
							list($total) = $this->db->db_fetch_row($this->db->db_query("select distinct count(id_object) from ".BAB_USERS_GROUPS_TBL." where id_group in (".implode(',', $arrtopcat[$arr['id']]).")"));
							$this->upercent = round((($total - $totalh)*100)/$total,2);
							}
						}
					else
						{
						$this->upercent = 0;
						}
					}
				else
					{
					$this->upercent = 0;
					}

				$tmparr = array();
				$tmparr['section'] = $arr['title'];
				$tmparr['dgname'] = $arr['dgname'];
				$tmparr['usage'] = $upercent;
				$this->arrinfo[] = $tmparr;				
				}

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);
			$this->urlordsec = $GLOBALS['babUrlScript']."?tg=statboard&idx=sections&order=".($col == 'section'? $this->sortord: $order)."&col=section";
			$this->urlorddesc = $GLOBALS['babUrlScript']."?tg=statboard&idx=sections&order=".($col == 'dgname'? $this->sortord: $order)."&col=dgname";
			$this->urlordusage = $GLOBALS['babUrlScript']."?tg=statboard&idx=sections&order=".($col == 'usage'? $this->sortord: $order)."&col=usage";
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
	if( $temp->count == 0 )
		{
		return;
		}
	$babBody->babecho(	bab_printTemplate($temp, "statboard.html", "sectionssummary"));
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

/* main */
if( !bab_isAccessValid(BAB_STATSMAN_GROUPS_TBL, 1))
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx)) { $idx = 'users';}
displaySummaryPanel($idx);

switch($idx)
	{
	case "users":
		summaryUsers();
		break;
	case "sections":
		if( !isset($col)) { $col = 'usage';}
		if( !isset($order)) { $order = 'desc';}
		summarySections($col, $order);
		break;

	case "delegat":
		if( !isset($col)) { $col = 'grpname';}
		if( !isset($order)) { $order = 'desc';}
		summaryDelegatList($col, $order);
		break;

	case "sumdp":
		include_once $babInstallPath."utilit/fileincl.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Personal Folders");
		if( $fid == 0 )
			{
			showPersonalFoldersDetail();
			}
		printBabBodyPopup();
		exit;
		break;

	case "fm":
		if( $babBody->isSuperAdmin )
		{
		include_once $babInstallPath."utilit/fileincl.php";
		if( !isset($col)) { $col = 'diskspace';}
		if( !isset($order)) { $order = 'desc';}
		summaryFolders($col, $order);
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	default:
		break;
	}
?>