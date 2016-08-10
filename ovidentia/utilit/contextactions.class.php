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
	/**
	 * 
	 * @var array
	 */
	protected $matches;
	
	public function getDescription()
	{
		return bab_translate('Base class for a contextual button, used by the editlinks addons');
	}
	
	
	/**
	 * Get a pattern or string to match a CSS class
	 * @return string
	 */
	public function getClassPattern()
	{
		throw new Exception('Must be implemented by sub-class');
	}
	
	/**
	 * This call is made by editlinks to set the matchs in the button object
	 * @param array $matches
	 */
	public function setPatternMatches(Array $matches)
	{
		$this->matches = $matches;
	}
	
	/**
	 * Get the list of actions to display for this pattern
	 * @return Widget_Action[]
	 */
	public function getActions()
	{
		return array();
	}
}