<?php

namespace App\Livewire\Students;

use App\Models\Student;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StudentTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::query()->with(['campus'])->withCount('enrollments')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('document_number', 'like', '%'.$this->search.'%')->orWhere('first_name', 'like', '%'.$this->search.'%')->orWhere('last_name', 'like', '%'.$this->search.'%')))
            ->when($this->status !== 'all', fn ($q) => $q->where('active', $this->status === 'active'))
            ->orderBy('last_name')->orderBy('first_name')->paginate(12);

        return view('livewire.students.student-table', compact('students'))->layout('layouts.app', ['title' => 'Estudiantes']);
    }
}
