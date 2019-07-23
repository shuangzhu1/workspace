<?php
/**
 * Created by PhpStorm.
 * User:ykuang
 * Date: 16-11-29
 * Time: 上午10:16
 */

namespace Components\Rules\Point;


use Phalcon\Mvc\User\Plugin;

abstract class AbstractRule extends Plugin implements RuleInterface
{

    /**
     * term list
     */
    const TERM_EVERY_BEHAVIOR = 9000;  //每次
    const TERM_ONCE_A_DAY = 9001;  //一天一次
    const TERM_ONLY_ONE = 9003;  //一次
    const TERM_DAY_LIMIT = 9004;  //每天有次数限制


    /**
     * point type list
     */
    const ACTION_UP = 'in';
    const ACTION_DOWN = 'out';

    public $termNameMap = array(
        self::TERM_EVERY_BEHAVIOR => "每次发生动作都执行",
        self::TERM_ONCE_A_DAY => "每天只执行一次",
        self::TERM_ONLY_ONE => "总共只执行一次",
        self::TERM_DAY_LIMIT => "每天有次数限制"
    );

    public $actionNameMap = array(
        self::ACTION_UP => "增加经验值",
        self::ACTION_DOWN => "减少经验值"
    );

    /**
     * @var number
     */
    public $customer_id = null;
    public $vip_grade = 0;

    public function __construct()
    {
        $this->customer_id = 0;
        $this->vip_grade = 0;
    }

    public function getBehaviorName($behavior)
    {
        if (static::$behaviorNameMap && array_key_exists($behavior, static::$behaviorNameMap)) {
            return static::$behaviorNameMap[$behavior];
        } else {
            return false;
        }
    }

    public function getTermName($term)
    {
        if (isset($this->termNameMap) && array_key_exists($term, $this->termNameMap)) {
            return $this->termNameMap[$term];
        } else {
            return false;
        }
    }

    public function getActionName($action)
    {
        if (isset($this->actionNameMap) && array_key_exists($action, $this->actionNameMap)) {
            return $this->actionNameMap[$action];
        } else {
            return false;
        }
    }

    public function getBehaviors()
    {
        $reflection = new \ReflectionClass(get_called_class());
        $constants = ($reflection->getConstants());

        $data = array();
        foreach ($constants as $key => $val) {
            if (strpos($key, 'BEHAVIOR') !== FALSE) {
                array_push($data, $val);
            }
        }
        return $data;
    }

    public function getActions()
    {
        $reflection = new \ReflectionClass(get_called_class());
        $constants = ($reflection->getConstants());

        $data = array();
        foreach ($constants as $key => $val) {
            if (strpos($key, 'ACTION') !== FALSE) {
                array_push($data, $val);
            }
        }
        return $data;
    }

    public function getTerms()
    {
        $reflection = new \ReflectionClass(get_called_class());
        $constants = ($reflection->getConstants());

        $data = array();
        foreach ($constants as $key => $val) {
            if (strpos($key, 'TERM') !== FALSE) {
                array_push($data, $val);
            }
        }
        return $data;
    }

    public function behaviorCheck($behavior)
    {
        $behaviors = $this->getBehaviors();
        if (in_array($behavior, $behaviors)) {
            return true;
        } else {
            return false;
        }
    }

    public function actionCheck($action)
    {
        $actions = $this->getActions();
        if (in_array($action, $actions)) {
            return true;
        } else {
            return false;
        }
    }

    public function termCheck($term)
    {
        $terms = $this->getTerms();
        if (in_array($term, $terms)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $user
     * @param RuleStructure $rule
     * @return mixed
     */
    abstract protected function checkLogUnique($user, RuleStructure $rule);

    /**
     * (non-PHPdoc)
     * @see Components\RuleManage\RuleInterface::writeLog()
     */
    abstract public function writeLog($user, RuleStructure $rule);

    /**
     * (non-PHPdoc)
     * @see Components\RuleManage\RuleInterface::executeRule()
     */
    abstract public function executeRule($user, $behavior);

    /**
     * (non-PHPdoc)
     * @see Components\RuleManage\RuleInterface::setRule()
     */
    abstract public function setRule($behavior, $action, $value, $term);

    /**
     * (non-PHPdoc)
     * @see Components\RuleManage\RuleInterface::getRule()
     */
    abstract public function getRule($behavior);
}