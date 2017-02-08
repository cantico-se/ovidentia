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


class bab_statSessionEvent
{
    /**
     * 
     * @var int
     */
    public $id;
    
    /**
     * 
     * @var string
     */
    public $time;
    
    /**
     * 
     * @var string
     */
    public $referer;
    
    /**
     * 
     * @var string
     */
    public $url;
    
    /**
     * 
     * @var int
     */
    public $iduser;
    
    /**
     * Serialized informations
     * @var string
     */
    public $info;
    
    /**
     * the next event or null
     * @var bab_statSessionEvent
     */
    public $next;
    
    
    /**
     * Get duration on page
     * @return int
     */
    public function getDuration()
    {
        if (!isset($this->next)) {
            return null;
        }
        
        return (bab_mktime($this->next->time) - bab_mktime($this->time));
    }
}



function bab_statGetEventWidget(Array $arr)
{
    
}



function bab_statCreateEventFromArray($prefix, Array $arr)
{
    $event = new bab_statSessionEvent();
    foreach ($arr as $prop => $value) {
        if ($prefix = mb_substr($prop, 0, mb_strlen($prefix))) {
            $objectProp = mb_substr($prop, mb_strlen($prefix) + 1);
            $event->$objectProp = $value;
        }
    }
    
    if (!isset($event->id)) {
        return null;
    }
    
    $event->id = (int) $event->id;
    $event->iduser = (int) $event->iduser;
    
    return $event;
}




function bab_statSessionDisplay($sess)
{
    global $babDB;
    $W = bab_Widgets();
    $page = $W->BabPage();
    
    $res = $babDB->db_query('SELECT 
            e.id evt_id, 
            e.evt_time,
            e.evt_referer,
            e.evt_url,
            e.evt_iduser,
            e.evt_info, 
        
            n.id            next_id,
            n.evt_time      next_time,
            n.evt_referer   next_referer,
            n.evt_url       next_url,
            n.evt_iduser    next_iduser,
            n.evt_info      next_info  
        FROM 
        '.BAB_STATS_EVENTS_TBL.' e 
            LEFT JOIN '.BAB_STATS_EVENTS_TBL.' n ON n.previous = e.id 
        WHERE evt_session_id='.$babDB->quote($sess).' 
        ORDER BY evt_time');
    
    while ($arr = $babDB->db_fetch_assoc($res)) {
        
        $event = bab_statCreateEventFromArray('evt', $arr);
        $event->next = bab_statCreateEventFromArray('next', $arr);
        
        $page->addItem(bab_statGetEventWidget($event));
    }
    
    $page->displayHtml();
}


function bab_statSessionList()
{
    
}



function bab_statSessions()
{
    $sess = bab_rp('sess', null);
    
    if (isset($sess)) {
        return bab_statSessionDisplay($sess);
    }
    
    bab_statSessionList();
}
