<?php
App::import('Libs', 'Calendar.Expander');
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
                          'rrule_bymonth' => array('checkByMonth' => array(
                                                                           'rule' => array('checkByMonth'),
                                                                           'allowEmpty' => true,
                                                                           )),
                          'rrule_bymonthday' => array('checkByMonthDay' => array(
                                                                                 'rule' => array('checkByMonthDay'),
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
             * jpn:終日イベントの場合はsetEvent()に渡す値を00:00:00を持たないで登録すると終日判定する
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
        $events = Expander::_generateCalendarTemplate($start, $end);

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
            $events = Expander::mergeEvents($events, Expander::expandEvent($eventStart, $eventEnd, $event));
        }

        // find rrule events
        $query = array();
        $query['conditions'] = array('Vevent.dtstart <=' =>  $end,
                                     array('Vevent.rrule_freq IS NOT NULL'),
                                     array('Vevent.rrule_freq !=' => ''));
        $query['order'] = array('Vevent.dtstart');
        $result2 = $this->find('all', $query);
        foreach ($result2 as $event) {
            $expander = new Expander();
            $e = $expander->expandEvents($start, $end, $event);
            foreach ($e as $ee) {
                if (empty($ee['Vevent'])) {
                    $ee = array('Vevent' => $ee);
                }
                $eventStart = $ee['Vevent']['event_start'];
                $eventEnd = $ee['Vevent']['event_end'];
                if (strtotime($eventStart) < strtotime($start)) {
                    $eventStart = $start;
                }
                if (strtotime($eventEnd) > strtotime($end)) {
                    $eventEnd = $end;
                }
                $events = Expander::mergeEvents($events, Expander::expandEvent($eventStart, $eventEnd, $ee));
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
}
