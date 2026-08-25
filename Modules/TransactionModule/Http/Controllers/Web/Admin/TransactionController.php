<?php

namespace Modules\TransactionModule\Http\Controllers\Web\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Services\AnalyticsReportService;
use Modules\TransactionModule\Entities\Transaction;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(private AnalyticsReportService $reports)
    {
    }

    public function index(Request $request)
    {
        abort_unless(access_checker('transaction_management'), 403);

        $transactions = $this->reports->transactionQuery($request)
            ->paginate(pagination_limit())
            ->appends($request->query());
        $summary = $this->reports->transactionSummary($request);
        $dropdowns = $this->reports->dropdowns();
        $filters = $request->query();

        return view('transactionmodule::admin.list', compact('transactions', 'summary', 'dropdowns', 'filters'));
    }

    public function show(string $id)
    {
        abort_unless(access_checker('transaction_management'), 403);

        $transaction = Transaction::query()
            ->with(['booking.customer', 'booking.zone', 'booking.details_amounts', 'from_user.provider', 'to_user.provider'])
            ->findOrFail($id);

        return view('transactionmodule::admin.show', compact('transaction'));
    }

    public function print(string $id)
    {
        abort_unless(access_checker('transaction_management'), 403);

        $transaction = Transaction::query()
            ->with(['booking.customer', 'booking.zone', 'from_user.provider', 'to_user.provider'])
            ->findOrFail($id);

        return view('transactionmodule::admin.print', compact('transaction'));
    }

    public function download(Request $request): StreamedResponse|string
    {
        abort_unless(access_checker('transaction_management', 'export') || access_checker('transaction_management'), 403);

        $items = $this->reports->transactionQuery($request)->limit(5000)->get();
        $filename = time() . '-transactions.xlsx';
        if ($request->input('format') === 'csv') {
            $filename = time() . '-transactions.csv';
        }

        return (new FastExcel($items))->download($filename, function (Transaction $transaction) {
            return $this->exportRow($transaction);
        });
    }

    private function exportRow(Transaction $transaction): array
    {
        $booking = $transaction->booking;
        $from = $transaction->from_user;
        $to = $transaction->to_user;

        return [
            'Transaction ID' => $transaction->id,
            'Reference ID' => $transaction->ref_trx_id,
            'Customer' => trim(($from->first_name ?? '') . ' ' . ($from->last_name ?? '')) ?: ($from->email ?? '-'),
            'Provider' => optional(optional($to)->provider)->company_name
                ?: trim(($to->first_name ?? '') . ' ' . ($to->last_name ?? '')),
            'Booking ID' => optional($booking)->readable_id ?: $transaction->booking_id,
            'Type' => $transaction->trx_type,
            'Payment method' => optional($booking)->payment_method,
            'Debit' => $transaction->debit,
            'Credit' => $transaction->credit,
            'Amount' => $transaction->debit + $transaction->credit,
            'Status' => optional($booking)->booking_status,
            'Date' => optional($transaction->created_at)->toDateTimeString(),
        ];
    }
}
