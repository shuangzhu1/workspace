<?php
/**
 * Created by PhpStorm.
 * User: ykuang
 * Date: 16-12-05
 * Time: 上午10:14
 */

namespace Components\Rules\Coin;


interface RuleInterface
{

    /**
     * @return array
     */
    public function getBehaviors();

    /**
     * @return array
     */
    public function getActions();

    /**
     * @return array
     */
    public function getTerms();

    /**
     * @param int $behavior
     * @return boolean
     */
    public function behaviorCheck($behavior);

    /**
     * @param $user_id
     * @param $behavior
     * @return mixed
     */
    public function executeRule($user_id, $behavior);

    /**
     * @param $behavior
     * @return mixed
     */
    public function getRule($behavior);

    /**
     * @param int $behavior
     * @return string
     */
    public function getBehaviorName($behavior);

    /**
     * @param int $term
     * @return string
     */
    public function getTermName($term);

    /**
     * @param string $action
     * @return string
     */
    public function getActionName($action);

    /**
     * @param $behavior
     * @param $action
     * @param $value
     * @param $term
     * @return mixed
     */
    public function setRule($behavior, $action, $value, $term);

    /**
     * @param $user
     * @param RuleStructure $rule
     * @return mixed
     */
    public function writeLog($user, RuleStructure $rule);

    /**
     * @param string $action
     * @return boolean
     */
    public function actionCheck($action);

    /**
     * @param int $term
     * @return boolean
     */
    public function termCheck($term);
}