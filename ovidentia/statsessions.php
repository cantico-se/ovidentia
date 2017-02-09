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
     * @var string
     */
    public $ip;
    
    /**
     * @var string
     */
    public $sitemap_node;
    
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
     * @return string
     */
    public function getDuration()
    {
        if (!isset($this->next)) {
            return null;
        }
        
        $seconds = (bab_mktime($this->next->time) - bab_mktime($this->time));
        
        if ($seconds > 60) {
            $minutes = (int) round($seconds /60);
            $seconds = $seconds % 60;
            
            return sprintf(bab_translate('%d minute(s), %d seconds'), $minutes, $seconds);
        }
        
        return sprintf(bab_translate('%d seconds'), $seconds);
    }
    
    
    public function getHour()
    {
        $ts = bab_mktime($this->time);
        return date('H:i', $ts);
    }
    
    /**
     * Get sitemap node
     * @return bab_siteMapItem
     */
    public function getSitemapItem()
    {
        if (empty($this->sitemap_node)) {
            return null;
        }
        
        $rootNode = bab_siteMap::getFromSite();
        $node = $rootNode->getNodeById($this->sitemap_node);
        
        if (!isset($node)) {
            return null;
        }
        
        return $node->getData();
    }
    
    
    /**
     * @return string
     */
    public function getUserName()
    {
        if ($iduser > 0) {
            return bab_getUserName($this->iduser);
        }
    
        return sprintf(bab_translate('Anonymous (Ip address: %s)'), $this->ip);
    }
}







function bab_statGetSessionHeaderWidget($res)
{
    global $babDB;
    
    // get last event and count
    $arr = $babDB->db_fetch_assoc($res);
    $event = bab_statCreateEventFromArray('evt', $arr);
    $babDB->db_data_seek($res, 0);
    $count = $babDB->db_num_rows($res);
    
    $W = bab_Widgets();
    $frame = $W->Frame();
    
    return $frame;
}



function bab_statGetEventWidget(bab_statSessionEvent $event)
{
    $W = bab_Widgets();
    
    $frame = $W->Frame(null, $W->FlowLayout()->setSpacing(1, 'em'));
    $frame->addClass('widget-bordered');
    
    // COL 1
    
    $frame->addItem($timeCell = $W->VBoxLayout());
    
    $timeCell->addClass('widget-20em');
    $timeCell->addItem($W->Label($event->getHour()));
    
    $duration = $event->getDuration();
    if (isset($duration)) {
        $timeCell->addItem($W->Label($duration)->addClass('widget-strong'));
    }
    
    // COL 2
    
    $frame->addItem($pageCell = $W->VBoxLayout()->setVerticalSpacing(.5, 'em'));
    $pageCell->addClass('widget-50em');
    
    
    if ($node = $event->getSitemapItem()) {
        $pageCell->addItem($W->Label($node->name)->addClass('widget-strong'));
    }
    
    $pageCell->addItem($W->Link($event->url, $event->url));
    
    
    return $frame;
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
    
    $res = $babDB->db_query('
        SELECT 
            e.id evt_id, 
            e.evt_time,
            e.evt_referer,
            e.evt_url,
            e.evt_ip,
            e.evt_sitemap_node,
            e.evt_iduser,
            e.evt_info, 
        
            n.id                next_id,
            n.evt_time          next_time,
            n.evt_referer       next_referer,
            n.evt_url           next_url,
            n.evt_ip            next_ip,
            n.evt_sitemap_node  next_sitemap_node,
            n.evt_iduser        next_iduser,
            n.evt_info          next_info  
        FROM 
        '.BAB_STATS_EVENTS_TBL.' e 
            LEFT JOIN '.BAB_STATS_EVENTS_TBL.' n ON n.previous = e.id 
        WHERE e.evt_session_id='.$babDB->quote($sess).' 
        ORDER BY e.evt_time DESC');
    
    $page->addItem(bab_statGetSessionHeaderWidget($res));
    
    while ($arr = $babDB->db_fetch_assoc($res)) {
        
        $event = bab_statCreateEventFromArray('evt', $arr);
        $event->next = bab_statCreateEventFromArray('next', $arr);
        
        $page->addItem(bab_statGetEventWidget($event));
    }
    
    $page->displayHtml();
}



/**
 * Paginated list of sessions, last sessions on top
 */
class bab_statSessionListCls
{
    const NB_ITEMS = 50;
    
    private $res;
    
    private $filter;
    
    public $t_user;
    public $t_time;
    public $t_view_details;
    
    public function __construct($filter)
    {
        
        $this->t_user = bab_toHtml(bab_translate('User'));
        $this->t_time = bab_toHtml(bab_translate('Last viewed'));
        $this->t_view_details = bab_toHtml(bab_translate('View details'));
        
        $this->filter = $filter;
        
        $pos = 0;
        if (isset($filter['pos'])) {
            $pos = (int) $filter['pos'];
        }
        
        global $babDB;
        
        $this->res = $babDB->db_query('SELECT 
            e.evt_session_id,
            evt_time,
            e.evt_url,
            evt_iduser,
            evt_ip,
            evt_client 
            
         FROM 
            '.BAB_STATS_EVENTS_TBL.' e 
            GROUP BY evt_session_id HAVING MAX(evt_time) ORDER BY evt_time DESC');
        
        $this->index = 0;
        $this->total = $babDB->db_num_rows($this->res);
        
        if ($pos > 0) {
            $babDB->db_data_seek($this->res, $pos);
            
            $this->previousPageUrl = bab_toHtml($this->getPageUrl($pos - self::NB_ITEMS));
        }
        
        if ($pos + self::NB_ITEMS < $this->total) {
            // next page 
            
            $this->nextPageUrl = bab_toHtml($this->getPageUrl($pos + self::NB_ITEMS));
        }
        
    }
    
    
    /**
     * @return string
     */
    protected function getPageUrl($pos)
    {
        $url = new bab_url();
        $url->filter = $this->filter;
        $url->filter['pos'] = $pos;
        
        return $url->toString();
    }
    
    /**
     * @return string
     */
    protected function getUserHtml($iduser, $ip)
    {
        if ($iduser > 0) {
            return bab_toHtml(bab_getUserName($iduser));
        }
        
        return bab_toHtml(sprintf(bab_translate('Anonymous (Ip address: %s)'), $ip));
    }
    
    
    public function getnext()
    {
        if ($this->index >= self::NB_ITEMS) {
            return false;
        }
        
        global $babDB;
        
        if ($arr = $babDB->db_fetch_assoc($this->res)) {
            
            $url = bab_url::get_request('tg', 'idx');
            $url->sess = $arr['evt_session_id'];
            
            $this->detailurl = bab_toHtml($url->toString());
            $this->name = $this->getUserHtml($arr['evt_iduser'], $arr['evt_ip'], $arr['evt_client']);
            $this->time = bab_toHtml(bab_shortDate(bab_mktime($arr['evt_time'])));
            $this->index++;
            return true;
        }
        
        return false;
    }
    
    
    public function getHtml()
    {
        return bab_printTemplate($this, 'statsessions.html', 'list');
    }
}


function bab_statSessionList()
{
    $W = bab_Widgets();
    $page = $W->BabPage();
    
    $filter = bab_rp('filter');
    
    $list = new bab_statSessionListCls($filter);
    
    $page->addItem($W->Html($list->getHtml()));
    
    $page->displayHtml();
}



function bab_statSessions()
{
    $sess = bab_rp('sess', null);
    
    if (isset($sess)) {
        return bab_statSessionDisplay($sess);
    }
    
    bab_statSessionList();
}
