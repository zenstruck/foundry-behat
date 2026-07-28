<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat;

use Behat\Gherkin\Node\TableNode;

use function Zenstruck\Foundry\set;

/**
 * @internal
 *
 * @method list<array<string, mixed>>        getHash()
 * @method list<array<string, mixed>>        getColumnsHash()
 * @method list<string>                      getColumn(int $index)
 * @method list<list<mixed>>                 getRows()
 * @method list<mixed>                       getRow(int $index)
 * @method array<int, list<mixed>>           getTable()
 * @method array<string, string|list<mixed>> getRowsHash()
 */
final class FoundryTableNode extends TableNode
{
    /** @var array<array-key, int> */
    private array $maxLineLength = [];

    private FactoryShortNameResolver $factoryShortNameResolver; // @phpstan-ignore property.uninitialized
    private ObjectRegistry $objectRegistry; // @phpstan-ignore property.uninitialized

    /**
     * @param array<int, array<array-key, mixed>> $table keys of body rows are property names
     */
    public static function create(
        FactoryShortNameResolver $factoryShortNameResolver,
        ObjectRegistry $objectRegistry,
        array $table,
    ): static {
        // Bypass the TableNode constructor: it rejects non-scalar cell values, but this table
        // carries resolved objects. The constructor's other sanity checks already ran on the
        // original TableNode this one is built from. This is the single place where a
        // behat/gherkin internal (the private "table" property) is written to.
        $tableNode = (new \ReflectionClass(self::class))->newInstanceWithoutConstructor();
        set($tableNode, 'table', $table);

        $tableNode->factoryShortNameResolver = $factoryShortNameResolver;
        $tableNode->objectRegistry = $objectRegistry;

        return $tableNode;
    }

    public function getRowAsString($rowNum): string
    {
        $values = [];
        // body rows are keyed by property name: re-index to align with maxLineLengths()
        foreach (\array_values($this->getRow($rowNum)) as $column => $value) {
            $values[] = $this->padRight(' '.$this->getValueAsString($value).' ', $this->maxLineLengths()[$column] + 2);
        }

        return \sprintf('|%s|', \implode('|', $values));
    }

    public function getRowAsStringWithWrappedValues($rowNum, $wrapper): string
    {
        $values = [];
        foreach (\array_values($this->getRow($rowNum)) as $column => $value) {
            $value = $this->padRight(' '.$this->getValueAsString($value).' ', $this->maxLineLengths()[$column] + 2);

            $values[] = \call_user_func($wrapper, $value, $column);
        }

        return \sprintf('|%s|', \implode('|', $values));
    }

    /**
     * Computed from the rendered cell values: object references or dates may be wider than
     * the raw strings the original table was built from.
     *
     * @return array<array-key, int>
     */
    private function maxLineLengths(): array
    {
        if ($this->maxLineLength) {
            return $this->maxLineLength;
        }

        foreach ($this->getRows() as $row) {
            foreach (\array_values($row) as $column => $value) {
                $this->maxLineLength[$column] = \max($this->maxLineLength[$column] ?? 0, \mb_strlen($this->getValueAsString($value), 'utf8'));
            }
        }

        return $this->maxLineLength;
    }

    private function getValueAsString(mixed $value): string
    {
        return match (true) {
            !\is_object($value) => (string) $value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof \BackedEnum => (string) $value->value,
            $this->objectRegistry->isStored($value) => "{$this->factoryShortNameResolver->getShortNameForClass($value::class)} {$this->objectRegistry->getNameFor($value)}",
            default => throw new \LogicException('Unsupported value type: '.\get_debug_type($value)),
        };
    }
}
