<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture;

use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Transformation\Transform;
use Zenstruck\Foundry\Test\Behat\AssertionSteps;
use Zenstruck\Foundry\Test\Behat\CreationSteps;
use Zenstruck\Foundry\Test\Behat\FoundryContextInterface;
use Zenstruck\Foundry\Test\Behat\PlaceholderTransforms;

/**
 * Custom context composed from the step traits: re-defining a trait method makes its
 * built-in wording disappear entirely (a class method takes precedence over the trait
 * one, attributes included), so the new attribute really replaces the built-in wording.
 * Private aliases give access to the built-in implementations for delegation, without
 * exposing their attributes to Behat (only public methods are read).
 */
final class OverridingFoundryContext implements FoundryContextInterface
{
    use AssertionSteps;
    use CreationSteps {
        createObject as private builtinCreateObject;
        createObjectWithProperties as private builtinCreateObjectWithProperties;
        createObjectsWithProperties as private builtinCreateObjectsWithProperties;
    }
    use PlaceholderTransforms {
        transformLastIdForSpecificObject as private builtinTransformLastIdForSpecificObject;
    }

    #[Given('create a :factoryShortName called :objectName')]
    public function createObject(string $factoryShortName, ?string $objectName = null): void
    {
        $this->builtinCreateObject($factoryShortName, $objectName);
    }

    /**
     * A step can also be re-worded with a regex using anonymous capture groups,
     * as long as they appear in the same order as the method parameters.
     */
    #[Given('/^an? (\S+) exists with:$/')]
    public function createObjectWithProperties(TableNode $table, string $factoryShortName, ?string $objectName = null): void
    {
        $this->builtinCreateObjectWithProperties($table, $factoryShortName, $objectName);
    }

    #[Given('the following :factoryShortName exist:')]
    public function createObjectsWithProperties(TableNode $table, string $factoryShortName): void
    {
        $this->builtinCreateObjectsWithProperties($table, $factoryShortName);
    }

    #[Transform('/(.*)\[lastId\(([^)]+?)\)\](.*)/')]
    public function transformLastIdForSpecificObject(string $before, string $factoryShortName, string $after): string
    {
        return $this->builtinTransformLastIdForSpecificObject($before, $factoryShortName, $after);
    }

    /**
     * Re-declaring the built-in pattern keeps the wording (the trait one is gone, so
     * there is no duplicate) while changing the implementation behind it.
     */
    #[Then('/^the (?|"(?P<factoryShortName>[^"]+)"|(?!\d)(?P<factoryShortName>\S+)) with id (?|"(?P<id>[^"]+)"|(?P<id>\S+)) should exist$/')]
    public function assertObjectWithIdExists(string $factoryShortName, string $id): void
    {
        throw new \LogicException('overridden implementation called');
    }
}
