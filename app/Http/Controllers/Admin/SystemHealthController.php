<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    /**
     * État de la file d'attente et des inscriptions.
     *
     * Accès provisoire : env local OU e-mail présent dans ADMIN_ALERT_EMAIL.
     * À l'étape 3, cet écran passera sous le middleware du rôle admin.
     */
    public function index(Request $request): View
    {
        abort_unless($this->mayView($request), 403);

        $mailQueue = DB::table('jobs')->where('queue', 'mail')->count();

        return view('admin.system-health', [
            'mailQueue' => $mailQueue,
            'totalJobs' => DB::table('jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'pendingRegistrations' => PendingRegistration::count(),
            'queueAlert' => $mailQueue > 50,
        ]);
    }

    private function mayView(Request $request): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        $admin = config('registration.admin_email');

        return $admin && $request->user() && $request->user()->email === $admin;
    }
}
