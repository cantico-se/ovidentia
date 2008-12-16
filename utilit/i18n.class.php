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
require_once 'base.php';


if(!class_exists('Collator'))
{
	class Collator
	{
		private $mixedLocale = null;
		
		const SORT_REGULAR	= SORT_REGULAR;
		const SORT_NUMERIC	= SORT_NUMERIC;
		const SORT_STRING	= SORT_STRING;
		
		public function __construct($locale)
		{
			$this->mixedLocale = $locale;
		}
		
		public static function create($locale)
		{
			return new Collator($locale);
		}
		
		public function sort(array &$aToSort)
		{
			return usort($aToSort, array('Collator', 'compare'));
		}
		
		public function asort(array &$aToSort)
		{
			return uasort($aToSort, array('Collator', 'compare'));
		}
		
		public static function compare($sStr1, $sStr2)
		{
			if('utf8' == bab_charset::getDatabase())
			{
				$sStr1 = utf8_decode($sStr1);
				$sStr2 = utf8_decode($sStr2);
			}
			return strnatcmp(bab_removeDiacritics($sStr1), bab_removeDiacritics($sStr2));
		}
	}
}



?>