<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, DefaultTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new DefaultTransportFactory();

        $this->assertEquals('default', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new DefaultTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfigurationAsAliasAsString()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['the_alias']);

        $this->assertEquals(['alias' => 'the_alias'], $config);
    }

    public function testShouldAllowAddConfigurationAsAliasAsOption()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['alias' => 'the_alias']]);

        $this->assertEquals(['alias' => 'the_alias'], $config);
    }

    public function testShouldAllowAddConfigurationAsDsn()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['dsn://']);

        $this->assertEquals(['dsn' => 'dsn://'], $config);
    }

    public function testThrowIfNeitherDsnNorAliasConfiguredOnCreateConnectionFactory()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[]]);

        // guard
        $this->assertEquals([], $config);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either dsn or alias option must be set');
        $transport->createConnectionFactory(new ContainerBuilder(), $config);
    }

    public function testThrowIfNeitherDsnNorAliasConfiguredOnCreateContext()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[]]);

        // guard
        $this->assertEquals([], $config);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either dsn or alias option must be set');
        $transport->createContext(new ContainerBuilder(), $config);
    }

    public function testThrowIfNeitherDsnNorAliasConfiguredOnCreateDriver()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[]]);

        // guard
        $this->assertEquals([], $config);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either dsn or alias option must be set');
        $transport->createDriver(new ContainerBuilder(), $config);
    }

    public function testShouldCreateConnectionFactoryFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, ['alias' => 'foo']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasAlias('enqueue.transport.default.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.foo.connection_factory',
            (string) $container->getAlias('enqueue.transport.default.connection_factory')
        );

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    public function testShouldCreateContextFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createContext($container, ['alias' => 'the_alias']);

        $this->assertEquals('enqueue.transport.default.context', $serviceId);

        $this->assertTrue($container->hasAlias($serviceId));
        $context = $container->getAlias($serviceId);
        $this->assertEquals('enqueue.transport.the_alias.context', (string) $context);

        $this->assertTrue($container->hasAlias('enqueue.transport.context'));
        $context = $container->getAlias('enqueue.transport.context');
        $this->assertEquals($serviceId, (string) $context);
    }

    public function testShouldCreateDriverFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $driverId = $transport->createDriver($container, ['alias' => 'the_alias']);

        $this->assertEquals('enqueue.client.default.driver', $driverId);

        $this->assertTrue($container->hasAlias($driverId));
        $context = $container->getAlias($driverId);
        $this->assertEquals('enqueue.client.the_alias.driver', (string) $context);

        $this->assertTrue($container->hasAlias('enqueue.client.driver'));
        $context = $container->getAlias('enqueue.client.driver');
        $this->assertEquals($driverId, (string) $context);
    }

    /**
     * @dataProvider provideDSNs
     *
     * @param mixed $dsn
     * @param mixed $expectedName
     */
    public function testShouldCreateConnectionFactoryFromDSN($dsn, $expectedName)
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => $dsn]);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasAlias('enqueue.transport.default.connection_factory'));
        $this->assertEquals(
            sprintf('enqueue.transport.%s.connection_factory', $expectedName),
            (string) $container->getAlias('enqueue.transport.default.connection_factory')
        );

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    /**
     * @dataProvider provideDSNs
     *
     * @param mixed $dsn
     * @param mixed $expectedName
     */
    public function testShouldCreateContextFromDsn($dsn, $expectedName)
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createContext($container, ['dsn' => $dsn]);

        $this->assertEquals('enqueue.transport.default.context', $serviceId);

        $this->assertTrue($container->hasAlias($serviceId));
        $context = $container->getAlias($serviceId);
        $this->assertEquals(
            sprintf('enqueue.transport.%s.context', $expectedName),
            (string) $context
        );

        $this->assertTrue($container->hasAlias('enqueue.transport.context'));
        $context = $container->getAlias('enqueue.transport.context');
        $this->assertEquals($serviceId, (string) $context);
    }

    /**
     * @dataProvider provideDSNs
     *
     * @param mixed $dsn
     * @param mixed $expectedName
     */
    public function testShouldCreateDriverFromDsn($dsn, $expectedName)
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $driverId = $transport->createDriver($container, ['dsn' => $dsn]);

        $this->assertEquals('enqueue.client.default.driver', $driverId);

        $this->assertTrue($container->hasAlias($driverId));
        $context = $container->getAlias($driverId);
        $this->assertEquals(
            sprintf('enqueue.client.%s.driver', $expectedName),
            (string) $context
        );

        $this->assertTrue($container->hasAlias('enqueue.client.driver'));
        $context = $container->getAlias('enqueue.client.driver');
        $this->assertEquals($driverId, (string) $context);
    }

    public static function provideDSNs()
    {
        yield ['amqp://', 'default_amqp'];

        yield ['null://', 'default_null'];

        yield ['file://', 'default_fs'];
    }
}
