<?php
/**
 * @copyright 2014 Instaclick Inc.
 */

namespace IC\Bundle\Base\PdiTestBundle\DataLoader;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * AMQP migration loader.
 *
 * @author Danilo Cabello <daniloc@nationalfibre.net>
 */
class Amqp extends ContainerAware
{
    /**
     * Load AMQP messages from given directories
     *
     * @param array $directoryList
     */
    public function load(array $directoryList)
    {
        foreach ($directoryList as $directory) {
            $this->loadDirectory($directory);
        }
    }

    /**
     * Load AMQP messages from a given directory.
     *
     * @param string $directory
     */
    private function loadDirectory($directory)
    {
        $messageFileList = $this->fetchMessageFileList($directory);

        $this->publishFileList($messageFileList);
    }

    /**
     * Fetch the list of message files
     *
     * @param string $directory
     *
     * @return array
     */
    private function fetchMessageFileList($directory)
    {
        return glob($directory . '/*.json');
    }

    /**
     * Publish list of files
     *
     * @param array $messageFileList
     */
    private function publishFileList($messageFileList)
    {
        foreach ($messageFileList as $messageFile) {
            $this->publishFile($messageFile);
        }
    }

    /**
     * Publish a single message file.
     *
     * @param string $messageFile
     */
    private function publishFile($messageFile)
    {
        $exchange = $this->getExchange($messageFile);

        foreach (json_decode(file_get_contents($messageFile)) as $object) {
            $exchange->publish(json_encode($object), '#', AMQP_NOPARAM, array('content_type'=>'application/json'));
        }
    }

    /**
     * Get exchange
     *
     * @param string $messageFile
     *
     * @return \AMQPExchange
     */
    private function getExchange($messageFile)
    {
        return $this->container->get(
            sprintf('ic_base_amqp.exchange.%s', basename($messageFile, '.json'))
        );
    }
}
