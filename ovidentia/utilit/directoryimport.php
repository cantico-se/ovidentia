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


require_once dirname(__FILE__) . '/path.class.php';
require_once dirname(__FILE__) . '/session.class.php';
require_once dirname(__FILE__) . '/userinfosincl.php';
require_once dirname(__FILE__) . '/../admin/register.php';
require_once dirname(__FILE__) . '/install.class.php';


/**
 * Error on mapping
 * go back to mapping page
 */
class bab_DirImportMappingException extends Exception 
{
    
}

/**
 * Error on one entry
 * Display one message in log
 */
class bab_DirImportEntryException extends Exception
{
    
}


class bab_processImportUsers
{

    /**
     *
     * @var int
     */
    private $id_directory;

    /**
     * A file in tmp where all the created users are stored
     * the file will be used to send notification email
     *
     * One user per row: id_user, password
     *
     * @var bab_Path
     */
    private $file;
    
    
    /**
     * @var bool
     */
    public $notifyuser = false;
    

    /**
     *
     * @param int $id_directory            
     */
    public function __construct($id_directory)
    {
        $this->id_directory = $id_directory;
        
        // create a temporary file to store the created users
        
        $this->file = new bab_Path($GLOBALS['babUploadPath'], 'tmp');
        $this->file->createDir();
        $this->file->push(session_id() . '_created_users.csv');
        
        if ($this->file->fileExists()) {
            $this->file->delete();
        }
    }

    /**
     * Add a user to the temporary CSV file
     * 
     * @param int $id_user            
     * @param string | null $password
     */
    public function addUser($id_user, $password)
    {
        if (!$this->notifyuser) {
            return;
        }
        
        if (null !== $password) {
            $password = str_replace('"', '""', $password);
        }
        
        $csvline = '"' . $id_user . '","' . ((string) $password) . '"' . "\n";
        return file_put_contents($this->file->tostring(), $csvline, FILE_APPEND);
    }

    /**
     * Display HTML page with progress
     */
    public function displayProgress()
    {

        $session = bab_getInstance('bab_Session');
        $session->bab_directory_import = $_POST;
        
        if ($this->notifyuser) {
            $session->bab_directory_import_email_tmp_file = $this->file->getBasename();
        }
        
        $t_upgrade = bab_translate('Import into a directory');
        $t_continue = bab_translate('Back to directory');
        $frameurl = $GLOBALS['babUrlScript'] . '?tg=directory&idx=monitorimport';
        $nextpageurl = $GLOBALS['babUrlScript'] . '?tg=directory&idx=sdbovml&directoryid=' . $this->id_directory;
        
        bab_installWindow::getPage($t_upgrade, $frameurl, $t_continue, $nextpageurl);
        
        return true;
    }

    public static function iframe()
    {
        $frame = new bab_installWindow();
        $frame->setStartMessage(bab_translate('Start CSV file import'));
        $frame->setStopMessage(bab_translate('Task done'), bab_translate('Import fail'));
        
        $frame->startInstall(array(
            __CLASS__,
            'iframe_process'
        ));
        die();
    }
    
    
    
    private static function importUsers()
    {
        $session = bab_getInstance('bab_Session');
        $post = $session->bab_directory_import;
        
        processImportDbFile($post);
    }
    
    

    /**
     * iframe progress
     */
    private static function sendEmails()
    {
        $session = bab_getInstance('bab_Session');
        
        if (!isset($session->bab_directory_import_email_tmp_file)) {
            return false;
        }
        
        $file = new bab_Path($GLOBALS['babUploadPath'], 'tmp', $session->bab_directory_import_email_tmp_file);
        if (! $file->fileExists()) {
            return false;
        }
        
        $fd = fopen($file->tostring(), "r");
        if ($fd) {
            while ($arr = fgetcsv($fd, 1024)) {
                if ($user = bab_userInfos::getRow($arr[0])) {
                    $name = bab_composeUserName($user['firstname'], $user['lastname']);
                    $email = trim($user['email']);
                    if (empty($email)) {
                        bab_installWindow::message(sprintf(bab_translate('Error, empty email address for %s'), $name));
                        continue;
                    }
                    
                    if (notifyAdminUserRegistration($name, $email, $user['nickname'], $arr[1])) {
                        bab_installWindow::message(sprintf(bab_translate('Notification sent to %s'), $name));
                    } else {
                        bab_installWindow::message(sprintf(bab_translate('Error : failed to notify %s (%s)'), $name, $email));
                    }
                }
            }
        }
        
        $file->delete();
        unset($session->bab_directory_import_email_tmp_file);
        return true;
    }
    
    
    /**
     * iframe progress
     * 
     * @throws bab_DirImportMappingException exception should move the user to the mapping page
     */
    public static function iframe_process()
    {
        self::importUsers();
        self::sendEmails();
        
        return true; // see setStopMessage
    }
}



/**
 * Import one csv row
 * 
 * @param int $idgroup
 * @param array $arr        The CSV row
 * @param array $post       posted mapping page
 * 
 */
function bab_directoryImportOneEntry($idgroup, Array $arr, Array $post, Array $arridfx, Array $arrnamef, bab_processImportUsers $monitor, $pcalendar)
{
    global $babDB;
    
    $id = $post['id'];
    
    if (empty($arr[$post['givenname']])) {
        throw new bab_DirImportEntryException(bab_translate('The firstname is missing'));
    }
    
    if (empty($arr[$post['sn']])) {
        throw new bab_DirImportEntryException(bab_translate('The lastname is missing'));
    }
    
    if ($idgroup > 0) {
        if (empty($arr[$post['nickname']])) {
            throw new bab_DirImportEntryException(bab_translate('The nickname is missing'));
        }
    }
    
    switch ($post['duphand']) {
        case 1: // Replace duplicates with items imported
        case 2: // Do not import duplicates
            if ($idgroup > 0) {
                $query = "select id from " . BAB_USERS_TBL . " where
                            nickname='" . $babDB->db_escape_string($arr[$post['nickname']]) . "'
                            OR (firstname LIKE '" . $babDB->db_escape_like($arr[$post['givenname']]) . "'
                                AND lastname LIKE '" . $babDB->db_escape_like($arr[$post['sn']]) . "')";
    
                $res2 = $babDB->db_query($query);
                if ($babDB->db_num_rows($res2) > 0) {
                    if (2 == $post['duphand']) {
                        bab_installWindow::message(bab_toHtml(sprintf(bab_translate('The directory entry %s has been ignored because of nickname or lastname/firstname duplication'), $arr[$post['sn']].' '.$arr[$post['givenname']])));
                        break;
                    }
    
                    $rrr = $babDB->db_fetch_array($res2);
                    $req = '';
    
                    for ($k = 0; $k < count($arrnamef); $k ++) {
                        if (isset($post[$arrnamef[$k]]) && $post[$arrnamef[$k]] != "") {
                            $req .= $arrnamef[$k] . "='" . $babDB->db_escape_string($arr[$post[$arrnamef[$k]]]) . "',";
                        }
                    }
    
                    $bupdate = false;
                    if (! empty($req)) {
                        $req = mb_substr($req, 0, mb_strlen($req) - 1);
                        $req = "update " . BAB_DBDIR_ENTRIES_TBL . " set " . $req;
                        $req .= " where id_directory='0' and id_user='" . $babDB->db_escape_string($rrr['id']) . "'";
                        $babDB->db_query($req);
                        $bupdate = true;
                    }
    
                    if (count($arridfx) > 0) {
                        list ($idu) = $babDB->db_fetch_array($babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='0' and id_user='" . $babDB->db_escape_string($rrr['id']) . "'"));
                        for ($k = 0; $k < count($arridfx); $k ++) {
                            if (isset($arr[$post["babdirf" . $arridfx[$k]]])) {
                                $bupdate = true;
                                $rs = $babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_fieldx='" . $babDB->db_escape_string($arridfx[$k]) . "' and  id_entry='" . $babDB->db_escape_string($idu) . "'");
                                if ($rs && $babDB->db_num_rows($rs) > 0) {
                                    $babDB->db_query("update " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " set field_value='" . $babDB->db_escape_string($arr[$post["babdirf" . $arridfx[$k]]]) . "' where id_fieldx='" . $babDB->db_escape_string($arridfx[$k]) . "' and id_entry='" . $babDB->db_escape_string($idu) . "'");
                                } else {
                                    $babDB->db_query("insert into " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " ( field_value, id_fieldx, id_entry) values ('" . $babDB->db_escape_string($arr[$post["babdirf" . $arridfx[$k]]]) . "', '" . $babDB->db_escape_string($arridfx[$k]) . "', '" . $babDB->db_escape_string($idu) . "')");
                                }
                            }
                        }
                    }
    
                    $password3 = $post['password3'];
    
                    if ($password3 !== '') {
                        $pwd = false;
                        if (mb_strlen($arr[$password3]) >= 6) {
                            $pwd = mb_strtolower($arr[$password3]);
                        }
                    } else {
                        $pwd = mb_strtolower($post['password1']);
                    }
                    $replace = array(
                        " " => "",
                        "-" => ""
                    );
                    $hashname = md5(mb_strtolower(strtr($arr[$post['givenname']] . $arr[$post['mn']] . $arr[$post['sn']], $replace)));
                    $hash = md5($arr[$post['nickname']] . bab_getHashVar());
    
                    $query = "update " . BAB_USERS_TBL . " set
                                nickname='" . $babDB->db_escape_string($arr[$post['nickname']]) . "',
                                firstname='" . $babDB->db_escape_string($arr[$post['givenname']]) . "',
                                lastname='" . $babDB->db_escape_string($arr[$post['sn']]) . "',
                                email='" . $babDB->db_escape_string($arr[$post['email']]) . "',
                                hashname='" . $babDB->db_escape_string($hashname) . "',
                                confirm_hash='" . $babDB->db_escape_string($hash) . "' ";
    
                    if (false !== $pwd) {
                        $query .= ", password='" . $babDB->db_escape_string(md5($pwd)) . "' ";
                    }
    
                    $query .= " where id='" . $babDB->db_escape_string($rrr['id']) . "'";
    
                    $babDB->db_query($query);
                    if ($bupdate) {
                        $babDB->db_query("update " . BAB_DBDIR_ENTRIES_TBL . " set date_modification=now(), id_modifiedby='" . $babDB->db_escape_string(bab_getUserId()) . "' where id_directory='0' and id_user='" . $babDB->db_escape_string($rrr['id']) . "'");
                    }
    
                    if ($idgroup > 1) {
                        bab_addUserToGroup($rrr['id'], $idgroup);
                    }
    
    
                    $emailpwd = ($pwd && 'Y' === $post['sendpwd']) ? $pwd : null;
                    $monitor->addUser($rrr['id'], $pwd);
    
                    bab_installWindow::message(bab_toHtml(sprintf(bab_translate('The directory entry %s has been updated'), $arr[$post['sn']].' '.$arr[$post['givenname']])));
                    break;
                }
            } else {
                $res2 = $babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_TBL . " where 
                    givenname='" . $babDB->db_escape_string($arr[$post['givenname']]) . "' 
                    and sn='" . $babDB->db_escape_string($arr[$post['sn']]) . "' 
                    and id_directory='" . $babDB->db_escape_string($id) . "'
                ");
                
                if ($res2 && $babDB->db_num_rows($res2) > 0) {
                    if (2 == $post['duphand']) {
                        bab_installWindow::message(bab_toHtml(sprintf(bab_translate('The directory entry %s has been ignored because of lastname/firstname duplication'), $arr[$post['sn']].' '.$arr[$post['givenname']])));
                        break;
                    } else {
                            $arr2 = $babDB->db_fetch_array($res2);
                    }

                    $req = '';
                    for ($k = 0; $k < count($arrnamef); $k ++) {
                        if (isset($post[$arrnamef[$k]]) && $post[$arrnamef[$k]] != "") {
                            $req .= $arrnamef[$k] . "='" . $babDB->db_escape_string($arr[$post[$arrnamef[$k]]]) . "',";
                        }
                    }

                    $bupdate = false;
                    if (! empty($req)) {
                        $req = mb_substr($req, 0, mb_strlen($req) - 1);
                        $req = "update " . BAB_DBDIR_ENTRIES_TBL . " set " . $req;
                        $req .= " where id='" . $babDB->db_escape_string($arr2['id']) . "'";
                        $babDB->db_query($req);
                        $bupdate = true;
                    }

                    if (count($arridfx) > 0) {
                        $bupdate = true;
                        for ($k = 0; $k < count($arridfx); $k ++) {
                            if (isset($arr[$post["babdirf" . $arridfx[$k]]])) {
                                $bupdate = true;
                                $rs = $babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_fieldx='" . $babDB->db_escape_string($arridfx[$k]) . "' and  id_entry='" . $babDB->db_escape_string($arr2['id']) . "'");
                                if ($rs && $babDB->db_num_rows($rs) > 0) {
                                    $babDB->db_query("update " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " set field_value='" . addslashes($arr[$post["babdirf" . $arridfx[$k]]]) . "' where id_fieldx='" . $babDB->db_escape_string($arridfx[$k]) . "' and id_entry='" . $babDB->db_escape_string($arr2['id']) . "'");
                                } else {
                                    $babDB->db_query("insert into " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " ( field_value, id_fieldx, id_entry) values ('" . $babDB->db_escape_string($arr[$post["babdirf" . $arridfx[$k]]]) . "', '" . $babDB->db_escape_string($arridfx[$k]) . "', '" . $babDB->db_escape_string($arr2['id']) . "')");
                                }
                            }
                        }
                    }
                    if ($bupdate) {
                        $babDB->db_query("update " . BAB_DBDIR_ENTRIES_TBL . " set date_modification=now(), id_modifiedby='" . $babDB->db_escape_string(bab_getUserId()) . "' where id='" . $babDB->db_escape_string($arr2['id']) . "'");
                    }
                    
                    bab_installWindow::message(bab_toHtml(sprintf(bab_translate('The directory entry %s has been updated'), $arr[$post['sn']].' '.$arr[$post['givenname']])));
                    break;
                }
            }
            
            
    
            /* no break; */
    
        case 0: // Allow duplicates to be created or create a new entry
            $req = "";
            $arrv = array();
            for ($k = 0; $k < count($arrnamef); $k ++) {
                if (isset($post[$arrnamef[$k]]) && $post[$arrnamef[$k]] != "") {
                    $req .= $arrnamef[$k] . ",";
                    $val = isset($arr[$post[$arrnamef[$k]]]) ? $arr[$post[$arrnamef[$k]]] : '';
                    array_push($arrv, $val);
                }
            }
    
            if (! empty($req)) {
                $req = "insert into " . BAB_DBDIR_ENTRIES_TBL . " (" . $req . "id_directory,date_modification,id_modifiedby) values (";
                for ($i = 0; $i < count($arrv); $i ++) {
                    $req .= "'" . $babDB->db_escape_string($arrv[$i]) . "',";
                }
                
                $req .= "'" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "',";
                $req .= "now(), '" . $babDB->db_escape_string(bab_getUserId()) . "')";
                $babDB->db_query($req);
                $idu = $babDB->db_insert_id();
                
                if ($idgroup > 0) {
                    
                    $replace = array( " " => "", "-" => "");
                    $hashname = md5(mb_strtolower(strtr($arr[$_POST['givenname']].$arr[$_POST['mn']].$arr[$_POST['sn']], $replace)));
                    $hash=md5($arr[$_POST['nickname']].bab_getHashVar());
                    if( bab_rp('password3') !== '' && mb_strlen($arr[bab_rp('password3')]) >= 6)
                        {
                        $pwd = mb_strtolower($arr[bab_rp('password3')]);
                        }
                    else
                        {
                        $pwd = mb_strtolower(bab_rp('password1'));
                        }

                    $babDB->db_query("insert into ".BAB_USERS_TBL." set 
                        nickname='".$babDB->db_escape_string($arr[$_POST['nickname']])."', 
                        firstname='".$babDB->db_escape_string($arr[$_POST['givenname']])."', 
                        lastname='".$babDB->db_escape_string($arr[$_POST['sn']])."', 
                        email='".$babDB->db_escape_string($arr[$_POST['email']])."', 
                        hashname='".$hashname."', 
                        password='".$babDB->db_escape_string(md5($pwd))."', 
                        confirm_hash='".$babDB->db_escape_string($hash)."', 
                        date=now(), 
                        is_confirmed='1', 
                        changepwd='1', 
                        lang=''
                   ");
                    
                    $iduser = $babDB->db_insert_id();
                    $babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type, actif) values ('".$babDB->db_escape_string($iduser)."', '1', ".$babDB->quote($pcalendar).")");
                    $babDB->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set id_user='".$babDB->db_escape_string($iduser)."' where id='".$babDB->db_escape_string($idu)."'");
                    if( $idgroup > 1 ) {
                        bab_addUserToGroup($iduser, $idgroup);
                    }

                    $emailpwd = ('Y' === bab_rp('sendpwd')) ? $pwd : null;
                    $monitor->addUser($iduser, $emailpwd);
                    
                }

                if (count($arridfx) > 0) {
                    for ($k = 0; $k < count($arridfx); $k ++) {
                        $val = isset($arr[$post["babdirf" . $arridfx[$k]]]) ? addslashes($arr[$post["babdirf" . $arridfx[$k]]]) : '';
                        $babDB->db_query("insert into " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " (id_fieldx, id_entry, field_value) values('" . $babDB->db_escape_string($arridfx[$k]) . "','" . $babDB->db_escape_string($idu) . "','" . $babDB->db_escape_string($val) . "')");
                    }
                }
                
                bab_installWindow::message(bab_toHtml(sprintf(bab_translate('The directory entry %s has been created'), $arr[$post['sn']].' '.$arr[$post['givenname']])));
                
            }
            break;
    }
}




/**
 * Import CSV file to database
 * 
 * @throws bab_DirImportMappingException
 */
function processImportDbFile($post, $import = true)
{
    global $babDB;
    
    $pfile = $post['pfile'];    // Full path to the temporary uploaded csv file
    $id = $post['id'];          // Directory id
    $separ = $post['separ'];    // CSV separator
    $monitor = new bab_processImportUsers($id);
    
    
    if (! file_exists($pfile)) {
        throw new bab_DirImportMappingException(bab_translate("No ongoing import"));
    }
    
    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    if($idgroup > 0)
    {
        list($pcalendar) = $babDB->db_fetch_row($babDB->db_query("select pcalendar as pcal from ".BAB_GROUPS_TBL." where id='".$idgroup."'"));
    }
    
    $arridfx = array();
    $arrnamef = array();
    $res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "'");
    while ($arr = $babDB->db_fetch_array($res)) {
        if ($arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
            $rr = $babDB->db_fetch_array($babDB->db_query("select description, name from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'"));
            $fieldname = $rr['name'];
            $arrnamef[] = $fieldname;
        } else {
            $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
            $fieldname = "babdirf" . $arr['id'];
            $arridfx[] = $arr['id'];
        }
        
        if ($arr['required'] == "Y" && (! isset($post[$fieldname]) || $post[$fieldname] == "")) {
            throw new bab_DirImportMappingException(bab_translate("You must complete required fields"));
        }
    }
    
    if ($idgroup > 0) {
        if ('' == $post['password1'] || '' == $post['password2'] || mb_strlen($post['nickname']) == 0) {
            throw new bab_DirImportMappingException(bab_translate("You must complete required fields"));
        }
        
        if (! isset($post['sn']) || $post['sn'] == "" || ! isset($post['givenname']) || $post['givenname'] == "") {
            throw new bab_DirImportMappingException(bab_translate("You must complete firstname and lastname fields !!"));
            return false;
        }
        
        $minPasswordLengh = 6;
        if (isset($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])) {
            $minPasswordLengh = $GLOBALS['babMinPasswordLength'];
            if ($minPasswordLengh < 1) {
                $minPasswordLengh = 1;
            }
        }
        if (mb_strlen($post['password1']) < $minPasswordLengh) {
            throw new bab_DirImportMappingException(sprintf(bab_translate("Password must be at least %s characters !!"), $minPasswordLengh));
        }
        
        if ($post['password1'] != $post['password2']) {
            throw new bab_DirImportMappingException(bab_translate("Passwords not match !!"));
        }
        
        $monitor->notifyuser = ('Y' == $post['notifyuser']);
    
    }
    
    if (!$import) {
        return;
    }
    
    $encoding = 'ISO-8859-15';
    if (isset($post['encoding'])) {
        $encoding = $post['encoding'];
    }
    
    $fd = fopen($pfile, "r");
    if ($fd) {
        $arr = fgetcsv($fd, 4096, $separ); // skip first line
        $line = 1;
        while ($arr = bab_getStringAccordingToDataBase(fgetcsv($fd, 4096, $separ), $encoding)) {
            $line++;
            try {
                bab_directoryImportOneEntry($idgroup, $arr, $post, $arridfx, $arrnamef, $monitor, $pcalendar);
            } catch(bab_DirImportEntryException $e) {
                bab_installWindow::message(
                    '<strong>'.
                    bab_toHtml(sprintf(bab_translate('Line %d:'), $line)).
                    '</strong> '.
                    bab_toHtml($e->getMessage())
                );
            }
            
            
        }
        fclose($fd);
        unlink($pfile);
    }
    
    
}

