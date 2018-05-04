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
require_once dirname(__FILE__).'/eventincl.php';


class bab_eventCsrfTokenError extends bab_event
{
    /**
     * Default request status on error
     * status false will issue a 403
     * status true will allow request
     * @var boolean
     */
    public $status = false;
}


/**
 * No token in POST
 */
class bab_eventCsrfInvalidPost extends bab_eventCsrfTokenError
{
    /**
     * @var array
     */
    public $post;
}


/**
 * No token in session
 */
class bab_eventCsrfNoSessionToken extends bab_eventCsrfTokenError
{
    /**
     * @var string
     */
    public $postedToken;
}

/**
 * Token missmatch
 */
class bab_eventCsrfInvalidToken extends bab_eventCsrfTokenError
{
    /**
     * @var string
     */
    public $sessionToken;

    /**
     * @var string
     */
    public $postedToken;
}





/**
 * CSRF protection for all requests other than GET
 * isRequestValid() need to be called on each request
 * @since 8.4.91
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
    public static function getToken()
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
        if (php_sapi_name() === 'cli') {
            return true;
        }

        if ('GET' === $_SERVER['REQUEST_METHOD']) {
            return true;
        }

        if (!bab_isCsrfProtectionEnabled()) {
            return true;
        }

        $token = bab_pp(self::FIELDNAME, null);

        if (!isset($token)) {
            $event = new bab_eventCsrfInvalidPost();
            $event->post = $_POST;
            bab_fireEvent($event);
            return $event->status;
        }

        $session = bab_getInstance('bab_Session');
        if (!isset($session->bab_CsrfProtectToken)) {
            $event = new bab_eventCsrfNoSessionToken();
            $event->postedToken = $token;
            bab_fireEvent($event);
            return $event->status;
        }

        if ($session->bab_CsrfProtectToken !== $token) {
            $event = new bab_eventCsrfInvalidToken();
            $event->sessionToken = $session->bab_CsrfProtectToken;
            $event->postedToken = $token;
            bab_fireEvent($event);
            return $event->status;
        }

        // this unset force a new token for each post
        // prevent double submit
        // unset($session->bab_CsrfProtectToken);

        return true;
    }
}