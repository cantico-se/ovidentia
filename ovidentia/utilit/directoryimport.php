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





/***
 * Import CSV file to database
 * This work only in a POST request
 * 
 * @param string $pfile Full path to the temporary uploaded csv file
 * @param int    $id    Directory id
 * @param string $separ CSRV separator
 */
function processImportDbFile( $pfile, $id, $separ )
    {
    global $babBody, $babDB;


    if (!file_exists($pfile))
    {
        $babBody->msgerror = bab_translate("No ongoing import");
        return false;
    }


    list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
    if($idgroup > 0)
    {
        list($pcalendar) = $babDB->db_fetch_row($babDB->db_query("select pcalendar as pcal from ".BAB_GROUPS_TBL." where id='".$idgroup."'"));
    }

    $arridfx = array();
    $arrnamef = array();
    $res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($id))."'");
    while( $arr = $babDB->db_fetch_array($res))
        {
        if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
            {
            $rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
            $fieldname = $rr['name'];
            $arrnamef[] = $fieldname;
            }
        else
            {
            $rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
            $fieldname = "babdirf".$arr['id'];
            $arridfx[] = $arr['id'];
            }

        if( $arr['required'] == "Y" && (!isset($_POST[$fieldname]) || $_POST[$fieldname] == "" ))
            {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return false;
            }

        }

    if( $idgroup > 0 )
        {
        if( '' == bab_rp('password1') || '' == bab_rp('password2') || mb_strlen(bab_rp('nickname')) == 0)
            {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return false;
            }

        if( !isset($_POST['sn']) || $_POST['sn'] == "" || !isset($_POST['givenname']) || $_POST['givenname'] == "")
            {
            $babBody->msgerror = bab_translate( "You must complete firstname and lastname fields !!");
            return false;
            }

        $minPasswordLengh = 6;
        if(isset($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
            $minPasswordLengh = $GLOBALS['babMinPasswordLength'];
            if($minPasswordLengh < 1){
                $minPasswordLengh = 1;
            }
        }
        if ( mb_strlen(bab_rp('password1')) < $minPasswordLengh )
            {
            $babBody->msgerror = sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
            return false;
            }

        if( bab_rp('password1') != bab_rp('password2'))
            {
            $babBody->msgerror = bab_translate("Passwords not match !!");
            return false;
            }




        if ('Y' == bab_rp('notifyuser'))
        {
            $email_users = new bab_processImportEmailUsers($id);
        }

    }

    $encoding = bab_rp('encoding', 'ISO-8859-15');

    $fd = fopen($pfile, "r");
    if( $fd )
        {
        $arr = fgetcsv($fd, 4096, $separ);
        while ($arr = bab_getStringAccordingToDataBase(fgetcsv($fd, 4096, $separ), $encoding))
            {
            if( $idgroup > 0 )
                {
            if(!isset($arr[$_POST['nickname']]) || empty($arr[$_POST['nickname']])
            || !isset($arr[$_POST['givenname']]) || empty($arr[$_POST['givenname']])
            || !isset($arr[$_POST['sn']]) || empty($arr[$_POST['sn']])
            )
            {
            continue;
            }
                }
            else
                {
                    if(!isset($arr[$_POST['givenname']]) || empty($arr[$_POST['givenname']])
                    || !isset($arr[$_POST['sn']]) || empty($arr[$_POST['sn']])
                    )
                    {
                    continue;
                    }
                }

            switch(bab_rp('duphand'))
                {
                case 1: // Replace duplicates with items imported
                case 2: // Do not import duplicates
                    if( $idgroup > 0 )
                        {
                        $query = "select id from ".BAB_USERS_TBL." where
                            nickname='".$babDB->db_escape_string($arr[$_POST['nickname']])."'
                            OR (firstname LIKE '".$babDB->db_escape_like($arr[$_POST['givenname']])."'
                                AND lastname LIKE '".$babDB->db_escape_like($arr[$_POST['sn']])."')";

                        $res2 = $babDB->db_query($query);
                        if( $babDB->db_num_rows($res2) > 0 )
                            {
                            if( 2 == bab_rp('duphand') )
                            {
                            break;
                            }

                            $rrr = $babDB->db_fetch_array($res2);
                            $req = '';

                            for( $k =0; $k < count($arrnamef); $k++ )
                                {
                                if( isset($_POST[$arrnamef[$k]]) && $_POST[$arrnamef[$k]] != "")
                                    {
                                    $req .= $arrnamef[$k]."='".$babDB->db_escape_string($arr[$_POST[$arrnamef[$k]]])."',";
                                    }
                                }

                            $bupdate = false;
                            if( !empty($req))
                                {
                                $req = mb_substr($req, 0, mb_strlen($req) -1);
                                $req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
                                $req .= " where id_directory='0' and id_user='".$babDB->db_escape_string($rrr['id'])."'";
                                $babDB->db_query($req);
                                $bupdate = true;
                                }

                            if( count($arridfx) > 0 )
                                {
                                list($idu) = $babDB->db_fetch_array($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($rrr['id'])."'"));
                                for( $k=0; $k < count($arridfx); $k++ )
                                    {
                                    if( isset($arr[$_POST["babdirf".$arridfx[$k]]]) )
                                        {
                                        $bupdate = true;
                                        $rs = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($arridfx[$k])."' and  id_entry='".$babDB->db_escape_string($idu)."'");
                                        if( $rs && $babDB->db_num_rows($rs) > 0 )
                                            {
                                            $babDB->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".$babDB->db_escape_string($arr[$_POST["babdirf".$arridfx[$k]]])."' where id_fieldx='".$babDB->db_escape_string($arridfx[$k])."' and id_entry='".$babDB->db_escape_string($idu)."'");
                                            }
                                        else
                                            {
                                            $babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".$babDB->db_escape_string($arr[$_POST["babdirf".$arridfx[$k]]])."', '".$babDB->db_escape_string($arridfx[$k])."', '".$babDB->db_escape_string($idu)."')");
                                            }
                                        }
                                    }
                                }


                            $password3 = bab_rp('password3');

                            if( $password3 !== '')
                                {
                                    $pwd=false;
                                    if (mb_strlen($arr[$password3]) >= 6)
                                    {
                                        $pwd = mb_strtolower($arr[$password3]);
                                    }
                                }
                            else
                                {
                                $pwd = mb_strtolower(bab_rp('password1'));
                                }
                            $replace = array( " " => "", "-" => "");
                            $hashname = md5(mb_strtolower(strtr($arr[$_POST['givenname']].$arr[$_POST['mn']].$arr[$_POST['sn']], $replace)));
                            $hash=md5($arr[$_POST['nickname']].bab_getHashVar());

                            $query = "update ".BAB_USERS_TBL." set
                                nickname='".$babDB->db_escape_string($arr[$_POST['nickname']])."',
                                firstname='".$babDB->db_escape_string($arr[$_POST['givenname']])."',
                                lastname='".$babDB->db_escape_string($arr[$_POST['sn']])."',
                                email='".$babDB->db_escape_string($arr[$_POST['email']])."',
                                hashname='".$babDB->db_escape_string($hashname)."',
                                confirm_hash='".$babDB->db_escape_string($hash)."' ";

                            if (false !== $pwd)
                            {
                                $query .= ", password='".$babDB->db_escape_string(md5($pwd))."' ";
                            }

                            $query .= " where id='".$babDB->db_escape_string($rrr['id'])."'";


                            $babDB->db_query($query);
                            if( $bupdate )
                                {
                                $babDB->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$babDB->db_escape_string(bab_getUserId())."' where id_directory='0' and id_user='".$babDB->db_escape_string($rrr['id'])."'");
                                }

                            if( $idgroup > 1 )
                                {
                                bab_addUserToGroup($rrr['id'], $idgroup);
                                }


                            if (isset($email_users))
                            {
                                $emailpwd = ($pwd && 'Y' === bab_rp('sendpwd')) ? $pwd : null;
                                $email_users->addUser($rrr['id'], $pwd);
                            }

                            break;
                            }
                        }
                    else
                        {
                        $res2 = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where givenname='".$babDB->db_escape_string($arr[$_POST['givenname']])."' and sn='".$babDB->db_escape_string($arr[$_POST['sn']])."' and id_directory='".$babDB->db_escape_string($id)."'");
                        if( $res2 && $babDB->db_num_rows($res2 ) > 0 )
                            {
                            if( 2 == bab_rp('duphand') )
                                break;
                            else
                                {
                                $arr2 = $babDB->db_fetch_array($res2);
                                }


                            $req = '';
                            for( $k =0; $k < count($arrnamef); $k++ )
                                {
                                if( isset($_POST[$arrnamef[$k]]) && $_POST[$arrnamef[$k]] != "")
                                    {
                                    $req .= $arrnamef[$k]."='".$babDB->db_escape_string($arr[$_POST[$arrnamef[$k]]])."',";
                                    }
                                }

                            $bupdate = false;
                            if( !empty($req))
                                {
                                $req = mb_substr($req, 0, mb_strlen($req) -1);
                                $req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
                                $req .= " where id='".$babDB->db_escape_string($arr2['id'])."'";
                                $babDB->db_query($req);
                                $bupdate = true;
                                }

                            if( count($arridfx) > 0 )
                                {
                                $bupdate = true;
                                for( $k=0; $k < count($arridfx); $k++ )
                                    {
                                    if( isset($arr[$_POST["babdirf".$arridfx[$k]]]) )
                                        {
                                        $bupdate = true;
                                        $rs = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($arridfx[$k])."' and  id_entry='".$babDB->db_escape_string($arr2['id'])."'");
                                        if( $rs && $babDB->db_num_rows($rs) > 0 )
                                            {
                                            $babDB->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".addslashes($arr[$_POST["babdirf".$arridfx[$k]]])."' where id_fieldx='".$babDB->db_escape_string($arridfx[$k])."' and id_entry='".$babDB->db_escape_string($arr2['id'])."'");
                                            }
                                        else
                                            {
                                            $babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".$babDB->db_escape_string($arr[$_POST["babdirf".$arridfx[$k]]])."', '".$babDB->db_escape_string($arridfx[$k])."', '".$babDB->db_escape_string($arr2['id'])."')");
                                            }
                                        }
                                    }
                                }
                            if( $bupdate )
                                {
                                $babDB->db_query("update ".BAB_DBDIR_ENTRIES_TBL." set date_modification=now(), id_modifiedby='".$babDB->db_escape_string(bab_getUserId())."' where id='".$babDB->db_escape_string($arr2['id'])."'");
                                }
                            break;
                            }
                        }

                /* no break; */

                case 0: // Allow duplicates to be created or create a new entry
                    $req = "";
                    $arrv = array();
                    for( $k =0; $k < count($arrnamef); $k++ )
                        {
                        if( isset($_POST[$arrnamef[$k]]) && $_POST[$arrnamef[$k]] != "")
                            {
                            $req .= $arrnamef[$k].",";
                            $val = isset($arr[$_POST[$arrnamef[$k]]]) ? $arr[$_POST[$arrnamef[$k]]] : '';
                            array_push( $arrv, $val);
                            }
                        }

                    if( !empty($req))
                        {
                        $req = "insert into ".BAB_DBDIR_ENTRIES_TBL." (".$req."id_directory,date_modification,id_modifiedby) values (";
                        for( $i = 0; $i < count($arrv); $i++)
                            $req .= "'". $babDB->db_escape_string($arrv[$i])."',";
                        $req .= "'".($idgroup !=0 ? 0: $babDB->db_escape_string($id))."',";
                        $req .= "now(), '".$babDB->db_escape_string(bab_getUserId())."')";
                        $babDB->db_query($req);
                        $idu = $babDB->db_insert_id();
                        if( $idgroup > 0 )
                            {
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
                            if( $idgroup > 1 )
                                {
                                bab_addUserToGroup($iduser, $idgroup);
                                }

                            if (isset($email_users))
                            {
                                $emailpwd = ('Y' === bab_rp('sendpwd')) ? $pwd : null;
                                $email_users->addUser($iduser, $emailpwd);
                            }
                        }

                        if( count($arridfx) > 0 )
                            {
                            for( $k=0; $k < count($arridfx); $k++ )
                                {
                                $val = isset($arr[$_POST["babdirf".$arridfx[$k]]]) ? addslashes($arr[$_POST["babdirf".$arridfx[$k]]]) : '';
                                $babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (id_fieldx, id_entry, field_value) values('".$babDB->db_escape_string($arridfx[$k])."','".$babDB->db_escape_string($idu)."','".$babDB->db_escape_string($val)."')");
                                }
                            }

                        }
                    break;

                }
            }
        fclose($fd);
        unlink($pfile);
        }

        if (isset($email_users))
        {
            return $email_users->displayProgress();
        }

        header('location:'.$GLOBALS['babUrlScript'].'?tg=directory&idx=sdbovml&directoryid='.$id);
        exit;
    }

