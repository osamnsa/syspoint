<?php
declare(strict_types=1);

$adminUser = require_admin();

// Back to the page the task form was on (only admin pages), else Tasks.
$return = (string) ($_POST['return'] ?? '');
$back = preg_match('#^admin(/[a-z0-9/-]*)?$#', $return) ? path($return) : path('admin/tasks');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'add') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $due = (string) ($_POST['due_date'] ?? '');
        $assignee = (int) ($_POST['assigned_to'] ?? $adminUser['id']);
        if ($title !== '') {
            db()->prepare('INSERT INTO crm_tasks (title, due_date, priority, customer_id, deal_id, assigned_to, created_by) VALUES (:t, :d, :p, :c, :deal, :a, :u)')
                ->execute([
                    't' => mb_substr($title, 0, 255),
                    'd' => DateTimeImmutable::createFromFormat('Y-m-d', $due) ? $due : null,
                    'p' => ($_POST['priority'] ?? '') === 'high' ? 'high' : 'normal',
                    'c' => (int) ($_POST['customer_id'] ?? 0) ?: null,
                    'deal' => (int) ($_POST['deal_id'] ?? 0) ?: null,
                    'a' => array_filter(crm_staff(), fn($u) => (int) $u['id'] === $assignee) ? $assignee : $adminUser['id'],
                    'u' => $adminUser['id'],
                ]);
            flash('success', 'Task added.');
        }
    } elseif ($action === 'done' || $action === 'reopen') {
        db()->prepare('UPDATE crm_tasks SET status = :s, completed_at = ' . ($action === 'done' ? 'NOW()' : 'NULL') . ' WHERE id = :id')
            ->execute(['s' => $action === 'done' ? 'done' : 'open', 'id' => $id]);
        if ($action === 'done') {
            $t = db()->prepare('SELECT * FROM crm_tasks WHERE id = :id');
            $t->execute(['id' => $id]);
            if (($task = $t->fetch()) && ($task['customer_id'] || $task['deal_id'])) {
                $cid = $task['customer_id'] ?: db()->query('SELECT customer_id FROM deals WHERE id = ' . (int) $task['deal_id'])->fetchColumn();
                crm_log($cid ? (int) $cid : null, $task['deal_id'] ? (int) $task['deal_id'] : null, 'note', 'Done: ' . $task['title']);
            }
        }
        flash('success', $action === 'done' ? 'Task done.' : 'Task reopened.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM crm_tasks WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Task deleted.');
    }
    header('Location: ' . $back);
    exit;
}

$view = (string) ($_GET['view'] ?? 'mine');
$where = match ($view) {
    'all' => "t.status = 'open'",
    'overdue' => "t.status = 'open' AND t.due_date < CURDATE()",
    'done' => "t.status = 'done'",
    default => "t.status = 'open' AND t.assigned_to = " . (int) $adminUser['id'],
};
$tasks = db()->query("SELECT t.*, c.name AS customer_name, d.title AS deal_title, u.name AS assignee FROM crm_tasks t
    LEFT JOIN customers c ON c.id = t.customer_id LEFT JOIN deals d ON d.id = t.deal_id LEFT JOIN users u ON u.id = t.assigned_to
    WHERE $where ORDER BY " . ($view === 'done' ? 't.completed_at DESC' : "t.due_date IS NULL, t.due_date, t.priority = 'high' DESC") . ' LIMIT 200')->fetchAll();
$counts = db()->query("SELECT SUM(status = 'open' AND assigned_to = " . (int) $adminUser['id'] . ") mine, SUM(status = 'open') open_all,
    SUM(status = 'open' AND due_date < CURDATE()) overdue FROM crm_tasks")->fetch();
$staff = crm_staff();
$today = date('Y-m-d');

$pageTitle = 'Tasks';
require __DIR__ . '/../partials/admin_header.php';
?>

<div class="admin-header-row"><div><p class="admin-kicker">CRM</p><h1>Tasks &amp; follow-ups</h1></div></div>

<form method="post" class="admin-form admin-form-wide crm-task-new">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <input type="text" name="title" placeholder="New task — e.g. Send ERP proposal to Federal Poly Bida" required aria-label="Task">
    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" aria-label="Due date">
    <select name="assigned_to" aria-label="Assign to"><?php foreach ($staff as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) $u['id'] === (int) $adminUser['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option><?php endforeach; ?></select>
    <select name="priority" aria-label="Priority"><option value="normal">Normal</option><option value="high">High priority</option></select>
    <button type="submit" class="btn btn-primary">Add Task</button>
</form>

<nav class="admin-filters" aria-label="Filter">
    <?php foreach (['mine' => 'Mine (' . (int) $counts['mine'] . ')', 'all' => 'All open (' . (int) $counts['open_all'] . ')', 'overdue' => 'Overdue (' . (int) $counts['overdue'] . ')', 'done' => 'Done'] as $k => $l): ?>
        <a href="<?= path('admin/tasks') ?>?view=<?= $k ?>"<?= $view === $k ? ' class="is-active"' : '' ?>><?= e($l) ?></a>
    <?php endforeach; ?>
</nav>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th style="width:44px;"></th><th>Task</th><th>About</th><th>Due</th><th>Assigned to</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tasks as $t): $late = $t['status'] === 'open' && $t['due_date'] && $t['due_date'] < $today; ?>
            <tr>
                <td>
                    <form method="post" style="margin:0;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                        <input type="hidden" name="action" value="<?= $t['status'] === 'open' ? 'done' : 'reopen' ?>">
                        <button type="submit" class="crm-check<?= $t['status'] === 'done' ? ' is-done' : '' ?>" title="<?= $t['status'] === 'open' ? 'Mark done' : 'Reopen' ?>" aria-label="<?= $t['status'] === 'open' ? 'Mark done' : 'Reopen' ?>"></button>
                    </form>
                </td>
                <td><?= $t['priority'] === 'high' ? '<span class="badge badge-danger">High</span> ' : '' ?><?= $t['status'] === 'done' ? '<s>' . e($t['title']) . '</s>' : e($t['title']) ?></td>
                <td>
                    <?php if ($t['customer_id']): ?><a href="<?= path('admin/customers/' . (int) $t['customer_id']) ?>"><?= e($t['customer_name']) ?></a><?php endif; ?>
                    <?php if ($t['deal_id']): ?><br><small><a href="<?= path('admin/deals/' . (int) $t['deal_id']) ?>"><?= e($t['deal_title']) ?></a></small><?php endif; ?>
                    <?php if (!$t['customer_id'] && !$t['deal_id']): ?><span class="muted">—</span><?php endif; ?>
                </td>
                <td style="white-space:nowrap;"><?= $t['due_date'] ? '<span class="' . ($late ? 'badge badge-danger' : ($t['due_date'] === $today ? 'badge badge-warning' : '')) . '">' . e($t['due_date'] === $today ? 'Today' : (new DateTimeImmutable($t['due_date']))->format('D j M')) . '</span>' : '—' ?></td>
                <td><?= e($t['assignee'] ?? '—') ?></td>
                <td><form method="post" style="margin:0;" onsubmit="return confirm('Delete this task?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button type="submit" class="admin-row-remove" aria-label="Delete task">×</button></form></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$tasks): ?><tr><td colspan="6" class="admin-empty"><?= $view === 'done' ? 'Nothing completed yet.' : 'Nothing to do here. 🎉' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../partials/admin_footer.php'; ?>
