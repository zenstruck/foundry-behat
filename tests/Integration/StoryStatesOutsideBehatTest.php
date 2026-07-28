<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\Stories\ConflictStory1;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\Stories\ConflictStory2;
use Zenstruck\Foundry\Test\Behat\Tests\Fixture\Stories\ContactStory;
use Zenstruck\Foundry\Tests\Fixture\Entity\Contact;

/**
 * Reproduces a hybrid Behat + PHPUnit project: the StateAddedToStory listener is compiled
 * into the test container ("behat.service_container" is a synthetic definition registered by
 * FriendsOfBehatSymfonyExtensionBundle at compile time), but no Behat exercise is running.
 */
final class StoryStatesOutsideBehatTest extends KernelTestCase
{
    protected function setUp(): void
    {
        ObjectRegistry::stopCapturingStoryStates();
        $this->objectRegistry()->reset();
    }

    #[Test]
    public function story_states_are_not_captured(): void
    {
        ContactStory::load();

        self::assertFalse($this->objectRegistry()->has(Contact::class, 'john-doe'));
    }

    #[Test]
    public function the_same_story_can_be_reloaded(): void
    {
        ContactStory::load();

        // simulate the next PHPUnit test in the same process: fresh Configuration, story registry cleared
        Configuration::shutdown();
        Configuration::boot(
            static fn() => self::getContainer()->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );

        ContactStory::load();

        self::assertFalse($this->objectRegistry()->has(Contact::class, 'john-doe'));
    }

    #[Test]
    public function stories_sharing_a_state_name_do_not_conflict(): void
    {
        ConflictStory1::load();
        ConflictStory2::load();

        self::assertFalse($this->objectRegistry()->has(Contact::class, 'duplicate'));
    }

    private function objectRegistry(): ObjectRegistry
    {
        return self::getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
