@php
    use App\Support\Terbilang;

    $brand = $claim->brand;
    $logo = ['AB' => '/logo-brand/marks/aeris.png', 'FTI' => '/logo-brand/marks/fti.png', 'K+A' => '/logo-brand/marks/kin.png'][$brand?->short] ?? null;
    $bankReady = filled($brand?->bank_name) && filled($brand?->bank_account_number);
    $money = fn (int $amount) => number_format($amount, 0, '.', ',');
    $addressLines = preg_split('/\r?\n/', (string) $brand?->company_address, -1, PREG_SPLIT_NO_EMPTY);
    $toLines = $claim->isDelivery()
        ? preg_split('/\r?\n|,\s*/', (string) $claim->shipping_address, -1, PREG_SPLIT_NO_EMPTY)
        : ['Ambil di gudang', config('claimly.warehouse_address')];
    $row = 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $claim->invoice_number }} · Product Claim</title>
    <link rel="icon" href="/favicon.ico?v=2" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0ec; color: #111; font: 12px/1.4 Poppins, sans-serif; }
        .toolbar { max-width: 794px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a {
            border: 1px solid #d5dbd6; background: #fff; color: #1f2a24; border-radius: 999px; padding: 8px 18px;
            font: 600 13px Poppins, sans-serif; cursor: pointer; text-decoration: none;
        }
        .toolbar button { background: #174f3b; border-color: #174f3b; color: #fff; }
        .sheet { width: 794px; min-height: 1060px; margin: 16px auto 40px; background: #fff; padding: 48px 56px; box-shadow: 0 10px 30px rgba(20, 40, 30, .1); }

        .head { display: flex; justify-content: space-between; align-items: center; }
        .head img { height: 30px; }
        .head .wordmark { font-size: 26px; letter-spacing: .35em; font-weight: 500; }
        .head h1 { margin: 0 40px 0 0; font-size: 22px; font-weight: 700; letter-spacing: .02em; }

        .from-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 22px; }
        .from { font-weight: 700; text-transform: uppercase; line-height: 1.45; }
        .from .address { text-transform: none; }
        .meta { margin: 30px 70px 0 0; border-collapse: collapse; }
        .meta td { padding: 0 8px 0 0; font-size: 11px; vertical-align: top; }

        .to { display: flex; gap: 8px; margin-top: 40px; font-size: 11px; }
        .to-label { font-weight: 700; white-space: nowrap; }
        .to-body { max-width: 300px; }
        .to-body .lines { margin-top: 2px; padding-left: 10px; font-weight: 700; font-size: 12px; line-height: 1.35; }
        .to-body .contact { margin-top: 6px; text-align: right; font-size: 11px; }

        table.items { width: 100%; margin-top: 22px; border-collapse: collapse; font-size: 11px; }
        .items th { background: #000; color: #fff; font-size: 9px; font-weight: 700; padding: 3px 6px; border: 1px solid #000; }
        .items td { border: 1px solid #000; padding: 3px 6px; vertical-align: middle; }
        .items .no { width: 28px; text-align: center; }
        .items .desc { width: 190px; text-align: center; }
        .items .qty { width: 36px; text-align: center; }
        .items .cur { width: 30px; border-right: 0; }
        .items .amt { width: 72px; text-align: right; border-left: 0; }
        .items .disc { text-align: right; }
        .items .blank { border: 0; }
        .items .total-label { background: #000; color: #fff; text-align: center; font-weight: 700; font-style: italic; }
        .items .total-cur, .items .total-amt { background: #ffef3a; font-weight: 700; }

        .terbilang { display: grid; grid-template-columns: 280px 1fr; margin-top: 4px; font-style: italic; font-size: 11px; }
        .terbilang span:last-child { text-align: center; }

        .pay { margin-top: 70px; font-weight: 700; font-size: 11px; line-height: 1.6; }
        .pay .title { text-decoration: underline; }
        .pay .warn { color: #a5521b; }

        .status { margin-top: 28px; padding-top: 10px; border-top: 1px dashed #c9cfca; color: #555; font-size: 11px; }

        @media print {
            @page { size: A4; margin: 12mm; }
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .items th, .items .total-label, .items .total-cur, .items .total-amt { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 820px) {
            .sheet { width: auto; margin: 12px; padding: 24px; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="/employee/progres">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
        <div class="head">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $brand?->name }}">
            @else
                <span class="wordmark">{{ strtoupper($brand?->name ?? '') }}</span>
            @endif
            <h1>INVOICE</h1>
        </div>

        <div class="from-meta">
            <div class="from">
                <div>{{ $brand?->company_name }}</div>
                <div>{{ $brand?->name }}</div>
                <div class="address">
                    @foreach ($addressLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            </div>
            <table class="meta">
                <tr><td>NO.</td><td>{{ $claim->invoice_number }}</td></tr>
                <tr><td>Date</td><td>{{ $issuedLabel }}</td></tr>
                <tr><td>Ref.</td><td>{{ $claim->number }}</td></tr>
            </table>
        </div>

        <div class="to">
            <span class="to-label">To :</span>
            <div class="to-body">
                <div>{{ $claim->user?->name }}</div>
                <div class="lines">
                    @foreach ($toLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
                <div class="contact">{{ $claim->user?->phone ?: $claim->user?->email }}</div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th colspan="2">Unit Price</th>
                    <th>Disc</th>
                    <th colspan="2">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($claim->items as $item)
                    <tr>
                        <td class="no">{{ ++$row }}</td>
                        <td class="desc">{{ $item->product_name }}</td>
                        <td class="qty">{{ $item->quantity }}</td>
                        <td class="cur">Rp</td>
                        <td class="amt">{{ $money($item->unit_retail_price) }}</td>
                        <td class="disc">{{ $claim->discount_percent }}%</td>
                        <td class="cur">Rp</td>
                        <td class="amt">{{ $money($item->unit_claim_price * $item->quantity) }}</td>
                    </tr>
                @endforeach
                @if ($claim->isDelivery() && $claim->shipping_fee > 0)
                    <tr>
                        <td class="no">{{ ++$row }}</td>
                        <td class="desc">Ongkos kirim</td>
                        <td class="qty">1</td>
                        <td class="cur">Rp</td>
                        <td class="amt">{{ $money($claim->shipping_fee) }}</td>
                        <td class="disc">-</td>
                        <td class="cur">Rp</td>
                        <td class="amt">{{ $money($claim->shipping_fee) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="blank" colspan="3"></td>
                    <td class="total-label" colspan="3">Total</td>
                    <td class="cur total-cur">Rp</td>
                    <td class="amt total-amt">{{ $money($claim->total_due) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="terbilang">
            <span>Terbilang :</span>
            <span>{{ Terbilang::rupiah($claim->total_due) }}</span>
        </div>

        <div class="pay">
            <div class="title">Pembayaran di transfer ke Rek</div>
            @if ($bankReady)
                <div>a/n {{ strtoupper($brand->bank_account_name ?: $brand->company_name) }}</div>
                <div>{{ $brand->bank_account_number }}</div>
                <div>BANK {{ strtoupper($brand->bank_name) }}</div>
            @else
                <div>a/n {{ strtoupper($brand?->company_name ?? '') }}</div>
                <div class="warn">Rekening belum diisi admin. Hubungi Finance sebelum transfer.</div>
            @endif
        </div>

        <div class="status">
            Status: {{ $claim->statusLabel() }}
            @if ($claim->paid_total > 0)
                · Sudah dibayar Rp {{ $money($claim->paid_total) }}
                @if ($claim->remainingDue() > 0)
                    · Sisa Rp {{ $money($claim->remainingDue()) }}
                @endif
            @endif
            @if ($claim->refund_total > 0)
                · Lebih bayar Rp {{ $money($claim->refund_total) }}{{ $claim->refunded_at ? ' (sudah dikembalikan)' : '' }}
            @endif
            · Cantumkan {{ $claim->invoice_number }} di berita transfer.
        </div>
    </main>
</body>
</html>
