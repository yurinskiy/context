<?php

declare(strict_types=1);

namespace Yurinskiy\Context\Tests;

use PHPUnit\Framework\TestCase;
use Yurinskiy\Context\ContextBucket;
use Yurinskiy\Context\ContextInterface;

class ContextBucketTest extends TestCase
{
    public function testInstanceCreatesBucket(): void
    {
        $context = new MockContext('test');
        $bucket = ContextBucket::instance($context);

        self::assertInstanceOf(ContextBucket::class, $bucket);
        self::assertSame($context, $bucket->last(MockContext::class));
    }

    public function testConstructorAddsContexts(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket($context1, $context2);

        self::assertSame($context2, $bucket->last(MockContext::class)); // last should be second
    }

    public function testAddMethod(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket();
        $bucket->add($context1);

        self::assertSame($context1, $bucket->last(MockContext::class));

        $bucket->add($context2);

        self::assertSame($context2, $bucket->last(MockContext::class));
    }

    public function testAddReturnsSelf(): void
    {
        $bucket = new ContextBucket();
        $result = $bucket->add(new MockContext('test'));

        self::assertSame($bucket, $result);
    }

    public function testLastReturnsNullWhenNoContext(): void
    {
        $bucket = new ContextBucket();

        self::assertNull($bucket->last(MockContext::class));
    }

    public function testLastReturnsLastAddedContext(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket($context1, $context2);

        self::assertSame($context2, $bucket->last(MockContext::class));
    }

    public function testAllReturnsAllContexts(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $all = $bucket->all();

        self::assertCount(2, $all); // 2 different classes
        self::assertContains($context1, $all[MockContext::class]);
        self::assertContains($context2, $all[MockContext::class]);
        self::assertContains($context3, $all[AnotherMockContext::class]);
    }

    public function testGroupReturnsAllOfSpecificType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $mockContexts = $bucket->group(MockContext::class);
        $anotherContexts = $bucket->group(AnotherMockContext::class);

        self::assertCount(2, $mockContexts);
        self::assertCount(1, $anotherContexts);
        self::assertContains($context1, $mockContexts);
        self::assertContains($context2, $mockContexts);
        self::assertContains($context3, $anotherContexts);
    }

    public function testGroupReturnsEmptyArrayForNonExistentType(): void
    {
        $bucket = new ContextBucket();

        $result = $bucket->group(MockContext::class);

        self::assertEmpty($result);
    }

    public function testHasReturnsTrueWhenContextExists(): void
    {
        $context = new MockContext('test');
        $bucket = new ContextBucket($context);

        self::assertTrue($bucket->has(MockContext::class));
    }

    public function testHasReturnsFalseWhenContextDoesNotExist(): void
    {
        $bucket = new ContextBucket();

        self::assertFalse($bucket->has(MockContext::class));
    }

    public function testHasReturnsFalseWhenContextIsNull(): void
    {
        // This test assumes that if last() returns null, has() returns false,
        // which is the current implementation
        $bucket = new ContextBucket();
        // The current implementation of has() relies on last() returning null
        // and casting that to bool (null -> false)
        self::assertFalse($bucket->has(MockContext::class));
    }

    public function testWithoutContextsReturnsNewBucketWithoutSpecifiedType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $originalBucket = new ContextBucket($context1, $context2, $context3);

        $newBucket = $originalBucket->withoutAll(MockContext::class);

        // Original should still have MockContext
        self::assertTrue($originalBucket->has(MockContext::class));
        self::assertTrue($originalBucket->has(AnotherMockContext::class));

        // New bucket should not have MockContext but should have AnotherMockContext
        self::assertFalse($newBucket->has(MockContext::class));
        self::assertTrue($newBucket->has(AnotherMockContext::class));
    }

    public function testToFlatArrayReturnsAllContextsInOneArray(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $flatArray = $bucket->toFlatArray();

        self::assertCount(3, $flatArray);
        self::assertContains($context1, $flatArray);
        self::assertContains($context2, $flatArray);
        self::assertContains($context3, $flatArray);
    }

    public function testFilterReturnsNewBucketWithFilteredContexts(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        // Filter to only MockContext instances
        $filteredBucket = $bucket->filter(static function (ContextInterface $ctx) {
            return $ctx instanceof MockContext;
        });

        $flatFiltered = $filteredBucket->toFlatArray();

        self::assertCount(2, $flatFiltered);
        foreach ($flatFiltered as $ctx) {
            self::assertInstanceOf(MockContext::class, $ctx);
        }
        self::assertFalse($filteredBucket->has(AnotherMockContext::class));
    }

    public function testFilterWithEmptyResult(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket($context1, $context2);

        // Filter to nothing
        $filteredBucket = $bucket->filter(static function (ContextInterface $ctx) {
            return false;
        });

        self::assertEmpty($filteredBucket->toFlatArray());
        self::assertNull($filteredBucket->last(MockContext::class));
    }

    public function testAddingMultipleContextsOfSameType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new MockContext('third');

        $bucket = new ContextBucket();
        $bucket->add($context1, $context2, $context3);

        $allMockContexts = $bucket->group(MockContext::class);
        self::assertCount(3, $allMockContexts);
        self::assertSame($context3, $bucket->last(MockContext::class));
    }

    public function testMultipleContextTypes(): void
    {
        $mockCtx = new MockContext('mock');
        $anotherCtx = new AnotherMockContext('another');

        $bucket = new ContextBucket($mockCtx, $anotherCtx);

        self::assertTrue($bucket->has(MockContext::class));
        self::assertTrue($bucket->has(AnotherMockContext::class));
        self::assertSame($mockCtx, $bucket->last(MockContext::class));
        self::assertSame($anotherCtx, $bucket->last(AnotherMockContext::class));
    }
}

// Mock-класс для интерфейса ContextInterface
class MockContext implements ContextInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

class AnotherMockContext implements ContextInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
