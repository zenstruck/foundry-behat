<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Exception;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class CompositeIdentifierNotSupported extends \RuntimeException
{
    /**
     * @param class-string $class
     * @param list<string> $fields
     */
    public static function forClass(string $class, array $fields): self
    {
        return new self(\sprintf('"%s" must be identified by a single field to resolve its latest record, got %d ("%s").', $class, \count($fields), \implode('", "', $fields)));
    }
}
