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
require_once dirname(__FILE__).'/addonlocation.class.php';


/**
 * Manage addon informations for one addon
 * @since 6.6.93
 */
class bab_addonInfos {

    /**
     * @var int
     */
    private $id_addon;

    /**
     * @var string
     */
    private $addonname;

    /**
     * @var bab_inifile
     */
    private $ini = null;

    /**
     *
     * @var array
     */
    private $tags;
    
    /**
     * @var bab_AddonLocation
     */
    protected $location;


    /**
     * Set addon Name
     * This function verifiy if the addon is accessible
     * define $this->id_addon and $this->addonname
     * @see bab_getAddonInfosInstance() this method is used for the creation of the instance with acces_rights=false
     *
     * @param	string	$addonname
     * @param	boolean	$access_rights	: access rights verification on addon
     * @return boolean
     */
    public function setAddonName($addonname, $access_rights = true) {

        $id_addon = bab_addonsInfos::getAddonIdByName($addonname, $access_rights);

        if (false === $id_addon) {
            return false;
        }

        if ($access_rights) {
            if (!bab_isAddonAccessValid($id_addon)) {
                return false;
            }
        }

        $this->id_addon = $id_addon;
        $this->addonname = $addonname;
        	
        return true;
    }
    
    
    /**
     * Get addon location paths
     * @return bab_AddonLocation
     */
    protected function getLocation() {
        if (!isset($this->location)) {
            
            if (!isset($this->addonname)) {
                throw new Exception('The addonname must be set');
            }
            
            $standard = new bab_AddonStandardLocation($this->addonname);

            if (file_exists($standard->getIniFilePath())) {
                $this->location = $standard;
            } else {
            
                $this->location = new bab_AddonInCoreLocation($this->addonname);
            }
        }
        
        return $this->location;
    }



    /**
     * Get the addon name
     * a replacement for $babAddonFolder
     * @return string
     */
    public function getName() {
        return $this->addonname;
    }

    /**
     * get the addon ID
     * @return int
     */
    public function getId() {
        return (int) $this->id_addon;
    }


    /**
     * a replacement for $babAddonTarget
     * @return string
     */
    public function getTarget() {
        return "addon/".$this->id_addon;
    }

    /**
     * a replacement for $babAddonUrl
     * @return string
     */
    public function getUrl() {
        global $babUrlScript;
        return $babUrlScript.'?tg=addon%2F'.$this->id_addon.'%2F';
    }

    /**
     * addon/addon-name/
     * a replacement for $babAddonHtmlPath
     * 
     * @deprecated Do not use relative path in addons
     *             Addons are subject to move out of the core folder in futures version
     *             
     * 
     * @return string
     */
    public function getRelativePath() {
        return 'addons/'.$this->addonname.'/';
    }

    /**
     * a replacement for $babAddonPhpPath
     * @return string
     */
    public function getPhpPath() {
        return $this->getLocation()->getPhpPath();
    }

    /**
     * Get the addon upload path
     * a replacement for $babAddonUpload
     * @return string
     */
    public function getUploadPath() {
        return $this->getLocation()->getUploadPath();
    }

    /**
     * Get path to template directory
     * @return string
     */
    public function getTemplatePath() {
        return $this->getLocation()->getTemplatePath();
    }


    /**
     * Get path to images directory
     * @return string
     */
    public function getImagesPath() {
        return $this->getLocation()->getImagesPath();
    }


    /**
     * Get path to ovml directory
     * @return string
     */
    public function getOvmlPath() {
        return $this->getLocation()->getOvmlPath();
    }


    /**
     * Get path to css stylesheets directory
     * @return string
     */
    public function getStylePath() {
        return $this->getLocation()->getStylePath();
    }

    /**
     * Get path to translation files directory
     * @return string
     */
    public function getLangPath() {
        return $this->getLocation()->getLangPath();
    }
    
    /**
     * Get path to the version ini file
     * relative to the ovidentia root folder (config.php)
     *
     * @return string
     */
    public function getIniFilePath()
    {
        return $this->getLocation()->getIniFilePath();
    }
    
    
    /**
     * Get path to the version ini file in the package, repository or zip archive
     * relative to the addon root folder or archive root folder
     *
     * @return string
     */
    public function getPackageIniFilePath()
    {
        return $this->getLocation()->getPackageIniFilePath();
    }
    
    
    /**
     * Get a template full path from a file name
     * @param string $filename
     * @return string
     */
    public function getTemplate($filename)
    {
        return $this->getTemplatePath().$filename;
    }
    
    /**
     * Convert template to html
     * 
     * @param object $class           object class instance with getnext methods on public properties
     * @param string $filename        File in addon template path
     * @param string $section         optional section name in template
     * 
     * @return string
     */
    public function printTemplate($class, $filename, $section = '')
    {
        $tpl = new bab_Template();
        return $tpl->printTemplate($class, $this->getTemplate($filename), $section);
    }
    
    
    /**
     * Add event listener
     * Register an addon function on an event listener
     * Once the listener is added, the function $function_name will be fired if bab_fireEvent is called with an event
     * inherited or instancied from the class $event_class_name
     *
     * The function return false if the event listener is already created
     * 
     * @param	string	$eventClassName
     * @param	string	$functionName			function name without (), if the function_name string contain a ->, the text before -> will be evaluated to get an object and the text after will be the method (not evaluated)
     * @param	string	$requireFile			file path relative to addon php path, the file where $function_name is declared, this can be an empty string if function exists in global scope
     * @param	int		[$priority]				for mutiple calls on one event, the calls will be ordered by priority descending
     *
     * @return boolean
     */
    public function addEventListener($eventClassName, $functionName, $requireFile, $priority = 0)
    {
        require_once dirname(__FILE__).'/eventincl.php';
        
        return bab_addEventListener(
            $eventClassName, 
            $functionName, 
            $this->getPhpPath().$requireFile, 
            $this->getName(), 
            $priority
        );
    }
    
    /**
     * Remove event listener
     * @see		bab_addEventListener()
     * @param	string	$eventClassName
     * @param	string	$functionName
     * @param	string	$requireFile          file path relative to addon php path
     */
    public function removeEventListener()
    {
        require_once dirname(__FILE__).'/eventincl.php';
        
        return bab_removeEventListener(
            $eventClassName, 
            $functionName, 
            $this->getPhpPath().$requireFile
        );
    }
    


    /**
     * get INI object, general section only
     * @return bab_inifile
     */
    public function getIni() {
        if (null === $this->ini) {
            include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
            $this->ini = new bab_inifile();
            $inifile = $this->getIniFilePath();
            	
            if (!is_readable($inifile)) {
                throw new Exception(sprintf('Error, the file %s must be readable', $inifile));
            }
            	
            if (!$this->ini->inifileGeneral($inifile)) {
                throw new Exception(sprintf('Error, the file %s is missing or has syntax errors', $inifile));
            }
        }

        return $this->ini;
    }


    /**
     * Check validity of addon INI file requirements
     * @return boolean
     */
    public function isValid() {
        include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
        $ini = new bab_inifile();
        $ini->inifile($this->getIniFilePath());

        return $ini->isValid();
    }


    /**
     * Get configuration url or null if no configuration page defined
     * @return string
     */
    public function getConfigurationUrl() {

        $ini = $this->getIni();

        if (!isset($ini->inifile['configuration_page'])) {
            return null;
        }

        return $this->getUrl().$ini->inifile['configuration_page'];
    }



    /**
     * addon has global access control
     * @return boolean
     */
    public function hasAccessControl() {
        $ini = $this->getIni();

        if (!$ini->fileExists()) {
            return false;
        }


        return !isset($ini->inifile['addon_access_control']) ||
        (isset($ini->inifile['addon_access_control']) && 1 === (int) $ini->inifile['addon_access_control']);
    }





    /**
     * Get the type of addon.
     * The addon type can be EXTENSION | LIBRARY | THEME
     *
     * @return string
     */
    public function getAddonType() {

        try {
            $ini = $this->getIni();
        } catch(Exception $e) {
            return 'EXTENSION';
        }

        if (!$ini->fileExists()) {
            return 'EXTENSION';
        }


        if (isset($ini->inifile['addon_type'])) {
            return $ini->inifile['addon_type'];
        }

        if (is_dir('skins/'.$this->getName())) {
            return 'THEME';
        }

        if ($this->hasAccessControl()) {
            return 'EXTENSION';
        } else {
            return 'LIBRARY';
        }
    }



    /**
     * addon is deletable by administrator
     * @return boolean
     */
    public function isDeletable() {
        try {
            $ini = $this->getIni();
        } catch (Exception $e) {
            return true;
        }
        return !$ini->fileExists() || (isset($ini->inifile['delete']) && 1 === (int) $ini->inifile['delete']);
    }

    /**
     * Test if addon is accessible
     * if access control, and addons access rights verification return false, addon is not accessible
     * if addons is disabled, the addons is not accessible
     * if addon is not installed, addon is not accessible
     * @return boolean
     */
    public function isAccessValid() {
        if (bab_isAddonAccessValid($this->id_addon)) {
            return true;
        }

        return false;
    }

    /**
     * is addon installed by administrator
     * @return boolean
     */
    public function isInstalled() {
        $arr = bab_addonsInfos::getDbRow($this->id_addon);
        return 'Y' === $arr['installed'];
    }


    /**
     * is addon disabled by administrator
     * @return boolean
     */
    public function isDisabled() {

        $arr = bab_addonsInfos::getDbRow($this->id_addon);
        return 'N' === $arr['enabled'];
    }

    /**
     * Disable addon
     * @return bab_addonInfos
     */
    public function disable() {
        global $babDB;
        $babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='N' WHERE id=".$babDB->quote($this->id_addon));
        bab_addonsInfos::clear();

        $event = new bab_eventAddonDisabled($this->addonname);
        bab_fireEvent($event);

        return $this;
    }


    /**
     * Enable addon
     * @return bab_addonInfos
     */
    public function enable() {
        global $babDB;
        $babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set enabled='Y' WHERE id=".$babDB->quote($this->id_addon));
        bab_addonsInfos::clear();

        $event = new bab_eventAddonEnabled($this->addonname);
        bab_fireEvent($event);

        return $this;
    }


    /**
     * Get version from ini file
     * @return string
     */
    public function getIniVersion() {

        $ini = $this->getIni();
        return $ini->getVersion();
    }


    /**
     * Get description from ini file
     * @return string
     */
    public function getDescription() {

        $ini = $this->getIni();

        if (false === $ini->fileExists()) {
            return bab_translate('Error, the files of addon are missing, please delete the addon or restore the orginal addon folders');
        }

        return $ini->getDescription();
    }







    /**
     * get version from database
     * @return string
     */
    public function getDbVersion() {
        $arr = bab_addonsInfos::getDbRow($this->id_addon);
        return $arr['version'];
    }

    /**
     * Test if the addon need an upgrade of the database
     * @return bool
     */
    public function isUpgradable() {

        try {
            $ini = $this->getIni();
        } catch (Exception $e) {
            // trigger_error($e->getMessage());
            return false;
        }

        $vini 	= $this->getIniVersion();
        $vdb 	= $this->getDbVersion();

        if ( empty($vdb) || 0 !== version_compare($vdb,$vini) || !$this->isInstalled()) {
            return true;
        }

        return false;
    }


    /**
     * Verify addon installation status
     * after addon files has been modified, this method update the table with new installation status
     * @return boolean
     */
    public function updateInstallStatus() {

        if (!$this->isUpgradable()) {
            return false;
        }

        global $babDB;



        if (!is_file($this->getPhpPath().'init.php')) {
            $babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set installed='N' WHERE id=".$babDB->quote($this->id_addon));
            bab_addonsInfos::clear();
            return false;
        }



        if (!bab_setAddonGlobals($this->id_addon)) {
            return false;
        }


        require_once( $this->getPhpPath().'init.php' );
        $func_name = $this->getName().'_upgrade';

        if (!function_exists($func_name)) {
            	
            $this->setDbVersion($this->getIniVersion());
            	
        } else {
            if ($this->isInstalled()) {
                $babDB->db_query("UPDATE ".BAB_ADDONS_TBL." set installed='N' WHERE id=".$babDB->quote($this->id_addon));
                bab_addonsInfos::clear();
            }
        }

        return true;
    }




    /**
     *
     * @return	boolean
     */
    private function setDbVersion($version) {

        global $babDB;

        $res = $babDB->db_query("
			UPDATE ".BAB_ADDONS_TBL."
			SET
				version=".$babDB->quote($version).",
				installed='Y'
			WHERE
				id=".$babDB->quote($this->id_addon)."
		");

        if (0 !== $babDB->db_affected_rows($res)) {
            bab_addonsInfos::clear();
            return true;
        }

        return false;
    }


    /**
     * Get the list of tables associated to addon
     * from db_prefix in addon ini file
     * @return array
     */
    public function getTablesNames() {

        global $babDB;
        $ini = $this->getIni();

        $tbllist = array();

        if (
            !empty($ini->inifile['db_prefix'])
            && mb_strlen($ini->inifile['db_prefix']) >= 3
            && mb_substr($ini->inifile['db_prefix'],0,3) != 'bab') {
                	
                $res = $babDB->db_query("SHOW TABLES LIKE '".$babDB->db_escape_like($ini->inifile['db_prefix'])."%'");
                while(list($tbl) = $babDB->db_fetch_array($res)) {
                    $tbllist[] = $tbl;
                }
            }

            return $tbllist;
    }



    /**
     * Return the image path
     * a 200x150px png, jpg or gif image, representation of the addon
     * @return string|null
     */
    public function getImagePath() {
        $ini = $this->getIni();

        if (!isset($ini->inifile['image'])) {
            return null;
        }

        $imgpath = $this->getImagesPath().$ini->inifile['image'];

        if (!is_file($imgpath)) {
            return null;
        }


        return $imgpath;
    }



    /**
     * Return the icon path
     * a 48x48px png, jpg or gif image, representation of the addon
     * @return string|null
     */
    public function getIconPath() {
        $ini = $this->getIni();

        switch ($this->getAddonType()) {
            case 'THEME':
                $default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-theme.png';
                break;
            case 'LIBRARY':
                $default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-library.png';
                break;
            case 'EXTENSION':
            default:
                $default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-extension.png';
                break;
        }
        //		$default = $GLOBALS['babSkinPath'].'images/48x48/apps/addon-default.png';

        if (!isset($ini->inifile['icon'])) {
            return $default;
        }

        $imgpath = $this->getImagesPath().$ini->inifile['icon'];

        if (!is_file($imgpath)) {
            return $default;
        }


        return $imgpath;
    }


    /**
     * Call upgrade function of addon
     * @return boolean
     */
    public function upgrade() {

        include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';

        if (!is_file($this->getPhpPath().'init.php')) {
            trigger_error('ini file not found for addon in '.$this->getPhpPath().'init.php');
            return false;
        }


        if (!bab_setAddonGlobals($this->id_addon)) {
            return false;
        }

        $func_name = $this->getName().'_upgrade';
        require_once( $this->getPhpPath().'init.php');

        global $babDB;

        $vini 	= $this->getIniVersion();
        $vdb 	= $this->getDbVersion();

        if ((function_exists($func_name) && $func_name($vdb, $vini)) || !function_exists($func_name))
        {


            if ($this->setDbVersion($vini)) {

                if (empty($vdb)) {
                    $from_version = '0.0';
                } else {
                    $from_version = $vdb;
                }
                bab_setUpgradeLogMsg($this->getName(), sprintf('The addon has been updated from %s to %s', $from_version, $vini));

                $event = new bab_eventAddonUpgraded($this->addonname);
                $event->previousVersion = $from_version;
                $event->newVersion = $vini;
                bab_fireEvent($event);


                // clear sitemap for addons without access rights management
                bab_siteMap::clearAll();
                return true;
            }
            	
            if ($vdb === $vini) {

                $event = new bab_eventAddonUpgraded($this->addonname);
                $event->previousVersion = $vini;
                $event->newVersion = $vini;
                bab_fireEvent($event);
                return true;
            }
        }
        	
        trigger_error(sprintf('failed processing the addon upgrade %s()', $func_name));
        return false;
    }







    /**
     * remove obsolete lines in tables
     * @return bool
     */
    private function deleteInTables() {
        global $babDB;
        include_once $GLOBALS['babInstallPath']."admin/acl.php";

        $babDB->db_query("delete from ".BAB_ADDONS_TBL." where id='".$babDB->db_escape_string($this->getId())."'");
        aclDelete(BAB_ADDONS_GROUPS_TBL, $this->getId());
        $babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$babDB->db_escape_string($this->getId())."' and type='4'");
        $babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$babDB->db_escape_string($this->getId())."' and type='4'");

        return true;
    }











    /**
     * Remove addon
     * @param	string	&$msgerror
     * @return boolean
     */
    public function delete(&$msgerror) {

        global $babDB, $babBody;
        include_once dirname(__FILE__).'/delincl.php';

        if (false === $this->isDeletable()) {
            $msgerror = bab_translate('This addon is not deletable');
            return false;
        }

        if ($this->getDependences()) {
            $msgerror = bab_translate('This addon has dependences from other addons');
            return false;
        }


        $event = new bab_eventAddonBeforeDeleted($this->addonname);
        bab_fireEvent($event);

        $ini = $this->getIni();

        if (!$ini->fileExists()) {
            $deleteInTables = $this->deleteInTables();
            	
            if ($deleteInTables) {
                $event = new bab_eventAddonDeleted($this->addonname);
                bab_fireEvent($event);
            }
            	
            return $deleteInTables;
        }


        if (!callSingleAddonFunction($this->getId(), $this->getName(), 'onDeleteAddon')) {
            $msgerror = $babBody->msgerror;
            return false;
        }



        	
        // if addon return true, the addon is uninstalled in the table.
        $babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' where id=".$babDB->quote($this->getId()));


        $tbllist = $this->getTablesNames();
        $loc_in = $this->getLocation()->getDeletePaths();

        foreach($loc_in as $path) {
            if (is_dir($path)) {
                if (false === bab_deldir($path, $msgerror)) {
                    return false;
                }
            }
        }
        	
        if (count($tbllist) > 0) {
            foreach($tbllist as $tbl) {
                $babDB->db_query("DROP TABLE ".$babDB->backTick($tbl));
            }
        }

        $deleteInTables = $this->deleteInTables();

        if ($deleteInTables) {
            $event = new bab_eventAddonDeleted($this->addonname);
            bab_fireEvent($event);
        }

        return $deleteInTables;
    }



    /**
     * list of addons used by the current addon
     * @return	array	in the key, the name of the addon, in the value a boolean for dependency satisfaction status
     */
    public function getDependencies() {
        $ini = new bab_inifile();
        $ini->inifile($this->getIniFilePath());
        $addons = $ini->getAddonsRequirements();
        $return = array();
        foreach($addons as $arr) {
            $return[$arr['name']] = $arr['result'];
        }

        return $return;
    }



    /**
     * list of addons that use the current addon
     * @return	array	in the key, the name of the addon, in the value a boolean for dependency satisfaction status
     */
    public function getDependences() {
        $return = array();
        foreach(bab_addonsInfos::getDbRows() as $arr) {
            $addon = bab_getAddonInfosInstance($arr['title']);
            foreach($addon->getDependencies() as $addonname => $satisfaction) {
                if ($addonname === $this->getName() && $addon->isInstalled()) {
                    $return[$addon->getName()] = $satisfaction;
                }
            }
        }

        return $return;
    }

    /**
     * get all dependencies for addon
     * @param	bab_OrphanRootNode	$root
     * @param	string				$parent
     * @return bool
     */
    private function getRecursiveDependencies(bab_OrphanRootNode $root, $nodeId = 'root', $parent = null)
    {
        include_once $GLOBALS['babInstallPath'].'utilit/treebase.php';

        $node = $root->createNode($this, $nodeId);
        	
        if (null === $node) {
            return false;
        }

        $root->appendChild($node, $parent);

        $dependencies = $this->getDependencies();
        foreach($dependencies as $addonname => $status)
        {
            if ($addon = bab_getAddonInfosInstance($addonname))
            {
                $childNodeId = $nodeId.'-'.$addon->getId();
                $addon->getRecursiveDependencies($root, $childNodeId, $nodeId);
            }
            else
            {
                throw new Exception('missing addon '.$addonname);
                return false;
            }
        }

        return true;
    }


    private function browseRecursiveDependencies(&$stack, bab_Node $node)
    {
        $addon = $node->getData();

        if ($node->hasChildNodes())
        {
            $child = $node->firstChild();
            do {
                $this->browseRecursiveDependencies($stack, $child);
            } while($child = $child->nextSibling());
        }
        	
        if ($addon) {
            $stack[$addon->getName()] = $addon->getName();
        }
    }


    /**
     * Get all dependencies for addons sorted in install order
     * the value and key in array is the addon name
     *
     * @return array
     */
    public function getSortedDependencies()
    {
        $stack = array();
        $root = new bab_OrphanRootNode;
        if ($this->getRecursiveDependencies($root))
        {
            $this->browseRecursiveDependencies($stack, $root);
            return $stack;
        }

        return array();
    }


    /**
     * Get all dependencies for addons sorted in install order
     * if the main addon specify a "pakage" configuration string, use it instead of the getSortedDependencies method
     * the value and key in array is the addon name
     *
     * @return array
     */
    public function getPackageDependencies()
    {
        $ini = $this->getIni();
        if (isset($ini->inifile['package_creation'])) {
            $return = array();
            $list = explode(',',$ini->inifile['package_creation']);
            foreach($list as $addonname)
            {
                $addonname = trim($addonname);
                if (!empty($addonname))
                {
                    $return[$addonname] = $addonname;
                }
            }
            	
            if (!empty($return))
            {
                $return[$this->getName()] = $this->getName();
                return $return;
            }
        }

        return $this->getSortedDependencies();
    }



    /**
     * Test if the addon is compatible with the specified charset
     * @param	string	$isoCharset
     * @boolean
     */
    public function isCharsetCompatible($isoCharset) {
        $ini = $this->getIni();
        $compatibles = array('latin1');
        if (isset($ini->inifile['mysql_character_set_database'])) {
            $compatibles = explode(',',$ini->inifile['mysql_character_set_database']);
        }



        foreach($compatibles as $addoncharset) {
            if ($isoCharset === bab_charset::getIsoCharsetFromDataBaseCharset(trim($addoncharset))) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get tags list associated to addons
     * @return array
     */
    public function getTags()
    {

        if (!isset($this->tags))
        {
            $this->tags = array();
            	
            $ini = $this->getIni();
            if (isset($ini->inifile['tags'])) {

                return array();
            }
            	
            $tags = preg_split('/\s*,\s*/', $ini->inifile['tags']);
            	
            foreach($tags as $name)
            {
                $name = mb_strtolower($name);
                $this->tags[$name] = $name;
            }
        }

        return $this->tags;
    }

    /**
     * Test if a tag exists in addon
     * if $tag is an array, return true if all tags are found in the addon
     *
     * @param string | array $tag
     * @return bool
     */
    public function hasTag($tag)
    {
        if (!is_array($tag))
        {
            $tag = array($tag);
        }

        foreach($tag as $name)
        {
            $name = mb_strtolower($name);
            $arr = $this->getTags();
            if (!isset($arr[$tag]))
            {
                return false;
            }
        }

        return true;
    }
}
