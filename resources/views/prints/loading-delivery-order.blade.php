<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delivery Order {{ $order->do_number }}</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            background: #e5e7eb;
            color: #111;
        }

        .toolbar {
            padding: 14px;
            background: white;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
        }

        .btn {
            padding: 8px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #111;
        }

        .btn-primary {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .page {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            background: white;
            padding: 16mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
            overflow: hidden;
        }

        .content {
            position: relative;
            z-index: 2;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 150%;
            text-align: center;
            transform: translate(-50%, -50%) rotate(-38deg);
            font-size: 42mm;
            font-weight: 900;
            letter-spacing: 4mm;
            color: rgba(220, 38, 38, 0.13);
            z-index: 1;
            pointer-events: none;
            white-space: nowrap;
            line-height: 1;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            font-size: 22px;
        }

        .duplicate-label {
            color: #dc2626;
            font-weight: 900;
            font-size: 13px;
            margin-top: 4px;
        }

        .meta {
            font-size: 13px;
            line-height: 1.6;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 16px;
            background: rgba(255,255,255,.78);
        }

        th, td {
            border: 1px solid #111;
            padding: 8px;
        }

        th {
            background: #f3f4f6;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 60px;
            text-align: center;
            font-size: 13px;
        }

        .signature-box {
            height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
            }

            .toolbar {
                display: none;
            }

            .page {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                min-height: 297mm;
                padding: 16mm;
            }

            .watermark {
                position: absolute;
                font-size: 42mm;
                color: rgba(220, 38, 38, 0.14);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table {
                background: rgba(255,255,255,.78);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div><strong>Delivery Order</strong> {{ $order->do_number }}</div>
        <div>
            <a href="{{ route('loading.index') }}" class="btn">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>
    </div>

    <div class="page">
        @if ($isDuplicate ?? false)
            <div class="watermark">DUPLICATE</div>
        @endif

        <div class="content">
            <div class="header">
                <div>
                    <h1>DELIVERY ORDER</h1>
                    <div class="meta"> Logistic</div>

                    @if ($isDuplicate ?? false)
                        <div class="duplicate-label">REPRINT DOCUMENT / DUPLICATE</div>
                    @endif
                </div>

                <div class="meta">
                    <div><strong>DO:</strong> {{ $order->do_number }}</div>
                    <div><strong>PO Customer:</strong> {{ $order->customer_po_number ?? '-' }}</div>
                    <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($order->loaded_at)->format('d/m/Y H:i') }}</div>
                    {{-- <div><strong>Print Count:</strong> {{ ($order->do_print_count ?? 0) + 1 }}</div> --}}
                </div>
            </div>

            <div class="meta">
                <div><strong>Customer:</strong> {{ $order->customer_name }}</div>
                <div><strong>Alamat:</strong> {{ $order->customer_address ?? '-' }}</div>
                <div><strong>No Truck:</strong> {{ $order->truck_number ?? '-' }}</div>
                <div><strong>Driver:</strong> {{ $order->driver_name ?? '-' }}</div>
                <div><strong>Loaded By:</strong> {{ $order->loaded_by_name ?? '-' }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="40">No</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th width="90">Qty Box</th>
                        <th width="90">UOM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->item_code }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->loaded_boxes }}</td>
                            <td>{{ $item->uom }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="signatures">
                <div class="signature-box">
                    <div>Dibuat Oleh</div>
                    <div>(________________)</div>
                </div>

                <div class="signature-box">
                    <div>Warehouse</div>
                    <div>(________________)</div>
                </div>

                <div class="signature-box">
                    <div>Driver</div>
                    <div>(________________)</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>
