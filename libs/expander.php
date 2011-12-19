<?php

/**
 * Expander
 *
 */
class Expander{

    private $event;
    private $events;
    private $eventStart; // DTSTART
    private $eventEnd; // DTEND
    private $freq; // FREQ
    private $count;
    private $until;
    private $byday;
    private $bymonth;
    private $bymonthday;
    private $interval; // INTERVAL

    private $expandStartPoint;
    private $expandEndPoint;
    private $eventDiff; // jpn:開始から終了までの差分秒

    private $first;

    /**
     * expandEvents
     *
     * @param
     * @return
     */
    public function expandEvents($start, $end, $event){
        $this->event = $event;
        $this->events = array();
        $this->eventStart = $event['Vevent']['dtstart'];
        $this->eventEnd = $event['Vevent']['dtend'];
        $this->freq = $event['Vevent']['rrule_freq'];
        $this->count = empty($event['Vevent']['rrule_count']) ? null : $event['Vevent']['rrule_count'];
        $this->until = empty($event['Vevent']['rrule_until']) ? null : $event['Vevent']['rrule_until'];
        $this->byday = empty($event['Vevent']['rrule_byday']) ? null : explode(',', $event['Vevent']['rrule_byday']);
        $this->bymonth = empty($event['Vevent']['rrule_bymonth']) ? null : explode(',', $event['Vevent']['rrule_bymonth']);
        if (!empty($this->bymonth)) {
            foreach ($this->bymonth as $key => $value) {
                $this->bymonth[$key] = sprintf('%02d', $value);
            }
        }
        $this->bymonthday = empty($event['Vevent']['rrule_bymonthday']) ? null : explode(',', $event['Vevent']['rrule_bymonthday']);
        $this->interval = empty($event['Vevent']['rrule_interval']) ? 1 : $event['Vevent']['rrule_interval']; // INTERVAL

        $this->eventDiff = strtotime($this->eventEnd) - strtotime($this->eventStart);

        $this->first = true;

        if (strtotime($this->eventStart) < strtotime($start)) {
            $this->expandStartPoint = $this->_expandDate($start);
        } else {
            $this->expandStartPoint = $this->_expandDate($this->eventStart);
        }

        if (!empty($this->count)) {
            $s = $this->_expandDate($this->eventStart);
            switch($this->freq) {
            case 'daily':
                $s['day'] = $s['day'] + ($this->count * $this->interval);
                break;
            case 'weekly':
                $s['day'] = $s['day'] + ($this->count * 7 * $this->interval);
                break;
            case 'monthly':
                $s['month'] = $s['month'] + ($this->count * $this->interval);
                break;
            case 'yearly':
                $s['year'] = $s['year'] + ($this->count * $this->interval);
                break;
            }
            $endPoint = $this->_mta($s);
            if (strtotime($end) < $endPoint) {
                $this->expandEndPoint = $this->_expandDate($end);
            } else {
                $this->expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } elseif (!empty($this->until)) {
            $endPoint = strtotime($this->until);
            if (strtotime($end) < $endPoint) {
                $this->expandEndPoint = $this->_expandDate($end);
            } else {
                $this->expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', $endPoint));
            }
        } else {
            $this->expandEndPoint = $this->_expandDate($end);
        }

        if ($this->_expandDate($this->eventStart) !== $this->expandStartPoint){
            //
            // jpn:表示範囲に最初の設定日が入っていない場合は$this->first = false
            $this->first = false;
        }

        if ($this->_mta($this->expandStartPoint) === $this->_mta($this->expandEndPoint)) {
            //
            // jpn:開始ポイントと終了ポイントが同じ場合は1イベントしか登録せず終了
            $this->_pushEvent($s);
            $this->first = false;
        }

        $events = array();
        switch($this->freq) {
        case 'daily':
            $this->_expandEventsDaily();
            break;
        case 'weekly':
            $this->_expandEventsWeekly();
            break;
        case 'monthly':
            $this->_expandEventsMonthly();
            break;
        case 'yearly':
            $this->_expandEventsYearly();
            break;
        }

        $this->_checkCount();

        return $this->events;
    }

    /**
     * _expandEventsDaily
     *
     *
     * @param $start, $end, $event
     * @return
     */
    private function _expandEventsDaily(){
        $s = $this->expandStartPoint;
        $e = $this->expandEndPoint;

        while($this->_mta($s) < $this->_mta($e)) {
            if ($this->bymonth) {
                $month = date('m', $this->_mta($s));
                if (in_array($month, $this->bymonth)) {
                    $this->_pushEvent($s);
                }
            } else {
                $this->_pushEvent($s);
            }
            $s['day'] += 1 * $this->interval;
        }
    }

    /**
     * _expandEventsWeekly
     *
     * @param $start, $end, $event
     * @return
     */
    private function _expandEventsWeekly(){
        $s = $this->expandStartPoint;
        $e = $this->expandEndPoint;

        while($this->_mta($s) < $this->_mta($e)) {
            $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
            if ($this->byday) {
                /**
                 * RRULE::BYDAY
                 */
                if (!$this->first || in_array($strW, $this->byday)) {
                    $w = date('w', $this->_mta($s));
                    $day = $s['day'];
                    if ($w == 6) {
                        $this->_pushByDayEvent($s);
                        $day++;
                        $w = 0;
                    }
                    if ($w != 0 && !$this->first) {
                        //
                        // jpn:2週目からは日から土まで探索する
                        $day -= $w;
                    }
                    while($w < 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
                        $day++;
                        $w = date('w', $this->_mta($t));
                    }
                } else {
                    /**
                     *
                     * jpn:BYDAYの最初の設定日は曜日指定に関わらずイベント登録される
                     *     例)毎週水曜日(BYDAY:WE)のイベントでイベント開始日が火曜日の場合、開始日のみ火曜日でもイベント登録される
                     */
                    $this->_pushEvent($s);

                    $day = $s['day'];
                    $day++;
                    $w = date('w', $this->_mta($s));
                    if ($w == 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
                        $day++;
                        $w = 0;
                    }
                    while($w < 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
                        $day++;
                        $w = date('w', $this->_mta($t));
                    }
                }
            } else {
                $this->_pushEvent($s);
            }
            $s['day'] += 7 * $this->interval;
            $this->first = false;
        }
    }

    /**
     * _expandEventsMonthly
     *
     *
     * @return
     */
    private function _expandEventsMonthly(){
        $s = $this->expandStartPoint;
        $e = $this->expandEndPoint;

        while($this->_mta($s) < $this->_mta($e)) {
            $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
            if ($this->byday) {
                /**
                 * RRULE::BYDAY
                 */
                if (!$this->first || in_array($strW, $this->byday)) {
                    $w = date('w', $this->_mta($s));
                    $day = $s['day'];
                    if ($w == 6) {
                        $this->_pushByDayEvent($s);
                        $day++;
                        $w = 0;
                    }
                    if ($w != 0 && !$this->first) {
                        //
                        // jpn:2週目からは日から土まで探索する
                        $day -= $w;
                    }
                    $month = date('m', $this->_mta($s));
                    while($month == $s['month']) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
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
                    $this->_pushEvent($s);

                    $day = $s['day'];
                    $day++;
                    $w = date('w', $this->_mta($s));

                    if ($w == 6) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
                        $day++;
                        $w = 0;
                    }
                    $month = date('m', $this->_mta($s));
                    while($month == $s['month']) {
                        $t = $s;
                        $t['day'] = $day;
                        $this->_pushByDayEvent($t);
                        $day++;
                        $w = date('w', $this->_mta($t));
                        $month = date('m', $this->_mta($t));
                    }
                }

            } else {
                $this->_pushEvent($s);
            }
            $s['month'] += 1 * $this->interval;
            $this->first = false;
        }
    }

    /**
     * _expandEventsYearly
     *
     *
     * @param $start, $end, $event
     * @return
     */
    private function _expandEventsYearly(){
        $s = $this->expandStartPoint;
        $e = $this->expandEndPoint;

        while($this->_mta($s) < $this->_mta($e)) {
            $year = $s['year'];
            $month = $s['month'];
            if ($this->bymonth) {
                /**
                 * RRULE::BYMONTH
                 */
                if (!$this->first || in_array($month, $this->bymonth)) {
                    $month = $s['month'];
                    $year = date('Y', $this->_mta($s));
                    if ($month != 1 && !$this->first) {
                        //
                        // jpn:2周目からは1月から探索する
                        $month = 1;
                    }
                    while($year == $s['year']) {
                        $t = $s;
                        $t['month'] = $month;
                        if (in_array($month, $this->bymonth)) {
                            $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
                            if ($this->byday) {
                                /**
                                 * RRULE::BYDAY
                                 */
                                if (!$this->first || in_array($strW, $this->byday)) {
                                    $w = date('w', $this->_mta($t));
                                    $day = $t['day'];
                                    if (!$this->first) {
                                        //
                                        // jpn:2周目からは1日目から探索する
                                        $day = 1;
                                    }
                                    $tmonth = date('m', $this->_mta($t));
                                    while($tmonth == $t['month']) {
                                        $tt = $t;
                                        $tt['day'] = $day;
                                        $this->_pushByDayEvent($tt);
                                        $day++;
                                        $w = date('w', $this->_mta($tt));
                                        $tmonth = date('m', $this->_mta($tt));
                                    }
                                } else {
                                    $this->_pushEvent($t);
                                }
                            } else {
                                $this->_pushEvent($t);
                            }
                        }

                        $month++;
                        $year = date('Y', $this->_mta($t));
                        $this->first = false;
                    }
                } else {
                    /**
                     *
                     * jpn:BYMONTHの最初の設定日は指定に関わらずイベント登録される
                     */
                    $this->_pushEvent($s);

                    while($year == $s['year']) {
                        $t = $s;
                        $t['month'] = $month;
                        if (in_array($month, $this->bymonth)) {
                            if ($this->byday) {
                                /**
                                 * RRULE::BYDAY
                                 */
                                $w = date('w', $this->_mta($t));
                                $day = $t['day'];
                                if (!$this->first) {
                                    //
                                    // jpn:次月からは1日目から探索する
                                    $day = 1;
                                }
                                $tmonth = date('m', $this->_mta($t));
                                while($tmonth == $t['month']) {
                                    $tt = $t;
                                    $tt['day'] = $day;
                                    $this->_pushByDayEvent($tt);
                                    $day++;
                                    $w = date('w', $this->_mta($tt));
                                    $tmonth = date('m', $this->_mta($tt));
                                }
                            } else {
                                $this->_pushEvent($t);
                            }
                        }
                        $month++;
                        $year = date('Y', $this->_mta($t));
                        $this->first = false;
                    }
                }
            } else {
                $this->_pushEvent($s);
            }

            $s['year'] += 1 * $this->interval;
            $this->first = false;
        }
    }

    /**
     * expandEvent
     *
     * jpn:複数の日に渡るようなイベントなどを展開する
     *     終日イベントの整形も行う
     * @param $start, $end, $event
     * @return
     */
    public static function expandEvent($start, $end, $event){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $events = array();
        $startDate = Expander::_expandDate($start);
        if (empty($event['Vevent'])) {
            $event = array('Vevent' => $event);
        }
        if ($daydiff === 0) {
            $evants = array($event);
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
                $e = Expander::_expandDate($eventEnd);
                if (mktime(0,0,0,$e['month'],$e['day'],$e['year']) > strtotime($key)) {
                    $date = Expander::_expandDate($key);
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
     * _checkCount
     *
     * jpn: COUNT属性チェックする
     */
    function _checkCount(){
        if (!empty($this->count)) {
            // @todo refactor code
            $this->events = array_slice($this->events, 0, $this->count);
        }
    }

    /**
     * _pushEvent
     *
     * @param $events, $event, $s, $diff
     * @return
     */
    function _pushEvent($s){
        $event = $this->event;
        $event['Vevent']['event_start'] = date('Y-m-d H:i:s', $this->_mta($s));
        $event['Vevent']['event_end'] = date('Y-m-d H:i:s', $this->_mta($s) + $this->eventDiff);
        $this->events[] = $event;
        return $this->events;
    }

    /**
     * _pushByDayEvent
     *
     * @param $s
     * @return
     */
    function _pushByDayEvent($s){
        $strW = substr(strtoupper(date('D', $this->_mta($s))), 0, 2);
        if (in_array($strW, $this->byday)) {
            $this->_pushEvent($s);
        }
    }

    /**
     * _mta
     * mktime() from array
     *
     * @param $a
     * @return
     */
    private function _mta($a){
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
    private static function _expandDate($date){
        $expand = array();
        $expand['year'] = date('Y', strtotime($date));
        $expand['month'] = date('m', strtotime($date));
        $expand['day'] = date('d', strtotime($date));
        $expand['hour'] = date('H', strtotime($date));
        $expand['min'] = date('i', strtotime($date));
        $expand['second'] = date('s', strtotime($date));
        return $expand;
    }

    /**
     * _generateCalendarTemplate
     *
     * @param $start, $end
     * @return
     */
    public static function _generateCalendarTemplate($start, $end){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $calendar = array();
        $startDate = Expander::_expandDate($start);
        for ($i = 0; $i <= $daydiff; $i++) {
            $key = date('Y-m-d', mktime(0, 0, 0, $startDate['month'], ($startDate['day'] + $i), $startDate['year']));
            $calendar[$key] = array();
        }
        return $calendar;
    }

    /**
     * mergeEvents
     *
     * @param $events1, $events2
     * @return
     */
    public static function mergeEvents($events1, $events2){
        foreach ($events1 as $date => $e) {
            if (!empty($events2[$date])) {
                foreach ($events2[$date] as $event) {
                    $events1[$date][] = $event;
                }
            }
        }
        return $events1;
    }
}