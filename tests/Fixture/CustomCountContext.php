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

use Behat\Behat\Context\Context;
use Behat\Step\Then;

/**
 * Defines a wording overlapping a built-in Foundry step (same matched text, different
 * pattern). Scenarios using it prove that the wording is free for the user's own
 * definition when FoundryAssertionContext is not loaded — otherwise Behat would raise
 * an ambiguous match.
 */
final class CustomCountContext implements Context
{
    #[Then('/^(\d+) "([^"]+)" should exist$/')]
    public function assertNbObjectsExist(int $nb, string $factoryShortName): void
    {
        throw new \LogicException('custom step called');
    }
}
