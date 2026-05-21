<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print QR Troli</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Arial, Helvetica, sans-serif;
        }

        .toolbar {
            padding: 16px;
            background: #ffffff;
            border-bottom: 1px solid #d1d5db;
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 8px;
            justify-content: space-between;
            align-items: center;
        }

        .toolbar-title {
            font-weight: 700;
            font-size: 14px;
        }

        .btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            color: #111827;
        }

        .btn-primary {
            background: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
        }

        .sheet {
            width: 80mm;
            margin: 16px auto;
            background: #ffffff;
            padding: 4mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
        }

        .label {
            width: 72mm;
            min-height: 90mm;
            border: 2px solid #111;
            padding: 4mm;
            text-align: center;
        }

        .label-title {
            font-size: 15px;
            font-weight: 900;
            border-bottom: 2px solid #111;
            padding-bottom: 2mm;
            margin-bottom: 4mm;
        }

        .qr-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 3mm;
        }

        #qrcode {
            width: 45mm;
            height: 45mm;
        }

        .trolley-code {
            font-size: 16px;
            font-weight: 900;
            margin-top: 2mm;
        }

        .barcode {
            font-size: 12px;
            font-weight: 700;
            margin-top: 1mm;
            letter-spacing: 1px;
        }

        .info {
            margin-top: 4mm;
            text-align: left;
            font-size: 11px;
            line-height: 1.6;
            border-top: 1px solid #111;
            padding-top: 3mm;
        }

        .status {
            display: inline-block;
            margin-top: 4mm;
            padding: 2mm 4mm;
            border: 1px solid #111;
            font-weight: 900;
            font-size: 12px;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: 80mm;
                margin: 0;
                padding: 4mm;
                box-shadow: none;
            }

            .label {
                width: 72mm;
                min-height: 90mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Print QR Troli</div>
            <div style="font-size: 12px; color: #6b7280;">
                {{ $trolley->trolley_code }}
            </div>
        </div>

        <div>
            <a href="{{ route('temporary-warehouse.index') }}" class="btn">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>
    </div>

    <div class="sheet">
        <div class="label">
            <div class="label-title">
                QR TROLI / HANDLING UNIT
            </div>

            <div class="qr-wrap">
                <div id="qrcode"></div>
            </div>

            <div class="trolley-code">
                {{ $trolley->trolley_code }}
            </div>

            <div class="barcode">
                {{ $trolley->barcode }}
            </div>

            <div class="info">
                <div><strong>Status:</strong> {{ $trolley->status }}</div>
                <div><strong>Isi:</strong> {{ $trolley->total_items }} / {{ $trolley->capacity }} Dus</div>
                <div><strong>Dibuat:</strong> {{ \Carbon\Carbon::parse($trolley->created_at)->format('d/m/Y H:i') }}</div>
            </div>

            <div class="status">
                {{ $trolley->status }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "{{ $trolley->barcode }}",
            width: 170,
            height: 170,
            correctLevel: QRCode.CorrectLevel.H
        });

        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
