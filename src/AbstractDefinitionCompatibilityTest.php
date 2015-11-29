<?php

namespace Interop\Container\Definition\Test;

use Assembly\ArrayDefinitionProvider;
use Assembly\Reference;
use Interop\Container\ContainerInterface;
use Interop\Container\Definition\DefinitionProviderInterface;

abstract class AbstractDefinitionCompatibilityTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Takes a definition provider in parameter and returns a container containing the entries.
     *
     * @param DefinitionProviderInterface $definitionProvider
     * @return ContainerInterface
     */
    abstract protected function getContainer(DefinitionProviderInterface $definitionProvider);

    public function testInstanceConverter()
    {
        $referenceDefinition = new \Assembly\ObjectDefinition('foo', '\\stdClass');

        $assemblyDefinition = new \Assembly\ObjectDefinition('bar', 'Interop\\Container\\Definition\\Fixtures\\Test');
        $assemblyDefinition->addConstructorArgument(42);
        $assemblyDefinition->addConstructorArgument(['hello' => 'world', 'foo' => new Reference('foo'), 'fooDirect' => $referenceDefinition]);

        $container = $this->getContainer(new ArrayDefinitionProvider([
            'bar' => $assemblyDefinition,
            'foo' => $referenceDefinition,
        ]));
        $result = $container->get('bar');

        $this->assertInstanceOf('Interop\\Container\\Definition\\Fixtures\\Test', $result);
        $this->assertEquals(42, $result->cArg1);
        $this->assertEquals('world', $result->cArg2['hello']);
        $this->assertInstanceOf('stdClass', $result->cArg2['foo']);
        $this->assertInstanceOf('stdClass', $result->cArg2['fooDirect']);
    }

    /**
     * Invalid objects (objects not extending one of the xxxDefinitionInterface interfaces) should trigger
     * an exception.
     *
     * @expectedException \RuntimeException
     */
    public function testParameterException()
    {
        $assemblyDefinition = new \Assembly\ObjectDefinition('foo', 'Interop\\Container\\Definition\\Fixtures\\Test');
        $assemblyDefinition->addConstructorArgument(new \stdClass());

        $this->getContainer(new ArrayDefinitionProvider([
            'bar' => $assemblyDefinition
        ]));
    }

    /**
     * Test method calls and property assignments
     */
    public function testInstanceConverterPropertiesAndMethodCalls()
    {
        $assemblyDefinition = new \Assembly\ObjectDefinition('bar', 'Interop\\Container\\Definition\\Fixtures\\Test');
        $assemblyDefinition->addMethodCall('setArg1', 42);
        $assemblyDefinition->addPropertyAssignment('cArg2', 43);

        $container = $this->getContainer(new ArrayDefinitionProvider([
            'bar' => $assemblyDefinition,
        ]));
        $result = $container->get('bar');

        $this->assertInstanceOf('Interop\\Container\\Definition\\Fixtures\\Test', $result);
        $this->assertEquals(42, $result->cArg1);
        $this->assertEquals(43, $result->cArg2);
    }

    public function testParameterConverter()
    {
        $assemblyDefinition = new \Assembly\ParameterDefinition('foo', '42');

        $container = $this->getContainer(new ArrayDefinitionProvider([
            'foo' => $assemblyDefinition,
        ]));
        $result = $container->get('foo');

        $this->assertEquals(42, $result);
    }

    public function testAliasConverter()
    {
        $aliasDefinition = new \Assembly\AliasDefinition('foo', 'bar');

        $assemblyDefinition = new \Assembly\ObjectDefinition('bar', 'Interop\\Container\\Definition\\Fixtures\\Test');

        $container = $this->getContainer(new ArrayDefinitionProvider([
            'bar' => $assemblyDefinition,
            'foo' => $aliasDefinition,
        ]));
        $result = $container->get('foo');
        $result2 = $container->get('bar');

        $this->assertTrue($result === $result2);
    }

    public function testFactoryConverter()
    {
        $factoryAssemblyDefinition = new \Assembly\ObjectDefinition('factory', 'Interop\\Container\\Definition\\Fixtures\\TestFactory');
        $factoryAssemblyDefinition->addConstructorArgument(42);

        $assemblyDefinition = new \Assembly\FactoryCallDefinition('test', new Reference('factory'), 'getTest');

        $container = $this->getContainer(new ArrayDefinitionProvider([
            'factory' => $factoryAssemblyDefinition,
            'test' => $assemblyDefinition,
        ]));
        $result = $container->get('test');

        $this->assertInstanceOf('Interop\\Container\\Definition\\Fixtures\\Test', $result);
        $this->assertEquals(42, $result->cArg1);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testUnsupportedDefinitionConverter()
    {
        $definition = $this->getMock('Interop\\Container\\Definition\\DefinitionInterface');

        $this->getContainer(new ArrayDefinitionProvider([
            'foo' => $definition,
        ]));
    }
}
