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

class bab_sqlExport
	{
	function bab_sqlExport($tables = false, $structure = 1, $drop_table = 0, $data = 0)
		{
		ini_set('max_execution_time','2400');
		
		$this->tables = $tables;
		$this->opt_structure = $structure;
		$this->opt_drop_table = $drop_table;
		$this->opt_data = $data;
		}

	function sqlExport($output = false)
		{
		$this->output = $output;

		$this->search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
        $this->replace      = array('\0', '\n', '\r', '\Z');
		$this->autoComma = 0;
		$this->commaGroup = array();
		$this->key_index = array();
		$this->dump = '';
		$this->db = &$GLOBALS['babDB'];
		
		$this->commentPush(bab_translate("Ovidentia database dump"));
		
		$this->commentPush("babSiteName : ".$GLOBALS['babSiteName'],true);
		$this->commentPush("babDBName : ".$GLOBALS['babDBName'],true);
		$this->commentPush("babDBLogin : ".$GLOBALS['babDBLogin'],true);
		$this->commentPush("babInstallPath : ".$GLOBALS['babInstallPath'],true);
		$this->commentPush("babUrl : ".$GLOBALS['babUrl'],true);
		
		
			
		$this->commentPush(bab_translate('Ovidentia version')." : ".bab_getDbVersion(),true);
			
		$arr = $this->db->db_fetch_array($this->db->db_query("show variables like 'version'"));
		$this->commentPush(bab_translate('Database server version')." : ".$arr['Value'],true);
		$this->commentPush(bab_translate('Php version')." : ".phpversion(),true);
		$this->commentPush(bab_translate('Date')." : ".bab_longDate(time()),true);

		if ($this->tables) {
			foreach($this->tables as $tablename)
				{
				$this->handleTable($tablename);
				}
			}
		}
		
	function sqlAddslashes($str)
		{
		global $babDB;
		return $babDB->db_escape_string($str);
		}
		
	function commentPush($str,$nobr = false)
		{
		if (!$nobr) $this->str_output("\n");
		$this->str_output("# ".$str."\n");
		if (!$nobr) $this->str_output("\n");
		}
		
	function brPush()
		{
		$this->str_output("\n");
		}
		
	function autoCommaStart()
		{
		$this->autoComma = 1;
		}
		
	function autoCommaEnd()
		{
		$this->autoComma = 0;
		$cnt = count($this->commaGroup);
		for ($i = 0; $i < $cnt ; $i++)
			{
			if ($i < $cnt-1)
				$this->dumpPush($this->commaGroup[$i].',');
			else
				$this->dumpPush($this->commaGroup[$i]);
			}
			
		$this->commaGroup = array();
		}
		
	function dumpPush($str)
		{
		if ($this->autoComma)
			$this->commaGroup[] = $str;
		else
			$this->str_output($str."\n");
		}
		
	function handleTable(&$name)
		{
		if ($this->opt_structure)
			$this->commentPush(bab_translate('Table structure for table')." `".$name."`");

		if ($this->opt_drop_table)
			$this->dumpPush("DROP TABLE IF EXISTS `".$name."`;");
		if ($this->opt_structure)
			{
			$this->dumpPush("CREATE TABLE `".$name."` (");
			
			$this->autoCommaStart();
			}
			
		$this->table_collumn = array();
		$describe = $this->db->db_query("DESCRIBE ".$name);
		while($collumn = $this->db->db_fetch_array($describe))
			{
			$this->handleCollumn($collumn);
			}
			
		if ($this->opt_structure)
			{
			$key = $this->db->db_query("SHOW KEYS FROM ".$name);
			while($line = $this->db->db_fetch_array($key))
				{
				$this->handleKey($line);
				}
			$this->dumpKeys();
			
			$this->autoCommaEnd();
			
			$this->dumpPush(") TYPE=MyISAM;");
			}
			
		
			
		if ($this->opt_data)
			{
			$this->commentPush(bab_translate('Dumping data for table')." `".$name."`");
			
			$select = $this->db->db_query("SELECT * FROM ".$name);
			while($line = $this->db->db_fetch_array($select))
				{
				$this->handleData($line,$name);
				}
			}
			
		$this->brPush();
		}
		
	function handleCollumn(&$collumn)
		{
		$this->table_collumn[] = $collumn['Field'];
		$this->collumn_type[$collumn['Field']] = $collumn['Type'];
		if ($this->opt_structure)
			{
			$str = '`'.$collumn['Field'].'` '.$collumn['Type'];
			if (!empty($collumn['Default']) )
				{
				$collumn['Default'] = str_replace('\\','\\\\',$collumn['Default']);
				$collumn['Default'] = str_replace("'","''",$collumn['Default']);
					if ('CURRENT_TIMESTAMP' != $collumn['Default']) {
					$str .= ' DEFAULT \''.$collumn['Default'].'\'';
					}
				}
			if ($collumn['Null'] != 'YES')
				$str .= ' NOT NULL';
			if (!empty($collumn['Extra']))
				$str .= ' ' . $collumn['Extra'];
				
			$this->dumpPush($str);
			}
		}
		
	function handleKey(&$row)
		{
		
		$kname    = $row['Key_name'];
		$comment  = (isset($row['Comment'])) ? $row['Comment'] : '';
		$sub_part = (isset($row['Sub_part'])) ? $row['Sub_part'] : '';

		if ($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
			$kname = 'UNIQUE|' . $kname;
			}
		if ($comment == 'FULLTEXT') {
			$kname = 'FULLTEXT|' . $kname;
			}
		if (!isset($this->key_index[$kname])) {
			$this->key_index[$kname] = array();
			}

		if ($sub_part > 1) {
			$this->key_index[$kname][] = $row['Column_name'] . '(' . $sub_part . ')';
			} 
		else {
			$this->key_index[$kname][] = $row['Column_name'];
			}
		}
		
	function dumpKeys()
		{
		if (!is_array($this->key_index) || count($this->key_index)  == 0)
			return false;
		reset($this->key_index);

		while (list($x, $columns) = each($this->key_index)) 
			{
			if ($x == 'PRIMARY') {
				$schema_create = 'PRIMARY KEY (';
				} else 
			if (substr($x, 0, 6) == 'UNIQUE') {
				$schema_create = 'UNIQUE ' . substr($x, 7) . ' (';
				} else 
			if (substr($x, 0, 8) == 'FULLTEXT') {
				$schema_create = 'FULLTEXT ' . substr($x, 9) . ' (';
				} 
			else {
				$schema_create = 'KEY ' . $x . ' (';
				}
			$this->dumpPush( $schema_create.implode($columns, ', ') . ')');
			}
		$this->key_index = array();
		}
		
	function getType($str)
		{
		if (substr_count($str,'(') > 0)
			{
			$tmp = explode('(',$str);
			return $tmp[0];
			}
		else
			return $str;
		}
		
	function handleData(&$line,&$table)
		{
		$value = array();
		for ($i = 0 ; $i < count($this->table_collumn) ; $i++ )
			{
			$col = $this->table_collumn[$i];
			
			if (!isset($line[$col]))
				$value[$i] = 'NULL';
			elseif ($line[$col] == 0 || $line[$col] != '')
				{
				switch ($this->getType($this->collumn_type[$col]))
					{
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'bigint':
					case 'timestamp':
						$value[$i] = "'".$line[$col]."'";
						break;
					case 'blob':
					case 'mediumblob':
					case 'longblob':
					case 'tinyblob':
						if (!empty($line[$col]))
							$value[$i] = '0x' . bin2hex($line[$col]);
						else
							$value[$i] = "''";
						break;
					default: // string
						$value[$i] = "'".str_replace($this->search, $this->replace, $this->sqlAddslashes($line[$col]))."'";
						break;
					}
				}
			else
				{
				$value[$i] = "''";
				}
			}

		$this->dumpPush( 'INSERT INTO `'.$table.'` (`'.implode('`,`',$this->table_collumn).'`) VALUES ('.implode(',',$value).');');
		}

	function str_output($str)
		{
		if (false === $this->output)
			echo $str;
		else
			{
			$func = $this->output;
			$this->dump .= $str;
			}
		}
		
	function exportFile()
		{
		header("content-type:text/plain");
		header("Content-Disposition: attachment; filename=".$GLOBALS['babSiteName'].".sql");


		$this->sqlExport();
		die();
		}
		
	function exportString()
		{
		$this->sqlExport(true);
		return $this->dump;
		}
		
	}


?>