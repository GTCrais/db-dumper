<?php

namespace Spatie\DbDumper\Databases;

use Spatie\DbDumper\DbDumper;
use Symfony\Component\Process\Process;
use Spatie\DbDumper\Exceptions\CannotStartDump;

class MongoDb extends DbDumper
{
    protected $port = 27017;

    /** @var null|string */
    protected $collection = null;

    /** @var bool */
    protected $enableCompression = false;

    /**
     * Dump the contents of the database to the given file.
     *
     * @param string $dumpFile
     *
     * @throws \Spatie\DbDumper\Exceptions\CannotStartDump
     * @throws \Spatie\DbDumper\Exceptions\DumpFailed
     */
    public function dumpToFile(string $dumpFile)
    {
        $this->guardAgainstIncompleteCredentials();

        $command = $this->getDumpCommand($dumpFile);

        $process = new Process($command);

        if (! is_null($this->timeout)) {
            $process->setTimeout($this->timeout);
        }

        $process->run();

        $this->checkIfDumpWasSuccessFul($process, $dumpFile);
    }

    /**
     * Verifies if the dbname and host options are set.
     *
     * @throws \Spatie\DbDumper\Exceptions\CannotStartDump
     * @return void
     */
    protected function guardAgainstIncompleteCredentials()
    {
        foreach (['dbName', 'host'] as $requiredProperty) {
            if (strlen($this->$requiredProperty) === 0) {
                throw CannotStartDump::emptyParameter($requiredProperty);
            }
        }
    }

    /**
     * @param string $collection
     *
     * @return \Spatie\DbDumper\Databases\MongoDb
     */
    public function setCollection(string $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return \Spatie\DbDumper\Databases\MongoDb
     */
    public function enableCompression()
    {
        $this->enableCompression = true;

        return $this;
    }

    /**
     * Generate the dump command for MongoDb.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getDumpCommand(string $filename) : string
    {
        $command = [
            "'{$this->dumpBinaryPath}mongodump'",
            "--db {$this->dbName}",
            "--archive=$filename",
        ];

        if ($this->userName) {
            $command[] = "--username '{$this->userName}'";
        }

        if ($this->password) {
            $command[] = "--password '{$this->password}'";
        }

        if (isset($this->host)) {
            $command[] = "--host {$this->host}";
        }

        if (isset($this->port)) {
            $command[] = "--port {$this->port}";
        }

        if (isset($this->collection)) {
            $command[] = "--collection {$this->collection}";
        }

        if ($this->enableCompression) {
            $command[] = '--gzip';
        }

        return implode(' ', $command);
    }
}
