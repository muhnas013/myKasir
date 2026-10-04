<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('report.view-all');

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $filename = 'laporan-'.$validated['start'].'-sd-'.$validated['end'].'.xlsx';

        return Excel::download(
            new OrdersExport($validated['start'], $validated['end']),
            $filename
        );
    }
}
