<?php
namespace BCC\CronManagerBundle\Annotation;


/**
 * Represents a @CronCommand annotation.
 * @Annotation
 * @Target({"CLASS"})
 * @author Rafael Calleja <rafa.calleja@d-noise.net>

 */
final class CronCommand
{
    public $cron;
    public $logFile;
    public $errorFile;
    public $comment;
    public $arguments;
    public $user = 'www-data';

    public function __construct(array $values)
    {

        if (isset($values['value'])) {
            $values['cron'] = $values['value'];
        }

        if (!isset($values['cron'])) {
            throw new \InvalidArgumentException('You must define a "cron" attribute for each CronCommand annotation.');
        }

        if (!isset($values['logFile'])) {
            throw new \InvalidArgumentException('You must define a "logFile" attribute for each CronCommand annotation.');
        }

        if (!isset($values['errorFile'])) {
            throw new \InvalidArgumentException('You must define a "errorFile" attribute for each CronCommand annotation.');
        }

        if (!isset($values['comment'])) {
            throw new \InvalidArgumentException('You must define a "comment" attribute for each CronCommand annotation.');
        }

        if (isset($values['arguments'])) {
            $this->arguments = $values['arguments'];
        }

        if (isset($values['user'])) {
            $this->user = $values['user'];
        }

        if ( empty($this->user) ) {
            throw new \InvalidArgumentException('You must define a "user" attribute for each CronCommand annotation.');
        }


        $this->cron = stripcslashes($values['cron']);
        $this->logFile = $values['logFile'];
        $this->errorFile = $values['errorFile'];
        $this->comment = $values['comment'];


    }
}