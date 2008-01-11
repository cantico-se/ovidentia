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
include_once $babInstallPath."utilit/statutil.php";
include_once $babInstallPath."utilit/uiutil.php";

function summaryFileManager($col, $order)
	{
	global $babBody;
	class summaryFileManagerCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryFileManagerCls($col, $order)
			{
			global $babBody, $babDB;
			$this->summaryBaseCls();
			$this->fullname = bab_translate("Folders");
			$this->dgtxt = bab_translate("Delegation");
			$this->diskspacetxt = bab_translate("Disk space");
			$this->filestxt = bab_translate("Files");
			$this->versionstxt = bab_translate("Versions");
			$this->kilooctet = " ".bab_translate("Kb");
			$req = "select fft.*, dg.name as dgname, fft.id_dgowner as iIdDgOwner, count(ft.id) as files from ".BAB_FM_FOLDERS_TBL." fft left join ".BAB_DG_GROUPS_TBL." dg on fft.id_dgowner=dg.id left join ".BAB_FILES_TBL." ft on ft.id_owner=fft.id";		
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " where fft.id_dgowner='".$babBody->currentAdmGroup."'";
				}
			$req .= " group by fft.id";

			$res = $babDB->db_query($req);		
			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->nbfilest = 0;
			$this->nbfilesvt = 0;
			$this->diskspacet = 0;
			$this->arrinfo = array();
			
			require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
			$oFileManagerEnv =& getEnvObject();
			
			$sRootFmPath = $oFileManagerEnv->getFmUploadPath();

			while($arr = $babDB->db_fetch_array($res))
				{
				$tmparr = array();
				$tmparr['id'] = $arr['id'];
				$tmparr['folder'] = $arr['folder'];
				$tmparr['dgname'] = isset($arr['dgname'])? $arr['dgname']: '';
				$tmparr['files'] = isset($arr['files'])? $arr['files']: 0;
				$tmparr['iIdDgOwner'] = isset($arr['iIdDgOwner'])? $arr['iIdDgOwner']: 0;
				
				$sFullPathName = BAB_FileManagerEnv::getCollectivePath($tmparr['iIdDgOwner']) . $arr['sRelativePath'] . $arr['folder'];
				
				$tmparr['diskspace'] = getDirSize($sFullPathName);
				$rr = $babDB->db_fetch_array($babDB->db_query("SELECT count( ffvt.id ) as total FROM bab_fm_filesver ffvt LEFT  JOIN bab_files ft ON ffvt.id_file = ft.id WHERE ft.id_owner =  '".$arr['id']."' AND ft.bgroup =  'Y'"));
				$tmparr['versions'] = isset($rr['total'])? $rr['total']: 0;
				$this->arrinfo[] = $tmparr;
				$this->nbfilest += $tmparr['files'];
				$this->nbfilesvt += $tmparr['versions'];
				$this->diskspacet += $tmparr['diskspace'];
				}
				
			$sUsersRootFmPath = $sRootFmPath . 'users/';
			$h = opendir($sUsersRootFmPath);
			$size = 0;
			while (($f = readdir($h)) != false)
				{
				if ($f != "." and $f != "..") 
					{
					if (is_dir($sUsersRootFmPath.$f))
						{
						if( $f{0} == 'U' && is_numeric(substr($f, 1)))
							{
							$size += getDirSize($sUsersRootFmPath.$f);
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

			$this->urlordfn = $GLOBALS['babUrlScript']."?tg=stat&idx=fm&order=".($col == 'folder'? $this->sortord: $order)."&col=folder";
			$this->urlorddg = $GLOBALS['babUrlScript']."?tg=stat&idx=fm&order=".($col == 'dgname'? $this->sortord: $order)."&col=dgname";
			$this->urlordds = $GLOBALS['babUrlScript']."?tg=stat&idx=fm&order=".($col == 'diskspace'? $this->sortord: $order)."&col=diskspace";
			$this->urlordf = $GLOBALS['babUrlScript']."?tg=stat&idx=fm&order=".($col == 'files'? $this->sortord: $order)."&col=files";
			$this->urlordfv = $GLOBALS['babUrlScript']."?tg=stat&idx=fm&order=".($col == 'versions'? $this->sortord: $order)."&col=versions";
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
					$this->url = $GLOBALS['babUrlScript']."?tg=stat&idx=sumdp&fid=".$this->fid;
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

	$temp = new summaryFileManagerCls($col, $order);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = $temp->fullname.$GLOBALS['exportchr'].$temp->dgtxt.$GLOBALS['exportchr'].$temp->diskspacetxt.$GLOBALS['exportchr'].$temp->filestxt.$GLOBALS['exportchr'].$temp->versionstxt."\n";
		while($temp->getnext())
			{
			$output .= $temp->urlname.$GLOBALS['exportchr'].$temp->dgname.$GLOBALS['exportchr'].$temp->diskspace.$GLOBALS['exportchr'].$temp->nbfiles.$GLOBALS['exportchr'].$temp->nbfilesv."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(	bab_printTemplate($temp, "statfile.html", "summaryfilesmanagerlist"));
		return $temp->count;
		}
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

			$this->users = false;
			if( $babBody->currentAdmGroup != 0 && $babBody->currentDGGroup['id_group'] != 0 )
				{
				$u = bab_getGroupsMembers($babBody->currentDGGroup['id_group']);
				for( $k=0; $k < count($u); $k++ )
					{
					$this->users[$u[$k]['id']] = true;
					}
				}

			}

		function getnext(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				list($key, $val) = each($this->arrinfo);
				if( $this->users && !isset($this->users[$key]))
					{
					$skip = true;
					$i++;
					return true;
					}

				$this->altbg = $this->altbg ? false : true;
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
	$babBodyPopup->babecho(bab_printTemplate($temp, "statfile.html", "personalfoldersdetail"));
}


function summaryFmDownloads($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryFmDownloadsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryFmDownloadsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("File");
			$this->hitstxt = bab_translate("Hits");
			$this->foldertxt = bab_translate("Folder");
			$this->pathtxt = bab_translate("Path");

			$req = "select ft.id, ft.name, fft.folder, ft.path, sum( sff.st_hits ) hits FROM ".BAB_STATS_FMFILES_TBL." sff left join ".BAB_FILES_TBL." ft on sff.st_fmfile_id=ft.id left join ".BAB_FM_FOLDERS_TBL." fft on fft.id=ft.id_owner where ft.bgroup='Y'";

			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and fft.id_dgowner='".$babBody->currentAdmGroup."'";
				}

			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sff.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sff.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sff.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by sff.st_fmfile_id order by hits desc";


			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			if( $this->total > BAB_STAT_MAX_ROWS)
				{
				$this->bnavigation = true;

				$prev = $pos - BAB_STAT_MAX_ROWS;
				if( $prev < 0)
					{
					$prev = 0;
					}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total)
					{
					$next = $pos;
					}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
				}
			else
				{
				$this->bnavigation = false;
				}

			$this->startnum = $pos+1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->totalhits = 0;
			$this->ptotalhits = 0;
			$this->arrinfo = array();
			$i = 0;
			while($arr = $babDB->db_fetch_array($res))
				{
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ) )
					{
					$tmparr = array();
					$tmparr['module'] = $arr['name'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['path'] = $arr['path'];
					$tmparr['folder'] = $arr['folder'];
					$tmparr['id'] = $arr['id'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=fmdown&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=fmdown&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			$this->urlordfolder = "idx=fmdown&order=".($col == 'folder'? $this->sortord: $order)."&col=folder&pos=".$pos;
			$this->urlordpath = "idx=fmdown&order=".($col == 'path'? $this->sortord: $order)."&col=path&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=fmdown&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=fmdown&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=fmdown&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=fmdown&order=".$order."&col=".$col."&pos=".$bottom;
				}
			$this->summaryBaseCls();
			}

		function isNumeric($col)
			{
			switch( $this->sortcol )
				{
				case 'hits':
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
				$this->modulename = $this->arrinfo[$i]['module'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->foldername = $this->arrinfo[$i]['folder'];
				$this->pathname = $this->arrinfo[$i]['path'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille= $this->totalhits > 0 ? (($this->nbhits*100)/$this->totalhits): 0;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sfmdown&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryFmDownloadsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Downloads");
		if( !empty($startday) && !empty($endday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		else if( !empty($startday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - )";
			}
		else if( !empty($endday))
			{
			$output .= " ( - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		$output .= " - ".bab_translate("Total: ").$temp->totalhits;
		$output .= "\n";
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->foldertxt.$GLOBALS['exportchr'].$temp->pathtxt.$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->foldername.$GLOBALS['exportchr'].$temp->pathname.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho( bab_printTemplate($temp, "statfile.html", "summarydownloadslist"));
		return $temp->count;
		}
	}

function summaryFmFolders($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryFmFoldersCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryFmFoldersCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Folders");
			$this->hitstxt = bab_translate("Hits");

			$req = "SELECT  fft.id, fft.folder, sum( sft.st_hits ) hits FROM  ".BAB_STATS_FMFOLDERS_TBL." sft left join ".BAB_FM_FOLDERS_TBL." fft  on sft.st_folder_id=fft.id  where fft.folder is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and fft.id_dgowner='".$babBody->currentAdmGroup."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sft.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sft.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sft.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by sft.st_folder_id order by hits desc";
			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			if( $this->total > BAB_STAT_MAX_ROWS)
				{
				$this->bnavigation = true;

				$prev = $pos - BAB_STAT_MAX_ROWS;
				if( $prev < 0)
					{
					$prev = 0;
					}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total)
					{
					$next = $pos;
					}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
				}
			else
				{
				$this->bnavigation = false;
				}

			$this->startnum = $pos+1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->totalhits = 0;
			$this->ptotalhits = 0;
			$this->arrinfo = array();
			$i = 0;
			while($arr = $babDB->db_fetch_array($res))
				{
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ) )
					{
					$tmparr = array();
					$tmparr['module'] = $arr['folder'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=fmfold&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=fmfold&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=fmfold&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=fmfold&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=fmfold&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=fmfold&order=".$order."&col=".$col."&pos=".$bottom;
				}
			$this->summaryBaseCls();
			}

		function isNumeric($col)
			{
			switch( $this->sortcol )
				{
				case 'hits':
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
				$this->modulename = $this->arrinfo[$i]['module'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sfmfold&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryFmFoldersCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Folders");
		if( !empty($startday) && !empty($endday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		else if( !empty($startday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - )";
			}
		else if( !empty($endday))
			{
			$output .= " ( - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		$output .= "\n";
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho( bab_printTemplate($temp, "statfile.html", "summaryfolderslist"));
		return $temp->count;
		}
	}


function showStatFmFolder($id, $date)
{
	global $babBodyPopup;
	class showStatFmFolderCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatFmFolderCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select fft.folder from ".BAB_FM_FOLDERS_TBL." fft where fft.id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sfmfold", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_FMFOLDERS_TBL." WHERE st_folder_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

			$this->dayinfo = array();
			$this->maxdayhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->dayinfo[$arr['day']] = $arr['hits'];
				if( $arr['hits'] > $this->maxdayhits )
					{
					$this->maxdayhits = $arr['hits'];
					}
				}


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_FMFOLDERS_TBL." WHERE st_folder_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
			$this->monthinfo = array();
			$this->maxmonthhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->monthinfo[$arr['month']] = $arr['hits'];
				if( $arr['hits'] > $this->maxmonthhits )
					{
					$this->maxmonthhits = $arr['hits'];
					}
				}

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_FMFOLDERS_TBL." WHERE st_folder_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
			$this->hourinfo = array();
			$this->maxhourhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->hourinfo[$arr['st_hour']] = $arr['hits'];
				if( $arr['hits'] > $this->maxhourhits )
					{
					$this->maxhourhits = $arr['hits'];
					}
				}

			}

		}
	$temp = new showStatFmFolderCls($id, $date);
	$babBodyPopup->babecho(bab_printTemplate($temp, "statfile.html", "summarydetail"));
}

function showStatFmDownloads($id, $date)
{
	global $babBodyPopup;
	class showStatFmDownloadsCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatFmDownloadsCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select ft.name from ".BAB_FILES_TBL." ft where ft.id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sfmdown", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_FMFILES_TBL." WHERE st_fmfile_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

			$this->dayinfo = array();
			$this->maxdayhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->dayinfo[$arr['day']] = $arr['hits'];
				if( $arr['hits'] > $this->maxdayhits )
					{
					$this->maxdayhits = $arr['hits'];
					}
				}


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_FMFILES_TBL." WHERE st_fmfile_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
			$this->monthinfo = array();
			$this->maxmonthhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->monthinfo[$arr['month']] = $arr['hits'];
				if( $arr['hits'] > $this->maxmonthhits )
					{
					$this->maxmonthhits = $arr['hits'];
					}
				}

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_FMFILES_TBL." WHERE st_fmfile_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
			$this->hourinfo = array();
			$this->maxhourhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->hourinfo[$arr['st_hour']] = $arr['hits'];
				if( $arr['hits'] > $this->maxhourhits )
					{
					$this->maxhourhits = $arr['hits'];
					}
				}

			}

		}
	$temp = new showStatFmDownloadsCls($id, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("File").": ".$babBodyPopup->title;
		$output .= "\n";
		$output .= bab_translate("Day").": ".$temp->daydate;
		$output .= "\n";
		$output .= bab_translate("Hour").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnexthour())
			{
			$output .= $temp->hour.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Month").": ".$temp->monthdate;
		$output .= "\n";
		$output .= bab_translate("Day").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextday())
			{
			$output .= $temp->day.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Year").": ".$temp->yeardate;
		$output .= "\n";
		$output .= bab_translate("Month").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextmonth())
			{
			$output .= $temp->monthname.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBodyPopup->babecho(bab_printTemplate($temp, "statfile.html", "summarydetail"));
		}
}





function displayFileTree($startDay, $endDay)
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
	$treeView = new bab_FileTreeView('file', 'N', '0');
	$treeView->addAttributes(BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION);
	$treeView->addStatistics($startDay, $endDay);
	$treeView->sort();
	$t = $treeView->printTemplate();
	$GLOBALS['babBody']->babecho($t);
}


?>