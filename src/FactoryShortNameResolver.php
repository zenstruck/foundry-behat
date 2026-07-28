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

use Symfony\Component\String\Inflector\EnglishInflector;
use Zenstruck\Foundry\Factory;
use Zenstruck\Foundry\ObjectFactory;
use Zenstruck\Foundry\Test\Behat\Attribute\FactoryShortName;
use Zenstruck\Foundry\Test\Behat\Exception\FactoryNotResolvable;

use function Symfony\Component\String\u;

/**
 * Resolves Foundry factories from their human-readable short names.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class FactoryShortNameResolver
{
    /**
     * @var array<string, list<ObjectFactory<object>>>
     */
    private array $factoryMap = [];

    /** @var array<class-string, list<string>> */
    private array $classToShortNames = [];

    /**
     * @param iterable<Factory<mixed>> $factories
     */
    public function __construct(iterable $factories)
    {
        $inflector = new EnglishInflector();

        foreach ($factories as $factory) {
            if (!$factory instanceof ObjectFactory) {
                continue;
            }

            $shortName = $this->shortNameFor($factory::class);

            // we allow multiple factories to have the same shortName:
            // we'll only trigger an error when trying to access an unambiguous shortname
            // we don't want to force the user to resolve all potential conflicts at startup.
            $this->factoryMap[$shortName] ??= [];
            $this->factoryMap[$shortName][] = $factory;

            $plural = \mb_strtolower($this->factoryShortNameAttribute($factory::class)->pluralName ?? $inflector->pluralize($shortName)[0]);
            $this->factoryMap[$plural] ??= [];
            $this->factoryMap[$plural][] = $factory;

            if (!\in_array($shortName, $this->classToShortNames[$factory::class()] ?? [], true)) {
                $this->classToShortNames[$factory::class()][] = $shortName;
            }
        }
    }

    /**
     * @return ObjectFactory<object>
     *
     * @throws FactoryNotResolvable
     */
    public function factoryFor(string $shortName): ObjectFactory
    {
        $normalized = \mb_strtolower($shortName);

        if (!isset($this->factoryMap[$normalized])) {
            throw FactoryNotResolvable::forName($shortName);
        }

        $factories = $this->factoryMap[$normalized];

        if (\count($factories) > 1) {
            throw FactoryNotResolvable::conflict($shortName, \array_map(static fn(ObjectFactory $f) => $f::class, $factories));
        }

        return $factories[0]::new();
    }

    /**
     * @return class-string
     */
    public function targetObjectClassFor(string $shortName): string
    {
        return $this->factoryFor($shortName)::class();
    }

    /**
     * @param class-string $className
     */
    public function hasFactoryForClass(string $className): bool
    {
        return isset($this->classToShortNames[$className]);
    }

    /**
     * Only meant for display purposes: when several factories with different short names
     * target the same class, the first registered one is used.
     *
     * @param class-string $className
     */
    public function getShortNameForClass(string $className): string
    {
        return $this->classToShortNames[$className][0] ?? throw new \LogicException("No factory found for class \"{$className}\".");
    }

    /**
     * @param class-string<ObjectFactory<object>> $factoryClass
     */
    private function shortNameFor(string $factoryClass): string
    {
        $attribute = $this->factoryShortNameAttribute($factoryClass);

        if ($attribute) {
            return \mb_strtolower($attribute->shortName);
        }

        $shortClass = u((new \ReflectionClass($factoryClass))->getShortName());

        if ($shortClass->endsWith('Factory')) {
            $shortClass = $shortClass->slice(0, -7);
        }

        return $shortClass
            ->snake()
            ->replace('_', ' ')
            ->lower()
            ->toString();
    }

    /**
     * @param class-string<ObjectFactory<object>> $factoryClass
     */
    private function factoryShortNameAttribute(string $factoryClass): ?FactoryShortName
    {
        $reflection = new \ReflectionClass($factoryClass);

        $attributes = $reflection->getAttributes(FactoryShortName::class);

        return ($attributes[0] ?? null)?->newInstance();
    }
}
