<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Total users
        $totalUsers = User::count();

        // Active users
        $activeToday = User::whereDate('last_login_at', now()->toDateString())->get();
        $activeThisMonth = User::whereBetween('last_login_at', [now()->startOfMonth(), now()->endOfMonth()])->get();

        // Total transactions across several tables
        $tables = ['pemasukan', 'pengeluaran', 'transfer', 'utang', 'piutang'];
        $totalTransactions = 0;
        foreach ($tables as $t) {
            if (DB::getSchemaBuilder()->hasTable($t)) {
                $totalTransactions += DB::table($t)->count();
            }
        }

        // Growth: new users per month for last 12 months
        $usersPerMonth = DB::table('users')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as total")
            ->whereNotNull('created_at')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        // ensure months exist in series
        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $labels[] = $m;
            $data[] = $usersPerMonth->get($m, 0);
        }

        // === ANALYTICS DATA ===
        // Total Income & Expense
        $totalIncome = 0;
        $totalExpense = 0;
        if (DB::getSchemaBuilder()->hasTable('pemasukan')) {
            $totalIncome = DB::table('pemasukan')->sum('jumlah') ?? 0;
        }
        if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
            $totalExpense = DB::table('pengeluaran')->sum('jumlah') ?? 0;
        }

        // Top Categories (Pemasukan)
        $topIncomeCategories = [];
        if (DB::getSchemaBuilder()->hasTable('pemasukan')) {
            $topIncomeCategories = DB::table('pemasukan')
                ->select('kategori', DB::raw('count(*) as count, sum(jumlah) as total'))
                ->groupBy('kategori')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
        }

        // Top Categories (Pengeluaran)
        $topExpenseCategories = [];
        if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
            $topExpenseCategories = DB::table('pengeluaran')
                ->select('kategori', DB::raw('count(*) as count, sum(jumlah) as total'))
                ->groupBy('kategori')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();
        }

        // Monthly Income/Expense Trend
        $incomeMonthly = [];
        $expenseMonthly = [];
        if (DB::getSchemaBuilder()->hasTable('pemasukan')) {
            $incomeMonthly = DB::table('pemasukan')
                ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as month, sum(jumlah) as total")
                ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('total', 'month');
        }
        if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
            $expenseMonthly = DB::table('pengeluaran')
                ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as month, sum(jumlah) as total")
                ->where('tanggal', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('total', 'month');
        }

        // Build consistent labels for monthly trend
        $trendLabels = [];
        $incomeData = [];
        $expenseData = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i)->format('Y-m');
            $trendLabels[] = $m;
            $incomeData[] = $incomeMonthly->get($m, 0);
            $expenseData[] = $expenseMonthly->get($m, 0);
        }

        return view('admin.dashboard', compact(
            'totalUsers', 'activeToday', 'activeThisMonth', 'totalTransactions', 'labels', 'data',
            'totalIncome', 'totalExpense', 'topIncomeCategories', 'topExpenseCategories',
            'trendLabels', 'incomeData', 'expenseData'
        ));
    }

    public function userInsights(Request $request)
    {
        // Most active users (by login)
        $mostActiveUsers = User::whereNotNull('last_login_at')
            ->orderBy('last_login_at', 'desc')
            ->limit(10)
            ->get();

        // Top spenders
        $topSpenders = [];
        if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
            $topSpenders = DB::table('pengeluaran')
                ->select('user_id', DB::raw('count(*) as transaction_count, sum(jumlah) as total_spent'))
                ->groupBy('user_id')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            // Attach user data
            $topSpenders = $topSpenders->map(function($record) {
                $user = User::find($record->user_id);
                return (object) array_merge((array)$record, ['user' => $user]);
            });
        }

        // User registration trend (last 30 days)
        $registrationTrend = DB::table('users')
            ->selectRaw("DATE(created_at) as date, count(*) as total")
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Average spending per user
        $avgSpending = 0;
        $avgIncome = 0;
        if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
            $avgSpending = DB::table('pengeluaran')
                ->selectRaw('sum(jumlah) / count(distinct user_id) as avg')
                ->value('avg') ?? 0;
        }
        if (DB::getSchemaBuilder()->hasTable('pemasukan')) {
            $avgIncome = DB::table('pemasukan')
                ->selectRaw('sum(jumlah) / count(distinct user_id) as avg')
                ->value('avg') ?? 0;
        }

        // Total per user breakdown
        $userStats = User::where('is_banned', 0)
            ->orWhereNull('is_banned')
            ->limit(20)
            ->get()
            ->map(function($user) {
                $spending = 0;
                $income = 0;
                if (DB::getSchemaBuilder()->hasTable('pengeluaran')) {
                    $spending = DB::table('pengeluaran')->where('user_id', $user->id)->sum('jumlah') ?? 0;
                }
                if (DB::getSchemaBuilder()->hasTable('pemasukan')) {
                    $income = DB::table('pemasukan')->where('user_id', $user->id)->sum('jumlah') ?? 0;
                }
                return (object) array_merge((array)$user, ['total_spending' => $spending, 'total_income' => $income]);
            });

        return view('admin.user-insights', compact(
            'mostActiveUsers', 'topSpenders', 'registrationTrend',
            'avgSpending', 'avgIncome', 'userStats'
        ));
    }

    public function usersIndex(Request $request)
    {
        $query = User::query();

        // search by username or email
        if ($q = $request->query('q')) {
            $query->where(function($q2) use ($q) {
                $q2->where('username', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        // filter by status
        if ($status = $request->query('status')) {
            if ($status === 'banned') {
                $query->where('is_banned', 1);
            } elseif ($status === 'active') {
                $query->where(function($q3) {
                    $q3->where('is_banned', 0)->orWhereNull('is_banned');
                });
            }
        }

        $per = (int) $request->query('per', 25);
        $per = in_array($per, [10,25,50]) ? $per : 25;
        $users = $query->orderBy('created_at', 'desc')->paginate($per)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function usersShow(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function ban(Request $request, User $user)
    {
        $user->is_banned = !$user->is_banned;
        $user->save();

        // Log activity
        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, $user->is_banned ? 'user.ban' : 'user.unban', $user, 'Admin toggled ban status for user');
        }

        return back()->with('success', $user->is_banned ? 'User diblokir.' : 'User dibuka blokir.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $temp = 'Reset1234';
        $user->password = \Illuminate\Support\Facades\Hash::make($temp);
        $user->save();

        // Log activity
        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'user.reset_password', $user, 'Admin reset password for user');
        }

        // Optionally: mail user the temporary password or log activity
        return back()->with('success', 'Password direset ke: ' . $temp);
    }
}
