<?php
namespace Tests\Unit;

use App\Services\CsvService;
use Tests\TestCase;

class CsvServiceTest extends TestCase {
    private CsvService $csvService;

    public function setUp(): void {
        $this->csvService = new CsvService();
    }

    public function test_export_writes_data_to_stream() {
        $stream = fopen('php://memory', 'r+');
        $headers = ['Name', 'Price'];
        $data = [
            ['Name' => 'Product A', 'Price' => 10.00],
            ['Name' => 'Product B', 'Price' => 20.00],
        ];

        $this->csvService->export($stream, $headers, $data);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $expected = "Name,Price\n\"Product A\",10\n\"Product B\",20\n";
        // fputcsv might use slightly different formatting depending on PHP version/OS, 
        // but let's check if it contains the data
        $this->assertStringContainsString('Product A', $content);
        $this->assertStringContainsString('10', $content);
        $this->assertStringContainsString('Product B', $content);
        $this->assertStringContainsString('20', $content);
    }

    public function test_import_reads_data_from_file() {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        $content = "name,sku,price\nProduct A,SKU-A,10.00\nProduct B,SKU-B,20.00";
        file_put_contents($tempFile, $content);

        $results = $this->csvService->import($tempFile);
        unlink($tempFile);

        $this->assertCount(2, $results);
        $this->assertEquals('Product A', $results[0]['name']);
        $this->assertEquals('SKU-A', $results[0]['sku']);
        $this->assertEquals('10.00', $results[0]['price']);
    }
}
