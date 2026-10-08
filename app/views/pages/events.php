<?php
declare(strict_types=1);

$pageTitle = 'Events';
$pageDescription = 'Tournaments, game nights, training intakes and open days at ' . site('site.hub_name') . '.';
$eventsList = events_upcoming(null, 50);
$channel = telegram_channel_url();

require __DIR__ . '/../partials/header.php';
?>

<section class="section">
    <div class="container">
        <span class="eyebrow">What’s on</span>
        <h1>Upcoming events</h1>
        <p class="section-lede">Tournaments, game nights, training intakes and open days at <?= e(site('site.hub_name')) ?>.<?php if ($channel): ?> <a href="<?= e($channel) ?>" target="_blank" rel="noopener">Follow us on Telegram</a> to hear about new ones first.<?php endif; ?></p>
        <?php if ($eventsList): ?>
            <?php require __DIR__ . '/../partials/events_list.php'; ?>
        <?php else: ?>
            <p class="event-empty">Nothing scheduled right now — check back soon<?= $channel ? ', or <a href="' . e($channel) . '" target="_blank" rel="noopener">join our Telegram channel</a> to be told first' : '' ?>.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../partials/footer.php'; ?>
