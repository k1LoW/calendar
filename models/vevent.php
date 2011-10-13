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
        if (!empty($event['uid'])) {
            $uid = $event['uid'];
            $current = $this->findByUid($event['uid']);
            $event = Set::merge($current, $event);
        } else {
            unset($event['id']);
            $uid = $this->_generateUid();
            $event['uid'] = $uid;
        }
        $this->create($event);
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
        if (empty($month)) {
            $month = date('m');
            $strMonth = date('Y-m');
        } elseif (is_numeric($month) && 1 <= $month && $month <= 12) {
            if (empty($year)) {
                $year = date('Y');
                $strMonth = $year . '-' . $month;
            } elseif (is_numeric($year)) {
                $strMonth = $year . '-' . $month;
            } else {
                return false;
            }
        } else {
            return false;
        }
        $start = $strMonth . '-1';
        $end = date('Y-m-d', strtotime($year . '-' . (int)($month + 1) . '-1'));
        $events = $this->findByRange($start, $end);
        return $events;
    }

    /**
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
            $eventStart = $event['Vevent']['dtstart'];
            $eventEnd = $event['Vevent']['dtend'];
            if (strtotime($eventStart) < strtotime($start)) {
                $eventStart = $start;
            }
            if (strtotime($eventEnd) > strtotime($end)) {
                $eventEnd = $end;
            }
            $events = Set::merge($events, $this->_generateCalendarTemplate($eventStart, $eventEnd, $event['Vevent']));
        }

        // find rrule events
        $query = array();
        $query['conditions'] = array('Vevent.dtstart <=' =>  $end,
                                     array('Vevent.rrule_freq IS NOT NULL'),
                                     array('Vevent.rrule_freq !=' => ''));
        $query['order'] = array('Vevent.dtstart');
        $result2 = $this->find('all', $query);
        foreach ($result2 as $event) {
            $this->_expandEvent($start, $end, $event);
        }

        return $events;
    }

    /**
     * _expandEvent
     *
     * @param
     * @return
     */
    function _expandEvent($start, $end, $event){
        $events = array();
        $freq = $event['Vevent']['rrule_freq'];
        switch($freq) {
        case 'daily':
            $events = $this->_expandEventDaily($start, $end, $event);
            break;
        }
    }

    /**
     * _expandEventDaily
     * 
     *
     * @param $start, $end, $event
     * @return
     */
    function _expandEventDaily($start, $end, $event){
        $eventStart = $event['Vevent']['dtstart'];
        $eventEnd = $event['Vevent']['dtend'];
        $eventDiff = strtotime($eventEnd) - strtotime($eventStart);       

        if (strtotime($eventStart) < strtotime($start)) {
            $expandStartPoint = $this->_expandDate($start);
        } else {
            $expandStartPoint = $this->_expandDate($eventStart);
        }       
        

        if (empty($event['Vevent']['rrule_count'])) {
            $expandEndPoint = $this->_expandDate($end);
        } else {
            $count = $event['Vevent']['rrule_count'];
            $s = $this->_expandDate($eventStart);
            $expandEndPoint = $this->_expandDate(date('Y-m-d H:i:s', mktime($s['hour'], $s['min'], $s['second'], $s['month'], $s['day'], $s['year'])));
        }

        
    }

    /**
     * _generateCalendarTemplate
     *
     * @param $start, $end
     * @return
     */
    function _generateCalendarTemplate($start, $end, $event = null){
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        $daydiff = (strtotime($end) - strtotime($start)) / (3600 * 24);
        $calendar = array();
        $startDate = $this->_expandDate($start);
        for ($i = 0; $i <= $daydiff; $i++) {
            $key = date('Y-m-d', mktime(0, 0, 0, $startDate['month'], ($startDate['day'] + $i), $startDate['year']));
            if ($event) {
                $sub = $event;
                $eventStart = $event['dtstart'];
                $eventEnd = $event['dtend'];
                if (strtotime($eventStart) < strtotime($key)) {
                    $eventStart = $key;
                }
                if (strtotime($eventEnd) > strtotime($key)) {
                    $date = $this->_expanDate($key);
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
        $expand['day'] = date('m', strtotime($date));
        $expand['hour'] = date('H', strtotime($date));
        $expand['min'] = date('i', strtotime($date));
        $expand['second'] = date('s', strtotime($date));
        return = $expand;
    }
}
