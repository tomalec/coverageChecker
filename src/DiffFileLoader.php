<?php
namespace exussum12\CoverageChecker;

use InvalidArgumentException;

class DiffFileLoader
{
    protected $fileLocation;

    protected $diffLines = [
        DiffLineHandle\NewVersion\NewFile::class,
        DiffLineHandle\NewVersion\AddedLine::class,
        DiffLineHandle\NewVersion\RemovedLine::class,
        DiffLineHandle\NewVersion\DiffStart::class,
    ];
    protected $handles = [];
    protected $diff;

    public function __construct($fileName)
    {
        $this->fileLocation = $fileName;
        $this->diff = new DiffFileState();
    }

    public function getChangedLines()
    {
        if ((
            !is_readable($this->fileLocation) &&
            strpos($this->fileLocation, "php://") !== 0
        )) {
            throw new InvalidArgumentException("Can't read file");
        }

        $handle = fopen($this->fileLocation, 'r');

        while (($line = fgets($handle)) !== false) {
            // process the line read.
            $lineHandle = $this->getLineHandle($line);
            $lineHandle->handle($line);
            $this->diff->incrementCurrentPosition();
        }

        fclose($handle);

        return $this->diff->getChangedLines();
    }

    private function getLineHandle($line)
    {
        foreach ($this->diffLines as $lineType) {
            $lineType = $this->getClass($lineType);
            if ($lineType->isValid($line)) {
                return $lineType;
            }
        }
        //not found, Class it as context
        return $this->getClass(DiffLineHandle\ContextLine::class);
    }

    private function getClass($className)
    {
        if (!isset($this->handles[$this->getFileHandleName($className)])) {
            $this->handles[
                $this->getFileHandleName($className)
            ] = new $className($this->diff);
        }

        return $this->handles[$this->getFileHandleName($className)];
    }

    private function getFileHandleName($namespace)
    {
        $namespace = explode('\\', $namespace);
        return end($namespace);
    }
}
