<?php
declare(strict_types=1);
/**
 * The quote/invoice itself — shown in the admin (and printed) and emailed.
 * @var array $doc  @var array $items  @var array $payments  @var bool $emailMode
 */
$co = company_details();
$items ??= doc_items((int) $doc['id']);
$isQuote = $doc['type'] === 'quote';
$balance = round((float) $doc['total'] - (float) $doc['amount_paid'], 2);
$s = $emailMode ?? false
    ? ['wrap' => 'font-family:Arial,sans-serif;max-width:680px;margin:0 auto;color:#0E0E24;', 'th' => 'text-align:left;padding:8px;border-bottom:2px solid #14143A;font-size:12px;', 'td' => 'padding:8px;border-bottom:1px solid #e5e5e5;']
    : ['wrap' => '', 'th' => '', 'td' => ''];
?>
<div class="doc" style="<?= $s['wrap'] ?>">
    <header class="doc-head">
        <div>
            <?php if (!($emailMode ?? false)): ?><img src="<?= asset('assets/img/logo.png') ?>" alt="" width="52" height="52"><?php endif; ?>
            <div>
                <strong class="doc-company"><?= e($co['name']) ?></strong>
                <small><?= nl2br(e($co['address'])) ?><?= $co['phone'] ? '<br>' . e($co['phone']) : '' ?><?= $co['email'] ? ' · ' . e($co['email']) : '' ?></small>
            </div>
        </div>
        <div class="doc-title">
            <h2 style="margin:0;"><?= $isQuote ? 'QUOTATION' : 'INVOICE' ?></h2>
            <p><?= e($doc['number']) ?></p>
        </div>
    </header>
    <div class="doc-parties">
        <div>
            <span class="doc-label"><?= $isQuote ? 'Prepared for' : 'Bill to' ?></span>
            <strong><?= e($doc['customer_name']) ?></strong>
            <?php if (!empty($doc['organisation_name'])): ?><br><?= e($doc['organisation_name']) ?><?php endif; ?>
            <?php if (!empty($doc['customer_address'])): ?><br><?= nl2br(e($doc['customer_address'])) ?><?php endif; ?>
            <?php if (!empty($doc['customer_email'])): ?><br><?= e($doc['customer_email']) ?><?php endif; ?>
            <?php if (!empty($doc['customer_phone'])): ?><br><?= e($doc['customer_phone']) ?><?php endif; ?>
        </div>
        <div class="doc-dates">
            <div><span class="doc-label">Date</span><?= e((new DateTimeImmutable($doc['issue_date']))->format('j M Y')) ?></div>
            <?php if ($doc['due_date']): ?><div><span class="doc-label"><?= $isQuote ? 'Valid until' : 'Due' ?></span><?= e((new DateTimeImmutable($doc['due_date']))->format('j M Y')) ?></div><?php endif; ?>
            <?php if (!$isQuote): ?><div><span class="doc-label">Balance due</span><strong><?= format_naira(max(0, $balance)) ?></strong></div><?php endif; ?>
        </div>
    </div>
    <table class="doc-items" style="width:100%;border-collapse:collapse;">
        <thead><tr><th style="<?= $s['th'] ?>">Description</th><th style="<?= $s['th'] ?>">Qty</th><th style="<?= $s['th'] ?>">Unit price</th><th style="<?= $s['th'] ?>">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $i): ?>
            <tr><td style="<?= $s['td'] ?>"><?= nl2br(e($i['description'])) ?></td><td style="<?= $s['td'] ?>"><?= e(rtrim(rtrim(number_format((float) $i['quantity'], 2), '0'), '.')) ?></td><td style="<?= $s['td'] ?>"><?= format_naira((float) $i['unit_price']) ?></td><td style="<?= $s['td'] ?>"><?= format_naira($i['quantity'] * $i['unit_price']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3">Subtotal</td><td><?= format_naira((float) $doc['subtotal']) ?></td></tr>
            <?php if ((float) $doc['discount'] > 0): ?><tr><td colspan="3">Discount</td><td>−<?= format_naira((float) $doc['discount']) ?></td></tr><?php endif; ?>
            <?php if ((float) $doc['tax'] > 0): ?><tr><td colspan="3">VAT (<?= e(rtrim(rtrim((string) $doc['tax_rate'], '0'), '.')) ?>%)</td><td><?= format_naira((float) $doc['tax']) ?></td></tr><?php endif; ?>
            <tr class="doc-total"><td colspan="3"><strong>Total</strong></td><td><strong><?= format_naira((float) $doc['total']) ?></strong></td></tr>
            <?php if (!$isQuote && (float) $doc['amount_paid'] > 0): ?>
                <tr><td colspan="3">Paid</td><td>−<?= format_naira((float) $doc['amount_paid']) ?></td></tr>
                <tr class="doc-total"><td colspan="3"><strong>Balance due</strong></td><td><strong><?= format_naira(max(0, $balance)) ?></strong></td></tr>
            <?php endif; ?>
        </tfoot>
    </table>
    <?php if ($doc['notes']): ?><div class="doc-block"><span class="doc-label">Note</span><?= nl2br(e($doc['notes'])) ?></div><?php endif; ?>
    <?php if (!$isQuote && $co['bank']): ?><div class="doc-block"><span class="doc-label">Pay to</span><?= nl2br(e($co['bank'])) ?></div><?php endif; ?>
    <?php if ($doc['terms']): ?><div class="doc-block doc-terms"><span class="doc-label">Terms</span><?= nl2br(e($doc['terms'])) ?></div><?php endif; ?>
    <?php if (!$isQuote && !$co['bank'] && !($emailMode ?? false)): ?><p class="doc-hint no-print">Tip: add your bank details in Website → Settings so they print on every invoice.</p><?php endif; ?>
</div>
