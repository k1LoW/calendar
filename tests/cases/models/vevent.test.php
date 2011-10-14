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
        $this->assertIdentical($result['2011-11-24'][0]['uid'], $uid);
        $this->assertIdentical($result['2011-11-25'][0]['uid'], $uid);
        $this->assertIdentical(count($result['2011-11-24']), 2);
        $this->assertIdentical(count($result['2011-11-25']), 1);
        $this->assertIdentical($result['2011-11-26'], array());
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