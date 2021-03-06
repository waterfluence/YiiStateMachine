<?php
namespace waterfluence\Yii2StateMachine;

use yii\base\Event;

/**
 * An event raised when a state machine transitions from one state to another
 *
 * @author Charles Pick
 * @package packages.stateMachine
 */
class AStateTransition extends Event
{
    /**
     * The state the machine is transitioning from
     * @var AState
     */
    public $from;
    /**
     * The state the machine is transitioning to
     * @var AState
     */
    public $to;

    /**
     * Whether the event is valid and the transition should proceed
     * @var boolean
     */
    public $isValid = true;

    public function __construct($sender = null, $data = null)
    {
        $this->sender = $sender;
        $this->data = $data;

        parent::__construct();
    }
}
