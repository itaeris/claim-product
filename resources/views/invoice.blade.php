@php
    use App\Support\Rupiah;

    $brand = $claim->brand;
    $logo = ['AB' => '/logo-brand/marks/aeris.png', 'FTI' => '/logo-brand/marks/fti.png', 'K+A' => '/logo-brand/marks/kin.png'][$brand?->short] ?? null;
    $bankReady = filled($brand?->bank_name) && filled($brand?->bank_account_number);
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f4f1; color: #1f2a24; font: 13px/1.55 Poppins, sans-serif; }
        .sheet { max-width: 820px; margin: 32px auto; background: #fff; border-radius: 16px; padding: 40px 44px; box-shadow: 0 10px 30px rgba(20, 40, 30, .08); }
        .toolbar { max-width: 820px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a { border: 1px solid #d5dbd6; background: #fff; color: #1f2a24; border-radius: 999px; padding: 8px 18px; font: 600 13px Poppins, sans-serif; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #1e5b3f; border-color: #1e5b3f; color: #fff; }
        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; border-bottom: 2px solid #1e5b3f; padding-bottom: 20px; }
        header img { height: 34px; margin-bottom: 10px; }
        h1 { margin: 0; font-size: 26px; letter-spacing: .04em; color: #1e5b3f; }
        .muted { color: #6b756f; }
        .company { font-weight: 700; font-size: 15px; }
        .meta { text-align: right; }
        .meta strong { display: block; font-size: 15px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 24px 0; }
        .label { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #6b756f; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: #6b756f; border-bottom: 1px solid #e3e7e3; padding: 8px 6px; }
        td { border-bottom: 1px solid #eef1ee; padding: 10px 6px; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: 320px; margin-top: 16px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { border-top: 2px solid #1e5b3f; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 16px; color: #1e5b3f; }
        .bank { margin-top: 28px; border: 1px dashed #b9c6bd; border-radius: 12px; padding: 16px 18px; background: #f8faf8; }
        .warn { color: #a5521b; font-weight: 600; }
        .status { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px; background: #e6f0ea; color: #1e5b3f; font-weight: 600; font-size: 12px; }
        footer { margin-top: 28px; font-size: 12px; color: #6b756f; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { box-shadow: none; margin: 0; max-width: none; border-radius: 0; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="/progres">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
        <header>
            <div>
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $brand?->name }}">
                @endif
                <div class="company">{{ $brand?->company_name }}</div>
                <div class="muted">Employee Purchase · {{ $brand?->name }}</div>
            </div>
            <div class="meta">
                <h1>INVOICE</h1>
                <strong>{{ $claim->invoice_number }}</strong>
                <div class="muted">Tanggal {{ $issuedLabel }}</div>
                <div class="muted">Ref. pesanan {{ $claim->number }}</div>
                <span class="status">{{ $claim->statusLabel() }}</span>
            </div>
        </header>

        <section class="grid">
            <div>
                <div class="label">Ditagihkan kepada</div>
                <div><strong>{{ $claim->user?->name }}</strong></div>
                <div>{{ $claim->user?->email }}</div>
                <div class="muted">{{ $claim->user?->team }} · {{ $claim->user?->employmentLabel() }}</div>
                <div class="muted">{{ $claim->user?->company }}</div>
            </div>
            <div>
                <div class="label">{{ $claim->isDelivery() ? 'Dikirim ke' : 'Ambil di gudang' }}</div>
                <div>{{ $claim->isDelivery() ? $claim->shipping_address : config('claimly.warehouse_address') }}</div>
                @if ($claim->tracking_number)
                    <div class="muted">No. resi {{ $claim->tracking_number }}</div>
                @endif
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="num">Qty</th>
                    <th class="num">Harga RSP</th>
                    <th class="num">Harga karyawan</th>
                    <th class="num">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($claim->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">{{ Rupiah::format($item->unit_retail_price) }}</td>
                        <td class="num">{{ Rupiah::format($item->unit_claim_price) }}</td>
                        <td class="num">{{ Rupiah::format($item->unit_claim_price * $item->quantity) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span>Total harga RSP</span><span>{{ Rupiah::format($claim->retail_total) }}</span></div>
            <div><span>Diskon karyawan {{ $claim->discount_percent }}%</span><span>− {{ Rupiah::format($claim->saving_total) }}</span></div>
            <div><span>Subtotal</span><span>{{ Rupiah::format($claim->claim_total) }}</span></div>
            <div><span>Ongkir</span><span>{{ $claim->isDelivery() ? Rupiah::format($claim->shipping_fee) : 'Gratis (pickup)' }}</span></div>
            <div class="grand"><span>Total tagihan</span><span>{{ Rupiah::format($claim->total_due) }}</span></div>
            @if ($claim->paid_total > 0)
                <div><span>Sudah dibayar</span><span>{{ Rupiah::format($claim->paid_total) }}</span></div>
                <div><span>Sisa tagihan</span><span>{{ Rupiah::format($claim->remainingDue()) }}</span></div>
            @endif
            @if ($claim->refund_total > 0)
                <div><span>Refund lebih bayar</span><span>{{ Rupiah::format($claim->refund_total) }}{{ $claim->refunded_at ? ' (selesai)' : '' }}</span></div>
            @endif
        </div>

        <section class="bank">
            <div class="label">Transfer ke</div>
            @if ($bankReady)
                <div><strong>{{ $brand->bank_name }} · {{ $brand->bank_account_number }}</strong></div>
                <div>a.n. {{ $brand->bank_account_name ?: $brand->company_name }}</div>
            @else
                <div class="warn">Rekening {{ $brand?->company_name }} belum diisi admin. Hubungi Finance sebelum transfer.</div>
            @endif
            <div class="muted">Cantumkan nomor invoice {{ $claim->invoice_number }} di berita transfer, lalu upload bukti di menu Progres.</div>
        </section>

        <footer>
            Pembelian karyawan maksimal {{ config('claimly.sku_yearly_limit') }} pcs per SKU per tahun. Invoice ini dibuat otomatis oleh Product Claim.
        </footer>
    </main>
</body>
</html>
