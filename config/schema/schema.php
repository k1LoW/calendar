<?php
class AppSchema extends CakeSchema {
    var $name = 'App';

    function before($event = array()) {
        return true;
    }

    function after($event = array()) {
    }

    var $vevents = array(
                           'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
                           'uid' => array('type' => 'text', 'null' => true, 'default' => NULL),

                           'dtstart' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'dtend' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'rrule_freq' => array('type' => 'text', 'null' => true, 'default' => NULL),
                           'rrule_interval' => array('type' => 'text', 'null' => true, 'default' => NULL),
                           'rrule_byminute' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_byhour' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_byday' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_bymonth' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_bymonthday' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_wkst' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_count' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                           'rrule_until' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),

                           'location' => array('type' => 'text', 'null' => true, 'default' => NULL),
                           'categories' => array('type' => 'text', 'null' => true, 'default' => NULL),
                           'summary' => array('type' => 'text', 'null' => true, 'default' => NULL),
                           'description' => array('type' => 'text', 'null' => true, 'default' => NULL),

                           'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'modified' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                           'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
                           'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
                           );
  }