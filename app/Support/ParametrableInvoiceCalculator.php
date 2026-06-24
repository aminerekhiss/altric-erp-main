<?php

namespace App\Support;

use NumberFormatter;

class ParametrableInvoiceCalculator
{
    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array<string, mixed>>  $adjustments
     * @return array{lines: array<int, array<string, mixed>>, adjustments: array<int, array<string, mixed>>, total_ht: float, adjustments_total: float, net_ht: float, amount_in_words: string}
     */
    public static function calculate(array $lines, array $adjustments): array
    {
        $normalizedLines = [];
        $totalHt = 0.0;
        $lineOrder = 1;

        foreach ($lines as $line) {
            $quantity = max(self::toFloat($line['quantity'] ?? 0), 0);
            $puht = max(self::toFloat($line['puht'] ?? 0), 0);
            $ptht = self::round3($quantity * $puht);

            $line['line_order'] = $lineOrder;
            $line['ptht'] = $ptht;
            $line['quantity'] = self::round3($quantity);
            $line['puht'] = self::round3($puht);

            $normalizedLines[] = $line;
            $totalHt += $ptht;
            $lineOrder++;
        }

        $totalHt = self::round3($totalHt);

        $normalizedAdjustments = [];
        $adjustmentsTotal = 0.0;
        $adjustmentOrder = 1;

        foreach ($adjustments as $adjustment) {
            $percentage = max(self::toFloat($adjustment['percentage'] ?? 0), 0);
            $operation = ($adjustment['operation'] ?? 'add') === 'subtract' ? 'subtract' : 'add';
            $amount = self::round3($totalHt * ($percentage / 100));
            $signedAmount = $operation === 'subtract' ? -$amount : $amount;

            $adjustment['sort_order'] = $adjustmentOrder;
            $adjustment['operation'] = $operation;
            $adjustment['percentage'] = self::round3($percentage);
            $adjustment['amount'] = $amount;

            $normalizedAdjustments[] = $adjustment;
            $adjustmentsTotal += $signedAmount;
            $adjustmentOrder++;
        }

        $adjustmentsTotal = self::round3($adjustmentsTotal);
        $netHt = self::round3($totalHt + $adjustmentsTotal);

        return [
            'lines' => $normalizedLines,
            'adjustments' => $normalizedAdjustments,
            'total_ht' => $totalHt,
            'adjustments_total' => $adjustmentsTotal,
            'net_ht' => $netHt,
            'amount_in_words' => self::amountInWords($netHt),
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

        // Keep digits, separators, and sign; remove currency symbols and text.
        $normalized = preg_replace('/[^0-9,\.\-]/', '', $stringValue) ?? '';

        if ($normalized === '' || $normalized === '-' || $normalized === '.' || $normalized === ',') {
            return 0.0;
        }

        // If comma is used as decimal separator, convert to dot.
        if (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            // Remove thousands separators when both comma and dot exist.
            $normalized = str_replace(',', '', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
