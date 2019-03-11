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


class Func_Ovml_Container_Folders extends Func_Ovml_Container
{

    public $index = 0;

    public $count = 0;

    public $IdEntries = array();

    public $oFmFolderSet = null;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $folderid = $ctx->curctx->getAttribute('folderid');
        $iIdDelegation = (int) $ctx->curctx->getAttribute('delegationid');

        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
        $this->oFmFolderSet = new BAB_FmFolderSet();

        $oIdDgOwner = $this->oFmFolderSet->aField['iIdDgOwner'];
        $oActive = $this->oFmFolderSet->aField['sActive'];
        $oId = $this->oFmFolderSet->aField['iId'];
        $oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];

        $oCriteria = $oActive->in('Y');
        $oCriteria = $oCriteria->_and($oRelativePath->in(''));

        if (0 !== $iIdDelegation) {
            $oCriteria = $oCriteria->_and($oIdDgOwner->in($iIdDelegation));
        }

        if (false !== $folderid && '' !== $folderid) {
            $oCriteria = $oCriteria->_and($oId->in(explode(',', $folderid)));
        }

        $this->oFmFolderSet->select($oCriteria);

        while (null !== ($oFmFolder = $this->oFmFolderSet->next())) {
            if (bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId())) {
                array_push($this->IdEntries, $oFmFolder->getId());
            }
        }
        $this->oFmFolderSet->select($oId->in($this->IdEntries), array(
            'sName' => 'ASC'
        ));
        $this->count = $this->oFmFolderSet->count();
        $this->ctx->curctx->push('CCount', $this->oFmFolderSet->count());
    }


    public function getnext()
    {
        static $iIndex = 0;

        if (null !== ($oFmFolder = $this->oFmFolderSet->next())) {
            $this->ctx->curctx->push('CIndex', $iIndex);
            $this->ctx->curctx->push('FolderName', $oFmFolder->getName());
            $this->ctx->curctx->push('FolderId', $oFmFolder->getId());
            $this->ctx->curctx->push('FolderDelegationId', $oFmFolder->getDelegationOwnerId());
            $this->ctx->curctx->push('FolderPath', $oFmFolder->getRelativePath());
            $this->ctx->curctx->push('FolderPathname', $oFmFolder->getName());
            $url = $GLOBALS['babUrl'] . '?tg=fileman&idx=list&id=' . $oFmFolder->getId() . '&gr=Y&path=' . $oFmFolder->getName();
            $this->ctx->curctx->push('FolderBrowseUrl', $url);
            $iIndex ++;
            $this->index = $iIndex;
            return true;
        } else {
            $this->oFmFolderSet->reset();
            $this->index = $iIndex = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_Folder extends Func_Ovml_Container
{
    public $index;

    public $count;

    public $oFmFolderSet = null;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $folderid = (int) $ctx->curctx->getAttribute('folderid');
        $this->count = 0;

        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
        $this->oFmFolderSet = new BAB_FmFolderSet();
        $oId = $this->oFmFolderSet->aField['iId'];

        if (0 !== $folderid && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folderid)) {
            $this->oFmFolderSet->select($oId->in($folderid));
            $this->count = $this->oFmFolderSet->count();
            $this->ctx->curctx->push('CCount', $this->count);
        } else {
            $this->ctx->curctx->push('CCount', 0);
        }
    }


    public function getnext()
    {
        static $iIndex = 0;

        if (0 != $this->oFmFolderSet->count() && null !== ($oFmFolder = $this->oFmFolderSet->next())) {
            $path = $oFmFolder->getRelativePath();
            $name = $oFmFolder->getName();
            $pathname = $path . $name;
            $this->ctx->curctx->push('CIndex', $iIndex);
            $this->ctx->curctx->push('FolderName', $name);
            $this->ctx->curctx->push('FolderId', $oFmFolder->getId());
            $this->ctx->curctx->push('FolderDelegationId', $oFmFolder->getDelegationOwnerId());
            $this->ctx->curctx->push('FolderPath', $path);
            $this->ctx->curctx->push('FolderPathname', $pathname);
            $url = $GLOBALS['babUrl'] . '?tg=fileman&idx=list&id=' . $oFmFolder->getId() . '&gr=Y&path=' . $pathname;
            $this->ctx->curctx->push('FolderBrowseUrl', $url);

            $iIndex ++;
            $this->index = $iIndex;
            return true;
        } else {
            $iIndex = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_FolderPrevious extends Func_Ovml_Container_Folder
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Folder::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Folders');

        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('folderid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        $this->bab_Folder($ctx);
    }
}



class Func_Ovml_Container_FolderNext extends Func_Ovml_Container_Folder
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Folder::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Folders');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('folderid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        $this->bab_Folder($ctx);
    }
}



class Func_Ovml_Container_SubFolders extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $oFmFolderSet = null;

    public $rootFolderPath;

    public $folderId;

    public $path;

    private $oFmFolder;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $folderid = (int) $ctx->curctx->getAttribute('folderid');
        $this->folderId = $folderid;
        $this->count = 0;

        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

        $sPath = (string) $ctx->curctx->getAttribute('path');
        $this->path = $sPath;

        $this->oFmFolderSet = new BAB_FmFolderSet();
        $oId = $this->oFmFolderSet->aField['iId'];

        if (0 !== $folderid) {
            $oFmFolder = $this->oFmFolderSet->get($oId->in($folderid));
            $this->oFmFolder = $oFmFolder;

            if (! is_null($oFmFolder)) {
                $iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
                $sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();

                $this->rootFolderPath = $sRelativePath;
                // bab_debug('sRelativePath ==> ' . $sRelativePath .
                // ' sRootFolderName ==> ' . getFirstPath($sRelativePath));

                $sRootFolderName = getFirstPath($sRelativePath);
                if ($this->accessValid($sRootFolderName, $oFmFolder->getName() . '/' . $sPath)) {
                    $sRelativePath = $sRootFolderName . '/' . $sPath . '/';

                    // $oFileManagerEnv =& getEnvObject();
                    $sUploadPath = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());

                    $sFullPathName = realpath($sUploadPath . $sRelativePath);

                    $this->walkDirectory($sFullPathName);

                    $this->count = count($this->IdEntries);
                    $order = $ctx->curctx->getAttribute('order');
                    if ($order === false || $order === '') {
                        $order = 'asc';
                    }

                    switch (mb_strtolower($order)) {
                        case 'desc':
                            bab_Sort::sort($this->IdEntries, bab_sort::CASE_INSENSITIVE);
                            $this->IdEntries = array_reverse($this->IdEntries);
                            break;
                        default:
                            bab_Sort::sort($this->IdEntries, bab_sort::CASE_INSENSITIVE);
                            break;
                    }
                }
            }
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('SubFolderName', $this->IdEntries[$this->idx]);
            $this->ctx->curctx->push('SubFolderPath', $this->path);
            $this->ctx->curctx->push('SubFolderPathname', $this->path . (empty($this->path) ? '' : '/') . $this->IdEntries[$this->idx]);
            $url = $GLOBALS['babUrl'] . '?tg=fileman&idx=list&id=' . $this->folderId . '&gr=Y&path=' . $this->rootFolderPath . (empty($this->path) ? '' : '/' . $this->path);
            $this->ctx->curctx->push('SubFolderBrowseUrl', $url);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }


    public function accessValid($sName, $sPath)
    {
        $oName = $this->oFmFolderSet->aField['sName'];
        $oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];

        $oCriteria = $oName->in($sName);
        $oCriteria = $oCriteria->_and($oRelativePath->in(''));

        // Get the root folder
        $oFmFolder = $this->oFmFolderSet->get($oCriteria);
        if (! is_null($oFmFolder)) {
            $iIdOwner = 0;
            $sRelativePath = '';

            BAB_FmFolderHelper::getFileInfoForCollectiveDir($oFmFolder->getId(), $sPath, $iIdOwner, $sRelativePath, $oFmFolder);


            return bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $iIdOwner);
        }
        return false;
    }


    public function walkDirectory($sFullPathName)
    {
        // bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sFullPathName);
        if (is_dir($sFullPathName)) {
            $oDir = dir($sFullPathName);
            while (false !== $sEntry = $oDir->read()) {
                // Skip pointers
                if ($sEntry == '.' || $sEntry == '..' || $sEntry == BAB_FVERSION_FOLDER) {
                    continue;
                }

                if (is_dir($sFullPathName . '/' . $sEntry) && $this->accessValid(getFirstPath($this->rootFolderPath), $this->oFmFolder->getName() . '/' . $this->path . '/' . $sEntry)) {
                    $this->IdEntries[] = $sEntry;
                }
            }
            $oDir->close();
        }
    }
}



class Func_Ovml_Container_Files extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $res;

    public $index;

    public $count;

    public $tags = array();

    public $oFmFolderSet = null;

    public $oFolderFileSet = null;

    public $iIdRootFolder = 0;

    public $sPath = '';

    public $sEncodedPath = '';

    public $iIdDelegation = 0;

    public $imageheightmax;

    public $imagewidthmax;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $folderid = (int) $ctx->curctx->getAttribute('folderid');
        $this->sPath = (string) $ctx->curctx->getAttribute('path');
        $iLength = mb_strlen(trim($this->sPath));

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        $order = (string) $ctx->curctx->getAttribute('order');
        switch (strtoupper(trim($order))) {
            case 'ASC':
            case 'DESC':
                break;

            default:
                $order = 'ASC';
                break;
        }


        $orderBy = (string) $ctx->curctx->getAttribute('orderby');
        switch ($orderBy) {
            case 'modification':
                $orderField = 'sModified';
                break;
            case 'creation':
                $orderField = 'sCreation';
                break;
            case 'size':
                $orderField = 'iSize';
                break;
            case 'hits':
                $orderField = 'iHits';
                break;
            case 'manual':
                $orderField = 'iDisplayPosition';
                break;
            case 'name':
            default:
                $orderField = 'sName';
                break;
        }


        if ($iLength && '/' === $this->sPath{$iLength - 1}) {
            $this->sPath = mb_substr($this->sPath, 0, - 1);
        }

        $this->sEncodedPath = urlencode($this->sPath);

        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

        $this->oFolderFileSet = new BAB_FolderFileSet();

        $this->oFmFolderSet = new BAB_FmFolderSet();
        $oId = $this->oFmFolderSet->aField['iId'];

        if (0 !== $folderid) {
            $oFmFolder = $this->oFmFolderSet->get($oId->in($folderid));
            if (! is_null($oFmFolder)) {
                $this->iIdDelegation = $oFmFolder->getDelegationOwnerId();

                $iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
                $sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();

                // bab_debug('sRelativePath ==> ' . $sRelativePath .
                // ' sRootFolderName ==> ' . getFirstPath($sRelativePath));

                $sRootFolderName = getFirstPath($sRelativePath);
                $sRelativePath = $sRootFolderName . '/' . ($iLength ? $this->sPath . '/' : '');

                $this->initRootFolderId($sRootFolderName);

                $rows = (int) $ctx->curctx->getAttribute('rows');
                $offset = (int) $ctx->curctx->getAttribute('offset');

                $oGroup = $this->oFolderFileSet->aField['sGroup'];
                $oState = $this->oFolderFileSet->aField['sState'];
                $oPathName = $this->oFolderFileSet->aField['sPathName'];
                $oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
                $oIdDgOwner = $this->oFolderFileSet->aField['iIdDgOwner'];

                $oCriteria = $oGroup->in('Y');
                $oCriteria = $oCriteria->_and($oState->in(''));
                $oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
                $oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
                $oCriteria = $oCriteria->_and($oIdDgOwner->in($oFmFolder->getDelegationOwnerId()));

                $aLimit = array();
                if (0 !== $rows) {
                    $aLimit = array(
                        $offset,
                        $rows
                    );
                }

                require_once dirname(__FILE__) . '/tagApi.php';

                $oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

                $this->oFolderFileSet->select($oCriteria, array(
                    $orderField => $order
                ), $aLimit);
                while (null !== ($oFolderFile = $this->oFolderFileSet->next())) {
                    $this->IdEntries[] = $oFolderFile->getId();
                    $this->tags[$oFolderFile->getId()] = array();

                    $oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $oFolderFile->getId()));
                    $oIterator->orderAsc('tag_name');
                    foreach ($oIterator as $oTag) {
                        $this->tags[$oFolderFile->getId()][] = $oTag->getName();
                    }
                }
                $this->oFolderFileSet->rewind();
                $this->count = count($this->IdEntries);
            }
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function initRootFolderId($sRootFolderName)
    {
        $oName = $this->oFmFolderSet->aField['sName'];
        $oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];
        $oIdDgOwner = $this->oFmFolderSet->aField['iIdDgOwner'];

        $oCriteria = $oName->in($sRootFolderName);
        $oCriteria = $oCriteria->_and($oRelativePath->in(''));
        $oCriteria = $oCriteria->_and($oIdDgOwner->in($this->iIdDelegation));

        // Get the root folder
        $oFmFolder = $this->oFmFolderSet->get($oCriteria);
        if (! is_null($oFmFolder)) {
            $this->iIdRootFolder = $oFmFolder->getId();
        }
    }

    public function getnext()
    {
        if (0 !== $this->oFolderFileSet->count()) {
            if (null !== ($oFolderFile = $this->oFolderFileSet->next())) {
                $iIdAuthor = (0 === $oFolderFile->getModifierId() ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId());

                $oFileManagerEnv = & getEnvObject();
                $sUploadPath = '';
                $sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

                $sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();

                $mime = bab_getFileMimeType($sFullPathName);

                if (substr($mime, 0, 5) == "image") {
                    setImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $sFullPathName);
                } else {
                    $this->ctx->curctx->push('ImageUrl', '');
                    $this->ctx->curctx->push('FileIsImage', 0);
                }

                $this->ctx->curctx->push('CIndex', $this->idx);
                $this->ctx->curctx->push('FileName', $oFolderFile->getName());
                $this->ctx->curctx->push('FileDescription', $oFolderFile->getDescription());
                $this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags[$oFolderFile->getId()]));
                $this->ctx->curctx->push('FileId', $oFolderFile->getId());
                $this->ctx->curctx->push('FileFolderId', $oFolderFile->getOwnerId());
                $this->ctx->curctx->push('FileDate', bab_mktime($oFolderFile->getModifiedDate()));
                $this->ctx->curctx->push('FileAuthor', $iIdAuthor);

                /*
                 * bab_debug(
                 * 'FileName ==> ' . $oFolderFile->getName() .
                 * ' FileAuthorId ==> ' . $iIdAuthor . ' ' .
                 * ' FileAuthor ==> ' . bab_getUserName($iIdAuthor));
                 * //
                 */

                $sGroup = $oFolderFile->getGroup();

                $sEncodedPath = urlencode(removeEndSlashes($oFolderFile->getPathName()));

                $this->ctx->curctx->push('FileUrl', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath);

                $this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&idx=viewFile&idf=' . $oFolderFile->getId() . '&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($oFolderFile->getName()));

                $this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&sAction=getFile&id=' . $oFolderFile->getOwnerId() . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($oFolderFile->getName()) . '&idf=' . $oFolderFile->getId());

                $oFileManagerEnv = & getEnvObject();
                $sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

                $sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
                if (file_exists($sFullPathName)) {
                    $this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathName)));
                } else {
                    $this->ctx->curctx->push('FileSize', '???');
                }
                $this->idx ++;
                $this->index = $this->idx;
                return true;
            } else {
                return false;
            }
        }
        $this->idx = 0;
        return false;
    }
}



class Func_Ovml_Container_File extends Func_Ovml_Container
{
    public $arr;

    public $count;

    public $oFolderFile = null;

    public $iIdRootFolder = 0;

    public $tags = array();

    public $oFolderFileSet = null;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

        $this->oFolderFileSet = new BAB_FolderFileSet();
        $oId = $this->oFolderFileSet->aField['iId'];

        parent::setOvmlContext($ctx);
        $this->count = 0;
        $sFileId = (string) $ctx->curctx->getAttribute('fileid');
        if (0 !== mb_strlen(trim($sFileId))) {
            $aFileId = explode(',', $sFileId);
            $this->oFolderFileSet->select($oId->in($aFileId));
            $this->count = $this->oFolderFileSet->count();
            $this->ctx->curctx->push('CCount', $this->count);
        }
    }


    public function getnext()
    {
        static $iIndex = 0;

        if ($iIndex < $this->count) {
            $bHaveFileAcess = false;

            while ($iIndex < $this->count && false === $bHaveFileAcess) {
                $iIndex ++;
                $this->oFolderFile = $this->oFolderFileSet->next();
                if (! is_null($this->oFolderFile)) {
                    if ('Y' === $this->oFolderFile->getGroup() && '' === $this->oFolderFile->getState() && 'Y' === $this->oFolderFile->getConfirmed() && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $this->oFolderFile->getOwnerId())) {
                        $bHaveFileAcess = true;
                    }
                }
            }

            if (true === $bHaveFileAcess) {
                require_once dirname(__FILE__) . '/tagApi.php';

                $oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

                $oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $this->oFolderFile->getId()));
                $oIterator->orderAsc('tag_name');
                foreach ($oIterator as $oTag) {
                    $this->tags[] = $oTag->getName();
                }

                $iIdAuthor = (0 === $this->oFolderFile->getModifierId() ? $this->oFolderFile->getAuthorId() : $this->oFolderFile->getModifierId());

                $this->ctx->curctx->push('CIndex', $this->idx);
                $this->ctx->curctx->push('FileName', $this->oFolderFile->getName());
                $this->ctx->curctx->push('FileDescription', $this->oFolderFile->getDescription());
                $this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags));
                $this->ctx->curctx->push('FileId', $this->oFolderFile->getId());
                $this->ctx->curctx->push('FileFolderId', $this->oFolderFile->getOwnerId());
                $this->ctx->curctx->push('FileDate', bab_mktime($this->oFolderFile->getModifiedDate()));
                $this->ctx->curctx->push('FileAuthor', $iIdAuthor);

                $sRootFolderName = getFirstPath($this->oFolderFile->getPathName());
                $this->initRootFolderId($sRootFolderName);

                $sEncodedPath = urlencode(removeEndSlashes($this->oFolderFile->getPathName()));

                $sGroup = $this->oFolderFile->getGroup();

                $this->ctx->curctx->push('FileUrl', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath);

                $this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&idx=viewFile&idf=' . $this->oFolderFile->getId() . '&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()));

                $this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&sAction=getFile&id=' . $this->oFolderFile->getOwnerId() . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()) . '&idf=' . $this->oFolderFile->getId());

                $sFullPathName = BAB_FileManagerEnv::getCollectivePath($this->oFolderFile->getDelegationOwnerId()) . $this->oFolderFile->getPathName() . $this->oFolderFile->getName();
                if (file_exists($sFullPathName)) {
                    $this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathName)));
                } else {
                    $this->ctx->curctx->push('FileSize', '???');
                }
                $this->idx ++;
                $this->index = $this->idx;
                return true;
            }
        }
        $this->idx = $iIndex = 0;
        return false;
    }


    public function initRootFolderId($sRootFolderName)
    {
        $oFmFolderSet = new BAB_FmFolderSet();
        $oName = $oFmFolderSet->aField['sName'];
        $oRelativePath = $oFmFolderSet->aField['sRelativePath'];
        $oIdDgOwner = $oFmFolderSet->aField['iIdDgOwner'];

        $oCriteria = $oName->in($sRootFolderName);
        $oCriteria = $oCriteria->_and($oRelativePath->in(''));
        $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

        // Get the root folder
        $oFmFolder = $oFmFolderSet->get($oCriteria);
        if (! is_null($oFmFolder)) {
            $this->iIdRootFolder = $oFmFolder->getId();
        }
    }
}



class Func_Ovml_Container_FileFields extends Func_Ovml_Container
{
    public $fileid;

    public $index;

    public $count;

    public $res;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $this->fileid = $ctx->curctx->getAttribute('fileid');
        if ($this->fileid !== false && $this->fileid !== '') {
            $res = $babDB->db_query("select * from " . BAB_FILES_TBL . " where id='" . $babDB->db_escape_string($this->fileid) . "'");
            if ($res && $babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_array($res);
                if ($arr['bgroup'] == 'Y' && $arr['state'] == '' && $arr['confirmed'] == 'Y' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id_owner'])) {
                    $this->res = $babDB->db_query("select ff.*, fft.name from " . BAB_FM_FIELDSVAL_TBL . " ff LEFT JOIN " . BAB_FM_FIELDS_TBL . " fft on fft.id = ff.id_field where id_file='" . $babDB->db_escape_string($this->fileid) . "' and id_folder='" . $babDB->db_escape_string($arr['id_owner']) . "'");
                    if ($this->res) {
                        $this->count = $babDB->db_num_rows($this->res);
                    }
                }
            }
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FileFieldName', bab_translate($arr['name']));
            $fieldval = bab_toHtml($arr['fvalue']);
            $this->ctx->curctx->push('FileFieldValue', $fieldval);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_FilePrevious extends Func_Ovml_Container_File
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_File::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Files');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        parent::setOvmlContext($ctx);
    }
}



class Func_Ovml_Container_FileNext extends Func_Ovml_Container_File
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_File::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Files');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        parent::setOvmlContext($ctx);
    }
}



class Func_Ovml_Container_RecentFiles extends Func_Ovml_Container
{
    public $index;

    public $count;

    public $res;

    public $lastlog;

    public $nbdays;

    public $last;

    public $folderid;

    public $oFmFolderSet = null;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

        parent::setOvmlContext($ctx);
        $this->nbdays = $ctx->curctx->getAttribute('from_lastlog');
        $this->last = $ctx->curctx->getAttribute('last');
        $this->folderid = $ctx->curctx->getAttribute('folderid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');
        $path = $ctx->curctx->getAttribute('path');
        $fullpath = $ctx->curctx->getAttribute('fullpath');

        $this->oFmFolderSet = new BAB_FmFolderSet();

        $sDelegation = ' ';
        if (0 != $delegationid) {
            $sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
        }

        if ($this->folderid === false || $this->folderid === '') {
            $arr = array();
        } else {
            $arr = explode(',', $this->folderid);
        }

        if (count($arr) == 0) {
            $req = "select * from " . BAB_FM_FOLDERS_TBL . " where active='Y'" . $sDelegation;
        } else {
            $oId = $this->oFmFolderSet->aField['iId'];
            $res = $this->oFmFolderSet->select($oId->in($arr));
            $arrpath = array();

            foreach ($res as $oFmFolder) {
                $iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
                $sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();
                $sRootFolderName = getFirstPath($sRelativePath);
                $arrpath[] = $sRootFolderName . '/' . $path;
            }

            $req = "select * from " . BAB_FM_FOLDERS_TBL . " where active='Y' and (sRelativePath='' AND id IN(" . $babDB->quote($arr) . ") OR CONCAT(sRelativePath, folder) IN(" . $babDB->quote($arrpath) . "))" . $sDelegation;
        }

        $arrid = array();
        $res = $babDB->db_query($req);
        while ($arr = $babDB->db_fetch_array($res)) {
            if (bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id']))
                $arrid[] = $arr['id'];
        }

        if (count($arrid) > 0) {
            $req = "select f.* from " . BAB_FILES_TBL . " f where f.bgroup='Y' and f.state='' and f.confirmed='Y'";

            if ($path === false || $path === '') {
                $path = '';
            }
            if ($path != '') {
                if ($fullpath) {
                    $req .= " and f.path = '" . $babDB->db_escape_string($path . '/') . "'";
                } else {
                    $req .= " and f.path like '%" . $babDB->db_escape_like($path . '/') . "'";
                }
            }

            $req .= " and f.id_owner IN (" . $babDB->quote($arrid) . ")";

            if ($this->nbdays !== false && bab_isUserLogged()) {
                require_once dirname(__FILE__) . '/userinfosincl.php';
                $usersettings = bab_userInfos::getUserSettings();

                $req .= " and f.modified >= DATE_ADD(\"" . $babDB->db_escape_string($usersettings['lastlog']) . "\", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
            }

            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '') {
                $order = "desc";
            }

            switch (mb_strtoupper($order)) {
                case "ASC":
                    $order = 'f.modified ASC';
                    break;
                case "RAND":
                    $order = 'rand()';
                    break;
                case "DESC":
                default:
                    $order = 'f.modified DESC';
                    break;
            }

            $req .= ' order by ' . $order;

            if ($this->last !== false) {
                $req .= ' limit 0, ' . $babDB->db_escape_string((int) $this->last);
            }

            $this->res = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->res);
        } else
            $this->count = 0;

        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            $sPath = removeEndSlah($arr['path']);

            $sName = getFirstPath($arr['path']);

            $oName = & $this->oFmFolderSet->aField['sName'];
            $oRelativePath = & $this->oFmFolderSet->aField['sRelativePath'];
            $oIdDgOwner = & $this->oFmFolderSet->aField['iIdDgOwner'];

            $oCriteria = $oName->in($sName);
            $oCriteria = $oCriteria->_and($oRelativePath->in(''));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in($arr['iIdDgOwner']));

            $oFmFolder = $this->oFmFolderSet->get($oCriteria);
            if (! is_null($oFmFolder)) {
                $iId = $oFmFolder->getId();

                $this->ctx->curctx->push('CIndex', $this->idx);
                $this->ctx->curctx->push('FileId', $arr['id']);
                $this->ctx->curctx->push('FileName', $arr['name']);
                $this->ctx->curctx->push('FilePath', $arr['path']);
                $this->ctx->curctx->push('FileDescription', $arr['description']);
                $this->ctx->curctx->push('FileUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&idx=list&id=" . $iId . "&gr=" . $arr['bgroup'] . "&path=" . urlencode(removeEndSlashes($sPath)));
                $this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&idx=viewFile&idf=" . $arr['id'] . "&id=" . $iId . "&gr=" . $arr['bgroup'] . "&path=" . urlencode(removeEndSlashes($sPath)) . "&file=" . urlencode($arr['name']));
                $this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&sAction=getFile&id=" . $iId . "&gr=" . $arr['bgroup'] . "&path=" . urlencode(removeEndSlashes($sPath)) . "&file=" . urlencode($arr['name']) . '&idf=' . $arr['id']);
                $this->ctx->curctx->push('FileAuthor', $arr['author']);
                $this->ctx->curctx->push('FileModifiedBy', $arr['modifiedby']);
                $this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));

                $sFullPathname = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $arr['path'] . $arr['name'];

                $this->ctx->curctx->push('FileFullPath', $sFullPathname);
                if (file_exists($sFullPathname)) {
                    $this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathname)));
                } else {
                    $this->ctx->curctx->push('FileSize', '???');
                }
                $this->ctx->curctx->push('FileDelegationId', $arr['iIdDgOwner']);
            } else {
                $this->ctx->curctx->push('CIndex', $this->idx);
                $this->ctx->curctx->push('FileId', 0);
                $this->ctx->curctx->push('FileName', '');
                $this->ctx->curctx->push('FilePath', '');
                $this->ctx->curctx->push('FileDescription', '');
                $this->ctx->curctx->push('FileUrl', '');
                $this->ctx->curctx->push('FilePopupUrl', '');
                $this->ctx->curctx->push('FileUrlGet', '');
                $this->ctx->curctx->push('FileAuthor', '');
                $this->ctx->curctx->push('FileModifiedBy', '');
                $this->ctx->curctx->push('FileDate', '');
                $this->ctx->curctx->push('FileSize', '');
                $this->ctx->curctx->push('FileDelegationId', '');
            }
            $this->ctx->curctx->push('FileFolderId', $arr['id_owner']);
            $this->idx ++;
            $this->index = $this->idx;

            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_WaitingFiles extends Func_Ovml_Container
{
    public $res;

    public $IdEntries = array();

    public $index;

    public $count;

    public $folderid;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        $sDelegation = ' ';
        $sLeftJoin = ' ';
        if (0 != $delegationid) {
            $sLeftJoin = 'LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' fld ON fld.id = id_owner ';
            $sDelegation = ' AND fld.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
        }

        $userid = $ctx->curctx->getAttribute('userid');
        if ($userid === false || $userid === '') {
            $userid = $GLOBALS['BAB_SESS_USERID'];
        }

        if ($userid != '') {
            $this->folderid = $ctx->curctx->getAttribute('folderid');
            $req = "select f.id, f.idfai from " . BAB_FILES_TBL . " f " . $sLeftJoin . "where f.bgroup='Y' and f.confirmed='N'" . $sDelegation;
            if ($this->folderid !== false && $this->folderid !== '') {
                $req .= " and f.id_owner IN (" . $babDB->quote(explode(',', $this->folderid)) . ")";
            }

            $arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
            if (count($arrschi) > 0) {
                $res = $babDB->db_query($req);
                while ($arr = $babDB->db_fetch_array($res)) {
                    if (in_array($arr['idfai'], $arrschi)) {
                        $this->IdEntries[] = $arr['id'];
                    }
                }

                $this->count = count($this->IdEntries);
                if ($this->count > 0) {
                    $this->res = $babDB->db_query("select * from " . BAB_FILES_TBL . " where id IN (" . $babDB->quote($this->IdEntries) . ")");
                    $this->count = $babDB->db_num_rows($this->res);
                }
            }
        } else {
            $this->count = 0;
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FileId', $arr['id']);
            $this->ctx->curctx->push('FileName', $arr['name']);
            $this->ctx->curctx->push('FilePath', $arr['path']);
            $this->ctx->curctx->push('FileDescription', $arr['description']);
            $this->ctx->curctx->push('FileAuthor', $arr['author']);
            $this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
            $this->ctx->curctx->push('FileUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&idx=list&id=" . $arr['id_owner'] . "&gr=" . $arr['bgroup'] . "&path=" . urlencode($arr['path']));
            $this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&idx=viewFile&idf=" . $arr['id'] . "&id=" . $arr['id_owner'] . "&gr=" . $arr['bgroup'] . "&path=" . urlencode($arr['path']) . "&file=" . urlencode($arr['name']));
            $this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=fileman&sAction=getFile&id=" . $arr['id_owner'] . "&gr=" . $arr['bgroup'] . "&path=" . urlencode($arr['path']) . "&file=" . urlencode($arr['name']) . '&idf=' . $arr['id']);
            $this->ctx->curctx->push('FileFolderId', $arr['id_owner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}

