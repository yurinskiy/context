<?php

namespace Yurinskiy\Context\Tests;

use Yurinskiy\Context\ContextBucket;
use Yurinskiy\Context\ContextInterface;
use PHPUnit\Framework\TestCase;

class ContextBucketTest extends TestCase
{
    public function testInstanceCreatesBucket(): void
    {
        $context = new MockContext('test');
        $bucket = ContextBucket::instance($context);

        $this->assertInstanceOf(ContextBucket::class, $bucket);
        $this->assertSame($context, $bucket->last(MockContext::class));
    }

    public function testConstructorAddsContexts(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket($context1, $context2);

        $this->assertSame($context2, $bucket->last(MockContext::class)); // last should be second
    }

    public function testAddMethod(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket();
        $bucket->add($context1);

        $this->assertSame($context1, $bucket->last(MockContext::class));

        $bucket->add($context2);

        $this->assertSame($context2, $bucket->last(MockContext::class));
    }

    public function testAddReturnsSelf(): void
    {
        $bucket = new ContextBucket();
        $result = $bucket->add(new MockContext('test'));

        $this->assertSame($bucket, $result);
    }

    public function testLastReturnsNullWhenNoContext(): void
    {
        $bucket = new ContextBucket();

        $this->assertNull($bucket->last(MockContext::class));
    }

    public function testLastReturnsLastAddedContext(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');

        $bucket = new ContextBucket($context1, $context2);

        $this->assertSame($context2, $bucket->last(MockContext::class));
    }

    public function testAllReturnsAllContexts(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $all = $bucket->all();

        $this->assertCount(2, $all); // 2 different classes
        $this->assertContains($context1, $all[MockContext::class]);
        $this->assertContains($context2, $all[MockContext::class]);
        $this->assertContains($context3, $all[AnotherMockContext::class]);
    }

    public function testGroupReturnsAllOfSpecificType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $mockContexts = $bucket->group(MockContext::class);
        $anotherContexts = $bucket->group(AnotherMockContext::class);

        $this->assertCount(2, $mockContexts);
        $this->assertCount(1, $anotherContexts);
        $this->assertContains($context1, $mockContexts);
        $this->assertContains($context2, $mockContexts);
        $this->assertContains($context3, $anotherContexts);
    }

    public function testGroupReturnsEmptyArrayForNonExistentType(): void
    {
        $bucket = new ContextBucket();

        $result = $bucket->group(MockContext::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGroupReturnsEmptyArrayWhenNullGiven(): void
    {
        $context = new MockContext('test');
        $bucket = new ContextBucket($context);

        $result = $bucket->group(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testHasReturnsTrueWhenContextExists(): void
    {
        $context = new MockContext('test');
        $bucket = new ContextBucket($context);

        $this->assertTrue($bucket->has(MockContext::class));
    }

    public function testHasReturnsFalseWhenContextDoesNotExist(): void
    {
        $bucket = new ContextBucket();

        $this->assertFalse($bucket->has(MockContext::class));
    }

    public function testHasReturnsFalseWhenContextIsNull(): void
    {
        // This test assumes that if last() returns null, has() returns false,
        // which is the current implementation
        $bucket = new ContextBucket();
        // The current implementation of has() relies on last() returning null
        // and casting that to bool (null -> false)
        $this->assertFalse($bucket->has(MockContext::class));
    }

    public function testWithoutContextsReturnsNewBucketWithoutSpecifiedType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $originalBucket = new ContextBucket($context1, $context2, $context3);

        $newBucket = $originalBucket->withoutContexts(MockContext::class);

        // Original should still have MockContext
        $this->assertTrue($originalBucket->has(MockContext::class));
        $this->assertTrue($originalBucket->has(AnotherMockContext::class));

        // New bucket should not have MockContext but should have AnotherMockContext
        $this->assertFalse($newBucket->has(MockContext::class));
        $this->assertTrue($newBucket->has(AnotherMockContext::class));
    }

    public function testToFlatArrayReturnsAllContextsInOneArray(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new AnotherMockContext('third');

        $bucket = new ContextBucket($context1, $context2, $context3);

        $flatArray = $bucket->toFlatArray();

        $this->assertCount(3, $flatArray);
        $this->assertContains($context1, $flatArray);
        $this->assertContains($context2, $flatArray);
        $this->assertContains($context3, $flatArray);
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

        $this->assertCount(2, $flatFiltered);
        foreach ($flatFiltered as $ctx) {
            $this->assertInstanceOf(MockContext::class, $ctx);
        }
        $this->assertFalse($filteredBucket->has(AnotherMockContext::class));
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

        $this->assertEmpty($filteredBucket->toFlatArray());
        $this->assertNull($filteredBucket->last(MockContext::class));
    }

    public function testAddingMultipleContextsOfSameType(): void
    {
        $context1 = new MockContext('first');
        $context2 = new MockContext('second');
        $context3 = new MockContext('third');

        $bucket = new ContextBucket();
        $bucket->add($context1, $context2, $context3);

        $allMockContexts = $bucket->group(MockContext::class);
        $this->assertCount(3, $allMockContexts);
        $this->assertSame($context3, $bucket->last(MockContext::class));
    }

    public function testMultipleContextTypes(): void
    {
        $mockCtx = new MockContext('mock');
        $anotherCtx = new AnotherMockContext('another');

        $bucket = new ContextBucket($mockCtx, $anotherCtx);

        $this->assertTrue($bucket->has(MockContext::class));
        $this->assertTrue($bucket->has(AnotherMockContext::class));
        $this->assertSame($mockCtx, $bucket->last(MockContext::class));
        $this->assertSame($anotherCtx, $bucket->last(AnotherMockContext::class));
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