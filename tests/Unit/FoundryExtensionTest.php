<?php

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Test\Behat\Tests\Unit\Test\Behat;

use DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\Foundry\Test\Behat\Exception\DamaNativeExtensionIncompatibility;
use Zenstruck\Foundry\Test\Behat\FoundryExtension;

final class FoundryExtensionTest extends TestCase
{
    private const BOOT_LISTENER_ID = '.zenstruck_foundry.behat.listener.boot_configuration';
    private const FIXTURES_LISTENER_ID = '.zenstruck_foundry.behat.listener.load_fixture';
    private const DATABASE_RESET_LISTENER_ID = '.zenstruck_foundry.behat.listener.database_reset';

    #[Test]
    public function it_defaults_to_disabled_mode_without_dama_support(): void
    {
        self::assertSame(
            ['database_reset_mode' => 'disabled', 'enable_dama_support' => false],
            $this->processConfiguration([])
        );
    }

    #[Test]
    public function it_rejects_dama_support_with_disabled_reset_mode(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('DAMA support cannot be enabled when database reset is disabled');

        $this->processConfiguration(['enable_dama_support' => true]);
    }

    #[Test]
    public function it_rejects_unknown_reset_modes(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['database_reset_mode' => 'each-step']);
    }

    #[Test]
    public function it_does_not_register_the_reset_listener_in_disabled_mode(): void
    {
        $container = $this->loadExtension(['database_reset_mode' => 'disabled', 'enable_dama_support' => false]);

        self::assertTrue($container->hasDefinition(self::BOOT_LISTENER_ID));
        self::assertTrue($container->hasDefinition(self::FIXTURES_LISTENER_ID));
        self::assertFalse($container->hasDefinition(self::DATABASE_RESET_LISTENER_ID));
    }

    #[Test]
    #[DataProvider('resetModeProvider')]
    public function it_registers_the_reset_listener_for_active_modes(string $mode): void
    {
        $container = $this->loadExtension(['database_reset_mode' => $mode, 'enable_dama_support' => false]);

        self::assertTrue($container->hasDefinition(self::DATABASE_RESET_LISTENER_ID));
    }

    public static function resetModeProvider(): iterable
    {
        yield 'scenario' => ['scenario'];
        yield 'feature' => ['feature'];
        yield 'manual' => ['manual'];
    }

    #[Test]
    public function it_rejects_foundry_dama_support_combined_with_the_native_extension(): void
    {
        $this->expectException(DamaNativeExtensionIncompatibility::class);
        $this->expectExceptionMessage('cannot be enabled when the native Behat extension');

        $this->loadExtension(
            ['database_reset_mode' => 'scenario', 'enable_dama_support' => true],
            nativeDamaExtensionEnabled: true,
        );
    }

    #[Test]
    public function it_rejects_feature_mode_with_the_native_extension(): void
    {
        $this->expectException(DamaNativeExtensionIncompatibility::class);
        $this->expectExceptionMessage('Database reset mode "feature" is not supported');

        $this->loadExtension(
            ['database_reset_mode' => 'feature', 'enable_dama_support' => false],
            nativeDamaExtensionEnabled: true,
        );
    }

    #[Test]
    public function it_rejects_manual_mode_with_the_native_extension(): void
    {
        $this->expectException(DamaNativeExtensionIncompatibility::class);
        $this->expectExceptionMessage('Database reset mode "manual" is not supported');

        $this->loadExtension(
            ['database_reset_mode' => 'manual', 'enable_dama_support' => false],
            nativeDamaExtensionEnabled: true,
        );
    }

    #[Test]
    public function it_accepts_scenario_mode_with_the_native_extension(): void
    {
        $container = $this->loadExtension(
            ['database_reset_mode' => 'scenario', 'enable_dama_support' => false],
            nativeDamaExtensionEnabled: true,
        );

        self::assertTrue($container->hasDefinition(self::DATABASE_RESET_LISTENER_ID));
        self::assertTrue($container->getDefinition(self::DATABASE_RESET_LISTENER_ID)->getArgument('$damaNativeExtensionIsEnabled'));
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $config): array
    {
        $treeBuilder = new TreeBuilder('zenstruck_foundry');
        (new FoundryExtension())->configure($treeBuilder->getRootNode());

        return (new Processor())->process($treeBuilder->buildTree(), [$config]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadExtension(array $config, bool $nativeDamaExtensionEnabled = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('extensions', $nativeDamaExtensionEnabled ? [DoctrineExtension::class] : []);

        (new FoundryExtension())->load($container, $config);

        return $container;
    }
}
