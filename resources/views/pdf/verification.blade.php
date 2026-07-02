<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Verification</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 30px 0;
            background-color: #f8fafc;
            color: #1e293b;
        }
        .container {
            width: 90%;
            max-width: 480px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-top: 6px solid #1e3a8a;
            border-radius: 12px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo-container {
            width: 60px;
            height: 60px;
            margin: 0 auto 12px auto;
            text-align: center;
        }
        .logo-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-fallback {
            width: 46px;
            height: 46px;
            background-color: #eff6ff;
            border: 2px solid #2563eb;
            border-radius: 50%;
            margin: 0 auto 12px auto;
            text-align: center;
        }
        .logo-fallback span {
            color: #2563eb;
            font-size: 15px;
            font-weight: 800;
            line-height: 42px;
        }
        .subtitle {
            font-size: 11px;
            font-weight: 700;
            color: #b45309;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }
        .title {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-table td {
            padding: 10px 12px;
            font-size: 13px;
            vertical-align: middle;
            border: 1px solid #f1f5f9;
        }
        .label-cell {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            width: 32%;
        }
        .value-cell {
            color: #0f172a;
            font-weight: 500;
        }
        .signature-card {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .signature-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: block;
        }
        .signature-wrapper {
            height: 90px;
            line-height: 90px;
            margin-bottom: 12px;
        }
        .signature-img {
            max-height: 80px;
            max-width: 220px;
            vertical-align: middle;
        }
        .no-signature {
            font-style: italic;
            color: #94a3b8;
            font-size: 13px;
            height: 80px;
            line-height: 80px;
        }
        .signature-line {
            width: 180px;
            border-top: 1px solid #cbd5e1;
            margin: 0 auto 6px auto;
        }
        .signer-title {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .signer-role {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .status-badge {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .status-text {
            color: #047857;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-note {
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            margin-top: 25px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(file_exists(public_path('images/logo.png')))
                <div class="logo-container">
                    <img class="logo-img" src="{{ public_path('images/logo.png') }}" alt="CADT Logo">
                </div>
            @else
                <div class="logo-fallback">
                    <span>DG</span>
                </div>
            @endif
            <div class="subtitle">Official Verification Slip</div>
            <h1 class="title">DOCUMENT VERIFICATION</h1>
        </div>

        <div class="divider"></div>

        <table class="details-table">
            <tr>
                <td class="label-cell">Date &amp; Time</td>
                <td class="value-cell">{{ $date }}</td>
            </tr>
            <tr>
                <td class="label-cell">Assigned Dept</td>
                <td class="value-cell">{{ $department }}</td>
            </tr>
        </table>

        <div class="signature-card">
            <span class="signature-label">Authorized Signature</span>
            <div class="signature-wrapper">
                @if($signature_path)
                    <img class="signature-img" src="{{ $signature_path }}" alt="Director General Signature">
                @else
                    <div class="no-signature">No signature registered</div>
                @endif
            </div>
            <div class="signature-line"></div>
            <h3 class="signer-title">DIRECTOR GENERAL</h3>
            <div class="signer-role">Digital Signatory</div>
        </div>

        <div class="status-badge">
            <span class="status-text">Digitally Verified &amp; Submitted</span>
        </div>

        <div class="footer-note">
            This verification slip was automatically generated and signed by the Director General via the CADT Document Management System.
        </div>
    </div>
</body>
</html>
