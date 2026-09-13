<?php

namespace App\Http\Controllers;

use App\Models\ChargeSchedule;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORTS = [
        'portfolio' => 'Cartera general', 'overdue' => 'Cartera vencida', 'revenue' => 'Recaudo',
        'student-payments' => 'Pagos por estudiante', 'enrollments' => 'Estado de matrículas',
        'high-delinquency' => 'Más de 3 obligaciones vencidas', 'groups' => 'Resumen por curso / grupo',
    ];

    public function index(Request $request)
    {
        $this->authorizeUser($request);

        return view('reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report)
    {
        $this->authorizeUser($request);
        abort_unless(isset(self::REPORTS[$report]), 404);
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'group_id' => ['nullable', 'exists:groups,id'], 'student_id' => ['nullable', 'exists:students,id'], 'status' => ['nullable', 'string']]);
        $organizationId = $request->user()->organization_id;
        $query = $this->query($request, $report, $organizationId);

        if ($request->string('format')->toString() === 'csv') {
            return $this->csv($report, $query->get());
        }

        $totals = $this->totals($report, (clone $query)->get());
        $records = $query->paginate(25)->withQueryString();
        $groups = Group::query()->whereHas('course.program', fn ($q) => $q->where('organization_id', $organizationId))->with('course')->orderBy('name')->get();
        $students = Student::where('organization_id', $organizationId)->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('reports.show', ['report' => $report, 'title' => self::REPORTS[$report], 'records' => $records, 'totals' => $totals, 'groups' => $groups, 'students' => $students]);
    }

    private function query(Request $request, string $report, string $organizationId)
    {
        if (in_array($report, ['revenue', 'student-payments'], true)) {
            return Payment::query()->where('payments.status', 'valid')->whereHas('enrollment.student', fn ($q) => $q->where('organization_id', $organizationId))
                ->with(['enrollment.student', 'enrollment.group.course', 'receipt'])
                ->when($request->filled('from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date('to')))
                ->when($request->filled('student_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('student_id', $request->string('student_id'))))
                ->when($request->filled('group_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('group_id', $request->string('group_id'))))
                ->latest('payment_date');
        }
        if ($report === 'overdue') {
            return ChargeSchedule::query()->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today())
                ->whereHas('enrollment.student', fn ($q) => $q->where('organization_id', $organizationId))->with(['enrollment.student', 'enrollment.group.course'])
                ->when($request->filled('student_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('student_id', $request->string('student_id'))))
                ->when($request->filled('group_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('group_id', $request->string('group_id'))))->orderBy('due_date');
        }
        if ($report === 'high-delinquency') {
            return Enrollment::query()->whereHas('student', fn ($q) => $q->where('organization_id', $organizationId))
                ->whereHas('chargeSchedules', fn ($q) => $q->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today()), '>', 3)
                ->with(['student', 'group.course'])->withCount(['chargeSchedules as overdue_count' => fn ($q) => $q->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today())])
                ->withSum(['chargeSchedules as overdue_amount' => fn ($q) => $q->where('status', 'pending')->whereNotNull('due_date')->whereDate('due_date', '<', today())], 'amount')
                ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')))
                ->when($request->filled('group_id'), fn ($q) => $q->where('group_id', $request->string('group_id')))->orderByDesc('overdue_count');
        }
        if ($report === 'groups') {
            return Group::query()->whereHas('course.program', fn ($q) => $q->where('organization_id', $organizationId))->with('course')
                ->withCount('enrollments')->withSum('enrollments as agreed_total', 'agreed_amount')
                ->when($request->filled('group_id'), fn ($q) => $q->whereKey($request->string('group_id')))->orderBy('name');
        }

        return Enrollment::query()->whereHas('student', fn ($q) => $q->where('organization_id', $organizationId))->with(['student', 'group.course'])
            ->withSum('validPayments as valid_payments_sum_amount', 'amount')
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->string('student_id')))
            ->when($request->filled('group_id'), fn ($q) => $q->where('group_id', $request->string('group_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('enrollment_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('enrollment_date', '<=', $request->date('to')))->latest();
    }

    private function totals(string $report, $records): array
    {
        return match ($report) {
            'revenue', 'student-payments' => ['Registros' => $records->count(), 'Total recaudado' => $records->sum('amount')],
            'overdue' => ['Obligaciones vencidas' => $records->count(), 'Total vencido' => $records->sum('amount')],
            'high-delinquency' => ['Estudiantes' => $records->count(), 'Obligaciones' => $records->sum('overdue_count')],
            'groups' => ['Grupos' => $records->count(), 'Matrículas' => $records->sum('enrollments_count')],
            default => ['Matrículas' => $records->count(), 'Valor acordado' => $records->sum('agreed_amount'), 'Pagado' => $records->sum('total_paid'), 'Saldo' => $records->sum(fn ($item) => $item->balance ?? 0)],
        };
    }

    private function csv(string $report, $records): StreamedResponse
    {
        return response()->streamDownload(function () use ($report, $records) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Informe', 'Estudiante/Grupo', 'Fecha/Estado', 'Valor'], ';');
            foreach ($records as $record) {
                if ($record instanceof Payment) {
                    $row = [$report, $record->enrollment->student->full_name, $record->payment_date?->format('Y-m-d'), $record->amount];
                } elseif ($record instanceof ChargeSchedule) {
                    $row = [$report, $record->enrollment->student->full_name, $record->due_date?->format('Y-m-d'), $record->amount];
                } elseif ($record instanceof Group) {
                    $row = [$report, $record->course->name.' / '.$record->name, 'Matrículas: '.$record->enrollments_count, $record->agreed_total];
                } else {
                    $row = [$report, $record->student->full_name, $record->status->value, $report === 'high-delinquency' ? $record->overdue_amount : $record->balance];
                }
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, 'edutech-'.$report.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function authorizeUser(Request $request): void
    {
        abort_unless(in_array($request->user()->role->value, ['superadmin', 'admin', 'cartera', 'coordinador', 'consulta'], true), 403);
    }
}
