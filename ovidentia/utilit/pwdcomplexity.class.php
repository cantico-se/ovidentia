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



/**
 * Default password complity functionaliy
 */
class Func_PwdComplexity extends bab_functionality {

    public $error = null;


    /**
     * Register myself as a functionality.
     */
    public static function register()
    {
        require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
        $functionalities = new bab_functionalities();
        return $functionalities->registerClass('Func_PwdComplexity', __FILE__);
    }


    public function isValid($pwd)
    {
        die(bab_translate("Func_PortalAuthentication::logout must not be called directly"));
    }

    public function getErrorDescription()
    {
        return $this->error;
    }
}


class Func_PwdComplexity_DefaultPortal extends Func_PwdComplexity {


    public function getDescription()
    {
        return bab_translate('Default password complexity');
    }

    /**
     * Register myself as a functionality.
     */
    public static function register()
    {
        require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
        $functionalities = new bab_functionalities();
        return $functionalities->registerClass('Func_PwdComplexity_DefaultPortal', __FILE__);
    }

    public function registerPwdComplexity()
    {
        if (Func_PwdComplexity::register() === false) {
            return false;
        }
        return Func_PwdComplexity_DefaultPortal::register();
    }


    public function isValid($pwd)
    {
        $minPasswordLengh = 6;
        if(ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
            $minPasswordLengh = $GLOBALS['babMinPasswordLength'];
        }

        if (mb_strlen($pwd) < $minPasswordLengh) {
            $this->error = sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
            return false;
        }

        return true;
    }

}
