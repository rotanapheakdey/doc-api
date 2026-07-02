<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Sign-off &amp; Verification</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background-color: #ffffff;
            color: #1e293b;
        }
        .container {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            border: 1px solid #e2e8f0;
            border-top: 6px solid #1e3a8a;
            border-radius: 12px;
            padding: 35px;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo-container {
            width: 64px;
            height: 64px;
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
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details-table td {
            padding: 10px 12px;
            font-size: 12px;
            vertical-align: middle;
            border: 1px solid #f1f5f9;
        }
        .label-cell {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            width: 25%;
        }
        .value-cell {
            color: #0f172a;
            font-weight: 500;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .signature-column {
            width: 50%;
            padding: 0 10px;
            vertical-align: top;
        }
        .signature-card {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            height: 200px;
        }
        .signature-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: block;
        }
        .signature-wrapper {
            height: 80px;
            line-height: 80px;
            margin-bottom: 10px;
        }
        .signature-img {
            max-height: 70px;
            max-width: 180px;
            vertical-align: middle;
        }
        .no-signature {
            font-style: italic;
            color: #94a3b8;
            font-size: 12px;
            height: 70px;
            line-height: 70px;
        }
        .signature-line {
            width: 140px;
            border-top: 1px solid #cbd5e1;
            margin: 0 auto 6px auto;
        }
        .signer-title {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        }
        .signer-role {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .timestamp-text {
            font-size: 8px;
            color: #10b981;
            font-weight: bold;
            margin-top: 4px;
        }
        .status-badge {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            margin-top: 20px;
        }
        .status-text {
            color: #15803d;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-note {
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            margin-top: 30px;
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
            <div class="subtitle">Official Action Report Sign-Off</div>
            <h1 class="title">SIGNATURE VERIFICATION ANNEX</h1>
        </div>

        <div class="divider"></div>

        <div class="section-title">Document Reference</div>
        <table class="details-table">
            <tr>
                <td class="label-cell">Control Number</td>
                <td class="value-cell" style="font-family: monospace; font-weight: 600;">{{ $document->control_no }}</td>
            </tr>
            <tr>
                <td class="label-cell">Document Title</td>
                <td class="value-cell">{{ $document->title }}</td>
            </tr>
            <tr>
                <td class="label-cell">Origin Department</td>
                <td class="value-cell">{{ $document->department?->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="section-title">Executive Sign-offs</div>
        <table class="signatures-table">
            <tr>
                <!-- VDG SIGNATURE -->
                <td class="signature-column">
                    <div class="signature-card">
                        <span class="signature-label">Verified &amp; Cleared By</span>
                        <div class="signature-wrapper">
                            @if($vdg_signature_path)
                                <img class="signature-img" src="{{ $vdg_signature_path }}" alt="VDG Signature">
                            @else
                                <div class="no-signature">Awaiting Review</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <h3 class="signer-title">{{ $vdg_name ?? 'VICE DIRECTOR GENERAL' }}</h3>
                        <div class="signer-role">Vice Director General</div>
                        @if($vdg_signed_at)
                            <div class="timestamp-text">✓ Signed: {{ $vdg_signed_at }}</div>
                        @endif
                    </div>
                </td>

                <!-- DG SIGNATURE -->
                <td class="signature-column">
                    <div class="signature-card">
                        <span class="signature-label">Executive Approval By</span>
                        <div class="signature-wrapper">
                            @if($dg_signature_path)
                                <img class="signature-img" src="{{ $dg_signature_path }}" alt="DG Signature">
                            @else
                                <div class="no-signature" style="color: #d97706;">Awaiting Sign-off</div>
                            @endif
                        </div>
                        <div class="signature-line"></div>
                        <h3 class="signer-title">{{ $dg_name ?? 'DIRECTOR GENERAL' }}</h3>
                        <div class="signer-role">Director General</div>
                        @if($dg_signed_at)
                            <div class="timestamp-text" style="color: #16a34a;">✓ Approved: {{ $dg_signed_at }}</div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        @if($dg_signed_at)
            <div class="status-badge">
                <span class="status-text">Document Execution Fully Approved &amp; Closed</span>
            </div>
        @else
            <div class="status-badge" style="background-color: #fffbeb; border: 1px solid #fef3c7;">
                <span class="status-text" style="color: #b45309;">Review Process In Progress</span>
            </div>
        @endif

        <div class="footer-note">
            This verification annex was dynamically appended to the report document. All signatures are digitally logged, verified, and secured under the CADT Document Management System.
        </div>
    </div>
</body>
</html>
