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
require_once $GLOBALS['babInstallPath'].'utilit/controller.class.php';

class bab_CoreController extends bab_Controller
{
	protected function getControllerTg()
	{
		return 'main';
	}

	/**
	 * Get object name to use in URL from the controller classname
	 * @param string $classname
	 * @return string
	 */
	protected function getObjectName($classname)
	{
		$prefix = strlen('bab_Ctrl');
		return strtolower(substr($classname, $prefix));
	}
}