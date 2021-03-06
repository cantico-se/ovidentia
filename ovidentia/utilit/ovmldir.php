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

include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/omlincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/userinfosincl.php';

class Func_Ovml_Container_DbDirectories extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$directoryid = $ctx->curctx->getAttribute('directoryid');
		$directorytype = mb_strtolower($ctx->curctx->getAttribute('type'));
		$delegationid = (int) $ctx->curctx->getAttribute('delegationid');

		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$groupsId = bab_getDelegateGroupe(true, $delegationid);
			$sDelegation = " AND (id_dgowner = '" . $babDB->db_escape_string($delegationid). "' OR id_group IN(".$babDB->quote($groupsId).") )";
		}

		if( $directoryid === false || $directoryid === '' )
			{
			if( $directorytype === false || !in_array($directorytype, array('database', 'group')) )
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL. ((0 != $delegationid) ? " where (id_dgowner = '" . $babDB->db_escape_string($delegationid) . "' OR id_group IN(".$babDB->quote($groupsId)."))" : ' ') .  " order by name asc");
				}
			elseif ('database' == $directorytype)
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." WHERE id_group='0'" . $sDelegation . "order by name asc");
				}
			elseif ('group' == $directorytype)
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." WHERE id_group>'0'" . $sDelegation . "order by name asc");
				}
			}
		else
			{
			$directoryid = explode(',', $directoryid);
			if( $directorytype === false || !in_array($directorytype, array('database', 'group')) )
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id IN (".$babDB->quote($directoryid).")" . $sDelegation . "order by name asc");
				}
			elseif ('database' == $directorytype)
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id IN (".$babDB->quote($directoryid).")" . $sDelegation . "AND id_group='0' order by name asc");
				}
			elseif ('group' == $directorytype)
				{
				$res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." where id IN (".$babDB->quote($directoryid).")" . $sDelegation . "AND id_group>'0' order by name asc");
				}
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

	public function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryName', $this->IdEntries[$this->idx]['name']);
			$this->ctx->curctx->push('DirectoryDescription', $this->IdEntries[$this->idx]['description']);
			$this->ctx->curctx->push('DirectoryId', $this->IdEntries[$this->idx]['id']);
			$this->ctx->curctx->push('DirectoryUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=sdb&id=".$this->IdEntries[$this->idx]['id']);
			$this->ctx->curctx->push('DirectoryDelegationId', $this->IdEntries[$this->idx]['id_dgowner']);
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


class Func_Ovml_Container_DbDirectory extends Func_Ovml_Container_DbDirectories
{

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$directoryid = $ctx->curctx->getAttribute('directoryid');
		if( $directoryid !== false && !empty($directoryid) )
			{
			parent::setOvmlContext($ctx);
			}
		else
			{
			parent::setOvmlContext($ctx);
			$this->count = 0;
			$this->ctx->curctx->push('CCount', $this->count);
			}
	}

}


class Func_Ovml_Container_DbDirectoryFields extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$directoryid = $ctx->curctx->getAttribute('directoryid');
		if( $directoryid !== false && !empty($directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $directoryid) )
			{
			$ball = $ctx->curctx->getAttribute('all');
			$res = $babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($directoryid)."'");
			if( $res && $babDB->db_num_rows($res ) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$idgroup = $arr['id_group'];
				if( $ball )
					{
					$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($directoryid))."' order by list_ordering asc");
					}
				else
					{
					$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($directoryid))."' and ordering!='0' order by ordering asc");
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

	public function getnext()
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


/** 
 * <OCDbDirectoryMembers></OCDbDirectoryMembers>
 */
class Func_Ovml_Container_DbDirectoryMembers extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;
	var $memberfields;
	var $dirfields = array();
	var $accountid;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$this->directoryid = $ctx->curctx->getAttribute('directoryid');
		if( $this->directoryid !== false && !empty($this->directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->directoryid) ) {
			$res = $babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($this->directoryid)."'");
			if( $res && $babDB->db_num_rows($res ) > 0 ) {
				$arr = $babDB->db_fetch_array($res);
				$idgroup = $arr['id_group'];
				$idfields = $ctx->curctx->getAttribute('fields');
				if( $idfields === false || empty($idfields) ) {
					$ball = $ctx->curctx->getAttribute('all');
					if( !$ball ) {
						$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($this->directoryid))."' and ordering!='0' order by ordering asc");
						$idfields = array();
						while( $arr = $babDB->db_fetch_array($res)) {
							if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS ) {
								$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
								$idfields[] = $rr['name'];
							} else {
								$idfields[] = "babdirf".$arr['id'];
							}
						}
					}
				} else {
					$ball = false;
					$idfields = explode(',', $idfields );
				}

				$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($this->directoryid))."' order by list_ordering asc");

				$nfields = array();
				$xfields = array();
				$leftjoin = array();
				$select = array();

				while( $arr = $babDB->db_fetch_array($res)) {
					if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS ) {
						$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
						if( $ball || in_array($rr['name'], $idfields)) {
							$nfields[] = $rr['name'];
							$this->IdEntries[] = array('name' => translateDirectoryField($rr['description']) , 'xname' => $rr['name']);
							$select[] = 'e.'.$rr['name'];
						}
					} else {
						$rr = $babDB->db_fetch_array($babDB->db_query("select name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						if( $ball || in_array( "babdirf".$arr['id'], $idfields)) {
							$xfields[] = "babdirf".$arr['id'];
							$this->IdEntries[] = array('name' => translateDirectoryField($rr['name']) , 'xname' => "babdirf".$arr['id']);

							$leftjoin[] = 'LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' lj'.$arr['id']." ON lj".$arr['id'].".id_fieldx='".$arr['id']."' AND e.id=lj".$arr['id'].".id_entry";
							$select[] = "lj".$arr['id'].'.field_value '."babdirf".$arr['id']."";
						}
					}
				}
				
				$this->count = 0;

				if( count($nfields) > 0 || count($xfields) > 0) {
					$nfields[] = "id";
					$select[] = 'e.id';
					$nfields[] = "id_user";
					$select[] = 'e.id_user';
					$select[] = 'e.date_modification';
					$select[] = 'e.id_modifiedby';
					if( !in_array('email', $select)) {
						$select[] = 'e.email';
					}

					$orderby = $ctx->curctx->getAttribute('orderby');

					if( $orderby === false || empty($orderby) ) {
						$orderby = $nfields[0];
					}

					$order = $ctx->curctx->getAttribute('order');

					if( $order === false || empty($order) ) {
						$order = 'asc';
					}

					$like = $ctx->curctx->getAttribute('like');

					if( $like === false || empty($like) ) {
						$like = '';
					} else {
						if ( false === mb_strpos($orderby, 'babdirf')) {
							$like = " AND `".$babDB->db_escape_string($orderby)."` LIKE '".$babDB->db_escape_string($like)."%'";
						} elseif (0 === mb_strpos($orderby, 'babdirf')) {
							$idfield = mb_substr($orderby,7);
							$like = " AND lj".$idfield.".field_value LIKE '".$babDB->db_escape_string($like)."%'";
						} else {
							$like = '';
						}
					}


					if( $idgroup > 1 ) {
						$req = " ".BAB_USERS_TBL." u2,
								".BAB_USERS_GROUPS_TBL." u,
								".BAB_DBDIR_ENTRIES_TBL." e 
									".implode(' ',$leftjoin)." 
									WHERE u.id_group='".$idgroup."' 
									AND u2.id=e.id_user 
									AND ".bab_userInfos::queryAllowedUsers('u2')." 
									AND u.id_object=e.id_user 
									AND e.id_directory='0'";
					} elseif (1 == $idgroup) {
						$req = " ".BAB_USERS_TBL." u,
						".BAB_DBDIR_ENTRIES_TBL." e 
						".implode(' ',$leftjoin)." 
						WHERE 
							u.id=e.id_user 
							AND ".bab_userInfos::queryAllowedUsers('u')." 
							AND e.id_directory='0'";
					} else {
						$req = " ".BAB_DBDIR_ENTRIES_TBL." e ".implode(' ',$leftjoin)." WHERE e.id_directory='".$babDB->db_escape_string($this->directoryid) ."'";
					}


					$req = "select ".implode(',', $select)." from ".$req." ".$like." order by `".$babDB->db_escape_string($orderby)."` ".$babDB->db_escape_string($order);

					$this->res = $babDB->db_query($req);				
					$this->count = $babDB->db_num_rows($this->res);

					/* find prefered mail account */
					$this->accountid = 0;
					
				}
			}
		} else {
			$this->count = 0;
		}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count) {
			$this->memberfields = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('DirectoryMemberId', $this->memberfields['id']);
			if( $this->memberfields['date_modification'] == '0000-00-00 00:00:00') {
				$this->ctx->curctx->push('DirectoryMemberUpdateDate', '');
			} else {
				$this->ctx->curctx->push('DirectoryMemberUpdateDate', bab_mktime($this->memberfields['date_modification']));
			}
			$this->ctx->curctx->push('DirectoryMemberUpdateAuthor', $this->memberfields['id_modifiedby']);
			$this->ctx->curctx->push('DirectoryMemberUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->directoryid."&userid=".$this->memberfields['id']);
			$this->ctx->curctx->push('DirectoryMemberEmailUrl', '');


			for( $k = 0; $k < count($this->IdEntries); $k++ ) {
				$this->ctx->curctx->push($this->IdEntries[$k]['xname']."Name", $this->IdEntries[$k]['name']);
				if( $this->IdEntries[$k]['xname'] == 'jpegphoto') {
					$photo = new bab_dirEntryPhoto($this->memberfields['id']);
					$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', $photo->getUrl());
				} else {
					$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', $this->memberfields[$this->IdEntries[$k]['xname']]);
				}
			}
			if( $this->memberfields['id_user'] != 0 ) {
				$this->ctx->curctx->push('DirectoryMemberUserId', $this->memberfields['id_user']);
			}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		} else {
			$this->idx=0;
			$this->IdEntries = array();
			return false;
		}
	}
}


class Func_Ovml_Container_DbDirectoryMemberFields extends Func_Ovml_Container
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		parent::setOvmlContext($ctx);
		$this->handler = $ctx->get_handler('Func_Ovml_Container_DbDirectoryMembers');
		if( $this->handler !== false && $this->handler !== '' )
			{
			$this->count = count($this->handler->IdEntries);
			}
		else
			{
			$this->count = 0;
			}

	}

	public function getnext()
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


class Func_Ovml_Container_DbDirectoryEntry extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;
	var $directoryid;
	var $userid;


	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$this->directoryid = $ctx->curctx->getAttribute('directoryid');
		$this->count = 0;

		if( $this->directoryid !== false && !empty($this->directoryid) && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->directoryid) )
			{
			$this->userid = $ctx->curctx->getAttribute('userid');
			$this->memberid = $ctx->curctx->getAttribute('memberid');
			if( ($this->userid !== false && !empty($this->userid)) ||  ($this->memberid !== false && !empty($this->memberid)) )
				{
				list($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from ".BAB_DB_DIRECTORIES_TBL." where id='".$babDB->db_escape_string($this->directoryid)."'"));

				$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($this->directoryid))."' AND disabled='N' order by list_ordering asc");

				$nfields = array();
				$xfields = array();
				
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
					$wh = "id='".$babDB->db_escape_string($this->memberid)."'";
					}
				else
					{
					$wh = "id_user='".$babDB->db_escape_string($this->userid)."'";
					}
				$res = $babDB->db_query("select *, LENGTH(photo_data) as plen from ".BAB_DBDIR_ENTRIES_TBL." det where det.id_directory='".($idgroup != 0? 0: $babDB->db_escape_string($this->directoryid))."' and ".$wh);

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
				if( $this->arrentries['date_modification'] == '0000-00-00 00:00:00')
					$this->ctx->curctx->push('DirectoryMemberUpdateDate', '');
				else
					$this->ctx->curctx->push('DirectoryMemberUpdateDate', bab_mktime($this->arrentries['date_modification']));
				$this->ctx->curctx->push('DirectoryMemberUpdateAuthor', $this->arrentries['id_modifiedby']);

				$this->ctx->curctx->push('DirectoryEntryMemberUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->directoryid."&userid=".$this->arrentries['id']);

				if (bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $this->directoryid)) {
					$this->ctx->curctx->push('DirectoryEntryEditUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$this->directoryid."&idu=".$this->arrentries['id']);
				} else {
					$this->ctx->curctx->push('DirectoryEntryEditUrl', '');
				}

				if (bab_isAccessValid(BAB_DBDIRDEL_GROUPS_TBL, $this->directoryid)) {
					$this->ctx->curctx->push('DirectoryEntryDeleteUrl', $GLOBALS['babUrlScript']."?tg=directory&idx=deldbc&id=".$this->directoryid."&idu=".$this->arrentries['id']);
				} else {
					$this->ctx->curctx->push('DirectoryEntryDeleteUrl', '');
				}


				$this->count = 1;
				}
			}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
						$photo = new bab_dirEntryPhoto($this->arrentries['id']);
						$this->ctx->curctx->push($this->IdEntries[$k]['xname'].'Value', $photo->getUrl());
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


class Func_Ovml_Container_DbDirectoryEntryFields extends Func_Ovml_Container
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		parent::setOvmlContext($ctx);
		$this->handler = $ctx->get_handler('Func_Ovml_Container_DbDirectoryEntry');
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

	public function getnext()
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
					$photo = new bab_dirEntryPhoto($this->handler->arrentries['id']);
					$this->ctx->curctx->push('DirectoryFieldValue', $photo->getUrl());
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


class Func_Ovml_Container_DbDirectoryAcl extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $ctx;
	var $index;
	var $idx = 0;
	var $count = 0;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$directoryid = $ctx->curctx->getAttribute('directoryid');

		if( $directoryid !== false && $directoryid !== '' )
		{
			$type = $ctx->curctx->getAttribute('type');
			if( $type !== false && $type !== '' )
			{
				switch(mb_strtolower($type))
				{
					case 'add':
						$table = BAB_DBDIRADD_GROUPS_TBL;
						break;
					case 'modify':
						$table = BAB_DBDIRUPDATE_GROUPS_TBL;
						break;
					case 'delete':
						$table = BAB_DBDIRDEL_GROUPS_TBL;
						break;
					case 'export':
						$table = BAB_DBDIREXPORT_GROUPS_TBL;
						break;
					case 'import':
						$table = BAB_DBDIRIMPORT_GROUPS_TBL;
						break;
					case 'empty':
						$table = BAB_DBDIREMPTY_GROUPS_TBL;
						break;
					case 'bind':
						$table = BAB_DBDIRBIND_GROUPS_TBL;
						break;
					case 'unbind':
						$table = BAB_DBDIRUNBIND_GROUPS_TBL;
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

			
			$groups = bab_getGroupsAccess($table, $directoryid);
			$this->IdEntries = bab_getGroupsMembers($groups);	
			$this->count = count($this->IdEntries);
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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

