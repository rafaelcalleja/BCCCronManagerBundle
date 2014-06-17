<?php
namespace BCC\CronManagerBundle\Manager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\Container;
use \Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Service
{

    protected $container;
    protected $root_dir, $log_dir, $env;
    protected $manager;

    public function __construct($container){

        $this->container = $container;
        $this->root_dir = $this->container->getParameter('kernel.root_dir');
        $this->env = $this->container->getParameter('kernel.environment');
        $this->log_dir = $this->container->getParameter('kernel.logs_dir');

    }

    private function initManager($userName){

        if(! $this->manager ){
            $this->manager = new CronManager($userName);
        }

        return $this->manager;
    }

    public function addCommandCron($userName, $commandName, $arguments, $cron, $log, $error, $comment){
        $this->initManager($userName);

        $schedule = $this->build($this->getCMD($commandName, $arguments), $cron, sprintf('%s/%s_%s', $this->log_dir, $log, $this->env), sprintf('%s/%s_%s', $this->log_dir, $error, $this->env), $comment);

        if( $this->deleteForUpdated($schedule) || ! $this->cronExists($schedule) ){
             $this->manager->add($schedule);
        }

    }

    private function deleteForUpdated($cron){
        foreach($this->manager->get() as $line => $c){
            if ( (string)$cron != (string)$c && strpos($c->getComment(), md5($cron->getCommand())) ) {
                $this->manager->remove($line);
                return true;
            }
        }

        return false;
    }

    private function cronExists($cron){

        foreach($this->manager->get() as $c){
            if ((string)$cron == (string)$c  ) return true;
        }

        return false;
    }


    protected function build($cmd, $cron, $log, $error, $comment){
        return Cron::parse(sprintf('%s %s > %s 2> %s #%s %s', $cron, $cmd, $log, $error, $comment, md5($cmd) ));
    }

    protected function getCMD($commandName, $arguments){
        return sprintf('%s %s %s --env=%s', $this->getConsolePath(), $commandName, $arguments, $this->env);
    }

    private function getConsolePath()
    {
        $finder = new Finder();
        $finder->name('console')->depth(0)->in($this->root_dir);
        $results = iterator_to_array($finder);
        $file = current($results);

        return sprintf('%s/%s', $file->getPath(), $file->getBasename());
    }
}
