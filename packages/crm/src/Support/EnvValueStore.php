<?php

namespace WilliamBeeler\Crm\Support;

use RuntimeException;

class EnvValueStore
{
    public function set(string $key, string $value): void
    {
        $path = app()->environmentFilePath();
        $contents = $this->read($path);
        $escapedValue = $this->quote($value);
        $pattern = "/^{$this->escapeKey($key)}=.*/m";

        if (preg_match($pattern, $contents) === 1) {
            $updatedContents = preg_replace($pattern, "{$key}={$escapedValue}", $contents, 1);

            if ($updatedContents === null) {
                throw new RuntimeException("Unable to update {$key} in the environment file.");
            }

            $this->write($path, $updatedContents);

            return;
        }

        $trimmedContents = rtrim($contents);
        $separator = $trimmedContents === '' ? '' : PHP_EOL;

        $this->write($path, $trimmedContents.$separator."{$key}={$escapedValue}".PHP_EOL);
    }

    public function remove(string $key): void
    {
        $path = app()->environmentFilePath();
        $contents = $this->read($path);
        $updatedContents = preg_replace("/^{$this->escapeKey($key)}=.*(?:\\R)?/m", '', $contents);

        if ($updatedContents === null) {
            throw new RuntimeException("Unable to remove {$key} from the environment file.");
        }

        $this->write($path, $updatedContents);
    }

    private function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read environment file at {$path}.");
        }

        return $contents;
    }

    private function write(string $path, string $contents): void
    {
        if (@file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write environment file at {$path}.");
        }
    }

    private function quote(string $value): string
    {
        $escapedValue = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

        return "\"{$escapedValue}\"";
    }

    private function escapeKey(string $key): string
    {
        return preg_quote($key, '/');
    }
}
