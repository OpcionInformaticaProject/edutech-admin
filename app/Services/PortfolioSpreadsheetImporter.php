<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Campus;
use App\Models\Contact;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PortfolioSpreadsheetImporter
{
    public function __construct(private ReceiptNumberGenerator $numbers) {}

    public function preview(string $path, Import $import): array
    {
        $sheet = IOFactory::load($path)->getSheetByName('CARTERA SANTA ROSA');
        abort_unless($sheet, 422, 'No existe la hoja CARTERA SANTA ROSA.');
        $summary = ['students' => 0, 'payments' => 0, 'withdrawn' => 0, 'inconsistencies' => 0, 'non_numeric_receipts' => 0, 'duplicate_receipts' => 0, 'unnormalized_schedules' => 0];
        $receiptCounts = [];
        for ($row = 5; $row <= $sheet->getHighestDataRow(); $row++) {
            $name = trim((string) $sheet->getCell('A'.$row)->getFormattedValue());
            if ($name === '') {
                continue;
            }
            $issues = [];
            $schedule = trim((string) $sheet->getCell('C'.$row)->getFormattedValue());
            $amount = $this->money($sheet->getCell('H'.$row)->getValue());
            if ($amount === null) {
                $issues[] = 'Valor de curso ausente o inválido';
            }
            if ($schedule === '' || ! preg_match('/(sab|sáb|dom|lunes|martes|miércoles|jueves|viernes)/iu', $schedule)) {
                $issues[] = 'Horario no normalizado';
                $summary['unnormalized_schedules']++;
            }
            $allText = mb_strtolower(implode(' ', array_map('strval', $sheet->rangeToArray('A'.$row.':GD'.$row, null, true, true, false)[0])));
            $red = $this->isRed($sheet, $row);
            $textWithdrawal = preg_match('/retirad[oa]|retitad[oa]|nunca asistio|van a retirar/iu', $allText) === 1 || $this->hasRedOperationalAnnotation($sheet, $row);
            $withdrawn = $red || $textWithdrawal;
            $withdrawnReason = $red ? 'red' : ($textWithdrawal ? 'text' : null);
            $payments = [];
            foreach (range(11, 89, 2) as $index => $columnIndex) {
                $column = Coordinate::stringFromColumnIndex($columnIndex);
                $receiptColumn = Coordinate::stringFromColumnIndex($columnIndex + 1);
                $paymentAmount = $this->money($sheet->getCell($column.$row)->getValue());
                $reference = trim((string) $sheet->getCell($receiptColumn.$row)->getFormattedValue());
                if ($paymentAmount === null) {
                    continue;
                }
                $date = $this->date($sheet->getCell($column.'3')->getValue());
                $method = mb_stripos($reference, 'nequi') !== false ? 'nequi' : 'historical';
                $payments[] = ['column' => $column, 'amount' => $paymentAmount, 'date' => $date, 'reference' => $reference ?: null, 'method' => $method];
                if ($reference !== '' && ! ctype_digit($reference)) {
                    $summary['non_numeric_receipts']++;
                }
                if ($reference !== '') {
                    $receiptCounts[$reference] = ($receiptCounts[$reference] ?? 0) + 1;
                }
            }
            $notes = trim(implode(' | ', array_filter([(string) $sheet->getCell('DZ'.$row)->getFormattedValue(), (string) $sheet->getCell('EA'.$row)->getFormattedValue(), (string) $sheet->getCell('EB'.$row)->getFormattedValue(), preg_match('/retirada|retitada|cambio de programa/iu', $allText, $match) ? $match[0] : null])));
            $raw = $sheet->rangeToArray('A'.$row.':GD'.$row, null, true, true, true)[$row];
            $data = ['name' => $name, 'schedule' => $schedule, 'phone' => trim((string) $sheet->getCell('D'.$row)->getFormattedValue()), 'phone_2' => trim((string) $sheet->getCell('E'.$row)->getFormattedValue()), 'agreed_amount' => $amount, 'payments' => $payments, 'withdrawn' => $withdrawn, 'withdrawn_reason' => $withdrawnReason, 'notes' => $notes];
            ImportRow::create(['import_id' => $import->id, 'source_sheet' => $sheet->getTitle(), 'source_row' => $row, 'source_column' => 'A:GD', 'source_raw_value' => $raw, 'fingerprint' => hash('sha256', json_encode($raw)), 'normalized_data' => $data, 'inconsistencies' => $issues, 'status' => $issues === [] ? 'valid' : 'warning']);
            $summary['students']++;
            $summary['payments'] += count($payments);
            $summary['withdrawn'] += (int) $withdrawn;
            $summary['inconsistencies'] += count($issues);
        }
        $summary['duplicate_receipts'] = collect($receiptCounts)->filter(fn ($count) => $count > 1)->count();
        $import->update(['main_sheet' => $sheet->getTitle(), 'summary' => $summary, 'mapping' => ['header_row' => 4, 'student' => 'A', 'schedule' => 'C', 'phones' => ['D', 'E'], 'agreed_amount' => 'H', 'payments' => 'K:CK odd columns', 'receipts' => 'L:CL even columns', 'observations' => ['DZ', 'EA', 'EB']]]);

        return $summary;
    }

    public function confirm(Import $import): array
    {
        return DB::transaction(function () use ($import) {
            $campus = Campus::findOrFail($import->campus_id);
            $program = Program::firstOrCreate(['organization_id' => $import->organization_id, 'code' => 'HIST-SR'], ['name' => 'Programa histórico Santa Rosa', 'active' => true]);
            $course = Course::firstOrCreate(['program_id' => $program->id, 'code' => 'CARTERA'], ['name' => 'Curso importado de cartera', 'active' => true]);
            foreach ($import->rows as $row) {
                $data = $row->normalized_data;
                [$first,$last] = $this->splitName($data['name']);
                $sourceKey = hash('sha256', $campus->id.'|'.Str::lower($data['name']).'|'.$data['phone']);
                $student = Student::firstOrCreate(['source_key' => $sourceKey], ['organization_id' => $import->organization_id, 'campus_id' => $campus->id, 'first_name' => $first, 'last_name' => $last, 'phone' => $data['phone'] ?: null, 'active' => ! $data['withdrawn']]);
                if ($data['phone_2'] !== '') {
                    Contact::firstOrCreate(['student_id' => $student->id, 'phone' => $data['phone_2']], ['name' => $data['name'], 'relationship' => 'Contacto historico sin identificar']);
                }
                $group = Group::firstOrCreate(['course_id' => $course->id, 'campus_id' => $campus->id, 'name' => mb_substr($data['schedule'] ?: 'Horario sin normalizar', 0, 255)], ['schedule' => $data['schedule'] ?: 'Sin definir', 'active' => true]);
                $enrollment = Enrollment::firstOrCreate(['student_id' => $student->id, 'group_id' => $group->id], ['enrollment_date' => null, 'billing_start_date' => null, 'agreed_amount' => $data['agreed_amount'], 'data_status' => $data['agreed_amount'] === null ? 'needs_review' : 'complete', 'historical' => true, 'status' => $data['withdrawn'] ? EnrollmentStatus::Withdrawn : EnrollmentStatus::Active, 'notes' => $data['notes']]);
                $enrollment->update(['historical' => true, 'status' => $data['withdrawn'] ? EnrollmentStatus::Withdrawn : EnrollmentStatus::Active]);
                foreach ($data['payments'] as $paymentData) {
                    $sourceKey = $import->file_hash.'|'.$row->source_row.'|'.$paymentData['column'];
                    $payment = Payment::firstOrCreate(['source_key' => $sourceKey], ['enrollment_id' => $enrollment->id, 'payment_date' => $paymentData['date'], 'amount' => $paymentData['amount'], 'method' => $paymentData['method'], 'reference' => $paymentData['reference'], 'notes' => 'Importado del Excel histórico', 'registered_by' => $import->uploaded_by, 'import_row_id' => $row->id, 'status' => 'valid', 'historical' => true]);
                    $payment->update(['import_row_id' => $row->id]);
                    $this->historicalReceipt($payment, $campus, $enrollment, $import);
                }
                $row->update(['student_id' => $student->id, 'enrollment_id' => $enrollment?->id, 'status' => 'imported']);
            }
            $import->update(['status' => 'confirmed', 'confirmed_at' => now()]);

            return $import->summary;
        });
    }

    private function historicalReceipt(Payment $payment, Campus $campus, Enrollment $enrollment, Import $import): void
    {
        if ($payment->receipt()->exists()) {
            return;
        }

        [$sequence, $number] = $this->numbers->next($campus);
        $newBalance = $enrollment->fresh()->balance;
        Receipt::create([
            'organization_id' => $import->organization_id,
            'campus_id' => $campus->id,
            'payment_id' => $payment->id,
            'sequence' => $sequence,
            'number' => $number,
            'legacy_reference' => $payment->reference,
            'issued_on' => $payment->payment_date,
            'student_name' => $enrollment->student->full_name,
            'course_name' => $enrollment->group->course->name,
            'previous_balance' => $newBalance === null ? null : $newBalance + (float) $payment->amount,
            'payment_amount' => $payment->amount,
            'new_balance' => $newBalance,
            'payment_method' => $payment->method,
            'cashier_name' => $import->uploader?->name ?? 'Importacion historica',
            'issued_by' => $import->uploaded_by,
        ]);
    }

    private function money(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value > 0 ? (float) $value : null;
        }

        $digits = preg_replace('/[^0-9-]/', '', (string) $value);

        return $digits !== '' && (float) $digits > 0 ? (float) $digits : null;
    }

    private function date(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $value = trim((string) $value);
        if (! preg_match('/\b\d{4}\b/', $value)) {
            return null;
        }

        foreach (['d/m/Y', 'j/n/Y', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function isRed(Worksheet $sheet, int $row): bool
    {
        // El bloque maestro A:J usa rojo como estado del estudiante. Los rojos en
        // columnas posteriores pertenecen a anotaciones operativas, no al estado.
        foreach (range(1, 10) as $column) {
            $style = $sheet->getStyle(Coordinate::stringFromColumnIndex($column).$row);
            foreach ([$style->getFont()->getColor(), $style->getFill()->getStartColor()] as $color) {
                $rgb = strtoupper((string) $color->getRGB());
                if ($rgb === 'FF0000') {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasRedOperationalAnnotation(Worksheet $sheet, int $row): bool
    {
        foreach (range(11, 190) as $column) {
            $style = $sheet->getStyle(Coordinate::stringFromColumnIndex($column).$row);
            if (strtoupper((string) $style->getFont()->getColor()->getRGB()) === 'FF0000' || strtoupper((string) $style->getFill()->getStartColor()->getRGB()) === 'FF0000') {
                return true;
            }
        }

        return false;
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) < 3) {
            return [$parts[0] ?? $name, implode(' ', array_slice($parts, 1)) ?: '(sin apellido)'];
        }

        return [implode(' ', array_slice($parts, 0, -2)), implode(' ', array_slice($parts, -2))];
    }
}
