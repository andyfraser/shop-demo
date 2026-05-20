<?php
namespace App\Services;

class CsvService implements CsvServiceInterface {
    public function export($stream, array $headers, iterable $data): void {
        fputcsv($stream, $headers, ",", "\"", "\\");
        foreach ($data as $row) {
            fputcsv($stream, (array)$row, ",", "\"", "\\");
        }
    }

    public function import(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("CSV file not found or not readable.");
        }

        $results = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 1000, ",", "\"", "\\");
            if ($headers) {
                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $results[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return $results;
    }
}
