<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Fixture\Factories;

use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Zenstruck\Foundry\Test\Behat\Attribute\FactoryShortName;
use Zenstruck\Foundry\Tests\Fixture\Entity\EntityWithUid;

/**
 * @extends PersistentObjectFactory<EntityWithUid>
 */
#[FactoryShortName(shortName: 'entity with uid', pluralName: 'entities with uid')]
final class EntityWithUidFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return EntityWithUid::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
