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
                          'daylong' => array(
                                           'checkDaylong' => array(
                                                                     'rule' => array('checkDaylong'),
                                                                     'allowEmpty' => true,
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
     * jpn:イベント登録
     *
     * @param $event
     * @return Mixed
     */
    function setEvent($event){
        if (!is_array($event)) {
            return false;
        }
        if (empty($event['Vevent'])) {
            $event = array('Vevent' => $event);
        }
        if (!empty($event['Vevent']['uid'])) {
            $uid = $event['Vevent']['uid'];
            $current = $this->findByUid($event['Vevent']['uid']);
            $event = Set::merge($current, $event);
        } else {
            unset($event['Vevent']['id']);
            $uid = $this->_generateUid();
            $event['Vevent']['uid'] = $uid;
        }
        if (empty($event['Vevent']['id'])) {
            $event = $this->create($event);
        }
        if (!empty($event['Vevent']['dtstart'])
            && preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $event['Vevent']['dtstart'])
            && !empty($event['Vevent']['dtend'])
            && preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $event['Vevent']['dtend'])) {
            /**
             * RFC2445:
             * The "VEVENT" is also the calendar component used to specify an
             * anniversary or daily reminder within a calendar. These events have a
             * DATE value type for the "DTSTART" property instead of the default
             * data type of DATE-TIME. If such a "VEVENT" has a "DTEND" property, it
             * MUST be specified as a DATE value also. The anniversary type of
             * "VEVENT" can span more than one date (i.e, "DTEND" property value is
             * set to a calendar date after the "DTSTART" property value).
             * jpn:終日イベントの場合は00:00:00を持たないで登録する
             *     Calendar Pluginの場合はdaylongフラグでも可
             */
            $event['Vevent']['daylong'] = true;
        }
        if (!$this->save($event)) {
            return false;
        }
        return $uid;
    }

    /**
     * dropEvent
     *
     * jpn:イベント削除
     *
     * @param $uid
     * @return Boolean
     */
    function dropEvent($uid){
        if (!$uid) {
            return false;
        }
        $event = $this->findByUid($uid);
        if (empty($event)) {
            return false;
        }
        return $this->delete($event['Vevent']['id']);
    }

    /**
     * findByUid
     *
     * jpn:uid指定で検索する
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
        } elseif (preg_match('/^([0-9]{4})-([0-9]{2})$/',$month, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
        } elseif (!is_numeric($month)) {
            return false;
        } else {
        }
        $start = date('Y-m-d H:i:s', mktime(0,0,0,$month, 1, $year));
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
            $events = $this->_mergeEvents($events, $this->_expandEvent($eventStart, $eventEnd, $event));
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
        $eventStart = $event['Vevent']['dtstart']; // DTSTART
        $eventEnd = $event['Vevent']['dtend']; // DTEND
        $interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval']; // INTERVAL

        $expandStartPoint; // jpn:登録するイベント群の開始日時
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart); // jpn:開始から終了までの差分秒

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $s['day'] = $s['day'] + ($count * $interval);
            $endPoint = $this->_mta($s);
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

        if($this->_mta($s) === $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while($this->_mta($s) < $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);

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
            $s['day'] = $s['day'] + ($count * 7 * $interval);
            $endPoint = $this->_mta($s);
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
        if ($this->_expandDate($event['Vevent']['dtstart']) !== $s){
            //
            // jpn:表示範囲に最初の設定日が入っていない場合は$first = false
            $first = false;
        }
        if ($this->_mta($s) === $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
            $events[] = $event['Vevent'];
            $first = false;
        }
        while($this->_mta($s) < $this->_mta($e)) {
            $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
            if ($byday) {
                /**
                 * RRULE::BYDAY
                 */
                if (!$first || in_array($strW, $byday)) {
                    $w = date('w', $this->_mta($s));
                    $day = $s['day'];
                    if ($w == 6) {
                        $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    if ($w != 0 && !$first) {
                        //
                        // jpn:2週目からは日から土まで探索する
                        $day -= $w;
                    }
                    while($w < 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', $this->_mta($t));
                    }
                } else {
                    /**
                     *
                     * jpn:BYDAYの最初の設定日は曜日指定に関わらずイベント登録される
                     *     例)毎週水曜日(BYDAY:WE)のイベントでイベント開始日が火曜日の場合、開始日のみ火曜日でもイベント登録される
                     */
                    $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                    $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
                    $events[] = $event['Vevent'];

                    $day = $s['day'];
                    $day++;
                    $w = date('w', $this->_mta($s));
                    if ($w == 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    while($w < 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', $this->_mta($t));
                    }
                }
            } else {
                $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
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
        $byday = empty($event['Vevent']['rrule_byday']) ? null : explode(',', $event['Vevent']['rrule_byday']);

        $expandStartPoint;
        $expandEndPoint;
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart);

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }

        if (!empty($event['Vevent']['rrule_count'])) {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $s['month'] = $s['month'] + ($count * $interval);
            $endPoint = $this->_mta($s);
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
        if ($this->_expandDate($event['Vevent']['dtstart']) !== $s){
            //
            // jpn:表示範囲に最初の設定日が入っていない場合は$first = false
            $first = false;
        }
        if ($this->_mta($s) === $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while($this->_mta($s) < $this->_mta($e)) {
            $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
            if ($byday) {
                /**
                 * RRULE::BYDAY
                 */
                if (!$first || in_array($strW, $byday)) {
                    $w = date('w', $this->_mta($s));
                    $day = $s['day'];
                    if ($w == 6) {
                        $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    if ($w != 0 && !$first) {
                        //
                        // jpn:2週目からは日から土まで探索する
                        $day -= $w;
                    }
                    $month = substr(strtoupper(date('m', $this->_mta($s))), 0, 2);
                    while($month == $s['month']) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', $this->_mta($t));
                        $month = date('m', $this->_mta($t));
                    }
                } else {
                    /**
                     *
                     * jpn:BYDAYの最初の設定日は曜日指定に関わらずイベント登録される
                     *     例)毎週水曜日(BYDAY:WE)のイベントでイベント開始日が火曜日の場合、開始日のみ火曜日でもイベント登録される
                     */
                    $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                    $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
                    $events[] = $event['Vevent'];

                    $day = $s['day'];
                    $day++;
                    $w = date('w', $this->_mta($s));

                    if ($w == 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = 0;
                    }
                    $month = substr(strtoupper(date('m', $this->_mta($s))), 0, 2);
                    pr($month);
                    while($month == $s['month']) {
                        $t = $s;
                        $t['day'] = $day;
                        $strW = substr(strtoupper(date('D', $this->_mta($t))), 0, 2);
                        if (in_array($strW, $byday)) {
                            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($t));
                            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($t) + $eventDiff);
                            $events[] = $event['Vevent'];
                        }
                        $day++;
                        $w = date('w', $this->_mta($t));
                        $month = date('m', $this->_mta($t));
                    }
                }

            } else {
                $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
                $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
                $events[] = $event['Vevent'];
            }
            $s['month'] += 1 * $interval;
            $first = false;
            /*
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
            $events[] = $event['Vevent'];
            $s['month'] += 1 * $interval;
            */
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
            $s['year'] = $s['year'] + ($count * $interval);
            $endPoint = $this->_mta($s);
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
        if ($this->_mta($s) === $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
            $events[] = $event['Vevent'];
        }
        while($this->_mta($s) < $this->_mta($e)) {
            $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
            $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $eventDiff);
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
     * jpn:複数の日に渡るようなイベントなどを展開する
     *     終日イベントの整形も行う
     * @param $start, $end, $event
     * @return
     */
    function _expandEvent($start, $end, $event){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $events = array();
        $startDate = $this->_expandDate($start);
        if (empty($event['Vevent'])) {
            $event = array('Vevent' => $event);
        }
        for ($i = 0; $i <= $daydiff; $i++) {
            $key = date('Y-m-d', mktime(0, 0, 0, $startDate['month'], ($startDate['day'] + $i), $startDate['year']));
            if ($event) {
                $sub = $event;
                $eventStart = $sub['Vevent']['event_start'];
                $eventEnd = $sub['Vevent']['event_end'];
                if (strtotime($eventStart) < strtotime($key)) {
                    $eventStart = $key . ' 00:00:00';
                }
                $e = $this->_expandDate($eventEnd);
                if (mktime(0,0,0,$e['month'],$e['day'],$e['year']) > strtotime($key)) {
                    $date = $this->_expandDate($key);
                    //
                    // jpn: DTSTART及びDTENDは時刻なので次の日の00:00:00が$eventEndにはいる
                    $eventEnd = date('Y-m-d H:i:s', mktime(0, 0, 0, $date['month'], $date['day'] + 1, $date['year']));
                }
                if ($eventStart < $eventEnd) {
                    $sub['Vevent']['event_start'] = $eventStart;
                    $sub['Vevent']['event_end'] = $eventEnd;
                    $events[$key] = array($sub);
                } else {
                    $events[$key] = array();
                }
            } else {
                $events[$key] = array();
            }
        }
        return $events;
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
     * _mta
     * mktime() from array
     *
     * @param $a
     * @return
     */
    function _mta($a){
        if (!is_array($a) || count($a) !== 6) {
            return false;
        }
        return mktime($a['hour'], $a['min'], $a['second'], $a['month'], $a['day'], $a['year']);
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
