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
require_once dirname(__FILE__).'/dateTime.php';

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
	 * Columns in query result
	 * @var array
	 */
	protected $cols = array();
	
	
	/**
	 * List of custom fields
	 * @var array
	 */
	protected $custom = array();
	
	
	/**
	 * Ressource used to fetch all row to export
	 * @var ressource
	 */
	protected $res;
	
	
	
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
	
	
	
	
	
	
	/**
	 * create ressource and columns
	 * @param	array	$listfd		optional column filter
	 */
	protected function init($listfd = null, $photo = false, $revision = false)
	{
		global $babDB;
	
		$arrnamef = array();
		$leftjoin = array();
		$select = array();
		
		$query = "select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where
				id_directory='".($this->directory['id_group'] != 0? 0: $babDB->db_escape_string($id))."' 
				";
		
		if (isset($listfd))
		{
			$query .= "AND id_field IN(".$babDB->quote($listfd).")";
		}
		
		$query .= " order by list_ordering asc";
	
		$res = $babDB->db_query($query);
	
	
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
				
				$this->custom["babdirf".$arr['id']] = $fieldn;
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
			WHERE u.id_group=".$babDB->quote($this->directory['id_group'])." 
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
		
		
		if ($photo)
		{
			$select[] = 'photo_data';
			$select[] = 'photo_type';
		}
		
		if ($revision)
		{
			$select[] = 'date_modification';
			$select[] = 'id_modifiedby';
		}
	
	
		$req = "select ".implode(',', $select)." from ".$req;
		$this->res = $babDB->db_query($req);
	}
	
	
	
	/**
	 * 
	 * @param string $prefix
	 * @param array $row
	 * @return string
	 */
	protected function getAddress($prefix, Array $row, $linefeed = "\n")
	{
		$street = $row[$prefix.'streetaddress'];
		$postalcode = $row[$prefix.'postalcode'];
		$city = $row[$prefix.'city'];
		$state = $row[$prefix.'state'];
		$country = $row[$prefix.'country'];
	
		$address = '';
		if (!empty($street))
		{
			$address .= $street.$linefeed;
		}
	
		if (!empty($postalcode))
		{
			$address .= $postalcode." ";
		}
	
		if (!empty($city))
		{
			$address .= $city.$linefeed;
		}
	
		if (!empty($state))
		{
			$address .= $state.$linefeed;
		}
	
		if (!empty($country))
		{
			$address .= $country.$linefeed;
		}
	
		return trim($address);
	}
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
	 * key is position, value is column label (first CSV row)
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
			
			$this->outputRow($row);
		}
	}
	
	
	abstract protected function outputRow(Array $row);
	
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
	
	
	
	
	protected function getColumns()
	{
		return $this->cols;
	}
	
	/**
	 * Output row 
	 */
	protected function outputRow(Array $row)
	{
		foreach($row as $name => $value)
		{
			$row[$name] = $this->csvencode($value);
		}
		echo implode($this->separator, $row)."\n";
	}
}








/**
 * Export CSV file, google format
 *
 */
class bab_dbdir_export_google_csv extends bab_dbdir_export_csv
{
	public function __construct($id_directory, $includedisabled)
	{
		parent::__construct($id_directory, $includedisabled);
	
		$this->init();
	}
	
	
	/**
	 * Output row
	 */
	protected function outputRow(Array $row)
	{
		$output = array_fill(0, 63, '');
		
		$output[0] = $this->csvencode($row['givenname'].' '.$row['sn']);
		$output[1] = $this->csvencode($row['givenname']);
		$output[3] = $this->csvencode($row['sn']);
		
		$output[27] = $this->csvencode('*');
		$output[28] = $this->csvencode($row['email']);
		
		$output[29] = $this->csvencode('Home');
		$output[30] = $this->csvencode($row['htel']);
		
		$output[31] = $this->csvencode('Mobile');
		$output[32] = $this->csvencode($row['mobile']);
		
		$output[33] = $this->csvencode('Work');
		$output[34] = $this->csvencode($row['btel']);
		
		$output[35] = $this->csvencode('Work Fax');
		$output[36] = $this->csvencode($row['bfax']);
		
		$output[37] =  $this->csvencode('Home');
		$output[38] =  $this->csvencode($this->getAddress('h', $row, "\r\n"));
		$output[39] =  $this->csvencode($row['hstreetaddress']);
		$output[40] =  $this->csvencode($row['hcity']);
		$output[42] =  $this->csvencode($row['hstate']);
		$output[43] =  $this->csvencode($row['hpostalcode']);
		$output[44] =  $this->csvencode($row['hcountry']);
		
		$output[46] =  $this->csvencode('Work');
		$output[47] =  $this->csvencode($this->getAddress('b', $row, "\r\n"));
		$output[48] =  $this->csvencode($row['bstreetaddress']);
		$output[49] =  $this->csvencode($row['bcity']);
		$output[51] =  $this->csvencode($row['bstate']);
		$output[52] =  $this->csvencode($row['bpostalcode']);
		$output[53] =  $this->csvencode($row['bcountry']);
		
		$output[56] =  $this->csvencode($row['organisationname']);
		$output[58] =  $this->csvencode($row['title']);
		$output[59] =  $this->csvencode($row['departmentnumber']);
		
		$i = 63;
		
		foreach($this->custom as $name => $label)
		{
			$output[$i] =  $this->csvencode($label);
			$i++;
			$output[$i] =  $this->csvencode($row[$name]);
			$i++;
		}

		echo implode($this->separator, $output)."\r\n";
	}

	
	/**
	 * Encode a value for CSV file
	 * @param	string $v
	 * @return string
	 */
	protected function csvencode($v)
	{
		if(strstr($v, "\n") !== false){
			return '"'.str_replace('"', '""', $v).'"';
		}else{
			return str_replace(array('"', ','), array('""', ''), $v);
		}
	}
	
	
	protected function getColumns()
	{
		$headers = array(
			0 => 'Name',							// full name
			1 => 'Given Name',
			2 => 'Additional Name',
			3 => 'Family Name',
			4 => 'Yomi Name',
			5 => 'Given Name Yomi',
			6 => 'Additional Name Yomi',
			7 => 'Family Name Yomi',
			8 => 'Name Prefix',
			9 => 'Name Suffix',
			10 => 'Initials',
			11 => 'Nickname',
			12 => 'Short Name',
			13 => 'Maiden Name',
			14 => 'Birthday',
			15 => 'Gender',
			16 => 'Location',
			17 => 'Billing Information',
			18 => 'Directory Server',
			19 => 'Mileage',
			20 => 'Occupation',
			21 => 'Hobby',
			22 => 'Sensitivity',
			23 => 'Priority',
			24 => 'Subject',
			25 => 'Notes',
			26 => 'Group Membership',
			27 => 'E-mail 1 - Type',				// Ce champ aura toujours la valeur *
			28 => 'E-mail 1 - Value',
			29 => 'Phone 1 - Type',					// Ce champ aura toujours la valeur Home
			30 => 'Phone 1 - Value',				// Telephone (domicile)
			31 => 'Phone 2 - Type',					// Ce champ aura toujours la valeur Mobile
			32 => 'Phone 2 - Value',				// Tel. mobile
			33 => 'Phone 3 - Type',					// Ce champ aura toujours la valeur Work
			34 => 'Phone 3 - Value',				// Telephone (bureau)
			35 => 'Phone 4 - Type',					// Ce champ aura toujours la valeur Work Fax
			36 => 'Phone 4 - Value',				// Telecopie (bureau)
			37 => 'Address 1 - Type',				// Ce champ aura toujours la valeur Home
			38 => 'Address 1 - Formatted',			// composee a partir de plusieurs champ de l'annuaire
			39 => 'Address 1 - Street',				// Rue (domicile)
			40 => 'Address 1 - City',				// Ville (domicile)
			41 => 'Address 1 - PO Box',			
			42 => 'Address 1 - Region',				// Dep/Region (domicile)
			43 => 'Address 1 - Postal Code',		// Code postal (domicile)
			44 => 'Address 1 - Country',			// Pays (domicile)
			45 => 'Address 1 - Extended Address',
			46 => 'Address 2 - Type',				// Ce champ aura toujours la valeur Work
			47 => 'Address 2 - Formatted',			// Adresse (bureau), composee a partir de plusieurs champ de l'annuaire
			48 => 'Address 2 - Street',				// Rue (bureau)
			49 => 'Address 2 - City',				// Ville (bureau)
			50 => 'Address 2 - PO Box',
			51 => 'Address 2 - Region',				// Dep/Region (bureau)
			52 => 'Address 2 - Postal Code',		// Code postal (bureau)
			53 => 'Address 2 - Country',			// Pays (bureau)
			54 => 'Address 2 - Extended Address',
			55 => 'Organization 1 - Type',			// Toujours vide
			56 => 'Organization 1 - Name',			// Societe
			57 => 'Organization 1 - Yomi Name',
			58 => 'Organization 1 - Title',			// Titre
			59 => 'Organization 1 - Department',	// Service
			60 => 'Organization 1 - Symbol',
			61 => 'Organization 1 - Location',
			62 => 'Organization 1 - Job Description'
			/*63 => 'Custom Field 1 - Type', 		// Ce champ aura toujours la valeur Resume
			64 => 'Custom Field 1 - Value', 	// Resume (champ supplementaire configure dans l'annuaire)
			65 => 'Custom Field 2 - Type', 		// Ce champ aura toujours la valeur Spoken languages
			66 => 'Custom Field 2 - Value', 	// Spoken languages (champ supplementaire configure dans l'annuaire)
			67 => 'Custom Field 3 - Type', 		// Ce champ aura toujours la valeur UIC identifier
			68 => 'Custom Field 3 - Value' 		// UIC identifier (champ supplementaire configure dans l'annuaire)*/
		);
		
		$i = 63;
		$j = 1;
		foreach($this->custom as $name => $label)
		{
			$headers[$i] =  'Custom Field '.$j.' - Type';
			$i++;
			$headers[$i] =  'Custom Field '.$j.' - Value';
			$i++;
			$j++;
		}
		
		return $headers;
	}
}



/**
 * Export CSV file, outlook format
 *
 */
class bab_dbdir_export_outlook_csv extends bab_dbdir_export_csv
{
	public function __construct($id_directory, $includedisabled)
	{
		parent::__construct($id_directory, $includedisabled);
	
		$this->init();
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
			$header[] = $label;
		}
		
		echo implode($this->separator, $header)."\r\n";
		
		while ($row = $babDB->db_fetch_assoc($this->res))
		{
			
			$this->outputRow($row);
		}
	}
	
	/**
	 * Encode a value for CSV file
	 * @param	string $v
	 * @return string
	 */
	protected function csvencode($v)
	{
		if($v == ''){
			return '';
		}
		return '"'.str_replace(array('"', "\n", '\r'), array('""', ' ', ''), $v).'"';
	}
	
	
	/**
	 * Output row
	 */
	protected function outputRow(Array $row)
	{
		
		$output = array_fill(0, 87, '');
		
		
		
		$output[0] = $this->csvencode($row['givenname']);
		$output[1] = $this->csvencode($row['mn']);
		$output[2] = $this->csvencode($row['sn']);
		$output[3] = $this->csvencode($row['title']);
		$output[14] = $this->csvencode($row['email']);
		$output[17] = $this->csvencode($row['btel']);
		$output[18] = $this->csvencode($row['htel']);
		$output[20] = $this->csvencode($row['mobile']);
		$output[23] = $this->csvencode($this->getAddress('h', $row));
		$output[24] =  $this->csvencode($row['hstreetaddress']);
		$output[28] =  $this->csvencode($row['hcity']);
		$output[29] =  $this->csvencode($row['hstate']);
		$output[30] =  $this->csvencode($row['hpostalcode']);
		$output[31] =  $this->csvencode($row['hcountry']);
		$output[38] =  $this->csvencode($row['htel']);
		$output[42] =  $this->csvencode($row['organisationname']);
		$output[43] =  $this->csvencode($row['title']);
		$output[44] =  $this->csvencode($row['departmentnumber']);
		$output[49] =  $this->csvencode($this->getAddress('b', $row));
		$output[50] =  $this->csvencode($row['bstreetaddress']);
		$output[54] =  $this->csvencode($row['bcity']);
		$output[55] =  $this->csvencode($row['bstate']);
		$output[56] =  $this->csvencode($row['bpostalcode']);
		$output[57] =  $this->csvencode($row['bcountry']);
		$output[75] =  $this->csvencode($row['user1']);
		$output[76] =  $this->csvencode($row['user2']);
		$output[77] =  $this->csvencode($row['user3']);
		$output[85] =  $this->csvencode('Normal');
		$output[87] =  $this->csvencode($this->directory['name']);
		
		
		echo implode($this->separator, $output).",\r\n";
	}
	
	
	protected function getColumns()
	{
		return array(
				
				0  => 'First Name',
				1  => 'Middle Name',
				2  => 'Last Name',
				3  => 'Title',
				4  => 'Suffix', 				// Vide
				5  => 'Initials', 				// Vide
				6  => 'Web Page', 				// Vide
				7  => 'Gender', 				// Vide
				8  => 'Birthday',				// Vide
				9  => 'Anniversary',			// Vide
				10  => 'Location',				// Vide
				11  => 'Language',				// Vide
				12  => 'Internet Free Busy',	// Vide
				13  => 'Notes',					// Vide
				14  => 'E-mail Address', 
				15  => 'E-mail 2 Address', 		// Vide
				16  => 'E-mail 3 Address', 		// Vide
				17  => 'Primary Phone',
				18  => 'Home Phone',
				19  => 'Home Phone 2',			// Vide
				20  => 'Mobile Phone',
				21  => 'Pager',					// Vide
				22  => 'Home Fax',				// Vide
				23  => 'Home Address',
				24  => 'Home Street',
				25  => 'Home Street 2',			// Vide
				26  => 'Home Street 3',			// Vide
				27  => 'Home Address PO Box',	// Vide
				28  => 'Home City',			
				29  => 'Home State',
				30  => 'Home Postal Code',	
				31  => 'Home Country',
				32  => 'Spouse',				// Vide
				33  => 'Children',				// Vide
				34  => 'Manager\'s Name', 		// Vide
				35  => 'Assistant\'s Name',		// Vide
				36  => 'Referred By',			// Vide
				37  => 'Company Main Phone',	// Vide
				38  => 'Business Phone',	
				39  => 'Business Phone 2',		// Vide
				40  => 'Business Fax',		
				41  => 'Assistant\'s Phone',	// Vide
				42  => 'Company',
				43  => 'Job Title',
				44  => 'Department',
				45  => 'Office Location',		// Vide
				46  => 'Organizational ID Number', // Vide
				47  => 'Profession',			// Vide
				48  => 'Account',				// Vide
				49  => 'Business Address',		
				50  => 'Business Street',
				51  => 'Business Street 2', 	// Vide
				52  => 'Business Street 3',		// Vide
				53  => 'Business Address PO Box',	// Vide
				54  => 'Business City',
				55  => 'Business State',
				56  => 'Business Postal Code',
				57  => 'Business Country',
				58  => 'Other Phone',			// Vide
				59  => 'Other Fax',				// Vide
				60  => 'Other Address',			// Vide
				61  => 'Other Street',			// Vide
				62  => 'Other Street 2',	 	// Vide
				63  => 'Other Street 3',		// Vide
				64  => 'Other Address PO Box',	// Vide
				65  => 'Other City',			// Vide
				66  => 'Other State',			// Vide
				67  => 'Other Postal Code',		// Vide
				68  => 'Other Country',			// Vide
				69  => 'Callback',				// Vide
				70  => 'Car Phone',				// Vide
				71  => 'ISDN',					// Vide
				72  => 'Radio Phone',			// Vide
				73  => 'TTY/TDD Phone',			// Vide
				74  => 'Telex',					// Vide
				75  => 'User 1',
				76  => 'User 2', 
				77  => 'User 3',
				78  => 'User 4',				// Vide
				79  => 'Keywords',				// Vide
				80  => 'Mileage',				// Vide
				81  => 'Hobby',					// Vide
				82  => 'Billing Information',	// Vide
				83  => 'Directory Server',		// Vide
				84  => 'Sensitivity',			// Vide
				85  => 'Priority',				// Ce champ aura toujours la valeur "Normal"
				86  => 'Private', 				// Vide
				87  => 'Categories'				
		);
	}
}







class bab_dbdir_export_vcard extends bab_dbdir_export
{
	
	public function __construct($id_directory, $includedisabled)
	{
		parent::__construct($id_directory, $includedisabled);
	
		$this->contenttype = 'text/vcard';
		$this->filename = bab_removeDiacritics($this->directory['name']).'.vcf';
		
		$this->init(null, true, true);
	}
	
	
	/**
	 * echo text to export
	 */
	protected function outputText()
	{
		global $babDB;
		
		while ($row = $babDB->db_fetch_assoc($this->res))
		{
			$this->outputRow($row);
		}
	}
	
	
	private function getPhotoType($mimetype)
	{
		switch($mimetype)
		{
			case 'image/png': return 'PNG';
			case 'image/jpeg': return 'JPG';
			case 'image/gif': return 'GIF';
		}
		
		return false;
	}

	
	/**
	 * Output row
	 */
	protected function outputRow(Array $row)
	{
		
		extract($row);
		
		if (!$sn)
		{
			return;
		}
		
		
		$T = @bab_functionality::get('Thumbnailer');
		$vcard_image_type = $this->getPhotoType($photo_type);
		$b64 = '';
		
		if ($T && $photo_data && $vcard_image_type)
		{
			/*@var $T Func_Thumbnailer */
			$T->setSourceBinary($photo_data, $date_modification);
			$imagePath = $T->getThumbnailPath(48, 48);
			$b64 = base64_encode(file_get_contents($imagePath->tostring()));
		}
		
		$rev = BAB_DateTime::fromIsoDateTime($date_modification);
		$date_modification = $rev->getICal(true);
		
		$vcard = "BEGIN:VCARD
VERSION:2.1
N:$sn;$givenname
FN:$givenname $sn
KIND:Individual
";
		
		if ($organisationname)
		{
			$vcard .= "ORG:$organisationname\n";
		}
		
		if ($title)
		{
			$vcard .= "TITLE:$title\n";
		}

		if ($b64 && $vcard_image_type)
		{
			$vcard .= "PHOTO;$vcard_image_type;ENCODING=BASE64:$b64\n";
		}
		
		if ($email)
		{
			$vcard .= "EMAIL:$email\n";
		}

		if ($mobile)
		{
			$vcard .= "TEL;CELL:$mobile\n";
		}

		if ($btel)
		{
			$vcard .= "TEL;WORK;VOICE:$btel\n";
		}
		
		if ($bfax)
		{
			$vcard .= "TEL;WORK;FAX:$bfax\n";
		}
		
		if ($bstreetaddress || $bcity)
		{
			$vcard .= "ADR;WORK:;;$bstreetaddress;$bcity;$bstate;$bpostalcode;$bcountry\n";
		}

		if ($htel)
		{
			$vcard .= "TEL;HOME;VOICE:$htel\n";
		}


		if ($hstreetaddress || $hcity)
		{
			$vcard .= "ADR;HOME:;;$hstreetaddress;$hcity;$hstate;$hpostalcode;$hcountry\n";
		}

		if ($date_modification)
		{
			$vcard .= "REV:$date_modification\n";
		}
		$vcard .= "END:VCARD\n";

		echo bab_convertStringFromDatabase($vcard, 'UTF-8');
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