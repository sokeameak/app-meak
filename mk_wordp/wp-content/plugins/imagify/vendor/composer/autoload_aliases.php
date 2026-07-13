<?php

// Functions and constants

namespace {

}


namespace Imagify\Dependencies {

    use BrianHenryIE\Strauss\Types\AutoloadAliasInterface;

    /**
     * @see AutoloadAliasInterface
     *
     * @phpstan-type ClassAliasArray array{'type':'class',isabstract:bool,classname:string,namespace?:string,extends:string,implements:array<string>}
     * @phpstan-type InterfaceAliasArray array{'type':'interface',interfacename:string,namespace?:string,extends:array<string>}
     * @phpstan-type TraitAliasArray array{'type':'trait',traitname:string,namespace?:string,use:array<string>}
     * @phpstan-type AutoloadAliasArray array<string,ClassAliasArray|InterfaceAliasArray|TraitAliasArray>
     */
    class AliasAutoloader
    {
        private string $includeFilePath;

        /**
         * @var AutoloadAliasArray
         */
        private array $autoloadAliases = array (
  'WP_Async_Request' => 
  array (
    'type' => 'class',
    'classname' => 'WP_Async_Request',
    'isabstract' => true,
    'namespace' => '\\',
    'extends' => 'Imagify_WP_Async_Request',
    'implements' => 
    array (
    ),
  ),
  'WP_Background_Process' => 
  array (
    'type' => 'class',
    'classname' => 'WP_Background_Process',
    'isabstract' => true,
    'namespace' => '\\',
    'extends' => 'Imagify_WP_Background_Process',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\DefaultValueArgument' => 
  array (
    'type' => 'class',
    'classname' => 'DefaultValueArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\DefaultValueArgument',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\DefaultValueInterface',
    ),
  ),
  'League\\Container\\Argument\\Literal\\ArrayArgument' => 
  array (
    'type' => 'class',
    'classname' => 'ArrayArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\ArrayArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\BooleanArgument' => 
  array (
    'type' => 'class',
    'classname' => 'BooleanArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\BooleanArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\CallableArgument' => 
  array (
    'type' => 'class',
    'classname' => 'CallableArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\CallableArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\FloatArgument' => 
  array (
    'type' => 'class',
    'classname' => 'FloatArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\FloatArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\IntegerArgument' => 
  array (
    'type' => 'class',
    'classname' => 'IntegerArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\IntegerArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\ObjectArgument' => 
  array (
    'type' => 'class',
    'classname' => 'ObjectArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\ObjectArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\Literal\\StringArgument' => 
  array (
    'type' => 'class',
    'classname' => 'StringArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument\\Literal',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\Literal\\StringArgument',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\LiteralArgument' => 
  array (
    'type' => 'class',
    'classname' => 'LiteralArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\LiteralArgument',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\LiteralArgumentInterface',
    ),
  ),
  'League\\Container\\Argument\\ResolvableArgument' => 
  array (
    'type' => 'class',
    'classname' => 'ResolvableArgument',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Argument',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Argument\\ResolvableArgument',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\ResolvableArgumentInterface',
    ),
  ),
  'League\\Container\\Container' => 
  array (
    'type' => 'class',
    'classname' => 'Container',
    'isabstract' => false,
    'namespace' => 'League\\Container',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Container',
    'implements' => 
    array (
      0 => 'League\\Container\\DefinitionContainerInterface',
    ),
  ),
  'League\\Container\\Definition\\Definition' => 
  array (
    'type' => 'class',
    'classname' => 'Definition',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Definition',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Definition\\Definition',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\ArgumentResolverInterface',
      1 => 'League\\Container\\Definition\\DefinitionInterface',
    ),
  ),
  'League\\Container\\Definition\\DefinitionAggregate' => 
  array (
    'type' => 'class',
    'classname' => 'DefinitionAggregate',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Definition',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Definition\\DefinitionAggregate',
    'implements' => 
    array (
      0 => 'League\\Container\\Definition\\DefinitionAggregateInterface',
    ),
  ),
  'League\\Container\\Exception\\ContainerException' => 
  array (
    'type' => 'class',
    'classname' => 'ContainerException',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Exception',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Exception\\ContainerException',
    'implements' => 
    array (
      0 => 'Psr\\Container\\ContainerExceptionInterface',
    ),
  ),
  'League\\Container\\Exception\\NotFoundException' => 
  array (
    'type' => 'class',
    'classname' => 'NotFoundException',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Exception',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Exception\\NotFoundException',
    'implements' => 
    array (
      0 => 'Psr\\Container\\NotFoundExceptionInterface',
    ),
  ),
  'League\\Container\\Inflector\\Inflector' => 
  array (
    'type' => 'class',
    'classname' => 'Inflector',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Inflector',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Inflector\\Inflector',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\ArgumentResolverInterface',
      1 => 'League\\Container\\Inflector\\InflectorInterface',
    ),
  ),
  'League\\Container\\Inflector\\InflectorAggregate' => 
  array (
    'type' => 'class',
    'classname' => 'InflectorAggregate',
    'isabstract' => false,
    'namespace' => 'League\\Container\\Inflector',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\Inflector\\InflectorAggregate',
    'implements' => 
    array (
      0 => 'League\\Container\\Inflector\\InflectorAggregateInterface',
    ),
  ),
  'League\\Container\\ReflectionContainer' => 
  array (
    'type' => 'class',
    'classname' => 'ReflectionContainer',
    'isabstract' => false,
    'namespace' => 'League\\Container',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\ReflectionContainer',
    'implements' => 
    array (
      0 => 'League\\Container\\Argument\\ArgumentResolverInterface',
      1 => 'Psr\\Container\\ContainerInterface',
    ),
  ),
  'League\\Container\\ServiceProvider\\AbstractServiceProvider' => 
  array (
    'type' => 'class',
    'classname' => 'AbstractServiceProvider',
    'isabstract' => true,
    'namespace' => 'League\\Container\\ServiceProvider',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\ServiceProvider\\AbstractServiceProvider',
    'implements' => 
    array (
      0 => 'League\\Container\\ServiceProvider\\ServiceProviderInterface',
    ),
  ),
  'League\\Container\\ServiceProvider\\ServiceProviderAggregate' => 
  array (
    'type' => 'class',
    'classname' => 'ServiceProviderAggregate',
    'isabstract' => false,
    'namespace' => 'League\\Container\\ServiceProvider',
    'extends' => 'Imagify\\Dependencies\\League\\Container\\ServiceProvider\\ServiceProviderAggregate',
    'implements' => 
    array (
      0 => 'League\\Container\\ServiceProvider\\ServiceProviderAggregateInterface',
    ),
  ),
  'WPMedia\\PluginFamily\\Controller\\PluginFamily' => 
  array (
    'type' => 'class',
    'classname' => 'PluginFamily',
    'isabstract' => false,
    'namespace' => 'WPMedia\\PluginFamily\\Controller',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\PluginFamily\\Controller\\PluginFamily',
    'implements' => 
    array (
      0 => 'WPMedia\\PluginFamily\\Controller\\PluginFamilyInterface',
    ),
  ),
  'WPMedia\\PluginFamily\\Model\\PluginFamily' => 
  array (
    'type' => 'class',
    'classname' => 'PluginFamily',
    'isabstract' => false,
    'namespace' => 'WPMedia\\PluginFamily\\Model',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\PluginFamily\\Model\\PluginFamily',
    'implements' => 
    array (
    ),
  ),
  'WPMedia\\PluginFamily\\PostInstall' => 
  array (
    'type' => 'class',
    'classname' => 'PostInstall',
    'isabstract' => false,
    'namespace' => 'WPMedia\\PluginFamily',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\PluginFamily\\PostInstall',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Base_MixpanelBase' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Base_MixpanelBase',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Base_MixpanelBase',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_ConsumerStrategies_AbstractConsumer' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_ConsumerStrategies_AbstractConsumer',
    'isabstract' => true,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_ConsumerStrategies_AbstractConsumer',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_ConsumerStrategies_CurlConsumer' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_ConsumerStrategies_CurlConsumer',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_ConsumerStrategies_CurlConsumer',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_ConsumerStrategies_FileConsumer' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_ConsumerStrategies_FileConsumer',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_ConsumerStrategies_FileConsumer',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_ConsumerStrategies_SocketConsumer' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_ConsumerStrategies_SocketConsumer',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_ConsumerStrategies_SocketConsumer',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Mixpanel' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Mixpanel',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Mixpanel',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Producers_MixpanelBaseProducer' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Producers_MixpanelBaseProducer',
    'isabstract' => true,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Producers_MixpanelBaseProducer',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Producers_MixpanelEvents' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Producers_MixpanelEvents',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Producers_MixpanelEvents',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Producers_MixpanelGroups' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Producers_MixpanelGroups',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Producers_MixpanelGroups',
    'implements' => 
    array (
    ),
  ),
  'WPMedia_Producers_MixpanelPeople' => 
  array (
    'type' => 'class',
    'classname' => 'WPMedia_Producers_MixpanelPeople',
    'isabstract' => false,
    'namespace' => '\\',
    'extends' => 'Imagify_WPMedia_Producers_MixpanelPeople',
    'implements' => 
    array (
    ),
  ),
  'WPMedia\\Mixpanel\\Optin' => 
  array (
    'type' => 'class',
    'classname' => 'Optin',
    'isabstract' => false,
    'namespace' => 'WPMedia\\Mixpanel',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\Mixpanel\\Optin',
    'implements' => 
    array (
    ),
  ),
  'WPMedia\\Mixpanel\\Tracking' => 
  array (
    'type' => 'class',
    'classname' => 'Tracking',
    'isabstract' => false,
    'namespace' => 'WPMedia\\Mixpanel',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\Mixpanel\\Tracking',
    'implements' => 
    array (
    ),
  ),
  'WPMedia\\Mixpanel\\TrackingPlugin' => 
  array (
    'type' => 'class',
    'classname' => 'TrackingPlugin',
    'isabstract' => false,
    'namespace' => 'WPMedia\\Mixpanel',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\Mixpanel\\TrackingPlugin',
    'implements' => 
    array (
    ),
  ),
  'WPMedia\\Mixpanel\\WPConsumer' => 
  array (
    'type' => 'class',
    'classname' => 'WPConsumer',
    'isabstract' => false,
    'namespace' => 'WPMedia\\Mixpanel',
    'extends' => 'Imagify\\Dependencies\\WPMedia\\Mixpanel\\WPConsumer',
    'implements' => 
    array (
    ),
  ),
  'League\\Container\\Argument\\ArgumentResolverTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'ArgumentResolverTrait',
    'namespace' => 'League\\Container\\Argument',
    'use' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\ArgumentResolverTrait',
    ),
  ),
  'League\\Container\\ContainerAwareTrait' => 
  array (
    'type' => 'trait',
    'traitname' => 'ContainerAwareTrait',
    'namespace' => 'League\\Container',
    'use' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\ContainerAwareTrait',
    ),
  ),
  'League\\Container\\Argument\\ArgumentInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ArgumentInterface',
    'namespace' => 'League\\Container\\Argument',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\ArgumentInterface',
    ),
  ),
  'League\\Container\\Argument\\ArgumentResolverInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ArgumentResolverInterface',
    'namespace' => 'League\\Container\\Argument',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\ArgumentResolverInterface',
    ),
  ),
  'League\\Container\\Argument\\DefaultValueInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'DefaultValueInterface',
    'namespace' => 'League\\Container\\Argument',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\DefaultValueInterface',
    ),
  ),
  'League\\Container\\Argument\\LiteralArgumentInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'LiteralArgumentInterface',
    'namespace' => 'League\\Container\\Argument',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\LiteralArgumentInterface',
    ),
  ),
  'League\\Container\\Argument\\ResolvableArgumentInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ResolvableArgumentInterface',
    'namespace' => 'League\\Container\\Argument',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Argument\\ResolvableArgumentInterface',
    ),
  ),
  'League\\Container\\ContainerAwareInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ContainerAwareInterface',
    'namespace' => 'League\\Container',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\ContainerAwareInterface',
    ),
  ),
  'League\\Container\\Definition\\DefinitionAggregateInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'DefinitionAggregateInterface',
    'namespace' => 'League\\Container\\Definition',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Definition\\DefinitionAggregateInterface',
    ),
  ),
  'League\\Container\\Definition\\DefinitionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'DefinitionInterface',
    'namespace' => 'League\\Container\\Definition',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Definition\\DefinitionInterface',
    ),
  ),
  'League\\Container\\DefinitionContainerInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'DefinitionContainerInterface',
    'namespace' => 'League\\Container',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\DefinitionContainerInterface',
    ),
  ),
  'League\\Container\\Inflector\\InflectorAggregateInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'InflectorAggregateInterface',
    'namespace' => 'League\\Container\\Inflector',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Inflector\\InflectorAggregateInterface',
    ),
  ),
  'League\\Container\\Inflector\\InflectorInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'InflectorInterface',
    'namespace' => 'League\\Container\\Inflector',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\Inflector\\InflectorInterface',
    ),
  ),
  'League\\Container\\ServiceProvider\\BootableServiceProviderInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'BootableServiceProviderInterface',
    'namespace' => 'League\\Container\\ServiceProvider',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\ServiceProvider\\BootableServiceProviderInterface',
    ),
  ),
  'League\\Container\\ServiceProvider\\ServiceProviderAggregateInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ServiceProviderAggregateInterface',
    'namespace' => 'League\\Container\\ServiceProvider',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\ServiceProvider\\ServiceProviderAggregateInterface',
    ),
  ),
  'League\\Container\\ServiceProvider\\ServiceProviderInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ServiceProviderInterface',
    'namespace' => 'League\\Container\\ServiceProvider',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\League\\Container\\ServiceProvider\\ServiceProviderInterface',
    ),
  ),
  'Psr\\Container\\ContainerExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ContainerExceptionInterface',
    'namespace' => 'Psr\\Container',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\Psr\\Container\\ContainerExceptionInterface',
    ),
  ),
  'Psr\\Container\\ContainerInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'ContainerInterface',
    'namespace' => 'Psr\\Container',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\Psr\\Container\\ContainerInterface',
    ),
  ),
  'Psr\\Container\\NotFoundExceptionInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'NotFoundExceptionInterface',
    'namespace' => 'Psr\\Container',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\Psr\\Container\\NotFoundExceptionInterface',
    ),
  ),
  'WPMedia\\PluginFamily\\Controller\\PluginFamilyInterface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'PluginFamilyInterface',
    'namespace' => 'WPMedia\\PluginFamily\\Controller',
    'extends' => 
    array (
      0 => 'Imagify\\Dependencies\\WPMedia\\PluginFamily\\Controller\\PluginFamilyInterface',
    ),
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        /**
         * @param string $class
         */
        public function autoload($class): void
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile): void
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        /**
         * @param ClassAliasArray $class
         */
        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        /**
         * @param InterfaceAliasArray $interface
         */
        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }

        /**
         * @param TraitAliasArray $trait
         */
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}
