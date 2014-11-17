<?php
// -------------------------------------------------------------------------
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
// -------------------------------------------------------------------------
/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */

class babMenu
{
    var $curItem = '';
    var $items = array();
    
    function babMenu()
    {
    	$GLOBALS['babCurrentMenu'] = '';
    }
    
    function addItem($title, $txt, $url, $enabled=true)
    {
    	$this->items[$title]['text'] = $txt;
    	$this->items[$title]['url'] = $url;
    	$this->items[$title]['enabled'] = $enabled;
    }
    
    function addItemAttributes($title, $attr)
    {
    	$this->items[$title]['attributes'] = $attr;
    }
    
    function setCurrent($title, $enabled=false)
    {
    	foreach($this->items as $key => $val)
		{
		  if( !strcmp($key, $title))
			{
			$this->curItem = $key;
			$this->items[$key]['enabled'] = $enabled;
			if( !$enabled )
				$GLOBALS['babCurrentMenu'] = $this->items[$key]['text'];
			break;
			}
		}
    }
}

