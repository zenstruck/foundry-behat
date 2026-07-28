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

use Behat\Behat\Definition\Call\DefinitionCall;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\Filter\CallFilter;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\Behat\Exception\CompositeIdentifierNotSupported;
use Zenstruck\Foundry\Test\Behat\Exception\InvalidObjectParameter;
use Zenstruck\Foundry\Test\Behat\Exception\ObjectNotFound;

/**
 * @internal
 *
 * Transforms TableNodes into FoundryTableNodes where all types are resolved
 */
final class FoundryCallFilter implements CallFilter
{
    public function __construct(
        private readonly KernelInterface $symfonyKernel,
    ) {
    }

    public function supportsCall(Call $call): bool
    {
        return array_any(
            $call->getArguments(),
            static fn($argument) => $argument instanceof TableNode && !$argument instanceof ExampleTableNode
        );
    }

    public function filterCall(Call $call): Call
    {
        if (!$call instanceof DefinitionCall) {
            return $call;
        }

        $reflection = $call->getCallee()->getReflection();

        if (!$reflection instanceof \ReflectionMethod || !\is_a($reflection->class, FoundryContextInterface::class, allow_string: true)) {
            return $call;
        }

        $arguments = $call->getArguments();
        $factoryShortName = $this->resolveFactoryShortNameArgument($reflection, $arguments);

        if (null === $factoryShortName) {
            throw new \InvalidArgumentException(<<<ERROR
                Cannot filter call without a "\$factoryShortName" argument.
                This must be the name of the argument in the #[Given], #[When], #[Then] definitions.
                When overriding a step with a custom regex pattern, use a named capture group ((?P<factoryShortName>...)),
                or anonymous capture groups appearing in the same order as the method parameters.
                ERROR);
        }

        return new DefinitionCall(
            $call->getEnvironment(),
            $call->getFeature(),
            $call->getStep(),
            $call->getCallee(),
            \array_map(
                fn(mixed $argument) => match ($argument instanceof TableNode) {
                    true => $this->normalizeObjectParameters($argument, $factoryShortName),
                    false => $argument,
                },
                $arguments
            ),
            $call->getErrorReportingLevel(),
        );
    }

    /**
     * Named capture groups produce arguments keyed by parameter name, anonymous ones (e.g. from
     * a re-worded regex pattern) produce arguments keyed by parameter position.
     *
     * @param array<array-key, mixed> $arguments
     */
    private function resolveFactoryShortNameArgument(\ReflectionMethod $method, array $arguments): ?string
    {
        $argument = $arguments['factoryShortName']
            ?? $arguments[array_find_key(
                $method->getParameters(),
                static fn(\ReflectionParameter $parameter) => 'factoryShortName' === $parameter->getName()
            ) ?? -1]
            ?? null;

        return \is_string($argument) ? $argument : null;
    }

    private function normalizeObjectParameters(TableNode $tableNode, string $factoryShortName): TableNode
    {
        $table = $tableNode->getTable();

        $headKey = \array_key_first($table) ?? throw new \LogicException('Table has no header row.');
        $thead = $table[$headKey];
        unset($table[$headKey]);

        return FoundryTableNode::create(
            $this->factoryResolver(),
            $this->objectRegistry(),
            [$headKey => $thead] + \array_map(
                fn(array $parameters) => $this->normalizeTableRow($parameters, $thead, $factoryShortName),
                $table
            )
        );
    }

    /**
     * @param list<string> $parameters
     * @param list<string> $thead
     *
     * @return array<string, mixed>
     */
    private function normalizeTableRow(array $parameters, array $thead, string $factoryShortName): array
    {
        $normalized = [];
        foreach ($parameters as $key => $value) {
            if (!isset($thead[$key])) {
                throw new \LogicException("Table has no column for parameter \"{$key}\". This should never happen, table integrity is checked in TableNode.");
            }

            $propertyName = $thead[$key];

            if ('_ref' === $propertyName) {
                $normalized['_ref'] = $value;

                continue;
            }

            $normalized[$propertyName] = match (true) {
                'null' === $value => null,
                'true' === $value => true,
                'false' === $value => false,
                default => $this->resolveExplicitObjectReference($propertyName, $value)
                    ?? $this->resolveObjectReferenceBasedOnPropertyType($propertyName, $value, $factoryShortName),
            };
        }

        return $normalized;
    }

    private function resolveExplicitObjectReference(string $propertyName, string $value): ?object
    {
        if (\preg_match('/^<foundry:lastObject\(\s*(?<factoryShortName>[^)]+?)\s*\)>$/', $value, $matches)) {
            try {
                return $this->objectRegistry()->lastObjectFor(self::unquote($matches['factoryShortName']));
            } catch (CompositeIdentifierNotSupported $e) {
                throw InvalidObjectParameter::compositeIdentifier($propertyName, $e);
            }
        }

        if (!\preg_match('/^<foundry:object\(\s*(?<factoryShortName>[^,)]+?)\s*,\s*(?<objectName>[^)]+?)\s*\)>$/', $value, $matches)) {
            return null;
        }

        try {
            return $this->objectRegistry()->getByFactoryShortName(self::unquote($matches['factoryShortName']), self::unquote($matches['objectName']));
        } catch (ObjectNotFound $e) {
            throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
        }
    }

    /**
     * Same quoting rules as the <foundry:id()>/<foundry:lastId()> placeholders.
     */
    private static function unquote(string $value): string
    {
        return \trim($value, '"');
    }

    private function resolveObjectReferenceBasedOnPropertyType(string $propertyName, string $value, string $factoryShortName): mixed
    {
        $targetClass = $this->factoryResolver()->targetObjectClassFor($factoryShortName);
        $expectedTypeClass = $this->getPropertyTypeIfClass(new \ReflectionClass($targetClass), $propertyName);

        if (!$expectedTypeClass) {
            return $value;
        }

        if ($this->factoryResolver()->hasFactoryForClass($expectedTypeClass)) {
            try {
                return $this->objectRegistry()->getByObjectClass($expectedTypeClass, $value);
            } catch (ObjectNotFound $e) {
                throw InvalidObjectParameter::objectReferencedInTableDoesNotExist($propertyName, $e);
            }
        }

        if (\is_a($expectedTypeClass, \DateTimeInterface::class, allow_string: true)) {
            try {
                return new $expectedTypeClass($value);
            } catch (\Throwable $e) { // @phpstan-ignore catch.neverThrown
                throw InvalidObjectParameter::invalidDate($propertyName, $value, $e);
            }
        }

        if (\is_a($expectedTypeClass, \BackedEnum::class, allow_string: true)) {
            $value = \is_numeric($value) ? (int) $value : $value;

            return $expectedTypeClass::tryFrom($value) ?? throw InvalidObjectParameter::invalidEnumValue($propertyName, (string) $value);
        }

        throw new \LogicException("Cannot normalize parameter \"{$propertyName}\" with value \"{$value}\".");
    }

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return class-string|null
     */
    private function getPropertyTypeIfClass(\ReflectionClass $class, string $propertyName): ?string
    {
        try {
            $property = $class->getProperty($propertyName);
        } catch (\ReflectionException) {
            if ($class = $class->getParentClass()) {
                return $this->getPropertyTypeIfClass($class, $propertyName);
            }
        }

        if (
            !isset($property)
            || !($type = $property->getType()) instanceof \ReflectionNamedType
            || $type->isBuiltin()
            || !\class_exists($type->getName())
        ) {
            return null;
        }

        return $type->getName();
    }

    private function factoryResolver(): FactoryShortNameResolver
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.factory_resolver'); // @phpstan-ignore return.type
    }

    private function objectRegistry(): ObjectRegistry
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
