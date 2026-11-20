<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\FlightDeck;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The flight deck (story 45): Mission Control's home page.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, FlightDeck $deck): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('admin/dashboard', $deck->present($user));
    }
}
