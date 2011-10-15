<?php
class Vevent extends CalendarAppModel {
    var $name = 'Vevent';
    var $displayField = 'summary';

    var $actsAs = array('Calendar.CalendarValidationRule');

    var $validate = array(
                          'uid' => array('isUniqueUid' => array(
                                                                'rule' => array('isUnique'),
                                                                'required' => true,
                                                                )),
                          'dtstart' => array('notEmptyDtstart' => array(
                                                                        'rule' => array('notEmpty'),
                                                                        'required' => true,
                                                                        ),
                                             'checkEventDate' => array(
                                                                       'rule' => array('checkEventDate'),
                                                                       'required' => true,
                                                                       )
                                             ),
                          'dtend' => array(
                                           'checkEventDate' => array(
                                                                     'rule' => array('checkEventDate'),
                                                                     'required' => true,
                                                                     )
                                           ),
                          'summary' => array('notEmptySummary' => array(
                                                                        'rule' => array('notEmpty'),
                                                                        'required' => true,
                                                                        )),
                          'rrule_freq' => array('inListFreq' => array(
                                                                      'rule' => array('inList', array('daily', 'weekly', 'monthly', 'yearly')),
                                                                      'allowEmpty' => true,
                                                                      ),
                                                ),
                          'rrule_count' => array('exclusiveRrule' => array(
                                                                           'rule' => array('exclusiveRrule'),
                                                                           'allowEmpty' => true,
                                                                           )),
                          'rrule_until' => array('exclusiveRrule' => array(
                                                                           'rule' => array('exclusiveRrule'),
                                                                           'allowEmpty' => true,
                                                                           )),
                          'rrule_byday' => array('checkByDay' => array(
                                                                       'rule' => array('checkByDay'),
                                                                       'allowEmpty' => true,
                                                                       )),
                          );

    /**
     * setEvent
     *
     * @param $event
     * @return
     */
    function setEvent($event){
        if (!is_array($event)) {
            return false;
        }
        if (!empty($event['Vevent'])) {
            $event = $event['Vevent'];
        }
        if (!empty($event['uid'])) {
            $uid = $event['uid'];
            $current = $this->findByUid($event['uid']);
            $event = Set::merge($current, $event);
        } else {
            unset($event['id']);
            $uid = $this->_generateUid();
            $event['uid'] = $uid;
        }
        if (empty($event['id'])) {
            $event = $this->create($event);
        }
        if (!$this->save($event)) {
            return false;
        }
        return $uid;
    }

    /**
     * findByUid
     *
     * @param $uid
     * @return
     */
    function findByUid($uid){
        $query = array();
        $query['conditions'] = array('Vevent.uid' => $uid);
        $query['order'] = array('Vevent.created');
        $event = $this->find('first', $query);
        return $event;
    }

    /**
     * findByDate
     *
     * @param $date
     * @return
     */
    function findByDate($date){
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $start = $date;
        $end = date('Y-m-d', strtotime($start . ' +1day'));
        $events = $this->findByRange($start, $end);
        return $events;
    }

    /**
     * findByMonth
     *
     * @param String $month
     * @return
     */
    function findByMonth($month, $year = null){
        if (empty($year)) {
            $year = date('Y');
        }
        if (empty($month)) {
            $month = date('m');
            $strMonth = $year . '-' . $month;
        } elseif (preg_match('/^[0-9]{4}-[0-9]{2}$/',$month)) {
            $strMonth = $month;
        } elseif (!is_numeric($month)) {
            return false;
        } else {
            $strMonth = $year . '-' . $month;
        }
        $start = $strMonth . '-1';
        $end = date('Y-m-d H:i:s', mktime(0,0,0,(int)($month + 1), 0, $year));
        $events = $this->findByRange($start, $end);
        return $events;
    }

    /*
     * findByRange
     *
     * @param $start, $end
     * @return
     */
    function findByRange($start, $end){
        //$s = $this->_expandDate($start);
        //$e = $this->_expandDate($end);
        //$start = date('Y-m-d H:i:s', mktime(0,0,0, $s['month'], $s['day'], $s['year']));
        //$end = date('Y-m-d H:i:s', mktime(23,59,59, $e['month'], $e['day'], $e['year']));
        $events = $this->_generateCalendarTemplate($start, $end);

        $query = array();
        $query['conditions'] = array('OR' => array(array('Vevent.dtstart >=' => $start,
                                                         'Vevent.dtstart <=' => $end,
                                                         ),
                                                   array('Vevent.dtend >=' => $start,
                                                         'Vevent.dtend <=' => $end,
                                                         )
                                                   ),
                                     'OR' => array(array('Vevent.rrule_freq IS NULL'),
                                                   array('Vevent.rrule_freq' => '')));
        $query['order'] = array('Vevent.dtstart');
        $result = $this->find('all', $query);

        foreach ($result as $event) {
            $event['Vevent']['event_start'] = $event['Vevent']['dtstart'];
            $event['Vevent']['event_end'] = $event['Vevent']['dtend'];
            $eventStart = $event['Vevent']['dtstart'];
            $eventEnd = $event['Vevent']['dtend'];
            if (strtotime($eventStart) < strtotime($start)) {
                $eventStart = $start;
            }
            if (strtotime($eventEnd) > strtotime($end)) {
                $eventEnd = $end;
            }
            $events = $this->_mergeEvents($events, $this->_expandEvent($eventStart, $eventEnd, $event['Vevent']));
        }

        // find rrule events
        $query = array();
        $query['conditions'] = array('Vevent.dtstart <=' =>  $end,
                                     array('Vevent.rrule_freq IS NOT NULL'),
                                     array('Vevent.rrule_freq !=' => ''));
        $query['order'] = array('Vevent.dtstart');
        $result2 = $this->find('all', $query);
        foreach ($result2 as $event) {
            $e = $this->_expandEvents($start, $end, $event);
            foreach ($e as $ee) {
                $eventStart = $ee['event_start'];
                $eventEnd = $ee['event_end'];
                if (strtotime($eventStart) < strtotime($start)) {
                    $eventStart = $start;
                }
                if (strtotime($eventEnd) > strtotime($end)) {
                    $eventEnd = $end;
                }
                $events = $this->_mergeEvents($events, $this->_expandEvent($eventStart, $eventEnd, $ee));
            }
        }

        return $events;
    }

    /**
     * _mergeEvents
     *
     * @param $arg
     * @return
     */
    function _mergeEvents($events1, $events2){
        foreach ($events1 as $date => $e) {
            if (!empty($events2[$date])) {
                foreach ($events2[$date] as $event) {
                    $events1[$date][] = $event;
                }
            }
        }
        return $events1;
    }

    /**
     * _expandEvents
     *
     * @param
     * @return
     */
    function _expandEvents($start, $end, $event){
        $events = array();
        $freq = $event['Vevent']['rrule_freq'];
        $byday = $event['Vevent']['rrule_byday'];
        switch($freq) {
        case 'daily':
            $events = $this->_expandEventsDaily($start, $end, $event);
            break;
        case 'weekly':
            $events = $this->_expandEventsWeekly($start, $end, $event);
            break;
        case 'monthly':
            $events = $this->_expandEventsMonthly($start, $end, $event);
            break;
        case 'yearly':
            $events = $this->_expandEventsYearly($start, $end, $event);
            break;
        }
        return $events;
    }

    /**
     * _expandEventsDaily
     *
     *
     * @param $start, $end, $event
     * @return
     */
    function _expandEventsDaily($start, $end, $event){
        $eventStart = $event['Vevent']['dtstart'];
        $eventEnd = $event['Vevent']['dtend'];
        $interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval'];

        $expandStartPoint; // 登録するイベント群の開始日時
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart); // 開始から終了までの差分秒

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $endPoint = mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'] + ($count * $interval), $s['year']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } elseif (!empty($event['Vevent']['rrule_until'])) {
            $endPoint = strtotime($event['Vevent']['rrule_until']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } else {
            
            $expandEndPoint = $this->_expandDate($end);
        }

        $events = array();
        $s = $expandStartPoint;
        $e = $expandEndPoint;

        if(mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) === mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while(mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) < mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);

            $events[] = $event['Vevent'];

            $s['day'] += 1 * $interval;
        }
        return $events;
    }

    /**
     * _expandEventsWeekly
     *
     * @param $start, $end, $event
     * @return
     */
    function _expandEventsWeekly($start, $end, $event){
        $eventStart = $event['Vevent']['dtstart'];
        $eventEnd = $event['Vevent']['dtend'];
        $interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval'];
        $byday = empty($event['Vevent']['rrule_byday']) ? null : explode(',', $event['Vevent']['rrule_byday']);

        $expandStartPoint; // 登録するイベント群の開始日時
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart); // 開始から終了までの差分秒

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $endPoint = mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'] + ($count * 7 * $interval), $s['year']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } elseif (!empty($event['Vevent']['rrule_until'])) {
            $endPoint = strtotime($event['Vevent']['rrule_until']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } else {
            $expandEndPoint = $this->_expandDate($end);
        }

        $events = array();
        $s = $expandStartPoint;
        $e = $expandEndPoint;
        $first = true;
        if (mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) === mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
            $first = false;
        }

        while(mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) < mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $strW = substr(strtoupper(date('D', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']))), 0, 2);

            if ($byday) {
                if (!$first || in_array($strW, $byday)) {
                    $w = date('w', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
                    $day = $s['day'];
                    if ($w === 6) {
                        $strW = substr(strtoupper(date('D', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    if ($w != 0) {
                        $day -= $w;
                    }
                    while($w < 6) {
                        $strW = substr(strtoupper(date('D', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                    }
                } else {
                    $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
                    $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
                    $events[] = $event['Vevent'];

                    $day = $s['day'];
                    $day++;
                    $w = date('w', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));

                    if ($w === 6) {
                        $strW = substr(strtoupper(date('D', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    /*
                      if ($w != 0) {
                      $day -= $w;
                      }
                    */
                    while($w < 6) {
                        $strW = substr(strtoupper(date('D', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $day, $s['year']));
                    }
                }
            } else {
                $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
                $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
                $events[] = $event['Vevent'];
            }
            $s['day'] += 7 * $interval;
            $first = false;
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            // @todo refactor code
            return array_slice($events, 0, $event['Vevent']['rrule_count']);
        }

        return $events;
    }

    /**
     * _expandEventsMonthly
     *
     *
     * @param $start, $end, $event
     * @return
     */
    function _expandEventsMonthly($start, $end, $event){
        $eventStart = $event['Vevent']['dtstart'];
        $eventEnd = $event['Vevent']['dtend'];
        $interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval'];

        $expandStartPoint; // 登録するイベント群の開始日時
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart); // 開始から終了までの差分秒

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $endPoint = mktime($s['hour'], $s['min'], $s['second'], $s['month'] + ($count * $interval), $s['day'], $s['year']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } else {
            $expandEndPoint = $this->_expandDate($end);
        }

        $events = array();
        $s = $expandStartPoint;
        $e = $expandEndPoint;
        if (mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) === mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while(mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) < mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
            $s['month'] += 1 * $interval;
        }

        return $events;
    }

    /**
     * _expandEventsYearly
     *
     *
     * @param $start, $end, $event
     * @return
     */
    function _expandEventsYearly($start, $end, $event){
        $eventStart = $event['Vevent']['dtstart'];
        $eventEnd = $event['Vevent']['dtend'];
        $interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval'];

        $expandStartPoint; // 登録するイベント群の開始日時
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart); // 開始から終了までの差分秒

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $endPoint = mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year'] + ($count * $interval));
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } elseif (!empty($event['Vevent']['rrule_until'])) {
            $endPoint = strtotime($event['Vevent']['rrule_until']);
            if (strtotime($end) < $endPoint) {
                $expandEndPoint = $this->_expandDate($end);
            } else {
                $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } else {
            $expandEndPoint = $this->_expandDate($end);
        }

        $events = array();
        $s = $expandStartPoint;
        $e = $expandEndPoint;
        if (mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) === mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while(mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) < mktime($e['hour'], $e['min'], $e['second'], $e['month'], $e['day'], $e['year'])) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year']) + $eventDiff);
            $events[] = $event['Vevent'];
            $s['year'] += 1 * $interval;
        }

        return $events;
    }

    /**
     * _generateCalendarTemplate
     *
     * @param $start, $end
     * @return
     */
    function _generateCalendarTemplate($start, $end){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $calendar = array();
        $startDate = $this->_expandDate($start);
        for ($i = 0; $i <= $daydiff; $i++) {
            $key = date('Y-m-d', mktime(0, 0, 0, $startDate['month'], ($startDate['day'] + $i), $startDate['year']));
            $calendar[$key] = array();
        }
        return $calendar;
    }

    /**
     * _expandEvent
     *
     * @param $start, $end
     * @return
     */
    function _expandEvent($start, $end, $event = null){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $calendar = array();
        $startDate = $this->_expandDate($start);
        for ($i = 0; $i <= $daydiff; $i++) {
            $key = date('Y-m-d', mktime(0, 0, 0, $startDate['month'], ($startDate['day'] + $i), $startDate['year']));
            if ($event) {
                $sub = $event;
                $eventStart = $sub['event_start'];
                $eventEnd = $sub['event_end'];
                if (strtotime($eventStart) < strtotime($key)) {
                    $eventStart = $key . ' 00:00:00';
                }
                $e = $this->_expandDate($eventEnd);
                if (mktime(0,0,0,$e['month'],$e['day'],$e['year']) > strtotime($key)) {
                    $date = $this->_expandDate($key);
                    $eventEnd = date('Y-m-d H:i:s', mktime(23, 59, 59, $date['month'], $date['day'], $date['year']));
                }
                $sub['event_start'] = $eventStart;
                $sub['event_end'] = $eventEnd;
                $calendar[$key] = array($sub);
            } else {
                $calendar[$key] = array();
            }
        }
        return $calendar;
    }

    /**
     * _generateUid
     *
     * @param $arg
     * @return
     */
    function _generateUid(){
        // http://jp2.php.net/uniqid#94959 v4
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                       // 32 bits for "time_low"q
                       mt_rand(0, 0xffff), mt_rand(0, 0xffff),

                       // 16 bits for "time_mid"
                       mt_rand(0, 0xffff),

                       // 16 bits for "time_hi_and_version",
                       // four most significant bits holds version number 4
                       mt_rand(0, 0x0fff) | 0x4000,

                       // 16 bits, 8 bits for "clk_seq_hi_res",
                       // 8 bits for "clk_seq_low",
                       // two most significant bits holds zero and one for variant DCE1.1
                       mt_rand(0, 0x3fff) | 0x8000,

                       // 48 bits for "node"
                       mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                       );
    }

    /**
     * _expandDate
     *
     *
     * @param $date
     * @return
     */
    function _expandDate($date){
        $expand = array();
        $expand['year'] = date('Y', strtotime($date));
        $expand['month'] = date('m', strtotime($date));
        $expand['day'] = date('d', strtotime($date));
        $expand['hour'] = date('H', strtotime($date));
        $expand['min'] = date('i', strtotime($date));
        $expand['second'] = date('s', strtotime($date));
        return $expand;
    }
}
