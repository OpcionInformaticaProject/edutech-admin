<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Models\Campus;
use App\Models\Import;
use App\Services\PortfolioSpreadsheetImporter;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function create()
    {
        return view('imports.create');
    }

    public function store(StoreImportRequest $request, PortfolioSpreadsheetImporter $importer)
    {
        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());
        $existing = Import::where('organization_id', $request->user()->organization_id)->where('file_hash', $hash)->first();
        if ($existing) {
            return redirect()->route('imports.show', $existing)->with('success', 'Este archivo ya fue cargado; se reutilizó su importación.');
        }
        $path = $file->store('imports');
        $campus = Campus::where('organization_id', $request->user()->organization_id)->where('name', 'Santa Rosa')->firstOrFail();
        $import = Import::create(['organization_id' => $request->user()->organization_id, 'campus_id' => $campus->id, 'uploaded_by' => $request->user()->id, 'original_filename' => $file->getClientOriginalName(), 'stored_path' => $path, 'file_hash' => $hash, 'status' => 'previewed']);
        $importer->preview(Storage::path($path), $import);

        return redirect()->route('imports.show', $import);
    }

    public function show(Import $import)
    {
        return view('imports.show', ['import' => $import->load('rows')]);
    }

    public function confirm(Import $import, PortfolioSpreadsheetImporter $importer)
    {
        $importer->confirm($import);

        return redirect()->route('imports.show', $import)->with('success', 'Importación confirmada.');
    }
}
