<?php

use App\Controllers\Studio;
use CodeIgniter\Test\CIUnitTestCase;

final class StudioSecurityTest extends CIUnitTestCase
{
    public function testCsvFormulaValuesAreExportedAsText(): void
    {
        $method = new ReflectionMethod(Studio::class, 'safeCsvValue');
        $studio = new Studio();

        $this->assertSame("'=2+2", $method->invoke($studio, '=2+2'));
        $this->assertSame("'  +SUM(A1:A2)", $method->invoke($studio, '  +SUM(A1:A2)'));
        $this->assertSame('Normal customer inquiry', $method->invoke($studio, 'Normal customer inquiry'));
    }
}
