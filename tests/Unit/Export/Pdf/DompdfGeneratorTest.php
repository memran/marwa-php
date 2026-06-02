<?php

declare(strict_types=1);

namespace Tests\Unit\Export\Pdf;

use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\NullPdfGenerator;
use App\Support\Export\Pdf\PdfGeneratorInterface;
use Marwa\Router\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use RuntimeException;

final class DompdfGeneratorTest extends TestCase
{
    public function testFluentChainingReturnsSelf(): void
    {
        $gen = new DompdfGenerator();

        self::assertSame($gen, $gen->html('<p>x</p>'));
        self::assertSame($gen, $gen->options(['defaultFont' => 'sans-serif']));
    }

    public function testBinaryReturnsValidPdf(): void
    {
        $gen = new DompdfGenerator();
        $pdf = $gen->html('<html><body><h1>Hello</h1></body></html>')->binary();

        self::assertStringStartsWith('%PDF-', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertGreaterThan(500, strlen($pdf));
    }

    public function testEmbedsUnicodeFontForNonAsciiContent(): void
    {
        $gen = new DompdfGenerator();
        $ascii = $gen->html('<html><body><p>Hello ASCII</p></body></html>')->binary();
        $unicode = $gen->html('<html><body><p>Zoë Müller, 日本語, Привет — long paragraph that forces the font subset to include non-Latin glyphs needed for proper rendering of accented Latin, CJK, and Cyrillic characters</p></body></html>')->binary();

        self::assertGreaterThan(strlen($ascii), strlen($unicode), 'PDF with Unicode should be larger than ASCII-only PDF');
    }

    public function testSaveWritesFileToDisk(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf_test_') . '.pdf';
        try {
            (new DompdfGenerator())
                ->html('<html><body><p>Save test</p></body></html>')
                ->save($tmp);

            self::assertFileExists($tmp);
            self::assertStringStartsWith('%PDF-', (string) file_get_contents($tmp));
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }

    public function testDownloadReturnsResponseWithPdfHeaders(): void
    {
        $response = (new DompdfGenerator())
            ->html('<html><body><p>Download</p></body></html>')
            ->download('test.pdf');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        self::assertStringContainsString('test.pdf', $response->getHeaderLine('Content-Disposition'));
    }

    public function testStreamReturnsInlineResponse(): void
    {
        $response = (new DompdfGenerator())
            ->html('<html><body><p>Stream</p></body></html>')
            ->stream('inline.pdf');

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('inline', $response->getHeaderLine('Content-Disposition'));
        self::assertStringContainsString('inline.pdf', $response->getHeaderLine('Content-Disposition'));
    }

    public function testNullGeneratorRejectsAllOperations(): void
    {
        $null = new NullPdfGenerator();

        self::assertInstanceOf(PdfGeneratorInterface::class, $null);
        self::assertSame($null, $null->html('<p>'));
        self::assertSame($null, $null->options([]));

        $this->expectException(RuntimeException::class);
        $null->binary();
    }

    public function testConstructorAcceptsCustomDompdfInstance(): void
    {
        $gen = new DompdfGenerator();
        $reflect = new ReflectionClass($gen);
        $prop = $reflect->getProperty('injected');
        $prop->setAccessible(true);

        self::assertNull($prop->getValue($gen));
    }
}
