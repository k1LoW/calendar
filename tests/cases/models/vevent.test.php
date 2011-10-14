<?php

App::import('Core', 'Model');
App::import('Model', array('Calendar.Vevent'));
App::import('Fixture', 'Calendar.Vevent');

class VeventPost extends CakeTestModel{

    public $name = 'Vevent';

    public $actsAs = array();
}

class VeventTestCase extends CakeTestCase{

    public $fixtures = array('plugin.calendar.vevent');

    function startTest() {
        $this->Vevent = ClassRegistry::init('Calendar.Vevent');
        $this->VeventFixture = ClassRegistry::init('Calendar.VeventFixture');
    }

    function endTest() {
        unset($this->Vevent);
        unset($this->VeventFixture);
    }

    /**
     * test_setEvent
     *
     * jpn:正常にイベントが登録できる
     */
    function test_setEvent(){
        $data = array(
                      'dtstart' => '2011-11-14 00:00:00',
                      'dtend' => '2011-11-15 00:00:00',
                      'summary' => 'テストイベント',
                      'description' => 'テストイベント\nテストイベント'
                      );
        $result = $this->Vevent->setEvent($data);
        $this->assertTrue($result);
    }

    /**
     * test_setEventAndFindByUid
     *
     * jpn:正常にイベントが登録でき、登録したデータを取得できる
     */
    function test_setEventAndFindByUid(){
        $data = array(
                      'dtstart' => '2011-11-14 00:00:00',
                      'dtend' => '2011-11-15 00:00:00',
                      'summary' => 'テストイベント',
                      'description' => 'テストイベント\nテストイベント'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByUid($uid);
        $this->assertIdentical($result['Vevent']['uid'], $uid);
    }

    /**
     * test_invalidData
     *
     * jpn:足りないデータがある場合はエラー
     */
    function test_invalidData(){
        $data = array(
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('dtstart',
                          'dtend',
                          'summary',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_invalidDate
     *
     * jpn:イベント開始日と終了日の日付が不正の場合はエラー
     */
    function test_invalidDate(){
        $data = array(
                      'dtstart' => '2011-11-16 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => '開始日と終了日が逆転している'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('dtstart',
                          'dtend',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_invalidFreq
     *
     * jpn:rrule_freqが不正な場合はエラー
     */
    function test_invalidFreq(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_freqに不正な値が入っている',
                      'rrule_freq' => 'invalid'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_freq',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_invalidFreqAndUntil
     *
     * jpn:rrule_freqとrrule_untilは排他的
     */
    function test_invalidFreqAndUntil(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_freqに不正な値が入っている',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10,
                      'rrule_until' => '2011-11-15 10:00:00'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_count',
                          'rrule_until',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_checkByDay
     *
     * jpn:rrule_bydayのチェック
     */
    function test_checkByDay(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_ bydayに不正な値が入っている',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'invalid'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_byday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_freqがdailyの場合は排他的',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10,
                      'rrule_byday' => 'SU'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_byday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_bydayに不正な値が入っている',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'SU'
                      );
        $result = $this->Vevent->setEvent($data);
        $this->assertTrue($result);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                     'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'OK',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'SU,MO'
                      );
        $result = $this->Vevent->setEvent($data);
        $this->assertTrue($result);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                     'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_bydayに不正な値が入っている',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'SU,MU'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_byday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_freqDailyCount10
     *
     * jpn:rrule_freq = 'daily'のときに正しくスケジュールが展開されること
     */
    function test_freqDailyCount10(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎日10回',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');

        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 2);
        $this->assertIdentical($result['2011-11-23'][0]['uid'], $uid);
        $this->assertIdentical($result['2011-11-24'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-23']), 2);
        $this->assertIdentical(count($result['2011-11-24']), 1);
        $this->assertIdentical($result['2011-11-25'], array());
    }

    /**
     * test_freqWeeklyCount5
     *
     */
    function test_freqWeeklyCount5(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎週5回',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => '5'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');
        $this->assertIdentical(count($result), 30);
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-11-21'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-21']), 1);
        $this->assertIdentical(count($result['2011-11-22']), 1);
        $this->assertIdentical($result['2011-11-28'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-28']), 1);
        $this->assertIdentical(count($result['2011-11-29']), 1);
        $this->assertIdentical($result['2011-11-30'], array());
    }

    /**
     * test_freqMonthlyCount3
     *
     */
    function test_freqMonthlyCount3(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎月3回',
                      'rrule_freq' => 'monthly',
                      'rrule_count' => '3'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2012-02-28');

        $this->assertIdentical(count($result), (30 + 31 + 31 + 28));
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-12-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-12-14']), 1);
        $this->assertIdentical(count($result['2011-12-15']), 1);
        $this->assertIdentical($result['2011-12-16'], array());
        $this->assertIdentical($result['2012-01-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2012-01-14']), 1);
        $this->assertIdentical(count($result['2012-01-15']), 1);
        $this->assertIdentical($result['2012-01-16'], array());
        $this->assertIdentical($result['2012-02-14'], array());
    }

    /**
     * test_freqYearlyCount4
     *
     */
    function test_freqYearlyCount4(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎年4回',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => '4'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2015-11-30');
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-13'], array());
        $this->assertIdentical($result['2012-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2012-11-14']), 1);
        $this->assertIdentical(count($result['2012-11-15']), 1);
        $this->assertIdentical($result['2012-11-16'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2013-11-14']), 1);
        $this->assertIdentical(count($result['2013-11-15']), 1);
        $this->assertIdentical($result['2013-11-16'], array());

        $this->assertIdentical($result['2014-11-13'], array());
        $this->assertIdentical($result['2014-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2014-11-14']), 1);
        $this->assertIdentical(count($result['2014-11-15']), 1);
        $this->assertIdentical($result['2014-11-16'], array());

        $this->assertIdentical($result['2015-11-14'], array());
        $this->assertIdentical($result['2015-11-15'], array());
    }

    /**
     * test_freqDailyUntil
     *
     */
    function test_freqDailyUntil(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '11月16日まで',
                      'rrule_freq' => 'daily',
                      'rrule_until' => '2011-11-16 23:59:59',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');
        $this->assertIdentical(count($result), 30);
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 2);
        $this->assertIdentical(count($result['2011-11-16']), 2);

        // @memo UNTILはその時間までにDTSTARTが含まれるイベントを表示させている
        // $this->assertIdentical($result['2011-11-17'], array());
        $this->assertIdentical(count($result['2011-11-17']), 1);
    }

    /**
     * test_freqWeeklyUntil
     *
     */
    function test_freqWeeklyUntil(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '11月25日まで',
                      'rrule_freq' => 'weekly',
                      'rrule_until' => '2011-11-25 23:59:59',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');
        $this->assertIdentical(count($result), 30);
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);

        $this->assertIdentical(count($result['2011-11-21']), 1);
        $this->assertIdentical(count($result['2011-11-22']), 1);

        $this->assertIdentical($result['2011-11-28'], array());
    }

    /**
     * test_freqYearlyUntil
     *
     */
    function test_freqYearlyUntil(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '2013年まで',
                      'rrule_freq' => 'yearly',
                      'rrule_until' => '2013-12-31 23:59:59'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2015-11-30');
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-13'], array());
        $this->assertIdentical($result['2012-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2012-11-14']), 1);
        $this->assertIdentical(count($result['2012-11-15']), 1);
        $this->assertIdentical($result['2012-11-16'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2013-11-14']), 1);
        $this->assertIdentical(count($result['2013-11-15']), 1);
        $this->assertIdentical($result['2013-11-16'], array());

        $this->assertIdentical($result['2014-11-14'], array());
        $this->assertIdentical($result['2014-11-15'], array());
    }

    /**
     * test_freqDailyCount5Interval2
     *
     * jpn:rrule_freq = 'daily'のときに正しくスケジュールが展開されること
     */
    function test_freqDailyCount5Interval2(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎日5回2回に1回',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 5,
                      'rrule_interval' => 2,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');

        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-22'][0]['uid'], $uid);
        $this->assertIdentical($result['2011-11-23'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-22']), 1);
        $this->assertIdentical(count($result['2011-11-23']), 1);
        $this->assertIdentical($result['2011-11-24'], array());
    }

    /**
     * test_freqWeeklyCount2Interval3
     *
     */
    function test_freqWeeklyCount2Interval3(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎週2回3回に1回',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 2,
                      'rrule_interval' => 3,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-12-31');
        $this->assertIdentical(count($result), 61);
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-11-21'], array());
        $this->assertIdentical($result['2011-11-28'], array());
        $this->assertIdentical($result['2011-12-05'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-12-05']), 1);
        $this->assertIdentical(count($result['2011-12-06']), 1);
        $this->assertIdentical($result['2011-12-12'], array());
    }

    /**
     * test_freqMonthlyCount3Interval2
     *
     */
    function test_freqMonthlyCount3Interval2(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎月3回2回に1回',
                      'rrule_freq' => 'monthly',
                      'rrule_count' => 3,
                      'rrule_interval' => 2,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2012-02-28');

        $this->assertIdentical(count($result), (30 + 31 + 31 + 28));
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-12-14'], array());
        $this->assertIdentical($result['2012-01-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2012-01-14']), 1);
        $this->assertIdentical(count($result['2012-01-15']), 1);
        $this->assertIdentical($result['2012-01-16'], array());
        $this->assertIdentical($result['2012-02-14'], array());
    }

    /**
     * test_freqYearlyCount3Interval2
     *
     */
    function test_freqYearlyCount3Interval2(){

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 12:00:00',
                      'summary' => '毎年3回2回に1回',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => 3,
                      'rrule_interval' => 2,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2015-11-30');
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-14'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2013-11-14']), 1);
        $this->assertIdentical(count($result['2013-11-15']), 1);
        $this->assertIdentical($result['2013-11-16'], array());

        $this->assertIdentical($result['2014-11-14'], array());

        $this->assertIdentical($result['2015-11-13'], array());
        $this->assertIdentical($result['2015-11-14'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2015-11-14']), 1);
        $this->assertIdentical(count($result['2015-11-15']), 1);
        $this->assertIdentical($result['2015-11-16'], array());
    }

    /**
     * test_freqWeeklyCount10ByDay
     *
     * RFC2445:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH
     * ==> (1997 9:00 AM EDT)September 2,4,9,11,16,18,23,25,30;October 2
     * Every other week on Monday, Wednesday and Friday until December 24,
     * 1997, but starting on Tuesday, September 2, 1997:
     *
     * jpn:
     */
    function test_freqWeeklyCount10ByDay(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'TU,TH'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-11-30');
        $this->assertIdentical($result['1997-09-02'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-04'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-09'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-11'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-18'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-23'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-25'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-02'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-07'], array());
        $this->assertIdentical($result['1997-10-09'], array());
    }

    /**
     * test_freqWeeklyUntilByDay
     *
     * RFC2445:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;UNTIL=19971224T000000Z;WKST=SU;
     * BYDAY=MO,WE,FR
     * ==> (1997 9:00 AM EDT)September 2,3,5,15,17,19,29;October
     * 1,3,13,15,17
     * (1997 9:00 AM EST)October 27,29,31;November 10,12,14,24,26,28;
     * December 8,10,12,22
     * Every other week on Tuesday and Thursday, for 8 occurrences:
     *
     * jpn:
     */
    function test_freqWeeklyUntilByDay(){
        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_until' => '1997-12-24 12:00:00',
                      'rrule_interval' => 2,
                      'rrule_byday' => 'MO,WE,FR'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
         $this->assertIdentical($result['1997-09-02'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-03'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-05'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-15'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-17'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-19'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-09-29'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-01'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-01'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-03'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-13'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-15'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-17'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-27'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-29'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-10-31'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-10'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-12'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-14'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-24'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-26'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-11-28'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-12-08'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-12-10'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-12-12'][0]['uid'], $uid);
        $this->assertIdentical($result['1997-12-22'][0]['uid'], $uid);
    }

    /**
     * test_findRange
     *
     * jpn:findByRange()で日付範囲分の配列が生成されること
     */
    function test_findByRange(){
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-10');
        $this->assertIdentical(count($result), 10);
    }

    /**
     * test_findRangeWithEvent
     *
     * jpn:findByRange()で日付範囲分の配列が生成され登録されているイベントがセットされていること
     */
    function test_findByRangeWithEvent(){
        $result = $this->Vevent->findByRange('2011-10-01', '2011-10-18');
        $this->assertIdentical(count($result), 18);
        $this->assertIdentical($result['2011-10-15'][0]['uid'], 'xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1');
        $this->assertIdentical($result['2011-10-16'][0]['uid'], 'xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1');
    }
}