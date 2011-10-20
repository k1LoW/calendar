<?php
class VeventFixture extends CakeTestFixture {
    var $name = 'Vevent';

    var $fields = array(
                        'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
                        'uid' => array('type' => 'text', 'null' => true, 'default' => NULL),

                        'dtstart' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                        'dtend' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                        'daylong' => array('type' => 'boolean', 'null' => true, 'default' => NULL),

                        'rrule_freq' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'rrule_interval' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'rrule_byday' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'rrule_bymonth' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'rrule_bymonthday' => array('type' => 'text', 'null' => true, 'default' => NULL),

                        'rrule_count' => array('type' => 'integer', 'null' => true, 'default' => NULL),
                        'rrule_until' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),

                        'location' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'categories' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'summary' => array('type' => 'text', 'null' => true, 'default' => NULL),
                        'description' => array('type' => 'text', 'null' => true, 'default' => NULL),

                        'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                        'modified' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                        );

    var $records = array(
                         array(
                               'id' => 1,
                               'uid' => 'xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1',
                               'dtstart' => '2011-10-15 10:00:00',
                               'dtend' => '2011-10-16 17:00:00',
                               'daylong' => null,
                               'rrule_freq' => null,
                               'rrule_interval' => null,
                               'rrule_byday' => null,
                               'rrule_bymonth' => null,
                               'rrule_bymonthday' => null,
                               'rrule_count' => null,
                               'rrule_until' => null,
                               'location' => 'Fukuoka',
                               'categories' => '',
                               'summary' => 'PHPMatsuri 2011',
                               'description' => 'PHPMatsuri 2011',
                               'created' => '2011-08-23 12:05:02',
                               'modified' => '2011-08-23 12:05:02'
                               ),
                         );
}