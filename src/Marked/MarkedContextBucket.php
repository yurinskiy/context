<?php

declare(strict_types=1);

namespace Yurinskiy\Context\Marked;

use Yurinskiy\Context\ContextBucket;
use Yurinskiy\Context\ContextInterface;

class MarkedContextBucket extends ContextBucket
{
    /**
     * @var list<class-string<ContextInterface>>
     */
    private array $marks;

    /**
     * @param list<class-string<ContextInterface>> $marks
     */
    public function __construct(array $marks, ContextInterface ...$contexts)
    {
        $this->marks = $marks;

        parent::__construct(...$contexts);
    }

    public function add(ContextInterface ...$contexts): self
    {
        foreach ($contexts as $context) {
            foreach ($this->marks as $mark) {
                if (is_a($context, $mark)) {
                    parent::add($context);
                }
            }
        }

        return $this;
    }
}
