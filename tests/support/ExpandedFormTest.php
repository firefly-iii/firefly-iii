<?php
use FireflyIII\Support\ExpandedForm;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use League\FactoryMuffin\Facade as FactoryMuffin;

/**
 * Class ExpandedFormTest
 */
class ExpandedFormTest extends TestCase
{

    /**
     * @var ExpandedForm
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new ExpandedForm;
        $user         = FactoryMuffin::create('FireflyIII\User');
        $this->be($user);
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::amount
     * @covers FireflyIII\Support\ExpandedForm::label
     * @covers FireflyIII\Support\ExpandedForm::expandOptionArray
     * @covers FireflyIII\Support\ExpandedForm::getHolderClasses
     * @covers FireflyIII\Support\ExpandedForm::fillFieldValue
     */
    public function testAmount()
    {
        $result = $this->object->amount('abcde', '12.23', ['label' => 'Some Label']);

        $this->assertTrue(str_contains($result, '23'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::balance
     * @covers FireflyIII\Support\ExpandedForm::label
     */
    public function testBalance()
    {
        $result = $this->object->balance('abcde', '12.23', []);

        $this->assertTrue(str_contains($result, '23'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::checkbox
     * @covers FireflyIII\Support\ExpandedForm::getHolderClasses
     */
    public function testCheckbox()
    {
        // add error to session for this thing:
        $errors = new MessageBag;
        $errors->add('abcde', 'Some error here.');
        $this->session(['errors' => $errors]);

        $result = $this->object->checkbox('abcde', 1, true);


        $this->assertTrue(str_contains($result, 'checked="checked"'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::date
     * @covers FireflyIII\Support\ExpandedForm::fillFieldValue
     */
    public function testDate()
    {
        $preFilled = [
            'abcde' => '1998-01-01'
        ];
        $this->session(['preFilled' => $preFilled]);

        $result = $this->object->date('abcde');

        $this->assertTrue(str_contains($result, '1998'));
        $this->assertTrue(str_contains($result, 'type="date"'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::integer
     */
    public function testInteger()
    {
        $result = $this->object->integer('abcde', 12345);

        $this->assertTrue(str_contains($result, '12345'));
        $this->assertTrue(str_contains($result, 'type="number"'));
        $this->assertTrue(str_contains($result, 'step="1"'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::location
     */
    public function testLocation()
    {
        $result = $this->object->location('abcde');

        $this->assertTrue(str_contains($result, 'id="clearLocation"'));
        $this->assertTrue(str_contains($result, 'id="map-canvas"'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::makeSelectList
     */
    public function testMakeSelectList()
    {
        $collection = new Collection;
        for ($i = 0; $i < 5; $i++) {
            $collection->push(FactoryMuffin::create('FireflyIII\Models\Account'));
        }
        $result = $this->object->makeSelectList($collection, true);

        $this->assertCount(6, $result);
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::multiRadio
     */
    public function testMultiRadio()
    {
        $list = [
            'some'  => 'BlaBla',
            'other' => 'ThisIsCool'
        ];

        $result = $this->object->multiRadio('abcde', $list);

        $this->assertTrue(str_contains($result, 'ThisIsCool'));
        $this->assertTrue(str_contains($result, 'other'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::optionsList
     */
    public function testOptionsList()
    {
        $result = $this->object->optionsList('update', 'MotorCycle');
        $this->assertTrue(str_contains($result, 'MotorCycle'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::select
     */
    public function testSelect()
    {
        $list = [
            'some'  => 'BlaBla',
            'other' => 'ThisIsCool'
        ];

        $result = $this->object->select('abcde', $list);
        $this->assertTrue(str_contains($result, 'ThisIsCool'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::tags
     */
    public function testTags()
    {
        $result = $this->object->tags('abcde', 'some,tags');
        $this->assertTrue(str_contains($result, 'data-role="tagsinput"'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::text
     */
    public function testText()
    {
        $result = $this->object->text('abcde', 'MotorBike!');
        $this->assertTrue(str_contains($result, 'MotorBike!'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }

    /**
     * @covers FireflyIII\Support\ExpandedForm::textarea
     */
    public function testTextarea()
    {
        $result = $this->object->textarea('abcde', 'Pillow fight!');
        $this->assertTrue(str_contains($result, 'Pillow fight!'));
        $this->assertTrue(str_contains($result, 'abcde_holder'));
    }
}
