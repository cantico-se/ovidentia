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

class bab_dbtree
{
	var $iduser;
	var $userinfo;
	var $table;
	var $where;
	var $rootid;
	var $subtree;

	function bab_dbtree($table, $id, $userinfo = "")
	{
		$this->table = $table;
		$this->iduser = $id;
		$this->userinfo = $userinfo;
		$this->firstnode = 1;
		$this->firstnode_parent = 0;
		$this->subtree = '';

		$this->where = "id_user='".$id."'";
		if( !empty($userinfo))
			$this->where .= " and info_user='".$userinfo."'";
	}

	function getWhereClause($table='', $subtree = true)
	{
		if( empty($table))
		{
			if ($subtree) {
				return $this->where.$this->subtree;
			} else {
				return $this->where;
				}
		}
		else
		{
			$where = '';
			if (!empty($this->iduser))
				{
				$where = $table.".id_user='".$this->iduser."'";
				if( !empty($this->userinfo))
					$where .= " and ".$table.".info_user='".$this->userinfo."'";
				}

			return $where;
		}
	}

	function setSubTree($lf, $lr)
	{
		$this->subtree = " AND lf>='".$lf."' AND lr<='".$lr."'";
	}


	function getNodeInfo($id)
	{
		global $babDB;
		$where = $this->getWhereClause('', false);
		if (!empty($where))
			{
			$where .= " and";
			}
		$res = $babDB->db_query("SELECT * from ".$this->table." where ".$where." id='".$id."'" );
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			return $babDB->db_fetch_array($res);
		}
		else
		{
			return false;
		}
	}

	function getRootInfo()
	{
		global $babDB;
		$where = $this->getWhereClause();
		if (!empty($where ))
			$where = 'where '.$where ;
		$res = $babDB->db_query("SELECT * from ".$this->table." ".$where." order by lf asc limit 0,1" );
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			return $babDB->db_fetch_array($res);
		}
		else
		{
			return false;
		}
	}

	function update($lr, $offset=1, $positive=true)
	{
		global $babDB;

		$offset *= 2; 

		//$where = $this->getWhereClause();
		$where = $this->getWhereClause('', false);

		if (!empty($where)) {
			$where = ' and '.$where;
			}

		if( $positive )
		{
			$babDB->db_query("UPDATE ".$this->table." set lr = lr+".$offset." where lr > '".$lr."'".$where);
		}
		else
		{
			$babDB->db_query("UPDATE ".$this->table." set lr = lr-".$offset." where lr > '".$lr."'".$where);
		}
		
		if( $positive )
		{
			$babDB->db_query("UPDATE ".$this->table." set lf = lf+".$offset." where lf > '".$lr."'".$where);
		}
		else
		{
			$babDB->db_query("UPDATE ".$this->table." set lf = lf-".$offset." where lf > '".$lr."'".$where);
		}
	}

	function getPreviousSibling($id)
	{
		global $babDB;
		$where = $this->getWhereClause('p1');
		if (!empty($where))
			$where .= ' and ';
        $res = $babDB->db_query("SELECT p1.* FROM ".$this->table." p1 ,".$this->table." p2 WHERE ".$where." p2.lf=p1.lr+1 AND p2.id_parent=p1.id_parent AND p2.id='".$id."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			return $babDB->db_fetch_array($res);
		}
		else
		{
			return false;
		}
	}

	function getNextSibling($id)
	{
		global $babDB;
		$where = $this->getWhereClause('p1');
		if (!empty($where))
			$where .= ' and ';
		$res = $babDB->db_query("SELECT p1.* FROM ".$this->table." p1 ,".$this->table." p2 WHERE ".$where." p2.lr=p1.lf-1 AND p2.id_parent=p1.id_parent AND p2.id='".$id."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			return $babDB->db_fetch_array($res);
		}
		else
		{
			return false;
		}
	}


	function getChilds($id, $all = 0)
	{
		global $babDB;
		$arr = array();
		if( !$all )
		{
			$where = $this->getWhereClause('p1');
			if (!empty($where))
				$where .= ' and ';
				
			$query = "SELECT p2.* FROM ".$this->table." p1 ,".$this->table." p2 WHERE ".$where." p1.id=p2.id_parent AND p1.id=".$babDB->quote($id)." order by p2.lf asc";
			$res = $babDB->db_query($query);
			
			
		}
		else
		{
			$nodeinfo = $this->getNodeInfo($id);
			if( !$nodeinfo )
			{
				return false;
			}
			if( $nodeinfo['lr'] == ($nodeinfo['lf'] + 1) )
			{
				return $arr;
			}
			$where = $this->getWhereClause();
			if (!empty($where))
				{
				$where .= " and";
				}
			$res = $babDB->db_query("SELECT * FROM ".$this->table." WHERE ".$where." lf > '".$nodeinfo['lf']."' and lr < '".$nodeinfo['lr']."' order by lf asc");
		}

		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			while($row = $babDB->db_fetch_array($res))
			{
				$arr[] = $row;
			}
			return $arr;
		}
		else
		{
			return false;
		}
	}

	function getFirstChild($id)
	{
		global $babDB;
		$arr = $this->getChilds($id);
		if( $arr === false )
		{
			return false;
		}
		return $arr[0];
	}
	
	function getLastChild($id)
	{
		global $babDB;
		$arr = $this->getChilds($id);
		if( $arr === false )
		{
			return false;
		}
		return $arr[count($arr) -1];
	}
	
	function add($parentId = 0, $previousId=0, $bprev=true, $id_node=false)
	{
		global $babDB;
		$rowdata = array();
		$lr = 0;

		if( $parentId || $previousId )
		{
			if( $previousId )
			{
				$previnfo = $this->getNodeInfo($previousId);
				if( !$previnfo )
				{
					return false;
				}

				$rowdata['id_parent'] = $previnfo['id_parent'];
				if( $rowdata['id_parent'] == $this->firstnode_parent )
				{
					$rowdata['id_parent'] = $previousId;					
					$lastchild = $this->getLastChild($previousId);
					if( $lastchild )
					{
						$lr = $lastchild['lr'];
					}
					else
					{
						$lr = $previnfo['lf'];
					}
				}
				else
				{
					if( $bprev )
					{
						$lr = $previnfo['lr'];
					}
					else 
					{
						$idprev = $this->getPreviousSibling($previousId);
						if( $idprev )
							{
							$previousId = $idprev['id'];
							$lr = $previnfo['lr'];
							}
						else
							{
							$parentinfo = $this->getNodeInfo($rowdata['id_parent']);
							$lr = $parentinfo['lf'];
							}
					}
				}
			}
			else 
			{
				$parentinfo = $this->getNodeInfo($parentId);
				if( !$parentinfo )
				{
					return false;
				}
				$rowdata['id_parent'] = $parentId;
				$lastchild = $this->getLastChild($parentId);
				if( $lastchild )
				{
					$lr = $lastchild['lr'];
				}
				else
				{
					$lr = $parentinfo['lf'];
				}
			}
		}
		else
		{
			$rootinfo = $this->getRootInfo();
			if( !$rootinfo )
			{
				$rowdata['id_parent'] = 0;
				$lr = 0;
			}
			else
			{
			$rowdata['id_parent'] = $rootinfo['id'];
			$lastchild = $this->getLastChild($rootinfo['id']);
			if( $lastchild )
				{
				$lr = $lastchild['lr'];
				}
			else
				{
				$lr = $rootinfo['lf'];
				}
			}
		}

		$this->update($lr);

		$rowdata['lf'] = $lr + 1;
		$rowdata['lr'] = $lr + 2;

		$id_user = !empty($this->iduser) ? array(', id_user', ",'".$this->iduser."'") : array('','');
		$userinfo = !empty($this->userinfo) ? array(', info_user', ",'".$this->userinfo."'") : array('','');

		if (false === $id_node)
			{
			$res = $babDB->db_query("INSERT INTO ".$this->table." (lf, lr, id_parent".$id_user[0].$userinfo[0].") values ('".$rowdata['lf']."','".$rowdata['lr']."','".$rowdata['id_parent']."'".$id_user[1].$userinfo[1].")");

			if( $res )
				{
					$rowdata['id'] = $babDB->db_insert_id();
					return $rowdata['id'];
				}
			}
		else
			{
			$res = $babDB->db_query("INSERT INTO ".$this->table." (id, lf, lr, id_parent".$id_user[0].$userinfo[0].") values ('".$id_node."', '".$rowdata['lf']."','".$rowdata['lr']."','".$rowdata['id_parent']."'".$id_user[1].$userinfo[1].")");

			if( $res )
				{
					$rowdata['id'] = $id_node;
					return $rowdata['id'];
				}
			}


		return 0;
	}

	function remove($id)
	{
		global $babDB;

		$nodeinfo = $this->getNodeInfo($id);
		if( !$nodeinfo )
			return false;

		if( $nodeinfo['id_parent'] == 0 &&  ($nodeinfo['lr'] - $nodeinfo['lf']) > 1 )
			return false;

		$lf = $nodeinfo['lf'];
		$lr = $nodeinfo['lr'];

		$where = $this->getWhereClause();
		if (!empty($where))
			$where = ' AND '.$where;
		
		if(  $lr - $lf > 1 )
		{
			$babDB->db_query("UPDATE ".$this->table." set lr = lr-1, lf=lf-1 where lf > '".$lf."' and lr < '".$lr."'".$where);

			$babDB->db_query("UPDATE ".$this->table." set id_parent='".$nodeinfo['id_parent']."' where id_parent='".$id."'".$where);

		}
		$this->update(	$lr, 1, false);
		$babDB->db_query("DELETE from ".$this->table." where id='".$id."'".$where);

		return true;
	}

	/* remove id and all childs and childs of childs ... */
	function removeTree($id)
	{
		global $babDB;

		$nodeinfo = $this->getNodeInfo($id);
		if( !$nodeinfo )
			return false;

		$lf = $nodeinfo['lf'];
		$lr = $nodeinfo['lr'];
		
		if(  $lr - $lf > 1 )
		{
			$babDB->db_query("DELETE from ".$this->table." where lf between ".$lf." and ".$lr." and ".$this->getWhereClause());
			$this->update($lr, ($lr-$lf + 1)/2, false);
		}
		else
		{
			return $this->remove($id);
		}

		return true;
	}

	function move($id, $parentId, $previousId =0, $bprev=true )
	{
		global $babDB;

		$nodeinfo = $this->getNodeInfo($id);
		if( !$nodeinfo )
			return false;

		if( $nodeinfo['id_parent'] == $this->firstnode_parent )
			return false;

		if( $parentId || $previousId )
		{
			if( $previousId )
			{
				$previousinfo = $this->getNodeInfo($previousId);
				if( !$previousinfo || $previousinfo['id_parent'] == $this->firstnode_parent )
					return false;

			}
			else
			{
				$parentinfo = $this->getNodeInfo($parentId);
				if( !$parentinfo || $nodeinfo['id_parent'] == $parentId)
					return false;
			}
		}
		else
		{
			return false;
		}


		$lf = $nodeinfo['lf'];
		$lr = $nodeinfo['lr'];
		
		if(  $lr - $lf > 1 )
		{
			$babDB->db_query("UPDATE ".$this->table." set lr = lr-1, lf=lf-1 where lf > '".$lf."' and lr < '".$lr."' and ".$this->getWhereClause());

			$babDB->db_query("UPDATE ".$this->table." set id_parent='".$nodeinfo['id_parent']."' where ".$this->getWhereClause()." and id_parent='".$id."'");

		}
		$this->update($lr, 1, false);

		//delete
		if( $previousId )
		{
			$previousinfo = $this->getNodeInfo($previousId);
			$parentId = $previousinfo['id_parent'];
			if( !$bprev )
			{
				$idprev = $this->getPreviousSibling($previousId);
				if( $idprev )
					{
					$lr = $idprev['lr'];
					}
				else
					{
					$parentinfo = $this->getNodeInfo($parentId);
					$lr = $parentinfo['lf'];
					}

			}
			else
			{
				$lr = $previousinfo['lr'];
			}
		}
		else
		{
			$parentinfo = $this->getNodeInfo($parentId);
			$lastchild = $this->getLastChild($parentId);
			if( $lastchild )
				{
				$lr = $lastchild['lr'];
				}
			else
				{
				$lr = $parentinfo['lf'];
				}

		}
		$this->update($lr);

		$res = $babDB->db_query("UPDATE ".$this->table." set lf='".($lr + 1)."', lr='".($lr + 2)."', id_parent='". $parentId."' where id='".$id."'");
		return true;
	}


	function moveTree($id, $parentId, $previousId =0, $bprev=true )
	{
		global $babDB;

		$nodeinfo = $this->getNodeInfo($id);
		if( !$nodeinfo )
			return false;

		if( $nodeinfo['id_parent'] == 0 )
			return false;

		if( $parentId || $previousId )
		{
			if( $previousId )
			{
				if( !$bprev )
				{
					$idprev = $this->getPreviousSibling($previousId);
					if( $idprev )
						{
						$previousId = $idprev['id'];
						}

				}
				if( $id == $previousId )
					return true;

				$previnfo = $this->getNodeInfo($previousId);
				if( !$previnfo || $previnfo['id_parent'] == 0)
					return false;

				$parentId = $previnfo['id_parent'];
				$lr = $previnfo['lr'];

				$parentinfo = $this->getNodeInfo($parentId);
				if( !$parentinfo )
					return false;
			}
			else 
			{
				if( $id == $parentId )
					return true;

				$parentinfo = $this->getNodeInfo($parentId);
				if( !$parentinfo || $nodeinfo['id_parent'] == $parentId)
					return false;

				$lastchild = $this->getLastChild($parentId);
				if( $lastchild )
				{
					$lr = $lastchild['lr'];
				}
				else
				{
					$lr = $parentinfo['lf'];
				}
			}
		}
		else
		{
			return false;
		}

		if( $parentinfo['lf'] > $nodeinfo['lf'] && $parentinfo['lr'] < $nodeinfo['lr'] )
		{
			return false;
		}

        $nbchilds = ($nodeinfo['lr'] - $nodeinfo['lf']+1)/2;
		$this->update($lr, $nbchilds);

		$nodeinfo = $this->getNodeInfo($id);

		if( $previousId)
		{
			$previnfo = $this->getNodeInfo($previousId);
			$offset = $previnfo['lr'];
		}
		else
		{
			$lastchild = $this->getLastChild($parentId);
			if( $lastchild )
				{
				$offset = $lastchild['lr'];
				}
			else
				{
				$parentinfo = $this->getNodeInfo($parentId);
				$offset = $parentinfo['lf'];
				}
		}

		$offset = $offset - $nodeinfo['lf'];
        $offset++;

		$lf = $nodeinfo['lf'];
		$lr = $nodeinfo['lr'];

		$where = $this->getWhereClause('', false);
		//$where = $this->getWhereClause();

		$query = "UPDATE ".$this->table." set lr = lr+$offset, lf=lf+$offset where lf > '".($lf-1)."' and lr < '".($lr+1)."' and ".$where;
		//echo $query."\n";
		$babDB->db_query($query);

		$offset = $lr - $lf + 1;

		$query = "UPDATE ".$this->table." set lr = lr-$offset, lf=lf-$offset where lf > '".$lf."' and ".$where;
		//echo $query."\n";
		$babDB->db_query($query);

		$query = "UPDATE ".$this->table." set lr = lr-$offset where lf < '".$lf."' and lr > '".$lr."' and ".$where;
		//echo $query."\n";
		$babDB->db_query($query);

		$query = "UPDATE ".$this->table." set id_parent ='".$parentId."' where ".$where." and id='".$id."'";
		//echo $query."\n";
		$babDB->db_query($query);
		return true;
	}

}


class bab_arraytree
{
	var $nodes = array();
	var $rootid;
	var $iduser;
	var $userinfo;
	var $table;
	var $where;

	function bab_arraytree($table, $id, $userinfo = "", $rootid = 0)
	{
		global $babDB;
		$this->table = $table;
		$this->iduser = $id;

		if( $id )
		{
		$this->where = "id_user='".$id."'";
		}
		else
		{
		$this->where = '';
		}
		if( !empty($userinfo))
		{
			if( $this->where )
				$this->where .= " and";
			
			$this->where .= " info_user='".$userinfo."'";
		}

		if( $rootid )
		{
		if( $this->where )
			$res = $babDB->db_query("select * from ".$this->table." where ".$this->where." and id='".$rootid."'");
		else
			$res = $babDB->db_query("select * from ".$this->table." where id='".$rootid."'");
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			if( $this->where )
				$req = "select * from ".$this->table." where ".$this->where." and lf between ".$arr['lf']." and ".$arr['lr']." order by lf asc";
			else
				$req = "select * from ".$this->table." where lf between ".$arr['lf']." and ".$arr['lr']." order by lf asc";
			}
		else
			{
			if( $this->where )
				$req = "select * from ".$this->table." where ".$this->where." order by lf asc";
			else
				$req = "select * from ".$this->table." order by lf asc";
			}
		}
		else
		{
			if( $this->where )
				$req = "select * from ".$this->table." where ".$this->where." order by lf asc";
			else
				$req = "select * from ".$this->table." order by lf asc";
		}
		$res = $babDB->db_query($req);
		$parents = array();
		$k = 0;
		while( $arr = $babDB->db_fetch_assoc($res))
			{
			if( $k == 0)
				{
				$this->rootid = $arr['id'];
				}
			$k++;
			$row['id']= $arr['id'];
			$row['lf']= $arr['lf'];
			$row['lr']= $arr['lr'];
			$row['id_parent']= $arr['id_parent'];
			$row['lastChild']= 0;
			$row['previousSibling'] = 0;
			$row['nextSibling'] = 0;
			$row['firstChild']= 0;
			$row['lastChild']= 0;
			$row['nodeinfo']= $arr;
			if( count($parents) > 0 )
				{
				while(  count($parents)> 0 && ($arr['lr'] > $this->nodes[$parents[count($parents)-1]]['lr']))
					{
					array_pop($parents);
					}

				}
			$row['level'] = count($parents);
			$this->nodes[$arr['id']] = $row;
			if( count($parents) > 0 )
				{
				$this->appendChild($parents[count($parents)-1], $arr['id']);
				}
			$parents[] = $arr['id'];
			}
	
	}

	function getRootId()
	{
		global $babDB;
		return $this->rootid;
	}
	
	function getNodeInfo($id)
	{
		global $babDB;
		if( isset($this->nodes[$id]))
			return $this->nodes[$id]['nodeinfo'];
		else
			return false;
	}
	
	function appendChild($parentid, $child)
	{
		if( isset( $this->nodes[$parentid]) && isset( $this->nodes[$child]))
		{
			$this->nodes[$child]['previousSibling'] = $this->nodes[$parentid]['lastChild'];
			$this->nodes[$child]['nextSibling'] = 0;
			if( $this->nodes[$parentid]['lastChild'] != 0 )
			{
				$this->nodes[$this->nodes[$parentid]['lastChild']]['nextSibling'] = $this->nodes[$child]['id'];
			}
			else
			{
				$this->nodes[$parentid]['firstChild'] = $child;
			}

			$this->nodes[$parentid]['lastChild'] = $child;
			$this->nodes[$child]['firstChild']= 0;
			$this->nodes[$child]['lastChild']= 0;
			$this->nodes[$child]['level']= $this->nodes[$parentid]['level'] + 1;
			return true;
		}
		else
		{
			return false;
		}
	}


	function hasChildren($id)
	{
		if( isset($this->nodes[$id]) && ($this->nodes[$id]['lr'] - $this->nodes[$id]['lf']) > 1 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function getParentId($id)
	{
		if( isset($this->nodes[$id]) )
		{
			return $this->nodes[$id]['id_parent'];
		}
		else
		{
			return 0;
		}
	}

	function getLeftValue($id)
	{
		return $this->nodes[$id]['lf'];
	}

	function getRightValue($id)
	{
		return $this->nodes[$id]['lr'];
	}

	function getFirstChild($id)
	{
		return $this->nodes[$id]['firstChild'];
	}

	function getLastChild($id)
	{
		return $this->nodes[$id]['lastChild'];
	}

	function getNextSibling($id)
	{
		return $this->nodes[$id]['nextSibling'];
	}

	function getPreviousSibling($id)
	{
		return $this->nodes[$id]['previousSibling'];
	}

	function getChilds($id, $all = true)
	{
		if( !isset($this->nodes[$id] ))
		{
			return false;
		}

		$lf = $this->nodes[$id]['lf'];
		$lr = $this->nodes[$id]['lr'];


		reset($this->nodes);
		$arr = array();
		while( $row=each($this->nodes) ) 
			{
			if( $row[1]['lf'] > $lf && $row[1]['lf'] < $lr )
				{
				if( $all )
					{
					$arr[] = $row[1]['id'];
					}
				elseif( $id == $row[1]['id_parent'] )
					{
					$arr[] = $row[1]['id'];
					}
				}
			}
		return $arr;
	}

	function removeNode($id)
	{
		if( !isset($this->nodes[$id] ))
		{
			return false;
		}

		if( $this->nodes[$id]['id_parent'] == 0 &&  ($this->nodes[$id]['lr'] - $this->nodes[$id]['lf']) > 1 )
			return false;

		$lf = $this->nodes[$id]['lf'];
		$lr = $this->nodes[$id]['lr'];

		if(  $lr - $lf > 1 )
		{
			reset($this->nodes);
			while( $row=each($this->nodes) ) 
				{
				if( $row[1]['lf'] > $lf && $row[1]['lr'] < $lr )
					{
					$this->nodes[$row[1]['id']]['lf'] -= 1;
					$this->nodes[$row[1]['id']]['lr'] -= 1;
					$this->nodes[$row[1]['id']]['level'] -= 1;

					if( $this->nodes[$row[1]['id']]['id_parent'] == $id )
						{
						$this->nodes[$row[1]['id']]['id_parent'] = $this->nodes[$id]['id_parent'];
						}
					}
				}
		}

		if( $this->nodes[$id]['firstChild'] )
			{
			$this->nodes[$this->nodes[$id]['firstChild']]['previousSibling'] = $this->nodes[$id]['previousSibling'];
			if( $this->nodes[$id]['previousSibling'] )
				{
				$this->nodes[$this->nodes[$id]['previousSibling']]['nextSibling'] = $this->nodes[$id]['firstChild'];
				}
			else
				{
				$this->nodes[$this->nodes[$id]['id_parent']]['firstChild'] = $this->nodes[$id]['firstChild'];
				}

			if( $this->nodes[$id]['lastChild'] )
				{
				$this->nodes[$this->nodes[$id]['lastChild']]['nextSibling'] = $this->nodes[$id]['nextSibling'];
				if( $this->nodes[$id]['nextSibling'] )
					{
					$this->nodes[$this->nodes[$id]['nextSibling']]['previousSibling'] = $this->nodes[$id]['lastChild'];
					}
				else
					{
					$this->nodes[$this->nodes[$id]['id_parent']]['lastChild'] = $this->nodes[$id]['lastChild'];
					}
				}

			}
		else
			{
			if( $this->nodes[$id]['previousSibling'] )
				{
				$this->nodes[$this->nodes[$id]['previousSibling']]['nextSibling'] = $this->nodes[$id]['nextSibling'];
				}
			else
				{
				$this->nodes[$this->nodes[$id]['id_parent']]['firstChild'] = $this->nodes[$id]['nextSibling'];
				}
			
			if( $this->nodes[$id]['nextSibling'] )
				{
				$this->nodes[$this->nodes[$id]['nextSibling']]['previousSibling'] = $this->nodes[$id]['previousSibling'];
				}
			else
				{
				$this->nodes[$this->nodes[$id]['id_parent']]['firstChild'] = $this->nodes[$id]['previousSibling'];
				}

			}

		reset($this->nodes);
		while( $row=each($this->nodes) ) 
			{
			if( $row[1]['lr'] > $lr  )
				{
				$this->nodes[$row[1]['id']]['lr'] -= 2;
				}
			if( $row[1]['lf'] > $lr  )
				{
				$this->nodes[$row[1]['id']]['lf'] -= 2;
				}
			}

		unset($this->nodes[$id]);
	}

	function removeChilds($id)
	{
		if( !isset($this->nodes[$id] ))
		{
			return false;
		}

		$lf = $this->nodes[$id]['lf'];
		$lr = $this->nodes[$id]['lr'];

		if( $this->hasChildren($id))
		{
			reset($this->nodes);
			$arr = array();
			while( $row=each($this->nodes) ) 
				{
				if( $row[1]['lf'] > $lf && $row[1]['lf'] < $lr )
					{
					$arr[] = $row[1]['id'];
					}
				}
			for( $i = 0; $i < count($arr); $i++)
			{
				unset($this->nodes[$arr[$i]]);
			}

			$offset = $lr-$lf - 1;

			reset($this->nodes);
			while( $arr=each($this->nodes) ) 
			{
				if( $arr[1]['lr'] > $lf )
					$this->nodes[$arr[1]['id']]['lr'] = $arr[1]['lr'] - $offset;
			}
			
			reset($this->nodes);
			while( $arr=each($this->nodes) ) 
			{
				if( $arr[1]['lf'] > $lf )
				{
					$this->nodes[$arr[1]['id']]['lf'] = $arr[1]['lf'] - $offset;
				}
			}
			$this->nodes[$id]['firstChild'] = 0;
			$this->nodes[$id]['lastChild'] = 0;
		}

	}

	function removeTree($id)
	{
		if( !isset($this->nodes[$id] ))
		{
			return false;
		}

		$this->removeChilds($id);

		if( $this->nodes[$id]['id_parent'] != 0 )
		{
			$this->removeNode($id);
		}

	}
}
?>