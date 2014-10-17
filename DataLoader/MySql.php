<?php
/**
 * @copyright 2014 Instaclick Inc.
 */

namespace IC\Bundle\Base\PdiTestBundle\DataLoader;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * MySQL migration loader.
 *
 * @author Danilo Cabello <daniloc@nationalfibre.net>
 */
class MySql extends ContainerAware
{
    /**
     * Load MySQL migrations from a given list of directories.
     *
     * @param array $directoryList
     */
    public function load(array $directoryList)
    {
        foreach ($directoryList as $directory => $skipDropAndCreate) {
            $this->loadDirectory($directory, $skipDropAndCreate);
        }
    }

    /**
     * Load MySQL migrations from a given directory.
     *
     * @param string  $directory
     * @param boolean $skipDropAndCreate
     */
    private function loadDirectory($directory, $skipDropAndCreate)
    {
        $connection        = $this->getConnection($directory);
        $migrationFileList = $this->fetchMigrationFileList($directory);

        if ($skipDropAndCreate === false) {
            $this->dropAndCreateDatabase($connection);
        }

        $this->migrateFileList($connection, $migrationFileList);
    }

    /**
     * Retrieve connection based on last directory in path.
     *
     * @param string $directory
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection($directory)
    {
        $connectionName = basename($directory);

        return $this->container->get(sprintf('doctrine.dbal.%s_connection', $connectionName));
    }

    /**
     * Fetch SQL files in a given directory sorted by version.
     *
     * @param string $directory
     *
     * @return array
     */
    private function fetchMigrationFileList($directory)
    {
        $migrationFileList = array();
        $fileList          = glob($directory . '/*.sql');

        foreach ($fileList as $file) {
            $version = basename($file, '.sql');

            if (preg_match('~^[vV]([^_]+)_~', $version, $matches)) {
                $version = $matches[1];
            }

            $migrationFileList[$version] = $file;
        }

        uksort($migrationFileList, 'version_compare');

        return $migrationFileList;
    }

    /**
     * Drop and recreate database
     *
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function dropAndCreateDatabase($connection)
    {
        $connectionParams = $connection->getParams();
        unset($connectionParams['dbname']);

        $connectionFactory   = $this->container->get('doctrine.dbal.connection_factory');
        $connectionTemporary = $connectionFactory->createConnection($connectionParams);

        $connectionTemporary->getSchemaManager()->dropAndCreateDatabase($connection->getDatabase());
    }

    /**
     * Execute migrations.
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param array                     $migrationFileList
     */
    private function migrateFileList($connection, $migrationFileList)
    {
        foreach ($migrationFileList as $migrationFile) {
            $this->migrateFile($connection, $migrationFile);
        }
    }

    /**
     * Execute a single migration.
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param string                    $migrationFile
     */
    private function migrateFile($connection, $migrationFile)
    {
        foreach (explode(";\n", trim(file_get_contents($migrationFile))) as $sqlStatement) {
            $connection->executeQuery($sqlStatement);
        }
    }
}
