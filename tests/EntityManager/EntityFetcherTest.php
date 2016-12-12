<?php

namespace ORM\Test\EntityManager;

use Mockery\Mock;
use ORM\EntityFetcher;
use ORM\Exceptions\NotJoined;
use ORM\Test\Entity\Examples\ContactPhone;
use ORM\Test\Entity\Examples\StaticTableName;
use ORM\Test\Entity\Examples\StudlyCaps;
use ORM\Test\TestCase;

class EntityFetcherTest extends TestCase
{
    public function testRunsQueryWithoutParameters()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);

        $this->pdo->shouldReceive('query')->once()->with('SELECT t0.* FROM contact_phone AS t0');

        $fetcher->one();
    }

    public function testReturnsNullWhenQueryFails()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);
        $this->pdo->shouldReceive('query')->andReturn(false);

        $result = $fetcher->one();

        self::assertNull($result);
    }

    public function testReturnsNullWhenResultIsEmpty()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->andReturn(false);

        $result = $fetcher->one();

        self::assertNull($result);
    }

    public function testExecutesQueryOnce()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);

        $this->pdo->shouldReceive('query')->once()
            ->with('SELECT t0.* FROM contact_phone AS t0')
            ->andReturn(false);

        $fetcher->one();
        $fetcher->one();
    }

    public function testUsesSpecifiedQuery()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);
        $this->pdo->shouldReceive('query')->once()
            ->with('SELECT * FROM contact_phone WHERE id = 42 AND name = \'mobile\'')
            ->andReturn(false);

        $fetcher->setQuery('SELECT * FROM contact_phone WHERE id = 42 AND name = \'mobile\'');
        $fetcher->one();
    }

    public function testReplacesQuestionmarksWithQuotedValue()
    {
        $fetcher = new EntityFetcher($this->em, ContactPhone::class);
        $this->pdo->shouldReceive('query')->once()
                  ->with('SELECT * FROM contact_phone WHERE id = 42 AND name = \'mobile\'')
                  ->andReturn(false);
        $this->em->shouldReceive('convertValue')->once()->with(42, 'default')->andReturn('42');
        $this->em->shouldReceive('convertValue')->once()->with('mobile', 'default')->andReturn('\'mobile\'');

        $fetcher->setQuery('SELECT * FROM contact_phone WHERE id = ? AND name = ?', [42, 'mobile']);
        $fetcher->one();
    }

    public function testReturnsAnEntity()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->once()->with(\PDO::FETCH_ASSOC)->andReturn([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+49 151 00000000'
        ]);

        $contactPhone = $fetcher->one();

        self::assertInstanceOf(ContactPhone::class, $contactPhone);
    }

    public function testReturnsPreviouslyMapped()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $this->em->map($e1);

        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->andReturn([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+49 151 00000000'
        ]);

        $contactPhone = $fetcher->one();

        self::assertSame($e1, $contactPhone);
    }

    public function testUpdatesOriginalData()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+41 160 21305919'
        ], true);
        $this->em->map($e1);
        $e1->number = '+49 151 00000000';

        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->andReturn([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+49 151 00000000'
        ]);

        $contactPhone = $fetcher->one();

        self::assertFalse($contactPhone->isDirty());
    }

    public function testResetsData()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $this->em->map($e1);

        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->andReturn([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+49 151 00000000'
        ]);

        $contactPhone = $fetcher->one();

        self::assertFalse($contactPhone->isDirty());
        self::assertSame('+49 151 00000000', $contactPhone->number);
    }

    public function testResetsOnlyNonDirty()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $this->em->map($e1);
        $e1->number = '+41 160 23142312';

        $fetcher = $this->em->fetch(ContactPhone::class);
        $statement = \Mockery::mock(\PDOStatement::class);
        $this->pdo->shouldReceive('query')->andReturn($statement);
        $statement->shouldReceive('fetch')->andReturn([
            'id' => 42,
            'name' => 'mobile',
            'number' => '+49 151 00000000'
        ]);

        $contactPhone = $fetcher->one();

        self::assertTrue($contactPhone->isDirty());
        self::assertSame('+41 160 23142312', $contactPhone->number);

        $contactPhone->reset('number');

        self::assertSame('+49 151 00000000', $contactPhone->number);
    }

    public function testAllReturnsEmptyArray()
    {
        /** @var EntityFetcher|Mock $fetcher */
        $fetcher = \Mockery::mock(EntityFetcher::class, [$this->em, ContactPhone::class])->makePartial();
        $fetcher->shouldReceive('one')->once()->andReturn(null);

        $contactPhones = $fetcher->all();

        self::assertSame([], $contactPhones);
    }

    public function testAllReturnsArrayWithAllEntities()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $e2 = new ContactPhone([
            'id' => 43,
            'name' => 'mobile'
        ], true);
        $e3 = new ContactPhone([
            'id' => 44,
            'name' => 'mobile'
        ], true);

        /** @var EntityFetcher|Mock $fetcher */
        $fetcher = \Mockery::mock(EntityFetcher::class, [$this->em, ContactPhone::class])->makePartial();
        $fetcher->shouldReceive('one')->times(4)->andReturn($e1, $e2, $e3, null);

        $contactPhones = $fetcher->all();

        self::assertSame([
            $e1,
            $e2,
            $e3
        ], $contactPhones);
    }

    public function testAllReturnsRemainingEntities()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $e2 = new ContactPhone([
            'id' => 43,
            'name' => 'mobile'
        ], true);
        $e3 = new ContactPhone([
            'id' => 44,
            'name' => 'mobile'
        ], true);

        /** @var EntityFetcher|Mock $fetcher */
        $fetcher = \Mockery::mock(EntityFetcher::class, [$this->em, ContactPhone::class])->makePartial();
        $fetcher->shouldReceive('one')->times(4)->andReturn($e1, $e2, $e3, null);

        $first = $fetcher->one();

        $contactPhones = $fetcher->all();

        self::assertSame([
            $e2,
            $e3
        ], $contactPhones);
    }

    public function testAllReturnsLimitedAmount()
    {
        $e1 = new ContactPhone([
            'id' => 42,
            'name' => 'mobile'
        ], true);
        $e2 = new ContactPhone([
            'id' => 43,
            'name' => 'mobile'
        ], true);
        $e3 = new ContactPhone([
            'id' => 44,
            'name' => 'mobile'
        ], true);

        /** @var EntityFetcher|Mock $fetcher */
        $fetcher = \Mockery::mock(EntityFetcher::class, [$this->em, ContactPhone::class])->makePartial();
        $fetcher->shouldReceive('one')->twice()->andReturn($e1, $e2, $e3, null);

        $contactPhones = $fetcher->all(2);

        self::assertSame([
            $e1,
            $e2
        ], $contactPhones);
    }

    public function testColumnsCantBeChanged()
    {
        $fetcher = $this->em->fetch(ContactPhone::class);
        $fetcher->columns(['a', 'b']);
        $fetcher->column('c');

        self::assertSame('SELECT t0.* FROM contact_phone AS t0', $fetcher->getQuery());
    }

    public function provideJoins()
    {
        return [
            ['join', 'JOIN'],
            ['leftJoin', 'LEFT JOIN'],
            ['rightJoin', 'RIGHT JOIN'],
            ['fullJoin', 'FULL JOIN']
        ];
    }

    /**
     * @dataProvider provideJoins
     */
    public function testJoinsGetAliasAutomatically($method, $sql)
    {
        $fetcher = $this->em->fetch(ContactPhone::class);

        call_user_func([$fetcher, $method], StudlyCaps::class, 't0.a = t1.b');

        self::assertSame(
            'SELECT t0.* FROM contact_phone AS t0 ' . $sql . ' studly_caps AS t1 ON t0.a = t1.b',
            $fetcher->getQuery()
        );
    }

    public function testTranslatesColumnNames()
    {
        $fetcher = $this->em->fetch(StaticTableName::class);

        $fetcher->where('id', 23);

        self::assertSame('SELECT t0.* FROM my_table AS t0 WHERE t0.stn_id = 23', $fetcher->getQuery());
    }

    public function testTranslatesClassNames()
    {
        $fetcher = $this->em->fetch(StaticTableName::class);

        $fetcher->where(StaticTableName::class . '::id', 23);

        self::assertSame('SELECT t0.* FROM my_table AS t0 WHERE t0.stn_id = 23', $fetcher->getQuery());
    }

    public function testThrowsWhenClassIsNotJoined()
    {
        $fetcher = $this->em->fetch(StaticTableName::class);

        self::expectException(NotJoined::class);
        self::expectExceptionMessage("Class " . ContactPhone::class . " not joined");

        $fetcher->where(ContactPhone::class . '::id', 23);
    }

    public function testThrowsWhenAliasUnknown()
    {
        $fetcher = $this->em->fetch(StaticTableName::class);

        self::expectException(NotJoined::class);
        self::expectExceptionMessage("Alias foobar unknown");

        $fetcher->where('foobar.id', 23);
    }

    public function testKnowsAliasesInParenthesis()
    {
        $fetcher = $this->em->fetch(StaticTableName::class);

        $fetcher->parenthesis()->where('id = 23')->close();

        self::assertSame('SELECT t0.* FROM my_table AS t0 WHERE (t0.stn_id = 23)', $fetcher->getQuery());
    }
}
