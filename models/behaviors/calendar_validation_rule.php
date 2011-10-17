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

        if (strtotime($start) >= strtotime($end)) {
            return false;
        }
        return true;
    }

    /**
     * exclusiveRrule
     *
     * jpn: rrule_*の排他チェック
     * @return Boolean
     */
    function exclusiveRrule(&$model, $fields){
        if (!empty($model->data[$model->alias]['rrule_count'])
            && !empty($model->data[$model->alias]['rrule_until'])) {
            return false;
        }
        return true;
    }

    /**
     * checkByDay
     * rrule_bydayのチェック
     *
     * @param  &$model
     * @return
     */
    function checkByDay(&$model, $fields){
        $byday = explode(',', array_shift($fields));
        if (!empty($byday) && $model->data[$model->alias]['rrule_freq'] === 'daily') {
            return false;
        }
        foreach ($byday as $value) {
            if (!in_array($value, array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'))) {
                return false;
            }
        }
        return true;
    }
}