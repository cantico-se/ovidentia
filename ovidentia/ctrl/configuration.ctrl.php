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
include_once 'base.php';
require_once dirname(__FILE__) . '/corecontroller.class.php';


/**
 *
 */
class bab_CtrlConfiguration extends bab_CoreController
{
    public function displayList()
    {
        if (!bab_isUserAdministrator()) {
            throw new bab_AccessException();
        }

        $W = bab_Widgets();
        $page = $W->BabPage();
        $page->setTitle('Configuration');

        $lockedConfigEntries = array();
        $userEntries = array();
        $defaultEntries = array();
        $configEntries = array();

        $entries = array();

        $constants = get_defined_constants(true);

        foreach ($constants['user'] as $constantKey => $constantValue) {
            if (substr($constantKey, 0, 2) == '!/') {
                $path = substr($constantKey, 1);
                $lockedConfigEntries[$path] = $path;
                $entries[$path] = $path;
            } elseif (substr($constantKey, 0, 1) == '/') {
                $path = $constantKey;
                $configEntries[$path] = $path;
                $entries[$path] = $path;
            }
        }

        $userId = bab_getUserId();

        $babDB = bab_getDB();

        $sql = "SELECT *
                FROM bab_registry
                WHERE dirkey LIKE '/%'
                ORDER BY dirkey";


        $configuration = new bab_Configuration();

        $box = $W->VBoxItems();
        $page->addItem($box);
        $registryEntries = $babDB->db_query($sql);
        while ($registryEntry = $babDB->db_fetch_assoc($registryEntries)) {
            $path = explode('/', trim($registryEntry['dirkey'], '/'));

            if ($path[0] === 'U') {
                if ($path[1] == $userId) {
                    array_shift($path);
                    array_shift($path);
                    $path = '/' . implode('/', $path);
                    $userEntries[$path] = $path;
                    $entries[$path] = $path;
                }
                continue;
            }
            $defaultEntries[$registryEntry['dirkey']] = $registryEntry['dirkey'];
            $entries[$registryEntry['dirkey']] = $registryEntry['dirkey'];
        }

        bab_Sort::ksort($entries);

        foreach ($entries as $entry) {
            $status = 'Application default';
            if (isset($lockedConfigEntries[$entry])) {
                $status = 'Config locked';
            } elseif (isset($userEntries[$entry])) {
                $status = 'User';
            } elseif (isset($defaultEntries[$entry])) {
                $status = 'Default';
            } elseif (isset($configEntries[$entry])) {
                $status = 'Config default';
            }
            $value = $configuration->get($entry);

            $box->addItem(
                $W->FlowItems(
                    $W->Label($entry)
                        ->addClass('widget-strong')
                        ->setSizePolicy('widget-30pc'),
                    $W->Label($status)
                        ->setSizePolicy('widget-10pc'),
                    $W->Label(gettype($value))
                        ->setSizePolicy('widget-10pc'),
                    $W->Label($value)
                        ->setSizePolicy('widget-40pc'),
                    $W->Link(
                        '',
                        $this->proxy()->deleteGlobal($entry)
                    )->addClass('icon', Func_Icons::ACTIONS_EDIT_DELETE)
                    ->setAjaxAction()
                    ->setSizePolicy(Func_Icons::ICON_LEFT_16)
                )->setSizePolicy('widget-list-element')
                ->setVerticalAlign('top')
            );
        }

        $page->setReloadAction($this->proxy()->displayList());

        return $page;
    }


    /**
     * Saves one key/value pair for the current user only.
     *
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function setValue($key = null, $value = null)
    {
        if (isset($key) && isset($value)) {
            $configuration = new bab_Configuration();
            $configuration->setForUser($key, $value);
        }

        return true;
    }


    /**
     * Saves multiple key/values at once for the current user only.
     *
     * @param array $values
     * @return boolean
     */
    public function setValues($path = '', $values = null)
    {
        if (isset($values) && is_array($values)) {
            foreach ($values as $key => $value) {
                $configuration = new bab_Configuration();
                $configuration->setForUser($path . $key, $value);
            }
        }

        return true;
    }


    /**
     *
     * @param string $path
     * @return boolean
     */
    public function delete($path = '')
    {
        if (isset($path) && $path !== '') {
            $configuration = new bab_Configuration();
            $configuration->deleteForUser($path);
        }

        return true;
    }



    /**
     * Saves one key/value pair for the current user only.
     *
     * @param string $key
     * @param string $value
     * @return boolean
     */
    public function setGlobalValue($key = null, $value = null)
    {
        if (!bab_isUserAdministrator()) {
            throw new bab_AccessException();
        }

        if (isset($key) && isset($value)) {
            $configuration = new bab_Configuration();
            $configuration->setDefault($key, $value);
        }

        return true;
    }


    /**
     * Saves multiple key/values at once, default for all users.
     *
     * @param array $values
     * @return boolean
     */
    public function setGlobalValues($path = '', $values = null)
    {
        if (!bab_isUserAdministrator()) {
            throw new bab_AccessException();
        }

        if (isset($values) && is_array($values)) {
            foreach ($values as $key => $value) {
                $configuration = new bab_Configuration();
                $configuration->setDefault($path . $key, $value);
            }
        }

        return true;
    }


    /**
     *
     * @param string $path
     * @return boolean
     */
    public function deleteGlobal($path = '')
    {
        if (!bab_isUserAdministrator()) {
            throw new bab_AccessException();
        }


        if (isset($path) && $path !== '') {
            $configuration = new bab_Configuration();
            $configuration->deleteDefault($path);
        }

        return true;
    }
}




class bab_Configuration
{

    /**
     *
     * @param string $userId
     * @return string
     */
    public function getUserIdPathPrefix($userId)
    {
        return '/U/' . $userId;
    }

    /**
     * This method can be used to store default key/values.
     * They can be retrieved later using  self::getDefault().
     *
     * @see self::getDefault()
     *
     * @param string $key			A key in the form of a path (eg. '/tableview_a/visiblecolumns/name').
     * @param mixed  $value			The value to store.
     */
    public function setDefault($key, $value)
    {
        if (is_array($value)) {
            $value = self::recursiveConvertToDatabaseEncoding($value);
        }
        bab_Registry::set($key, $value);
    }




    /**
     * This method can be used to retrieve the default stored configuration key/values.
     *
     * @see self::setDefault()
     *
     * @param string $key   		A key in the form of a path (eg. 'tableview_a/visiblecolumns/name').
     * @return mixed|null
     */
    public function getDefault($key)
    {
        return bab_Registry::get($key);
    }




    /**
     * Deletes all the default configuration key/values under the specified path.
     *
     * @param string $path   The path to delete (eg. 'tableview_a/visiblecolumns').
     */
    public static function deleteDefault($path)
    {
        return bab_Registry::delete($path);
    }


    /**
     * This method can be used to store key/values for the currently
     * connected user. They can be retrieved later using  self::getForUser().
     *
     * @see self::getForUser()
     *
     * @param string $key			A key in the form of a path (eg. 'tableview_a/visiblecolumns/name').
     * @param mixed  $value			The value to store.
     * @param bool   $sessionOnly	If true, the configuration is taken from the _SESSION array.
     */
    public function setForUser($key, $value, $sessionOnly = false)
    {
        if (!bab_isUserLogged() || $sessionOnly) {
            $_SESSION['bab_Configuration/' . $key] = $value;
            return;
        }

        if (is_array($value)) {
            $value = self::recursiveConvertToDatabaseEncoding($value);
        }
        $userId = bab_getUserId();
        $prefix = $this->getUserIdPathPrefix($userId);
        bab_Registry::set($prefix . '/' . $key, $value);

    }




    /**
     * This method can be used to retrieve stored configuration key/values for the currently
     * connected user.
     *
     * @see self::setForUser()
     *
     * @param string $key   		A key in the form of a path (eg. 'tableview_a/visiblecolumns/name').
     * @param bool	 $sessionOnly	If true, the configuration is taken from the _SESSION array.
     * @return mixed|null
     */
    public function getForUser($key, $sessionOnly = false)
    {
        if (!bab_isUserLogged() || $sessionOnly) {
            if (isset($_SESSION['bab_Configuration/' . $key])) {
                return $_SESSION['bab_Configuration/' . $key];
            }
            return null;
        }

        $userId = bab_getUserId();
        $prefix = $this->getUserIdPathPrefix($userId);
        return bab_Registry::get($prefix . '/' . $key);
    }



    /**
     * Deletes all the user configuration key/values under the specified path.
     *
     * @param string $path   The path to delete (eg. 'tableview_a/visiblecolumns').
     * @param bool	 $sessionOnly	If true, the configuration is taken from the _SESSION array.
     */
    public function deleteForUser($path, $sessionOnly = false)
    {
        if (!bab_isUserLogged() || $sessionOnly) {
            unset($_SESSION['bab_Configuration/' . $path]);
            return true;
        }

        $userId = bab_getUserId();
        $prefix = $this->getUserIdPathPrefix($userId);
        return bab_Registry::delete($prefix . '/' . $path);
    }



    /**
     *
     * @param string $key   		A key in the form of a path (eg. 'tableview_a/visiblecolumns/name').
     * @param bool	 $sessionOnly	If true, the configuration is taken from the _SESSION array.
     * @return mixed
     */
    public function get($key, $sessionOnly = false)
    {
        $userValue = bab_Registry::getLocked($key);
        if (!isset($userValue)) {
            $userValue = $this->getForUser($key, $sessionOnly);
            if (!isset($userValue)) {
                $userValue = $this->getDefault($key);
            }
        }
        return $userValue;
    }
}
