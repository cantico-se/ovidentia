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


/**
 * Query list of sessions
 */
class bab_StatSessions
{
    /**
     * Optional filter user
     */
    public $idUser;
    
    /**
     * Optional filter start date
     */
    public $startDate;
    
    /**
     * Optional filter end date
     */
    public $endDate;
    
    
    protected function getUserSessions()
    {
        global $babDB;
        
        $sessions = array();
        
        $res = $babDB->db_query('SELECT evt_session_id FROM bab_stats_events 
            WHERE evt_iduser='.$babDB->quote($this->idUser).' GROUP BY evt_session_id');
        while ($arr = $babDB->db_fetch_assoc($res)) {
            $sessions[] = $arr['evt_session_id'];
        }
        
        return $sessions;
    }
    
    
    public function getResource()
    {
        global $babDB;
        
        $where = array();
        
        if (isset($this->idUser)) {
            $where[] = 'e.evt_session_id IN('.$babDB->quote($this->getUserSessions()).')';
        }
        
        if (isset($this->startDate)) {
            $where[] = 'DATE(e.evt_time)>='.$babDB->quote($this->startDate);
        }
        
        if (isset($this->endDate)) {
            $where[] = 'DATE(e.evt_time)<='.$babDB->quote($this->endDate);
        }
        
        $query = 'SELECT
            e.evt_session_id,
            e.evt_time,
            e.evt_url,
            e.evt_iduser,
            e.evt_ip,
            e.evt_client
         FROM
            bab_stats_events e
            JOIN (SELECT evt_session_id, MAX(evt_time) evt_time FROM bab_stats_events GROUP BY evt_session_id) AS max
                ON e.evt_session_id=max.evt_session_id
                AND e.evt_time=max.evt_time 
        ';
        
        if (count($where) > 0) {
            $query .= ' WHERE '.implode(' AND ', $where);
        }
        
        $query .= ' GROUP BY evt_session_id ORDER BY e.evt_time DESC';
        return $babDB->db_query($query);
    }
    
    
    /**
     * Create links to user sessions
     * @return Widget_Frame
     */
    public function getWidget()
    {
        if (bab_statisticsAccess() == -1) {
            return null;
        }
        
        global $babDB;
        
        $W = bab_Widgets();
        $frame = $W->Frame(null, $W->VBoxLayout()->setVerticalSpacing(1, 'em'));
        
        $res = $this->getResource();
        while ($arr = $babDB->db_fetch_assoc($res)) {
            $text = sprintf(bab_translate('Last access: %s'), BAB_DateTimeUtil::relativePastDate($arr['evt_time'], true, true));
            $frame->addItem($W->Link($text, '?tg=statsessions&sess='.$arr['evt_session_id']));
        }
            
        return $frame;
    }
}
