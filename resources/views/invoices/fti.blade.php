@php
    $brand = $claim->brand;
    $bankReady = filled($brand?->bank_name) && filled($brand?->bank_account_number);
    $money = fn (int $amount) => number_format($amount, 0, '.', ',');
    $website = $brand?->website;
    $websiteUrl = $website && ! str_starts_with($website, 'http') ? 'https://'.$website : $website;
    $billAddress = $claim->isDelivery()
        ? $claim->shipping_address
        : 'Ambil di gudang · '.config('claimly.warehouse_address');
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lora:wght@600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0ec; color: #1d1d1d; font: 13px/1.45 Poppins, sans-serif; }
        .toolbar { max-width: 860px; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button, .toolbar a {
            border: 1px solid #d5dbd6; background: #fff; color: #1f2a24; border-radius: 999px; padding: 8px 18px;
            font: 600 13px Poppins, sans-serif; cursor: pointer; text-decoration: none;
        }
        .toolbar button { background: #174f3b; border-color: #174f3b; color: #fff; }
        .sheet { width: 860px; margin: 16px auto 40px; background: #fff; border-top: 6px solid #1f4e8c; padding: 28px 30px 40px; box-shadow: 0 10px 30px rgba(20, 40, 30, .1); }

        .head { display: grid; grid-template-columns: 96px 1fr auto; gap: 14px; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #d9d9d9; }
        .logo { text-align: center; }
        .logo img { width: 44px; height: auto; display: block; margin: 0 auto -4px; }
        .logo span { font: 600 8px Lora, serif; letter-spacing: .08em; }
        .brand h1 { margin: 0 0 4px; font-size: 24px; font-weight: 700; letter-spacing: .01em; }
        .brand div { font-size: 12px; }
        .brand a { color: #1155cc; }
        .tag { display: inline-block; margin-top: 6px; padding: 1px 8px; border-radius: 3px; background: #1e7b34; color: #fff; font-size: 10px; font-weight: 600; }
        .title { text-align: right; }
        .title h2 { margin: 0; font-size: 40px; font-weight: 700; letter-spacing: .01em; }
        .title div { font-size: 11px; color: #555; }

        .bill { margin-top: 28px; }
        .bill strong { display: block; font-size: 14px; }
        .bill div { margin-top: 4px; }
        .bill .address { max-width: 560px; }

        table.items { width: 100%; margin-top: 26px; border-collapse: separate; border-spacing: 0 2px; }
        .items th { background: #e0e0e0; font-size: 13px; font-weight: 600; padding: 4px 8px; text-align: center; }
        .items td { background: #f3f3f3; padding: 6px 8px; text-align: right; vertical-align: bottom; font-size: 12px; }
        .items td.product { text-align: left; background: #ececec; width: 290px; }
        .items td.center { text-align: center; }

        .summary { width: 446px; margin-left: auto; margin-top: 4px; }
        .summary div { display: flex; justify-content: space-between; padding: 3px 8px; font-size: 12px; }
        .summary div:nth-child(2) { background: #f3f3f3; }
        .summary strong { font-weight: 700; }
        .summary .grand { margin-top: 2px; background: #d9d9d9; font-size: 18px; font-weight: 700; padding: 4px 8px; }

        .terms { width: 300px; margin-top: 30px; font-size: 12px; }
        .terms .label { background: #e0e0e0; padding: 4px 6px; font-weight: 600; font-size: 14px; }
        .terms div { padding: 4px 6px; }
        .terms .shade { background: #f3f3f3; }
        .terms .warn { color: #a5521b; font-weight: 600; }

        .status { margin-top: 26px; padding-top: 10px; border-top: 1px dashed #c9cfca; color: #555; font-size: 11px; }

        @media print {
            @page { size: A4; margin: 10mm; }
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; margin: 0; box-shadow: none; }
            .sheet, .tag, .items th, .items td, .summary div, .terms .label, .terms .shade { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 880px) {
            .sheet { width: auto; margin: 12px; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="/progres">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
        <div class="head">
            <div class="logo">
                <img src="/logo-brand/FTI.png" alt="">
                <span>{{ strtoupper($brand?->name ?? '') }}</span>
            </div>
            <div class="brand">
                <h1>{{ strtoupper($brand?->name ?? '') }}</h1>
                @if ($brand?->contact_phone)
                    <div>{{ $brand->contact_phone }}</div>
                @endif
                @if ($brand?->contact_email)
                    <div>{{ $brand->contact_email }}</div>
                @endif
                @if ($website)
                    <div><a href="{{ $websiteUrl }}" target="_blank" rel="noopener">{{ $website }}</a></div>
                @endif
                <span class="tag">{{ $brand?->name }}</span>
            </div>
            <div class="title">
                <h2>INVOICE</h2>
                <div>{{ $claim->invoice_number }}</div>
                <div>{{ $issuedLabel }}</div>
            </div>
        </div>

        <section class="bill">
            <strong>Bill To</strong>
            <div>{{ $claim->user?->name }}</div>
            @if ($claim->user?->phone)
                <div>{{ $claim->user->phone }}</div>
            @endif
            <div class="address">{{ $billAddress }}</div>
        </section>

        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Unit price</th>
                    <th>Qty</th>
                    <th>Total price</th>
                    <th>Discount</th>
                    <th>Total Discount</th>
                    <th>After Discount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($claim->items as $item)
                    @php
                        $gross = $item->unit_retail_price * $item->quantity;
                        $net = $item->unit_claim_price * $item->quantity;
                    @endphp
                    <tr>
                        <td class="product">{{ $item->product_name }}</td>
                        <td>{{ $money($item->unit_retail_price) }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td>{{ $money($gross) }}</td>
                        <td class="center">{{ $claim->discount_percent }}%</td>
                        <td>{{ $money($gross - $net) }}</td>
                        <td>{{ $money($net) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div><span>Subtotal</span><strong>Rp{{ $money($claim->claim_total) }}</strong></div>
            <div>
                <span>{{ $claim->isDelivery() ? 'Biaya Pengiriman' : 'Biaya Pengiriman (pickup)' }}</span>
                <strong>Rp{{ $money($claim->shipping_fee) }}</strong>
            </div>
            <div class="grand"><span>GRAND TOTAL</span><span>Rp{{ $money($claim->total_due) }}</span></div>
        </div>

        <section class="terms">
            <div class="label">Terms &amp; Conditions</div>
            <div>Silahkan transfer ke rekening :</div>
            @if ($bankReady)
                <div class="shade">{{ $brand->bank_account_number }}</div>
                <div>{{ $brand->bank_name }}&nbsp; a/n {{ $brand->bank_account_name ?: $brand->company_name }}</div>
            @else
                <div class="shade warn">Rekening belum diisi admin. Hubungi Finance sebelum transfer.</div>
            @endif
        </section>

        <div class="status">
            Status: {{ $claim->statusLabel() }}
            @if ($claim->paid_total > 0)
                · Sudah dibayar Rp{{ $money($claim->paid_total) }}
                @if ($claim->remainingDue() > 0)
                    · Sisa Rp{{ $money($claim->remainingDue()) }}
                @endif
            @endif
            @if ($claim->refund_total > 0)
                · Lebih bayar Rp{{ $money($claim->refund_total) }}{{ $claim->refunded_at ? ' (sudah dikembalikan)' : '' }}
            @endif
            · Cantumkan {{ $claim->invoice_number }} di berita transfer.
        </div>
    </main>
</body>
</html>
