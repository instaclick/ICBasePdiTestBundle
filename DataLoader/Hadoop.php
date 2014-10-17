<?php
/**
 * @copyright 2014 Instaclick Inc.
 */

namespace IC\Bundle\Base\PdiTestBundle\DataLoader;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Finder\Finder;

/**
 * Hadoop loader.
 *
 * @author Eldar Gafurov <eldarg@nationalfibre.net>
 */
class Hadoop extends ContainerAware
{
    /**
     * @var \IC\Bundle\Base\HadoopBundle\Service\HdfsService
     */
    private $hdfsService;

    /**
     * Load Hadoop file list from given directories
     *
     * @param array $directoryList
     */
    public function load(array $directoryList)
    {
        $this->hdfsService = $this->container->get('ic_base_hadoop.service.hdfs');

        foreach ($directoryList as $directory) {
            $this->loadDirectory($directory);
        }
    }

    /**
     * Load file list for Hadoop from a given directory, delete old files, and create new files.
     *
     * @param string $directory
     */
    private function loadDirectory($directory)
    {
        $this->hdfsService->delete('/');

        $finderDirectoryList  = new Finder();

        $this->createDirectoryList(
            $finderDirectoryList
                ->directories()
                ->in($directory)
        );

        $finderFileList  = new Finder();

        $this->createFileList(
            $finderFileList
                ->files()
                ->in($directory)
        );
    }

    /**
     * Create directories in HDFS. Only creates empty directories,
     * parent will be created automatically in other cases.
     *
     * @param \Symfony\Component\Finder\Finder $fileList
     */
    private function createDirectoryList($fileList)
    {
        foreach ($fileList as $file) {
            if (empty(glob(sprintf('%s/*', $file->getRealpath())))) {
                $this->hdfsService->mkdirs($file->getRelativePathname());
            }
        }
    }

    /**
     * Create files in HDFS. Parent directories will be created automatically.
     *
     * @param \Symfony\Component\Finder\Finder $fileList
     */
    private function createFileList($fileList)
    {
        foreach ($fileList as $file) {
            $this->hdfsService->create($file->getRelativePathname(), $file->getContents());
        }
    }
}
