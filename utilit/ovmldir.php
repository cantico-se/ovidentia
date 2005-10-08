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
include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";


class bab_DbDirectories extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	function bab_DbDirectories( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$directoryid = $ctx->get_value('directoryid');
		if( $directoryid === false || $directoryid === '' )
			{
			$res = $babDB->db_query("select id, name, description from ".BAB_DB_DIRECTORIES_TBL." order by name asc");
			}
		else
			{
			$directoryid = explode(',', $directoryid);
			$res = $babDB->db_query("select id, name, description from ".BAB_DB_DIRECTORIES_TBL." where id IN (".implode(',', $directoryid).") order by name asc");
			}

		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
				{
				array_push($this->IdEntries, $row);
				}
			}
		$this->count = count($this->IdEntries);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryName', $this->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('DirectoryDescription', $this->IdEntries[$this->idx]['description']);
			$this->ctx->curctx->push('DirectoryId', $this->IdEntries[$this->idx]['id']);
			$this->ctx->curctx->push('DirectoryUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->IdEntries[$this->idx]['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_DbDirectory extends bab_DbDirectories
{

	function bab_DbDirectory( &$ctx)
	{
		$directoryid = $ctx->get_value('directoryid');
		if( $directoryid !== false && !empty($directoryid) )
			{
			$this->bab_DbDirectories($ctx);
			}
		else
			{
			$this->bab_handler($ctx);
			$this->count = 0;
			$this->ctx->curctx->push('CCount', $this->count);
			}
	}

}


class bab_DbDirectoryFields extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	function bab_DbDirectoryFields( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$directoryid = $ctx->get_value('directoryid');
		if( $directoryid !== false && !empty($directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $directoryid) )
			{
			$ball = $ctx->get_value('all');
			$res = $babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$directoryid."'");
			if( $res && $babDB->db_num_rows($res ) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$idgroup = $arr['id_group'];
				if( $ball )
					{
					$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $directoryid)."' order by list_ordering asc");
					}
				else
					{
					$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $directoryid)."' and ordering!='0' order by ordering asc");
					}

				while( $arr = $babDB->db_fetch_array($res))
					{
					if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
						$this->IdEntries[] = array('name' => translateDirectoryField($rr['description']) , 'xname' => $rr['name']);
						}
					else
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						$this->IdEntries[] = array('name' => translateDirectoryField($rr['name']) , 'xname' => "babdirf".$arr['id']);
						}
					}
				}
			$this->count = count($this->IdEntries);
			}
		else
			{
			$this->count = 0;
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryFieldName', $this->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('DirectoryFieldId', $this->IdEntries[$this->idx]['xname']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}



class bab_DbDirectoryMembers extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;
	var $memberfields;
	var $dirfields = array();
	var $accountid;

	function bab_DbDirectoryMembers( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$this->directoryid = $ctx->get_value('directoryid');
		if( $this->directoryid !== false && !empty($this->directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->directoryid) )
			{
			$res = $babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->directoryid."'");
			if( $res && $babDB->db_num_rows($res ) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$idgroup = $arr['id_group'];
				$idfields = $ctx->get_value('fields');
				if( $idfields === false || empty($idfields) )
					{
					$ball = $ctx->get_value('all');
					if( !$ball )
						{
						$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $this->directoryid)."' and ordering!='0' order by ordering asc");
						$idfields = array();
						while( $arr = $babDB->db_fetch_array($res))
							{
							if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
								{
								$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
								$idfields[] = $rr['name'];
								}
							else
								{
								$idfields[] = "babdirf".$arr['id'];
								}
							}
						}
					}
				else
					{
					$ball = false;
					$idfields = explode(',', $idfields );
					}

				$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $this->directoryid)."' order by list_ordering asc");

				$nfields = array();
				$xfields = array();

				while( $arr = $babDB->db_fetch_array($res))
					{
					if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
						if( $ball || in_array($rr['name'], $idfields))
							{
							$nfields[] = $rr['name'];
							$this->IdEntries[] = array('name' => translateDirectoryField($rr['description']) , 'xname' => $rr['name']);
							}
						}
					else
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						if( $ball || in_array( "babdirf".$arr['id'], $idfields))
							{
							$xfields[] = "babdirf".$arr['id'];
							$this->IdEntries[] = array('name' => translateDirectoryField($rr['name']) , 'xname' => "babdirf".$arr['id']);
							}
						}
					}
				
				$this->count = 0;

				if( count($nfields) > 0 || count($xfields) > 0)
					{
					$nfields[] = "id";
					$nfields[] = "id_user";
					$orderby = $ctx->get_value('orderby');

					if( $orderby === false || empty($orderby) )
						{
						$orderby = $nfields[0];
						}

					$order = $ctx->get_value('order');

					if( $order === false || empty($order) )
						{
						$order = 'asc';
						}

					$like = $ctx->get_value('like');

					if( $like === false || empty($like) )
						{
						$like = '';
						}

					$babDB->db_query("create temporary table bab_dbdir_temptable select ".implode(',', $nfields)." from ".BAB_DBDIR_ENTRIES_TBL." where 0");
					$babDB->db_query("alter table bab_dbdir_temptable add unique (id)");

					for( $m=0; $m < count($nfields); $m++)
						{
						$nfields[$m] = "det.".$nfields[$m];
						}

					if( $idgroup > 1 )
						{
						$req = "insert into bab_dbdir_temptable select ".implode($nfields, ",")." from ".BAB_DBDIR_ENTRIES_TBL." det join ".BAB_USERS_GROUPS_TBL." ugt where ugt.id_group='".$idgroup."' and ugt.id_object=det.id_user and det.id_directory='".($idgroup != 0? 0: $this->directoryid)."'";
						}
					else
						{
						$req = "insert into bab_dbdir_temptable select ".implode($nfields, ",")." from ".BAB_DBDIR_ENTRIES_TBL." det where det.id_directory='".($idgroup != 0? 0: $this->directoryid)."'";
						}

					$babDB->db_query($req);

					for( $i=0; $i < count($xfields); $i++)
						{
						$babDB->db_query("alter table bab_dbdir_temptable add `".$xfields[$i]."` VARCHAR( 255 ) NOT NULL");
						}

					if( count($xfields) > 0 )
						{
						$res = $babDB->db_query("select id from bab_dbdir_temptable");
						while( $rr = $babDB->db_fetch_array($res))
							{
							for( $k = 0; $k < count($xfields); $k++ )
								{
								$tmparr = substr($xfields[$k], strlen("babdirf"));
								$sqlfv = array();
								$res2 = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$tmparr."' and id_entry='".$rr['id']."'");
								while( $rf = $babDB->db_fetch_array($res2))
									{
									$sqlfv[] = "`".$xfields[$k]."`='".$rf['field_value']."'";
									}
								if( count($sqlfv) > 0 )
									{
									$babDB->db_query("update bab_dbdir_temptable set ".implode(',', $sqlfv)." where id='".$rr['id']."'");
									}
								}
							}
						}

					$req = "select * from bab_dbdir_temptable where `".$orderby."` like '".$like."%' order by `".$orderby."` ".$order;

					$this->res = $babDB->db_query($req);				
					$this->count = $babDB->db_num_rows($this->res);

					/* find prefered mail account */
					$this->accountid = 0;
					$res = $babDB->db_query("select id from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$GLOBALS['BAB_SESS_USERID']."' order by prefered desc limit 0,1");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$arr = $babDB->db_fetch_array($res);
						$this->accountid = $arr['id'];
						}
					}
				}
			}
		else
			{
			$this->count = 0;
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->memberfields = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryMemberId', $this->memberfields['id']);
			$this->ctx->curctx->push('DirectoryMemberUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->directoryid."&userid=".$this->memberfields['id']);
			if( isset($this->memberfields['email']) && $this->accountid )
				{
				$this->ctx->curctx->push('DirectoryMemberEmailUrl', $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accountid."&to=".$this->memberfields['email']);
				}
			else
				{
				$this->ctx->curctx->push('DirectoryMemberEmailUrl', '');
				}

			for( $k = 0; $k < count($this->IdEntries); $k++ )
				{
				$this->ctx->curctx->push($this->IdEntries[$k]['xname']."Name", $this->IdEntries[$k]['name']);
				$this->ctx->curctx->push($this->IdEntries[$k]['xname']."Value", $this->memberfields[$this->IdEntries[$k]['xname']]);
				}
			if( $this->memberfields['id_user'] != 0 )
				{
				$this->ctx->curctx->push('DirectoryMemberUserId', $this->memberfields['id_user']);
				}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			$this->IdEntries = array();
			return false;
		}
	}
}


class bab_DbDirectoryMemberFields extends bab_handler
{
	var $handler;

	function bab_DbDirectoryMemberFields( &$ctx)
	{
		$this->bab_handler($ctx);
		$this->handler = $ctx->get_handler('bab_DbDirectoryMembers');
		if( $this->handler !== false && $this->handler !== '' )
			{
			$this->count = count($this->handler->IdEntries);
			}
		else
			{
			$this->count = 0;
			}

	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryFieldName', $this->handler->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('DirectoryFieldId', $this->handler->IdEntries[$this->idx]['xname']);
			$this->ctx->curctx->push('DirectoryMemberUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->handler->directoryid."&userid=".$this->handler->memberfields['id']);
			$this->ctx->curctx->push('DirectoryFieldValue', $this->handler->memberfields[$this->handler->IdEntries[$this->idx]['xname']]);
			if( isset($this->handler->memberfields['email']) && $this->handler->accountid )
				{
				$this->ctx->curctx->push('DirectoryMemberEmailUrl', $GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->handler->accountid."&to=".$this->handler->memberfields['email']);
				}
			else
				{
				$this->ctx->curctx->push('DirectoryMemberEmailUrl', '');
				}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}

}


class bab_DbDirectoryEntry extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;
	var $directoryid;
	var $userid;


	function bab_DbDirectoryEntry( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$this->directoryid = $ctx->get_value('directoryid');
		$this->count = 0;

		if( $this->directoryid !== false && !empty($this->directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->directoryid) )
			{
			$this->userid = $ctx->get_value('userid');
			$this->memberid = $ctx->get_value('memberid');
			if( ($this->userid !== false && !empty($this->userid)) ||  ($this->memberid !== false && !empty($this->memberid)) )
				{
				list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$this->directoryid."'"));

				$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $this->directoryid)."' AND disabled='N' order by list_ordering asc");

				while( $arr = $babDB->db_fetch_array($res))
					{
					if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
						$nfields[] = $rr['name'];
						$this->IdEntries[] = array('name' => translateDirectoryField($rr['description']) , 'xname' => $rr['name']);
						}
					else
						{
						$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						$xfields[] = "babdirf".$arr['id'];
						$this->IdEntries[] = array('name' => translateDirectoryField($rr['name']) , 'xname' => "babdirf".$arr['id']);
						}
					}

				$this->arrentries = array();

				if( $this->memberid !== false && !empty($this->memberid) )
					{
					$wh = "id='".$this->memberid."'";
					}
				else
					{
					$wh = "id_user='".$this->userid."'";
					}
				$res = $babDB->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." det where det.id_directory='".($idgroup != 0? 0: $this->directoryid)."' and ".$wh);

				$this->arrentries = $babDB->db_fetch_array($res);

				$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$this->arrentries['id']."'");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$this->arrentries['babdirf'.$arr['id_fieldx']] = $arr['field_value'];
					}

				if( $this->arrentries['id_user'] != 0 )
					{
					$this->ctx->curctx->push('DirectoryEntryUserId', $this->arrentries['id_user']);
					}
				$this->ctx->curctx->push('DirectoryEntryMemberId', $this->arrentries['id']);

				$this->count = 1;
				}
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);

			for( $k=0; $k < count($this->IdEntries); $k++ )
				{
				$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Name', $this->IdEntries[$k]['name']);
				if( isset($this->arrentries[$this->IdEntries[$k]['xname']]))
					{
					if( $this->IdEntries[$k]['xname'] == 'jpegphoto' && $this->arrentries['plen'] != 0 )
						{
						$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->directoryid."&idu=".$this->arrentries['id']);
						}
					else
						{
						$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', $this->arrentries[$this->IdEntries[$k]['xname']]);
						}
					}
				else
					{
					$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', '');
					}
				}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_DbDirectoryEntryFields extends bab_handler
{
	var $handler;

	function bab_DbDirectoryEntryFields( &$ctx)
	{
		$this->bab_handler($ctx);
		$this->handler = $ctx->get_handler('bab_DbDirectoryEntry');
		if( $this->handler !== false && $this->handler !== '' )
			{
			$this->count = count($this->handler->IdEntries);
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryFieldName', $this->handler->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('DirectoryFieldId', $this->handler->IdEntries[$this->idx]['xname']);
			if( isset($this->handler->arrentries[$this->handler->IdEntries[$this->idx]['xname']]))
				{
				if( $this->handler->IdEntries[$this->idx]['xname'] == 'jpegphoto' && $this->handler->arrentries['plen'] != 0 )
					{
					$this->ctx->curctx->push('DirectoryFieldValue', $GLOBALS['babUrlScript']."?tg=directory&idx=getimg&id=".$this->handler->directoryid."&idu=".$this->handler->arrentries['id']);
					}
				else
					{
					$this->ctx->curctx->push('DirectoryFieldValue', $this->handler->arrentries[$this->handler->IdEntries[$this->idx]['xname']]);
					}
				}
			else
				{
				$this->ctx->curctx->push('DirectoryFieldValue', '');
				}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}

}


class bab_DbDirectoryAcl extends bab_handler
{
	var $IdEntries = array();
	var $ctx;
	var $index;
	var $idx = 0;
	var $count = 0;

	function bab_DbDirectoryAcl( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$directoryid = $ctx->get_value('directoryid');

		if( $directoryid !== false && $directoryid !== '' )
		{
			$type = $ctx->get_value('type');
			if( $type !== false && $type !== '' )
			{
				switch(strtolower($type))
				{
					case 'add':
						$table = BAB_DBDIRADD_GROUPS_TBL;
						break;
					case 'modify':
						$table = BAB_DBDIRUPDATE_GROUPS_TBL;
						break;
					case 'view':
					default:
						$table = BAB_DBDIRVIEW_GROUPS_TBL;
						break;
				}
			
			}
			else
			{
				$table = BAB_DBDIRVIEW_GROUPS_TBL;
			}

			include_once $GLOBALS['babInstallPath']."utilit/addonapi.php";
			$groups = bab_getGroupsAccess($table, $directoryid);
			$this->IdEntries = bab_getGroupsMembers($groups);	
			$this->count = count($this->IdEntries);
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('UserId', $this->IdEntries[$this->idx]['id']);
			$this->ctx->curctx->push('UserFullName', $this->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('UserEmail', $this->IdEntries[$this->idx]['email']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}
}

?>