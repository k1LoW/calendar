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
     * test_freqDaily
     *
     */
    function test_freqDaily(){
        $data = array(
                      'dtstart' => '2011-11-14 10:00:00',
                      'dtend' => '2011-11-14 12:00:00',
                      'summary' => '毎日10回',
                      'rrule_freq' => 'daily',
                      'rrule_count' => '10'
                      );
        $uid = $this->Vevent->setEvent($data);
        $result = $this->Vevent->findByRange('2011-11-01', '2011-11-30');
        
        $this->assertIdentical($result['2011-11-13'], array());
        $this->assertIdentical($result['2011-11-14'][0]['uid'], $uid);
        $this->assertIdentical($result['2011-11-24'][0]['uid'], $uid);
        $this->assertIdentical($result['2011-11-25'], array());
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
}