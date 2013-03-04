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
	 * @var string
	 */
	public $modification_comment;


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

		$this->getFromIdDraft($id_draft);

		if (!$this->log('lock'))
		{
			throw new Exception('log failed');
		}

		return $this;
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

		$this->getFromIdDraft($id_draft);

		return $this;
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
	 * Log article entry
	 *
	 * lock : draft creation
	 * unlock : cancel draft
	 * commit : commit draft to article
	 * accepted, refused : approbation
	 *
	 *
	 * @param	string	$action		lock|unlock|commit|accepted|refused
	 * @param 	string 	$comment
	 *
	 * @return bool
	 */
	public function log($action, $comment = null)
	{
		global $babDB;

		if (!$this->id_article)
		{
			return false;
		}

		if (null == $comment && 'commit' === $action)
		{
			$comment = $this->modification_comment;
		}

		$ordering = 0;
		$res = $babDB->db_query("SELECT ordering FROM bab_art_log WHERE id_article=".$babDB->quote($this->id_article));
		if ($lastlog = $babDB->db_fetch_assoc($res))
		{
			$ordering = (int) $lastlog['ordering'];
		}

		$ordering++;


		$babDB->db_query("
			insert into bab_art_log
			(
				id_article,
				id_author,
				date_log,
				action_log,
				art_log,
				ordering
			)
			values
			(
				".$babDB->quote($this->id_article).",
				".$babDB->quote($GLOBALS['BAB_SESS_USERID']).",
				now(),
				".$babDB->quote($action).",
				".$babDB->quote($comment).",
				".$babDB->quote($ordering)."
			)
		");

		return true;

	}



	/**
	 * Save Draft
	 * @return bool
	 */
	public function save()
	{
		global $babDB;
		require_once $GLOBALS['babInstallPath']."utilit/imgincl.php";
		
		$ar = array();
		$this->head = imagesReplace($this->head, $this->id."_draft_", $ar);
		$this->body = imagesReplace($this->body, $this->id."_draft_", $ar);
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
	 *
	 * @return unknown_type
	 */
	public function getImageUrl()
	{
		$image = bab_getImageDraftArticle($this->getId());
		if ($image)
		{
			if ($T = @bab_functionality::get('Thumbnailer'))
			{
				/*@var $T Func_Thumbnailer */
				$T->setSourceFile($GLOBALS['babUploadPath'].'/'.$image['relativePath'].$image['name']);
				return $T->getThumbnail(80, 80);
			}
		}

		return null;
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

		global $babDB;

		$res = $babDB->db_query('SELECT allow_addImg FROM bab_topics WHERE id='.$babDB->quote($this->id_topic));
		$top = $babDB->db_fetch_assoc($res);

		if ('N' === $top['allow_addImg'])
		{
			return $this;
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
				$returnRename = rename($filePickerItem->getFilePath()->toString(), $target->toString());
				if(!$returnRename){//needed on some windows environement
					$returnRename = copy($filePickerItem->getFilePath()->toString(), $target->toString());
					unlink($filePickerItem->getFilePath()->toString());
				}
				
				if ($returnRename)
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

		$res = $babDB->db_query('SELECT allow_attachments FROM bab_topics WHERE id='.$babDB->quote($this->id_topic));
		$top = $babDB->db_fetch_assoc($res);

		$filepicker = $W->FilePicker();
		/*@var $filepicker Widget_FilePicker */

		$filepicker->setEncodingMethod(null)->setName('articleFiles');

		$I = $filepicker->getTemporaryFiles('articleFiles');
		if (($I instanceOf Widget_FilePickerIterator) && !empty($files) && 'Y' === $top['allow_attachments'])
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
					// already in table, update sortkey and description

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



	/**
	 *
	 * @param array $tags <bab_Tag>
	 * @return unknown_type
	 */
	public function saveTags(Array $tags)
	{
		// copy tags to draft

		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
		/* @var $oReferenceMgr bab_ReferenceMgr */

		$oReferenceDraft = bab_Reference::makeReference('ovidentia', '', 'articles', 'draft', $this->id);

		$oReferenceMgr->removeByReference($oReferenceDraft);

		foreach($tags as $tag) {
			$oReferenceMgr->add($tag, $oReferenceDraft);
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
				$g[$id_group] = bab_abbr($name, BAB_ABBR_FULL_WORDS, 50);
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
	 * @param	int		$restriction
	 * @param 	array 	$groups
	 * @param	string	$operator
	 * @return unknown_type
	 */
	public function setRestriction($restriction, Array $groups, $operator)
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
		global $babBody, $babDB, $BAB_SESS_USERID;

		if( $this->id_topic == 0 )
		{
			return false;
		}



		if( $this->id_article != 0 )
		{
			$this->log('commit', (string) $this->modification_comment);

			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate, tt.auto_approbation
				from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$babDB->db_escape_string($this->id_article)."'
			");

			$rr = $babDB->db_fetch_array($res);
			if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
			{
			if( 2 === (int) $this->approbation )
				{
				$rr['saupdate'] = 0;
				}
			}
		}
		else
		{
			$res = $babDB->db_query("select tt.idsaart as saupdate, tt.auto_approbation from ".BAB_TOPICS_TBL." tt where tt.id='".$babDB->db_escape_string($this->id_topic)."'");
			$rr = $babDB->db_fetch_array($res);
		}


		$idfai = null;

		if( $rr['saupdate'] !=  0 )
		{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			if( $rr['auto_approbation'] == 'Y' )
			{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$this->getId(), $GLOBALS['BAB_SESS_USERID']); // Auto approbation
			}
			else
			{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$this->getId());
			}
		}

		if( $rr['saupdate'] == 0 || $idfai === true)
		{
			if( $this->id_article != 0 && true === $idfai)
			{

				$this->log('accepted', bab_translate('Accepted automatically at the first step of approbation schema'));
			}

			$this->id_article = acceptWaitingArticle($this->getId());
			if( $this->id_article == 0)
			{
				return false;
			}
			bab_deleteArticleDraft($this->getId());
		}
		else
		{
			if( !empty($idfai))
			{
				$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_WAIT."' , idfai='".$idfai."', date_submission=now() where id='".$babDB->db_escape_string($this->getId())."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleDraftApprovers($this->getId(), $nfusers);
			}
		}

		return true;
	}




	/**
	 * Delete draft
	 * @return bab_ArtDraft
	 */
	public function delete()
	{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		bab_deleteDraft($this->id);
		$this->log('unlock');
		$this->id = null;

		return $this;
	}





	public function isModifiable()
	{
		global $babDB;

		$res = $babDB->db_query("
			select
				adt.id_topic,
				adt.id_author,
				tt.allow_update,
				tt.allow_manupdate,
				adt.id_article

			FROM bab_art_drafts adt
				LEFT JOIN bab_topics tt ON adt.id_topic=tt.id

			where
				adt.id=".$babDB->quote($this->getId()));

		if( $res && $babDB->db_num_rows($res) == 1 )
		{
			$rr = $babDB->db_fetch_array($res);

			if ($rr['id_article'] !== '0' && bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic']))
			{
				// the topic is modifiable and the article exists
				return true;
			}

			if ($rr['id_article'] === '0' && bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $rr['id_topic']))
			{
				// can create a new article from this draft
				return true;
			}

			if( $rr['allow_update'] !== '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID'])
			{
				// i am the author
				return true;
			}

			if ( $rr['allow_manupdate'] !== '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic']))
			{
				// i am topic manager
				return true;
			}
		}

		return false;
	}
}