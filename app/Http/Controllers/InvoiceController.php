<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Support\IndonesianDate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function show(Request $request, Claim $claim): View
    {
        $user = $request->user();

        abort_unless($user->is_admin || (int) $claim->user_id === (int) $user->id, 403);
        abort_if($claim->invoice_issued_at === null, 404, 'Invoice terbit setelah stok dicek.');

        $claim->loadMissing(['items', 'brand', 'user', 'payments']);

        return view('invoice', [
            'claim' => $claim,
            'issuedLabel' => IndonesianDate::date($claim->invoice_issued_at),
        ]);
    }
}
