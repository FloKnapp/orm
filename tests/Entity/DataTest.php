<?php

namespace ORM\Test\Entity;

use Mockery\Mock;
use ORM\Entity;
use ORM\EntityManager;
use ORM\Test\Entity\Examples\Snake_Ucfirst;
use ORM\Test\Entity\Examples\StudlyCaps;

class DataTest extends TestCase
{

    public function testOnChangeGetCalled()
    {
        /** @var Mock|Entity $mock */
        $mock = \Mockery::mock(StudlyCaps::class)->makePartial();
        $mock->shouldReceive('onChange')->once()->with('someVar', null, 'foobar');

        $mock->someVar = 'foobar';
    }

    public function testStoresData()
    {
        $studlyCaps = new StudlyCaps();

        $studlyCaps->someVar = 'foobar';

        self::assertSame('foobar', $studlyCaps->someVar);
    }

    public function testShouldNotCallIfNotChanged()
    {
        /** @var Mock|Entity $mock */
        $mock = \Mockery::mock(StudlyCaps::class)->makePartial();
        $mock->someVar = 'foobar';

        $mock->shouldNotReceive('onChange');

        $mock->someVar = 'foobar';
    }

    public function testStoresDataInCorrectNamingScheme()
    {
        $studlyCaps                = new StudlyCaps();
        $emMock = \Mockery::mock(EntityManager::class);
        $emMock->shouldReceive('save')->once()->with($studlyCaps, [
            'some_var' => 'foobar'
        ]);

        $studlyCaps->someVar = 'foobar';
        $studlyCaps->save($emMock);
    }

    public function testDelegatesToSetter()
    {
        $mock = \Mockery::mock(StudlyCaps::class)->makePartial();
        $mock->shouldReceive('setAnotherVar')->once()->with('foobar');

        $mock->anotherVar = 'foobar';
    }

    public function testOnChangeWatchesDataChanges()
    {
        /** @var Mock|Entity $mock */
        $mock = \Mockery::mock(StudlyCaps::class)->makePartial();
        $mock->shouldNotReceive('onChange');

        $mock->anotherVar = 'foobar';
    }

    public function testReturnsAnotherVar()
    {
        $mock = \Mockery::mock(StudlyCaps::class)->makePartial();
        $mock->shouldReceive('getAnotherVar')->once()->andReturn('foobar');

        self::assertSame('foobar', $mock->anotherVar);
    }

    public function testUsesNamingSchemeMethods()
    {
        Entity::$namingSchemeMethods = 'snake_lower';
        $mock = \Mockery::mock(Snake_Ucfirst::class)->makePartial();
        $mock->shouldReceive('set_another_var')->once()->with('foobar');
        $mock->shouldReceive('get_another_var')->atLeast()->once();

        $mock->another_var = 'foobar';
    }

    public function testGetsInitialDataOverConstructor()
    {
        StudlyCaps::$namingSchemeColumn = 'snake_lower';
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);

        self::assertSame(42, $studlyCaps->id);
        self::assertSame('foobar', $studlyCaps->someVar);
    }

    public function testItIsNotDirtyAfterCreate()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);

        self::assertFalse($studlyCaps->isDirty());
    }

    public function testIsDirtyAfterChange()
    {
        $studlyCaps = new StudlyCaps();

        $studlyCaps->someVar = 'foobar';

        self::assertTrue($studlyCaps->isDirty());
    }

    public function testOnlyTheChangedColumnsAreDirty()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);

        $studlyCaps->someVar = 'foobaz';
        $studlyCaps->newVar = 'foobar';

        self::assertTrue($studlyCaps->isDirty('someVar'));
        self::assertTrue($studlyCaps->isDirty('newVar'));
        self::assertFalse($studlyCaps->isDirty('id'));
        self::assertFalse($studlyCaps->isDirty('nonExistingVar'));
    }

    public function testResetRestoresOriginalData()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);
        $studlyCaps->someVar = 'foobaz';
        $studlyCaps->newVar = 'foobar';

        $studlyCaps->reset();

        self::assertSame('foobar', $studlyCaps->someVar);
        self::assertNull($studlyCaps->newVar);
    }

    public function testResetRestoresSpecificData()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);
        $studlyCaps->someVar = 'foobaz';
        $studlyCaps->newVar = 'foobar';

        $studlyCaps->reset('someVar');

        self::assertSame('foobar', $studlyCaps->someVar);
        self::assertSame('foobar', $studlyCaps->newVar);
    }

    public function testResetDeletesData()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);
        $studlyCaps->someVar = 'foobaz';
        $studlyCaps->newVar = 'foobar';

        $studlyCaps->reset('newVar');

        self::assertSame('foobaz', $studlyCaps->someVar);
        self::assertNull($studlyCaps->newVar);
    }

    public function testIsDirtyWithNewOriginalData()
    {
        $studlyCaps = new StudlyCaps([
            'id' => 42,
            'some_var' => 'foobar'
        ]);

        $studlyCaps->setOriginalData([
            'id' => 42,
            'some_var' => 'foobaz'
        ]);

        self::assertTrue($studlyCaps->isDirty());
        self::assertFalse($studlyCaps->isDirty('id'));
        self::assertTrue($studlyCaps->isDirty('someVar'));
    }
}
