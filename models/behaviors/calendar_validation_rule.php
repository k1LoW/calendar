<?php
class CalendarValidationRuleBehavior extends ModelBehavior {

    /**
     * checkEventDate
     *
     * jpn:開始日と終了日の順番のチェックする
     * @return Boolean
     */
    function checkEventDate(&$model, $fields){
        if (empty($model->data[$model->alias]['dtstart'])
            || empty($model->data[$model->alias]['dtend'])) {
            return false;
        }
        $start = $model->data[$model->alias]['dtstart'];
        $end = $model->data[$model->alias]['dtend'];

        if (strtotime($start) > strtotime($end)) {
            return false;
        }
        return true;
    }

    /**
     * checkFreq
     *
     * jpn: rrule_freqに設定される値チェック
     * @return Boolean
     */
    function checkFreq(&$model, $fields){
        
    }
}