<?php
namespace waterfluence\Yii2StateMachine\tests;

use waterfluence\Yii2StateMachine\AState;
use waterfluence\Yii2StateMachine\AStateMachine;
use waterfluence\Yii2StateMachine\AStateTransition;
use yii\base\Component;

/**
 * Tests for the {@AStateMachine} class
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class AStateMachineTest extends TestCase
{
    /**
     * Tests the state machine magic methods
     */
    public function testMagicMethods()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
                            new ExampleEnabledState($machine, ['name' => 'enabled']),
                            new ExampleDisabledState($machine, ['name' => 'disabled']),
                            ));
        $machine->defaultStateName = "enabled";
        $this->assertTrue($machine->is("enabled"));
        $this->assertFalse($machine->is("disabled"));
        $this->assertTrue($machine->isEnabled);
        $this->assertTrue(isset($machine->testProperty));
        $machine->disable();
        $this->assertTrue($machine->is("disabled"));
        $this->assertFalse($machine->is("enabled"));
        $this->assertFalse($machine->isEnabled);
        $this->assertFalse(isset($machine->testProperty));
    }

    /**
     * Tests adding and removing states from a state machine
     */
    public function testAddRemoveStates()
    {
        $machine = new AStateMachine();

        $machine->addState(new ExampleEnabledState($machine, ['name' => 'enabled']));
        $this->assertFalse(isset($machine->testProperty));
        $machine->defaultStateName = "enabled";
        $this->assertTrue(isset($machine->testProperty));
        $machine->removeState("enabled");
        $this->assertFalse(isset($machine->testProperty));
        $this->assertNull($machine->getState());
    }

    /**
     * Tests the transition events
     */
    public function testTransitions()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
                            new ExampleEnabledState($machine, ['name' => 'enabled']),
                            new ExampleDisabledState($machine, ['name' => 'disabled']),
                            new ExampleIntermediateState($machine, ['name' => 'intermediate']),
                            ));
        $machine->defaultStateName = "enabled";
        $machine->enableTransitionHistory = true;
        $machine->maximumTransitionHistorySize = 2;
        $this->assertFalse($machine->transition("intermediate")); // intermediate state blocks transition from enabled -> intermediate
        $this->assertTrue($machine->transition("disabled"));
        $this->assertTrue($machine->transition("intermediate"));
        $this->assertEquals(2, count($machine->getTransitionHistory()));
        $this->assertTrue($machine->transition("intermediate")); // should work
        $this->assertEquals(2, count($machine->getTransitionHistory()));
    }

    public function testCanTransit()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
            new ExampleEnabledState($machine, ['name' => 'enabled']),
            new ExampleDisabledState($machine, ['name' => 'disabled']),
            new ExampleIntermediateState($machine, ['name' => 'intermediate']),
        ));
        $machine->defaultStateName = "enabled";

        $this->assertFalse($machine->canTransit("intermediate")); // intermediate state blocks transition from enabled -> intermediate

        $this->assertTrue($machine->canTransit("disabled"));
        $this->assertTrue($machine->transition("disabled"));

        $this->assertTrue($machine->canTransit("intermediate"));
        $this->assertTrue($machine->transition("intermediate")); // should work
    }

    public function testCanTransitWithTransitionsMapSpecified()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
            array(
                'name'=>'published',
                'transitsTo'=>'registration, canceled'
            ),
            array(
                'name'=>'registration',
                'transitsTo'=>'published, processing, canceled'
            ),
            array(
                'name'=>'processing',
                'transitsTo'=>'finished, canceled'
            ),
            array('name'=>'finished'),
            array('name'=>'canceled')
        ));
        $machine->defaultStateName = "published";
        $machine->checkTransitionMap = true;

        $this->assertFalse($machine->canTransit("processing"));
        $this->assertFalse($machine->canTransit("finished"));
        $this->assertFalse($machine->canTransit("published"));

        $this->assertTrue($machine->canTransit("registration"));
        $this->assertTrue($machine->canTransit("canceled"));

        $this->assertTrue($machine->transition("registration"));

        $this->assertFalse($machine->canTransit("finished"));
        $this->assertFalse($machine->canTransit("registration"));

        $this->assertTrue($machine->canTransit("published"));
        $this->assertTrue($machine->canTransit("processing"));
        $this->assertTrue($machine->canTransit("canceled"));

        $this->assertTrue($machine->transition("processing"));

        $this->assertFalse($machine->canTransit("processing"));
        $this->assertFalse($machine->canTransit("registration"));
        $this->assertFalse($machine->canTransit("published"));

        $this->assertTrue($machine->canTransit("finished"));
        $this->assertTrue($machine->canTransit("canceled"));

        $this->assertTrue($machine->transition("finished"));

        $this->assertFalse($machine->canTransit("finished"));
        $this->assertFalse($machine->canTransit("processing"));
        $this->assertFalse($machine->canTransit("registration"));
        $this->assertFalse($machine->canTransit("published"));
        $this->assertFalse($machine->canTransit("canceled"));
    }

    public function testGetAvailableStates()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
            array(
                'name'=>'not_saved',
                'transitsTo'=>'published'
            ),
            array(
                'name'=>'published',
                'transitsTo'=>'registration, canceled',
            ),
            array(
                'name'=>'registration',
                'transitsTo'=>'published, processing, canceled'
            ),
            array(
                'name'=>'processing',
                'transitsTo'=>'finished, canceled'
            ),
            array('name'=>'finished'),
            array('name'=>'canceled')
        ));
        $machine->checkTransitionMap = true;
        $machine->defaultStateName = 'not_saved';

        $this->checkStates(array('published'), $machine->availableStates);

        $machine->transition('published');
        $this->checkStates(array('registration', 'canceled'), $machine->availableStates);

        $machine->transition('registration');
        $this->checkStates(array('published', 'processing', 'canceled'), $machine->availableStates);

        $machine->transition('processing');
        $this->checkStates(array('finished', 'canceled'), $machine->availableStates);

        $machine->transition('finished');
        $this->checkStates(array(), $machine->availableStates);
    }

    protected function checkStates($shouldBeAvailable, $states)
    {
        $this->assertCount(count($shouldBeAvailable), $states);
        foreach ($shouldBeAvailable as $state)
            $this->assertContains($state, $states);
    }

    /**
     * Tests for the behavior functionality
     */
    public function testBehavior()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
                            new ExampleEnabledState($machine, ['name' => 'enabled']),
                            new ExampleDisabledState($machine, ['name' => 'disabled']),
                            ));
        $machine->defaultStateName = "enabled";

        $component = new Component();
        $component->attachBehavior("status", $machine);
        $this->assertTrue($component->is("enabled"));
        $this->assertFalse($component->is("disabled"));
        $this->assertTrue($component->demoMethod());
        $this->assertTrue($component->transition("disabled"));
        $this->assertTrue($component->is("disabled"));
        $this->assertFalse($component->is("enabled"));
    }

    /**
     * Tests that we can get the owner of the state machine
     */
    public function testThatWeCanGetOwnerOfTheMachine()
    {
        $machine = new AStateMachine();
        $machine->setStates(array(
            new ExampleEnabledState($machine, ['name' => 'enabled']),
            new ExampleDisabledState($machine, ['name' => 'disabled']),
        ));
        $machine->defaultStateName = "enabled";

        $component = new Component();
        $component->attachBehavior("status", $machine);

        $this->assertSame($component->getOwner(), $machine, "Call to AStateMachine::getOwner() failed.");
    }

    public function testEvents()
    {
        $machine = $this->getMock("phpnode\YiiStateMachine\AStateMachine", [ "onBeforeTransition", "onAfterTransition" ]);

        $enabled  = $this->getMock("phpnode\YiiStateMachine\AState", [ "onBeforeEnter", "onBeforeExit", "onAfterEnter", "onAfterExit" ], [ $machine, [ 'name' => 'enabled' ] ]);
        $disabled = $this->getMock("phpnode\YiiStateMachine\AState", [ "onBeforeEnter", "onBeforeExit", "onAfterEnter", "onAfterExit" ], [ $machine, [ 'name' => 'disabled' ] ]);

        $machine->setStates(array($enabled,$disabled));
        $machine->defaultStateName = "enabled";

        $params           = array("param"=>1, "param"=>2);
        $transition       = new AStateTransition($machine, $params);
        $transition->to   = $disabled;
        $transition->from = $enabled;

        $machine->expects($this->once())
            ->method("onBeforeTransition")
            ->with($transition);

        $machine->expects($this->once())
            ->method("onAfterTransition")
            ->with($transition);

        $enabledTransition = new AStateTransition($enabled);
        $enabledTransition->to = $disabled;
        $enabledTransition->from = $enabled;

        $enabled->expects($this->never())
            ->method("onBeforeEnter");
        $enabled->expects($this->never())
            ->method("onAfterEnter");
        $enabled->expects($this->once())
            ->method("onBeforeExit")
            ->with($enabledTransition);
        $enabled->expects($this->once())
            ->method("onAfterExit")
            ->with($enabledTransition);

        $disabledTransition = new AStateTransition($disabled);
        $disabledTransition->to = $disabled;
        $disabledTransition->from = $enabled;

        $disabled->expects($this->never())
            ->method("onBeforeExit");
        $disabled->expects($this->never())
             ->method("onAfterExit");
        $disabled->expects($this->once())
             ->method("onBeforeEnter")
             ->with($disabledTransition);
        $disabled->expects($this->once())
            ->method("onAfterEnter")
            ->with($disabledTransition);

        $this->assertTrue($machine->transition("disabled", $params));
    }
}

/**
 * An example of an enabled state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleEnabledState extends AState
{
    /**
     * An example of a state property
     * @var boolean
     */
    public $isEnabled = true;

    /**
     * An example of a state property
     * @var boolean
     */
    public $testProperty = true;

    /**
     * Sets the state to disabled
     */
    public function disable()
    {
        $this->_machine->transition("disabled");
    }

    public function demoMethod()
    {
        return true;
    }
}
/**
 * An example of a disabled state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleDisabledState extends AState
{
    /**
     * An example of a state property
     * @var boolean
     */
    public $isEnabled = false;
    /**
     * Sets the state to enabled
     */
    public function enable()
    {
        $this->_machine->transition("enabled");
    }
}

/**
 * An example of an intermediate state
 * @author Charles Pick
 * @package packages.stateMachine.tests
 */
class ExampleIntermediateState extends AState
{
    /**
     * An example of a state property
     * @var boolean
     */
    public $isEnabled = null;

    /**
     * Blocks the transition from enabled to intermediate
     * @param  AState  $fromState the state we're transitioning from
     * @return boolean whether the transition should continue
     */
    public function beforeEnter()
    {
        $fromState = $this->_machine->getState();
        if ($fromState->getName() == "enabled") {
            return false;
        }

        return parent::beforeEnter();
    }
}
