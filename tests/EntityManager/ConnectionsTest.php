<?php

namespace ORM\Test\EntityManager;

use ORM\DbConfig;
use ORM\EntityManager;
use ORM\Exceptions\InvalidConfiguration;
use ORM\Exceptions\NoConnection;
use ORM\Test\TestCase;

class ConnectionsTest extends TestCase
{
    public function testCallsSetConnectionForDefault()
    {
        $emMock = \Mockery::mock(EntityManager::class)->makePartial();
        $emMock->shouldReceive('setConnection')->with('default', 'something')->once();

        $emMock->__construct([
            EntityManager::OPT_DEFAULT_CONNECTION => 'something'
        ]);
    }

    public function testCallsSetConnectionForEveryConfig()
    {
        $emMock = \Mockery::mock(EntityManager::class)->makePartial();
        $emMock->shouldReceive('setConnection')->with('a', 'z')->once();
        $emMock->shouldReceive('setConnection')->with('b', 'y')->once();

        $emMock->__construct([
            EntityManager::OPT_CONNECTIONS => [
                'a' => 'z',
                'b' => 'y'
            ]
        ]);
    }

    public function testThrowsWhenOptConnectionsIsNotArray()
    {
        self::expectException(InvalidConfiguration::class);
        self::expectExceptionMessage('OPT_CONNECTIONS requires an array');

        new EntityManager([
            EntityManager::OPT_CONNECTIONS => 'foobar'
        ]);
    }

    public function testSetConnectionAcceptsOnlyCallableArrayDbConfig()
    {
        $em = new EntityManager();
        self::expectException(InvalidConfiguration::class);
        self::expectExceptionMessage(
            'Connection must be callable, DbConfig or an array of parameters for DbConfig::__constructor'
        );

        $em->setConnection('default', 'foobar');
    }

    public function testSetConnectionDoesNotCallGetter()
    {
        $em = new EntityManager();
        $mock = \Mockery::mock(\stdClass::class);
        $mock->shouldReceive('get')->never();

        $em->setConnection('default', [$mock, 'get']);
    }

    public function testGetConnectionCallsGetterAndThrows()
    {
        $em = new EntityManager();
        $mock = \Mockery::mock(\stdClass::class);
        $mock->shouldReceive('get')->once()->andReturn('foobar');
        $em->setConnection('default', [$mock, 'get']);
        self::expectException(NoConnection::class);
        self::expectExceptionMessage('Getter for default does not return PDO instance');

        $em->getConnection('default');
    }

    public function testGetConnectionCallsGetterAndReturnsTheResult()
    {
        $pdo = new \PDO('sqlite:///tmp/test.sqlite');
        $em = new EntityManager([
            EntityManager::OPT_DEFAULT_CONNECTION => function () use ($pdo) {
                return $pdo;
            }
        ]);

        $result = $em->getConnection('default');

        self::assertSame($pdo, $result);
    }

    public function testGetConnectionThrowsForUnknown()
    {
        $em = new EntityManager();
        self::expectException(NoConnection::class);
        self::expectExceptionMessage('Unknown database connection foobar');

        $em->getConnection('foobar');
    }

    public function testGetConnectionUsesConfiguredDbConfig()
    {
        $dbConfig = new DbConfig('sqlite', '/tmp/test.sqlite');
        $em = new EntityManager([
            EntityManager::OPT_DEFAULT_CONNECTION => $dbConfig
        ]);

        $pdo = $em->getConnection();

        self::assertSame('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        self::assertSame(
            ['0','main','/tmp/test.sqlite'],
            $pdo->query('PRAGMA DATABASE_LIST')->fetch(\PDO::FETCH_NUM)
        );
    }

    public function testConfigurationArray()
    {
        $em = new EntityManager([
            EntityManager::OPT_DEFAULT_CONNECTION => ['sqlite', '/tmp/test.sqlite', null, null, null, null, [
                \PDO::ATTR_CASE => \PDO::CASE_LOWER
            ]]
        ]);

        $pdo = $em->getConnection();

        self::assertSame('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        self::assertSame(
            ['0','main','/tmp/test.sqlite'],
            $pdo->query('PRAGMA DATABASE_LIST')->fetch(\PDO::FETCH_NUM)
        );
        self::assertSame(\PDO::CASE_LOWER, $pdo->getAttribute(\PDO::ATTR_CASE));
    }
}
