<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Import;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Services\PortfolioSpreadsheetImporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PortfolioImportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        @unlink(storage_path('framework/testing-cartera.xlsx'));
        parent::tearDown();
    }

    public function test_preview_red_withdrawal_historical_payment_and_idempotence(): void
    {
        $this->seed(DatabaseSeeder::class);
        $path = storage_path('framework/testing-cartera.xlsx');
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('CARTERA SANTA ROSA');
        $sheet->setCellValue('A4', 'NOMBRES Y APELLIDOS')->setCellValue('C4', 'HORARIO')->setCellValue('H4', 'VALOR CURSO');
        $sheet->setCellValue('K3', '4/7/2026')->setCellValue('A5', 'Maria Prueba Retirada')->setCellValue('C5', 'Sabado 8 AM')->setCellValue('D5', '3001234567')->setCellValue('H5', 500000)->setCellValue('K5', 100000)->setCellValue('L5', 'NEQUI');
        $sheet->setCellValue('A6', 'Luis Datos Pendientes')->setCellValue('C6', 'Domingo 9 AM')->setCellValue('K6', 50000)->setCellValue('L6', 'REC-X');
        $sheet->getStyle('A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
        (new Xlsx($book))->save($path);
        $organization = Organization::firstOrFail();
        $campus = Campus::firstOrFail();
        $user = User::firstOrFail();
        $import = Import::create(['organization_id' => $organization->id, 'campus_id' => $campus->id, 'uploaded_by' => $user->id, 'original_filename' => 'test.xlsx', 'stored_path' => $path, 'file_hash' => hash_file('sha256', $path), 'status' => 'previewed']);
        $service = app(PortfolioSpreadsheetImporter::class);
        $summary = $service->preview($path, $import);
        $this->assertSame(2, $summary['students']);
        $this->assertSame(2, $summary['payments']);
        $this->assertSame(1, $summary['withdrawn']);
        $service->confirm($import->fresh());
        $service->confirm($import->fresh());
        $this->assertSame(2, Student::count());
        $this->assertFalse(Student::where('first_name', 'Maria')->firstOrFail()->active);
        $this->assertSame(2, Payment::count());
        $this->assertDatabaseHas('enrollments', ['agreed_amount' => null, 'data_status' => 'needs_review']);
        $this->assertSame('nequi', Payment::where('reference', 'NEQUI')->firstOrFail()->method);
    }
}
