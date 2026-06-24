<?php

namespace App\Http\Controllers;

use App\Models\Common\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketPrintController extends Controller
{
    public function show(Ticket $ticket)
    {
        abort_unless(
            $ticket->company_id === Auth::user()?->current_company_id,
            403,
            'Unauthorized ticket access.'
        );

        return view('print-ticket', [
            'ticket' => $ticket,
        ]);
    }
}
