<?php

use App\Controllers\Admin;
use CodeIgniter\Test\CIUnitTestCase;

final class AdminSecurityTest extends CIUnitTestCase
{
    public function testCsvFormulaValuesAreExportedAsText(): void
    {
        $method = new ReflectionMethod(Admin::class, 'safeCsvValue');
        $admin = new Admin();

        $this->assertSame("'=2+2", $method->invoke($admin, '=2+2'));
        $this->assertSame("'  +SUM(A1:A2)", $method->invoke($admin, '  +SUM(A1:A2)'));
        $this->assertSame('Normal customer inquiry', $method->invoke($admin, 'Normal customer inquiry'));
    }
}
