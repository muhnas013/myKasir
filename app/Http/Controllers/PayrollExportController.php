<?php

namespace App\Http\Controllers;

use App\Exports\WageSummaryExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class PayrollExportController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('settings.manage');

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $filename = 'penggajian-'.$validated['start'].'-sd-'.$validated['end'].'.xlsx';

        return Excel::download(
            new WageSummaryExport($validated['start'], $validated['end']),
            $filename
        );
    }
}
