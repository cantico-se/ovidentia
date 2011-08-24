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

require_once 'base.php';
require_once dirname(__FILE__) . '/artapi.php';
require_once dirname(__FILE__) . '/artincl.php';


/**
 * Article draft
 */
class bab_ArtDraft
{
	/**
	 * if id != null, draft is in database
	 * @var int
	 */
	private $id = null;
	
	/**
	 * @var int
	 */
	public $id_author;
	
	
	public $date_creation			= '0000-00-00 00:00:00';
	public $date_modification		= '0000-00-00 00:00:00';
	public $date_submission			= '0000-00-00 00:00:00';
	public $date_publication		= '0000-00-00 00:00:00';
	public $date_archiving			= '0000-00-00 00:00:00';
	
	/**
	 * @var string
	 */
	public $title;
	public $head;
	public $head_format = 'html';
	public $body;
	public $body_format = 'html';
	
	/**
	 * @var string
	 */
	public $lang;
	
	/**
	 * @var string Y | N
	 */
	public $trash;
	
	/**
	 * @var int
	 */
	public $id_topic;
	
	/**
	 * @var int
	 */
	public $id_article;
	
	/**
	 * 
	 * @var string
	 */
	public $restriction;
	
	/**
	 * @var string Y | N
	 */
	public $hpage_private;
	
	/**
	 * @var string Y | N
	 */
	public $hpage_public;
	
	/**
	 * @var string Y | N
	 */
	public $notify_members;
	
	/**
	 * Update de modification date of article on draft submit
	 * @var string Y | N
	 */
	public $update_datemodif;
	
	/**
	 * Approbation instance
	 * @var int
	 */
	public $idfai;
	
	/**
	 * 
	 */
	public $result;
	
	
	/**
	 * @var unknown_type
	 */
	public $id_anonymous;
	
	/**
	 * @var int		1 | 2 | 3
	 */
	public $approbation;
	
	
	/**
	 * 
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	
	/**
	 * Get draft as array
	 * @return array
	 */
	public function getValues()
	{
		$arr = get_object_vars($this);
		
		if(0 == $this->id_article) {
			
			if (null !== $template = $this->getTopicTemplate())
			{
				if (empty($arr['head']))
				{
					$arr['head'] = $template['head'];
				}
				
				if (empty($arr['body']))
				{
					$arr['body'] = $template['body'];
				}
			}
		}

		return $arr;
	}
	
	
	/**
	 * Fill the class properties with database informations
	 * @param int $id
	 * @return bab_ArtDraft
	 */
	public function getFromIdDraft($id_draft)
	{
		$arr = bab_getDraftArticleArray($id_draft);
		
		if (empty($arr))
		{
			throw new ErrorException(bab_translate('This draft does not exists'));
			return;
		}
		

		foreach($arr as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->$property = $value;
				
			}
		}
		
		return $this;
	}
	
	
	
	
	/**
	 * Create a draft in database from an article, and fill class properties with draft
	 * access rights verifications will be tested from article topic
	 * 
	 * @throws ErrorException
	 * 
	 * @param int $id_article
	 * @return bab_ArtDraft
	 */
	public function getFromIdArticle($id_article)
	{
		$id_draft = bab_newArticleDraft(0, $id_article);
		
		return $this->getFromIdDraft($id_draft);
	}
	
	
	
	/**
	 * Create an empty draft in database and link it to a topic
	 * access rights verifications will be tested from topic
	 * 
	 * @throws ErrorException
	 * 
	 * @param int $id_topic
	 * @return bab_ArtDraft
	 */
	public function createInTopic($id_topic)
	{
		$id_draft = bab_newArticleDraft($id_topic, 0);
		
		return $this->getFromIdDraft($id_draft);
	}
	
	
	
	/**
	 * Import datetime from user input
	 * @param string $field
	 * @param string $date		typed date format
	 * @param string $time		ISO time
	 * @return bab_ArtDraft
	 */
	public function importDate($field, $date, $time)
	{
		$dt = BAB_DateTime::fromUserInput($date);
		if($dt != null){
			$dt->setIsoTime($time);
			$this->$field = $dt->getIsoDateTime();
		} else {
			$this->$field = '0000-00-00 00:00:00';
		}
		
		return $this;
	}
	
	
	/**
	 * Save Draft
	 * @return bool
	 */
	public function save()
	{
		global $babDB;
		
		$values = $this->getValues();
		
		if (null === $this->id)
		{
			throw new Exception('Disabled');
			/*
			$babDB->db_query('INSERT INTO bab_art_drafts ('.implode(',', array_keys($values)).') VALUES ('.$babDB->quote($values).')');
			$this->id = $babDB->db_insert_id();
			*/
			
		} else {
			
			$tmp = array();
		
			$query = 'UPDATE bab_art_drafts SET ';
			foreach($values as $field => $value)
			{
				$tmp[] = $field.'='.$babDB->quote($value);
			}
			
			$query .= implode(', ', $tmp);
			
			$query .= ' WHERE id='.$babDB->quote($this->id);
			
			$babDB->db_query($query);
		
		}
		
		return true;
	}
	
	
	/**
	 * Copy draft image to file picker temporary folder
	 * @param Widget_FilePicker $filePicker
	 * @return bab_ArtDraft
	 */
	public function loadTempPicture(Widget_FilePicker $filePicker)
	{
		if (null === $this->id)
		{
			throw new ErrorException('missing draft data');
		}

		$image = bab_getImageDraftArticle($this->id);
		
		if (false === $image)
		{
			return $this;
		}
		
		$source = new bab_path($GLOBALS['babUploadPath'], $image['relativePath'], $image['name']);

		$filePicker->importFile($source, bab_Charset::getIso());
		
		return $this;
	}
	
	
	
	/**
	 * Copy attachments to file picker temporary folder
	 * @param Widget_FilePicker $filePicker
	 * @return bab_ArtDraft
	 */
	public function loadTempAttachments(Widget_FilePicker $filePicker)
	{
		if (null === $this->id)
		{
			throw new ErrorException('missing draft data');
		}
		
		global $babDB;
		
		$draftPath = new bab_path($GLOBALS['babUploadPath'], 'drafts');
		// les fichiers actuel sont enregistres dans le repertoire draft avec id,fichier
		
		
		$targetPath = $filePicker->getFolder();
		if ($targetPath->isDir())
		{
			$targetPath->deleteDir();
		}
		$targetPath->createDir();
		
		$res = $babDB->db_query("SELECT name, description, ordering FROM bab_art_drafts_files WHERE id_draft=".$babDB->quote($this->id));
		while ($arr = $babDB->db_fetch_assoc($res))
		{
			
			$filePath = clone $draftPath;
			$filePath->push($this->id.','.$arr['name']);
			
			
			$target = clone $targetPath;
			$target->push(Widget_FilePicker::NONE.$arr['name']);
			
			copy($filePath->toString(), $target->toString());
			
			$_SESSION['bab_articleTempAttachments'][$arr['name']] = array(
				'description' 	=> $arr['description'],
				'ordering'		=> $arr['ordering']
			);
		}
		
		
		return $this;
	}
	
	
	/**
	 * Save temporary picture to draft
	 * @return bab_ArtDraft
	 */
	public function saveTempPicture()
	{
		$currentImage = bab_getImageDraftArticle($this->id);
		if (false !== $currentImage)
		{
			bab_deleteImageDraftArticle($this->id);
		}
		
		
		$W = bab_Widgets();

		$filepicker = $W->FilePicker();
		/*@var $filepicker Widget_FilePicker */
		$filepicker->setEncodingMethod(null)->setName('articlePicture');
		$I = $filepicker->getTemporaryFiles('articlePicture');
		
		if ($I instanceOf Widget_FilePickerIterator)
		{
		
			require_once dirname(__FILE__).'/artincl.php';
			require_once dirname(__FILE__).'/path.class.php';
	
			$oPubPathEnv = bab_getInstance('bab_PublicationPathsEnv');
			/*@var $oPubPathEnv bab_PublicationPathsEnv */
			if(false === $oPubPathEnv->setEnv(0))
			{
				throw new Exception('Unexpected error');
			}
	
			$targetPath = new bab_path($oPubPathEnv->getDraftArticleImgPath($this->id));
			
			
			$targetPath->createDir();
			foreach($I as $filePickerItem)
			{
				/*@var $filePickerItem Widget_FilePickerItem */
				
				$target = clone $targetPath;
				$target->push($filePickerItem->toString());
				
				if (rename($filePickerItem->getFilePath()->toString(), $target->toString()))
				{
					$sRelativePath = mb_substr($targetPath->toString(), 1 + mb_strlen($GLOBALS['babUploadPath']));
					bab_addImageToDraftArticle($this->id, $filePickerItem->toString(), $sRelativePath.'/');
					
					return $this;
				}
				
				
			}
			
			
			// remove temporary directory
			
			$tmpPath = $filepicker->getFolder();
			/*@var $tmpPath bab_Path */
			$tmpPath->deleteDir();
		}
		
		return $this;
	}
	
	
	
	
	/**
	 * Save temporary attachmements to draft
	 * @param	Array		$files		posted files description and ordering
	 * @return bab_ArtDraft
	 */
	public function saveTempAttachments(Array $files)
	{
		$targetPath = new bab_path($GLOBALS['babUploadPath'], 'drafts');

		global $babDB;
		
		$tablefiles = array();
		
		$res = $babDB->db_query("SELECT id, name FROM bab_art_drafts_files WHERE id_draft=".$babDB->quote($this->id));
		while ($arr = $babDB->db_fetch_assoc($res))
		{
			$tablefiles[$arr['name']] = $arr['id'];
		}
		
		$sortkeys = array_flip(array_keys($files));
		
		$W = bab_Widgets();

		$filepicker = $W->FilePicker();
		/*@var $filepicker Widget_FilePicker */
		
		$filepicker->setEncodingMethod(null)->setName('articleFiles');
	
		$I = $filepicker->getTemporaryFiles('articleFiles');
		if (($I instanceOf Widget_FilePickerIterator) && !empty($files))
		{
			$targetPath->createDir();
			foreach($I as $filePickerItem)
			{
				/*@var $filePickerItem Widget_FilePickerItem */
				
				$fname = $filePickerItem->getFileName();
				$target = clone $targetPath;
				$target->push($this->id.','.$filePickerItem->toString());
				if (isset($tablefiles[$fname]))
				{
					// allready in table, update sortkey and description
					
					unlink($target->toString());
					unset($tablefiles[$fname]);
					
					$babDB->db_query('UPDATE 
							bab_art_drafts_files 				
						SET 
							description='.$babDB->quote($files[$fname]).',
							ordering='.$babDB->quote($sortkeys[$fname] +1).' 
						
						WHERE name='.$babDB->quote($filePickerItem->toString()).' AND id_draft='.$babDB->quote($this->id).' 
					');
					
				} else {
					// add to table
					
					$babDB->db_query('INSERT INTO bab_art_drafts_files (id_draft, name, description, ordering) VALUES 
						(
							'.$babDB->quote($this->id).', 
							'.$babDB->quote($filePickerItem->toString()).',
							'.$babDB->quote($files[$fname]).',
							'.$babDB->quote($sortkeys[$fname] +1).'
						) 
					');
				}
				
				rename($filePickerItem->getFilePath()->toString(), $target->toString());
				
				
			}
			
			$babDB->db_query('DELETE FROM bab_art_drafts_files 
						WHERE id IN('.$babDB->quote($tablefiles).') AND id_draft='.$babDB->quote($this->id));
			
			
			// remove temporary directory
			
			$tmpPath = $filepicker->getFolder();
			/*@var $tmpPath bab_Path */
			$tmpPath->deleteDir();
			
			unset($_SESSION['bab_articleTempAttachments']);
			
		} else {
			
			foreach($tablefiles as $name => $id)
			{
				$target = clone $targetPath;
				$target->push($this->id.','.$name);
				unlink($target->toString());
			}
			
			$babDB->db_query('DELETE FROM bab_art_drafts_files 
						WHERE id_draft='.$babDB->quote($this->id));
		}
		
		return $this;
	}
	
	
	/**
	 * get tags as array
	 * @return array
	 */
	public function getTags()
	{
		require_once dirname(__FILE__) . '/tagApi.php';
	
		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
	
		$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $this->id));
		$oIterator->orderAsc('tag_name');
		
		$tags = array();
		
		foreach($oIterator as $oTag) {
			$tags[] = $oTag->getName();
		}
		
		return $tags;
	}
	
	
	
	
	public function saveTags($tags)
	{
		$arr = explode(',', $tags);
		
		// copy tags to draft
			
		require_once dirname(__FILE__) . '/tagApi.php';
	
		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
		/* @var $oReferenceMgr bab_ReferenceMgr */
		
		$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $this->id);
		
		$oReferenceMgr->removeByReference($oReferenceDraft);
		
		foreach($arr as $tagname) {
			$tagname = trim($tagname);
			$oReferenceMgr->add($tagname, $oReferenceDraft);
		}
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getRestrictionsOptions()
	{
		$g = array();
			
		require_once dirname(__FILE__).'/../admin/acl.php';
		$groups = aclGetAccessGroups(BAB_TOPICSVIEW_GROUPS_TBL, $this->id_topic);
		
		foreach($groups as $id_group)
		{
			if ($id_group < BAB_ADMINISTRATOR_GROUP)
			{
				continue;
			}
			
			$name = bab_getGroupName($id_group, false);
			if ($name)
			{
				$g[$id_group] = $name;
			}
		}
		
		return $g;
	}
	
	
	/**
	 * 
	 * @return array
	 */
	public function getRestrictions()
	{
		if ('' === $this->restriction || null === $this->restriction)
		{
			return array();
		}
		
		if( strchr($this->restriction, "&"))
		{
			$arr = explode('&', $this->restriction);
		}
		else if( strchr($this->restriction, ","))
		{
			$arr = explode(',', $this->restriction);
		}
		else
		{
			$arr = array($this->restriction);
		}
		
		return $arr;
	}
	
	/**
	 * operator value
	 * @return string
	 */
	public function getOperator()
	{
		if (strchr($this->restriction, "&"))
		{
			return '&';
		}
		
		return ',';
	}
	
	/**
	 * 
	 * @param array $groups
	 * @return unknown_type
	 */
	public function setRestriction($restriction, $groups, $operator)
	{
		if (!$restriction)
		{
			$this->restriction = '';
			return;
		}
		
		$this->restriction = implode($operator, $groups);
	}
	
	
	
	
	/**
	 * 
	 * @return array
	 */
	public function getTopicTemplate()
	{
		$values = array('head' => '', 'body' => '');
		
		if (!$this->id_topic)
		{
			return $values;
		}
		
		global $babDB;
		
		$res = $babDB->db_query("select article_tmpl from bab_topics  where id=".$babDB->quote($this->id_topic));
		if( !$res || $babDB->db_num_rows($res) !== 1 ) {
			return $values;
		}
		
	
		$topic = $babDB->db_fetch_array($res);
		$template = $topic['article_tmpl'];
		
		if (empty($template))
		{
			return $values;
		}
		
		return bab_getTopicTemplate($template, $this->head_format, $this->body_format);
	}
	
	
	
	
	
	/**
	 * Submit draft as article
	 * create article and delete draft or send notification to approver
	 * @return bool
	 */
	public function submit()
	{
		return bab_submitArticleDraft($this->id);
	}
	
	
	
	
	/**
	 * Delete draft
	 * @return bab_ArtDraft
	 */
	public function delete()
	{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		bab_deleteDraft($this->id);
		$this->id = null;
		
		return $this;
	}
}