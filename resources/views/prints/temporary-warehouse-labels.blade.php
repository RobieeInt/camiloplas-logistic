<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Barcode Labels</title>

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

        .toolbar-actions {
            display: flex;
            gap: 8px;
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
            width: 100mm;
            margin: 16px auto;
            background: #ffffff;
            padding: 2mm;
            box-shadow: 0 8px 30px rgba(0,0,0,.15);
        }

        .label {
            width: 96mm;
            height: 30mm;
            border: 1px solid #111;
            margin-bottom: 2mm;
            padding: 2mm 3mm;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .label-header {
            font-size: 10px;
            font-weight: 800;
            border-bottom: 1px solid #111;
            padding-bottom: 1mm;
            margin-bottom: 1.5mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-body {
            display: grid;
            grid-template-columns: 48% 52%;
            gap: 2mm;
            height: 19mm;
        }

        .label-info {
            font-size: 9px;
            line-height: 1.35;
            font-weight: 600;
            padding-left: 2mm;
        }

        .qty {
            font-weight: 800;
            margin-top: 1mm;
        }

        .barcode-wrap {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

.barcode-wrap svg {
    width: 100%;
    height: 11mm !important;
    max-height: 11mm !important;
}

.barcode-number {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-top: 1.5mm;
    line-height: 1;
}

        @media print {
            @page {
                size: 100mm auto;
                margin: 0;
            }

            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: 100mm;
                margin: 0;
                padding: 2mm;
                box-shadow: none;
            }

            .label {
                width: 96mm;
                height: 28mm;
                margin-bottom: 2mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <div class="toolbar-title">Print Barcode Labels</div>
            <div style="font-size: 12px; color: #6b7280;">
                Batch: {{ $batchId }} | Total: {{ count($labels) }} label
            </div>
        </div>

        <div class="toolbar-actions">
            <a href="{{ route('temporary-warehouse.index') }}" class="btn">Kembali</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>
    </div>

    <div class="sheet">
        @foreach ($labels as $label)
            <div class="label">
                <div class="label-header">
                    {{ $label->item_name }} ({{ $label->item_code }})
                </div>

                <div class="label-body">
                    <div class="label-info">
                        <div>{{ \Carbon\Carbon::parse($label->production_date)->format('d/m/Y') }}</div>
                        <div>{{ $label->spk_number }}</div>
                        <div>{{ $label->box_number }}</div>
                        <div class="qty">{{ number_format($label->qty, 0, ',', '.') }}{{ $label->uom }}</div>
                    </div>

                    <div class="barcode-wrap">
                        <svg
    class="barcode"
    jsbarcode-format="CODE128"
    jsbarcode-value="{{ $label->barcode }}"
    jsbarcode-textmargin="0"
    jsbarcode-fontoptions="bold"
    jsbarcode-height="32"
    jsbarcode-width="1.3"
    jsbarcode-margin="0"
    jsbarcode-displayvalue="false"
></svg>

                        <div class="barcode-number">
                            {{ $label->barcode }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode(".barcode").init();

        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
