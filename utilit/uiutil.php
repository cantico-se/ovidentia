<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
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
class babFontTag
{
	var $color;
	var $face;
	var $size;

	function babFontTag( $color = "", $face= "", $size ="")
		{
		$this->color = $color;
		$this->size = $size;
		$this->face = $face;
		}

	function setDefault()
		{
		$this->color = "";
		$this->size = "";
		$this->face = "";
		}

	function output()
		{
		$result = "";

		if( $this->color != "")
			$result = " color=\"".$this->color."\"";

		if( $this->face != "")
			$result = $result . " face=\"".$this->face."\"";

		if( $this->size != "")
			$result = $result . " size=\"".$this->size."\"";

		return $result;
		}

	function textOut($data)
		{
		$result = "<font". $this->output() . ">";
		$result .= htmlspecialchars($data). "</font>";
		return $result;
		}

	function startTag()
		{
		return "<font". $this->output(1) . ">";
		}

	function endTag()
		{
		return "</font>";
		}

};
?>
