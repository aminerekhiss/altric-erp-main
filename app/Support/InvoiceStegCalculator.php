<?php

namespace App\Support;

use NumberFormatter;

class InvoiceStegCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{lines: array<int, array<string, mixed>>, total_ht: float, tva_19: float, rg_5: float, total_ttc: float, retenue_source_1: float, tva_25: float, net_a_payer: float, amount_in_words: string}
     */
    public static function calculate(array $lines): array
    {
        $normalizedLines = [];
        $totalHt = 0.0;
        $lineOrder = 1;

        foreach ($lines as $line) {
            $quantity = max(self::toFloat($line['quantity'] ?? 0), 0);
            $puht = max(self::toFloat($line['puht'] ?? 0), 0);
            $ptht = self::round3($quantity * $puht);

            $line['line_order'] = $lineOrder;
            $line['quantity'] = self::round3($quantity);
            $line['puht'] = self::round3($puht);
            $line['ptht'] = $ptht;

            $normalizedLines[] = $line;
            $totalHt += $ptht;
            $lineOrder++;
        }

        $totalHt = self::round3($totalHt);
        $tva19 = self::round3($totalHt * 0.19);
        $rg5 = self::round3($totalHt * 0.05);
        $totalTtc = self::round3($totalHt + $tva19 - $rg5);
        $retenueSource1 = self::round3($totalTtc * 0.01);
        $tva25 = self::round3($tva19 * 0.25);
        $netAPayer = self::round3($totalTtc - $retenueSource1 - $tva25);

        return [
            'lines' => $normalizedLines,
            'total_ht' => $totalHt,
            'tva_19' => $tva19,
            'rg_5' => $rg5,
            'total_ttc' => $totalTtc,
            'retenue_source_1' => $retenueSource1,
            'tva_25' => $tva25,
            'net_a_payer' => $netAPayer,
            'amount_in_words' => self::amountInWords($netAPayer),
        ];
    }

    public static function amountInWords(float $amount): string
    {
        $amount = self::round3(max($amount, 0));

        $dinar = (int) floor($amount);
        $millime = (int) round(($amount - $dinar) * 1000);

        if ($millime === 1000) {
            $dinar += 1;
            $millime = 0;
        }

        if (class_exists(NumberFormatter::class)) {
            $formatter = new NumberFormatter('fr_FR', NumberFormatter::SPELLOUT);
            $dinarWords = ucfirst($formatter->format($dinar));
            $millimeWords = $formatter->format($millime);

            return "{$dinarWords} dinars et {$millimeWords} millimes";
        }

        return number_format($amount, 3, '.', ' ') . ' dinars';
    }

    public static function round3(float $value): float
    {
        return round($value, 3);
    }

    public static function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_null($value) || is_bool($value) || is_array($value) || is_object($value)) {
            return 0.0;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return 0.0;
        }

        $normalized = preg_replace('/[^0-9,\.\-]/', '', $stringValue) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0.0;
        }

        if (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
