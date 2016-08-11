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

class Func_ContextActions extends bab_functionality
{

	
	public function getDescription()
	{
		return bab_translate('Base class to provide a list of actions, used by the editlinks addons');
	}
	
	
	/**
	 * Get a CSS selector to match element in page
	 * @return string
	 */
	public function getClassSelector()
	{
		throw new Exception('Must be implemented by sub-class');
	}
	
	/**
	 * Get the list of actions
	 * @param array $classes all css classes found on the element
	 * @param bab_url $url Page url where the actions will be added
	 * @return Widget_Action[]
	 */
	public function getActions(Array $classes, bab_url $url)
	{
		return array();
	}
}