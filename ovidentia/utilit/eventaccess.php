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
 * @copyright Copyright (c) 2018 by CANTICO ({@link http://www.cantico.fr})
 */

require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';


/**
 * The bab_eventCheckAccessValid is used to override result of the bab_isAccessValid function.
 *
 * @package events
 * @since 8.6.97
 */
class bab_eventCheckAccessValid extends bab_event
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $objectId;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var bool|null
     */
    protected $access = null;

    /**
     *
     * @param string $table
     * @param string $objectId
     * @param int $userId
     */
    public function __construct($table, $objectId, $userId)
    {
        $this->table = $table;
        $this->objectId = $objectId;
        $this->userId = $userId;
    }


    /**
     * @return bool|null
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * If access is set to null, the bab_isAccessValid function will proceed as usual.
     * If access is set to true or false, bab_isAccessValid() will return this value without proceeding to other checks.
     *
     * @param bool|null $access
     * @return self
     */
    public function setAccess($access)
    {
        $this->access = $access;
        return $this;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return self
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     * @return self
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
}



/**
 * The bab_eventGetAccessibleObjects is used to override result of the bab_getAccessibleObjects function.
 *
 * @package events
 * @since 8.6.97
 */
class bab_eventGetAccessibleObjects extends bab_event
{
    /**
     * @var string
     */
    protected $table;

    /**
     * @var int[]
     */
    protected $objects;

    /**
     * @var int
     */
    protected $userId;


    /**
     *
     * @param string $table
     * @param int $userId
     * @param string $objects
     */
    public function __construct($table, $userId, $objects)
    {
        $this->table = $table;
        $this->objects = $objects;
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return self
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param int[] $objects
     * @return self
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return self
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }
}
