@php
if (!function_exists('toKhmerNumber')) {
    function toKhmerNumber($number) {
        $khmerDigits = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];
        return str_replace(range(0, 9), $khmerDigits, (string)$number);
    }
}

if (!function_exists('formatKhmerDate')) {
    function formatKhmerDate($date, $withTime = false) {
        if (!$date) return '';
        if (is_numeric($date)) {
            $timestamp = (int)$date;
        } elseif ($date instanceof \DateTimeInterface) {
            $timestamp = $date->getTimestamp();
        } else {
            $timestamp = strtotime($date);
        }
        if (!$timestamp) return (string)$date;
        
        $months = [
            1 => 'មករា', 2 => 'កុម្ភៈ', 3 => 'មីនា', 4 => 'មេសា',
            5 => 'ឧសភា', 6 => 'មិថុនា', 7 => 'កក្កដា', 8 => 'សីហា',
            9 => 'កញ្ញា', 10 => 'តុលា', 11 => 'វិច្ឆិកា', 12 => 'ធ្នូ'
        ];
        
        $day = date('d', $timestamp);
        $month = (int)date('m', $timestamp);
        $year = date('Y', $timestamp);
        
        $khmerDay = toKhmerNumber($day);
        $khmerMonth = $months[$month] ?? '';
        $khmerYear = toKhmerNumber($year);
        
        $result = "ថ្ងៃទី{$khmerDay} ខែ{$khmerMonth} ឆ្នាំ{$khmerYear}";
        if ($withTime) {
            $hour = toKhmerNumber(date('H', $timestamp));
            $min = toKhmerNumber(date('i', $timestamp));
            $result .= " វេលាម៉ោង {$hour}:{$min} នាទី";
        }
        return $result;
    }
}

$resolvedDgSig = $dg_signature_path ?? $signature_path ?? null;
if (!$resolvedDgSig && !empty($document->dg_signature_path)) {
    $resolvedDgSig = public_path($document->dg_signature_path);
}
$resolvedDgName = $dg_name ?? $document->dg?->name ?? 'ផុស សុវណ្ណ';
$resolvedDgDate = $dg_signed_at ?? $date ?? $document->dg_signed_at ?? now();

$resolvedVdgSig = $vdg_signature_path ?? null;
if (!$resolvedVdgSig && !empty($document->vdg_signature_path)) {
    $resolvedVdgSig = public_path($document->vdg_signature_path);
}
$resolvedVdgName = $vdg_name ?? $document->vdg?->name ?? 'VDG ( FIN )';
$resolvedVdgDate = $vdg_signed_at ?? $document->vdg_signed_at ?? null;
@endphp
<!DOCTYPE html>
<html lang="km">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>លិខិតបញ្ជាក់ការចាត់ចែងឯកសាររដ្ឋបាល</title>
    <style>
        body {
            font-family: 'KhmerOSsiemreap', 'siemreap', sans-serif;
            font-size: 10.5pt;
            line-height: 1.55;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .moul {
            font-family: 'KhmerOSMoulLight', 'moul', serif;
            font-weight: normal !important;
        }

        /* ─── TOP ROYAL LETTERHEAD ─── */
        .letterhead-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .letterhead-left {
            width: 53%;
            vertical-align: top;
            text-align: left;
        }
        .letterhead-right {
            width: 47%;
            vertical-align: top;
            text-align: center;
        }
        .logo-img {
            width: 52px;
            height: 52px;
        }

        /* ─── DOCUMENT TITLE ─── */
        .document-title-container {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .document-main-title {
            font-family: 'KhmerOSMoulLight', 'moul', serif;
            font-weight: normal !important;
            font-size: 14pt;
            color: #0f172a;
            margin: 0 0 6px 0;
            line-height: 1.7;
        }
        .title-divider {
            width: 85px;
            height: 2px;
            background-color: #1e3a8a;
            margin: 0 auto;
        }

        /* ─── METADATA / INFORMATION TABLE (ORIGINAL CLEAN STYLE) ─── */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .admin-table th {
            font-family: 'KhmerOSMoulLight', 'moul', serif;
            font-weight: normal !important;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 10pt;
            text-align: left;
            padding: 8px 12px;
            line-height: 1.6;
        }
        .admin-table td {
            padding: 8px 12px;
            font-size: 9.5pt;
            line-height: 1.6;
            vertical-align: middle;
        }
        .table-label {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
            width: 34%;
        }
        .table-value {
            color: #0f172a;
            width: 66%;
        }
        .dg-instruction {
            color: #1e3a8a;
            font-weight: bold;
            line-height: 1.6;
        }

        /* ─── STATUS BADGES ─── */
        .status-badge-approved {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
        }

        /* ─── SIGNATURE BLOCKS ─── */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 22px;
        }
        .sig-col {
            vertical-align: top;
            text-align: center;
            padding: 0 10px;
        }
        .signature-box {
            height: 55px;
            text-align: center;
            vertical-align: middle;
        }
        .signature-img {
            max-height: 55px;
            max-width: 180px;
        }
        .sig-placeholder {
            color: #94a3b8;
            font-size: 8.5pt;
            padding-top: 16px;
        }
        .sig-status-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #065f46;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
        }

        /* ─── OFFICIAL FOOTER ─── */
        .footer-box {
            border-top: 1px dashed #cbd5e1;
            padding-top: 10px;
            margin-top: 16px;
            text-align: center;
            font-size: 8.5pt;
            color: #64748b;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <!-- ─── 1. ROYAL CAMBODIAN KINGDOM HEADER (TOP CENTER) ─── -->
    <table style="width: 100%; border-collapse: collapse; text-align: center; margin-bottom: 10px;">
        <tr>
            <td align="center" style="font-family: 'KhmerOSMoulLight', 'moul', serif; font-weight: normal; font-size: 14pt; color: #0f172a; padding-bottom: 8px; line-height: 1.6;">ព្រះរាជាណាចក្រកម្ពុជា</td>
        </tr>
        <tr>
            <td align="center" style="font-family: 'KhmerOSMoulLight', 'moul', serif; font-weight: normal; font-size: 14pt; color: #0f172a; padding-bottom: 10px; line-height: 1.6;">ជាតិ សាសនា ព្រះមហាក្សត្រ</td>
        </tr>
        <tr>
            <td align="center" style="padding-top: 2px; padding-bottom: 15px;">
                <table align="center" style="margin: 0 auto; width: 75px; border-collapse: collapse;">
                    <tr><td style="border-bottom: 1.5px solid #0f172a; height: 1px; font-size: 1pt; padding: 0;">&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- ─── 2. MINISTRY & DEPARTMENT IDENTITY (LEFT ALIGNED) ─── -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <table style="border-collapse: collapse;">
                    <tr>
                        @if(file_exists(public_path('images/logo_circular.png')))
                            <td style="vertical-align: top; padding-right: 12px;">
                                <img src="{{ public_path('images/logo_circular.png') }}" class="logo-img" alt="INB Logo">
                            </td>
                        @elseif(file_exists(public_path('images/logo.png')))
                            <td style="vertical-align: top; padding-right: 12px;">
                                <img src="{{ public_path('images/logo.png') }}" class="logo-img" alt="INB Logo">
                            </td>
                        @endif
                        <td style="vertical-align: top;">
                            <table style="border-collapse: collapse; width: 100%;">
                                <tr>
                                    <td style="font-family: 'KhmerOSMoulLight', 'moul', serif; font-weight: normal; font-size: 11pt; color: #0f172a; padding-bottom: 7px; line-height: 1.6;">ក្រសួងព័ត៌មាន</td>
                                </tr>
                                <tr>
                                    <td style="font-family: 'KhmerOSMoulLight', 'moul', serif; font-weight: normal; font-size: 10pt; color: #1e3a8a; padding-bottom: 8px; line-height: 1.6;">អគ្គនាយកដ្ឋានព័ត៌មាន និងសោតទស្សន៍</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 9.5pt; color: #475569; line-height: 1.6;">លេខកូដសម្គាល់៖ <strong>{{ $document->control_no ?? '...' }}</strong> អ.ព.ស</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; vertical-align: top;">&nbsp;</td>
        </tr>
    </table>

    <!-- ─── DOCUMENT TITLE ─── -->
    <div class="document-title-container">
        <h1 class="document-main-title">លិខិតបញ្ជាក់ការចាត់ចែងឯកសាររដ្ឋបាល</h1>
        <div class="title-divider"></div>
    </div>

    <!-- ─── METADATA / INFORMATION TABLE ─── -->
    <table class="admin-table">
        <tr>
            <th colspan="2" class="table-header">ព័ត៌មានលម្អិតនៃការចាត់ចែង និងបញ្ជូនឯកសារ</th>
        </tr>
        <tr>
            <td class="table-label">លេខកូដសម្គាល់ឯកសារ</td>
            <td class="table-value"><strong>{{ $document->control_no ?? '...' }}</strong></td>
        </tr>
        <tr>
            <td class="table-label">ចំណងជើងឯកសារ</td>
            <td class="table-value"><strong>{{ $document->title ?? '...' }}</strong></td>
        </tr>
        <tr>
            <td class="table-label">នាយកដ្ឋានសាមី / អនុវត្ត</td>
            <td class="table-value">{{ $document->department?->name ?? 'នាយកដ្ឋានជំនាញ' }}</td>
        </tr>
        <tr>
            <td class="table-label">កាលបរិច្ឆេទចុះបញ្ជី</td>
            <td class="table-value">{{ formatKhmerDate($document->created_at ?? now(), true) }}</td>
        </tr>
        <tr>
            <td class="table-label">ស្ថានភាពឯកសារ</td>
            <td class="table-value">
                <span class="status-badge-approved">ឯកសារត្រូវបានពិនិត្យ និងចុះហត្ថលេខាសម្រេចរួចរាល់</span>
            </td>
        </tr>
        @if(!empty($dg_instruction) || !empty($document->dg_instruction))
        <tr>
            <td class="table-label">ចំណារដឹកនាំរបស់អគ្គនាយក</td>
            <td class="table-value dg-instruction">
                « {{ $dg_instruction ?? $document->dg_instruction }} »
            </td>
        </tr>
        @endif
        @if(!empty($vdg_comment) || !empty($document->vdg_comment))
        <tr>
            <td class="table-label">មតិយោបល់អគ្គនាយករង</td>
            <td class="table-value">
                « {{ $vdg_comment ?? $document->vdg_comment }} »
            </td>
        </tr>
        @endif
    </table>

    <!-- ─── SIGNATURE BLOCK (DG ONLY - RIGHT ALIGNED) ─── -->
    <table class="signatures-table" style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Left 50% blank to align DG signature block cleanly on the right -->
            <td style="width: 50%; vertical-align: top;">&nbsp;</td>

            <!-- RIGHT COLUMN: DG -->
            <td class="sig-col" style="width: 50%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; text-align: center;">
                    <tr>
                        <td align="center" style="font-size: 9.5pt; color: #334155; padding-bottom: 8px; line-height: 1.6;">រាជធានីភ្នំពេញ, {{ formatKhmerDate($resolvedDgDate ?? now()) }}</td>
                    </tr>
                    <tr>
                        <td align="center" style="font-family: 'KhmerOSMoulLight', 'moul', serif; font-weight: normal; font-size: 11pt; color: #0f172a; padding-bottom: 14px;">អគ្គនាយក</td>
                    </tr>
                    <tr>
                        <td align="center" style="height: 55px; vertical-align: middle; padding-bottom: 10px;">
                            @if($resolvedDgSig && file_exists($resolvedDgSig))
                                <img class="signature-img" src="{{ $resolvedDgSig }}" alt="ហត្ថលេខាអគ្គនាយក">
                            @else
                                <div class="sig-placeholder">( ហត្ថលេខា និងត្រាឌីជីថល )</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="font-weight: bold; font-size: 11pt; color: #0f172a; padding-bottom: 8px;">{{ $resolvedDgName }}</td>
                    </tr>
                    @if($resolvedDgDate)
                    <tr>
                        <td align="center">
                            <span class="sig-status-badge">បានឯកភាព និងចុះហត្ថលេខាអនុម័ត</span>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- ─── FOOTER ─── -->
    <div class="footer-box">
        <strong>បញ្ជាក់៖</strong> លិខិតបញ្ជាក់នេះត្រូវបានបង្កើតឡើងដោយស្វ័យប្រវត្តិតាមប្រព័ន្ធគ្រប់គ្រងឯកសារ (DMS) នៃអគ្គនាយកដ្ឋានព័ត៌មាន និងសោតទស្សន៍។ រាល់ទិន្នន័យ និងហត្ថលេខាទាំងអស់មានសុពលភាពផ្លូវការស្របតាមច្បាប់ស្តីពីឯកសារអេឡិចត្រូនិក។
    </div>

</body>
</html>
