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


class bab_Password
{
    /**
     * Get encrypted password with used hash function
     * @return object
     */
    public static function hash($password)
    {
        $encPassword = new stdClass();
    
        if (!defined('BAB_PASSWORD_HASH_FUNCTION')) {
            // default fo new accounts from 8.4.92
            if (function_exists('password_hash')) { // PHP 5 >= 5.5.0
                define('BAB_PASSWORD_HASH_FUNCTION', 'password_hash');
            } else {
                define('BAB_PASSWORD_HASH_FUNCTION', 'md5');
            }
        }
    
        $encPassword->hashfunc = BAB_PASSWORD_HASH_FUNCTION;
    
        switch(BAB_PASSWORD_HASH_FUNCTION) {
            case 'md5': // case insensitive
                $encPassword->value = md5(mb_strtolower(trim($password)));
                break;
    
            case 'password_hash': // PHP 5 >= 5.5.0
                $encPassword->value = password_hash(trim($password), PASSWORD_DEFAULT);
                break;
    
    
        }
    
        return $encPassword;
    }
    
    
    /**
     * @param string $password                  User input
     * @param string $hash                      Hash from database
     * @param string $password_hash_function    Has function from database
     * @return boolean
     */
    public static function verify($password, $hash, $password_hash_function)
    {
        switch($password_hash_function) {
            default:
            case 'md5':
                return ($hash === md5(mb_strtolower(trim($password))));
    
            case 'password_hash':
                return password_verify(trim($password), $hash);
        }
    }
}