<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{translate('transaction_details')}} {{$transaction->id}}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 32px; color: #222; }
        h1 { font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        td:first-child { color: #666; width: 220px; }
    </style>
</head>
<body onload="window.print()">
<h1>MSTOO {{translate('transaction_details')}}</h1>
<table>
    <tr><td>{{translate('transaction_id')}}</td><td>{{$transaction->id}}</td></tr>
    <tr><td>{{translate('reference')}}</td><td>{{$transaction->ref_trx_id ?: '-'}}</td></tr>
    <tr><td>{{translate('type')}}</td><td>{{$transaction->trx_type}}</td></tr>
    <tr><td>{{translate('debit')}}</td><td>{{with_currency_symbol($transaction->debit)}}</td></tr>
    <tr><td>{{translate('credit')}}</td><td>{{with_currency_symbol($transaction->credit)}}</td></tr>
    <tr><td>{{translate('booking')}}</td><td>{{optional($transaction->booking)->readable_id ?: '-'}}</td></tr>
    <tr><td>{{translate('date')}}</td><td>{{optional($transaction->created_at)->toDateTimeString()}}</td></tr>
</table>
</body>
</html>
