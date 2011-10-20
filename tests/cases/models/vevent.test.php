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

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'yearlyなのにbymonthもbymonthdayもない',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'TH'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_byday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_checkByMonth
     *
     * @return
     */
    function test_checkByMonth(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_frewがdaily',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10,
                      'rrule_bymonth' => '2,4'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_bymonth',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
    }

    /**
     * test_checkByMonthDay
     *
     * @return
     */
    function test_checkByMonthDay(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_frewがdaily',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10,
                      'rrule_bymonthday' => '2,4'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_bymonthday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_frewがdaily',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => 10,
                      'rrule_bymonthday' => '2,4'
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('rrule_bymonthday',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);

        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'rrule_frewがdaily',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => 10,
                      'rrule_bymonth' => '1,6',
                      'rrule_bymonthday' => '2,4'
                      );
        $result = $this->Vevent->setEvent($data);
        $this->assertTrue($result);
    }


    /**
     * test_DropEvent
     *
     */
    function test_DropEvent(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-15 10:00:00',
                      'summary' => 'OK',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      'rrule_byday' => 'SU,MO'
                      );
        $uid = $this->Vevent->setEvent($data);

        $result = $this->Vevent->dropEvent($uid);
        $this->assertTrue($result);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 2);
        $this->assertIdentical($result['2011-11-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-11-23'][1]['Vevent']['event_start'], '2011-11-23 10:00:00');
        $this->assertIdentical($result['2011-11-23'][1]['Vevent']['event_end'], '2011-11-24 00:00:00');
        $this->assertIdentical($result['2011-11-24'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-11-24'][0]['Vevent']['event_start'], '2011-11-24 00:00:00');
        $this->assertIdentical($result['2011-11-24'][0]['Vevent']['event_end'], '2011-11-24 12:00:00');
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-11-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-21']), 1);
        $this->assertIdentical(count($result['2011-11-22']), 1);
        $this->assertIdentical($result['2011-11-28'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-12-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-12-14']), 1);
        $this->assertIdentical(count($result['2011-12-15']), 1);
        $this->assertIdentical($result['2011-12-16'], array());
        $this->assertIdentical($result['2012-01-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-13'], array());
        $this->assertIdentical($result['2012-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2012-11-14']), 1);
        $this->assertIdentical(count($result['2012-11-15']), 1);
        $this->assertIdentical($result['2012-11-16'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2013-11-14']), 1);
        $this->assertIdentical(count($result['2013-11-15']), 1);
        $this->assertIdentical($result['2013-11-16'], array());

        $this->assertIdentical($result['2014-11-13'], array());
        $this->assertIdentical($result['2014-11-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-13'], array());
        $this->assertIdentical($result['2012-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2012-11-14']), 1);
        $this->assertIdentical(count($result['2012-11-15']), 1);
        $this->assertIdentical($result['2012-11-16'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-22'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-11-23'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-11-21'], array());
        $this->assertIdentical($result['2011-11-28'], array());
        $this->assertIdentical($result['2011-12-05'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());
        $this->assertIdentical($result['2011-12-14'], array());
        $this->assertIdentical($result['2012-01-14'][0]['Vevent']['uid'], $uid);
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
        $this->assertIdentical($result['2011-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-14']), 1);
        $this->assertIdentical(count($result['2011-11-15']), 1);
        $this->assertIdentical($result['2011-11-16'], array());

        $this->assertIdentical($result['2012-11-14'], array());

        $this->assertIdentical($result['2013-11-13'], array());
        $this->assertIdentical($result['2013-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2013-11-14']), 1);
        $this->assertIdentical(count($result['2013-11-15']), 1);
        $this->assertIdentical($result['2013-11-16'], array());

        $this->assertIdentical($result['2014-11-14'], array());

        $this->assertIdentical($result['2015-11-13'], array());
        $this->assertIdentical($result['2015-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical(count($result['2015-11-14']), 1);
        $this->assertIdentical(count($result['2015-11-15']), 1);
        $this->assertIdentical($result['2015-11-16'], array());
    }

    /**
     * test_freqWeeklyBydayMoFr
     *
     */
    function test_freqWeeklyBydayMoFr(){
        $this->Vevent->dropEvent('xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1');

        $data = array(
                      'dtstart' => '2011-10-15 10:00:00',
                      'dtend' => '2011-10-15 12:00:00',
                      'summary' => 'Byday',
                      'rrule_freq' => 'weekly',
                      'rrule_byday' => 'MO,FR',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-10-01', '2011-10-31');

        $this->assertIdentical(count($result), 31);
        $this->assertIdentical($result['2011-10-14'], array());
        $this->assertIdentical($result['2011-10-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-10-17'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-10-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-10-24'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2011-10-28'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_freqYearlyBymonth
     *
     */
    function test_freqYearlyBymonth(){
        $data = array(
                      'dtstart' => '1997-10-15 10:00:00',
                      'dtend' => '1997-10-15 12:00:00',
                      'summary' => 'bymonth',
                      'rrule_freq' => 'yearly',
                      'rrule_bymonth' => '2,3',
                      );
        $uid = $this->Vevent->setEvent($data);

        $result = $this->Vevent->findByRange('1997-01-01', '2000-12-31');
        $this->assertIdentical($result['1997-02-15'], array());
        $this->assertIdentical($result['1997-03-15'], array());
        $this->assertIdentical($result['1997-10-14'], array());
        $this->assertIdentical($result['1997-10-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-02-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-15'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_freqYearlyBymonth1_3BydayWe
     *
     */
    function test_freqYearlyBymonth1_3BydayWe(){
        $data = array(
                      'dtstart' => '1997-01-15 10:00:00',
                      'dtend' => '1997-01-15 12:00:00',
                      'summary' => 'bymonth',
                      'rrule_freq' => 'yearly',
                      'rrule_bymonth' => '1,3',
                      'rrule_byday' => 'WE',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-01-01', '2000-12-31');
        $this->assertIdentical($result['1997-01-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-01-22'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-01-29'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-05'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-19'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-26'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-07'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-28'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-25'][0]['Vevent']['uid'], $uid);
    }


    /**
     * test_dayLong
     *
     * jpn:終日判定
     */
    function test_dayLong(){
        $data = array(
                      'dtstart' => '2011-11-01',
                      'dtend' => '2011-11-02',
                      'summary' => 'Allday',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-10-01', '2011-11-30');

        $this->assertIdentical(count($result), 31 + 30);
        $this->assertIdentical($result['2011-10-31'], array());
        $this->assertIdentical($result['2011-11-01'][0]['Vevent']['uid'], $uid);
        $this->assertTrue($result['2011-11-01'][0]['Vevent']['daylong']);
        $this->assertIdentical($result['2011-11-02'], array());
    }

    /**
     * test_invalidDayLong
     *
     * jpn:終日チェック時に時間が00:00:00でない場合はfalse
     */
    function test_invalidDayLong(){
        $data = array(
                      'dtstart' => '2011-11-01 00:10:00',
                      'dtend' => '2011-11-02 00:00:00',
                      'summary' => 'Allday',
                      'daylong' => true,
                      );
        $result = $this->Vevent->setEvent($data);
        $expected = array('daylong',
                          );
        $this->assertIdentical(array_keys($this->Vevent->validationErrors), $expected);
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
        $this->assertIdentical($result['2011-10-15'][0]['Vevent']['uid'], 'xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1');
        $this->assertIdentical($result['2011-10-16'][0]['Vevent']['uid'], 'xxxxxxxx-xxxx-xxxx-xxxxxxxxxxx1');
    }

    /**
     * test_RFC2445freqDailyUntil
     *
     * RFC2445:
     * Daily until December 24, 1997:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=DAILY;UNTIL=19971224T000000Z
     * ==> (1997 9:00 AM EDT)September 2-30;October 1-25
     *     (1997 9:00 AM EST)October 26-31;November 1-30;December 1-23
     *
     * jpn:
     */
    function test_RFC2445freqDailyUntil(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'daily',
                      'rrule_until' => '1997-12-24 00:00:00',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-24'], array());
    }

    /**
     * test_RFC2445freqDailyInterval
     *
     * RFC2445:
     * Every other day - forever:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=DAILY;INTERVAL=2
     * ==> (1997 9:00 AM EDT)September2,4,6,8...24,26,28,30;
     *     October 2,4,6...20,22,24
     *    (1997 9:00 AM EST)October 26,28,30;November 1,3,5,7...25,27,29;
     *     Dec 1,3,...
     *
     * jpn:
     */
    function test_RFC2445freqDailyInterval(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'daily',
                      'rrule_interval' => 2,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31 23:59:59');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-31'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqDailyCount10
     *
     * RFC2445:
     * Daily for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=DAILY;COUNT=10
     * ==> (1997 9:00 AM EDT)September 2-11
     *
     * jpn:
     */
    function test_RFC2445freqDailyCount10(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'daily',
                      'rrule_count' => 10,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-11-30');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-03'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-12'], array());
    }


    /**
     * test_RFC2445freqDailyInterval10Count5
     *
     * RFC2445:
     * Every 10 days, 5 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=DAILY;INTERVAL=10;COUNT=5
     * ==> (1997 9:00 AM EDT)September 2,12,22;October 2,12
     *
     * jpn:
     */
    function test_RFC2445freqDailyInterval10Count5(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'daily',
                      'rrule_interval' => 10,
                      'rrule_count' => 5,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-22'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-12'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqYearlyUntil
     *
     * RFC2445:
     * Everyday in January, for 3 years:
     * DTSTART;TZID=US-Eastern:19980101T090000
     * RRULE:FREQ=YEARLY;UNTIL=20000131T090000Z;
     * BYMONTH=1;BYDAY=SU,MO,TU,WE,TH,FR,SA
     * or
     * RRULE:FREQ=DAILY;UNTIL=20000131T090000Z;BYMONTH=1
     * ==> (1998 9:00 AM EDT)January 1-31
     *    (1999 9:00 AM EDT)January 1-31
     *    (2000 9:00 AM EDT)January 1-31
     */
    function test_RFC2445freqYearlyUntil(){
    }

    /**
     * test_RFC2445freqWeeklyCount10
     *
     * RFC2445:
     * Weekly for 10 occurrences
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;COUNT=10
     *==> (1997 9:00 AM EDT)September 2,9,16,23,30;October 7,14,21
     *   (1997 9:00 AM EST)October 28;November 4
     */
    function test_RFC2445freqWeeklyCount10() {
        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 10,
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-07'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-28'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-11'], array());
    }

    /**
     * test_RFC2445freqWeeklyUntil1224
     *
     * RFC2445:
     * Weekly until December 24, 1997
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;UNTIL=19971224T000000Z
     * ==> (1997 9:00 AM EDT)September 2,9,16,23,30;October 7,14,21
     *     (1997 9:00 AM EST)October 28;November 4,11,18,25;
     *                       December 2,9,16,23
     */
    function test_RFC2445freqWeeklyUntil1224() {
        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_until' => '1997-12-24 00:00:00',
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
        $this->assertIdentical($result['1997-09-01'], array());
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-08'], array());
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-24'], array());
    }


    /**
     * test_RFC2445freqWeeklyInterval
     *
     * RFC2445:
     * Every other week - forever:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;WKST=SU
     * ==> (1997 9:00 AM EDT)September 2,16,30;October 14
     *     (1997 9:00 AM EST)October 28;November 11,25;December 9,23
     *     (1998 9:00 AM EST)January 6,20;February
     * ...
     *
     * jpn:
     */
    function test_RFC2445freqWeeklyInterval(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_interval' => 2
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1998-01-31');
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-28'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-25'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-09'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-06'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-01-20'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqWeeklyCount10Byday
     *
     * RFC2445:
     * Weekly on Tuesday and Thursday for 5 weeks:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;UNTIL=19971007T000000Z;WKST=SU;BYDAY=TU,TH
     * or
     * RRULE:FREQ=WEEKLY;COUNT=10;WKST=SU;BYDAY=TU,TH
     * ==> (1997 9:00 AM EDT)September 2,4,9,11,16,18,23,25,30;October 2
     *
     * jpn:
     */
    function test_RFC2445freqWeeklyCount10Byday(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_until' => '1997-10-07 00:00:00',
                      'rrule_byday' => 'TU,TH'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1998-01-31');
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-04'][0]['Vevent']['event_start'], '1997-09-04 09:00:00');
        $this->assertIdentical($result['1997-09-04'][0]['Vevent']['event_end'], '1997-09-04 12:00:00');
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['event_start'], '1997-09-09 09:00:00');
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['event_end'], '1997-09-09 12:00:00');
        $this->assertIdentical($result['1997-09-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-02'][0]['Vevent']['uid'], $uid);
    }


    /**
     * test_RFC2445freqWeeklyUntilByDay
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
    function test_RFC2445freqWeeklyUntilByDay(){
        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_until' => '1997-12-24 00:00:00',
                      'rrule_interval' => 2,
                      'rrule_byday' => 'MO,WE,FR'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1997-12-31');
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-03'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-05'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-17'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-19'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-29'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-01'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-01'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-03'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-13'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-15'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-17'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-27'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-29'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-31'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-24'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-26'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-28'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-08'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-12-22'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqWeeklyCount8Interval2ByDay
     *
     * RFC2445:
     * Every other week on Tuesday and Thursday, for 8 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;COUNT=8;WKST=SU;BYDAY=TU,TH
     * ==> (1997 9:00 AM EDT)September 2,4,16,18,30;October 2,14,16
     *
     * jpn:
     */
    function test_RFC2445freqWeeklyCount8Interval2ByDay(){

        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'weekly',
                      'rrule_count' => 8,
                      'rrule_interval' => 2,
                      'rrule_byday' => 'TU,TH'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1998-01-31');
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-16'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqMonthlyCount10BydayFirstFr
     *
     * RFC2445:
     * Monthly on the 1st Friday for ten occurrences:
     * DTSTART;TZID=US-Eastern:19970905T090000
     * RRULE:FREQ=MONTHLY;COUNT=10;BYDAY=1FR
     * ==> (1997 9:00 AM EDT)September 5;October 3
     *    (1997 9:00 AM EST)November 7;Dec 5
     *    (1998 9:00 AM EST)January 2;February 6;March 6;April 3
     *    (1998 9:00 AM EDT)May 1;June 5
     *
     */
    function test_RFC2445freqMonthlyCount10BydayFirstFr() {
    }

    /**
     * test_RFC2445freqMonthlyUntilBydayFirstFr
     *
     * RFC2445:
     * Monthly on the 1st Friday until December 24, 1997:
     * DTSTART;TZID=US-Eastern:19970905T090000
     * RRULE:FREQ=MONTHLY;UNTIL=19971224T000000Z;BYDAY=1FR
     * ==> (1997 9:00 AM EDT)September 5;October 3
     *     (1997 9:00 AM EST)November 7;December 5
     *
     */
    function test_RFC2445freqMonthlyUntilBydayFirstFr() {
    }

    /**
     * test_RFC2445freqMonthlyInterval2Count10Byday1Suminus1Su
     *
     * RFC2445:
     * Every other month on the 1st and last Sunday of the month for 10
     * occurrences:
     * DTSTART;TZID=US-Eastern:19970907T090000
     * RRULE:FREQ=MONTHLY;INTERVAL=2;COUNT=10;BYDAY=1SU,-1SU
     * ==> (1997 9:00 AM EDT)September 7,28
     *     (1997 9:00 AM EST)November 2,30
     *     (1998 9:00 AM EST)January 4,25;March 1,29
     *     (1998 9:00 AM EDT)May 3,31
     */
    function test_RFC2445freqMonthlyInterval2Count10Byday1Suminus1Su() {
    }

    /**
     * test_RFC2445freqMonthlyCount6BydayMinus2Mo
     *
     * RFC2445:
     * Monthly on the second to last Monday of the month for 6 months:
     * DTSTART;TZID=US-Eastern:19970922T090000
     * RRULE:FREQ=MONTHLY;COUNT=6;BYDAY=-2MO
     * ==> (1997 9:00 AM EDT)September 22;October 20
     *    (1997 9:00 AM EST)November 17;December 22
     *    (1998 9:00 AM EST)January 19;February 16
     */
    function test_RFC2445freqMonthlyCount6BydayMinus2Mo(){
    }

    /**
     * test_RFC2445freqMonthlyBymonthdayMinus3
     *
     * RFC2445:
     * Monthly on the third to the last day of the month, forever:
     * DTSTART;TZID=US-Eastern:19970928T090000
     * RRULE:FREQ=MONTHLY;BYMONTHDAY=-3
     * ==> (1997 9:00 AM EDT)September 28
     *   (1997 9:00 AM EST)October 29;November 28;December 29
     *   (1998 9:00 AM EST)January 29;February 26
     * ...
     */
    function test_RFC2445freqMonthlyBymonthdayMinus3() {
    }

    /**
     * test_RFC2445freqMonthlyCount10Bymonthday2_15
     *
     * RFC2445:
     * Monthly on the 2nd and 15th of the month for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=2,15
     * ==> (1997 9:00 AM EDT)September 2,15;October 2,15
     *     (1997 9:00 AM EST)November 2,15;December 2,15
     *     (1998 9:00 AM EST)January 2,15
     */
    function test_RFC2445freqMonthlyCount10Bymonthday2_15() {
    }

    /**
     * test_RFC2445freqMonthlyCount10Bymonthday1_Minus1
     *
     * RFC2445:
     * Monthly on the first and last day of the month for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970930T090000
     * RRULE:FREQ=MONTHLY;COUNT=10;BYMONTHDAY=1,-1
     * ==> (1997 9:00 AM EDT)September 30;October 1
     *     (1997 9:00 AM EST)October 31;November 1,30;December 1,31
     *     (1998 9:00 AM EST)January 1,31;February 1
     */
    function test_RFC2445freqMonthlyCount10Bymonthday1_Minus1() {
    }

    /**
     * test_RFC2445freqMonthlyInterval18Count10Bymonthday10_11_12_13_14_15
     *
     * RFC2445:
     * Every 18 months on the 10th thru 15th of the month for 10
     * occurrences:
     * DTSTART;TZID=US-Eastern:19970910T090000
     * RRULE:FREQ=MONTHLY;INTERVAL=18;COUNT=10;BYMONTHDAY=10,11,12,13,14,
     * 15
     * ==> (1997 9:00 AM EDT)September 10,11,12,13,14,15
     *     (1999 9:00 AM EST)March 10,11,12,13
     */
    function test_RFC2445freqMonthlyInterval18Count10Bymonthday10_11_12_13_14_15() {
    }

    /**
     * test_RFC2445freqMonthlyInterval2BydayTu
     *
     * RFC2445:
     * Every Tuesday, every other month:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=MONTHLY;INTERVAL=2;BYDAY=TU
     * ==> (1997 9:00 AM EDT)September 2,9,16,23,30
     *     (1997 9:00 AM EST)November 4,11,18,25
     *     (1998 9:00 AM EST)January 6,13,20,27;March 3,10,17,24,31
     * ...
     */
    function test_RFC2445freqMonthlyInterval2BydayTu() {
        $data = array(
                      'dtstart' => '1997-09-02 09:00:00',
                      'dtend' => '1997-09-02 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'monthly',
                      'rrule_interval' => 2,
                      'rrule_byday' => 'TU'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-09-01', '1998-01-31');
        $this->assertIdentical($result['1997-09-02'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-09'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-16'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-23'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-09-30'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-10-07'], array());
        $this->assertIdentical($result['1997-10-14'], array());
        $this->assertIdentical($result['1997-10-21'], array());
        $this->assertIdentical($result['1997-10-28'], array());
        $this->assertIdentical($result['1997-11-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-11-25'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqYearlyCount10Bymonth6_7
     *
     * RFC2445:
     * Yearly in June and July for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970610T090000
     * RRULE:FREQ=YEARLY;COUNT=10;BYMONTH=6,7
     * ==> (1997 9:00 AM EDT)June 10;July 10
     *     (1998 9:00 AM EDT)June 10;July 10
     *     (1999 9:00 AM EDT)June 10;July 10
     *     (2000 9:00 AM EDT)June 10;July 10
     *     (2001 9:00 AM EDT)June 10;July 10
     * Note: Since none of the BYDAY, BYMONTHDAY or BYYEARDAY components
     * are specified, the day is gotten from DTSTART
     */
    function test_RFC2445freqYearlyCount10Bymonth6_7() {
        $data = array(
                      'dtstart' => '1997-06-10 09:00:00',
                      'dtend' => '1997-06-10 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'yearly',
                      'rrule_count' => 10,
                      'rrule_bymonth' => '6,7'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-06-01', '2001-07-31');
        $this->assertIdentical($result['1997-06-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-06-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-07-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-06-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-07-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2000-06-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2000-07-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2001-06-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['2001-07-10'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqYearlyInterval2Count10Bymonth1_2_3
     *
     * RFC2445:
     * Every other year on January, February, and March for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970310T090000
     * RRULE:FREQ=YEARLY;INTERVAL=2;COUNT=10;BYMONTH=1,2,3
     * ==> (1997 9:00 AM EST)March 10
     *     (1999 9:00 AM EST)January 10;February 10;March 10
     *     (2001 9:00 AM EST)January 10;February 10;March 10
     *     (2003 9:00 AM EST)January 10;February 10;March 10
     */
    function test_RFC2445freqYearlyInterval2Count10Bymonth1_2_3() {
    }

    /**
     * test_RFC2445freqYearlyInterval3Count10Byyeary1_100_200
     *
     * RFC2445:
     * Every 3rd year on the 1st, 100th and 200th day for 10 occurrences:
     * DTSTART;TZID=US-Eastern:19970101T090000
     * RRULE:FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200
     * ==> (1997 9:00 AM EST)January 1
     *     (1997 9:00 AM EDT)April 10;July 19
     *     (2000 9:00 AM EST)January 1
     *     (2000 9:00 AM EDT)April 9;July 18
     *     (2003 9:00 AM EST)January 1
     *     (2003 9:00 AM EDT)April 10;July 19
     *     (2006 9:00 AM EST)January 1
     */
    function test_RFC2445freqYearlyInterval3Count10Byyeary1_100_200() {
    }

    /**
     * test_RFC2445freqYearlyByday20Mo
     *
     * RFC2445:
     * Every 20th Monday of the year, forever:
     * DTSTART;TZID=US-Eastern:19970519T090000
     * RRULE:FREQ=YEARLY;BYDAY=20MO
     * ==> (1997 9:00 AM EDT)May 19
     *     (1998 9:00 AM EDT)May 18
     *     (1999 9:00 AM EDT)May 17
     * ...
     */
    function test_RFC2445freqYearlyByday20Mo() {
    }

    /**
     * test_RFC2445freqYearlyByweekno20BydayMo
     *
     * RFC2445
     * Monday of week number 20 (where the default start of the week is
     * Monday), forever:
     * DTSTART;TZID=US-Eastern:19970512T090000
     * RRULE:FREQ=YEARLY;BYWEEKNO=20;BYDAY=MO
     * ==> (1997 9:00 AM EDT)May 12
     *     (1998 9:00 AM EDT)May 11
     *     (1999 9:00 AM EDT)May 17
     * ...
     */
    function test_RFC2445freqYearlyByweekno20BydayMo() {
    }

    /**
     * test_RFC2445freqYearlyBymonthBydayTh
     *
     * RFC2445:
     * Every Thursday in March, forever:
     * DTSTART;TZID=US-Eastern:19970313T090000
     * RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=TH
     * ==> (1997 9:00 AM EST)March 13,20,27
     *     (1998 9:00 AM EST)March 5,12,19,26
     *     (1999 9:00 AM EST)March 4,11,18,25
     * ...
     */
    function test_RFC2445freqYearlyBymonthBydayTh() {
         $data = array(
                      'dtstart' => '1997-03-13 09:00:00',
                      'dtend' => '1997-03-13 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'yearly',
                      'rrule_byday' => 'TH',
                      'rrule_bymonth' => '3'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-03-01', '1999-03-31');
        $this->assertIdentical($result['1997-03-13'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-20'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-03-27'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-05'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-19'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-03-26'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-03-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-03-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-03-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1999-03-25'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqYearlyBydaythBymonth6_7_8
     *
     * RFC2445:
     * Every Thursday, but only during June, July, and August, forever:
     * DTSTART;TZID=US-Eastern:19970605T090000
     * RRULE:FREQ=YEARLY;BYDAY=TH;BYMONTH=6,7,8
     * ==> (1997 9:00 AM EDT)June 5,12,19,26;July 3,10,17,24,31;
     *                 August 7,14,21,28
     *     (1998 9:00 AM EDT)June 4,11,18,25;July 2,9,16,23,30;
     *                 August 6,13,20,27
     *     (1999 9:00 AM EDT)June 3,10,17,24;July 1,8,15,22,29;
     *                 August 5,12,19,26
     * ...
     */
    function test_RFC2445freqYearlyBydaythBymonth6_7_8() {
         $data = array(
                      'dtstart' => '1997-06-05 09:00:00',
                      'dtend' => '1997-06-05 12:00:00',
                      'summary' => 'RFC2445',
                      'rrule_freq' => 'yearly',
                      'rrule_byday' => 'TH',
                      'rrule_bymonth' => '6,7,8'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('1997-06-01', '2000-08-31');
        $this->assertIdentical($result['1997-06-05'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-06-12'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-06-19'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-06-26'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-03'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-10'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-17'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-24'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-07-31'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-08-07'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-08-14'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-08-21'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1997-08-28'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-06-04'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-06-11'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-06-18'][0]['Vevent']['uid'], $uid);
        $this->assertIdentical($result['1998-06-25'][0]['Vevent']['uid'], $uid);

        $this->assertIdentical($result['1998-07-02'][0]['Vevent']['uid'], $uid);

        $this->assertIdentical($result['1998-08-06'][0]['Vevent']['uid'], $uid);

        $this->assertIdentical($result['1999-06-03'][0]['Vevent']['uid'], $uid);

        $this->assertIdentical($result['1999-07-01'][0]['Vevent']['uid'], $uid);
    }

    /**
     * test_RFC2445freqMonthlyBydayFrBymonthday13
     *
     * RFC2445:
     * Every Friday the 13th, forever:
     *
     * DTSTART;TZID=US-Eastern:19970902T090000
     * EXDATE;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13
     * ==> (1998 9:00 AM EST)February 13;March 13;November 13
     *     (1999 9:00 AM EDT)August 13
     *     (2000 9:00 AM EDT)October 13
     * ...
     */
    function test_RFC2445freqMonthlyBydayFrBymonthday13() {
    }

    /**
     * test_RFC2445freqMonthlyBydaySaBymonthDay7_8_9_10_11_12_13
     *
     * RFC2445:
     * The first Saturday that follows the first Sunday of the month,
     * forever:
     * DTSTART;TZID=US-Eastern:19970913T090000
     * RRULE:FREQ=MONTHLY;BYDAY=SA;BYMONTHDAY=7,8,9,10,11,12,13
     *
     * ==> (1997 9:00 AM EDT)September 13;October 11
     *     (1997 9:00 AM EST)November 8;December 13
     *     (1998 9:00 AM EST)January 10;February 7;March 7
     *     (1998 9:00 AM EDT)April 11;May 9;June 13...
     * ...
     */
    function test_RFC2445freqMonthlyBydaySaBymonthDay7_8_9_10_11_12_13() {
    }

    /**
     * test_RFC2445freqYearlyInterval4Bymonth11BydayTuBymonthday2_3_4
     *
     * RFC2445:
     * Every four years, the first Tuesday after a Monday in November,
     * forever (U.S. Presidential Election day):
     * DTSTART;TZID=US-Eastern:19961105T090000
     * RRULE:FREQ=YEARLY;INTERVAL=4;BYMONTH=11;BYDAY=TU;BYMONTHDAY=2,3,4,
     * 5,6,7,8
     * ==> (1996 9:00 AM EST)November 5
     *     (2000 9:00 AM EST)November 7
     *     (2004 9:00 AM EST)November 2
     * ...
     */
    function test_RFC2445freqYearlyInterval4Bymonth11BydayTuBymonthday2_3_4() {
    }

    /**
     * test_RFC2445freqMonthlyCount3BydayTu_WeBysetpos3
     *
     * RFC2445:
     * The 3rd instance into the month of one of Tuesday, Wednesday or
     * Thursday, for the next 3 months:
     * DTSTART;TZID=US-Eastern:19970904T090000
     * RRULE:FREQ=MONTHLY;COUNT=3;BYDAY=TU,WE,TH;BYSETPOS=3
     * ==> (1997 9:00 AM EDT)September 4;October 7
     *     (1997 9:00 AM EST)November 6
    */
    function test_RFC2445freqMonthlyCount3BydayTu_WeBysetpos3() {
    }

    /**
     * test_RFC2445freqMonthlyBydayMo_Tu_We_Th_FrBysetposMinus2
     *
     * RFC2445:
     * The 2nd to last weekday of the month:
     * DTSTART;TZID=US-Eastern:19970929T090000
     * RRULE:FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2
     * ==> (1997 9:00 AM EDT)September 29
     *     (1997 9:00 AM EST)October 30;November 27;December 30
     *     (1998 9:00 AM EST)January 29;February 26;March 30
     * ...
    */
    function test_RFC2445freqMonthlyBydayMo_Tu_We_Th_FrBysetposMinus2() {
    }

    /**
     * test_RFC2445freqHourlyInterval3Until
     *
     * RFC2445:
     * Every 3 hours from 9:00 AM to 5:00 PM on a specific day:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=HOURLY;INTERVAL=3;UNTIL=19970902T170000Z
     * ==> (September 2, 1997 EDT)09:00,12:00,15:00
    */
    function test_RFC2445freqHourlyInterval3Until() {
    }

    /**
     * test_RFC2445freqMinutelyInterval15Count6
     *
     * RFC2445:
     * Every 15 minutes for 6 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=MINUTELY;INTERVAL=15;COUNT=6
     * ==> (September 2, 1997 EDT)09:00,09:15,09:30,09:45,10:00,10:15
    */
    function test_RFC2445freqMinutelyInterval15Count6() {
    }

    /**
     * test_RFC2445freqMinutelyInterval90Count4
     *
     * RFC2445:
     * Every hour and a half for 4 occurrences:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=MINUTELY;INTERVAL=90;COUNT=4
     * ==> (September 2, 1997 EDT)09:00,10:30;12:00;13:30
    */
    function test_RFC2445freqMinutelyInterval90Count4() {
    }

    /**
     * test_RFC2445freqMinutelyInterval20Byhour9_10_11_12_13_14_15_16
     *
     * RFC2445:
     * Every 20 minutes from 9:00 AM to 4:40 PM every day:
     * DTSTART;TZID=US-Eastern:19970902T090000
     * RRULE:FREQ=DAILY;BYHOUR=9,10,11,12,13,14,15,16;BYMINUTE=0,20,40
     * or
     * RRULE:FREQ=MINUTELY;INTERVAL=20;BYHOUR=9,10,11,12,13,14,15,16
     * ==> (September 2, 1997 EDT)9:00,9:20,9:40,10:00,10:20,
     *     ... 16:00,16:20,16:40
     *     (September 3, 1997 EDT)9:00,9:20,9:40,10:00,10:20,
     *     ...16:00,16:20,16:40
     * ...
    */
    function test_RFC2445freqMinutelyInterval20Byhour9_10_11_12_13_14_15_16() {
    }

    /**
     * test_RFC2445freqWeeklyInterval2Count4BydayTu_SuWkstMo
     *
     * RFC2445:
     * An example where the days generated makes a difference because of
     * WKST:
     * DTSTART;TZID=US-Eastern:19970805T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=MO
     * ==> (1997 EDT)Aug 5,10,19,24
    */
    function test_RFC2445freqWeeklyInterval2Count4BydayTu_SuWkstMo() {
    }

    /**
     * test_RFC2445freqWeeklyInterval2Count4BydayTu_SuWkstSu
     *
     * RFC2445:
     * changing only WKST from MO to SU, yields different results...
     * DTSTART;TZID=US-Eastern:19970805T090000
     * RRULE:FREQ=WEEKLY;INTERVAL=2;COUNT=4;BYDAY=TU,SU;WKST=SU
     * ==> (1997 EDT)August 5,17,19,31
    */
    function test_RFC2445freqWeeklyInterval2Count4BydayTu_SuWkstSu() {
    }

}