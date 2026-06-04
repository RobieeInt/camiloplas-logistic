<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan {{ $order->do_number }}</title>

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
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            background: white;
            padding: 16mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            font-size: 22px;
        }

        .meta {
            font-size: 13px;
            line-height: 1.6;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 16px;
        }

        th, td {
            border: 1px solid #111;
            padding: 7px;
        }

        th {
            background: #f3f4f6;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 60px;
            text-align: center;
            font-size: 12px;
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
                width: auto;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div><strong>Surat Jalan</strong> {{ $order->do_number }}</div>
        <div>
            <a href="{{ route('loading.index') }}" class="btn">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <h1>SURAT JALAN</h1>
            <div class="meta"> Logistic</div>
        </div>

        <div class="grid meta">
            <div>
                <div><strong>No Surat Jalan:</strong> SJ-{{ $order->do_number }}</div>
                <div><strong>No DO:</strong> {{ $order->do_number }}</div>
                <div><strong>PO Customer:</strong> {{ $order->customer_po_number ?? '-' }}</div>
                <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($order->loaded_at)->format('d/m/Y H:i') }}</div>
            </div>

            <div>
                <div><strong>Customer:</strong> {{ $order->customer_name }}</div>
                <div><strong>Alamat:</strong> {{ $order->customer_address ?? '-' }}</div>
                <div><strong>No Truck:</strong> {{ $order->truck_number ?? '-' }}</div>
                {{-- <div><strong>Driver:</strong> {{ $order->driver_name ?? '-' }}</div> --}}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th width="90">Qty Box</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->item_code }}</td>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->loaded_boxes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div class="signature-box">
                <div>Dibuat</div>
                <div>({{ $order->loaded_by_name ?? '-' }})</div>
            </div>

            <div class="signature-box">
                <div>Warehouse</div>
                <div>({{ $order->loaded_by_name ?? '-' }})</div>
            </div>

            <div class="signature-box">
                <div>Security</div>
                <div>({{ $order->security_name ?? '________' }})</div>
            </div>

            <div class="signature-box">
                <div>Driver</div>
                <div>({{ $order->driver_name ?? '-' }})</div>
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
