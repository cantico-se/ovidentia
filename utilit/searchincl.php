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


function bab_highlightWord( $w, $text)
{
	$arr = explode(" ",trim(urldecode($w)));
	foreach($arr as $mot)
		{
		$mot_he = htmlentities($mot);
		$text = preg_replace("/".$mot."/i", "<span class=\"Babhighlight\">".$mot."</span>", $text);
		if ($mot != $mot_he)
			$text = preg_replace("/".$mot_he."/i", "<span class=\"Babhighlight\">".$mot_he."</span>", $text);
		}
	return $text;
}


function bab_sql_finder_he($tbl,$str,$not="")
	{
	if ($not == "NOT") $op = "AND";
	else $op =  "OR";
	$tmp = htmlentities($str);
	if ($tmp != $str)
		return " ".$op." ".$tbl.$not." like '%".$tmp."%'";
	}

function bab_sql_finder($req2,$tablename,$option = "OR",$req1="")
{
	$like = '';

if( !bab_isMagicQuotesGpcOn())
	{
	$req2 = mysql_escape_string($req2);
	$req1 = mysql_escape_string($req1);
	}

if (trim($req1) != "") 
	$like = $tablename." like '%".$req1."%'".bab_sql_finder_he($tablename,$req1);

if (trim($req2) != "") 
	{
	$tb = explode(" ",trim($req2));
	switch ($option)
		{
		case "NOT":
			foreach($tb as $key => $mot)
				{
				if (trim($req1) == "" && $key==0)
					$like = $tablename." like '%".$mot."%'";
				else
					$like .= " AND ".$tablename." NOT like '%".$mot."%'".bab_sql_finder_he($tablename,$mot," NOT");
				}
		break;
		case "OR":
		case "AND":
		default:
			foreach($tb as $key => $mot)
				{
				$he = bab_sql_finder_he($tablename,$mot);
				if ( trim($req1) == "" && $key == 0 )
					$like = $tablename." like '%".$mot."%'".$he;
				else if ($he != "" && $option == "AND")
					$like .= " AND (".$tablename." like '%".$mot."%'".$he.")";
				else
					$like .= " ".$option." ".$tablename." like '%".$mot."%'".$he;
				}
		break;
		}
	}
	return $like;
}

?>