<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */

require_once dirname(__FILE__).'/iterator/iterator.php';


/**
 * A collection of registry values
 */
class bab_RegistryIterator extends BAB_MySqlResultIterator
{

    /**
     * Process a registry entry
     *
     * (non-PHPdoc)
     * @see utilit/iterator/BAB_MySqlResultIterator#getObject($aDatas)
     *
     * @return mixed
     */
    public function getObject($arr)
    {
        switch($arr['value_type']) {

            case 'boolean':
                $arr['value'] = $arr['value'] ? true : false;
                break;

            case 'object':
            case 'array':
                $arr['value'] = unserialize($arr['value']);
                break;

            default:
                settype($arr['value'], $arr['value_type']);

        }

        return array(
            'key' => basename($arr['dirkey']),
            'value' => $arr['value'],
            'create_id_user' => (int) $arr['create_id_user'],
            'update_id_user' => (int) $arr['update_id_user'],
            'createdate' => (int) $arr['createdate'], // timestamp
            'lastupdate' => (int) $arr['lastupdate']  // timestamp
        );
    }

}

/**
 * @see bab_getRegistryInstance in addon api
 */
class bab_Registry
{
    static $registry = null;

    private $dir = '/';
    private $r = null;

    /**
     * This constructor should not be used directly.
     * Use function bab_getRegistryInstance instead.
     *
     * @see bab_getRegistryInstance
     * @return bab_Registry
     */
    public function __construct()
    {

    }


    /**
     * Returns the full path terminated with a '/' of directory $path
     * whether $path is itself an absolute or relative path.
     * getFullPath does not checks that the path actually exists in
     * the registry.
     *
     * @since 6.5.91
     * @param string	$path		An absolute or relative path.
     * @return string				The corresponding absolute path terminated with a '/'.
     */
    public function getFullPath($path)
    {
        if ('/' !== mb_substr($path, 0, 1)) {
            $path = $this->dir . $path;
        }
        if ('/' !== mb_substr($path, -1)) {
            $path .= '/';
        }
        return $path;
    }


    /**
     * Sets the current directory of the registry
     * Most other registry methods work relatively to this directory.
     *
     * @param string	$path		An absolute or relative path.
     */
    public function changeDirectory($path)
    {
        $this->dir = $this->getFullPath($path);
    }


    /**
     * Inserts or updates a value with a key parameter
     * The key will be inserted into the current directory
     * Possible return values are:
     * 0 : the function has done nothing
     * 1 : the value has been updated
     * 2 : the value has been inserted
     *
     * @param string $key
     * @param mixed $value
     * @see bab_registry::changeDirectory()
     * @return 0|1|2
     */
    public function setKeyValue($key, $value)
    {
        $babDB = bab_getDB();

        if (false !== mb_strpos($key, '/')) {
            trigger_error('"/" are forbidden in the key parameter of setKeyValue');
            return 0;
        }

        $userId = (int)bab_getUserId();

        $dirkey = $this->dir.$key;

        $value_type = gettype($value);

        switch($value_type) {

            case 'boolean':
                $value = $value ? 1 : 0;
                break;

            case 'array':
            case 'object':
                $value = serialize($value);
                break;
        }

        $res = $babDB->db_query("SELECT COUNT(*) FROM bab_registry WHERE dirkey=".$babDB->quote($dirkey));

        list($n) = $babDB->db_fetch_array($res);

        if ($n > 0) {

            $res = $babDB->db_query("

			UPDATE bab_registry
				SET
					value			= ".$babDB->quote($value).",
					value_type		= ".$babDB->quote($value_type).",
					update_id_user	= ".$babDB->quote($userId).",
					lastupdate		= NOW()
				WHERE
					dirkey			= ".$babDB->quote($dirkey)."
			");

            if (0 < $babDB->db_affected_rows($res)) {
                return 1;
            }

        } else {

            $babDB->db_query("

			INSERT INTO bab_registry
				(
					dirkey,
					value,
					value_type,
					create_id_user,
					update_id_user,
					createdate,
					lastupdate
				)
			VALUES
				(
					".$babDB->quote($dirkey).",
					".$babDB->quote($value).",
					".$babDB->quote($value_type).",
					".$babDB->quote($userId).",
					".$babDB->quote($userId).",
					NOW(),
					NOW()
				)
			");

            return 2;

        }

        return 0;
    }


    /**
     * Remove the key/value pair from the registry
     * @param string $key
     * @return boolean
     * @see bab_registry::changeDirectory()
     */
    public function removeKey($key)
    {
        $babDB = bab_getDB();

        $dirkey = $this->dir.$key;
        $res = $babDB->db_query("DELETE FROM bab_registry WHERE dirkey = ".$babDB->quote($dirkey));

        return 0 < $babDB->db_affected_rows($res);
    }


    /**
     * Get current path
     * @return string
     */
    public function getDirectory()
    {
        return $this->dir;
    }


    /**
     * Get a value
     * If the second parameter is not NULL and the key is not created,
     * the key will be created with the second parameter as a value
     * @param string $key
     * @param mixed $default_create
     * @return mixed|null
     */
    public function getValue($key, $default_create = NULL)
    {
        $arr = $this->getValueEx($key);
        if (NULL !== $arr) {
            return $arr['value'];
        }

        if (NULL !== $default_create) {
            $this->setKeyValue($key, $default_create);
            return $default_create;
        }

        return NULL;
    }



    /**
     * Get a value with additionnal parameters
     *
     * @since 7.7.94 this method accept an array of keys for the key parameter
     *
     * @param string | array $key
     * @return array | bab_RegistryIterator | null
     */
    public function getValueEx($key)
    {
        $babDB = bab_getDB();

        if (is_array($key))
        {
            $dirkey = array();
            foreach($key as $name)
            {
                $dirkey[] = $this->dir.$name;
            }
        } else {

            $dirkey = $this->dir.$key;
        }


        $res = $babDB->db_query("
			SELECT
				dirkey,
				value,
				value_type,
				create_id_user,
				update_id_user,
				UNIX_TIMESTAMP(createdate) createdate,
				UNIX_TIMESTAMP(lastupdate) lastupdate
			FROM bab_registry
			WHERE
				dirkey IN(".$babDB->quote($dirkey).")
		");



        $I = new bab_RegistryIterator();
        $I->setMySqlResult($res);

        if (!is_array($key))
        {
            if (0 === $I->count())
            {
                return null;
            }

            foreach($I as $arr)
            {
                return $arr;
            }
        }

        return $I;
    }

    /**
     * Delete the current directory
     * @return int affected rows
     */
    public function deleteDirectory()
    {
        $babDB = bab_getDB();

        $l = mb_strlen($this->dir);

        $res = $babDB->db_query("
			DELETE
			FROM bab_registry
			WHERE LEFT(dirkey,'".$l."') = " . $babDB->quote($this->dir)
            );

        return $babDB->db_affected_rows($res);
    }


    /**
     * Checks whether the path (absolute or not) is an existing directory.
     *
     * @since 6.5.91
     * @param string $path
     * @return bool
     */
    public function isDirectory($path)
    {
        $babDB = bab_getDB();

        $path = $this->getFullPath($path);

        $sql = '
			SELECT dirkey FROM bab_registry
			WHERE LEFT(dirkey, ' . $babDB->quote(mb_strlen($path)) . ') = ' . $babDB->quote($path);

        $res = $babDB->db_query($sql);
        return ($babDB->db_num_rows($res) > 0);
    }


    /**
     * Moves the directory $source to $dest
     *
     * @since 6.5.91
     * @param string	$source		The absolute or relative path of the source directory.
     * @param string	$dest		The absolute or relative path of the destination directory.
     * @return bool		TRUE if the directory was moved, FALSE otherwise.
     */
    public function moveDirectory($source, $dest)
    {
        $babDB = bab_getDB();

        // If destination directory already exists we return with error.
        if ($this->isDirectory($dest)) {
            return false;
        }

        $source = $this->getFullPath($source);
        $dest = $this->getFullPath($dest);

        $sourceLength = mb_strlen($source);

        $sql = '
			UPDATE bab_registry
			SET dirkey = CONCAT(' . $babDB->quote($dest) . ', SUBSTRING(dirkey, ' . $babDB->quote($sourceLength + 1) . '))
			WHERE LEFT(dirkey, ' . $babDB->quote($sourceLength) . ') = ' . $babDB->quote($source);

        $res = $babDB->db_query($sql);
        return ($babDB->db_affected_rows($res) > 0);
    }


    /**
     * get next subfolder
     * @return string|false
     */
    public function fetchChildDir()
    {
        $babDB = bab_getDB();

        if($this->r === null){
            $this->r = array();
        }
        if (!isset($this->r[$this->dir])) {
            $l = mb_strlen($this->dir);
            $sql = "SELECT DISTINCT
            LEFT(RIGHT(dirkey,LENGTH(dirkey)-'$l'), LOCATE('/',RIGHT(dirkey,LENGTH(dirkey)-'$l')) ) dirkey
            FROM bab_registry
            WHERE dirkey REGEXP ".$babDB->quote('^'.preg_quote($this->dir).'[^/]+/.+$');
            $this->r[$this->dir] = $babDB->db_query($sql);
        }

        if ($arr = $babDB->db_fetch_assoc($this->r[$this->dir])) {
            return $arr['dirkey'];
        }

        if (0 < $babDB->db_num_rows($this->r[$this->dir])) {
            $babDB->db_data_seek($this->r[$this->dir], 0);
        }
        return false;
    }


    /**
     * get next child key from current directory
     * @return string|false
     */
    public function fetchChildKey()
    {
        $babDB = bab_getDB();

        static $r = array();
        if (!isset($r[$this->dir])) {
            $l = mb_strlen($this->dir);
            $r[$this->dir] = $babDB->db_query("

                SELECT
                RIGHT(dirkey,LENGTH(dirkey)-'$l') dirkey
                FROM bab_registry
                WHERE dirkey REGEXP ".$babDB->quote('^'.preg_quote($this->dir).'[^/]+$')."
				");
        }

        if ($arr = $babDB->db_fetch_assoc($r[$this->dir])) {
            return $arr['dirkey'];
        }

        if (0 < $babDB->db_num_rows($r[$this->dir])) {
            $babDB->db_data_seek($r[$this->dir], 0);
        }
        return false;
    }




    /**
     *
     * @param string $path
     *
     * @since 8.5.96
     */
    public static function get($path, $defaultValue = null)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        if (defined('!' . $path)) {
            return constant('!' . $path);
        }

        if (!isset(self::$registry)) {
            self::$registry = bab_getRegistry();
        }

        $elements = explode('/', $path);
        $key = array_pop($elements);
        $registryPath = implode('/', $elements);
        self::$registry->changeDirectory($registryPath);
        $value = self::$registry->getValue($key);

        if (isset($value)) {
            return $value;
        }

        if (defined($path)) {
            return constant($path);
        }

        return $defaultValue;
    }


    /**
     *
     * @since 8.6.97
     *
     * @param string $path
     * @param mixed $value
     */
    public static function set($path, $value)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        if (!isset(self::$registry)) {
            self::$registry = bab_getRegistry();
        }

        $elements = explode('/', $path);
        $key = array_pop($elements);
        $registryPath = implode('/', $elements);
        self::$registry->changeDirectory($registryPath);
        self::$registry->setKeyValue($key, $value);
    }


    /**
     *
     * @since 8.6.97
     *
     * @param string $path
     */
    public static function delete($path)
    {
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        if (!isset(self::$registry)) {
            self::$registry = bab_getRegistry();
        }

        $elements = explode('/', $path);
        $key = array_pop($elements);
        $registryPath = implode('/', $elements);
        self::$registry->changeDirectory($registryPath);
        self::$registry->removeKey($key);
    }
}
