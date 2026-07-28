<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture\Stories;

use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;
use Zenstruck\Foundry\Tests\Fixture\Factories\Entity\GenericEntityFactory;

#[AsFixture(name: 'behat-generic-entity')]
final class GenericEntityStory extends Story
{
    public function build(): void
    {
        $this->addState(
            'generic fixture',
            GenericEntityFactory::createOne(['prop1' => 'fixture'])
        );
    }
}
