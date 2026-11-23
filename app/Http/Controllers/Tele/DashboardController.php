<?php

namespace App\Http\Controllers\Tele;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // users.level values that may see region-wide statistics
    private const VIEWER_LEVELS = ['superadmin'];

    // For now every signed-in user may see the dashboard. Set to false to limit it
    // to VIEWER_LEVELS again, together with DASHBOARD_FOR_EVERYONE in the frontend's
    // src/utils/roles.ts.
    private const OPEN_TO_EVERYONE = true;

    /**
     * Region-wide teleconsultation statistics for the admin Dashboard tab:
     * totals and consultations per month over the last 12 months.
     */
    public function stats(Request $request)
    {
        if (!self::OPEN_TO_EVERYONE && !in_array($request->user()?->level, self::VIEWER_LEVELS, true)) {
            abort(403, 'Only administrators can view the dashboard.');
        }

        $today = Carbon::today()->toDateString();

        $totals = DB::table('teleconsults')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(is_finished = 1) AS finished')
            ->selectRaw('SUM(COALESCE(is_finished, 0) <> 1 AND date_meeting >= ?) AS upcoming', [$today])
            ->first();

        // Last 12 calendar months including this one, with empty months filled in
        $start = Carbon::today()->startOfMonth()->subMonths(11);

        $counts = DB::table('teleconsults')
            ->selectRaw("DATE_FORMAT(date_meeting, '%Y-%m') AS month")
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(is_finished = 1) AS finished')
            ->where('date_meeting', '>=', $start->toDateString())
            ->where('date_meeting', '<', Carbon::today()->startOfMonth()->addMonth()->toDateString())
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $monthly = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $row = $counts->get($month->format('Y-m'));

            $monthly[] = [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'total' => (int) ($row->total ?? 0),
                'finished' => (int) ($row->finished ?? 0),
            ];
        }

        return response()->json([
            'totals' => [
                'consultations' => (int) $totals->total,
                'upcoming' => (int) $totals->upcoming,
                'finished' => (int) $totals->finished,
                'patients' => DB::table('tbl_master_patient')->count(),
            ],
            'monthly' => $monthly,
        ]);
    }
}
