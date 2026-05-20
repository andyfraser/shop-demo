<?php
namespace App\Services;

interface CsvServiceInterface {
    /**
     * Export data to a CSV stream.
     * @param resource $stream
     * @param array $headers
     * @param iterable $data
     */
    public function export($stream, array $headers, iterable $data): void;

    /**
     * Import data from a CSV file.
     * @param string $filePath
     * @return array
     */
    public function import(string $filePath): array;
}
