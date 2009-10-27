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
require_once dirname(__FILE__) . '/utilit/tagApi.php';


function displayTags()
{
	global $babBody;
	class displayTagsCls
		{
		var $oTagIterator = null;
		function displayTagsCls()
			{
			global $babDB;

			$this->tags_txt		= bab_translate("Tags to add ( Comma separated: tag1, tag2, etc )");
			$this->update_txt	= bab_translate("Update");
			$this->add_txt		= bab_translate("Add");
			$this->tag_txt		= bab_translate("Tag to update ( empty = delete )");
			
			$oTagMgr = bab_getInstance('bab_TagMgr');
			$this->oTagIterator = $oTagMgr->select()->orderAsc('tag_name');
			
			$this->tagsvalue	= isset($GLOBALS['tagsvalue'])?$GLOBALS['tagsvalue']: '';
			$this->tagvalue		= isset($GLOBALS['tagvalue'])?$GLOBALS['tagvalue']: '';
			$this->tagidvalue	= isset($GLOBALS['tagidvalue'])?$GLOBALS['tagidvalue']: '';
			}

		function getnexttag()
			{
			$this->oTagIterator->next();
			if($this->oTagIterator->valid())
				{
				$oTag			= $this->oTagIterator->current();
				$this->tagname	= $oTag->getName();
				$this->tagid	= $oTag->getId();
				if(isset($GLOBALS['lasttags']) && in_array($this->tagid, $GLOBALS['lasttags']))
					{
					$this->big = true;
					}
				else
					{
					$this->big = false;
					}
				
				return true;
				}
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
			$this->maxfilesize = $GLOBALS['babMaxFileSize'];
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

function outPutTagsToJson()
{
	$like		= bab_rp('like', '');
	$ret		= array();
	$oName		= new BAB_Field('tag_name');
	$oTagMgr	= bab_getInstance('bab_TagMgr');
	
	$oTagIterator = $oTagMgr->select();
	$oTagIterator->setCriteria($oName->contain($like))->orderAsc('tag_name');
	
	while($oTagIterator->next())
	{
		if($oTagIterator->valid())
		{
			$oTag = $oTagIterator->current();
			$ret[] = '{"id": "'.$oTag->getId().'", "tagname": "'.bab_toHtml($oTag->getName()).'"}';	
		}
	}
	
	print '['.join(',', $ret).']';
}

function updateTags()
{
	global $babBody, $babDB;
	$GLOBALS['tagsvalue'] = '';
	$GLOBALS['tagvalue'] = '';
	$GLOBALS['tagidvalue'] = 0;

	$oTagMgr = bab_getInstance('bab_TagMgr'); 
	
	$tags = trim(bab_rp('tagsname', ''));
	if( !empty($tags))
	{
		$arr = explode(',', $tags);
		for( $k = 0; $k < count($arr); $k++ )
		{
			$tag = trim($arr[$k]);
			if( !empty($tag) )
			{
				$iId = $oTagMgr->create($tag);
				if(false !== $iId)
				{
					$GLOBALS['lasttags'][] = $iId;
				}
			}
		}
	}

	$tag = trim(bab_rp('tagname', ''));
	$tagid = trim(bab_rp('tagid', 0));

	if( !empty($tag) && $tagid )
	{
		if(false !== $oTagMgr->update($tagid, $tag))
		{
			$GLOBALS['lasttags'][] = $tagid;
		}
	}
	elseif( $tagid )
	{
		$oTagMgr->delete($tagid);
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
		$oTagMgr = bab_getInstance('bab_TagMgr'); 
		
		while ($arr = fgetcsv($fd, 4096, $separ))
			{
				$tag = trim($arr[$tagcol]);
				if( !empty($tag) )
				{
					$iId = $oTagMgr->create($tag);
					if(false !== $iId)
					{
						$GLOBALS['lasttags'][] = $iId;
					}
				}
			}
		}
}


/* main */
$idx = bab_rp('idx', 'tagsman');


$updtags = bab_rp('updtags', '');
if( $updtags )
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
	case 'tagsjson':
		outPutTagsToJson();
		exit;
		break;

	case "tagsimp":
		if( bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1) )
		{
		$babBody->title = bab_translate("Tags import");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("tagsimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		importTagsFile();
		}
		else
		{
		$babBody->msgerror = bab_translate("Access denied");
		}
		break;

	case "impmap":
		if( bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1) )
		{
		$babBody->title = bab_translate("Tags import");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("impmap", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		$wsepar = bab_rp('wsepar', 0);
		$separ = bab_rp('separ', ';');
		mapTagsImportFile($_FILES['uploadf']['name'], $_FILES['uploadf']['tmp_name'], $wsepar, $separ);
		}
		else
		{
		$babBody->msgerror = bab_translate("Access denied");
		}
		break;


	case "tagsman":
	default:
		if( bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1) )
		{
		$babBody->title = bab_translate("Tags management");
		$babBody->addItemMenu("tagsman", bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsman");
		$babBody->addItemMenu("tagsimp", bab_translate("Import"), $GLOBALS['babUrlScript']."?tg=thesaurus&idx=tagsimp");
		displayTags();
		}
		else
		{
		$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserThesaurus');
?>
