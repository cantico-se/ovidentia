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


class Func_Ovml_Container_Calendars extends Func_Ovml_Container
{
    public $res;

    public $Entries = array();

    public $index;

    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $type = $ctx->curctx->getAttribute('type');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');


        switch (bab_getICalendars()->defaultview) {
            case BAB_CAL_VIEW_DAY:
                $this->view = 'calday';
                break;
            case BAB_CAL_VIEW_WEEK:
                $this->view = 'calweek';
                break;
            default:
                $this->view = 'calmonth';
                break;
        }

        $calendars = bab_getICalendars()->getCalendars();
        $typename = mb_strtolower($type);

        switch ($typename) {
            case 'user':
                $class = 'bab_PersonalCalendar';
                break;
            case 'group':
                $class = 'bab_PublicCalendar';
                break;
            case 'resource':
                $class = 'bab_ResourceCalendar';
                break;
            default:
                $class = 'bab_EventCalendar';
                break;
        }

        $calendarid = $ctx->curctx->getAttribute('calendarid');
        if ($calendarid !== false && $calendarid !== '') {
            $calendarid = array_flip(explode(',', $calendarid));
        } else {
            $calendarid = null;
        }

        foreach ($calendars as $calendar) {
            if (! ($calendar instanceof $class)) {
                continue;
            }

            if (isset($calendarid) && ! isset($calendarid[$calendar->getUid()])) {
                continue;
            }

            $dg = $calendar->getDgOwner();

            if (0 != $delegationid && isset($dg) && $delegationid != $dg) {
                continue;
            }

            $this->Entries[] = $calendar;
        }

        bab_Sort::sortObjects($this->Entries, 'getName'); // sort by name

        $this->count = count($this->Entries);
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $calendar = current($this->Entries);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CalendarId', $calendar->getUid());
            $this->ctx->curctx->push('CalendarName', $calendar->getName());
            $this->ctx->curctx->push('CalendarDescription', $calendar->getDescription());
            $this->ctx->curctx->push('CalendarOwnerId', $calendar->getIdUser());

            switch ($calendar->getReferenceType()) {
                case 'personal':
                    $this->ctx->curctx->push('CalendarType', BAB_CAL_USER_TYPE);
                    break;

                case 'public':
                    $this->ctx->curctx->push('CalendarType', BAB_CAL_PUB_TYPE);
                    break;

                case 'resource':
                    $this->ctx->curctx->push('CalendarType', BAB_CAL_RES_TYPE);
                    break;

                default:
                    $this->ctx->curctx->push('CalendarType', 0);
                    break;
            }

            $this->ctx->curctx->push('CalendarUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=" . $this->view . "&calid=" . $calendar->getUrlIdentifier());
            $this->idx ++;
            $this->index = $this->idx;
            next($this->Entries);
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_CalendarCategories extends Func_Ovml_Container
{
    public $res;

    public $IdEntries = array();

    public $index;

    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $req = "select * from " . BAB_CAL_CATEGORIES_TBL . " order by name asc";

        $this->res = $babDB->db_query($req);
        $this->count = $babDB->db_num_rows($this->res);
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CalendarCategoryId', $arr['id']);
            $this->ctx->curctx->push('CalendarCategoryName', $arr['name']);
            $this->ctx->curctx->push('CalendarCategoryDescription', $arr['description']);
            $this->ctx->curctx->push('CalendarCategoryColor', $arr['bgcolor']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



/**
 * Return a list of calendar events
 *
 * calendarid 			: coma separated calendars id
 * delegationid			: filter the list of calendars by delegation
 * filter 				: filter by delegation YES | NO, if filter=NO calendars without access rights can be used
 * date					: ISO date or ISO datetime, default is current date
 * limit				: x days before, y days after the date "x,y"
 * holiday 				: return vacation events YES | NO (default YES)
 * private 				: return non accessibles private events YES | NO (default YES)
 * awaiting_approval 	: return non accessibles awaiting approval events YES | NO (default NO)
 * maxevents		 	: max number of events display (default 0) (0 = unlimited)
 *
 * <OCCalendarEvents calendarid="" delegationid="" date="NOW()" limit="" filter="YES" holiday="YES" private="YES" awaiting_approval="NO" maxevents="0">
 *
 * 	<OVEventId>
 * 	<OVEventTitle>
 * 	<OVEventDescription>
 * 	<OVEventLocation>
 * 	<OVEventBeginDate>
 * 	<OVEventEndDate>
 * 	<OVEventCategoryId>
 * 	<OVEventCategoryColor>	category color
 * 	<OVEventColor>			event color or category color if exists
 * 	<OVEventUrl>
 * 	<OVEventCalendarUrl>
 * 	<OVEventCategoriesPopupUrl>
 * 	<OVEventCategoryName>
 * 	<OVEventOwner>
 * 	<OVEventUpdateDate>
 * 	<OVEventUpdateAuthor>
 * 	<OVEventAuthor>
 *
 * </OCCalendarEvents>
 *
 */
class Func_Ovml_Container_CalendarEvents extends Func_Ovml_Container
{

    public $res;

    public $IdEntries = array();

    public $index;

    public $count;

    public $maxEvent;

    public $cal_groups = 1;

    public $cal_resources = 1;

    public $cal_users = 1;

    public $cal_default_users = 1;

    // if empty calendarid, get all accessibles user calendars

    /**
     * @var bool
     */
    private $private = null;

    /**
     * @var bool
     */
    private $awaiting_approval = null;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);

        $calendarid = $ctx->curctx->getAttribute('calendarid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');
        $this->maxEvent = (int) $ctx->curctx->getAttribute('maxevents');
        $filter = mb_strtoupper($ctx->curctx->getAttribute('filter')) !== "NO";
        $holiday = mb_strtoupper($ctx->curctx->getAttribute('holiday')) !== "NO";
        $this->private = mb_strtoupper($ctx->curctx->getAttribute('private')) === "YES" || ! $ctx->curctx->getAttribute('private');
        $this->awaiting_approval = mb_strtoupper($ctx->curctx->getAttribute('awaiting_approval')) === "YES";

        switch (bab_getICalendars()->defaultview) {
            case BAB_CAL_VIEW_DAY:
                $this->view = 'calday';
                break;
            case BAB_CAL_VIEW_WEEK:
                $this->view = 'calweek';
                break;
            default:
                $this->view = 'calmonth';
                break;
        }

        include_once $GLOBALS['babInstallPath'] . "utilit/workinghoursincl.php";
        include_once $GLOBALS['babInstallPath'] . "utilit/dateTime.php";

        $startdate = $ctx->curctx->getAttribute('date');
        if ($startdate === false || $startdate === '') {
            $startdate = BAB_DateTime::now();
        } else {
            $startdate = BAB_DateTime::fromIsoDateTime($startdate);
        }

        $limit = $ctx->curctx->getAttribute('limit');
        $lf = $lr = 0;

        if ($limit !== false && $limit !== '') {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $lf = empty($limit[0]) ? 0 : (int) $limit[0];
                $lr = empty($limit[1]) ? 0 : (int) $limit[1];
            } elseif (count($limit) == 1) {
                $lf = empty($limit[0]) ? 0 : (int) $limit[0];
            }
        }


        $enddate = $startdate->cloneDate();
        $startdate->add((- 1 * $lf), BAB_DATETIME_DAY);
        $enddate->add($lr, BAB_DATETIME_DAY);


        $this->whObj = new bab_UserPeriods($startdate, $enddate);

        $backend = bab_functionality::get('CalendarBackend/Ovi');
        /*@var $backend Func_CalendarBackend_Ovi */
        $factory = $backend->Criteria();
        /*@var $factory bab_PeriodCriteriaFactory */

        if ($filter) {
            $calendars = $this->getUserCalendars($calendarid, $delegationid);
        } else {
            $calendars = $this->getCalendars($calendarid);
        }

        $criteria = $factory->Calendar($calendars);

        $categoryid = $ctx->curctx->getAttribute('categoryid');
        if ($categoryid !== false && $categoryid !== '') {
            $catnames = array();
            $arr = explode(",", $categoryid);
            foreach ($arr as $categoryid) {
                $cat = bab_getCalendarCategory($categoryid);
                $catnames[] = $cat['name'];
            }

            $criteria = $criteria->_AND_($factory->Property('CATEGORIES', $catnames));
        }

        $backend->includePeriodCollection();
        $collections = array(
            'bab_CalendarEventCollection'
        );

        if ($holiday) {
            $collections[] = 'bab_VacationPeriodCollection';
        }

        $criteria = $criteria->_AND_($factory->Collection($collections));

        $this->whObj->createPeriods($criteria);
        $this->whObj->orderBoundaries();

        $this->events = $this->whObj->getEventsBetween($startdate->getTimeStamp(), $enddate->getTimeStamp(), null, false);


        if (! $this->private || ! $this->awaiting_approval) {
            foreach ($this->events as $key => $event) {
                /* @var $event bab_CalendarPeriod */
                if (! $this->awaiting_approval && ! $event->WfInstanceAccess()) {
                    // the ovml container does not require to display waiting events and the event is in waiting state
                    unset($this->events[$key]);
                }


                if (! $this->private && (! $event->isPublic() && $event->getAuthorId() !== (int) $GLOBALS['BAB_SESS_USERID'])) {
                    // the ovml container does not require to display the private events and the event is private
                    unset($this->events[$key]);
                }
            }

            reset($this->events);
        }


        $this->count = count($this->events);
        $this->ctx->curctx->push('CCount', $this->count);
    }


    /**
     * @return bab_EventCalendar[]
     */
    protected function getAllNonPersonalCalendars()
    {
        global $babDB;

        $backend = bab_functionality::get('CalendarBackend/Ovi');
        /*@var $backend Func_CalendarBackend_Ovi */

        $query = "SELECT cpt.*, ct.id as idcal, ct.type as type
            FROM " . BAB_CAL_PUBLIC_TBL . " cpt
            LEFT JOIN " . BAB_CALENDAR_TBL . " ct on ct.owner=cpt.id
            WHERE
                ct.type IN('" . BAB_CAL_PUB_TYPE . "', '" . BAB_CAL_RES_TYPE . "')
                AND ct.actif='Y'
        ";
        $res = $babDB->db_query($query);

        $return = array();
        while ($arr = $babDB->db_fetch_assoc($res)) {
            switch ($arr['type']) {
                case BAB_CAL_RES_TYPE:
                    $calendar = $backend->ResourceCalendar();
                    break;
                case BAB_CAL_PUB_TYPE:
                    $calendar = $backend->PublicCalendar();
                    break;
            }

            $calendar->init(0, $arr);
            $return[] = $calendar;
        }

        return $return;
    }


    /**
     * Get available calendar without filter
     *
     * @return bab_EventCalendar[]
     */
    public function getCalendars($calendarid)
    {
        require_once dirname(__FILE__) . '/cal.ovicalendar.class.php';

        if (empty($calendarid)) {
            return $this->getAllNonPersonalCalendars();
        }
        $public = bab_cal_getPublicCalendars(0, $calendarid);
        $resource = bab_cal_getResourceCalendars(0, $calendarid);
        $personal = bab_cal_getPersonalCalendars(0, $calendarid);

        return array_merge($public, $resource, $personal);
    }


    /**
     * Get available calendar with filter
     *
     * @param string $calendarid    Comma-separated list of calendar ids
     * @param int $delegationid
     *
     */
    public function getUserCalendars($calendarid, $delegationid)
    {
        $calendars = bab_getICalendars()->getCalendars();

        if ($calendarid) {
            $calendarid_list = array_flip(explode(',', $calendarid));
        } elseif (! $delegationid) {
            switch (true) {
                case ($this instanceof Func_Ovml_Container_CalendarGroupEvents):

                    $calendarid_list = array();
                    foreach ($calendars as $calendar) {
                        if ($calendar instanceof bab_PublicCalendar) {

                            $calendarid_list[$calendar->getUid()] = 1;
                        }
                    }
                    break;

                case ($this instanceof Func_Ovml_Container_CalendarResourceEvents):

                    $calendarid_list = array();
                    foreach ($calendars as $calendar) {
                        if ($calendar instanceof bab_ResourceCalendar) {

                            $calendarid_list[$calendar->getUid()] = 1;
                        }
                    }
                    break;


                case ($this instanceof Func_Ovml_Container_CalendarUserEvents):

                    $personal = bab_getICalendars()->getPersonalCalendar();
                    if (! $personal) {
                        return array();
                    }

                    $calendarid_list = array(
                        $personal->getUid() => 1
                    );

                    break;

                default:

                    $calendarid_list = array();
                    foreach ($calendars as $calendar) {
                        $calendarid_list[$calendar->getUid()] = 1;
                    }

                    break;
            }
        }

        $return = array();

        foreach ($calendars as $calendar) {
            if (isset($calendarid_list) && ! isset($calendarid_list[$calendar->getUid()])) {
                continue;
            }

            $dg = $calendar->getDgOwner();

            if ($delegationid && $delegationid != $dg) {
                continue;
            }



            $return[] = $calendar;
        }

        return $return;
    }


    /**
     * for deprecated attribute idgroup, iduser, idresource
     * in events contener
     * idcalendar is better
     *
     * @param object $ctx
     * @param array $owner
     */
    public function getCalendarsFromOwner(&$ctx, $owner)
    {
        global $babDB;
        $calendars = array();
        $res = $babDB->db_query("SELECT id FROM " . BAB_CALENDAR_TBL . " WHERE owner IN(" . $babDB->quote($owner) . ")");
        while ($arr = $babDB->db_fetch_assoc($res)) {
            $calendars[] = $arr['id'];
        }

        $ctx->curctx->push('calendarid', implode(',', $calendars));
    }


    public function getnext()
    {
        if ($this->idx < $this->count && ($this->maxEvent == 0 || $this->idx < $this->maxEvent)) {
            list (, $p) = each($this->events);
            $arr = $p->getData();

            $id_category = '';
            $category_color = '';
            $color = $p->getProperty('X-CTO-COLOR');

            $cat = bab_getCalendarCategory($p->getProperty('CATEGORIES'));
            if ($cat) {
                $id_category = $cat['id'];
                $category_color = $cat['bgcolor'];
                $color = $category_color;
            }

            $id_event = $p->getProperty('UID');

            $collection = $p->getCollection();
            $calendar = $collection->getCalendar();


            if (! $calendar) {
                $calendar = reset($p->getCalendars());
            }


            if ($calendar) {
                /* @var $calendar bab_EventCalendar */
                $arr['id_cal'] = $calendar->getUrlIdentifier();
            } else {
                $arr['id_cal'] = 0;
            }

            $calid_param = ! empty($arr['id_cal']) ? '&idcal=' . $arr['id_cal'] : '';
            $summary = $p->getValue('SUMMARY');
            $description = bab_toHtml($p->getValue('DESCRIPTION')); // default value if wysiwyg api not present
            $location = $p->getValue('LOCATION');
            $categories = $p->getValue('CATEGORIES');
            $date = date('Y,m,d', $p->ts_begin);

            // with filter
            if ($calendar && ! $calendar->canViewEventDetails($p)) {
                $summary = $p->isPublic() ? bab_translate('Awaiting approval') : bab_translate('Private');
                $description = '';
                $location = '';
                $categories = '';
            }

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('EventId', $id_event);
            $this->ctx->curctx->push('EventCalendarId', $arr['id_cal']);
            $this->ctx->curctx->push('EventCalendarUrlId', $calendar->getUrlIdentifier());
            $this->ctx->curctx->push('EventCalendarName', $calendar->getName());
            $this->ctx->curctx->push('EventCalendarType', $calendar->getType());
            $this->ctx->curctx->push('EventTitle', $summary);

            if (isset($arr['description']) && isset($arr['description_format'])) {
                $this->pushEditor('EventDescription', $arr['description'], $arr['description_format'], 'bab_calendar_event');
            } else {
                $this->ctx->curctx->push('EventDescription', $description);
            }
            $this->ctx->curctx->push('EventLocation', $location);
            $this->ctx->curctx->push('EventBeginDate', $p->ts_begin);
            $this->ctx->curctx->push('EventEndDate', $p->ts_end);
            $this->ctx->curctx->push('EventCategoryId', $id_category);
            $this->ctx->curctx->push('EventCategoryColor', $category_color);
            $this->ctx->curctx->push('EventColor', $color);
            if ($calid_param) {
                $this->ctx->curctx->push('EventUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=calendar&idx=vevent&evtid=" . $id_event . $calid_param);
                $this->ctx->curctx->push('EventCalendarUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=" . $this->view . $calid_param . "&date=" . $date);
                $this->ctx->curctx->push('EventCategoriesPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=calendar&idx=viewc" . $calid_param);
            } else {
                $this->ctx->curctx->push('EventUrl', '');
                $this->ctx->curctx->push('EventCalendarUrl', '');
                $this->ctx->curctx->push('EventCategoriesPopupUrl', '');
            }

            $this->ctx->curctx->push('EventCategoryName', $categories);

            $EventOwner = isset($arr['id_cal']) ? bab_getCalendarOwner($arr['id_cal']) : '';

            $this->ctx->curctx->push('EventOwner', $EventOwner);
            if (isset($arr['id_modifiedby']) && $arr['id_modifiedby']) {
                $this->ctx->curctx->push('EventUpdateDate', BAB_DateTime::fromICal($p->getProperty('LAST-MODIFIED'))->getTimeStamp());
                $this->ctx->curctx->push('EventUpdateAuthor', $arr['id_modifiedby']);
            }
            if (isset($arr['id_creator']) && $arr['id_creator']) {
                $this->ctx->curctx->push('EventAuthor', $arr['id_creator']);
            }

            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}




/**
 * Return a list of domain
 *
 * calendarid 			: complete calendar id (ex: public/3), should be <OVEventCalendarId<OVEventId>>
 * eventid				: uid of an event, should be <OVEventId>
 * dtstart 				: <OVEventBeginDate>
 *
 * <OCCalendarEvents calendarid="" eventid="" dtstart="">
 *
 * 	<OVDomainName>
 * 	<OVDomainValue>
 *
 * </OCCalendarEvents>
 *
 */
class Func_Ovml_Container_CalendarEventDomains extends Func_Ovml_Container
{
    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);

        $calendarid = $ctx->curctx->getAttribute('calendarid');
        $eventid = $ctx->curctx->getAttribute('eventid');
        $dtstart = $ctx->curctx->getAttribute('dtstart');

        $calendar = bab_getICalendars()->getEventCalendar($calendarid);

        $backend = $calendar->getBackend();

        $period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $eventid, $dtstart);

        $domsStr = $period->getDomains();

        $this->doms = array();
        if ($domsStr) {
            $this->doms = bab_getDomains($domsStr);
        }

        $this->count = count($this->doms);
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        if (! empty($this->doms) && $dom = array_shift($this->doms)) {
            $this->ctx->curctx->push('DomainName', $dom['domain']);
            $this->ctx->curctx->push('DomainValue', $dom['value']);
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_CalendarUserEvents extends Func_Ovml_Container_CalendarEvents
{
    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_CalendarEvents::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->cal_users = 1;
        $this->cal_groups = 0;
        $this->cal_resources = 0;
        $this->cal_default_users = 0;

        $userid = $ctx->curctx->getAttribute('userid');

        if (false !== $userid && '' !== $userid) {
            $this->getCalendarsFromOwner($ctx, explode(',', $userid));
        }

        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_CalendarGroupEvents extends Func_Ovml_Container_CalendarEvents
{
    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_CalendarEvents::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->cal_users = 0;
        $this->cal_groups = 1;
        $this->cal_resources = 0;

        $groupid = $ctx->curctx->getAttribute('groupid');

        if (false !== $groupid && '' !== $groupid) {
            $this->getCalendarsFromOwner($ctx, explode(',', $groupid));
        }

        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_CalendarResourceEvents extends Func_Ovml_Container_CalendarEvents
{
    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_CalendarEvents::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->cal_users = 0;
        $this->cal_groups = 0;
        $this->cal_resources = 1;

        $resourceid = $ctx->curctx->getAttribute('resourceid');

        if (false !== $resourceid && '' !== $resourceid) {
            $this->getCalendarsFromOwner($ctx, explode(',', $resourceid));
        }

        parent::setOvmlContext($ctx);
    }
}
