<?php

declare(strict_types=1);

namespace Yurinskiy\Context;

class ContextBucket
{
    /**
     * @var array<class-string<ContextInterface>, list<ContextInterface>>
     */
    private array $contexts = [];

    public static function instance(ContextInterface ...$contexts): self
    {
        return new self(...$contexts);
    }

    public function __construct(ContextInterface ...$contexts)
    {
        $this->add(...$contexts);
    }

    public function add(ContextInterface ...$contexts): self
    {
        foreach ($contexts as $context) {
            $this->contexts[get_class($context)][] = $context;
        }

        return $this;
    }

    /**
     * @template T of ContextInterface
     *
     * @param class-string<T> $contextFqcn
     *
     * @return T|null
     */
    public function last(string $contextFqcn): ?ContextInterface
    {
        return isset($this->contexts[$contextFqcn]) ? end($this->contexts[$contextFqcn]) : null;
    }

    /**
     * @return array<class-string<ContextInterface>, list<ContextInterface>>
     */
    public function all(): array
    {
        return $this->contexts;
    }

    /**
     * @template T of ContextInterface
     *
     * @param class-string<T>|null $contextFqcn
     *
     * @return T[] Возвращает все контексты группированные по типам
     */
    public function group(?string $contextFqcn = null): array
    {
        if (null !== $contextFqcn) {
            return $this->contexts[$contextFqcn] ?? [];
        }

        return [];
    }

    /**
     * @param class-string<ContextInterface> $contextFqcn
     */
    public function has(string $contextFqcn): bool
    {
        return (bool) $this->last($contextFqcn);
    }

    /**
     * Removes all contexts of the given class.
     *
     * @param class-string<ContextInterface> $contextFqcn
     */
    public function withoutContexts(string $contextFqcn): ContextBucket
    {
        $cloned = clone $this;

        unset($cloned->contexts[$contextFqcn]);

        return $cloned;
    }

    /**
     * @return ContextInterface[] Возвращает все контексты в одномерном массиве
     */
    public function toFlatArray(): array
    {
        return array_merge(...array_values($this->contexts));
    }

    /**
     * @psalm-param \Closure(ContextInterface):bool $p
     */
    public function filter(\Closure $p): ContextBucket
    {
        $list = $this->toFlatArray();
        $list = array_filter($list, $p);

        return ContextBucket::instance(...$list);
    }
}
