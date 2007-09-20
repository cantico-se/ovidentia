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



function displayTags()
{
	global $babBody;
	class displayTagsCls
		{

		function displayTagsCls()
			{
			global $babDB;

			$this->tags_txt = bab_translate("Tags to add ( Comma separated: tag1, tag2, etc )");
			$this->update_txt = bab_translate("Update");
			$this->add_txt = bab_translate("Add");
			$this->tag_txt = bab_translate("Tag to update ( empty = delete )");

			$this->res = $babDB->db_query("select * from ".BAB_TAGS_TBL." order by tag_name asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->tagsvalue= isset($GLOBALS['tagsvalue'])?$GLOBALS['tagsvalue']: '';
			$this->tagvalue= isset($GLOBALS['tagvalue'])?$GLOBALS['tagvalue']: '';
			$this->tagidvalue= isset($GLOBALS['tagidvalue'])?$GLOBALS['tagidvalue']: '';
			}

		function getnexttag()
			{
			global $babDB;
			static $k = 0;
			if( $k < $this->count )
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->tagname = $arr['tag_name'];
				$this->tagid = $arr['id'];
				if( isset($GLOBALS['lasttags']) && in_array($this->tagid, $GLOBALS['lasttags']) )
					{
					$this->big = true;
					}
				else
					{
					$this->big = false;
					}
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new displayTagsCls();
	$babBody->babecho(	bab_printTemplate($temp, "thesaurus.html", "tagsman"));
}


function importTagsFile()
	{
	global $babBody;
	class temp
		{
		var $import;
		var $name;
		var $id;
		var $separator;
		var $other;
		var $comma;
		var $tab;

		function temp()
			{
			$this->import = bab_translate("Import");
			$this->name = bab_translate("File");
			$this->separator = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"thesaurus.html", "tagsimpfile"));
	}



function mapTagsImportFile($file, $tmpfile, $wsepar, $separ)
	{
	global $babBody;
	class temp
		{
		var $res;
		var $count;
		var $db;
		var $id;

		function temp($pfile, $wsepar, $separ)
			{
			$this->helpfields = bab_translate("Choose the column");
			$this->process = bab_translate("Import");
			$this->ofieldname = bab_translate("Column");

			$this->pfile = $pfile;

			switch($wsepar)
				{
				case "1":
					$separ = ",";
					break;
				case "2":
					$separ = "\t";
					break;
				default:
					if( empty($separ))
						$separ = ",";
					break;
				}
			$fd = fopen($pfile, "r");
			$this->arr = fgetcsv( $fd, 4096, $separ);
			fclose($fd);
			$this->separ = $separ;
			$this->count = count($this->arr);
			}

		function getnextval()
			{
			static $i = 0;
			static $k = 0;
			if( $i < $this->count)
				{
				$this->ffieldid = $i;
				$this->ffieldname = $this->arr[$i];
				$i++;
				return true;
				}
			else
				{
				$k++;
				$i = 0;
				return false;
				}
			}

		}

	include_once $GLOBALS['babInstallPath']."utilit/tempfile.php";
	$tmpdir = get_cfg_var('upload_tmp_dir');
	if( empty($tmpdir))
		$tmpdir = session_save_path();

	$tf = new babTempFiles($tmpdir);
	$nf = $tf->tempfile($tmpfile, $file);
	if( empty($nf))
		{
		$babBody->msgerror = bab_translate("Cannot create temporary file");
		return;
		}
	$temp = new temp($nf, $wsepar, $separ);
	$babBody->babecho(	bab_printTemplate($temp,"thesaurus.html", "tagsmapfile"));
	}




function updateTags()
{
	global $babBody, $babDB;
	$GLOBALS['tagsvalue'] = '';
	$GLOBALS['tagvalue'] = '';
	$GLOBALS['tagidvalue'] = 0;

	$tags = trim(bab_rp('tagsname', ''));
	if( !empty($tags))
	{
		$arr = explode(',', $tags);
		for( $k = 0; $k < count($arr); $k++ )
		{
			$tag = trim($arr[$k]);
			if( !empty($tag) )
			{
				$res = $babDB->db_query("select * from ".BAB_TAGS_TBL." where tag_name='".$babDB->db_escape_string($tag)."'");
				if( !$res || $babDB->db_num_rows($res) == 0 )
				{
					$babDB->db_query("insert into ".BAB_TAGS_TBL." (tag_name) values ('".$babDB->db_escape_string($tag)."')");
					$GLOBALS['lasttags'][] = $babDB->db_insert_id();
				}
			}
		}
	}

	$tag = trim(bab_rp('tagname', ''));
	$tagid = trim(bab_rp('tagid', 0));

	if( !empty($tag) && $tagid )
	{
		$res = $babDB->db_query("select * from ".BAB_TAGS_TBL." where id !='".$babDB->db_escape_string($tagid)."' and tag_name='".$babDB->db_escape_string($tag)."'");
		if( !$res || $babDB->db_num_rows($res) == 0 )
		{
			$babDB->db_query("update ".BAB_TAGS_TBL." set tag_name='".$babDB->db_escape_string($tag)."' where id='".$babDB->db_escape_string($tagid)."'");
			$GLOBALS['lasttags'][] = $tagid;
		}
	}
	elseif( $tagid )
	{
		$babDB->db_query("delete from ".BAB_TAGS_TBL." where id='".$babDB->db_escape_string($tagid)."'");
	}
	else
	{
		$GLOBALS['tagvalue'] = $tag;
		$GLOBALS['tagidvalue'] = $tagid;
	}
}


function processImportTagsFile()
{
	global $babBody, $babDB;

	$pfile = bab_rp('pfile', '');
	$separ = bab_rp('separ', ';');
	$tagcol = bab_rp('tagcol', '');
	$fd = fopen($pfile, "r");
	if( $fd )
		{
		$arr = fgetcsv($fd, 4096, $separ);

		while ($arr = fgetcsv($fd, 4096, $separ))
			{
				$tag = trim($arr[$tagcol]);
				if( !empty($tag) )
				{
					$res = $babDB->db_query("select * from ".BAB_TAGS_TBL." where tag_name='".$babDB->db_escape_string($tag)."'");
					if( !$res || $babDB->db_num_rows($res) == 0 )
					{
						$babDB->db_query("insert into ".BAB_TAGS_TBL." (tag_name) values ('".$babDB->db_escape_string($tag)."')");
						$GLOBALS['lasttags'][] = $babDB->db_insert_id();
					}
				}
			}
		}
}

/* main */
if( !bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1) )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'tagsman');


if( isset($updtags) )
	{
		if( $updtags == 'updtags' )
		{
			updateTags();
		}
		elseif( $updtags == 'imptags' )
		{
			processImportTagsFile();
		}
	}


switch($idx)
	{

	case "tagsimp":
		$babBody->title = bab_translate("Tags import");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("tagsimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		importTagsFile();
		break;

	case "impmap":
		$babBody->title = bab_translate("Tags import");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("impmap", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		mapTagsImportFile($uploadf_name, $uploadf, $wsepar, $separ);
		break;


	case "tagsman":
	default:
		$babBody->title = bab_translate("Tags management");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("tagsimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		displayTags();
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>