<?php
/**
 * @copyright 2014 Instaclick Inc.
 */

namespace IC\Bundle\Base\PdiTestBundle\Service;

use Symfony\Component\Process\Process;

/**
 * Service to run a kitchen command
 *
 * @author David Maignan <davidm@nationalfibre.net>
 */
class KitchenService
{
    /**
     * @var string
     */
    private $job;

    /**
     * @var string
     */
    private $jobName;

    /**
     * @var string
     */
    private $jobFile;

    /**
     * @var string
     */
    private $jobRepository;

    /**
     * @var string
     */
    private $directory = '/opt/pentaho/design-tools/data-integration';

    /**
     * @var \Symfony\Component\Process\Process
     */
    private $process;

    /**
     * Constructor
     *
     * @param string $jobName
     * @param string $jobFile
     */
    public function __construct($jobName, $jobFile, $jobRepository)
    {
        $this->jobName       = $jobName;
        $this->jobFile       = $jobFile;
        $this->jobRepository = $jobRepository;
        $this->job           = sprintf('./kitchen.sh -rep:"%s" -job:"%s" -dir:jobs/%s -level:Basic', $jobRepository, $jobName, $jobFile);
    }

    /**
     * @return string
     */
    public function getJobFile()
    {
        return $this->jobFile;
    }


    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @return string
     */
    public function getJobRepository()
    {
        return $this->jobRepository;
    }

    /**
     * Get job
     *
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * Execute service
     *
     * @param boolean $output Display console output
     *
     * @throws \RuntimeException
     *
     * @return integer
     */
    public function run($output = false)
    {
        $this->process = new Process($this->getJob(), $this->directory);

        $this->process->setTimeout(240);
        $this->process->run();

        try {
            $this->process->isSuccessful();
        } catch (\RuntimeException $exception) {

        }

        if ($output) {
            print $this->process->getOutput();
        }

        return $this->process->getExitCode();
    }

    /**
     * Get exit message
     *
     * @param integer $exitCode
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getExitMessage($exitCode)
    {
        $messageList = array(
            '0'=> 'The job ran without a problem.',
            '1'=> 'Errors occurred during processing',
            '2'=> 'An unexpected error occurred during loading / running of the job',
            '7'=> 'The job couldn\'t be loaded from XML or the Repository',
            '8'=> 'Error loading steps or plugins (error in loading one of the plugins mostly)',
            '9'=> 'Command line usage printing',
        );

        if ( ! array_key_exists($exitCode, $messageList)) {
            throw new \Exception(sprintf("The code %d returned by the job: %s does not match any expected code", $exitCode, $this->getJobName()));
        }

        return sprintf("%s (%s)", $messageList[$exitCode], $this->getJobName());
    }
}
