<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Configuration;
use Zenstruck\Foundry\Test\Behat\ObjectRegistry;

/**
 * @internal
 * @author Nicolas PHILIPPE <nikophil@gmail.com>
 */
final class BootConfigurationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelInterface $symfonyKernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => [['startCapturingStoryStates', 200], ['bootFoundryForExercise', 100]],
            ExerciseCompleted::AFTER => ['shutdownFoundry', -100],

            FeatureTested::BEFORE => ['bootFoundry', 100],
            FeatureTested::AFTER => ['shutdownFoundryAfterFeature', -100],

            ScenarioTested::BEFORE => [['startScenario', 110], ['bootFoundry', 100]],
            ExampleTested::BEFORE => [['startScenario', 110], ['bootFoundry', 100]],
        ];
    }

    /**
     * Named objects created by a replayed Background may re-register their name: the registry
     * only rejects duplicates within a single scenario.
     */
    public function startScenario(): void
    {
        ObjectRegistry::startScenario();
    }

    /**
     * ZenstruckFoundryBundle::boot() may have already booted Foundry with the concrete
     * Configuration of the initial container (FriendsOfBehat boots the kernel before any Behat
     * event): replace it with a closure re-resolving the configuration from the kernel's
     * CURRENT container, so services stay fresh across the per-scenario kernel reboots even in
     * modes that never shut Foundry down (disabled, manual).
     */
    public function bootFoundryForExercise(): void
    {
        Configuration::shutdown();
        $this->bootFoundry();
    }

    public function bootFoundry(): void
    {
        if (Configuration::isBooted()) {
            return;
        }

        Configuration::boot(
            fn() => $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.configuration') // @phpstan-ignore argument.type
        );
    }

    /**
     * Story states must only reach the ObjectRegistry during a Behat exercise: the listener
     * registered on StateAddedToStory also lives in the container PHPUnit boots (see ObjectRegistry).
     */
    public function startCapturingStoryStates(): void
    {
        ObjectRegistry::startCapturingStoryStates();
    }

    public function shutdownFoundry(): void
    {
        ObjectRegistry::stopCapturingStoryStates();
        Configuration::shutdown();
    }

    /**
     * In any case, we want to shutdown Foundry after each feature:
     * - to reset the object registry
     * - to reset the story registry
     */
    public function shutdownFoundryAfterFeature(): void
    {
        $this->objectRegistry()->reset();
        Configuration::shutdown();
    }

    private function objectRegistry(): ObjectRegistry
    {
        return $this->symfonyKernel->getContainer()->get('.zenstruck_foundry.behat.object_registry'); // @phpstan-ignore return.type
    }
}
