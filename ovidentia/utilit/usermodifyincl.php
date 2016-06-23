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



// for php < 5.4 (need allow_url_fopen)
if (!function_exists('getimagesizefromstring')) {
    function getimagesizefromstring($string_data)
    {
        $uri = 'data://application/octet-stream;base64,'  . base64_encode($string_data);
        return getimagesize($uri);
    }
}




/**
 * This file is included only if a user is added or modified
 * @package users
 */
class bab_userModify {


    /**
     * @static
     */
    public static function testBeforeCreate($firstname, $lastname, $middlename, $email, $nickname, $password1, $password2,  &$error) {

        global $babDB;

        if( empty($firstname) )
            {
            $error = bab_translate("Firstname is required");
            return false;
            }

        if( empty($lastname) )
            {
            $error = bab_translate("Lastname is required");
            return false;
            }


        if( empty($nickname) )
            {
            $error = bab_translate( "Login ID is required");
            return false;
            }

        if( empty($password1) || empty($password2))
            {
            $error = bab_translate( "Passwords not match !!");
            return false;
            }

        if( $password1 != $password2)
            {
            $error = bab_translate("Passwords not match !!");
            return false;
            }

        $oPwdComplexity = @bab_functionality::get('PwdComplexity');
        if (!$oPwdComplexity->isValid($password1)) {
            $error = $oPwdComplexity->getErrorDescription();
            return false;
        }

        $query = "select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'";
        $res = $babDB->db_query($query);
        if( $babDB->db_num_rows($res) > 0)
            {
            $error = bab_translate("This login ID already exists !!");
            return false;
            }


        $replace = array( " " => "", "-" => "");

        $hashname = md5(mb_strtolower(strtr($firstname.$middlename.$lastname, $replace)));
        $query = "select id from ".BAB_USERS_TBL." where hashname='".$babDB->db_escape_string($hashname)."'";
        $res = $babDB->db_query($query);
        if( $babDB->db_num_rows($res) > 0)
            {
            $error = bab_translate("Firstname and Lastname already exists !!");
            return false;
            }

        return true;
    }


    /**
     * @static
     */
    public static function addUser($firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, &$error, $bgroup) {

        global $babBody, $babLanguage, $babDB;

        if (!bab_userModify::testBeforeCreate($firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $error)) {
            return false;
        }

        $BAB_HASH_VAR = bab_getHashVar();

        $password1=mb_strtolower($password1);
        $hash=md5($nickname.$BAB_HASH_VAR);
        if( $isconfirmed )
            {
            $isconfirmed = 1;
            }
        else
            {
            $isconfirmed = 0;
            }

        $replace = array( " " => "", "-" => "");
        $hashname = md5(mb_strtolower(strtr($firstname.$middlename.$lastname, $replace)));

        $sql="insert into ".BAB_USERS_TBL." (nickname, firstname, lastname, hashname, password,email,date,confirm_hash,is_confirmed,changepwd,lang, langfilter, datelog, lastlog) ".
            "values (
            '". $babDB->db_escape_string($nickname)."',
            '".$babDB->db_escape_string($firstname)."',
            '".$babDB->db_escape_string($lastname)."',
            '".$babDB->db_escape_string($hashname)."',
            '". md5($password1) ."',
            '".$babDB->db_escape_string($email)."',
             now(),
             '".$babDB->db_escape_string($hash)."',
             '".$babDB->db_escape_string($isconfirmed)."',
             '1',
             '',
             '".$babDB->db_escape_string(bab_getInstance('babLanguageFilter')->getFilterAsInt())."',
              now(),
              now()
              )";

        $result=$babDB->db_query($sql);
        if ($result)
            {
            $id = $babDB->db_insert_id();
            list($pcalendar) = $babDB->db_fetch_row($babDB->db_query("select pcalendar as pcal from ".BAB_GROUPS_TBL." where id='".BAB_REGISTERED_GROUP."'"));
            $babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type, actif) values ('".$babDB->db_escape_string($id)."', '1', '".$pcalendar."')");
            $idusercal = $babDB->db_insert_id();
            $babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_TBL."
                (givenname, mn, sn, email, id_directory, id_user)
                values
                ('".$babDB->db_escape_string($firstname)."',
                '".$babDB->db_escape_string($middlename)."',
                '".$babDB->db_escape_string($lastname)."',
                '".$babDB->db_escape_string($email)."',
                '0',
                '".$id."'
                )");

            if( isset($babBody->babsite) && isset($babBody->babsite['iDefaultCalendarAccess'] ))
            {
                $iDefaultCalendarSiteAccess = (int)($babBody->babsite['iDefaultCalendarAccess']);

                // other users share there calendars with me

                $query = "select
                        c.id,
                        c.owner,
                        uo.iDefaultCalendarAccess,
                        uo.calendar_backend
                    FROM ".BAB_CALENDAR_TBL." c
                        left join ".BAB_CAL_USER_OPTIONS_TBL." uo ON c.owner=uo.id_user
                    WHERE
                        c.type=".BAB_CAL_USER_TYPE."
                ";


                if( $iDefaultCalendarSiteAccess == BAB_CAL_ACCESS_NONE )
                {
                    // no site option, narrow query to user with setting
                    $query .= " and uo.iDefaultCalendarAccess is not null and uo.iDefaultCalendarAccess != ".BAB_CAL_ACCESS_NONE;
                }

                $resc = $babDB->db_query($query);

                while ($arrc = $babDB->db_fetch_assoc($resc))
                {
                    $backend = bab_functionality::get('CalendarBackend/'.$arrc['calendar_backend']);
                    if (!$backend)
                    {
                        continue;
                    }

                    $calendar = $backend->Personalcalendar($arrc['owner']);

                    $access = $arrc['iDefaultCalendarAccess'];
                    if( $iDefaultCalendarSiteAccess != BAB_CAL_ACCESS_NONE && (null === $access || BAB_CAL_ACCESS_NONE == $access))
                    {
                        $access = $iDefaultCalendarSiteAccess;
                    }


                    $babDB->db_query("
                        INSERT INTO ".BAB_CALACCESS_USERS_TBL."
                            (id_cal, id_user, bwrite, caltype)
                        VALUES
                            (
                                ".$babDB->quote($arrc['id']).",
                                ".$babDB->quote($id).",
                                ".$babDB->quote($access).",
                                ".$babDB->quote($calendar->getReferenceType())."
                            )
                    ");
                }



                if( $iDefaultCalendarSiteAccess != BAB_CAL_ACCESS_NONE )
                {
                    $resc = $babDB->db_query("select id from ".BAB_USERS_TBL." where id !=".$babDB->quote($id));

                    while ($arrc = $babDB->db_fetch_assoc($resc))
                    {
                        $babDB->db_query("insert into ".BAB_CALACCESS_USERS_TBL."
                            (id_cal, id_user, bwrite, caltype) VALUES (
                                ".$babDB->quote($idusercal).",
                                ".$babDB->quote($arrc['id']).",
                                ".$babDB->quote($iDefaultCalendarSiteAccess).",
                                ".$babDB->quote('personal')."
                            )
                        ");
                    }

                }
            }

            if( $bgroup && isset($babBody->babsite['idgroup']) && $babBody->babsite['idgroup'] != 0)
                {
                bab_addUserToGroup($id, $babBody->babsite['idgroup']);
                }
            else
                {
                $babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
                }

            include_once $GLOBALS['babInstallPath']."utilit/eventdirectory.php";
            $event = new bab_eventUserCreated($id);
            bab_fireEvent($event);

            // notifiy the user into registered users
            $event = new bab_eventUserAttachedToGroup($id, BAB_REGISTERED_GROUP);
            bab_fireEvent($event);

            /**
             * @deprecated
             */
            include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
            bab_callAddonsFunction('onUserCreate', $id);
            return $id;
            }
        else
            return false;
    }



    /**
     * Update a user
     * update only the registrer user directory
     *
     * @param	int		$id
     * @param	array	$info
     * @param	string	&$error
     * @return 	boolean
     */
    public static function updateUserById($id, $info, &$error) {


        global $babDB, $BAB_HASH_VAR;
        $res = $babDB->db_query('select u.*, det.mn, det.id as id_entry
              from '.BAB_USERS_TBL.' u
              left join '.BAB_DBDIR_ENTRIES_TBL.' det on det.id_user=u.id
           WHERE
              u.id=\''.$babDB->db_escape_string($id).'\'
              AND det.id_directory='.$babDB->quote(0).'
        ');

        $arruq = array();
        $arrdq = array();

        if (!$res || 0 === $babDB->db_num_rows($res) )
        {
            $error = bab_translate("Unknown user");
            return false;
        }


        $arruinfo = $babDB->db_fetch_array($res);

        if (!is_array($info) || 0 === count($info))
        {
            $error = bab_translate("Nothing Changed");
            return false;
        }

        if (isset($info['nickname']) )
        {
            $info['nickname'] = trim($info['nickname']);

            if (empty($info['nickname'])) {
                $error = bab_translate("You must provide a nickname");
                return false;
            }

            $res = $babDB->db_query("select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($info['nickname'])."' and id !='".$arruinfo['id']."'");
            if( $babDB->db_num_rows($res) > 0) {
                $error = bab_translate("This login ID already exists !!");
                return false;
            }

            $hash = md5($info['nickname'].$BAB_HASH_VAR);
            $arruq[] = 'confirm_hash=\''.$babDB->db_escape_string($hash).'\'';
            $arruq[] = 'nickname=\''.$babDB->db_escape_string($info['nickname']).'\'';
        }

        if( isset($info['password']) && empty($info['password']) )
        {
            $error = bab_translate("Empty password");
            return false;
        }

        if( isset($info['password']) )
        {
            if(!bab_updateUserPasswordById($arruinfo['id'], $info['password'], $info['password'], true, true, $error))
            {
                return false;
            }
        }

        if( isset($info['disabled']))
        {
            if($info['disabled'])
            {
                $arruq[] =  'disabled=1';
            }
            else
            {
                $arruq[] =  'disabled=0';
            }
        }


        if( isset($info['is_confirmed']))
        {
            if($info['is_confirmed'])
            {
                $arruq[] =  'is_confirmed=1';
            }
            else
            {
                $arruq[] =  'is_confirmed=0';
            }
        }

        if( isset($info['email']))
        {
            $arruq[] =  'email=\''.$babDB->db_escape_string($info['email']).'\'';
        }



        if (isset($info['jpegphoto'])) {

            if ($info['jpegphoto'] instanceOf bab_fileHandler) {

                // process photo import by file upload or file copy

                if (false !== $tmppath = $info['jpegphoto']->importTemporary()) {
                    include_once dirname(__FILE__).'/dirincl.php';
                    $photo = new bab_dirEntryPhoto($arruinfo['id_entry']);
                    if (!$photo->setDataByFile($tmppath)) {
                        $error = bab_translate("photo cannot be updated");
                        return false;
                    }
                }
            } else if ('' === $info['jpegphoto']) {
                // empty string to remove photo from table

                $arrdq[] = "photo_data=''";
                $arrdq[] = "photo_type=''";

            } else {

                // detect mime from given string

                $ims = getimagesizefromstring($info['jpegphoto']);

                if (isset($ims['mime']))
                {
                    $arrdq[] = "photo_data=".$babDB->quote($info['jpegphoto']);
                    $arrdq[] = "photo_type=".$babDB->quote($ims['mime']);
                }
            }

            unset($info['jpegphoto']);
        }



        if( isset($info['sn']) || isset($info['givenname']) || isset($info['mn']))
        {
            if( isset($info['sn']))
            {
                if ('' === $info['sn'])
                {
                    $error = bab_translate( "Lastname is required");
                    return false;
                } else {
                    $lastname = $info['sn'];
                }
            }
            else
            {
                $lastname = $arruinfo['lastname'];
            }

            if( isset($info['givenname']))
            {
                if ('' === $info['givenname'])
                {
                    $error = bab_translate( "Firstname is required");
                    return false;
                } else {
                    $firstname = $info['givenname'];
                }
            } else {
                $firstname = $arruinfo['firstname'];
            }

            if( isset($info['mn']))
            {
                $mn = $info['mn'];
            }
            else
            {
                $mn = $arruinfo['mn'];
            }

            $replace = array( " " => "", "-" => "");
            $hashname = md5(mb_strtolower(strtr($firstname.$mn.$lastname, $replace)));
            $arruq[] =  'firstname=\''.$babDB->db_escape_string($firstname).'\'';
            $arruq[] =  'lastname=\''.$babDB->db_escape_string($lastname).'\'';
            $arruq[] =  'hashname=\''.$babDB->db_escape_string($hashname).'\'';

            $arrdq[] =  'givenname=\''.$babDB->db_escape_string($firstname).'\'';
            $arrdq[] =  'sn=\''.$babDB->db_escape_string($lastname).'\'';
            $arrdq[] =  'mn=\''.$babDB->db_escape_string($mn).'\'';

        }

        if(isset($info['force_pwd_change'])){
            $arruq[] =  'force_pwd_change=\''.$babDB->db_escape_string($info['force_pwd_change']).'\'';
        }

        if( count($arruq))
        {
            $babDB->db_query('update '.BAB_USERS_TBL.' set '.implode(',', $arruq).' where id=\''.$babDB->db_escape_string($id).'\'');
        }

        $res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='0'");
        while( $arr = $babDB->db_fetch_array($res))
            {
            if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
                {
                $rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
                $fieldname = $rr['name'];
                    switch( $fieldname )
                    {
                        case 'sn':
                        case 'givenname':
                        case 'mn':
                            break;
                        default:
                            if( isset($info[$fieldname]))
                            {
                            $arrdq[] =  $fieldname.'=\''.$babDB->db_escape_string($info[$fieldname]).'\'';
                            }
                            break;
                    }

                }
            else
                {
                $rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
                $fieldname = "babdirf".$arr['id'];
                if( isset($info[$fieldname]))
                    {
                    $res2 = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($arr['id'])."' and id_entry='".$babDB->db_escape_string($arruinfo['id_entry'])."'");
                    if( $res2 && $babDB->db_num_rows($res2) > 0 )
                        {
                        $arr2 = $babDB->db_fetch_array($res2);
                        $babDB->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".$babDB->db_escape_string($info[$fieldname])."' where id='".$babDB->db_escape_string($arr2['id'])."'");
                        }
                    else
                        {
                        $babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (id_fieldx, id_entry, field_value) values('".$babDB->db_escape_string($arr['id'])."','".$babDB->db_escape_string($arruinfo['id_entry'])."','".$babDB->db_escape_string($info[$fieldname])."')");
                        }
                    }
                }
            }

        if( count($arrdq))
        {
            $arrdq[] = 'date_modification=NOW()';
            $arrdq[] = 'id_modifiedby='.$babDB->quote(bab_getUserId());

            $babDB->db_query('update '.BAB_DBDIR_ENTRIES_TBL.' set '.implode(',', $arrdq).' where id=\''.$babDB->db_escape_string($arruinfo['id_entry']).'\'');
        }

        require_once($GLOBALS['babInstallPath']."utilit/eventdirectory.php");
        $event = new bab_eventUserModified($id);
        bab_fireEvent($event);

        return true;
    }
}

