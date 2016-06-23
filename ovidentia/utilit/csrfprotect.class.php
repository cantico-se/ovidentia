<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */

require_once dirname(__FILE__).'/session.class.php';


/**
 * CSRF protection for all requests other than GET
 * isRequestValid() need to be called on each request
 * 
 */
class bab_CsrfProtect
{
    
    /**
     * 
     * @var string
     */
    const FIELDNAME = 'babCsrfProtect';
    
    /**
     * Get token to put in forms hidden fields
     * @return string
     */
    public function getToken()
    {
        $session = bab_getInstance('bab_Session');
        if (!isset($session->bab_CsrfProtectToken)) {
            $session->bab_CsrfProtectToken = uniqid('bab', true);
        }
        
        return $session->bab_CsrfProtectToken;
    }
    
    
    /**
     * Validate CSRF token on all requests other than GET
     * @return boolean
     */
    public function isRequestValid()
    {
        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            return true;
        }
        
        if (defined('BAB_CSRF_PROTECT') && false === BAB_CSRF_PROTECT) {
            return true;
        }
        
        $token = bab_pp(self::FIELDNAME, null);
        
        if (!isset($token)) {
            return false;
        }
        
        $session = bab_getInstance('bab_Session');
        if (!isset($session->bab_CsrfProtectToken)) {
            return false;
        }
        
        if ($session->bab_CsrfProtectToken !== $token) {
            return false;
        }
        
        // this unset force a new token for each post
        // prevent double submit
        // unset($session->bab_CsrfProtectToken);
        
        return true;
    }
}