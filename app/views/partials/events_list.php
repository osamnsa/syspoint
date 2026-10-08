<?php
/** Event cards. Set $eventsList (rows from events_upcoming()) before including. */
?>
<div class="event-grid">
    <?php foreach ($eventsList as $ev):
        $start = new DateTimeImmutable((string) $ev['starts_at']);
        $price = event_price_label($ev); ?>
        <article class="event-card" id="event-<?= (int) $ev['id'] ?>">
            <?php if ($ev['image_path']): ?>
                <div class="event-card-img"><img src="<?= e(media_url($ev['image_path'])) ?>" alt="" loading="lazy"></div>
            <?php endif; ?>
            <div class="event-card-body">
                <div class="event-card-head">
                    <time class="event-date" datetime="<?= e($start->format('c')) ?>"><span><?= e($start->format('M')) ?></span><strong><?= e($start->format('j')) ?></strong></time>
                    <div>
                        <span class="event-kind"><?= e(EVENT_KINDS[$ev['kind']] ?? '') ?><?= $price !== '' ? ' · ' . e($price) : '' ?></span>
                        <h3><?= e($ev['title']) ?></h3>
                    </div>
                </div>
                <p class="event-meta"><?= e(event_when($ev)) ?><?= $ev['venue'] ? '<br>' . e($ev['venue']) : '' ?></p>
                <?php if ($ev['description']): ?><p class="event-text"><?= nl2br(e($ev['description'])) ?></p><?php endif; ?>
                <?php if ($ev['button_url']): ?>
                    <a href="<?= e($ev['button_url']) ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener"><?= e($ev['button_text'] ?: 'Details') ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
