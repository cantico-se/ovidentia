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
	include "base.php";

	DEFINE('BAB_NONE', -1);
	DEFINE('BAB_ALL_ATTRIBUTS', -2);
	DEFINE('BAB_STAR_ATTRIBUT', -3);

	class BAB_TableWrapper
	{
		var $m_db;
		var $m_TableName;

		function BAB_TableWrapper($tableName)
		{
			$this->m_db = & $GLOBALS['babDB'];
			$this->m_TableName = $tableName;
		}

		function setTableName($tableName)
		{
			$this->m_TableName = $tableName;
		}

		function getTableName()
		{
			return $this->m_TableName;
		}

		function & getDbObject()
		{
			return $this->m_db;
		}
		
		//Zero based
		function buildSelect(& $attribut, $offset = BAB_ALL_ATTRIBUTS, $length = BAB_ALL_ATTRIBUTS)
		{
			if(BAB_STAR_ATTRIBUT == $offset)
			{
				return '*';
			}
			else if(BAB_ALL_ATTRIBUTS == $offset)
			{
				return implode(",", array_keys($attribut));
			}
			else
			{
				$arrayKeys = array_keys($attribut);
				$size = count($arrayKeys);
				$start = ( $offset > 0 && $offset < $size ) ? $offset : 0;
				if(BAB_ALL_ATTRIBUTS == $length)
				{
					$end = $size - $start;
				}
				else
				{
					$remain = $size - $start;
					$end = ( $length > 0 && $length < $remain ) ? $length : $remain;
				}
				$arrayKeys = array_slice($arrayKeys, $start, $end);
				return implode(",", $arrayKeys);
			}
		}

		//zero based
		function buildWhereClause(& $attribut, $offset = BAB_ALL_ATTRIBUTS, $length = BAB_ALL_ATTRIBUTS)
		{
			if(BAB_NONE == $offset)
			{
				return '';
			}

			$size = 0;
			$start = 0;
			$end   = 0;
			
			if(BAB_ALL_ATTRIBUTS == $offset)
			{
				$end = count($attribut);
			}
			else
			{
				$size = count($attribut);

				$start = ( $offset > 0 && $offset < $size ) ? $offset : 0;

				if(BAB_ALL_ATTRIBUTS == $length)
				{
					$end = $size - $start;
				}
				else
				{
					$remain = $size - $start;
					$end = ( $length > 0 && $length < $remain ) ? $length : $remain;
				}
			}

			$reducedArray = array_slice($attribut, $start, $end);
			$size = count($reducedArray);

			$idx = 0;
			$whereClause = $key = $value = '';

			while($idx < $size) 
			{
				list($key, $value) = each($reducedArray);
				$whereClause .= ' AND ' . $key . '=\'' . $this->m_db->db_escape_string($value) . '\'';
				++$idx;
			}

			reset($attribut);
			return ' WHERE ' . mb_substr($whereClause, mb_strlen(' AND '));
		}

		function load(& $attribut, $selectOffset = BAB_ALL_ATTRIBUTS, $selectLength = BAB_ALL_ATTRIBUTS,
			$whereClauseOffset = BAB_ALL_ATTRIBUTS, $whereClauseLength = BAB_ALL_ATTRIBUTS)
		{
			$attributNameList	= $this->buildSelect($attribut, $selectOffset, $selectLength);
			$whereClause		= $this->buildWhereClause($attribut, $whereClauseOffset, $whereClauseLength);

			$request = 'SELECT ' . $attributNameList . ' FROM ' . $this->m_TableName . $whereClause; 

			//bab_debug($request);
			
			$result = $this->m_db->db_query($request);
			return $this->m_db->db_fetch_assoc($result); 
		}

		function save(& $attributsList, $skipFirst = true)
		{
			reset($attributsList);
			
			if(true === $skipFirst)
			{
				//skip the first element
				next($attributsList);
			}
				
			$item 	= '';
			$insert = '';
			$values = '';

			while($item = each($attributsList))
			{
				$insert .= ', `' . $item['key'] . '`';
				$values .= ', ' . '\'' . $this->m_db->db_escape_string($item['value']) . '\'';
			}

			$requete = 'INSERT INTO ' . $this->m_TableName . '(  '. mb_substr($insert, mb_strlen(', ')) . ') ' . 
				'VALUES ( ' . mb_substr($values, mb_strlen(', ')) . ')';

			//bab_debug($requete);

			return $this->m_db->db_query($requete);
		}

		function update(& $attributsList)
		{
			//skip the first element
			reset($attributsList);
			next($attributsList);
				
			$requete = '';
			$item = '';
			while($item = each($attributsList))
			{
				$requete .= ', `' . $item['key'] . '`=\'' . $this->m_db->db_escape_string($item['value']) . '\'';
			}
			
			$requete = 'UPDATE ' . $this->m_TableName . ' SET ' . mb_substr($requete, mb_strlen(', ')) . ' WHERE id = \'' 
				. $this->m_db->db_escape_string($attributsList['id']) . '\'';

			//bab_debug($requete);

			return $this->m_db->db_query($requete);
		}

		function delete(& $attribut, $whereClauseOffset = BAB_ALL_ATTRIBUTS, $whereClauseLength = BAB_ALL_ATTRIBUTS)
		{
			$whereClause = $this->buildWhereClause($attribut, $whereClauseOffset, $whereClauseLength);

			$request = 'DELETE FROM ' . $this->m_TableName . $whereClause; 
			
			//bab_debug($request);
			
			return $this->m_db->db_query($request);
		}
	}
?>