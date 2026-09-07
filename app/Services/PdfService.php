<?php

namespace App\Services;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Illuminate\Support\Facades\Log;

class PdfService
{
    /**
     * Render a Blade view to a PDF binary string using mPDF with full OpenType Khmer script shaping.
     *
     * @param string $view
     * @param array $data
     * @return string
     * @throws \Mpdf\MpdfException
     */
    public static function renderView(string $view, array $data = []): string
    {
        $rawHtml = view($view, $data)->render();

        // Normalize font-family declarations for mPDF font keys
        $cleanHtml = str_replace(
            ['KhmerOSBattambang', 'KhmerOSsiemreap', 'KhmerOSSiemreap', 'Siemreap'],
            'siemreap',
            $rawHtml
        );
        $cleanHtml = str_replace(
            ['KhmerOSMoulLight', 'KhmerOSMoul', 'MoulLight'],
            'moul',
            $cleanHtml
        );

        // Strip CSS @page and @font-face rules since mPDF handles pages/fonts in constructor
        $cleanHtml = preg_replace('/@page\s*\{[^}]*\}/s', '', $cleanHtml);
        $cleanHtml = preg_replace('/@font-face\s*\{[^}]*\}/s', '', $cleanHtml);

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Resilient Temp Directory Resolution
        $tempDir = storage_path('app/temp_mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
            @chmod($tempDir, 0777);
        }

        $mpdfSub = $tempDir . '/mpdf';
        if (!is_dir($mpdfSub)) {
            @mkdir($mpdfSub, 0777, true);
            @chmod($mpdfSub, 0777);
        }

        // Fallback to system /tmp if storage path is not writable
        if (!is_writable($tempDir) || !is_writable($mpdfSub)) {
            $sysTemp = sys_get_temp_dir() . '/temp_mpdf';
            if (!is_dir($sysTemp)) {
                @mkdir($sysTemp, 0777, true);
                @chmod($sysTemp, 0777);
            }
            $sysMpdfSub = $sysTemp . '/mpdf';
            if (!is_dir($sysMpdfSub)) {
                @mkdir($sysMpdfSub, 0777, true);
                @chmod($sysMpdfSub, 0777);
            }
            if (is_writable($sysTemp) && is_writable($sysMpdfSub)) {
                $tempDir = $sysTemp;
            }
        }

        $mpdf = new Mpdf([
            'fontDir' => array_merge($fontDirs, [
                public_path('fonts'),
            ]),
            'fontdata' => $fontData + [
                'siemreap' => [
                    'R' => 'KhmerOSsiemreap.ttf',
                    'B' => 'KhmerOSsiemreap.ttf',
                    'useOTL' => 0xFF,
                ],
                'moul' => [
                    'R' => 'KhmerOSMoulLight.ttf',
                    'B' => 'KhmerOSMoulLight.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'siemreap',
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 12,
            'margin_bottom' => 10,
            'margin_left' => 14,
            'margin_right' => 14,
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($cleanHtml);
        return $mpdf->Output('', 'S');
    }
}
