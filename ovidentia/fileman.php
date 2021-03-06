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

require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/pathUtil.class.php';
require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/uploadincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/baseFormProcessingClass.php';
require_once $GLOBALS['babInstallPath'].'utilit/i18n.class.php';


/*
 * Called by ajax in the the upload fileform
 */
class BAB_GetHtmlUploadBlock
{
    public $iBlockNbr		= 0;
    public $sName			= '';
    public $sDescription	= '';
    public $sKeywords		= '';
    public $sFieldname		= '';
    public $sAttribute		= '';
    public $sYes			= '';
    public $sNo			= '';

    public $descval		= '';
    public $keysval		= '';

    public $oResult		= false;
    public $iCount			= 0;

    public $bUseKeyword	= false;

    function __construct($iIdRootFolder, $sGr)
    {
        $this->iBlockNbr		= (int) bab_rp('iBlockNbr', 0);
        $this->sName			= bab_translate("Name");
        $this->sDescription		= bab_translate("Description");
        $this->sKeywords		= bab_toHtml(bab_translate("Keywords"));
        $this->sAttribute		= bab_translate("Final version");
        $this->sMaxDownloads	= bab_translate("Maximum number of downloads");
        $this->sYes				= bab_translate("Yes");
        $this->sNo				= bab_translate("No");

        $this->maxdownloadsval	= '';
        $this->downloadsval	= '';

        global $babDB;

        $oFmEnv	= &getEnvObject();

        if ($oFmEnv->userIsInCollectiveFolder()) {
            if ($oFmEnv->oFmFolder instanceof Bab_FmFolder) {
                $this->bUseKeyword = true;
                $this->bManageMaxDownloads = ($oFmEnv->oFmFolder->getDownloadsCapping() == 'Y');
                $this->maxdownloadsval = $oFmEnv->oFmFolder->getMaxDownloads();
            }
        }

        if ($sGr == 'Y') {
            $this->oResult = $babDB->db_query('SELECT * FROM ' . BAB_FM_FIELDS_TBL . ' WHERE id_folder = ' . $babDB->quote($iIdRootFolder));
            $this->iCount = $babDB->db_num_rows($this->oResult);
        } else {
            $this->iCount = 0;
        }
    }

    function getNextField()
    {
        global $babDB;
        static $i = 0;

        if ($i < $this->iCount) {
            $arr = $babDB->db_fetch_array($this->oResult);
            $this->sFieldname = bab_translate($arr['name']);
            $this->field = 'field'.$arr['id'];
            $this->fieldval = bab_toHtml($arr['defaultval']);
            $i++;
            return true;
        }
        return false;
    }

    function getHtml()
    {
        return bab_printTemplate($this, 'fileman.html', 'uploadBlock');
    }
}



class listFiles
{
    public $db;
    public $res;
    public $count;
    public $id;
    public $gr;
    public $path;
    public $jpath;
    public $countmgrp;
    public $countwf;
    public $reswf;
    public $buaf;

    public $oFolderFileSet = null;

    public $aCuttedDir = array();

    public $sProcessedIdx = '';
    public $sListFunctionName = '';

    public $bParentUrl = false;
    public $sParentTitle = '';
    public $sParent = '. .';
    public $bVersion = false;

    /**
     *
     * @var BAB_FileManagerEnv
     */
    public $oFileManagerEnv = null;

    public $sRootFolderPath = '';
    /**
     * Files extracted by readdir
     */
    public $files_from_dir = array();

    public $aFolders = array();

    public $order;


    function __construct($what="list")
    {
        $this->sParentTitle = bab_translate("Parent");

        global $babDB;
        include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

        $this->oFolderFileSet = new BAB_FolderFileSet();

        $this->sProcessedIdx = $what;
        $this->initEnv();

        $this->{$this->sListFunctionName}();

        $this->prepare();

        $this->autoadd_files();

        $this->fmfields = array();
        $res = $babDB->db_query("select * from ".BAB_FM_HEADERS_TBL." where fmh_order != '0' order by fmh_order asc");
        while( $arr = $babDB->db_fetch_array($res))
        {
            $this->fmfields[$arr['fmh_name']] = bab_translate($arr['fmh_description']);
        }
    }

    function getnextfmfield()
    {
        if( list($fhm_name,$fhm_text) = each($this->fmfields))
        {
            $this->fmfielddesc = $fhm_text;
            $this->fmfieldname = $fhm_name;
            $this->columnname = 'col-' . $fhm_name;
            $var = 'fmh_'.$fhm_name;
            if( isset($this->{$var}))
            {
                $this->fmfieldval = bab_toHTML($this->{$var});
            }
            else
            {
                $this->fmfieldval = '';
            }
            return true;
        }
        else
        {
            reset($this->fmfields);
            return false;
        }
    }

    function initEnv()
    {
        global $BAB_SESS_USERID;

        $this->oFileManagerEnv =& getEnvObject();
        $this->countwf = 0;

        if($this->oFileManagerEnv->userIsInRootFolder())
        {
            $this->sListFunctionName = 'listRootFolders';
        }
        else if($this->oFileManagerEnv->userIsInCollectiveFolder())
        {
            $this->sListFunctionName = 'listCollectiveFolder';

            if(0 !== $this->oFileManagerEnv->iPathLength)
            {
                if('list' === $this->sProcessedIdx)
                {
                    $oFmFolder = $this->oFileManagerEnv->oFmFolder;
                    if(!is_null($oFmFolder))
                    {
                        if(0 !== $oFmFolder->getApprobationSchemeId())
                        {
                            $this->buaf = isUserApproverFlow($oFmFolder->getApprobationSchemeId(), $BAB_SESS_USERID);
                            if($this->buaf)
                            {
                                $this->selectWaitingFile();
                            }
                        }
                    }
                }
            }
        }
        else if($this->oFileManagerEnv->userIsInPersonnalFolder())
        {
            $this->sListFunctionName = 'listPersonnalFolder';
        }
        $this->getClipboardFolder();

        $this->sParentUrl = $GLOBALS['babUrlScript'] . '?tg=fileman&idx=' . urlencode($this->sProcessedIdx) . '&id=' . $this->oFileManagerEnv->iId .
            '&gr=' . $this->oFileManagerEnv->sGr . '&path=';
        $this->bParentUrl = $this->oFileManagerEnv->setParentPath($this->sParentUrl);
        $this->sParentUrl = bab_toHtml($this->sParentUrl);

        $sPath = $this->oFileManagerEnv->sPath;
        $this->path = $sPath;
        $this->id = $this->oFileManagerEnv->iId;
        $this->gr = $this->oFileManagerEnv->sGr;

        $this->jpath = bab_toHtml($sPath, BAB_HTML_JS);
    }


    function listRootFolders()
    {
        global $BAB_SESS_USERID;

        $oFmFolderSet = new BAB_FmFolderSet();
        $oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
        $oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];

        $oCriteria = $oRelativePath->in('');
        $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

//		bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
        $oFmFolderSet->select($oCriteria);

        while(null !== ($oFmFolder = $oFmFolderSet->next()))
        {
            $this->addCollectiveDirectory($oFmFolder, $oFmFolder->getId());
        }
        bab_sort::asort($this->aFolders, 'sName', bab_Sort::CASE_INSENSITIVE);

        if(bab_userHavePersonnalStorage())
        {
            $aItem = array(
                'iId' => 0,
                'bCanManageFolder' => false,
                'bCanBrowseFolder' => true,
                'bCanEditFolder' => false,
                'bCanSetRightOnFolder' => false,
                'bCanCutFolder' => false,
                'sName' => bab_translate("Personal Folder"),
                'sGr' => 'N',
                'sCollective' => 'N',
                'sHide' => 'N',
                'sUrlPath' => '',
                'iIdUrl' => $BAB_SESS_USERID);

            $this->aFolders[] = $aItem;
        }
    }

    function listPersonnalFolder()
    {
        $sFullPathname = (string) $this->oFileManagerEnv->getPersonnalFolderPath();
        if(is_dir(realpath($sFullPathname)))
        {
            $this->walkDirectory($sFullPathname, 'simpleDirectoryCallback');
        }
        bab_sort::asort($this->aFolders, 'sName', bab_Sort::CASE_INSENSITIVE);
    }

    function listCollectiveFolder()
    {
        $sFullPathname = (string) $this->oFileManagerEnv->getCollectiveFolderPath();

        if(is_dir(realpath($sFullPathname)))
        {
            $this->walkDirectory($sFullPathname, 'collectiveDirectoryCallback');
        }
        bab_sort::asort($this->aFolders, 'sName', bab_Sort::CASE_INSENSITIVE);
    }

    function walkDirectory($sPathName, $sCallbackFunction)
    {
        if(is_dir($sPathName))
        {
            $oDir = dir($sPathName);
            while(false !== ($sEntry = $oDir->read()))
            {
                // Skip pointers
                if($sEntry == '.' || $sEntry == '..' || $sEntry == BAB_FVERSION_FOLDER)
                {
                    continue;
                }
                $this->$sCallbackFunction($sPathName, $sEntry);
            }
            $oDir->close();
        }
    }

    function simpleDirectoryCallback($sPathName, $sEntry)
    {
        if(is_dir($sPathName . $sEntry))
        {
            $sGr				= '';
            $sRootFmPath		= '';
            $sRelativePath		= $this->oFileManagerEnv->sRelativePath . $sEntry . '/';
            $bCanManage			= canManage($sRelativePath);
            $bCanBrowse			= canBrowse($sRelativePath);
            $bAccessValid		= false;
            $bCanBrowseFolder	= false;

            if($this->oFileManagerEnv->userIsInCollectiveFolder() || $this->oFileManagerEnv->userIsInRootFolder())
            {
                $sRootFmPath		= $this->oFileManagerEnv->getCollectiveRootFmPath();
                $sGr				= 'Y';
                $bAccessValid		= ($bCanManage || canUpload($sRelativePath) || canUpdate($sRelativePath) || ($bCanBrowse && 'N' === $this->oFileManagerEnv->oFmFolder->getHide()));
                $bCanBrowseFolder	= ($bCanBrowse && 'Y' === $this->oFileManagerEnv->oFmFolder->getActive());
            }
            else if($this->oFileManagerEnv->userIsInPersonnalFolder())
            {
                $sRootFmPath		= $this->oFileManagerEnv->getPersonnalFolderPath();
                $sGr				= 'N';
                $bAccessValid		= $bCanManage || $bCanBrowse;
                $bCanBrowseFolder	= $bCanBrowse;
            }
            else
            {
                return;
            }

            $sFullPathName	= $sRootFmPath . $this->oFileManagerEnv->sRelativePath . $sEntry;
            $bInClipBoard	= (bool) array_key_exists($sFullPathName, $this->aCuttedDir);

            if(false === $bInClipBoard)
            {
                if($bAccessValid)
                {
                    $aItem = array(
                        'iId' => 0,
                        'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL),
                        'bCanBrowseFolder' => $bCanBrowseFolder,
                        'bCanEditFolder' => canEdit($sRelativePath),
                        'bCanSetRightOnFolder' => false,
                        'bCanCutFolder' => (!$bInClipBoard && canCutFolder($sRelativePath)),
                        'sName' => $sEntry,
                        'sGr' => $sGr,
                        'sCollective' => 'N',
                        'sHide' => 'N',
                        'sUrlPath' => $this->oFileManagerEnv->sRelativePath . $sEntry,
                        'iIdUrl' => $this->oFileManagerEnv->iId);

                    $this->aFolders[] = $aItem;
                }
            }
        }
        else
        {
            $this->files_from_dir[] = $sEntry;
        }
    }

    function collectiveDirectoryCallback($sPathName, $sEntry)
    {
        $oFmFolderSet = new BAB_FmFolderSet();
        $oName =& $oFmFolderSet->aField['sName'];
        $oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
        $oIdDgOwner =& $oFmFolderSet->aField['iIdDgOwner'];

        $oCriteria = $oName->in($sEntry);
        $oCriteria = $oCriteria->_and($oRelativePath->in($this->oFileManagerEnv->sRelativePath));
        $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

        /* @var $oFmFolder BAB_FmFolder */
        $oFmFolder = $oFmFolderSet->get($oCriteria);
        if(!is_null($oFmFolder))
        {
            $this->addCollectiveDirectory($oFmFolder, $this->oFileManagerEnv->iId);
        }
        else
        {
            $this->simpleDirectoryCallback($sPathName, $sEntry);
        }
    }


    function addCollectiveDirectory($oFmFolder, $iIdRootFolder)
    {
        $sRelativePath = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';

        $sRootFmPath = $this->oFileManagerEnv->getCollectiveRootFmPath();
        $sFullPathName = $sRootFmPath . $oFmFolder->getRelativePath() . $oFmFolder->getName();

        $bInClipBoard = (bool) array_key_exists($sFullPathName, $this->aCuttedDir);

        if(false === $bInClipBoard)
        {
            $bCanManage = canManage($sRelativePath);
            $bCanBrowse = canBrowse($sRelativePath);

            // if($bCanManage || canUpload($sRelativePath) || canUpdate($sRelativePath) || ($bCanBrowse && 'N' === $oFmFolder->getHide()))
            if($bCanManage || ($bCanBrowse && 'N' === $oFmFolder->getHide()))
            {
                $aItem = array(
                    'iId' => $oFmFolder->getId(),
                    'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL),
                    'bCanBrowseFolder' => (canBrowse($sRelativePath) && 'Y' === $oFmFolder->getActive()),
                    'bCanEditFolder' => canEdit($sRelativePath),
                    'bCanSetRightOnFolder' => canSetRight($sRelativePath),
                    'bCanCutFolder' => canCutFolder($sRelativePath),
                    'sName' => $oFmFolder->getName(),
                    'sGr' => 'Y',
                    'sCollective' => 'Y',
                    'sHide' => $oFmFolder->getHide(),
                    'sUrlPath' => $oFmFolder->getRelativePath() . $oFmFolder->getName(),
                    'iIdUrl' => $iIdRootFolder);

                $this->aFolders[] = $aItem;
            }
        }
    }


    function getClipboardFolder()
    {
        $sRootFmPath = '';
        $sGr = '';

        $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
        $oIdDgOwner = $oFmFolderCliboardSet->aField['iIdDgOwner'];
        $oGroup = $oFmFolderCliboardSet->aField['sGroup'];

        $oCriteria = null;

        if($this->oFileManagerEnv->userIsInCollectiveFolder() || $this->oFileManagerEnv->userIsInRootFolder())
        {
            $sGr = 'Y';
            $sRootFmPath = $this->oFileManagerEnv->getCollectiveRootFmPath();

            $oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
        }
        else if($this->oFileManagerEnv->userIsInPersonnalFolder())
        {
            $sGr = 'N';
            $sRootFmPath = $this->oFileManagerEnv->getPersonnalFolderPath();

            $oCriteria = $oIdDgOwner->in(0);
        }
        else
        {
            return;
        }


        $oCriteria = $oCriteria->_and($oGroup->in($sGr));
        $aOrder = array('sName' => 'ASC');
        $oFmFolderCliboardSet->select($oCriteria, $aOrder);

        $bSrcPathIsCollective = true;
        $iIdTrgRootFolder = $this->oFileManagerEnv->iId;
        $sTrgPath = $this->oFileManagerEnv->sPath;

        while(null !== ($oFmFolderCliboard = $oFmFolderCliboardSet->next()))
        {
            $sSrcPath = $oFmFolderCliboard->getRelativePath() . $oFmFolderCliboard->getName();
            $iIdSrcRootFolder = $oFmFolderCliboard->getRootFolderId();

            $sRelativePath =  $sSrcPath . '/';

            if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
            {
                $aItem = array(
                    'iId' => $oFmFolderCliboard->getFolderId(),
                    'bCanManageFolder' => haveRight($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL),
                    'bCanBrowseFolder' => canBrowse($sRelativePath),
                    'bCanEditFolder' => false,
                    'bCanSetRightOnFolder' => false,
                    'bCanCutFolder' => false,
                    'sName' => $oFmFolderCliboard->getName(),
                    'sGr' => $sGr,
                    'sCollective' => $oFmFolderCliboard->getCollective(),
                    'sUrlPath' => $sTrgPath,
                    'iIdUrl' => $this->oFileManagerEnv->iId,
                    'iIdSrcRootFolder' => $iIdSrcRootFolder,
                    'sSrcPath' => $sSrcPath);

                $sFullPathName = $sRootFmPath . $sSrcPath;
                $this->aCuttedDir[$sFullPathName] = $aItem;
            }
        }
    }


    function selectWaitingFile()
    {
        $aWaitingAppInstanceId = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
        if(count($aWaitingAppInstanceId) > 0)
        {
            $this->oFolderFileSet->bUseAlias = false;
            $oIdOwner =& $this->oFolderFileSet->aField['iIdOwner'];
            $oGroup =& $this->oFolderFileSet->aField['sGroup'];
            $oState =& $this->oFolderFileSet->aField['sState'];
            $oPathName =& $this->oFolderFileSet->aField['sPathName'];
            $oConfirmed =& $this->oFolderFileSet->aField['sConfirmed'];
            $oIdFlowApprobationInstance = $this->oFolderFileSet->aField['iIdFlowApprobationInstance'];
            $oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];

            $iIdOwner = $this->oFileManagerEnv->iIdObject;

            $oCriteria = $oIdOwner->in($iIdOwner);
            $oCriteria = $oCriteria->_and($oGroup->in('Y'));
            $oCriteria = $oCriteria->_and($oState->in(''));
            $oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
            $oCriteria = $oCriteria->_and($oConfirmed->in('N'));
            $oCriteria = $oCriteria->_and($oIdFlowApprobationInstance->in($aWaitingAppInstanceId));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

            $this->oFolderFileSet->select($oCriteria);
            $this->reswf = $this->oFolderFileSet->_oResult;
            $this->countwf = $this->oFolderFileSet->count();
            $this->oFolderFileSet->bUseAlias = true;
        }
    }

    function prepare()
    {
        $this->oFolderFileSet->bUseAlias = false;
        $oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
        $oGroup = $this->oFolderFileSet->aField['sGroup'];
        $oState = $this->oFolderFileSet->aField['sState'];
        $oPathName = $this->oFolderFileSet->aField['sPathName'];
        $oConfirmed = $this->oFolderFileSet->aField['sConfirmed'];
        $oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];

        $iIdOwner = $this->oFileManagerEnv->iIdObject;

        $oCriteria = $oIdOwner->in($iIdOwner);
        $oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
        $oCriteria = $oCriteria->_and($oState->in(''));
        $oCriteria = $oCriteria->_and($oPathName->in($this->oFileManagerEnv->sRelativePath));
        $oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
        $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));


        $order = bab_rp('order', null);

        if (isset($order)) {
            // The user has selected a column to order the list.
            $fieldName = substr($order, 0, -1);
            $asc = (substr($order, -1) == 'A') ? 'ASC' : 'DESC';

            $aOrder = array($fieldName => $asc);

            $this->order = $order;
        } else if (is_object($this->oFileManagerEnv->oFmFolder) && $this->oFileManagerEnv->oFmFolder->aDatas['bManualOrder']) {
            $aOrder = array('iDisplayPosition' => 'ASC');
            $this->order = 'iDisplayPositionA';
        } else {
            $aOrder = array('sName' => 'ASC');
            $this->order = 'sNameA';
        }

//		$aOrder = array('sName' => 'ASC');

//bab_debug($aOrder);

        $this->oFolderFileSet->select($oCriteria, $aOrder);

        $this->res = $this->oFolderFileSet->_oResult;
        $this->count = $this->oFolderFileSet->count();
        $this->oFolderFileSet->bUseAlias = true;
    }


    /**
     * if there is file not presents in database, add and recreate $this->res
     */
    function autoadd_files()
    {
        global $babDB;
        if(!isset($GLOBALS['babAutoAddFilesAuthorId']) || empty($GLOBALS['babAutoAddFilesAuthorId']))
        {
            return;
        }

        $res = $babDB->db_query('select id from '.BAB_USERS_TBL.' where id='.$babDB->quote($GLOBALS['babAutoAddFilesAuthorId']));
        if(0 == $babDB->db_num_rows($res))
        {
            return;
        }



        if($this->count < count($this->files_from_dir))
        {
            $oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];
            $oGroup = $this->oFolderFileSet->aField['sGroup'];
            $oPathName = $this->oFolderFileSet->aField['sPathName'];
            $oName = $this->oFolderFileSet->aField['sName'];
            $oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];

            $iIdOwner = $this->oFileManagerEnv->iIdObject;

            $oFolderFile = new BAB_FolderFile();
            foreach($this->files_from_dir as $dir_file)
            {
                $oCriteria = $oPathName->in($this->oFileManagerEnv->sRelativePath);
                $oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
                $oCriteria = $oCriteria->_and($oName->in($dir_file));
                $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
                $oCriteria = $oCriteria->_and($oIdOwner->in($iIdOwner));

                $this->oFolderFileSet->select($oCriteria);

                if(0 === $this->oFolderFileSet->count())
                {
                    $sFullPathName	= $this->oFileManagerEnv->sFmRootPath . $this->oFileManagerEnv->sRelativePath . $dir_file;

                    $oFolderFile->setName($dir_file);
                    $oFolderFile->setPathName($this->oFileManagerEnv->sRelativePath);

                    $oFolderFile->setOwnerId($iIdOwner);
                    $oFolderFile->setGroup($this->oFileManagerEnv->sGr);
                    $oFolderFile->setCreationDate(date("Y-m-d H:i:s"));
                    $oFolderFile->setAuthorId($GLOBALS['babAutoAddFilesAuthorId']);
                    $oFolderFile->setModifiedDate(date("Y-m-d H:i:s"));
                    $oFolderFile->setModifierId($GLOBALS['babAutoAddFilesAuthorId']);
                    $oFolderFile->setConfirmed('Y');

                    $oFolderFile->setDescription('');
                    $oFolderFile->setLinkId(0);
                    $oFolderFile->setReadOnly('N');
                    $oFolderFile->setState('');
                    $oFolderFile->setHits(0);
                    $oFolderFile->setFlowApprobationInstanceId(0);
                    $oFolderFile->setFolderFileVersionId(0);
                    $oFolderFile->setMajorVer(1);
                    $oFolderFile->setMinorVer(0);
                    $oFolderFile->setCommentVer('');
                    $oFolderFile->setStatusIndex(0);
                    $oFolderFile->setDelegationOwnerId(bab_getCurrentUserDelegation());
                    $oFolderFile->setSize(filesize($sFullPathName));

                    $oFolderFile->save();
                    $oFolderFile->setId(null);
                }
            }
            $this->prepare();
        }
    }
}


class DisplayFolderFormBase extends BAB_BaseFormProcessing
{
    function __construct()
    {
        parent::BAB_BaseFormProcessing();

        $sFunction 	= (string) bab_gp('sFunction', '');
        $sDirName	= (string) bab_gp('sDirName', '');
        $iIdFolder 	= (int) bab_gp('iIdFolder', 0);

        $this->set_data('sIdx', 'list');
        $this->set_data('sAction', $sFunction);
        $this->set_data('sTg', 'fileman');

        $this->setCaption();

        $oFileManagerEnv =& getEnvObject();
        $this->set_data('iId', $oFileManagerEnv->iId);
        $this->set_data('sPath', $oFileManagerEnv->sPath);
        $this->set_data('sGr', $oFileManagerEnv->sGr);

        $this->set_data('sDirName', $sDirName);
        $this->set_data('sOldDirName', '');
        $this->set_data('iIdFolder', 0);

        $this->set_data('sSimple', 'simple');
        $this->set_data('sCollective', 'collective');
        $this->set_data('sHtmlTable', '');

        $this->set_data('iIdFolder', $iIdFolder);

        $this->set_data('bDelete', false);

        if('createFolder' === $sFunction)
        {
            $this->handleCreation();
        }
        else if('editFolder' === $sFunction)
        {
            $this->handleEdition();
        }
    }

    function setCaption()
    {
        $this->set_caption('sDirName', bab_translate("Name") . ': ');
        $this->set_caption('sDelete', bab_translate("Delete"));
        $this->set_caption('sSubmit', bab_translate("Submit"));
    }

    function handleCreation()
    {

    }

    function handleEdition()
    {
        $sDirName = null;

        $this->get_data('sDirName', $sDirName);
        $this->set_data('sOldDirName', $sDirName);
    }

    function printTemplate()
    {
    }
}

class DisplayUserFolderForm extends DisplayFolderFormBase
{
    function __construct()
    {
        parent::__construct();
    }

    function handleEdition()
    {
        parent::handleEdition();

        global $BAB_SESS_USERID;
        $iId = null;
        $this->get_data('iId', $iId);
        $this->set_data('bDelete', (((int) $iId === (int) $BAB_SESS_USERID) ? true : false));
    }

    function printTemplate()
    {
        $this->set_data('sHtmlTable', bab_printTemplate($this, 'fileman.html', 'userDir'));

        $this->raw_2_html(BAB_RAW_2_HTML_CAPTION);
        return bab_printTemplate($this, 'fileman.html', 'displayFolderForm');
    }
}


class DisplayCollectiveFolderForm extends DisplayFolderFormBase
{
    public $iApprobationSchemeId = null;
    public $oAppSchemeRes = false;

    function __construct()
    {
        parent::__construct();

        $this->setCaption();
        $this->set_data('sYes', 'Y');
        $this->set_data('sNo', 'N');
        $this->set_data('iNone', 0);

        $this->set_data('iAppSchemeId', 0);
        $this->set_data('iAppSchemeName', '');

        global $babDB;
        $this->oAppSchemeRes = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
    }

    function setCaption()
    {
        parent::setCaption();
        $this->set_caption('sType', bab_translate("Type") . ': ');
        $this->set_caption('sActive', bab_translate("Actif") . ': ');
        $this->set_caption('sApprobationScheme', bab_translate("Approbation schema") . ': ');
        $this->set_caption('sAutoApprobation', bab_translate("Automatically approve author if he belongs to approbation schema") . ': ');
        $this->set_caption('sNotification', bab_translate("Notification") . ': ');
        $this->set_caption('sVersioning', bab_translate("Versioning") . ': ');
        $this->set_caption('sDisplay', bab_translate("Visible in file manager?") . ': ');
        $this->set_caption('sAddTags', bab_translate("Users can add new tags") . ': ');
        $this->set_caption('sSimple', bab_translate("Simple"));
        $this->set_caption('sCollective', bab_translate("Collective"));
        $this->set_caption('sDownloadsCapping', bab_translate("Manage maximum number of downloads per file") . ': ');
        $this->set_caption('sMaxDownloads', bab_translate("Default value") . ': ');
        $this->set_caption('sDownloadHistory', bab_translate("Manage downloads history") . ': ');
        $this->set_caption('sYes', bab_translate("Yes"));
        $this->set_caption('sNo', bab_translate("No"));
        $this->set_caption('sNone', bab_translate("None"));
        $this->set_caption('sAdd', bab_translate("Add"));
        $this->set_caption('sConfRights', bab_translate("Inherit the rights and the options of the parent directory"));
        $this->set_caption('sManualOrder', bab_translate("Manual order") . ': ');
        $this->set_caption('thelp1', bab_translate("Deactivate a folder allows to archive it: the folder content will not be accessible"));
        $this->set_caption('thelp2', bab_translate("Activate the management of the versions allows to keep a history of all the modifications brought to the same file"));
        $this->set_caption('thelp3', bab_translate("If the folder is hidden, it will not be visible in the file manager except from the manager, its contents remain accessible outside of the file manager (link since an article, a file OVML...)"));
        $this->set_caption('thelp4', bab_translate("If this option is activated, the keywords of files will be seized freely by their authors and automatically added in the thesaurus. If the option is deactivated, only the keywords seized by the managers of the thesaurus can be selected by the authors of files"));
        $this->set_caption('thelp5', bab_translate("Allows to specify how many times a file can be downloaded. Any user downloading the file adds one hit to this counter. Once the counter reaches the set value, the file cannot be downloaded anymore."));
        $this->set_caption('thelp6', bab_translate("Sets the default value that appears in the upload form. The upolading user can change this value while filling the upload form."));
        $this->set_caption('thelp7', bab_translate("Allow to record which user has downloaded the files included in this folder. Downloads by anonymous users are counted as done by one single 'anonymous user'."));
        $this->set_caption('thelp8', bab_translate("Allows the user granted with management rights on this folder to order manually the files. Subfolders are not affected by this option."));

    }

    function handleCreation()
    {
//		echo __METHOD__;
        $sActive				= 'Y';
        $iIdApprobationScheme	= 0;
        $sAutoApprobation		= 'N';
        $sNotification			= 'N';
        $sVersioning			= 'N';
        $sDisplay				= 'N';
        $sAddTags				= 'Y';
        $sDownloadsCapping		= 'N';
        $iMaxDownloads			= 0;
        $sDownloadHistory		= 'N';
        $bManualOrder			= false;

        $oFileManagerEnv =& getEnvObject();
        $oFirstCollectiveParent = BAB_FmFolderSet::getFirstCollectiveFolder($oFileManagerEnv->sRelativePath);
        if (!is_null($oFirstCollectiveParent)) {
            $sActive				= (string) $oFirstCollectiveParent->getActive();
            $iIdApprobationScheme	= (int) $oFirstCollectiveParent->getApprobationSchemeId();
            $sAutoApprobation		= (string) $oFirstCollectiveParent->getAutoApprobation();
            $sNotification			= (string) $oFirstCollectiveParent->getFileNotify();
            $sVersioning			= (string) $oFirstCollectiveParent->getVersioning();
            $sDisplay				= (string) $oFirstCollectiveParent->getHide();
            $sAddTags				= (string) $oFirstCollectiveParent->getAddTags();
            $sDownloadsCapping		= (string) $oFirstCollectiveParent->getDownloadsCapping();
            $iMaxDownloads			= (int) $oFirstCollectiveParent->getMaxDownloads();
            $sDownloadHistory		= (string) $oFirstCollectiveParent->getDownloadHistory();
            $bManualOrder			= (bool) $oFirstCollectiveParent->getManualOrder();
        }

        $this->iApprobationSchemeId = $iIdApprobationScheme;
        $this->set_data('isCollective', false);
        $this->set_data('isActive', ('Y' === $sActive) ? true : false);
        $this->set_data('isAutoApprobation', ('Y' === $sAutoApprobation) ? true : false);
        $this->set_data('isFileNotify', ('Y' === $sNotification) ? true : false);
        $this->set_data('isVersioning', ('Y' === $sVersioning) ? true : false);
        $this->set_data('isShow', ('Y' === $sDisplay) ? false : true);
        $this->set_data('isAddTags', ('Y' === $sAddTags) ? true : false);
        $this->set_data('isDownloadsCapping', ('Y' === $sDownloadsCapping) ? true : false);
        $this->set_data('isDownloadHistory', ('Y' === $sDownloadHistory) ? true : false);
        $this->set_data('isManualOrder', $bManualOrder);
        $this->set_data('iMaxDownloads', $iMaxDownloads);
        $this->set_data('sChecked', 'checked');
        $this->set_data('sDisabled', '');


        $oFileManagerEnv =& getEnvObject();
        if ($oFileManagerEnv->userIsInRootFolder()) {
            $this->set_data('isCollective', true);
            $this->set_data('sDisabled', 'disabled');
        }
    }

    function handleEdition()
    {
        $this->set_data('isCollective', false);
        $this->set_data('isActive', true);
        $this->set_data('isAutoApprobation', false);
        $this->set_data('isFileNotify', false);
        $this->set_data('isVersioning', false);
        $this->set_data('isShow', true);
        $this->set_data('isAddTags', true);
        $this->set_data('sChecked', 'checked');
        $this->set_data('sDisabled', '');

        $iId = null;
        $sPath = null;
        $sDirName = null;
        $iIdFolder = null;

        $this->get_data('iId', $iId);
        $this->get_data('sPath', $sPath);
        $this->get_data('sDirName', $sDirName);
        $this->set_data('sOldDirName', $sDirName);
        $this->get_data('iIdFolder', $iIdFolder);

        $this->set_data('sCheckedOder', '');

        $oFileManagerEnv =& getEnvObject();


        $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
        if(!is_null($oFmFolder))
        {
            $sActive				= (string) $oFmFolder->getActive();
            $iIdApprobationScheme	= (int) $oFmFolder->getApprobationSchemeId();
            $sAutoApprobation		= (string) $oFmFolder->getAutoApprobation();
            $sNotification			= (string) $oFmFolder->getFileNotify();
            $sVersioning			= (string) $oFmFolder->getVersioning();
            $sDisplay				= (string) $oFmFolder->getHide();
            $sAddTags				= (string) $oFmFolder->getAddTags();
            $sDownloadsCapping		= (string) $oFmFolder->getDownloadsCapping();
            $sDownloadHistory		= (string) $oFmFolder->getDownloadHistory();


            $this->iApprobationSchemeId = $iIdApprobationScheme;
            $this->set_data('isCollective', true);
            $this->set_data('isActive', ('Y' === $sActive) ? true : false);
            $this->set_data('isAutoApprobation', ('Y' === $sAutoApprobation) ? true : false);
            $this->set_data('isFileNotify', ('Y' === $sNotification) ? true : false);
            $this->set_data('isVersioning', ('Y' === $sVersioning) ? true : false);
            $this->set_data('isShow', ('Y' === $sDisplay) ? false : true);
            $this->set_data('isAddTags', ('Y' === $sAddTags) ? true : false);
            $this->set_data('isDownloadsCapping', ('Y' === $sDownloadsCapping) ? true : false);
            $this->set_data('isDownloadHistory', ('Y' === $sDownloadHistory) ? true : false);
            $this->set_data('iMaxDownloads', $oFmFolder->getMaxDownloads());
            $this->set_data('isManualOrder', $oFmFolder->getManualOrder());
            $this->set_data('iIdFolder', $oFmFolder->getId());
            $this->set_data('sOldDirName', $oFmFolder->getName());
            $this->set_data('sChecked', '');

            if ($oFileManagerEnv->userIsInRootFolder()) {
                $this->set_data('sDisabled', 'disabled');
            }
        }
        else
        {
            $sDownloadsCapping		= (string) $oFileManagerEnv->oFmFolder->getDownloadsCapping();
            $sDownloadHistory		= (string) $oFileManagerEnv->oFmFolder->getDownloadHistory();

            $this->set_data('isDownloadsCapping', ('Y' === $sDownloadsCapping) ? true : false);
            $this->set_data('isDownloadHistory', ('Y' === $sDownloadHistory) ? true : false);
            $this->set_data('iMaxDownloads', $oFileManagerEnv->oFmFolder->getMaxDownloads());
        }
        $this->set_data('bDelete', canCreateFolder($oFileManagerEnv->sRelativePath));

    }

    function getNextApprobationScheme()
    {
        if (false !== $this->oAppSchemeRes) {
            global $babDB;
            $aDatas = $babDB->db_fetch_array($this->oAppSchemeRes);
            if (false !== $aDatas) {
                $this->set_data('iAppSchemeId', $aDatas['id']);
                $this->set_data('iAppSchemeName', $aDatas['name']);
                $this->set_data('sAppSchemeNameSelected', '');

                if ($this->iApprobationSchemeId == $aDatas['id']) {
                    $this->set_data('sAppSchemeNameSelected', 'selected="selected"');
                }
                return true;
            }
        }
        return false;
    }

    function printTemplate()
    {
        global $babBody;
        $this->set_data('sHtmlTable', bab_printTemplate($this, 'fileman.html', 'collectiveDir'));

        $this->raw_2_html(BAB_RAW_2_HTML_CAPTION);
        $babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
        return bab_printTemplate($this, 'fileman.html', 'displayFolderForm');
    }
}


function listTrashFiles()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $babBody->title = bab_translate("Trash");

    $babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] .
        '?idx=list&id=' . $oFileManagerEnv->iId .
        '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath).'&tg=fileman');

    if(canUpload($oFileManagerEnv->sRelativePath))
    {
        $babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript'] .
            '?idx=displayAddFileForm&id=' . $oFileManagerEnv->iId .
            '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath).'&tg=fileman');
    }

    if(canManage($oFileManagerEnv->sRelativePath))
    {
        $babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript'] .
            '?idx=trash&id=' . $oFileManagerEnv->iId .
            '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath).'&tg=fileman');
    }


    class listTrashFilesTpl
    {
        public $db;
        public $arrext = array();
        public $idfile;
        public $delete;
        public $restore;
        public $nametxt;
        public $modifiedtxt;
        public $sizetxt;
        public $postedtxt;
        public $oFolderFileSet = null;
        public $sPath = '';
        public $sRelativePath = '';
        public $sEndSlash = '';

        public $oFileManagerEnv = null;

        function __construct()
        {
            $this->oFileManagerEnv =& getEnvObject();

            $this->id = $this->oFileManagerEnv->iId;
            $this->gr = $this->oFileManagerEnv->sGr;
            $this->sPath = $this->oFileManagerEnv->sPath;
            $this->bytes = bab_translate("bytes");
            $this->delete = bab_translate("Delete");
            $this->restore = bab_translate("Restore");
            $this->nametxt = bab_translate("Name");
            $this->sizetxt = bab_translate("Size");
            $this->modifiedtxt = bab_translate("Modified");
            $this->postedtxt = bab_translate("Posted by");
            $this->checkall = bab_translate("Check all");
            $this->uncheckall = bab_translate("Uncheck all");
            $this->selectTrashFile();
        }

        function selectTrashFile()
        {

            global $babDB;
            $this->oFolderFileSet = new BAB_FolderFileSet();
            $oState =& $this->oFolderFileSet->aField['sState'];
            $oPathName =& $this->oFolderFileSet->aField['sPathName'];
            $oIdOwner =& $this->oFolderFileSet->aField['iIdOwner'];
            $oGroup =& $this->oFolderFileSet->aField['sGroup'];
            $oIdDgOwner =& $this->oFolderFileSet->aField['iIdDgOwner'];

            $oCriteria = $oState->in('D');
            $oCriteria = $oCriteria->_and($oPathName->like($babDB->db_escape_like($this->oFileManagerEnv->sRelativePath)));
            $oCriteria = $oCriteria->_and($oIdOwner->in($this->oFileManagerEnv->iIdObject));
            $oCriteria = $oCriteria->_and($oGroup->in($this->oFileManagerEnv->sGr));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

            $this->oFolderFileSet->select($oCriteria, array('sName' => 'ASC'));
        }


        function getnextfile()
        {
            if(!is_null($this->oFolderFileSet) && $this->oFolderFileSet->count() > 0)
            {
                $oFolderFile = $this->oFolderFileSet->next();
                if(!is_null($oFolderFile))
                {
                    $iPos = mb_strpos($oFolderFile->getName(), ".");
                    $ext = mb_substr($oFolderFile->getName(), $iPos+1);
                    if(empty($this->arrext[$ext]))
                    {
                        $this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
                    }
                    if(empty($this->arrext[$ext]))
                    {
                        $this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");
                    }

                    $this->fileimage = $this->arrext[$ext];
                    $this->name = bab_toHtml($oFolderFile->getName());
                    $this->idfile = $oFolderFile->getId();


                    if(file_exists($this->oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getName()))
                    {
                        $fstat = stat($this->oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getName());
                        $this->sizef = $fstat[7];
                    }
                    else
                    {
                        $this->sizef = "???";
                    }

                    $this->modified = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getModifiedDate()), true));
                    $this->postedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId() == 0 ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId()));
                    return true;
                }
            }
            return false;
        }
    }

    $temp = new listTrashFilesTpl();
    $babBody->babecho(bab_printTemplate($temp,"fileman.html", "trashfiles"));
}

function showDiskSpace()
    {
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $babBody->title = bab_translate("Trash");

    $babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript'] .
        '?idx=list&id=' . $oFileManagerEnv->iId .
        '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath)).'&tg=fileman';

    if(canUpload($oFileManagerEnv->sRelativePath))
    {
        $babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript'] .
            '?idx=displayAddFileForm&id=' . $oFileManagerEnv->iId .
            '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath)).'&tg=fileman';
    }

    if(canManage($oFileManagerEnv->sRelativePath))
    {
        $babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript'] .
            '?idx=trash&id=' . $oFileManagerEnv->iId .
            '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath)).'&tg=fileman';
    }



    class showDiskSpaceTpl
        {
        public $id;
        public $gr;
        public $path;
        public $cancel;
        public $bytes;
        public $babCss;
        public $arrgrp = array();
        public $arrmgrp = array();
        public $countgrp;
        public $countmgrp;
        public $diskp;
        public $diskg;
        public $groupname;
        public $diskspace;
        public $allowedspace;
        public $remainingspace;
        public $grouptxt;
        public $diskspacetxt;
        public $allowedspacetxt;
        public $remainingspacetxt;

        /**
         *
         * @var BAB_FileManagerEnv
         */
        public $oFileManagerEnv;

        public $sContent;

        function __construct()
            {
            $oFileManagerEnv =& getEnvObject();

            $this->id = $oFileManagerEnv->iId;
            $this->gr = $oFileManagerEnv->sGr;
            $this->path = $oFileManagerEnv->sPath;

            $this->grouptxt = bab_translate("Name");
            $this->diskspacetxt = bab_translate("Used");
            $this->allowedspacetxt = bab_translate("Allowed");
            $this->remainingspacetxt = bab_translate("Remaining");
            $this->cancel = bab_translate("Close");
            $this->bytes = bab_translate("bytes");
            $this->kilooctet = " ".bab_translate("Kb");
            $this->babCss = bab_printTemplate($this,"config.html", "babCss");
            $this->sContent		= 'text/html; charset=' . bab_charset::getIso();

            $this->oFileManagerEnv =& getEnvObject();

            $oFmFolderSet = new BAB_FmFolderSet();
            $oRelativePath =& $oFmFolderSet->aField['sRelativePath'];
            $oFmFolderSet->select($oRelativePath->in(''));

            while(null !== ($oFmFolder = $oFmFolderSet->next()))
            {
                if(bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $oFmFolder->getId()))
                {
                    $this->arrmgrp[] = 	$oFmFolder->getId();
                }
                else
                {
                    $sRelativePath = $oFmFolder->getName() . '/';
                    if(canUpload($sRelativePath) || canUpdate($sRelativePath) || canDownload($sRelativePath))
                    {
                        $this->arrgrp[] = 	$oFmFolder->getId();
                    }
                }
            }

            $oFileManagerEnv =& getEnvObject();
            if(!empty($GLOBALS['BAB_SESS_USERID']) && bab_userHavePersonnalStorage())
                $this->diskp = 1;
            else
                $this->diskp = 0;
            if(!empty($GLOBALS['BAB_SESS_USERID'] ) && bab_isUserAdministrator())
                $this->diskg = 1;
            else
                $this->diskg = 0;
            $this->countgrp = count($this->arrgrp);
            $this->countmgrp = count($this->arrmgrp);
            }

        function getprivatespace()
            {
            static $i = 0;
            if( $i < $this->diskp)
                {
                $pathx = BAB_FileManagerEnv::getFmRealPersonalPath() . BAB_FileManagerEnv::userPrefix . bab_getUserId() . '/';
                $size = getDirSize($pathx);
                $this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
                $this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxUserSize']).$this->kilooctet);
                $this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxUserSize'] - $size).$this->kilooctet);
                $this->groupname = bab_translate("Personal Folder");
                $i++;
                return true;
                }
            else
                return false;
            }

        function getglobalspace()
            {
            static $i = 0;
            if( $i < $this->diskg)
                {
                $size = getDirSize($this->oFileManagerEnv->getFmUploadPath());
                $this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
                $this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxTotalSize']).$this->kilooctet);
                $this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxTotalSize'] - $size).$this->kilooctet);
                $this->groupname = bab_translate("Global space");
                $i++;
                return true;
                }
            else
                return false;
            }

        function getnextgrp(&$bSkip)
        {
            static $i = 0;
            if($i < $this->countgrp)
            {
                $this->groupname = 'B';
                $oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrgrp[$i]);
                $i++;
                if(is_null($oFmFolder))
                {
                    $bSkip = true;
                    return true;
                }
                $this->groupname = $oFmFolder->getName();
                $pathx = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $oFmFolder->getName();
                $size = getDirSize($pathx);
                $this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
                $this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
                $this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
                return true;
            }
            else
            {
                return false;
            }
        }

        function getnextmgrp(&$bSkip)
        {
            static $i = 0;
            if($i < $this->countmgrp)
            {
                $this->groupname = 'A';
                $oFmFolder = BAB_FmFolderHelper::getFmFolderById($this->arrmgrp[$i]);
                $i++;
                if(is_null($oFmFolder))
                {
                    $bSkip = true;
                    return true;
                }

                $this->groupname = $oFmFolder->getName();
                $pathx = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $oFmFolder->getName();
                $size = getDirSize($pathx);
                $this->diskspace = bab_toHtml(bab_formatSizeFile($size).$this->kilooctet);
                $this->allowedspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize']).$this->kilooctet);
                $this->remainingspace =  bab_toHtml(bab_formatSizeFile($GLOBALS['babMaxGroupSize'] - $size).$this->kilooctet);
                return true;
            }
            else
            {
                return false;
            }
        }

        }

    $temp = new showDiskSpaceTpl();
    echo bab_printTemplate($temp,"fileman.html", "diskspace");
    exit;
    }


function listFiles()
{
    global $babBody;

    class listFilesTpl extends listFiles
        {
        public $bytes;
        public $mkdir;
        public $rename;
        public $delete;
        public $directory;
        public $download;
        public $download_limit_reached;
        public $cuttxt;
        public $paste;
        public $undo;
        public $deltxt;
        public $root;
        public $refresh;
        public $nametxt;
        public $sizetxt;
        public $modifiedtxt;
        public $createdtxt;
        public $postedtxt;
        public $diskspace;
        public $hitstxt;
        public $altreadonly;
        public $rooturl;
        public $refreshurl;
        public $urldiskspace;
        public $upfolderimg;
        public $usrfolderimg;
        public $grpfolderimg;
        public $manfolderimg;
        public $xres;
        public $xcount;
        public $block;
        public $blockauth;
        public $ovfurl;
        public $ovfhisturl;
        public $ovfcommiturl;
        public $bfvwait;

        public $sFolderFormAdd;
        public $sFolderFormEdit;
        public $sFolderFormUrl;
        public $sAddFolderFormUrl;
        public $bFolderUrl;

        public $sRight;
        public $sRightUrl;
        public $bRightUrl;

        public $sCutFolder;
        public $sCutFolderUrl;
        public $bCutFolderUrl;

        public $sFolderZipUrl;

        public $sFolderZip;
        public $unziptxt;
        public $unzipconfirmtxt;

        public $bCollectiveFolder = false;
        public $bCanBrowseFolder;
        public $bCanEditFolder;
        public $bCanSetRightOnFolder;
        public $bCanCutFolder;
        public $bCanCreateFolder;
        public $bCanManageFolder;


        public $altfilelog;
        public $altfilelock;
        public $altfileunlock;
        public $altfilewrite;
        public $altbg = false;

        public $bCanManageCurrentFolder = false;
        public $bDownload = false;
        public $bUpdate = false;

        public $sUploadPath = '';

        public $iCurrentUserDelegation = 0;
        public $bDisplayDelegationSelect = false;
        public $aVisibleDelegation = array();
        public $iIdDelegation = 0;
        public $sDelegationName = '';
        public $sDelegationSelected = '';
        public $sSubmit = 'Soumettre';

        public $sWaitingFileTitle = '';

        public $bUnZip;

        public $fname;
        public $fmh_name;

        public $pathLink;

        function __construct()
        {
            parent::__construct();
            $this->bytes = bab_translate("bytes");
            $this->mkdir = bab_translate("Create");
            $this->rename = bab_translate("Rename");
            $this->delete = bab_translate("Delete");
            $this->directory = bab_translate("Directory");
            $this->download = bab_translate("Download");
            $this->download_limit_reached = bab_translate("Download limit reached");
            $this->cuttxt = bab_translate("Cut");
            $this->paste = bab_translate("Paste");
            $this->undo = bab_translate("Undo");
            $this->deltxt = bab_translate("Do you really want to delete?");
            $this->root = bab_translate("Home folder");
            $this->refresh = bab_translate("Refresh");
            $this->nametxt = bab_translate("Name");
            $this->sizetxt = bab_translate("Size");
            $this->modifiedtxt = bab_translate("Modified");
            $this->createdtxt = bab_translate("Created");
            $this->postedtxt = bab_translate("Posted by");
            $this->diskspace = bab_translate("Show disk space usage");
            $this->hitstxt = bab_translate("Hits");
            $this->altreadonly =  bab_translate("Final version");
            $this->sFolderFormAdd = bab_translate("Create a folder");
            $this->sFolderFormEdit = bab_translate("Edit folder");
            $this->sRight = bab_translate("Rights");
            $this->sCutFolder = bab_translate("Cut");
            $this->sFolderZip = bab_translate("Download ZIP folder");
            $this->altfilelog =  bab_translate("View log");
            $this->altfilelock =  bab_translate("Edit file");
            $this->altfileunlock =  bab_translate("Unedit file");
            $this->altfilewrite =  bab_translate("Commit file");
            $this->sWaitingFileTitle = bab_translate("This file is awaiting approval");
            $this->unziptxt = bab_translate("Unzip here");
            $this->unzipconfirmtxt = bab_translate("This archive is about to be extracted and all its files and subfolders will be reachable under the current folder.");

            $iId = $this->oFileManagerEnv->iId;
            $sGr = $this->oFileManagerEnv->sGr;

            $pathArray  = explode('/', $this->path);
            $lastArray = array_pop($pathArray);
            $pathString = '';
            $this->pathLink = '';

            foreach($pathArray as $path){
                if($this->pathLink == ''){
                    $pathString.= $path;
                }else{
                    $pathString.= '/' . $path;
                }

                $this->pathLink.= '/<a href="' . bab_toHtml($GLOBALS['babUrlScript'] . "?idx=list&id=".$iId."&gr=".$sGr."&path=".urlencode($pathString).'&tg=fileman') . '">' . $path . '</a>';
            }
            $this->pathLink.= '/'.$lastArray;


            if($sGr == 'N'){
                if($this->pathLink == '/'){
                    $this->pathLink = '/'.bab_translate('Private folder').$this->pathLink;
                }else{
                    $this->pathLink = '/<a href="' . bab_toHtml($GLOBALS['babUrlScript'] . "?idx=list&id=".$iId."&gr=".$sGr."&path=".'&tg=fileman') . '">'.bab_translate('Private folder').'</a>' . $this->pathLink;
                }
            }

            $this->pathLink = '<a href="' . bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list") . '">...</a>'.$this->pathLink;

            $this->rooturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=list");
            $this->refreshurl = bab_toHtml($GLOBALS['babUrlScript']."?idx=list&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path).'&tg=fileman');
            $this->urldiskspace = bab_toHtml($GLOBALS['babUrlScript']."?idx=disk&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path).'&tg=fileman');

            $order = $this->order;

            // Here we initialize refresh urls used when a column header is clicked.

            $this->nameSortAsc = $order == 'sNameA';
            $this->nameSortDesc = $order == 'sNameD';
            $this->nameSortUrl = $this->refreshurl . bab_toHtml($order == 'sNameA' ? '&order=sNameD' : '&order=sNameA');
            $this->descriptionSortAsc = $order == 'sDescriptionA';
            $this->descriptionSortDesc = $order == 'sDescriptionD';
            $this->descriptionSortUrl = $this->refreshurl . bab_toHtml($order == 'sDescriptionA' ? '&order=sDescriptionD' : '&order=sDescriptionA');
            $this->modifiedSortAsc = $order == 'sModifiedA';
            $this->modifiedSortDesc = $order == 'sModifiedD';
            $this->modifiedSortUrl = $this->refreshurl . bab_toHtml($order == 'sModifiedA' ? '&order=sModifiedD' : '&order=sModifiedA');
            $this->createdSortAsc = $order == 'sCreationA';
            $this->createdSortDesc = $order == 'sCreationD';
            $this->createdSortUrl = $this->refreshurl . bab_toHtml($order == 'sCreationA' ? '&order=sCreationD' : '&order=sCreationA');
            $this->hitsSortAsc = $order == 'iHitsA';
            $this->hitsSortDesc = $order == 'iHitsD';
            $this->hitsSortUrl = $this->refreshurl . bab_toHtml($order == 'iHitsA' ? '&order=iHitsD' : '&order=iHitsA');
            $this->versionSortAsc = $order == 'iVerMajorA';
            $this->versionSortDesc = $order == 'iVerMajorD';
            $this->versionSortUrl = $this->refreshurl . bab_toHtml($order == 'iVerMajorA' ? '&order=iVerMajorD' : '&order=iVerMajorA');
            $this->pathSortAsc = $order == 'sPathNameA';
            $this->pathSortDesc = $order == 'sPathNameD';
            $this->pathSortUrl = $this->refreshurl . bab_toHtml($order == 'sPathNameA' ? '&order=sPathNameD' : '&order=sPathNameA');
            $this->sizeSortAsc = $order == 'iSizeA';
            $this->sizeSortDesc = $order == 'iSizeD';
            $this->sizeSortUrl = $this->refreshurl . bab_toHtml($order == 'iSizeA' ? '&order=iSizeD' : '&order=iSizeA');
            $this->authorSortAsc = $order == 'iIdAuthorA';
            $this->authorSortDesc = $order == 'iIdAuthorD';
            $this->authorSortUrl = $this->refreshurl . bab_toHtml($order == 'iIdAuthorA' ? '&order=iIdAuthorD' : '&order=iIdAuthorA');
            $this->updatedbySortAsc = $order == 'iIdModifierA';
            $this->updatedbySortDesc = $order == 'iIdModifierD';
            $this->updatedbySortUrl = $this->refreshurl . bab_toHtml($order == 'iIdModifierA' ? '&order=iIdModifierD' : '&order=iIdModifierA');

            if ($order == 'sNameD') {
                $this->aFolders = array_reverse($this->aFolders);
            }
            $this->sAddFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript']."?idx=displayFolderForm&sFunction=createFolder&id=".$iId."&gr=".$sGr."&path=".urlencode($this->path).'&tg=fileman');

            $this->sCutFolderUrl = '#';
            $this->bCutFolderUrl = false;

            $this->upfolderimg = bab_printTemplate($this, "config.html", "parentfolder");
            $this->usrfolderimg = bab_printTemplate($this, "config.html", "userfolder");
            $this->grpfolderimg = bab_printTemplate($this, "config.html", "groupfolder");
            $this->manfolderimg = bab_printTemplate($this, "config.html", "managerfolder");

            $sRelativePath = $this->oFileManagerEnv->sRelativePath;
            $this->bCanManageCurrentFolder = haveRightOn($sRelativePath, BAB_FMMANAGERS_GROUPS_TBL);


            $this->bDownload = canDownload($sRelativePath);
            $this->bUpdate = canUpdate($sRelativePath);
            $this->bCanCreateFolder = canCreateFolder($sRelativePath);


            $this->bVersion = (!is_null($this->oFileManagerEnv->oFmFolder) && 'Y' === $this->oFileManagerEnv->oFmFolder->getVersioning());


            if($this->oFileManagerEnv->userIsInPersonnalFolder())
            {
                $this->sUploadPath = $this->oFileManagerEnv->getRootFmPath();
            }
            else
            {
                $this->sUploadPath = $this->oFileManagerEnv->getCollectiveRootFmPath();
            }

            $this->xcount = 0;
            if($this->bCanManageCurrentFolder)
            {
                $this->selectCuttedFiles();
            }

            $this->aVisibleDelegation = bab_getUserFmVisibleDelegations();
            // We force the All site delegation to appear in the list even if no folder is accessible.
            if (!array_key_exists(0, $this->aVisibleDelegation)) {
                $this->aVisibleDelegation = array(0 => bab_translate('Common content')) + $this->aVisibleDelegation;
            }
            $this->bDisplayDelegationSelect = (count($this->aVisibleDelegation) > 1);
            $this->iCurrentUserDelegation = bab_getCurrentUserDelegation();



            $oFirstCollectiveParent = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
            if(!is_null($oFirstCollectiveParent) && $oFirstCollectiveParent->getManualOrder() && $this->bCanManageCurrentFolder){

                global $babBody;
                $babBody->addItemMenu('displayOrderFolder', bab_translate("Order files"), $GLOBALS['babUrlScript'] .
                '?tg=fileman' .
                '&idx=displayOrderFolder' .
                '&id=' . bab_gp('id','') .
                '&gr=' . bab_gp('gr','') .
                '&path=' . urlencode(bab_gp('path','')) .
                '&iIdFolder=' . $oFirstCollectiveParent->getId());
            }
        }


        function getNextUserFmVisibleDelegation()
        {
            $aItem = each($this->aVisibleDelegation);
            if(false !== $aItem)
            {
                $this->iIdDelegation = $aItem['key'];
                $this->sDelegationName = $aItem['value'];
                $this->sDelegationSelected = '';

                if((int) $this->iCurrentUserDelegation === (int) $this->iIdDelegation)
                {
                    $this->sDelegationSelected = 'selected="selected"';
                }

                return true;
            }
            return false;
        }


        function selectCuttedFiles()
        {
            $this->oFolderFileSet->bUseAlias = false;
            $oState = $this->oFolderFileSet->aField['sState'];
            $oGroup = $this->oFolderFileSet->aField['sGroup'];
            $oIdDgOwner = $this->oFolderFileSet->aField['iIdDgOwner'];
            $oIdOwner = $this->oFolderFileSet->aField['iIdOwner'];

            $oCriteria = $oGroup->in($this->oFileManagerEnv->sGr);
            $oCriteria = $oCriteria->_and($oState->in('X'));

            if($this->oFileManagerEnv->userIsInPersonnalFolder())
            {
                $oCriteria = $oCriteria->_and($oIdOwner->in($this->oFileManagerEnv->iId));
                $oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
            }
            else
            {
                $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
            }

            $this->oFolderFileSet->select($oCriteria);
//			bab_debug($this->oFolderFileSet->getSelectQuery($oCriteria));
            $this->xres = $this->oFolderFileSet->_oResult;
            $this->xcount = $this->oFolderFileSet->count();
            $this->oFolderFileSet->bUseAlias = true;
        }

        function getNextFolder()
        {
            $aItem = each($this->aFolders);
            if(false !== $aItem)
            {
                $aItem						= $aItem['value'];
                $iIdRootFolder				= $aItem['iIdUrl'];
                $iIdFolder					= $aItem['iId'];
                $this->bCollectiveFolder	= ('Y' === $aItem['sCollective']);
                $sEncodedPath				= urlencode($this->path);
                $sEncodedName				= urlencode($aItem['sName']);
                $sUrlEncodedPath			= urlencode($aItem['sUrlPath']);
                $sGr						= $aItem['sGr'];

                $this->bCanBrowseFolder		= $aItem['bCanBrowseFolder'];
                $this->bCanEditFolder		= $aItem['bCanEditFolder'];
                $this->bCanSetRightOnFolder	= $aItem['bCanSetRightOnFolder'];
                $this->bCanCutFolder		= $aItem['bCanCutFolder'];
                $this->bCanManageFolder		= $aItem['bCanManageFolder'];

                $this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&iIdFolder=' . $iIdFolder);

                $this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sFunction=editFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);

                $this->sCutFolderUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?sAction=cutFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName.'&tg=fileman');

                $this->sFolderZipUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=zipFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);

                $this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?idx=list&id=' . $iIdRootFolder . '&gr=' . $sGr . '&path=' . $sUrlEncodedPath.'&tg=fileman');

                $this->altbg = !$this->altbg;
                $this->fname = $aItem['sName'];
                $this->fmh_name = $aItem['sName'];
                return true;
            }
            return false;
        }

        function getNextCuttedFolder()
        {
            $aItem = each($this->aCuttedDir);
            if(false !== $aItem)
            {
                $aItem						= $aItem['value'];
                $iIdRootFolder				= $aItem['iIdUrl'];
                $iIdFolder					= $aItem['iId'];
                $this->bCollectiveFolder	= ('Y' == $aItem['sCollective']);
                $sEncodedPath				= urlencode($this->path);
                $sEncodedName				= urlencode($aItem['sName']);
                $sGr						= $aItem['sGr'];

                $iIdSrcRootFolder			= $aItem['iIdSrcRootFolder'];
                $sEncodedSrcPath			= urlencode($aItem['sSrcPath']);

                $this->bCanBrowseFolder		= $aItem['bCanBrowseFolder'];
                $this->bCanEditFolder		= $aItem['bCanEditFolder'];
                $this->bCanSetRightOnFolder	= $aItem['bCanSetRightOnFolder'];
                $this->bCanCutFolder		= $aItem['bCanCutFolder'];
                $this->bCanManageFolder		= $aItem['bCanManageFolder'];


                $this->sRightUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayRightForm&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&iIdFolder=' . $iIdFolder);

                $this->sFolderFormUrl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&idx=displayFolderForm&sFunction=editFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . $sEncodedPath . '&sDirName=' . $sEncodedName . '&iIdFolder=' . $iIdFolder);

                $this->pasteurl = bab_toHtml($GLOBALS['babUrlScript'] . '?sAction=pasteFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . urlencode($this->oFileManagerEnv->sPath) .
                    '&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $sEncodedSrcPath.'&tg=fileman');

                $this->undopasteurl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=undopasteFolder&id=' . $iIdRootFolder .
                    '&gr=' . $this->oFileManagerEnv->sGr . '&path=' . urlencode($this->oFileManagerEnv->sPath) .
                    '&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $sEncodedSrcPath);

                $this->url = bab_toHtml($GLOBALS['babUrlScript'] . '?idx=list&id=' . $iIdSrcRootFolder . '&gr=' . $sGr . '&path=' . $sEncodedSrcPath.'&tg=fileman');

                $this->altbg = !$this->altbg;
                $this->fname = $aItem['sName'];
                $this->fmh_name = $aItem['sName'];
                return true;
            }
            return false;
        }

        function updateFileInfo($arr)
        {
            $this->fileimage = '';

            $iOffset = mb_strrpos($arr['name'], '.');
            if(false !== $iOffset)
            {
                $ext = mb_strtolower(mb_substr($arr['name'], $iOffset+1));
                if( !empty($ext) && empty($this->arrext[$ext]))
                    {
                    $this->arrext[$ext] = bab_printTemplate($this, "config.html", ".".$ext);
                    if( empty($this->arrext[$ext]))
                        $this->arrext[$ext] = bab_printTemplate($this, "config.html", ".unknown");
                    $this->fileimage = $this->arrext[$ext];
                    }
                else if( empty($ext))
                    {
                    $this->fileimage = bab_printTemplate($this, "config.html", ".unknown");
                    }
                else
                    $this->fileimage = $this->arrext[$ext];
            }
            else
            {
                $this->fileimage = bab_printTemplate($this, "config.html", ".unknown");
            }


            $this->fname = $arr['name'];
            $this->fmh_name = $arr['name'];

            if ($arr['size'] >= 0) {
                $this->sizef = bab_toHtml(bab_formatSizeFile($arr['size']) . ' ' . bab_translate('Kb'));
            } else {
                $this->sizef = '???';
            }

            $this->fmh_size = $this->sizef;

            $this->modified = bab_toHtml(bab_shortDate(bab_mktime($arr['modified']), true));
            $this->fmh_date_update = $this->modified;
            $this->fmh_date_creation = bab_toHtml(bab_shortDate(bab_mktime($arr['created']), true));
            $this->postedby = bab_toHtml(bab_getUserName($arr['modifiedby'] == 0? $arr['author']: $arr['modifiedby']));
            $this->fmh_updatedby = $this->postedby;
            $this->fmh_author = bab_toHtml(bab_getUserName($arr['author']));
            $this->fhits = bab_toHtml($arr['hits']);
            $this->fmh_hits = $this->fhits;
            $this->fmh_version = $this->bVersion? bab_toHtml($arr['ver_major'].'.'.$arr['ver_minor']):'';
            if( $arr['readonly'] == "Y" )
                $this->readonly = "R";
            else
                $this->readonly = "";
        }

        function getnextfile()
        {
            global $babDB;
            if (false !== $this->res && false !== ($arr = $babDB->db_fetch_array($this->res))) {
                $arrName = explode('.',$arr['name']);
                $ext = array_pop($arrName);
                if($ext == 'zip'){
                    $idObject = $this->oFileManagerEnv->iIdObject;
                    if( $this->oFileManagerEnv->sGr != 'Y' || bab_isAccessValid('bab_fmunzip_groups', $idObject) ){
                        $this->bUnZip = true;
                    }
                }else{
                    $this->bUnZip = false;
                }
                $this->altbg		= !$this->altbg;
                $iId				= $this->oFileManagerEnv->iId;
                $sGr				= $this->oFileManagerEnv->sGr;
                $this->bconfirmed	= 0;
                $this->description	= bab_toHTML($arr['description']);
                $this->fmh_description	= $this->description;
                $ufile				= urlencode($arr['name']);
                $upath				= urlencode($this->path);
                $this->fmh_path = bab_toHTML($this->path);

                $sUrlBase		= $GLOBALS['babUrlScript'] . '?tg=fileman&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath;
                $sUrlFile		= $sUrlBase . '&idf=' . $arr['id'] . '&file=' . $ufile;

                $file = BAB_FolderFileSet::getById($arr['id']);


                if ($file->downloadLimitReached()) {
                    $this->urlget = bab_toHtml('');
                } else {
                    $this->urlget = bab_toHtml($sUrlFile . '&sAction=getFile');
                }

                $this->viewurl	= bab_toHtml($sUrlFile . '&idx=viewFile');
                $this->cuturl	= bab_toHtml($sUrlFile . '&sAction=cutFile');
                $this->delurl	= bab_toHtml($sUrlFile . '&sAction=delFile');
                $this->unzipurl	= bab_toHtml($sUrlFile . '&sAction=unzipFile');
                $this->fileid	= $arr['id'];

                $this->updateFileInfo($arr);

                if ($this->bVersion) {
                    $sUrlBase		= $GLOBALS['babUrlScript'] . '?tg=filever&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath;
                    $sUrlFileId		= $sUrlBase . '&idf=' . $arr['id'];

                    $this->lastversion	= bab_toHtml($arr['ver_major'] . '.' . $arr['ver_minor']);
                    $this->ovfhisturl	= bab_toHtml($sUrlFileId . '&idx=hist');
                    $this->ovfversurl	= bab_toHtml($sUrlFileId . '&idx=lvers');

                    $this->bfvwait = false;
                    $this->blockauth = false;
                    if ($arr['edit']) {
                        $this->block = true;
                        list($lockauthor, $idfvai) = $babDB->db_fetch_array($babDB->db_query("select author, idfai from ".BAB_FM_FILESVER_TBL." where id='".$babDB->db_escape_string($arr['edit'])."'"));
                        if ($idfvai == 0 && $lockauthor == $GLOBALS['BAB_SESS_USERID']) {
                            $this->blockauth = true;
                        }

                        if ($idfvai != 0 && $this->buaf) {
                            $this->bfvwait = true;
                            $this->bupdate = true;
                        }

                        $this->ovfurl = bab_toHtml($sUrlFileId . '&idx=unlock');
                        if ($this->bfvwait) {
                            $this->ovfcommiturl = bab_toHtml($sUrlFileId . '&idx=conf');
                        } else {
                            $this->ovfcommiturl = bab_toHtml($sUrlFileId . '&idx=commit');
                        }
                    } else {
                        $this->block = false;
                        $this->ovfurl = bab_toHtml($sUrlFileId . '&idx=lock');
                    }
                }
                return true;
            }
            return false;
        }

        function getnextwfile()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->countwf) {
                $iId = $this->oFileManagerEnv->iId;
                $sGr = $this->oFileManagerEnv->sGr;

                $this->altbg = !$this->altbg;
                $arr = $babDB->db_fetch_array($this->reswf);
                $this->bconfirmed = 1;
                $this->updateFileInfo($arr);
                $this->description = bab_toHTML($arr['description']);
                $this->fmh_description	= $this->description;
                $ufile = urlencode($arr['name']);
                $upath = urlencode($this->path);
                $this->fmh_path = bab_toHTML($this->path);
                $this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
                $this->viewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
                $this->urlget = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile.'&idf='.$arr['id']);
                $this->cuturl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=cutFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile);
                $this->delurl = bab_toHtml($GLOBALS['babUrlScript'] . '?tg=fileman&sAction=delFile&id=' . $iId . '&gr=' . $sGr . '&path=' . $upath . '&file=' . $ufile);
                $i++;
                return true;
            }
            return false;
        }


        function getnextxfile(&$bSkip)
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->xcount)
            {
                $iId = $this->oFileManagerEnv->iId;
                $sGr = $this->oFileManagerEnv->sGr;

                $this->altbg = !$this->altbg;
                $arr = $babDB->db_fetch_array($this->xres);
                $this->bconfirmed = 0;

                $iIdSrcRootFolder = 0;
                if($this->oFileManagerEnv->userIsInCollectiveFolder())
                {
                    $oFmFolder = null;
                    BAB_FmFolderHelper::getInfoFromCollectivePath($arr['path'], $iIdSrcRootFolder, $oFmFolder);
                }
                else if($this->oFileManagerEnv->userIsInPersonnalFolder())
                {
                    $iIdSrcRootFolder = $iId;
                }

//				bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sSrcPath ==> ' . $arr['path'] . ' sTrgPath ==> ' . $this->path);

                $bCanPaste = canPasteFile($iIdSrcRootFolder, $arr['path'], $iId, $this->path, $arr['name']);
                $bSkip = !$bCanPaste;
                if($bCanPaste)
                {
                    $this->updateFileInfo($arr);
                    $this->description = bab_toHTML($arr['description']);
                    $this->fmh_description	= $this->description;
                    $ufile = urlencode($arr['name']);
                    $upath = '';
                    if(mb_strlen(trim($arr['path'])) > 0)
                    {
                        $upath = urlencode((string) mb_substr($arr['path'], 0, -1));
                    }
                    $this->fmh_path = '';//bab_toHTML($upath);
                    $this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=upd&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile);
                    $this->urlget = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$iId."&gr=".$sGr."&path=".$upath."&file=".$ufile.'&idf='.$arr['id']);

                    $this->pasteurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&sAction=pasteFile&id=' . $iId . '&gr=' . $sGr .
                        '&path=' . urlencode($this->path) . '&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $upath . '&file=' . $ufile);
                    $this->undopasteurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&sAction=undopasteFile&id=' . $iId . '&gr=' . $sGr .
                        '&path=' . urlencode($this->path) . '&iIdSrcRootFolder=' . $iIdSrcRootFolder . '&sSrcPath=' . $upath . '&file=' . $ufile);
                }
                $i++;
                return true;
            }
            return false;
        }

    }


    $oFileManagerEnv =& getEnvObject();


    $babBody->title = bab_translate("File manager");
    $babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath).'&tg=fileman');

    if('Y' === $oFileManagerEnv->sGr)
    {
        if(0 !== $oFileManagerEnv->iId)
        {
            $GLOBALS['babWebStat']->addFolder($oFileManagerEnv->iId);
        }
    }

    $sParentPath = $oFileManagerEnv->sRelativePath;

    if(canUpload($sParentPath)) {
        $babBody->addItemMenu('add', bab_translate("Upload"), $GLOBALS['babUrlScript']."?idx=displayAddFileForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath).'&tg=fileman');
    }

    if (haveRightOn($sParentPath, BAB_FMMANAGERS_GROUPS_TBL)) {
        $babBody->addItemMenu('trash', bab_translate("Trash"), $GLOBALS['babUrlScript']."?idx=trash&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath).'&tg=fileman');
    }

    $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
    $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'scriptaculous/scriptaculous.js');

    $temp = new listFilesTpl();

    $babBody->babecho(bab_printTemplate($temp, 'fileman.html', 'fileslist'));
    return $temp->count;
}




/**
 * Displays the download history of a file.
 */
function displayDownloadHistory()
{
    global $babBody;

    class displayDownloadHistoryTpl
    {
        function __construct(BAB_FolderFile $file)
        {
            global $babDB;

            $this->t_user = bab_translate("User");
            $this->t_nb_downloads = bab_translate("Nb downloads");
            $this->t_date_time = bab_translate("Date/time");

            $this->folderFileSet = new BAB_FolderFileSet();
            $this->idField = $this->folderFileSet->aField['iId'];

            $sql = 'SELECT id_user, id_file, COUNT(*) AS nb_downloads
                    FROM bab_fm_files_download_history
                    WHERE id_file=' . $babDB->quote($file->getId()) . '
                    GROUP BY `id_user`';

            $downloads = $babDB->db_query($sql);

            $this->downloads = array();
            while ($download = $babDB->db_fetch_assoc($downloads)) {
                $download['user_name'] = bab_getUserName($download['id_user'], true);
                $this->downloads[] = $download;
            }

            bab_Sort::asort($this->downloads, 'user_name', bab_Sort::CASE_INSENSITIVE);
        }

        function getNextUser()
        {
            global $babDB;

            if (list(,$download) = each($this->downloads)) {
                $this->user_name = bab_toHtml($download['user_name']);
                $this->nb_downloads = bab_toHtml($download['nb_downloads']);

                $sql = 'SELECT `date`
                    FROM bab_fm_files_download_history
                    WHERE id_file=' . $babDB->quote($download['id_file']) . '
                    AND id_user=' . $babDB->quote($download['id_user']) . '
                    ORDER BY `date` DESC';

                $this->downloadDates = $babDB->db_query($sql);

                return true;
            }
            return false;
        }

        function getNextDownload()
        {
            global $babDB;

            if ($download = $babDB->db_fetch_assoc($this->downloadDates)) {
                $this->download_date = bab_toHtml(bab_shortDate(bab_mktime($download['date'])));
                return true;
            }
            return false;
        }

    }

    $fileId = (int) bab_rp('idf', null);

    if (is_null($fileId)) {
        $babBody->addError(bab_translate("The file is not specified"));
        return false;
    }

    $folderFileSet = new BAB_FolderFileSet();
    $idField = $folderFileSet->aField['iId'];
    $file = $folderFileSet->get($idField->in($fileId));
    if (is_null($file)) {
        $babBody->addError(bab_translate("The file is not on the server"));
        return false;
    }

    $template = new displayDownloadHistoryTpl($file);
    $babBody->setTitle(sprintf(bab_translate("Download history for %s"), $file->getName()));

    $oFileManagerEnv =& getEnvObject();
    $enable = ($oFileManagerEnv->oFmFolder->getDownloadHistory() == 'Y');
    $sParentPath = $oFileManagerEnv->sRelativePath;
    $right = haveRight($sParentPath, BAB_FMDOWNLOADHISTORY_GROUPS_TBL);
    if($right && $enable){
        $babBody->babpopup(bab_printTemplate($template, 'fileman.html', 'download_history'));
    }else{
        $babBody->babpopup(bab_translate('Access denied'));
    }
}


/**
 * Displays a page with a form to upload one or more files to the file manager.
 * The actual form fields are fetched by ajax (@see getUploadBlock).
 */
function displayAddFileForm()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();
    $babBody->title = bab_translate("Upload file to") . ' ' . $oFileManagerEnv->sRelativePath;

    if (!canUpload($oFileManagerEnv->sRelativePath)) {
        $babBody->msgerror = bab_translate("Access denied");
        return;
    }

    $babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'] .
        '?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId . "&gr=" . $oFileManagerEnv->sGr .
        '&path=' . urlencode($oFileManagerEnv->sPath));

    $babBody->addItemMenu('displayAddFileForm', bab_translate("Upload"), $GLOBALS['babUrlScript'] .
        '?tg=fileman&idx=displayAddFileForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr .
        '&path=' . urlencode($oFileManagerEnv->sPath));

    if (canManage($oFileManagerEnv->sRelativePath)) {
        $babBody->addItemMenu('trash', bab_translate("Trash"), $GLOBALS['babUrlScript'] .
            '?tg=fileman&idx=trash&id=' . $oFileManagerEnv->iId . "&gr=" . $oFileManagerEnv->sGr .
            '&path=' . urlencode($oFileManagerEnv->sPath));
    }

    class displayAddFileFormTpl
    {
        public $add;
        public $path;
        public $id;
        public $gr;
        public $maxfilesize;
        public $descval;
        public $keysval;
        public $field;
        public $fieldname;
        public $fieldval;
        public $count;
        public $res;

        function __construct()
        {
            global $babBody;
            $this->add = bab_translate("Add");
            $this->t_warnmaxsize = bab_translate("File size must not exceed");
            $this->t_add_field = bab_translate("Attach another file");
            $this->t_remove_field = bab_translate("Remove");
            if ($GLOBALS['babMaxFileSize'] < 1000000) {
                $this->maxsize = bab_formatSizeFile($GLOBALS['babMaxFileSize']) . ' ' . bab_translate("Kb");
            } else {
                $this->maxsize = floor($GLOBALS['babMaxFileSize'] / 1000000 ) . ' ' . bab_translate("Mb");
            }

            $description = bab_pp('description', null);
            $keywords = bab_pp('keywords', null);

            $oFileManagerEnv =& getEnvObject();

            $this->id = $oFileManagerEnv->iId;
            $this->path = bab_toHtml($oFileManagerEnv->sPath);
            $this->gr = $oFileManagerEnv->sGr;

            $this->maxfilesize = $GLOBALS['babMaxFileSize'];
            $this->descval = (!is_null($description)) ? bab_toHtml($description[0]) : '';
            $this->keysval = (!is_null($keywords)) ? bab_toHtml($keywords[0]) : '';

            $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
            $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'scriptaculous/scriptaculous.js');
            $babBody->addStyleSheet('ajax.css');

            $oGetHtmlUploadBlock = new BAB_GetHtmlUploadBlock($this->id, $this->gr);
            $this->sUploadBlock = $oGetHtmlUploadBlock->getHtml();
        }
    }

    $temp = new displayAddFileFormTpl();
    $babBody->babecho(bab_printTemplate($temp, 'fileman.html', 'addfile'));
}



/*
 * Called in ajax by the filemanager on upload form
 */
function getUploadBlock()
{
    $oFileManagerEnv =& getEnvObject();

    if(!canUpload($oFileManagerEnv->sRelativePath))
    {
        die();
    }

    $iIdRootFolder	= (int) bab_rp('id', 0);
    $sGr			= (string) bab_rp('gr', '');

    $oGetHtmlUploadBlock = new BAB_GetHtmlUploadBlock($iIdRootFolder, $sGr);
//	header('Content-type: text/html; charset=' . bab_charset::getIso());
    die($oGetHtmlUploadBlock->getHtml());
}



/**
 * Updates the download history for the specified file.
 *
 * @param BAB_FolderFile $file
 */
function updateDownloadHistory(BAB_FolderFile $file)
{
    global $babDB;

    $filePathname = $file->getPathName();
    $firstCollectiveFolder = BAB_FmFolderSet::getFirstCollectiveFolder($filePathname);

    // Checks that download history is active on the file's owner folder.
    if (!is_null($firstCollectiveFolder) && $firstCollectiveFolder->getDownloadHistory() == 'Y') {
        $sql = 'INSERT INTO bab_fm_files_download_history(id_file, id_user, `date`)
                VALUES(' . $babDB->quote($file->getId()) . ', ' . $babDB->quote($GLOBALS['BAB_SESS_USERID']) . ', NOW())';

        $babDB->db_query($sql);
    }

}


/**
 * Outputs the specified file.
 */
function getFile()
{
    global $babBody;

    $inl = bab_rp('inl', false);
    if (false === $inl) {
        $inl = bab_getFileContentDisposition() == 1;
    }

    $iIdFile = (int) bab_rp('idf', 0);

    //OVML ne positionne pas la delegation
    $oFolderFileSet = new BAB_FolderFileSet();
    $oId = $oFolderFileSet->aField['iId'];
    $oFolderFile = $oFolderFileSet->get($oId->in($iIdFile));
    if (is_null($oFolderFile)) {
        $babBody->msgerror = bab_translate("The file is not on the server");
        return;
    }

    if ($oFolderFile->downloadLimitReached()) {
        $babBody->msgerror = sprintf(bab_translate("The download limit (%s) has been reached for file '%s'"),
                                        $oFolderFile->getMaxDownloads(), $oFolderFile->getName());
        return;
    }

    //Peut etre vient-on de l'OVML
    $iCurrentDelegation = bab_getCurrentUserDelegation();
    bab_setCurrentUserDelegation($oFolderFile->getDelegationOwnerId());

    $oFileManagerEnv =& getEnvObject();

    if (!canDownload($oFileManagerEnv->sRelativePath)) {
        bab_setCurrentUserDelegation($iCurrentDelegation);

        $babBody->msgerror = bab_translate("Access denied");
        return;
    }

    $oFolderFile->setHits($oFolderFile->getHits() + 1);
    $oFolderFile->setDownloads($oFolderFile->getDownloads() + 1);
    $oFolderFile->save();

    updateDownloadHistory($oFolderFile);

    $GLOBALS['babWebStat']->addFilesManagerFile($oFolderFile->getId());

    $sUploadPath = '';
    if (!$oFileManagerEnv->userIsInPersonnalFolder()) {
        $sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
    } else {
        $sUploadPath = $oFileManagerEnv->getRootFmPath();
    }

    $sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();

    if (!file_exists($sFullPathName)) {
        bab_setCurrentUserDelegation($iCurrentDelegation);
        $babBody->msgerror = bab_translate("The file is not on the server");
        return;
    }

    require_once dirname(__FILE__).'/utilit/path.class.php';
    bab_downloadFile(new bab_Path($sFullPathName), null, $inl);

}



function cutFile()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if(!canCutFile($oFileManagerEnv->sRelativePath))
    {
        $babBody->msgerror = bab_translate("Access denied");
        return false;
    }

    $file = bab_rp('file');

    $oFolderFileSet = new BAB_FolderFileSet();

    $oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
    $oGroup =& $oFolderFileSet->aField['sGroup'];
    $oState =& $oFolderFileSet->aField['sState'];
    $oPathName =& $oFolderFileSet->aField['sPathName'];
    $oName =& $oFolderFileSet->aField['sName'];
    $oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];

    $oCriteria = $oIdOwner->in($oFileManagerEnv->iIdObject);
    $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
    $oCriteria = $oCriteria->_and($oState->in(''));
    $oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
    $oCriteria = $oCriteria->_and($oName->in($file));
    $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

    $oFolderFile = $oFolderFileSet->get($oCriteria);
    if(!is_null($oFolderFile))
    {
        $oFolderFile->setState('X');
        $oFolderFile->save();
        return true;
    }
    return false;
}

function delFile()
{
    global $babBody;

//	bab_rp('file'), $id, $gr, $path, $bmanager

    $oFileManagerEnv =& getEnvObject();

    if(!canDelFile($oFileManagerEnv->sRelativePath))
    {
        $babBody->msgerror = bab_translate("Access denied");
        return false;
    }

    $sFilename = (string) bab_rp('file', '');

    $oFolderFileSet = new BAB_FolderFileSet();

    $oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
    $oGroup =& $oFolderFileSet->aField['sGroup'];
    $oState =& $oFolderFileSet->aField['sState'];
    $oPathName =& $oFolderFileSet->aField['sPathName'];
    $oName =& $oFolderFileSet->aField['sName'];

    $oCriteria = $oIdOwner->in($oFileManagerEnv->iIdObject);
    $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
    $oCriteria = $oCriteria->_and($oState->in(''));
    $oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
    $oCriteria = $oCriteria->_and($oName->in($sFilename));

    $oFolderFile = $oFolderFileSet->get($oCriteria);
    if(!is_null($oFolderFile))
    {
        $oFolderFile->setState('D');
        $oFolderFile->save();
        return true;
    }
    return false;
}

function unzipFile()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if(!canUpload($oFileManagerEnv->sRelativePath) && ( bab_rp('gr')== 'N' || !canUnzip($oFileManagerEnv->sRelativePath)))
    {
        $babBody->msgerror = bab_translate("Access denied");
        return false;
    }

    $file = bab_rp('file');

    $oFolderFileSet = new BAB_FolderFileSet();

    $oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
    $oGroup =& $oFolderFileSet->aField['sGroup'];
    $oState =& $oFolderFileSet->aField['sState'];
    $oPathName =& $oFolderFileSet->aField['sPathName'];
    $oName =& $oFolderFileSet->aField['sName'];
    $oIdDgOwner =& $oFolderFileSet->aField['iIdDgOwner'];

    $oCriteria = $oIdOwner->in($oFileManagerEnv->iIdObject);
    $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
    $oCriteria = $oCriteria->_and($oState->in(''));
    $oCriteria = $oCriteria->_and($oPathName->in($oFileManagerEnv->sRelativePath));
    $oCriteria = $oCriteria->_and($oName->in($file));
    $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

    $oFolderFile = $oFolderFileSet->get($oCriteria);
    if(bab_rp('gr','') == '' || bab_rp('idf','') == ''){
        return false;
    }
    if(!is_null($oFolderFile))
    {
        $arrName = explode('.',$oFolderFile->getName());
        $ext = array_pop($arrName);
        if($ext == 'zip'){
            /* @var $Zip Func_Archive_Zip */
            $Zip = bab_functionality::get('Archive/Zip');

            $sUploadPath = '';
            if (!$oFileManagerEnv->userIsInPersonnalFolder()) {
                $sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
            } else {
                $sUploadPath = $oFileManagerEnv->getRootFmPath();
            }
            $sUploadPath.= $oFolderFile->getPathName();

            $babPath = new bab_Path($GLOBALS['babUploadPath'],'tmp',session_id());
            if($babPath->isDir()){
                $babPath->deleteDir();
            }
            $babPath->createDir();

            if(filesize($sUploadPath.$oFolderFile->getName()) > $GLOBALS['babMaxZipSize']){
                $babBody->addError(bab_translate("The ZIP file size exceed the limit configured for the file manager"));
                return false;
            }

            $Zip->open($sUploadPath.$oFolderFile->getName());
            $Zip->extractTo($babPath->tostring());
            $Zip->close();

            if($babPath->isDir()){

                $unzipSize = getDirSize($babPath->tostring());
                if($unzipSize +  $oFileManagerEnv->getFMTotalSize() > $GLOBALS['babMaxTotalSize']){
                    $babBody->addError(bab_translate("The file size exceed the limit configured for the file manager"));
                }else{
                    if($GLOBALS['babQuotaFM']!= 0
                            && ( ($unzipSize +  $oFileManagerEnv->getFMTotalSize()) > ($GLOBALS['babMaxTotalSize']*$GLOBALS['babQuotaFM']/100))
                            && ( ($oFileManagerEnv->getFMTotalSize()) < ($GLOBALS['babMaxTotalSize']*$GLOBALS['babQuotaFM']/100))){
                        bab_notifyAdminQuota();
                    }
                    if($GLOBALS['babQuotaFM']!= 0 && ( ($unzipSize +  $oFileManagerEnv->getFMTotalSize()) > ($GLOBALS['babMaxTotalSize']*$GLOBALS['babQuotaFM']/100))){
                        bab_notifyAdminQuota(true);
                    }
                    $return = bab_moveUnzipFolder($babPath, $oFolderFile->getPathName(), $oFileManagerEnv->getRootFmPath());
                    if(!$return){
                        $babBody->addError(bab_translate("Incomplete unzipping"));
                        return false;
                    }
                    header('location: '. $GLOBALS['babUrl'] . 'index.php?tg=fileman&idx=list&id=' . bab_rp('id') . '&gr=' . bab_rp('gr') . '&path=' . bab_rp('path'));
                }

                $babPath->deleteDir();
            }

            return true;
        }
    }
    return false;
}


function bab_moveUnzipFolder(bab_Path $source, $destination, $absolutePath){
    $return = true;
    foreach($source as $babPath){
        if($babPath->isDir()){
            $currentBabPath = new bab_Path($absolutePath, $destination, $babPath->getBasename());
            $currentBabPath->createDir();
            $currentBabPath = new bab_Path($destination, $babPath->getBasename());

            $returntmp = bab_moveUnzipFolder($babPath, $currentBabPath->tostring(), $absolutePath);
            if($return){
                $return = $returntmp;
            }
        }else{
            $bgroup = false;
            $id = $GLOBALS['BAB_SESS_USERID'];
            if(bab_rp('gr') == 'Y'){
                $bgroup = true;
                $id = bab_rp('id');
            }
            if(bab_charset::getDatabase() == 'utf8'){
                if(!mb_check_encoding($babPath->getBasename(), 'UTF-8')){
                    global $babBody;
                    $babBody->addError(sprintf(bab_translate('Can not unzip file: %s'), mb_convert_encoding($babPath->getBasename(), 'UTF-8')));
                    $return = false;
                    continue;
                }
            }
            $fmFile = bab_FmFile::move($babPath->tostring());
            $returntmp = bab_importFmFile($fmFile, $id, $destination, $bgroup, false);
            if($return){
                $return = $returntmp;
            }
        }
    }
    return $return;
}


function pasteFile()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $iIdSrcRootFolder	= (int) bab_rp('iIdSrcRootFolder', 0);
    $iIdTrgRootFolder	= $oFileManagerEnv->iId;
    $sSrcPath			= (string) bab_rp('sSrcPath', '');
    $sTrgPath			= $oFileManagerEnv->sPath;
    $sFileName			=  (string) bab_rp('file', '');
    $sUpLoadPath		= $oFileManagerEnv->getRootFmPath();

    if(canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $sFileName))
    {
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Paste OK');

        $iOldIdOwner		= $iIdSrcRootFolder;
        $iNewIdOwner		= $iIdTrgRootFolder;
        $sOldRelativePath	= '';
        $sNewRelativePath	= '';

        if($oFileManagerEnv->userIsInPersonnalFolder())
        {
            $sOldEndPath = (mb_strlen(trim($sSrcPath)) > 0) ? '/' : '';
            $sNewEndPath = (mb_strlen(trim($sTrgPath)) > 0) ? '/' : '';

            $sOldRelativePath = $sSrcPath . $sOldEndPath;
            $sNewRelativePath = $sTrgPath . $sNewEndPath;
        }
        else if($oFileManagerEnv->userIsInCollectiveFolder())
        {
            $oFmFolder = null;
            BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdSrcRootFolder, $sSrcPath, $iOldIdOwner, $sOldRelativePath, $oFmFolder);
            BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdTrgRootFolder, $sTrgPath, $iNewIdOwner, $sNewRelativePath, $oFmFolder);
        }

        $sOldFullPathName = $sUpLoadPath . $sOldRelativePath . $sFileName;
        $sNewFullPathName = $sUpLoadPath . $sNewRelativePath . $sFileName;

//		bab_debug('sFileName ==> ' . $sFileName . ' iOldIdOwner ==> ' . $iOldIdOwner .
//			' sOldRelativePath ==> ' . $sOldRelativePath . ' iNewIdOwner ==> ' . $iNewIdOwner .
//			' sNewRelativePath ==> ' . $sNewRelativePath);
//
//		bab_debug('sOldFullPathName ==> ' . $sUpLoadPath . $sOldRelativePath . $sFileName);
//		bab_debug('sNewFullPathName ==> ' . $sUpLoadPath . $sNewRelativePath . $sFileName);
//		bab_debug('sUpLoadPath ==> ' . $sUpLoadPath);

        $oFolderFileSet	= new BAB_FolderFileSet();
        $oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];
        $oGroup			=& $oFolderFileSet->aField['sGroup'];
        $oPathName		=& $oFolderFileSet->aField['sPathName'];
        $oName			=& $oFolderFileSet->aField['sName'];
        $oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];

        if($sOldFullPathName === $sNewFullPathName)
        {
            $oCriteria = $oIdOwner->in($iOldIdOwner);
            $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
            $oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
            $oCriteria = $oCriteria->_and($oName->in($sFileName));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

            $oFolderFile = $oFolderFileSet->get($oCriteria);
            if(!is_null($oFolderFile))
            {
                $oFolderFile->setState('');
                $oFolderFile->save();
                return true;
            }
        }

        $totalsize = getDirSize($oFileManagerEnv->getCurrentFmRootPath());
        $filesize = filesize($sOldFullPathName);
        if($filesize + $totalsize > ($oFileManagerEnv->sGr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
        {
            $babBody->msgerror = bab_translate("Cannot paste file: The target folder does not have enough space");
            return false;
        }

        if(rename($sOldFullPathName, $sNewFullPathName))
        {
            $oCriteria = $oIdOwner->in($iOldIdOwner);
            $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
            $oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
            $oCriteria = $oCriteria->_and($oName->in($sFileName));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

            $oFolderFile = $oFolderFileSet->get($oCriteria);
            if(!is_null($oFolderFile))
            {
                $oFolderFile->setState('');
                $oFolderFile->setOwnerId($iNewIdOwner);
                $oFolderFile->setPathName($sNewRelativePath);
                $oFolderFile->save();

                if(is_dir($sUpLoadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/'))
                {
                    if(!is_dir($sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/'))
                    {
                        bab_mkdir($sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER, $GLOBALS['babMkdirMode']);
                    }
                }

                $oFolderFileVersionSet = new BAB_FolderFileVersionSet();
                $oIdFile =& $oFolderFileVersionSet->aField['iIdFile'];

                $sFn = $sFileName;
                $oFolderFileVersionSet->select($oIdFile->in($oFolderFile->getId()));
                while(null !== ($oFolderFileVersion = $oFolderFileVersionSet->next()))
                {
                    $sFileName = $oFolderFileVersion->getMajorVer() . ',' . $oFolderFileVersion->getMinorVer() . ',' . $sFn;
                    $sSrc = $sUpLoadPath . $sOldRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
                    $sTrg = $sUpLoadPath . $sNewRelativePath . BAB_FVERSION_FOLDER . '/' . $sFileName;
                    @rename($sSrc, $sTrg);
                }
            }
            return true;
        }
    }
    else
    {
        //bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Cannot Paste');
        $babBody->msgerror = bab_translate("Cannot paste file");
        return false;
    }
    return;
}

function undopasteFile()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $iIdSrcRootFolder	= (int) bab_rp('iIdSrcRootFolder', 0);
    $iIdTrgRootFolder	= $iIdSrcRootFolder;
    $sSrcPath			= (string) bab_rp('sSrcPath', '');
    $sTrgPath			= $sSrcPath;
    $sFileName			=  (string) bab_rp('file', '');
    $sUpLoadPath		= $oFileManagerEnv->getRootFmPath();

    if(canPasteFile($iIdSrcRootFolder, $sSrcPath, $iIdTrgRootFolder, $sTrgPath, $sFileName))
    {
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Paste OK');

        $iOldIdOwner		= $iIdSrcRootFolder;
        $iNewIdOwner		= $iIdTrgRootFolder;
        $sOldRelativePath	= '';
        $sNewRelativePath	= '';

        if($oFileManagerEnv->userIsInPersonnalFolder())
        {
            $sOldEndPath = (mb_strlen(trim($sSrcPath)) > 0) ? '/' : '';
            $sNewEndPath = (mb_strlen(trim($sTrgPath)) > 0) ? '/' : '';

            $sOldRelativePath = $sSrcPath . $sOldEndPath;
            $sNewRelativePath = $sTrgPath . $sNewEndPath;
        }
        else if($oFileManagerEnv->userIsInCollectiveFolder())
        {
            $oFmFolder = null;
            BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdSrcRootFolder, $sSrcPath, $iOldIdOwner, $sOldRelativePath, $oFmFolder);
            BAB_FmFolderHelper::getFileInfoForCollectiveDir($iIdTrgRootFolder, $sTrgPath, $iNewIdOwner, $sNewRelativePath, $oFmFolder);
        }

        $sOldFullPathName = $sUpLoadPath . $sOldRelativePath . $sFileName;
        $sNewFullPathName = $sUpLoadPath . $sNewRelativePath . $sFileName;

        $oFolderFileSet	= new BAB_FolderFileSet();
        $oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];
        $oGroup			=& $oFolderFileSet->aField['sGroup'];
        $oPathName		=& $oFolderFileSet->aField['sPathName'];
        $oName			=& $oFolderFileSet->aField['sName'];
        $oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];

        if($sOldFullPathName === $sNewFullPathName)
        {
            $oCriteria = $oIdOwner->in($iOldIdOwner);
            $oCriteria = $oCriteria->_and($oGroup->in($oFileManagerEnv->sGr));
            $oCriteria = $oCriteria->_and($oPathName->in($sOldRelativePath));
            $oCriteria = $oCriteria->_and($oName->in($sFileName));
            $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

            $oFolderFile = $oFolderFileSet->get($oCriteria);
            if(!is_null($oFolderFile))
            {
                $oFolderFile->setState('');
                $oFolderFile->save();
                return true;
            }
        }
    }
    else
    {
        //bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' Cannot Paste');
        $babBody->msgerror = bab_translate("Cannot undo paste file");
        return false;
    }
    return false;
}


/**
 * Form displayed when we clic on the name of a file in the filemanager
 */
function viewFile()
{
    global $babBody, $BAB_SESS_USERID;

    class ViewFileTpl
    {
        public $name;
        public $description;
        public $keywords;
        public $add;
        public $attribute;
        public $path;
        public $id;
        public $gr;
        public $yes;
        public $no;
        public $descval;
        public $keysval;
        public $descvalhtml;
        public $keysvalhtml;
        public $confirm;
        public $confirmno;
        public $confirmyes;
        public $idf;

        public $fmodified;
        public $fpostedby;
        public $fmodifiedtxt;
        public $fpostedbytxt;
        public $fcreatedtxt;
        public $fcreated;
        public $fmodifiedbytxt;
        public $fmodifiedby;
        public $fsizetxt;
        public $fsize;
        public $movetofolder;
        public $oFmFolderSet = null;

        public $field;
        public $resff;
        public $countff;
        public $fieldval;
        public $fieldid;
        public $fieldvalhtml;

        public $bUseKeyword = false;

        function __construct($oFmFolder, $oFolderFile, $bmanager, $bdownloadhistoryright, $access, $bconfirm, $bupdate, $bdownload, $bversion)
        {
            global $babBody, $babDB;

            $this->access = $access;

            if (!$access) {
                $babBody->title = bab_translate("Access denied");
                return;
            }
            $oFileManagerEnv =& getEnvObject();

            $this->bmanager = $bmanager;
            $this->bdownloadhistoryright = $bdownloadhistoryright;
            $this->bconfirm = $bconfirm;
            $this->bupdate = $bupdate;
            $this->bdownload = $bdownload;
            if ($bconfirm || $bmanager || $bupdate) {
                $this->bsubmit = true;
            } else {
                $this->bsubmit = false;
            }
            $this->idf = $oFolderFile->getId();

            $this->maxDownloads	= bab_translate("Maximum number of downloads");
            $this->description = bab_translate("Description");
            $this->t_keywords = bab_translate("Keywords");
            $this->keywords = bab_translate("Keywords");
            $this->notify = bab_translate("Notify members group");
            $this->t_yes = bab_translate("Yes");
            $this->t_no = bab_translate("No");
            $this->t_change_all = bab_translate("Change status for all versions");
            $this->tabIndexStatus = array(BAB_INDEX_STATUS_NOINDEX, BAB_INDEX_STATUS_INDEXED, BAB_INDEX_STATUS_TOINDEX);

            $this->id = $oFileManagerEnv->iId;

            $this->gr = $oFolderFile->getGroup();
            $this->bUseKeyword = ('Y' == $oFolderFile->getGroup());
            $this->path = bab_toHtml($oFileManagerEnv->sPath);
            $this->file = bab_toHtml($oFolderFile->getName());
            $GLOBALS['babBody']->setTitle($oFolderFile->getName() . (($bversion == 'Y') ? ' (' . $oFolderFile->getMajorVer() . '.' . $oFolderFile->getMinorVer() . ')' : '' ));
            $this->descval = $oFolderFile->getDescription();
            $this->descvalhtml = bab_toHtml($oFolderFile->getDescription());

            $this->maxdownloadsval = $oFolderFile->getMaxDownloads();
            $this->current_downloads = bab_toHtml(sprintf(bab_translate("Current downloads: %s"), $oFolderFile->getDownloads()));


            require_once dirname(__FILE__) . '/utilit/tagApi.php';

            $this->keysval = '';
            $oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

            $oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $oFolderFile->getId()));
            $oIterator->orderAsc('tag_name');
            foreach($oIterator as $oTag)
            {
                $this->keysval .= $oTag->getName() . ', ';
            }

            $this->keysvalhtml = bab_toHtml($this->keysval);

            $this->fsizetxt = bab_translate("Size");

            $sUploadPath = '';
            if (!$oFileManagerEnv->userIsInPersonnalFolder()) {
                $sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
            } else {
                $sUploadPath = $oFileManagerEnv->getRootFmPath();
            }

            $fullpath = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
            if (file_exists($fullpath))
            {
                $fstat = stat($fullpath);
                $this->fsize = bab_toHtml(bab_formatSizeFile($fstat[7]) . ' ' . bab_translate("Kb") . ' ( ' . bab_formatSizeFile($fstat[7], false) . ' ' . bab_translate("Bytes") . ' )');

            } else {
                $this->fsize = '???';
            }

            $this->fmodifiedtxt = bab_translate("Modified");
            $this->fmodified = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getModifiedDate()), true));
            $this->fmodifiedbytxt = bab_translate("Modified by");
            $this->fmodifiedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId()));
            $this->fcreatedtxt = bab_translate("Created");
            $this->fcreated = bab_toHtml(bab_shortDate(bab_mktime($oFolderFile->getCreationDate()), true));
            $this->fpostedbytxt = bab_translate("Posted by");
            $this->fpostedby = bab_toHtml(bab_getUserName($oFolderFile->getModifierId() == 0 ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId()));

            $this->geturl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&sAction=getFile&id='.$this->id.'&gr='.$oFolderFile->getGroup().'&path='.urlencode($oFileManagerEnv->sPath).'&file='.urlencode($oFolderFile->getName()).'&idf='.$oFolderFile->getId());
            $this->download_history_url = bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=displayDownloadHistory&id='.$this->id.'&gr='.$oFolderFile->getGroup().'&path='.urlencode($oFileManagerEnv->sPath).'&file='.urlencode($oFolderFile->getName()).'&idf='.$oFolderFile->getId());

            $this->download = bab_translate("Download");
            $this->download_history = bab_translate("View download history");

            $this->file = bab_translate("File");
            $this->name = bab_translate("Name");
            $this->nameval = bab_toHtml($oFolderFile->getName());
            $this->attribute = bab_translate("Final version");
            if ('Y' === $oFolderFile->getReadOnly()) {
                $this->readonlySelected = true;
                if ($this->bupdate) {
                    $this->bupdate = false;
                }
            } else {
                $this->readonlySelected = false;
            }

            $this->confirm = bab_translate("Confirm");
            if ('N' === $oFolderFile->getConfirmed()) {
                $this->confirmyes = 'selected';
                $this->confirmno = '';
            } else {
                $this->confirmno = 'selected';
                $this->confirmyes = '';
            }

            $this->update= bab_translate("Update");
            $this->yes = bab_translate("Yes");
            $this->no = bab_translate("No");
            $this->bviewnf = false;

            $this->versions = false;
            $this->yesnfselected = '';
            $this->nonfselected = '';
            $this->countff = 0;

            if (!is_null($oFmFolder)) {
                if ('Y' === $oFmFolder->getVersioning()) {
                    $this->versions = true;
                }

                if ('Y' === $oFolderFile->getGroup() && $this->bupdate) {
                    if ('N' === $oFmFolder->getFileNotify()) {
                        $this->nonfselected = 'selected';
                        $this->yesnfselected = '';
                    } else {
                        $this->yesnfselected = 'selected';
                        $this->nonfselected = '';
                    }

                    $this->bviewnf = true;

                }

                $this-> bdownloadscapping = ($oFileManagerEnv->oFmFolder->getDownloadsCapping() == 'Y');
                $this-> bdownloadhistory = ($oFileManagerEnv->oFmFolder->getDownloadHistory() == 'Y');

                if ('Y' === $oFolderFile->getGroup()) {
                    $this->resff = $babDB->db_query('SELECT * FROM '.BAB_FM_FIELDS_TBL.' WHERE id_folder='.$babDB->quote($oFolderFile->getOwnerId()));
                    $this->countff = $babDB->db_num_rows($this->resff);
                }
            }
            // indexation



            if (bab_isFileIndex($fullpath) && bab_isUserAdministrator()) {
                $engine = bab_searchEngineInfos();

                $this->index = true;
                $this->index_status = $oFolderFile->getStatusIndex();
                $this->t_index_status = bab_translate("Index status");

                $this->index_onload = $engine['indexes']['bab_files']['index_onload'];

                if (isset($_POST['index_status'])) {
                    // modify status

                    $babDB->db_query('UPDATE '.BAB_FILES_TBL.' SET index_status='.$babDB->quote($_POST['index_status']).' WHERE id='.$babDB->quote($_POST['idf']));

                    $files_to_index = array($fullpath);

                    if (isset($_POST['change_all']) && 1 == $_POST['change_all']) {
                        // modifiy index status for older versions
                        $res = $babDB->db_query('SELECT id, ver_major, ver_minor FROM '.BAB_FM_FILESVER_TBL.' WHERE id_file='.$babDB->quote($_POST['idf']));
                        while ($arrfv = $babDB->db_fetch_assoc($res)) {
                            $babDB->db_query('UPDATE '.BAB_FM_FILESVER_TBL.' SET index_status='.$babDB->quote($_POST['index_status']).' WHERE id='.$babDB->quote($arrfv['id']));

                            if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
                                $files_to_index[] = $oFileManagerEnv->getCurrentFmPath() . $oFolderFile->getPathName() . BAB_FVERSION_FOLDER.'/'.$arrfv['ver_major'].','.$arrfv['ver_minor'].','.$oFolderFile->getName();
                            }
                        }
                    }

                    if ($this->index_onload && BAB_INDEX_STATUS_INDEXED == $_POST['index_status']) {
                        $this->index_status = bab_indexOnLoadFiles($files_to_index , 'bab_files');
                        if (BAB_INDEX_STATUS_INDEXED === $this->index_status) {
                            foreach ($files_to_index as $f) {
                                $obj = new bab_indexObject('bab_files');
                                $obj->setIdObjectFile($f, $oFolderFile->getId(), $oFolderFile->getOwnerId());
                            }
                        }
                    } else {
                        $this->index_status = $_POST['index_status'];
                    }
                }
            }

            $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
            $babBody->addJavascriptFile($GLOBALS['babScriptPath'].'scriptaculous/scriptaculous.js');
            $babBody->addStyleSheet('ajax.css');
        }


        function getnextfield()
        {
            global $babDB;
            static $i = 0;
            if($i < $this->countff)
            {
                $arr = $babDB->db_fetch_array($this->resff);
                $this->field = bab_translate($arr['name']);
                $this->fieldid = 'field'.$arr['id'];
                $this->fieldval = '';
                $this->fieldvalhtml = '';
                $res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$babDB->db_escape_string($arr['id'])."' and id_file='".$babDB->db_escape_string($this->idf)."'");
                if($res && $babDB->db_num_rows($res) > 0)
                {
                    list($this->fieldval) = $babDB->db_fetch_array($res);
                    $this->fieldvalhtml = bab_toHtml($this->fieldval);
                }
                $i++;
                return true;
            }
            else
            {
                if($this->countff > 0)
                {
                    $babDB->db_data_seek($this->resff, 0 );
                }
                $i = 0;
                return false;
            }
        }


        function getnextistatus()
        {
            static $m=0;
            if($m < count($this->tabIndexStatus))
            {
                $this->value = $this->tabIndexStatus[$m];
                $this->disabled=false;
                $this->option = bab_toHtml(bab_getIndexStatusLabel($this->value));
                $this->selected = $this->index_status == $this->value;
                if(BAB_INDEX_STATUS_INDEXED == $this->value && !$this->index_onload)
                {
                    $this->disabled=true;
                }
                $m++;
                return true;
            }
            return false;
        }
    }


    $access = false;
    $bmanager = false;
    $bconfirm = false;
    $bupdate = false;
    $bdownload = false;
    $bdownloadhistoryright = false;
    $bversion = '';

    $idf = (int) bab_rp('idf');

    $oFolderFileSet = new BAB_FolderFileSet();
    $oId =& $oFolderFileSet->aField['iId'];
    $oState =& $oFolderFileSet->aField['sState'];

    $oCriteria = $oId->in($idf);
    $oCriteria = $oCriteria->_and($oState->in(''));

    $oFolderFile = $oFolderFileSet->get($oCriteria);

    if(!is_null($oFolderFile))
    {
        //A cause de OVML
        bab_setCurrentUserDelegation($oFolderFile->getDelegationOwnerId());
        $oFileManagerEnv =& getEnvObject();

        if('N' === $oFolderFile->getGroup())
        {
            if(bab_userHavePersonnalStorage() && $BAB_SESS_USERID == $oFolderFile->getOwnerId())
            {
                $access = true;
                $bmanager = true;
                $bupdate = true;
                $bdownload = true;
                $bdownloadhistoryright = true;
            }
        }
        else if('Y' === $oFolderFile->getGroup())
        {
            if('N' === $oFolderFile->getConfirmed())
            {
                $arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
                if(count($arrschi) > 0 && in_array($oFolderFile->getFlowApprobationInstanceId(), $arrschi))
                {
                    $bconfirm = true;
                }
            }

            $sParentPath = $oFileManagerEnv->sRelativePath;

            $access = (!is_null($oFileManagerEnv->oFmFolder));
            $bdownload = canDownload($sParentPath);
            $bmanager = haveRight($sParentPath, BAB_FMMANAGERS_GROUPS_TBL);//canManage($sParentPath);
            $bupdate = canUpdate($sParentPath);
            $bdownloadhistoryright = haveRight($sParentPath, BAB_FMDOWNLOADHISTORY_GROUPS_TBL);

            if ($bconfirm) {
                $bupdate = false;
                $bmanager = false;
            }

            if (isset($oFileManagerEnv->oFmFolder)) {
                $bversion = $oFileManagerEnv->oFmFolder->getVersioning();
                if (0 !== $oFolderFile->getFolderFileVersionId() || $bversion ==  'Y') {
                    $bupdate = false;
                }
            }
        }
    }

    if ($access) {
        $temp = new ViewFileTpl($oFileManagerEnv->oFmFolder, $oFolderFile, $bmanager, $bdownloadhistoryright, $access, $bconfirm, $bupdate, $bdownload, $bversion);
    } else {
        $temp = new ViewFileTpl(null, $oFolderFile, $bmanager, $bdownloadhistoryright, $access, $bconfirm, $bupdate, $bdownload, $bversion);
    }
    $babBody->babpopup(bab_printTemplate($temp, 'fileman.html', 'viewfile'));
}


function displayRightForm()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $iIdFolder = (int) bab_gp('iIdFolder', 0);

    $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
    if(!is_null($oFmFolder))
    {
        $sFolderName = $oFmFolder->getName();

        $babBody->addItemMenu("list", bab_translate("Folders"), $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
        if(canUpload($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'))
        {
            $babBody->addItemMenu("add", bab_translate("Upload"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayAddFileForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
        }
        if(canManage($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'))
        {
            $babBody->addItemMenu("trash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=fileman&idx=trash&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".urlencode($oFileManagerEnv->sPath));
        }
        $babBody->addItemMenu("displayRightForm", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=fileman&idx=displayRightForm&id=".$oFileManagerEnv->iId."&gr=".$oFileManagerEnv->sGr."&path=".
            urlencode($oFileManagerEnv->sPath) . '&iIdFolder=' . $iIdFolder);

        $babBody->title = bab_translate("Rights of directory") . ' ' . $sFolderName;

        if(canSetRight($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/'))
        {
            require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
            $macl = new macl("fileman", 'list', $iIdFolder, 'setRight', true, $oFmFolder->getDelegationOwnerId());

            $macl->set_hidden_field('path', $oFileManagerEnv->sPath);
            $macl->set_hidden_field('sAction', 'setRight');
            $macl->set_hidden_field('sPathName', $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/');
            $macl->set_hidden_field('id', $oFileManagerEnv->iId);
            $macl->set_hidden_field('gr', $oFileManagerEnv->sGr);
            $macl->set_hidden_field('iIdFolder', $iIdFolder);

            $macl->addtable( BAB_FMUPLOAD_GROUPS_TBL,bab_translate("Upload"));
            $macl->filter(0,0,1,0,1);
            $macl->addtable( BAB_FMDOWNLOAD_GROUPS_TBL,bab_translate("Download"));
            $macl->addtable( BAB_FMUPDATE_GROUPS_TBL,bab_translate("Update"));
            $macl->filter(0,0,1,0,1);
            $macl->addtable( BAB_FMMANAGERS_GROUPS_TBL,bab_translate("Manage"));
            $macl->filter(0,0,1,1,1);
            $macl->addtable( BAB_FMNOTIFY_GROUPS_TBL,bab_translate("Who is notified when a new file is uploaded or updated?"));
            $macl->filter(0,0,1,0,1);
            $macl->addtable( BAB_FMUNZIP_GROUPS_TBL,bab_translate("Who can unzip archives?"));
            $macl->filter(0,0,1,0,1);
            $macl->addtable( BAB_FMDOWNLOADHISTORY_GROUPS_TBL,bab_translate("Who can view the download history?"));
            $macl->filter(0,0,1,0,1);
            $macl->babecho();
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Invalid directory");
    }
}

function getOrphanFileList()
{
    if(false === bab_isUserAdministrator())
    {
        return;
    }

    $oFileManagerEnv =& getEnvObject();

    $oFolderFileSet = new BAB_FolderFileSet();
    $oGroup =& $oFolderFileSet->aField['sGroup'];

    $oFolderFileSet->select($oGroup->in('Y'));

    while(null !== ($oFolderFile = $oFolderFileSet->next()))
    {
        /*@var $oFolderFile bab_FolderFile */
        $collectivePath = $oFileManagerEnv->getCollectivePath($oFolderFile->getDelegationOwnerId());
        $sFullPathName = $collectivePath . $oFolderFile->getPathName() . $oFolderFile->getName();
        if(!is_file($sFullPathName))
        {
            bab_debug($sFullPathName);
        }
    }

}

function deleteOrphanFile()
{
    if(false === bab_isUserAdministrator())
    {
        return;
    }

    $oFileManagerEnv =& getEnvObject();


    $oFolderFileSet = new BAB_FolderFileSet();
    $oGroup =& $oFolderFileSet->aField['sGroup'];
    $oId =& $oFolderFileSet->aField['iId'];

    $oFolderFileSet->select($oGroup->in('Y'));
    $id_to_delete = array();

    while(null !== ($oFolderFile = $oFolderFileSet->next()))
    {
        /*@var $oFolderFile bab_FolderFile */
        $collectivePath = $oFileManagerEnv->getCollectivePath($oFolderFile->getDelegationOwnerId());

        $sFullPathName = $collectivePath . $oFolderFile->getPathName() . $oFolderFile->getName();
        if(!is_file($sFullPathName))
        {
            bab_debug($sFullPathName);
            $id_to_delete[] = $oFolderFile->getId();
        }
    }

    if ($id_to_delete)
    {
        $oFolderFileSet->remove($oId->in($id_to_delete));
    }

}








function deleteUnreferencedFiles($simulation = false)
{
    if(false === bab_isUserAdministrator())
    {
        return;
    }

    $oFileManagerEnv =& getEnvObject();
    $uploadPath = new bab_Path($oFileManagerEnv->getRootFmPath());
    $uploadPath->pop();

    bab_debug('Search in : '.$uploadPath->tostring());

    deleteUnreferencedFilesLevel($uploadPath , $simulation);


}

function deleteUnreferencedFilesLevel(bab_Path $path, $simulation)
{
    $oFileManagerEnv =& getEnvObject();
    $prefix = $oFileManagerEnv->getRootFmPath();

    $oFolderFileSet = new BAB_FolderFileSet();
    $oName = $oFolderFileSet->aField['sName'];
    $oPath = $oFolderFileSet->aField['sPathName'];

    foreach($path as $file)
    {
        /*@var $file bab_path */
        if ($file->isDir())
        {
            deleteUnreferencedFilesLevel($file, $simulation);
        } else {

            $basename = $file->getBasename();
            $path = mb_substr($file->toString(), mb_strlen($prefix), -1 * mb_strlen($basename));

            $r = $oFolderFileSet->get(
                    $oName->in($basename)
                    ->_and($oPath->in($path))
            );

            if (null === $r)
            {
                bab_debug($file->toString());

                // not found in database : delete
                if ($simulation)
                {
                    continue;
                } else {
                    unlink($file->toString());
                }
            }
        }
    }
}




function setRight()
{
    global $babBody;
    $sPathName = (string) bab_rp('sPathName', '');

    if(canSetRight($sPathName))
    {
        require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';
        maclGroups();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}

function fileUnload()
    {
    class fileUnloadTpl
        {
        public $message;
        public $close;
        public $redirecturl;

        function __construct()
            {
            $oFileManagerEnv	=& getEnvObject();
            $this->message		= bab_translate("Your file list has been updated");
            $this->close		= bab_translate("Close");

            $this->sContent		= 'text/html; charset=' . bab_charset::getIso();
            }
        }

        $temp = new fileUnloadTpl();
    echo bab_printTemplate($temp,"fileman.html", "fileunload");
    }

function deleteFiles($items)
{
    $oFolderFileSet = new BAB_FolderFileSet();
    $oId =& $oFolderFileSet->aField['iId'];
    $oFolderFileSet->remove($oId->in($items));
}

function restoreFiles($items)
{
    $oFileManagerEnv =& getEnvObject();

    $sPathName = $oFileManagerEnv->getCurrentFmPath();

//	bab_debug($sPathName);

    $oFolderFileSet = new BAB_FolderFileSet();
    $oId =& $oFolderFileSet->aField['iId'];

    for($i = 0; $i < count($items); $i++)
    {
        $oFolderFile = $oFolderFileSet->get($oId->in($items[$i]));
        if(!is_null($oFolderFile))
        {
            if(!is_dir($sPathName))
            {
                $rr = explode("/", $sPathName);
                $sPath = $oFileManagerEnv->getFmUploadPath();
                for($k = 0; $k < count($rr); $k++ )
                {
                    $sPath .= $rr[$k]."/";
                    if(!is_dir($sPath))
                    {
                        bab_mkdir($sPath, $GLOBALS['babMkdirMode']);
                    }
                }
            }
            $oFolderFile->setState('');
            $oFolderFile->save();
        }
    }
}


/**
 *
 * @return void
 */
function displayFolderForm()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    $babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' .
        $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath));

    $sFunction 	= (string) bab_gp('sFunction', '');
    if ($sFunction == 'createFolder') {
        $babBody->addItemMenu('displayFolderForm', bab_translate("Create a folder"), $GLOBALS['babUrlScript'] .
        '?tg=fileman&idx=displayFolderForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr .
        '&path=' . urlencode($oFileManagerEnv->sPath));
        $babBody->title = bab_translate("Add a new folder");
    } else {
        $babBody->addItemMenu('displayFolderForm', bab_translate("Edit folder"), $GLOBALS['babUrlScript'] .
        '?tg=fileman&idx=displayFolderForm&id=' . $oFileManagerEnv->iId . '&gr=' . $oFileManagerEnv->sGr .
        '&path=' . urlencode($oFileManagerEnv->sPath));
        $babBody->title = bab_translate("Edit folder");
    }

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        if(canCreateFolder($oFileManagerEnv->sRelativePath))
        {
            $oDspFldForm = new DisplayCollectiveFolderForm();
            $babBody->babecho($oDspFldForm->printTemplate());
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        if(canCreateFolder($oFileManagerEnv->sRelativePath))
        {
            $oDspFldForm = new DisplayUserFolderForm();
            $babBody->babecho($oDspFldForm->printTemplate());
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
}

function displayOrderFolder(){
    global $babBody;
    class displayOrderFolderTpl{
        public $forumtxt;
        public $moveup;
        public $movedown;
        public $create;
        public $db;
        public $res;
        public $count;
        public $arrid = array();
        public $forumid;
        public $forumval;


        function __construct(){
            global $babDB;
            $this->moveup = bab_translate("Move Up");
            $this->movedown = bab_translate("Move Down");
            $this->sorta = bab_translate("Sort ascending");
            $this->sortd = bab_translate("Sort descending");
            $this->create = bab_translate("Modify");
            $this->id_record = bab_gp('id');
            $this->gr_record = bab_gp('gr');
            $this->path_record = bab_gp('path');
            $this->sAction = "editOrder";

            $this->tg = bab_gp('tg');
            $req = "select id, name from ".BAB_FILES_TBL." where id_owner=".$babDB->quote(bab_gp('iIdFolder'))." AND path='" . bab_gp('path') . "/' order by display_position, name asc";
            $this->res = $babDB->db_query($req);
            while( $arr = $babDB->db_fetch_array($this->res) ){
                    $this->arrid[] = $arr['id'];
                    $this->arrname[] = $arr['name'];
            }
            $this->count = count($this->arrid);
        }

        function getnext(){
            static $i = 0;
            if( $i < $this->count){
                $this->filesname = bab_toHtml($this->arrname[$i]);
                $this->filesid = $this->arrid[$i];
                $i++;
                return true;
            }else{
                return false;
            }
        }
    }


    global $babBody;




    $babBody->addItemMenu('list', bab_translate("Folders"), $GLOBALS['babUrlScript'] .
    '?tg=fileman' .
    '&idx=list' .
    '&id=' . bab_gp('id','') .
    '&gr=' . bab_gp('gr','') .
    '&path=' . urlencode(bab_gp('path','')));



    $babBody->addItemMenu('displayOrderFolder', bab_translate("Order files"), $GLOBALS['babUrlScript'] .
    '?tg=fileman' .
    '&idx=displayOrderFolder' .
    '&id=' . bab_gp('displayOrderFolder','') .
    '&gr=' . bab_gp('gr','') .
    '&path=' . urlencode(bab_gp('path','')) .
    '&sDirName=' . urlencode(bab_gp('sDirName','')) .
    '&iIdFolder=' . bab_gp('iIdFolder',''));


    if (!haveRightOn(bab_gp('path'), BAB_FMMANAGERS_GROUPS_TBL))
    {
        $babBody->addError(bab_translate('Access denied'));
        return;
    }

    $temp = new displayOrderFolderTpl();


    $babBody->title = bab_translate("Order files");


    $babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
    $babBody->babecho(	bab_printTemplate($temp,"admfms.html", "filesorder"));

    return true;
}

function updateOrderFiles(){
    global $babDB, $babBody;

    if (!haveRightOn(bab_pp('path'), BAB_FMMANAGERS_GROUPS_TBL))
    {
        $babBody->addError(bab_translate('Access denied'));
        return false;
    }

    $listfiles = bab_pp('listfiles');
    $i = 0;
    foreach($listfiles as $fileID){
        $babDB->db_query("UPDATE " . BAB_FILES_TBL . " SET display_position='" . $i . "' WHERE id='" . $fileID . "'");
        $i++;
    }

    return true;
}

function displayDeleteFolderConfirm()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if(canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $sPath		= (string) bab_rp('path', '');
        $sDirName	= (string) bab_rp('sDirName', '');
        $iIdFld		= (int) bab_rp('iIdFolder', 0);

        $oBfp = new BAB_BaseFormProcessing();

        $oBfp->set_caption('yes', bab_translate("Yes"));
        $oBfp->set_caption('no', bab_translate("No"));
        $oBfp->set_caption('warning', bab_translate("CAUTION: This will permanently remove this directory and all subdirectories files on it !"));
        $oBfp->set_caption('message', bab_translate("You sure you want to delete this directory ?"));
        $oBfp->set_caption('title', $sDirName);

        $oBfp->set_data('sTg', 'fileman');
        $oBfp->set_data('sIdx', 'list');
        $oBfp->set_data('sAction', 'deleteFolder');
        $oBfp->set_data('sDirName', $sDirName);
        $oBfp->set_data('iIdFolder', $iIdFld);
        $oBfp->set_data('iId', $oFileManagerEnv->iId);
        $oBfp->set_data('sPath', $sPath);
        $oBfp->set_data('sPathName', $oFileManagerEnv->sRelativePath);
        $oBfp->set_data('sGr', $oFileManagerEnv->sGr);

        $babBody->babecho(bab_printTemplate($oBfp, 'fileman.html', 'warningyesno'));
    }
    else
    {
        $babBody->msgerror = bab_toHtml(bab_translate("Access denied"));
        return;
    }
}


function cutFolder()
{
    $oFileManagerEnv =& getEnvObject();
    if($oFileManagerEnv->userIsInRootFolder() || $oFileManagerEnv->userIsInCollectiveFolder())
    {
        cutCollectiveDir();
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        cutUserFolder();
    }
    else
    {
        $babBody = bab_getBody();
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function zipFolder()
{
    global $babBody, $babDB;
    $oFileManagerEnv =& getEnvObject();
    $sDirName = (string) bab_gp('sDirName', '');
    $gr = bab_rp('gr','');
    if(mb_strlen(trim($sDirName)) > 0 && ($oFileManagerEnv->userIsInRootFolder() || $oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInPersonnalFolder()))
    {
        if($gr == 'Y'){
            require_once $GLOBALS['babInstallPath'].'utilit/filemanApi.php';
            $folderPath = realpath($oFileManagerEnv->getCollectiveRootFmPath() . $oFileManagerEnv->sRelativePath . $sDirName);
        }elseif($gr == 'N'){
            $folderPath = realpath($oFileManagerEnv->getPersonalPath($GLOBALS['BAB_SESS_USERID']) . $oFileManagerEnv->sRelativePath . $sDirName);
        }else{
            if($sDirName == bab_translate('Private folder')){
                $_GET['gr'] = 'N';
                $_POST['gr'] = 'N';
                $personalPath = new bab_Path($oFileManagerEnv->getPersonalPath($GLOBALS['BAB_SESS_USERID']) . $oFileManagerEnv->sRelativePath);
                if(!$personalPath->isDir()){
                    $personalPath->createDir();
                }
                $folderPath = realpath($oFileManagerEnv->getPersonalPath($GLOBALS['BAB_SESS_USERID']) . $oFileManagerEnv->sRelativePath);
            }else{
                $_GET['gr'] = 'Y';
                $_POST['gr'] = 'Y';
                require_once $GLOBALS['babInstallPath'].'utilit/filemanApi.php';
                $folderPath = realpath($oFileManagerEnv->getCollectiveRootFmPath() . $oFileManagerEnv->sRelativePath . $sDirName);
            }
        }

        if(!is_dir($folderPath))
        {
            $babBody->addError(bab_translate("Invalid directory"));
            return;
        }
        if(getDirSize($folderPath) > $GLOBALS['babMaxZipSize']){
            $babBody->addError(bab_translate("The ZIP file size exceed the limit configured for the file manager"));
            return false;
        }

        $sourcePath = new bab_Path($folderPath);
        $destPath = new bab_Path($GLOBALS['babUploadPath'],'tmp',session_id());
        if($destPath->isDir()){
            $destPath->deleteDir();
        }

        $destPath->createDir();
        $destPath->push($sDirName.'.zip');

        $sql = "SELECT * FROM " . BAB_FILES_TBL . " WHERE confirmed = 'N' AND iIdDgOwner = '".bab_getCurrentUserDelegation()."'";
        $res = $babDB->db_query($sql);
        $notApproveFile = array();
        while($arr = $babDB->db_fetch_assoc($res)){
            $tmpPath = new bab_Path($oFileManagerEnv->getCollectiveRootFmPath(),$arr['path'],$arr['name']);
            $notApproveFile[] = realpath($tmpPath->tostring());
        }
        /* @var $Zip Func_Archive_Zip */
        $Zip = bab_functionality::get('Archive/Zip');
        $Zip->open($destPath->tostring());
        bab_zipFolderFile($sourcePath, $Zip, $sDirName, $notApproveFile);
        $Zip->close();
        if(is_file($destPath->tostring())){

            $fp = fopen($destPath->tostring(), 'rb');
            if ($fp)
            {
                bab_setTimeLimit(3600);
                if (mb_strtolower(bab_browserAgent()) == 'msie') {
                    header('Cache-Control: public');
                }
                $name = basename($destPath->getBasename());
                header('Content-Disposition: attachment; filename="'.$name.'"'."\n");
                $mime = 'application/zip';
                $fsize = filesize($destPath->tostring());
                header('Content-Type: '.$mime."\n");
                header('Content-Length: '.$fsize."\n");
                header('Content-transfert-encoding: binary'."\n");
                readfile($destPath->tostring());

                fclose($fp);
            }
        }
        return;
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}

function bab_zipFolderFile(bab_Path $source, $Zip, $currentPath = '', $notApproveFile){
    foreach($source as $babPath){
        if($currentPath == ''){
            $currentBabPath = new bab_Path($babPath->getBasename());
        }else{
            $currentBabPath = new bab_Path($currentPath, $babPath->getBasename());
        }
        if($babPath->isDir()){
            bab_zipFolderFile($babPath, $Zip, $currentBabPath->tostring(), $notApproveFile);
        }else{
            if(bab_rp('gr') == 'Y'){
                $folder = new bab_fileInfo($babPath->tostring());
                if(!$folder->isReadable()){
                    //$babBody->addError(bab_translate("Invalid directory"));
                    //return;
                    continue;
                }
            }
            if(in_array(realpath($babPath->tostring()), $notApproveFile)){
                continue;
            }
            $Zip->addFile($babPath->tostring(), $currentBabPath->tostring());

        }
    }
}

function cutCollectiveDir()
{
    global $babBody;
    $oFileManagerEnv =& getEnvObject();

    $sDirName = (string) bab_rp('sDirName', '');

    if(mb_strlen(trim($sDirName)) > 0)
    {
        if(!canCutFolder($oFileManagerEnv->sRelativePath . $sDirName . '/'))
        {
            $babBody->msgerror = bab_translate("Access denied");
            return;
        }

        $sFullPathName = realpath($oFileManagerEnv->getCollectiveRootFmPath() . $oFileManagerEnv->sRelativePath . $sDirName);

        if(!is_dir($sFullPathName))
        {
            $babBody->msgerror = bab_translate("Invalid directory");
            return;
        }

        $iIdRootFolder	= $oFileManagerEnv->iId;
        $iIdFolder		= 0;
        $sGroup			= 'Y';
        $sCollective	= 'N';
        $iIdOwner		= $oFileManagerEnv->iIdObject;
        $sCheckSum		= md5($sDirName);

        $oFmFolderSet	= new BAB_FmFolderSet();
        $oName			=& $oFmFolderSet->aField['sName'];
        $oRelativePath	=& $oFmFolderSet->aField['sRelativePath'];
        $oIdDgOwner		=& $oFmFolderSet->aField['iIdDgOwner'];

        $oCriteria = $oName->in($sDirName);
        $oCriteria = $oCriteria->_and($oRelativePath->in($oFileManagerEnv->sRelativePath));
        $oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));

        $oFmFolder = $oFmFolderSet->get($oCriteria);
        if(!is_null($oFmFolder))
        {
            $iIdFolder		= $oFmFolder->getId();
            $sCollective	= 'Y';
            $iIdOwner		= $oFmFolder->getId();
        }

        $oFmFolderCliboard = new BAB_FmFolderCliboard();
        $oFmFolderCliboard->setRootFolderId($iIdRootFolder);
        $oFmFolderCliboard->setFolderId($iIdFolder);
        $oFmFolderCliboard->setGroup($sGroup);
        $oFmFolderCliboard->setCollective($sCollective);
        $oFmFolderCliboard->setOwnerId($iIdOwner);
        $oFmFolderCliboard->setDelegationOwnerId(bab_getCurrentUserDelegation());
        $oFmFolderCliboard->setCheckSum($sCheckSum);
        $oFmFolderCliboard->setName($sDirName);
        $oFmFolderCliboard->setRelativePath($oFileManagerEnv->sRelativePath);
        $oFmFolderCliboard->save();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function cutUserFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

    global $babBody;
    $oFileManagerEnv =& getEnvObject();

    $sDirName = (string) bab_rp('sDirName', '');

    if(!canCutFolder($oFileManagerEnv->sRelativePath . $sDirName . '/'))
    {
        $babBody->msgerror = bab_translate("Access denied");
        return;
    }

    if(mb_strlen(trim($sDirName)) > 0)
    {
        $sUploadPath = $oFileManagerEnv->getRootFmPath();
        $sFullPathName = realpath($sUploadPath . $oFileManagerEnv->sRelativePath . $sDirName);

        if(!is_dir($sFullPathName))
        {
            $babBody->msgerror = bab_translate("Invalid directory");
            return;
        }

        $iIdRootFolder	= $oFileManagerEnv->iId;
        $iIdFolder		= 0;
        $sGroup			= 'N';
        $sCollective	= 'N';
        $sCheckSum		= md5($sDirName);

        $oFmFolderCliboard = new BAB_FmFolderCliboard();
        $oFmFolderCliboard->setRootFolderId($iIdRootFolder);
        $oFmFolderCliboard->setFolderId($iIdFolder);
        $oFmFolderCliboard->setGroup($sGroup);
        $oFmFolderCliboard->setCollective($sCollective);
        $oFmFolderCliboard->setOwnerId($iIdRootFolder);
        $oFmFolderCliboard->setDelegationOwnerId(0);
        $oFmFolderCliboard->setCheckSum($sCheckSum);
        $oFmFolderCliboard->setName($sDirName);
        $oFmFolderCliboard->setRelativePath($oFileManagerEnv->sRelativePath);
        $oFmFolderCliboard->save();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function pasteFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

    global $babBody;
    $oFileManagerEnv =& getEnvObject();

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        pasteCollectiveDir();
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        pasteUserFolder();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}

function undoPasteFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

    $oFileManagerEnv =& getEnvObject();

    $sSrcPath				= (string) bab_gp('sSrcPath', '');

    $oFmFolder				= null;

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        $bSrcPathIsCollective	= true;
    }
    else
    {
        $bSrcPathIsCollective	= false;
    }
    $sName = getLastPath($sSrcPath);
    $sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));

    $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
    $oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, $bSrcPathIsCollective == true ?'Y':'N');
}

function pasteCollectiveDir()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

    global $babBody;
    $oFileManagerEnv =& getEnvObject();

    $iIdSrcRootFolder		= (int) bab_rp('iIdSrcRootFolder', 0);
    $sSrcPath				= (string) bab_rp('sSrcPath', '');
    $bSrcPathIsCollective	= true;
    $iIdTrgRootFolder		= $oFileManagerEnv->iId;
    $sTrgPath				= (string) bab_rp('path', '');

    $oFmFolder				= null;

    if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
    {
        //Nom du repertoire a coller
        $sName = getLastPath($sSrcPath);

        //Emplacement du repertoire a coller
        $sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));

        $bTrgPathHaveVersioning = false;
        $bSrcPathCollective		= false;

        //Recuperation des informations concernant le repertoire source (i.e le repertoire a deplacer)
        {
            $iIdRootFolder	= 0;
            $oSrcFmFolder	= null;
            BAB_FmFolderHelper::getInfoFromCollectivePath($sSrcPath, $iIdRootFolder, $oSrcFmFolder);

            /* @var $oSrcFmFolder BAB_FmFolder */
            $bSrcPathCollective		= ((string) $sSrcPath . '/' === (string) $oSrcFmFolder->getRelativePath() . $oSrcFmFolder->getName() . '/');
        }

        $oFmFolderSet = new BAB_FmFolderSet();
        if($oFileManagerEnv->userIsInCollectiveFolder())
        {
            //Recuperation des informations concernant le repertoire cible (i.e le repertoire dans lequel le source est deplace)
            $oTrgFmFolder = null;
            BAB_FmFolderHelper::getInfoFromCollectivePath($sTrgPath, $iIdRootFolder, $oTrgFmFolder);

            /* @var $oTrgFmFolder BAB_FmFolder */
            $bTrgPathHaveVersioning = ('Y' === $oTrgFmFolder->getVersioning());
        }
        else if($oFileManagerEnv->userIsInRootFolder())
        {
            $oIdDgOwner		= $oFmFolderSet->aField['iIdDgOwner'];
            $oName			= $oFmFolderSet->aField['sName'];
            $oRelativePath	= $oFmFolderSet->aField['sRelativePath'];

            $oCriteria = $oIdDgOwner->in(bab_getCurrentUserDelegation());
            $oCriteria = $oCriteria->_and($oName->in($sName));
            $oCriteria = $oCriteria->_and($oRelativePath->in($sSrcPathRelativePath));

            $bSrcPathCollective = true;

//			bab_debug($oFmFolderSet->getSelectQuery($oCriteria));
            $oFmFolder = $oFmFolderSet->get($oCriteria);
            if(!is_null($oFmFolder))
            {
                //Le repertoire a coller est collectif

                $bTrgPathHaveVersioning = ('Y' === $oFmFolder->getVersioning());
            }
            else
            {
                //Le repertoire a coller n'est pas collectif
                //comme on colle dans la racine il faut le faire
                //devenir un repertoire collectif

                $oFmFolder = new BAB_FmFolder();
                $oFmFolder->setName($sName);
                $oFmFolder->setRelativePath('');
                $oFmFolder->setActive('Y');
                $oFmFolder->setApprobationSchemeId(0);
                $oFmFolder->setDelegationOwnerId((int) bab_getCurrentUserDelegation());
                $oFmFolder->setFileNotify('N');
                $oFmFolder->setHide('N');
                $oFmFolder->setAddTags('Y');
                $oFmFolder->setVersioning('N');
                $oFmFolder->setAutoApprobation('N');
            }
        }

        $sUploadPath = BAB_FileManagerEnv::getCollectivePath(bab_getCurrentUserDelegation());

        $sFullSrcPath = realpath((string) $sUploadPath . $sSrcPath);
        $sFullTrgPath = realpath((string) $sUploadPath . $sTrgPath);

        $oFileManagerEnv =& getEnvObject();
        $totalsize = getDirSize($oFileManagerEnv->getCurrentFmRootPath());
        $filesize = getDirSize($sFullSrcPath);
        if($filesize + $totalsize > ($oFileManagerEnv->sGr == "Y"? $GLOBALS['babMaxGroupSize']: $GLOBALS['babMaxUserSize']))
        {
            $babBody->msgerror = bab_translate("Cannot paste folder: The target folder does not have enough space");
            return false;
        }

//		bab_debug('sFullSrcPath ==> ' . $sFullSrcPath . ' versioning ' . (($bSrcPathHaveVersioning) ? 'Yes' : 'No') . ' bSrcPathCollective ' . (($bSrcPathCollective) ? 'Yes' : 'No'));
//		bab_debug('sFullTrgPath ==> ' . $sFullTrgPath . ' versioning ' . (($bTrgPathHaveVersioning) ? 'Yes' : 'No'));

//		$sPath = mb_substr($sFullTrgPath, 0, mb_strlen($sFullSrcPath));
//		if($sPath !== $sFullSrcPath)
        {
            $bSrcValid = ((realpath(mb_substr($sFullSrcPath, 0, mb_strlen(realpath($sUploadPath)))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
            $bTrgValid = ((realpath(mb_substr($sFullTrgPath, 0, mb_strlen(realpath($sUploadPath)))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));

//			bab_debug('bSrcValid ' . (($bSrcValid) ? 'Yes' : 'No'));
//			bab_debug('bTrgValid ' . (($bTrgValid) ? 'Yes' : 'No'));

            if($bSrcValid && $bTrgValid)
            {
                if(!is_null($oFmFolder))
                {
                    if(true !== $oFmFolder->save())
                    {
                        $babBody->msgerror = bab_translate("Error");
                        return;
                    }
                    $bTrgPathHaveVersioning = false;
                }

                global $babDB, $babBody;
                $oFolderFileSet = new BAB_FolderFileSet();
                $oIdDgOwnerFile =& $oFolderFileSet->aField['iIdDgOwner'];
                $oGroup =& $oFolderFileSet->aField['sGroup'];
                $oPathName =& $oFolderFileSet->aField['sPathName'];

                $oFmFolderSet = new BAB_FmFolderSet();
                $oIdDgOwnerFolder =& $oFmFolderSet->aField['iIdDgOwner'];
                $oRelativePath =& $oFmFolderSet->aField['sRelativePath'];

                $sLastRelativePath = $sSrcPath . '/';
                $sNewRelativePath = ((mb_strlen(trim($sTrgPath)) > 0) ?
                    $sTrgPath . '/' : '') . getLastPath($sSrcPath) . '/';

                if(false === $bSrcPathCollective)
                {
                     if(false === $bTrgPathHaveVersioning)
                     {
                        global $babDB;

                        //Suppression des versions des fichiers pour les repertoires qui ne sont pas contenus dans des
                        //repertoires collectifs
                        {
                            //Selection de tous les fichiers qui contiennent dans leurs chemins le repertoire a deplacer
                            $oCriteriaFile = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
                            $oCriteriaFile = $oCriteriaFile->_and($oGroup->in('Y'));
                            $oCriteriaFile = $oCriteriaFile->_and($oIdDgOwnerFile->in(bab_getCurrentUserDelegation()));

                            //Selection des repertoires collectifs
                            $oCriteriaFolder = $oRelativePath->like($babDB->db_escape_like($sLastRelativePath) . '%');
                            $oCriteriaFolder = $oCriteriaFolder->_and($oIdDgOwnerFolder->in(bab_getCurrentUserDelegation()));
                            $oFmFolderSet->select($oCriteriaFolder);
                            while(null !== ($oFmFolder = $oFmFolderSet->next()))
                            {
                                //exclusion des repertoires collectif (on ne touche pas a leurs versions)
                                $oCriteriaFile = $oCriteriaFile->_and($oPathName->notLike(
                                    $babDB->db_escape_like($oFmFolder->getRelativePath() . $oFmFolder->getName() . '/') . '%'));
                            }
                            $oFolderFileSet->removeVersions($oCriteriaFile);

                            $oFolderFileSet->select($oCriteriaFile);
                            while(null !== ($oFolderFile = $oFolderFileSet->next()))
                            {
                                $oFolderFile->setMajorVer(1);
                                $oFolderFile->setMinorVer(0);
                                $oFolderFile->save();
                            }
                        }
                     }
                }

                if(BAB_FmFolderSet::move($sUploadPath, $sLastRelativePath, $sNewRelativePath))
                {
                    BAB_FolderFileSet::move($sLastRelativePath, $sNewRelativePath, 'Y');

                    $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
                    $oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'Y');
                    $oFmFolderCliboardSet->move($sLastRelativePath, $sNewRelativePath, 'Y');
                }
            }
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function pasteUserFolder()
{
//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__);

    $oFileManagerEnv =& getEnvObject();

    $iIdSrcRootFolder		= (int) bab_gp('iIdSrcRootFolder', 0);
    $sSrcPath				= (string) bab_gp('sSrcPath', '');
    $bSrcPathIsCollective	= true;
    $iIdTrgRootFolder		= $oFileManagerEnv->iId;
    $sTrgPath				= (string) bab_gp('path', '');
    $oFmFolder				= null;
    $bSrcPathIsCollective	= false;

    if(canPasteFolder($iIdSrcRootFolder, $sSrcPath, $bSrcPathIsCollective, $iIdTrgRootFolder, $sTrgPath))
    {
        $sUploadPath = $oFileManagerEnv->getRootFmPath();

        $sFullSrcPath = realpath((string) $sUploadPath . $sSrcPath);
        $sFullTrgPath = realpath((string) $sUploadPath . $sTrgPath);

//		bab_debug($sFullSrcPath);
//		bab_debug($sFullTrgPath);

        //Nom du repertoire a coller
        $sName = getLastPath($sSrcPath);

        //Emplacement du repertoire a coller
        $sSrcPathRelativePath = addEndSlash(removeLastPath($sSrcPath . '/'));

        if($sFullSrcPath === realpath((string) $sFullTrgPath . '/' . getLastPath($sSrcPath)))
        {
            $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
            $oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'N');
        }
        else
        {
//			$sPath = mb_substr($sFullTrgPath, 0, mb_strlen($sFullSrcPath));
//			if($sPath !== $sFullSrcPath)
            {
                $bSrcValid = ((realpath(mb_substr($sFullSrcPath, 0, mb_strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_readable($sFullSrcPath));
                $bTrgValid = ((realpath(mb_substr($sFullTrgPath, 0, mb_strlen($sUploadPath))) === (string) realpath($sUploadPath)) && is_writable($sFullTrgPath));

//				bab_debug('bSrcValid ' . (($bSrcValid) ? 'Yes' : 'No'));
//				bab_debug('bTrgValid ' . (($bTrgValid) ? 'Yes' : 'No'));

                if($bSrcValid && $bTrgValid)
                {
                    $sLastRelativePath = $sSrcPath . '/';
                    $sNewRelativePath = ((mb_strlen(trim($sTrgPath)) > 0) ?
                        $sTrgPath . '/' : '') . getLastPath($sSrcPath) . '/';

                    $sSrc = removeEndSlah($sUploadPath . $sLastRelativePath);
                    $sTrg = removeEndSlah($sUploadPath . $sNewRelativePath);

//					bab_debug($sSrc);
//					bab_debug($sTrg);

                    if(rename($sSrc, $sTrg))
                    {
                        $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
                        $oFmFolderCliboardSet->deleteEntry($sName, $sSrcPathRelativePath, 'N');
                        $oFmFolderCliboardSet->move($sLastRelativePath, $sNewRelativePath, 'N');


                        global $babDB, $BAB_SESS_USERID;
                        // update database files
                        $oFolderFileSet = new BAB_FolderFileSet();
                        $oPathName		=& $oFolderFileSet->aField['sPathName'];
                        $oIdDgOwner		=& $oFolderFileSet->aField['iIdDgOwner'];
                        $oGroup			=& $oFolderFileSet->aField['sGroup'];
                        $oIdOwner		=& $oFolderFileSet->aField['iIdOwner'];

                        $oCriteria = $oPathName->like($babDB->db_escape_like($sLastRelativePath) . '%');
                        $oCriteria = $oCriteria->_and($oGroup->in('N'));
                        $oCriteria = $oCriteria->_and($oIdDgOwner->in(0));
                        $oCriteria = $oCriteria->_and($oIdOwner->in($BAB_SESS_USERID));

                        $oFolderFileSet->select($oCriteria);
                        $iL = mb_strlen($sLastRelativePath);
                        while(null !== ($oFolderFile = $oFolderFileSet->next()))
                        {
                            $opath = $oFolderFile->getPathName();
                            $oFolderFile->setPathName($sNewRelativePath.mb_substr($opath, $iL ));
                            $oFolderFile->save();
                        }
                    }
                }
            }
        }
    }
}


function createFolder()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        createFolderForCollectiveDir();
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        createFolderForUserDir();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function createFolderForCollectiveDir()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();
    if(canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $sDirName = (string) bab_pp('sDirName', '');
        $sDirName = trim($sDirName);
        if(mb_strlen($sDirName) > 0)
        {
            $sType					= (string) bab_pp('sType', 'collective');
            $sActive				= (string) bab_pp('sActive', 'Y');
            $iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
            $sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
            $sNotification			= (string) bab_pp('sNotification', 'N');
            $sVersioning			= (string) bab_pp('sVersioning', 'N');
            $sDisplay				= (string) bab_pp('sDisplay', 'N');
            $sAddTags				= (string) bab_pp('sAddTags', 'Y');
            $iIdFolder				= (int) $oFileManagerEnv->iId;

            $sRelativePath = '';
            $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
            if(!is_null($oFmFolder) || $oFileManagerEnv->userIsInRootFolder())
            {
                $sRelativePath	= $oFileManagerEnv->sRelativePath;
                $sUploadPath	= BAB_FileManagerEnv::getCollectivePath(bab_getCurrentUserDelegation());
                $sDirName		= replaceInvalidFolderNameChar($sDirName);

                if(!isStringSupportedByFileSystem($sDirName))
                {
                    $babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
                    return ;
                }


                $sFullPathName	= $sUploadPath . $sRelativePath . $sDirName;

//				bab_debug('sFullPathName ==> ' .  $sFullPathName);
//				bab_debug('sRelativePath ==> ' . $sRelativePath);

                if(BAB_FmFolderHelper::createDirectory($sFullPathName))
                {
                    if('collective' === $sType || $oFileManagerEnv->userIsInRootFolder())
                    {
                        $oFmFolder = new BAB_FmFolder();
                        $oFmFolder->setActive($sActive);
                        $oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
                        $oFmFolder->setAutoApprobation($sAutoApprobation);
                        $oFmFolder->setDelegationOwnerId(bab_getCurrentUserDelegation());
                        $oFmFolder->setFileNotify($sNotification);
                        $oFmFolder->setHide((($sDisplay === 'Y') ? 'N' : 'Y'));
                        $oFmFolder->setName($sDirName);
                        $oFmFolder->setAddTags($sAddTags);
                        $oFmFolder->setRelativePath($sRelativePath);
                        $oFmFolder->setVersioning($sVersioning);
                        $oFmFolder->setAutoApprobation($sAutoApprobation);
                        if(false === $oFmFolder->save())
                        {
                            rmdir($sFullPathName);
                        }
                    }
                }
            }
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function createFolderForUserDir()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);

    $oFileManagerEnv =& getEnvObject();

    if(canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $sDirName = (string) bab_pp('sDirName', '');
        $sDirName = trim($sDirName);
        if(mb_strlen($sDirName) > 0)
        {
//			bab_debug('sFullPathName ==> ' .  $sFullPathName);
//			bab_debug('sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);

            $sUploadPath	= $oFileManagerEnv->getCurrentFmPath();
            $sDirName		= replaceInvalidFolderNameChar($sDirName);

            if(!isStringSupportedByFileSystem($sDirName))
            {
                $babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
                return ;
            }

            $sFullPathName	= $sUploadPath . $sDirName;
            BAB_FmFolderHelper::createDirectory($sFullPathName);
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function editFolder()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        editFolderForCollectiveDir();
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        editFolderForUserDir();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }

}


function editFolderForCollectiveDir()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

//	bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sRelativePath ==> ' . $oFileManagerEnv->sRelativePath);

    if(canCreateFolder($oFileManagerEnv->sRelativePath))
    {
//bab_debug('Rajouter un test qui permet d\'etre que c\'est repertoire collectif ou pas');
        $sDirName = (string) bab_pp('sDirName', '');
        $sDirName = trim($sDirName);
        if(mb_strlen($sDirName) > 0)
        {
            $sType				= (string) bab_pp('sType', 'collective');
            $iIdFld				= (int) bab_pp('iIdFolder', 0);
            $bFolderRenamed		= false;
            $bChangeFileIdOwner = false;
            $sRelativePath		= $oFileManagerEnv->sRelativePath;

            $oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFld);
            if(!is_null($oFmFolder))
            {
                $bFolderRenamed	= ($sDirName !== $oFmFolder->getName()) ? true : false;
                $sOldDirName	= $oFmFolder->getName();
                $sRelativePath	= $oFmFolder->getRelativePath();

                //collectiveToSimple
                if('simple' === $sType)
                {
                    //changer les iIdOwner
                    //supprimer les droits
                    //supprimer les instances de schemas d'approbations
                    //supprimer l'entree dans fmfolders
                    $bDbRecordOnly = true;
                    $oFmFolderSet = new BAB_FmFolderSet();
                    $oFmFolderSet->delete($oFmFolder, $bDbRecordOnly);
                    $oFmFolder = null;
                }
            }
            else
            {
                $sOldDirName	= (string) bab_pp('sOldDirName', '');
                $bFolderRenamed	= ($sDirName !== $sOldDirName) ? true : false;

                //simpleToCollective
                if('collective' === $sType)
                {
                    //changer les iIdOwner
                    //creer l'entree dans fmfolders
                    $bChangeFileIdOwner = true;
                    $oFmFolder = new BAB_FmFolder();
                }
            }

            $sRootFmPath = $oFileManagerEnv->getCollectiveRootFmPath();
            /*
            bab_debug('sRootFmPath ==> ' . $sRootFmPath);
            bab_debug('sRelativePath ==> ' . $sRelativePath);
            bab_debug('sOldDirName ==> ' . $sOldDirName);
            bab_debug('sDirName ==> ' . $sDirName);
            //*/

            if($bFolderRenamed)
            {
                if(mb_strlen(trim($sOldDirName)) > 0)
                {
                    $sLocalDirName = replaceInvalidFolderNameChar($sDirName);

                    if(!isStringSupportedByFileSystem($sLocalDirName))
                    {
                        $babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
                        return ;
                    }

                    $bSuccess = BAB_FmFolderSet::rename($sRootFmPath, $sRelativePath, $sOldDirName, $sLocalDirName);
                    if(false !== $bSuccess)
                    {
                        $sDirName = $sLocalDirName;
                        BAB_FolderFileSet::renameFolder($sRelativePath . $sOldDirName . '/', $sLocalDirName, 'Y');
                        BAB_FmFolderCliboardSet::rename($sRelativePath, $sOldDirName, $sLocalDirName, 'Y');
                    }
                    else
                    {
                        $sDirName = $sOldDirName;
                    }
                }
                else
                {
                    bab_debug(__FUNCTION__ . ' ERROR invalid sOldDirName');
                }
            }

            if(!is_null($oFmFolder))
            {
                $sActive				= (string) bab_pp('sActive', 'Y');
                $iIdApprobationScheme	= (int) bab_pp('iIdApprobationScheme', 0);
                $sAutoApprobation		= (string) bab_pp('sAutoApprobation', 'N');
                $sNotification			= (string) bab_pp('sNotification', 'N');
                $sVersioning			= (string) bab_pp('sVersioning', 'N');
                $sDisplay				= (string) bab_pp('sDisplay', 'N');
                $sAddTags				= (string) bab_pp('sAddTags', 'Y');
                $iMaxDownloads			= (int) bab_pp('iMaxDownloads', 0);
                $sDownloadsCapping		= (string) bab_pp('sDownloadsCapping', 'N');
                $sDownloadHistory		= (string) bab_pp('sDownloadHistory', 'N');
                $orderm					= (string) bab_pp('manual_order', false);

                $iIdOwner				= 0;
                //simpleToCollective
                if ('collective' === $sType) {
                    $oFirstCollectiveParent = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);

                    if (!is_null($oFirstCollectiveParent)) {
                        $iIdOwner = (int) $oFirstCollectiveParent->getId();
                    }
                }

                $oFmFolder->setName($sDirName);
                $oFmFolder->setActive($sActive);
                $oFmFolder->setApprobationSchemeId($iIdApprobationScheme);
                $oFmFolder->setDelegationOwnerId(bab_getCurrentUserDelegation());
                $oFmFolder->setFileNotify($sNotification);
                $oFmFolder->setHide((('Y' === $sDisplay) ? 'N' : 'Y'));
                $oFmFolder->setRelativePath($sRelativePath);
                $oFmFolder->setAddTags($sAddTags);
                $oFmFolder->setVersioning($sVersioning);
                $oFmFolder->setAutoApprobation($sAutoApprobation);

                $oFmFolder->setDownloadsCapping($sDownloadsCapping);
                $oFmFolder->setMaxDownloads($iMaxDownloads);
                $oFmFolder->setDownloadHistory($sDownloadHistory);
                $oFmFolder->setManualOrder($orderm);


                $bRedirect = false;

                if (true === $oFmFolder->save() && 0 !== $iIdOwner) {
                    //To rebuild sitemap
                    $bRedirect = true;
                }

                if ($bChangeFileIdOwner) {
                    $oFirstFmFolder = BAB_FmFolderSet::getFirstCollectiveFolder($sRelativePath);
                    $oFolderFileSet = new BAB_FolderFileSet();
                    $sPathName = $oFmFolder->getRelativePath() . $oFmFolder->getName() . '/';
                    $oFolderFileSet->setOwnerId($sPathName, $oFirstFmFolder->getId(), $oFmFolder->getId());

                    $soFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
                    $soFmFolderCliboardSet->setOwnerId($sPathName, $oFirstFmFolder->getId(), $oFmFolder->getId());
                }

                if (true === $bRedirect) {
                    $sUrl = $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId .
                        '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath);

                    header('Location: ' . $sUrl);
                    exit;
                }
            }
        }
        else
        {
            $babBody->msgerror = bab_translate("Access denied");
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}




function editFolderForUserDir()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();
    $sRelativePath = $oFileManagerEnv->sRelativePath;

//	if(canEdit($sRelativePath))
    if(canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $sDirName = (string) bab_pp('sDirName', '');
        $sDirName = trim($sDirName);
        $sOldDirName = (string) bab_pp('sOldDirName', '');
        $sOldDirName = trim($sOldDirName);

        if(mb_strlen($sDirName) > 0 && mb_strlen($sOldDirName) > 0)
        {
            $sPathName = $sRelativePath . $sOldDirName . '/';
            $sRootFmPath = $oFileManagerEnv->getRootFmPath();

//			bab_debug('sRootFmPath ==> ' . $sRootFmPath);
//			bab_debug('sRelativePath ==> ' . $sRelativePath);
//			bab_debug('sOldDirName ==> ' . $sOldDirName);
//			bab_debug('sDirName ==> ' . $sDirName);
//			bab_debug('sPathName ==> ' . $sPathName);
            $bFolderRenamed	= ($sDirName !== $sOldDirName) ? true : false;

            if($bFolderRenamed)
            {
                $sDirName = replaceInvalidFolderNameChar($sDirName);

                if(!isStringSupportedByFileSystem($sDirName))
                {
                    $babBody->addError(bab_translate("The directory name contains characters not supported by the file system"));
                    return ;
                }

                if(BAB_FmFolderHelper::renameDirectory($sRootFmPath, $sRelativePath, $sOldDirName, $sDirName))
                {
                    BAB_FolderFileSet::renameFolder($sPathName, $sDirName, 'N');
                    BAB_FmFolderCliboardSet::rename($sRelativePath, $sOldDirName, $sDirName, 'N');
                }
            }
        }
        else
        {
            $babBody->addError(bab_translate("Access denied"));
        }
    }
    else
    {
        $babBody->addError(bab_translate("Access denied"));
    }
}


function deleteFolder()
{
    global $babBody;

    $oFileManagerEnv =& getEnvObject();

    if($oFileManagerEnv->userIsInCollectiveFolder() || $oFileManagerEnv->userIsInRootFolder())
    {
        deleteFolderForCollectiveDir();
    }
    else if($oFileManagerEnv->userIsInPersonnalFolder())
    {
        deleteFolderForUserDir();
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}


function deleteFolderForCollectiveDir()
{
    global $babBody;
    $oFileManagerEnv =& getEnvObject();


    if(!canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $babBody->msgerror = bab_translate("Access denied");
        return false;
    }

    $sDirName = (string) bab_pp('sDirName', '');

    if(false !== mb_strpos($sDirName, '/'))
    {
        $babBody->msgerror = bab_translate("Please give a valid folder name");
        return false;
    }

    if(false !== mb_strpos($sDirName, '\\'))
    {
        $babBody->msgerror = bab_translate("Please give a valid folder name");
        return false;
    }

    if(0 === mb_strlen(trim($sDirName)))
    {
        $babBody->msgerror = bab_translate("Please give a valid folder name");
        return false;
    }
    if($sDirName === '..')
    {
        $babBody->msgerror = bab_translate("Please give a valid folder name");
        return false;
    }


    $iIdFld	= (int) bab_pp('iIdFolder', 0);
    if(0 !== $iIdFld)
    {
        require_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
        bab_deleteFolder($iIdFld);

    }
    else
    {
        $oFmFolderSet = new BAB_FmFolderSet();
        $oFmFolderSet->removeSimpleCollectiveFolder($oFileManagerEnv->sRelativePath . $sDirName . '/');
    }
}


function deleteFolderForUserDir()
{
    global $babBody, $BAB_SESS_USERID;

    $oFileManagerEnv =& getEnvObject();



    if(bab_userHavePersonnalStorage() && canCreateFolder($oFileManagerEnv->sRelativePath))
    {
        $sDirName = (string) bab_pp('sDirName', '');
        if(preg_match('#^(|.*[/\\\\])\.\.(|[/\\\\].*)$#', $sDirName) === 0)
        {
            if(mb_strlen(trim($sDirName)) > 0)
            {
                $sUploadPath = $oFileManagerEnv->getRootFmPath();

                global $babDB;

                $sPathName = $oFileManagerEnv->sRelativePath . '/' . $sDirName . '/';
                $sFullPathName = $sUploadPath . $sPathName;

                $sPathNameDirFile = $sPathName;
                if(substr($sPathName, 0, 1) == '/'){
                    $sPathNameDirFile = substr($sPathName, 1);
                }

                if (!file_exists($sFullPathName)) {
                    $babBody->msgerror = sprintf(bab_translate("The folder %s does not exists"), $sPathNameDirFile);
                    return false;
                }

                $oFolderFileSet = new BAB_FolderFileSet();
                $oPathName =& $oFolderFileSet->aField['sPathName'];
                $oIdOwner =& $oFolderFileSet->aField['iIdOwner'];
                $oGroup =& $oFolderFileSet->aField['sGroup'];
                $oFolderFileSet->remove(
                    $oPathName->like($babDB->db_escape_like($sPathNameDirFile) . '%')
                    ->_and($oIdOwner->in($BAB_SESS_USERID))
                    ->_and($oGroup->in('N'))
                );

                $oFmFolderSet = new BAB_FmFolderSet();
                $oFmFolderSet->removeDir($sFullPathName);

                $oFmFolderCliboardSet = new BAB_FmFolderCliboardSet();
                $oFmFolderCliboardSet->deleteFolder($sDirName, $oFileManagerEnv->sRelativePath, 'N');
            }
        }
        else
        {
            $babBody->msgerror = bab_translate("Please give a valid folder name");
        }
    }
    else
    {
        $babBody->msgerror = bab_translate("Access denied");
    }
}

function changeDelegation()
{
    $aVisibleDelegation = bab_getUserFmVisibleDelegations();
    $iDelegation = (int) bab_pp('iDelegation', 0);

    if(array_key_exists($iDelegation, $aVisibleDelegation))
    {
        bab_setCurrentUserDelegation($iDelegation);
    }
}



/* main */
initEnvObject();

$oFileManagerEnv =& getEnvObject();
if(false === $oFileManagerEnv->accessValid())
{
    if (!$GLOBALS['BAB_SESS_LOGGED']) {
        bab_requireCredential(bab_translate('You must be logged in to access this page.'));
    }
    $babBody->addError(bab_translate('Access denied'));
    return;
}

$idx = bab_rp('idx','list');

$sAction = isset($_POST['sAction']) ? $_POST['sAction'] :
    (isset($_GET['sAction']) ? $_GET['sAction'] :
    (isset($_POST['setRight']) ? 'setRight' : '???')
    );

switch($sAction)
{
    case 'editOrder':
        bab_requireSaveMethod();
        if (updateOrderFiles())
        {
            header("Location: ". $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' .
            bab_pp('id_record') . '&gr=' . bab_pp('gr_record') . '&path=' . urlencode(bab_pp('path_record')));
            exit;
        }
        break;

    case 'createFolder':
        bab_requireSaveMethod() && createFolder();
        break;

    case 'editFolder':
        if(!isset($_POST['sDeleteFolder']))
        {
            editFolder();
        }
        else
        {
            $idx = 'displayDeleteFolderConfirm';
        }
        break;

    case 'cutFolder':
        bab_requireSaveMethod() && cutFolder();
        break;

    case 'zipFolder':
        bab_requireSaveMethod() && zipFolder();
        break;

    case 'pasteFolder':
        bab_requireSaveMethod() && pasteFolder();
        break;

    case 'undopasteFolder':
        undoPasteFolder();
        break;

    case 'deleteFolder':
        bab_requireDeleteMethod() && deleteFolder();
        break;

    case 'deleteRestoreFile':
        if(!empty($_REQUEST['delete']))
        {
            bab_requireSaveMethod() && deleteFiles(bab_rp('items'));
        }
        else
        {
            bab_requireSaveMethod() && restoreFiles(bab_rp('items'));
        }
        break;

    case 'setRight':
        setRight();
        break;

    case 'saveFile':
        $aFiles = array();
        foreach($_FILES as $sFieldname => $file)
        {
            $aFiles[] = bab_fmFile::upload($sFieldname);
        }
        $optionsReadonly = bab_pp('readonly');
        if (!is_array($optionsReadonly) || count($optionsReadonly) != count($aFiles)) {
            $optionsReadonly = array();
            foreach ($aFiles as $file) {
                $optionsReadonly[] = 'N';
            }
        }

        bab_requireSaveMethod();

        $bSuccess = saveFile($aFiles, $oFileManagerEnv->iId, $oFileManagerEnv->sGr,
                $oFileManagerEnv->sPath, bab_pp('description'), bab_pp('keywords'),
                $optionsReadonly, bab_pp('maxdownloads', null));
        if(false === $bSuccess)
        {
            $idx = "displayAddFileForm";
        }
        else
        {
            //Pour faire apparaitre le lien approbation de la section utulisateur
            $sUrl = $GLOBALS['babUrlScript'] . '?tg=fileman&idx=list&id=' . $oFileManagerEnv->iId .
                '&gr=' . $oFileManagerEnv->sGr . '&path=' . urlencode($oFileManagerEnv->sPath);

            header('Location: ' . $sUrl);
            exit;
        }
        break;

    case 'updateFile':
        bab_requireSaveMethod();
        $bSuccess = saveUpdateFile(bab_pp('idf'), bab_fmFile::upload('uploadf'),
            bab_pp('fname'), bab_pp('description'), bab_pp('keywords'),
            bab_pp('readonly', 'N'), bab_pp('confirm'), bab_pp('bnotify'),
            isset($_POST['description']), bab_pp('maxdownloads'));
        if(false === $bSuccess)
        {
            $idx = 'viewFile';
        }
        else
        {
            $idx = 'unload';
        }
        break;

    case 'getFile':
        getFile();
        break;

    case 'cutFile':
        bab_requireSaveMethod() && cutFile();
        break;

    case 'undopasteFile':
        undoPasteFile();
        break;

    case 'pasteFile':
        bab_requireSaveMethod() && pasteFile();
        break;

    case 'delFile':
        bab_requireDeleteMethod() && delFile();
        break;

    case 'unzipFile':
        bab_requireSaveMethod() && unzipFile();
        break;

    case 'changeDelegation':
        changeDelegation();
        break;
}


switch($idx)
{
    case 'displayFolderForm':
        displayFolderForm();
        break;

    case 'displayOrderFolder':
        displayOrderFolder();
        break;

    case 'displayDeleteFolderConfirm':
        displayDeleteFolderConfirm();
        break;

    case 'unload':
        fileUnload();
        exit;
        break;

    case 'viewFile':
        viewFile();
        exit;
        break;

    case 'displayRightForm':
        displayRightForm();
        break;

    case 'displayAddFileForm':
        displayAddFileForm();
        break;

    case 'displayDownloadHistory':
        displayDownloadHistory();
        break;

    case 'trash':
        listTrashFiles();
        break;

    case 'disk':
        showDiskSpace();
        break;

    /* Called in ajax by the filemanager */
    case 'GetUploadBlock':
        GetUploadBlock();
        break;

    case 'getOrphanFileList':
        getOrphanFileList();
        break;

    case 'deleteOrphanFile':
        deleteOrphanFile();
        break;

    case 'getUnreferencedFilesList':
        deleteUnreferencedFiles(true); // simulation
        break;

    case 'deleteUnreferencedFiles':
        deleteUnreferencedFiles(false);
        break;

    default:
    case 'list':
        listFiles();
        break;
}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserFm');
