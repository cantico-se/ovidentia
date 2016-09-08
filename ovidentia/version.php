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
include_once "base.php";

include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';


function getVersion()
{
	$ini = new bab_inifile();
	$ini->inifile($GLOBALS['babInstallPath'].'version.inc');

	$str = sprintf(bab_translate('Sources Version %s'), $ini->getVersion())."\n";

	$dbVer = bab_getDbVersion();
	if (NULL !== $dbVer) {
		$str .= sprintf(bab_translate('Database Version %s'), $dbVer) ."\n";
	} else {
		$str .= bab_translate('No Database Version (installation is not complete)')."\n";
	}
	return $str;
}



function echoLang($path)
{
	bab_setTimeLimit(3600);
	
	$arr = array();
	$handle = opendir($path); 
	while (false != ($filename = readdir($handle)))
		{ 
		if ($filename != "." && $filename != "..")
			{
			if (($filename == "utilit" || $filename == "admin") && is_dir($path.$filename))
				{
					$arr = array_merge($arr, echoLang($path.$filename."/"));
				}
			else
				{
				if( mb_substr($filename,-4) == ".php")
					{
					$file = fopen($path.$filename, "r");
					if( $file )
						{
						$txt = fread($file, filesize($path.$filename));
						fclose($file);
						$reg = "/bab_translate[[:space:]]*\([[:space:]]*\"([^\"]*)/s";
						$m1 = null;
						preg_match_all($reg, $txt, $m1);
						for ($i = 0; $i < count($m1[1]); $i++ )
							{
							if( !empty($m1[1][$i]) && !in_array($m1[1][$i], $arr) )
								{
								$arr[] = $m1[1][$i];
								}
							}
						}
					}
				}
			} 
		}
	closedir($handle);
	return $arr;
}


/**
 * Install one addon and exit process
 * @param string $name
 */
function bab_installAddon($name)
{
    bab_addonsInfos::insertMissingAddonsInTable();
    bab_addonsInfos::clear();
    
    $addon = bab_getAddonInfosInstance($name);
    if (false === $addon)
    {
        trigger_error('this addon does not exists');
        die(bab_translate("Failed"));
    }
    
    if (!$addon->isUpgradable() && !bab_isUserAdministrator())
    {
        trigger_error('Addon allready up to date');
        die(bab_translate("Failed"));
    }
    
    if (!$addon->isValid())
    {
        trigger_error('Invalid addon prerequists');
        foreach ($addon->getIni()->getRequirementErrors() as $req) {
            echo $req['description'].' '.$req['current']."<br />";
        }
        die(bab_translate("Failed"));
    }
    
    if (!$addon->upgrade())
    {
        trigger_error('Addon upgrade failed');
        die(bab_translate("Failed"));
    }
    
    die(bab_translate("Ok"));
}



/**
 * Button to launch tg=version&idx=upgrade in POST query
 * @return string
 */
function bab_getInstallButton()
{
    $csrfToken = bab_getInstance('bab_CsrfProtect')->getToken();
    
    return '<form method="post" action="'.bab_getSelf().'">
    <input type="hidden" name="babCsrfProtect" value="'.bab_toHtml($csrfToken).'" />
    <input type="hidden" name="tg" value="version" />
    <input type="hidden" name="idx" value="upgrade" />
    <input type="hidden" name="iframe" value="1" />
    <input type="submit" value="'.bab_translate("Update").'" />
    </form>';
}

/**
 * Button to launch tg=version&idx=upgradeaddons in POST query
 * @return string
 */
function bab_getInstallAddonsButton()
{
    $csrfToken = bab_getInstance('bab_CsrfProtect')->getToken();
    
    return '<form method="post" action="'.bab_getSelf().'">
    <input type="hidden" name="babCsrfProtect" value="'.bab_toHtml($csrfToken).'" />
    <input type="hidden" name="tg" value="version" />
    <input type="hidden" name="idx" value="upgradeaddons" />
    <input type="hidden" name="iframe" value="1" />
    <input type="submit" value="'.bab_translate("Install or update the addons provided in this package (optional)").'" />
    </form>
    <p><a href="?">'.bab_translate('Continue to home page').'</a></p>';
}

/**
 * Menu to display possibles action and do the POST queries
 * @return string
 */
function bab_getActionsMenu()
{
    
    $html = bab_getInstallButton();
    $folders = bab_getCoreFolders();
    bab_sort::natcasesort($folders);
    
    $options = '';
    foreach($folders as $name) {
        $options .= '<option value="'.bab_toHtml($name).'">'.bab_toHtml($name).'</option>';
    }
    
    $csrfToken = bab_getInstance('bab_CsrfProtect')->getToken();
    
    $html .= '
    <br />
    <h3>'.bab_translate('Copy addons from one folder to another').'</h3>
    <form method="post" action="'.bab_getSelf().'">
    <input type="hidden" name="babCsrfProtect" value="'.bab_toHtml($csrfToken).'" />
    <input type="hidden" name="tg" value="version" />
    <input type="hidden" name="idx" value="addons" />
    <select name="from">'.$options.'</select>
    <select name="to">'.$options.'</select>
    <input type="submit" value="'.bab_translate("Copy").'" />
    </form>
    ';
    
    
    $html .= '
    <br />
    <h3>'.bab_translate('Launch an addon upgrade program').'</h3>
    <form method="post" action="'.bab_getSelf().'">
    <input type="hidden" name="babCsrfProtect" value="'.bab_toHtml($csrfToken).'" />
    <input type="hidden" name="tg" value="version" />
    <input type="hidden" name="idx" value="addon" />
    <label>'.bab_translate('Addon name').': <input type="text" name="name" value="" /></label>
    <input type="submit" value="'.bab_translate("Update").'" />
    </form>
    
    ';
    
    return $html;
}



/* main */
$idx = bab_rp('idx','version');


$str = '';
$html = '';

switch($idx)
	{
	case "upgrade":
		if (empty($_POST)) {
		    $str = sprintf(bab_translate('Do you really want to update Ovidentia from %s to %s?'), 
		            bab_getDbVersion(), 
		            bab_getIniVersion());
		    $html = bab_getInstallButton();
		} else {
		    bab_requireSaveMethod();
		    if (true === bab_upgrade($GLOBALS['babInstallPath'], $str)) {
		        $html = bab_getInstallAddonsButton();
		    }
		}
		break;
		
	case 'upgradeaddons':
	    // upgrade addons found in the install folder
	    bab_requireSaveMethod() && bab_upgradeAddons($str);
	    break;

	case "addons":
		if( !bab_isUserAdministrator()) {
			die(bab_translate("You must be logged as administrator"));
		}
		bab_requireSaveMethod() && bab_cpaddons(bab_rp('from'), bab_rp('to',$GLOBALS['babInstallPath']), $str);
		break;
		
	case 'addon':
		// allow addon upgrade for annonymous users
		bab_requireSaveMethod() && bab_installAddon(bab_rp('name'));
		break;



	case "version":
	default:
		if( !bab_isUserAdministrator())
			exit;
		$str = getVersion();
		$html = bab_getActionsMenu();
		break;
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Ovidentia</TITLE>
<META NAME="Generator" CONTENT="Ovidentia">
<META NAME="Author" CONTENT="Cantico">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>
<BODY BGCOLOR="#FFFFFF">
	
		
	<?php

	if (bab_rp('iframe')) {
		
		echo bab_toHtml($str, BAB_HTML_ALL);
		echo $html;
		
		?>
		<br id="BAB_ADDON_INSTALL_END" />
		<?php 
	} else {
		?>
		<center>
		<h1>Ovidentia</h1>
		
		<?php
		echo $GLOBALS['babSiteName'] . "<br>";
		echo bab_toHtml($str, BAB_HTML_ALL);
		echo $html;
	    ?>
		<br>
		<p><a href="?"><?php echo bab_translate("Home");  ?></a></p>
		<p class="copyright">&copy; 2001, <a href="http://www.cantico.fr/">CANTICO</a> All rights reserved.</p>
	</center>
	
	<?php } ?>
		
</BODY>
</HTML>

