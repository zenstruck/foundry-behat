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
use Yceruto\BehatExtension\Context\ExceptionAssertionTrait;

/**
 * Provides the exception assertion steps alongside the built-in FoundryContext.
 */
final class TestFoundryContext implements Context
{
    use ExceptionAssertionTrait;
}
