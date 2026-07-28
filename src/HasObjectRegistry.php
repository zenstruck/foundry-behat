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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Provides the ObjectRegistry to a context composed from the step traits, through autowired
 * setter injection: the composing context needs no constructor and never references the
 * (internal) ObjectRegistry itself.
 *
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
trait HasObjectRegistry
{
    private ObjectRegistry $objectRegistry;

    #[Required]
    public function setObjectRegistry(
        #[Autowire(service: '.zenstruck_foundry.behat.object_registry')]
        ObjectRegistry $objectRegistry,
    ): void {
        $this->objectRegistry = $objectRegistry;
    }
}
