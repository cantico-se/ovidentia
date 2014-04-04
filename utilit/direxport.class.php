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
require_once dirname(__FILE__).'/dirincl.php';

/**
 * Export directory as text (csv, vcard)
 *
 */
abstract class bab_dbdir_export
{
	
	/**
	 * Directory informations
	 * @var array
	 */
	protected $directory;
	
	/**
	 * Include disabled users in export
	 * @var bool
	 */
	protected $includedisabled;
	
	/**
	 * 
	 * @var string
	 */
	protected $filename;
	
	/**
	 * Mime type
	 * var string
	 */
	protected $contenttype;
	
	/**
	 *
	 * @param	int		$id_directory				Directory to export
	 * @param	bool	$includedisabled			Include disabled users in export
	 */
	public function __construct($id_directory, $includedisabled)
	{
		$directories = bab_getUserDirectories(false);
		
		$this->directory = $directories[$id_directory];
		
		$this->includedisabled = $includedisabled;
		
	}
	
	/**
	 * Test if the directory to export contain users accounts
	 * @return bool
	 */
	protected function containUsers()
	{
		return ($this->directory['id_group'] > 0);
	}
	
	
	/**
	 * 
	 */
	public function output()
	{
		header("Content-Disposition: attachment; filename=\"".$this->filename."\""."\n");
		header("Content-Type: ".$this->contenttype."\n");
		header("Content-transfert-encoding: binary"."\n");
		$this->outputText();
		exit;
	}
	
	
	/**
	 * echo text to export
	 */
	abstract protected function outputText();
}



/**
 * Export CSV file
 * 
 *
 */
abstract class bab_dbdir_export_csv extends bab_dbdir_export
{
	/**
	 * 
	 * @var string
	 */
	protected $separator = ',';
	
	/**
	 * Ressource used to fetch all row to export
	 * @var ressource
	 */
	protected $res;
	
	public function __construct($id_directory, $includedisabled)
	{
		parent::__construct($id_directory, $includedisabled);
		
		$this->contenttype = 'text/csv';
		$this->filename = bab_removeDiacritics($this->directory['name']).'.csv';
	}
	
	
	public function setSeparator($s)
	{
		$this->separator = $s;
	}
	
	/**
	 * Get the columns to export
	 * @return Array
	 */
	abstract protected function getColumns();
	
	
	/**
	 * Encode a value for CSV file
	 * @param	string $v
	 * @return string
	 */
	protected function csvencode($v)
	{
		return '"'.str_replace('"', '""', $v).'"';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see bab_dbdir_export::outputText()
	 */
	protected function outputText()
	{
		global $babDB;
		
		$header = array();
		foreach($this->getColumns() as $label)
		{
			$header[] = $this->csvencode($label);
		}
		
		echo implode($this->separator, $header)."\n";
		
		while ($row = $babDB->db_fetch_assoc($this->res))
		{
			foreach($row as $name => $value)
			{
				$row[$name] = $this->csvencode($value);
			}
			echo implode($this->separator, $row)."\n";
		}
	}
}



/**
 * Export CSV file, ovidentia format 
 * separator and exported columns are configurables
 *
 */
class bab_dbdir_export_ovidentia_csv extends bab_dbdir_export_csv 
{
	/**
	 * 
	 * @var array
	 */
	protected $cols = array();
	
	/**
	 * 
	 * @param int $id_directory
	 * @param bool $includedisabled
	 * @param string $separ
	 * @param array $listfd
	 */
	public function __construct($id_directory, $includedisabled, $separ, Array $listfd)
	{
		parent::__construct($id_directory, $includedisabled);
		
		$this->setSeparator($separ);
		
		$this->init($listfd);
	}
	
	/**
	 * create ressource and columns
	 */
	private function init($listfd)
	{
		global $babDB;
		
		$arrnamef = array();
		$leftjoin = array();
		$select = array();
		
		$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where 
				id_directory='".($this->directory['id_group'] != 0? 0: $babDB->db_escape_string($id))."' 
				AND id_field IN(".$babDB->quote($listfd).") 
				order by list_ordering asc");
		
		
		if( $this->directory['id_group'] > 0 )
		{
			$this->cols[] = bab_translate("Login ID");
			$select[] = 'ua.nickname';
		}
		
		while($arr = $babDB->db_fetch_array($res))
		{
			if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
				$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
				$fieldn = translateDirectoryField($rr['description']);
				$arrnamef[] = $rr['name'];
				$select[] = 'e.'.$rr['name'];
			}
			else
			{
				$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
				$fieldn = translateDirectoryField($rr['name']);
				$arrnamef[] = "babdirf".$arr['id'];
			
				$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$babDB->db_escape_string($arr['id'])."' AND e.id=lj".$babDB->db_escape_string($arr['id']).".id_entry";
				$select[] = "lj".$arr['id'].'.field_value '."babdirf".$babDB->db_escape_string($arr['id'])."";
			}
			
			$this->cols[] = $fieldn;
		}
		
		
		if( $this->directory['id_group'] > 1 )
		{
			// un groupe
			
			$req = " ".BAB_USERS_GROUPS_TBL." u,
			".BAB_DBDIR_ENTRIES_TBL." e 
				LEFT JOIN bab_users ua ON ua.id=e.id_user 
				".implode(' ',$leftjoin)."
			WHERE u.id_group='".$idgroup."'
			AND u.id_object=e.id_user
			AND e.id_directory='0'";
		} 
		else if ($this->directory['id_group'] == 1 )
		{
			// tous les utilisateurs enregistres
			
			$req = " ".BAB_DBDIR_ENTRIES_TBL." e 
				LEFT JOIN bab_users ua ON ua.id=e.id_user 
				".implode(' ',$leftjoin)." 
			WHERE e.id_directory='0'";
		}
		else
		{
			// annuaire de base de donnes
			
			$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".$babDB->db_escape_string($id)."'";
		}
		
		
		if( $this->directory['id_group'] > 0 )
		{
			require_once dirname(__FILE__).'/userinfosincl.php';
			$req .= ' AND '.bab_userInfos::queryAllowedUsers('ua', false, $this->includedisabled);
		}
		
		
		$req = "select ".implode(',', $select)." from ".$req;
		$this->res = $babDB->db_query($req);
	}
	
	
	protected function getColumns()
	{
		return $this->cols;
	}
}




/**
 * Save export form values to database associated to user account
 * 
 * @param 	int		$id				Directory ID
 * @param	string	$output_format	
 * @param	array	$listfd			list of fields to export (only for ovidentia CSV)
 * @param	string	$separ			Separator (only for ovidentia CSV)
 * 
 * @return bool
 */
function bab_saveExportFormStatus($id, $output_format, $listfd, $separ)
{
	global $babDB;
	
	if (!bab_isUserLogged())
	{
		return false;
	}
	
	$id_user = bab_getUserId();
	
	
	$babDB->db_query("delete from ".BAB_DBDIR_FIELDSEXPORT_TBL." where id_directory='".$babDB->db_escape_string($id)."' and id_user='".$babDB->db_escape_string($id_user)."'");

	for($i=0; $i < count($listfd); $i++)
	{
		$babDB->db_query("insert into ".BAB_DBDIR_FIELDSEXPORT_TBL." (id_user, id_directory, id_field, ordering) 
				values ('".$babDB->db_escape_string($id_user)."','".$babDB->db_escape_string($id)."','".$babDB->db_escape_string($listfd[$i])."','".($i + 1)."')");
	}
	
	$babDB->db_query("delete from ".BAB_DBDIR_CONFIGEXPORT_TBL." where id_directory='".$babDB->db_escape_string($id)."' and id_user='".$babDB->db_escape_string($id_user)."'");
	$babDB->db_query("insert into ".BAB_DBDIR_CONFIGEXPORT_TBL." (id_user, id_directory, output_format, separatorchar) 
			values ('".$babDB->db_escape_string($id_user)."','".$babDB->db_escape_string($id)."', '".$babDB->db_escape_string($output_format)."', '".$babDB->db_escape_string(Ord($separ))."')");
	
	
	return true;
	
}



/**
 * Get posted separator for Ovidentia CSV
 * @return string
 */
function bab_getPostedSeparator($wsepar, $separ)
{

	switch($wsepar)
	{
		case "1":
			return ",";
			break;
		case "2":
			return "\t";
			break;
		default:
			if( empty($separ)) {
				return ",";
			}
			
			return $separ;
		break;
	}
}