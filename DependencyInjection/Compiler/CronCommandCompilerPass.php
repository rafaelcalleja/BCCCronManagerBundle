<?php
namespace BCC\CronManagerBundle\DependencyInjection\Compiler;

use BCC\CronManagerBundle\Annotation\CronCommand;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;



class CronCommandCompilerPass implements CompilerPassInterface
{
    protected $kernel;

    protected $container;

    protected $commands = array();


    public function __construct(KernelInterface $kernel){
        $this->kernel = $kernel;

    }

    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        foreach ( $this->kernel->getBundles() as $bundle) {
            if ($bundle instanceof Bundle) {
                $this->commands = array_merge( $this->listCommands($bundle), $this->commands);
            }
        }


        /** @var $reader AnnotationReader */
        $reader = $container->get('annotation_reader');
       // $definition = $container->getDefinition('bcc_cron_manager.cron.manager');
        $manager = $container->get('bcc_cron_manager.cron.manager');

        $resources = array();

        foreach($this->commands as $className ){

            try{
                $class = new \ReflectionClass( $className );
            }catch(\Exception $e){
                continue;
            }

            $resources[] = dirname($class->getFileName());

            foreach($reader->getClassAnnotations($class) as $annotation){
                if( $annotation instanceof CronCommand ){

                    /*$definition->addMethodCall(
                        'addCommandCron',
                        array($class->newInstance()->getName(), $annotation->arguments, $annotation->cron, $annotation->logFile, $annotation->errorFile, $annotation->comment )

                    );*/
                    $manager->addCommandCron($annotation->user, $class->newInstance()->getName(), $annotation->arguments, $annotation->cron, $annotation->logFile, $annotation->errorFile, $annotation->comment);
                }

            }

        }

        array_map(
        array($container, 'addResource')
        , array_map(
            function($value){ return new FileResource($value); } , array_unique($resources)));

    }

    /**
     * Finds and return list commands.
     *
     * Override this method if your bundle commands do not follow the conventions:
     *
     * * Commands are in the 'Command' sub-directory
     * * Commands extend Symfony\Component\Console\Command\Command
     *
     */
    private function listCommands($bundle)
    {
        $commandsClass = array();


        if (!is_dir($dir = $this->getPath($bundle).'/Command')) {
            return $commandsClass;
        }

        $finder = new Finder();
        $finder->files()->name('*Command.php')->in($dir);

        $prefix = $this->getNamespace($bundle).'\\Command';
        foreach ($finder as $file) {
            $ns = $prefix;
            if ($relativePath = $file->getRelativePath()) {
                $ns .= '\\'.strtr($relativePath, '/', '\\');
            }
            $class = $ns.'\\'.$file->getBasename('.php');
            if ($this->container) {
                $alias = 'console.command.'.strtolower(str_replace('\\', '_', $class));
                if ($this->container->has($alias)) {
                    continue;
                }
            }
            $r = new \ReflectionClass($class);
            if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command') && !$r->isAbstract() && !$r->getConstructor()->getNumberOfRequiredParameters()) {
                $commandsClass[] = get_class($r->newInstance());
            }
        }

        return $commandsClass;

    }

    private function getPath($bundle)
    {
        $reflected = new \ReflectionObject($bundle);
        $path = dirname($reflected->getFileName());
        return $path;

    }

    private function getNamespace($bundle)
    {
        $class = get_class($bundle);

        return substr($class, 0, strrpos($class, '\\'));
    }
}