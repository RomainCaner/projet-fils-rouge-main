<?php

declare(strict_types=1);

namespace Cyna\Services;

use Cyna\Core\Config;

/**
 * Générateur de facture au format PDF (sans dépendance).
 *
 * Produit un document PDF 1.4 minimaliste mais valide (une page A4, police
 * Helvetica encodée WinAnsi pour le rendu des accents). Suffisant pour une
 * facture téléchargeable conforme au cahier des charges.
 */
final class InvoicePdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 60;

    /** Taux de TVA appliqué (les prix affichés sont TTC ; la TVA est calculée à rebours). */
    private const VAT_RATE = 0.20;

    /**
     * @param array<string,mixed>       $order
     * @param list<array<string,mixed>> $items
     */
    public static function generate(array $order, array $items): string
    {
        return self::render(self::buildContent($order, $items));
    }

    /**
     * Construit le flux de contenu PDF (instructions de dessin du texte).
     *
     * @param array<string,mixed>       $order
     * @param list<array<string,mixed>> $items
     */
    private static function buildContent(array $order, array $items): string
    {
        $cursor = self::PAGE_HEIGHT - self::MARGIN;
        $stream = '';

        $stream .= self::text((string) Config::get('app.name', 'Cyna'), self::MARGIN, $cursor, 22);
        $cursor -= 18;
        $stream .= self::text('CYNA-IT — 10 rue de Penthievre, 75008 Paris', self::MARGIN, $cursor, 9);
        $cursor -= 40;

        $stream .= self::text('FACTURE ' . (string) $order['invoice_number'], self::MARGIN, $cursor, 14);
        $cursor -= 16;
        $stream .= self::text('Date : ' . date('d/m/Y', strtotime((string) $order['created_at'])), self::MARGIN, $cursor, 10);
        $cursor -= 30;

        $stream .= self::text('Adresse de facturation :', self::MARGIN, $cursor, 11);
        $cursor -= 15;
        foreach (self::billingLines($order) as $line) {
            $stream .= self::text($line, self::MARGIN, $cursor, 10);
            $cursor -= 13;
        }
        $cursor -= 20;

        // En-tête du tableau des lignes.
        $stream .= self::text('Service', self::MARGIN, $cursor, 10);
        $stream .= self::text('Periode', 300, $cursor, 10);
        $stream .= self::text('Qte', 380, $cursor, 10);
        $stream .= self::text('Total', 470, $cursor, 10);
        $cursor -= 6;
        $stream .= self::line(self::MARGIN, $cursor, self::PAGE_WIDTH - self::MARGIN);
        $cursor -= 16;

        foreach ($items as $item) {
            $period = $item['billing_period'] === 'annual' ? 'Annuel' : 'Mensuel';
            $stream .= self::text((string) $item['product_name'], self::MARGIN, $cursor, 10);
            $stream .= self::text($period, 300, $cursor, 10);
            $stream .= self::text((string) $item['quantity'], 380, $cursor, 10);
            $stream .= self::text(self::money((int) $item['line_total_cents']), 470, $cursor, 10);
            $cursor -= 15;
        }

        $cursor -= 6;
        $stream .= self::line(self::MARGIN, $cursor, self::PAGE_WIDTH - self::MARGIN);
        $cursor -= 20;

        // --- Récapitulatif financier (remise + TVA) ---------------------------
        $totalTtc    = (int) $order['total_cents'];
        $discount    = (int) ($order['discount_cents'] ?? 0);
        $subtotalTtc = $totalTtc + $discount;                       // avant remise
        $ht          = (int) round($totalTtc / (1 + self::VAT_RATE));
        $vat         = $totalTtc - $ht;
        $ratePercent = (int) round(self::VAT_RATE * 100);

        $labelX = 300;
        $valueX = 440;

        if ($discount > 0) {
            $stream .= self::text('Sous-total', $labelX, $cursor, 10);
            $stream .= self::text(self::money($subtotalTtc), $valueX, $cursor, 10);
            $cursor -= 14;

            $code = ($order['discount_code'] ?? null) !== null ? ' (' . (string) $order['discount_code'] . ')' : '';
            $stream .= self::text('Remise' . $code, $labelX, $cursor, 10);
            $stream .= self::text('-' . self::money($discount), $valueX, $cursor, 10);
            $cursor -= 14;
        }

        $stream .= self::text('Total HT', $labelX, $cursor, 10);
        $stream .= self::text(self::money($ht), $valueX, $cursor, 10);
        $cursor -= 14;

        $stream .= self::text('TVA (' . $ratePercent . ' %)', $labelX, $cursor, 10);
        $stream .= self::text(self::money($vat), $valueX, $cursor, 10);
        $cursor -= 8;

        $stream .= self::line($labelX, $cursor, self::PAGE_WIDTH - self::MARGIN);
        $cursor -= 18;
        $stream .= self::text('TOTAL TTC', $labelX, $cursor, 13);
        $stream .= self::text(self::money($totalTtc), $valueX, $cursor, 13);

        // Mentions légales de bas de page.
        $stream .= self::text('SIRET 913 711 032 00015 — TVA calculee au taux de ' . $ratePercent . ' %', self::MARGIN, self::MARGIN + 12, 8);
        $stream .= self::text('Merci de votre confiance — www.cyna-it.fr', self::MARGIN, self::MARGIN, 9);

        return $stream;
    }

    /** Instruction PDF d'affichage d'un texte à une position donnée. */
    private static function text(string $value, int $x, int $y, int $size): string
    {
        return sprintf("BT /F1 %d Tf %d %d Td (%s) Tj ET\n", $size, $x, $y, self::escape($value));
    }

    /** Trace une ligne horizontale. */
    private static function line(int $x1, int $y, int $x2): string
    {
        return sprintf("%d %d m %d %d l S\n", $x1, $y, $x2, $y);
    }

    /** @param array<string,mixed> $order @return list<string> */
    private static function billingLines(array $order): array
    {
        return array_values(array_filter([
            (string) $order['billing_name'],
            (string) $order['billing_line1'],
            trim((string) $order['billing_postal_code'] . ' ' . (string) $order['billing_city']),
            (string) $order['billing_country'],
        ], static fn (string $l): bool => trim($l) !== ''));
    }

    private static function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ') . ' EUR';
    }

    /** Échappe et encode le texte pour le PDF (WinAnsi pour les accents). */
    private static function escape(string $value): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        $value = $encoded !== false ? $encoded : $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $value);
    }

    /** Assemble les objets PDF, la table xref et le trailer. */
    private static function render(string $content): string
    {
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT,
        );
        $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[5] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;
        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
