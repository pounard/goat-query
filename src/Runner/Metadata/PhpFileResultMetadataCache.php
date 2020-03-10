<?php

declare(strict_types=1);

namespace Goat\Runner\Metadata;

/**
 * Array based implementation for testing, mostly.
 */
class PhpFileResultMetadataCache implements ResultMetadataCache
{
    private $data = null;
    private $filename;
    private $fileIsValid = true;

    public function __construct(?string $filename = null)
    {
        $this->filename = $filename ? $filename : \sys_get_temp_dir().'/goat_query_cache.php';
    }

    /**
     * Load file contents.
     *
     * @todo It's ugly, but it's working.
     */
    private function loadFile(): array
    {
        if (\file_exists($this->filename)) {
            try {
                $data = include $this->filename;
                if (!\is_array($data)) {
                    $this->fileIsValid = false;
                    throw new \InvalidArgumentException(\sprintf("'%s': file exists but does not contain an array", $this->filename));
                }
                return $data;
            } catch (\Throwable $e) {
                $this->fileIsValid = false;
                throw $e;
            }
        }
        return [];
    }

    /**
     * Write file contents
     */
    private function writeFile(): void
    {
        if (!$this->fileIsValid) {
            throw new \InvalidArgumentException(\sprintf("'%s': cannot write file, file is not ours!"));
        }
        \file_put_contents($this->filename, "<?php\nreturn ".\var_export($this->data, true).";");
    }

    /**
     * {@inheritdoc}
     */
    public function store(string $identifier, array $names, array $types): void
    {
        if (null === $this->data) {
            $this->data = $this->loadFile();
        }
        $this->data[$identifier] = [$names, $types];
        $this->writeFile();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(string $identifier): ?ResultMetadata
    {
        if (null === $this->data) {
            $this->data = $this->loadFile();
        }
        if (isset($this->data[$identifier])) {
            return new DefaultResultMetadata(...$this->data[$identifier]);
        }
        return null;
    }
}
