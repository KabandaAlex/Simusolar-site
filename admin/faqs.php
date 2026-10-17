<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDb();
$currentNav = 'faqs';
$pageTitle = 'FAQs';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                trim($_POST['question'] ?? ''),
                trim($_POST['answer'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
            ]);
            $message = 'FAQ added successfully.';
        } catch (Throwable $e) {
            $message = 'Error adding FAQ: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        try {
            $stmt = $pdo->prepare('UPDATE faqs SET question = ?, answer = ?, sort_order = ?, is_active = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['question'] ?? ''),
                trim($_POST['answer'] ?? ''),
                (int)($_POST['sort_order'] ?? 0),
                isset($_POST['is_active']) ? 1 : 0,
                (int)$_POST['id'],
            ]);
            $message = 'FAQ updated successfully.';
        } catch (Throwable $e) {
            $message = 'Error updating FAQ: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        try {
            $stmt = $pdo->prepare('DELETE FROM faqs WHERE id = ?');
            $stmt->execute([(int)$_POST['id']]);
            $message = 'FAQ deleted successfully.';
        } catch (Throwable $e) {
            $message = 'Error deleting FAQ: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$items = [];
try {
    $items = $pdo->query('SELECT * FROM faqs ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $items = [];
}

$totalFaqs = count($items);
$activeFaqs = count(array_filter($items, fn($i) => !empty($i['is_active'])));

include __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
  <?php if ($message): ?>
    <div class="admin-alert admin-alert--<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="page-header-enhanced">
    <div class="page-header-enhanced__content">
      <h1 class="page-header-enhanced__title">Frequently Asked Questions</h1>
      <p class="page-header-enhanced__subtitle">Manage the questions and answers that appear on the public FAQ page.</p>
    </div>
  </div>

  <div class="page-stats-grid">
    <div class="page-stat-card page-stat-card--purple">
      <div class="page-stat-card__value"><?php echo $totalFaqs; ?></div>
      <div class="page-stat-card__label">Total FAQs</div>
    </div>
    <div class="page-stat-card page-stat-card--green">
      <div class="page-stat-card__value"><?php echo $activeFaqs; ?></div>
      <div class="page-stat-card__label">Active</div>
      <div class="page-stat-card__trend">
        <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg>
        <?php echo $totalFaqs ? round(($activeFaqs / $totalFaqs) * 100) : 0; ?>% visible
      </div>
    </div>
  </div>

  <div class="filter-toolbar">
    <button type="button" class="btn btn--primary" onclick="var f=document.getElementById('create-faq-form'); f.style.display=f.style.display==='none'?'block':'none'">
      <svg viewBox="0 0 24 24" width="14" height="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add FAQ
    </button>
  </div>

  <div id="create-faq-form" class="admin-card" style="display: none;">
    <div class="admin-card__header">
      <h3 class="admin-card__title">
        <svg viewBox="0 0 24 24" width="16" height="16"><path d="M9 18h6M10 14h4M12 2a7 7 0 0 0-7 7c0 2.9 1.9 5.4 4.5 6.3L10 18h4l.5-2.7C17.1 14.4 19 11.9 19 9a7 7 0 0 0-7-7z"/></svg>
        New FAQ
      </h3>
    </div>
    <div class="admin-card__body">
      <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="form-group">
          <label>Question</label>
          <input type="text" name="question" class="form-control" required placeholder="e.g. How do I get started?">
        </div>
        <div class="form-group">
          <label>Answer</label>
          <textarea name="answer" class="form-control form-control--textarea" rows="5" required placeholder="Provide a clear, helpful answer."></textarea>
        </div>
        <div class="form-group">
          <label>Sort order</label>
          <input type="number" name="sort_order" class="form-control" value="<?php echo $totalFaqs * 10; ?>" style="max-width: 120px;">
        </div>
        <div class="form-group">
          <label><input type="checkbox" name="is_active" checked> Active</label>
        </div>
        <button type="submit" class="btn btn--primary">Add FAQ</button>
        <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('create-faq-form').style.display='none'">Cancel</button>
      </form>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card__header">
      <h3 class="admin-card__title">
        <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="10"/><path d="M9 9h6v6H9z"/></svg>
        All FAQs
      </h3>
    </div>
    <div class="admin-card__body">
      <?php if (empty($items)): ?>
        <p>No FAQs have been added yet.</p>
      <?php else: ?>
        <div class="admin-table-wrap">
          <table class="admin-table enhanced-data-table">
            <thead>
              <tr>
                <th style="width: 40%;">Question</th>
                <th>Answer</th>
                <th style="width: 80px;">Order</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $row): ?>
              <tr>
                <td><?php echo e($row['question']); ?></td>
                <td><?php echo nl2br(e($row['answer'])); ?></td>
                <td><?php echo (int)$row['sort_order']; ?></td>
                <td>
                  <span class="badge <?php echo $row['is_active'] ? 'badge--success' : 'badge--muted'; ?>">
                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                  </span>
                </td>
                <td>
                  <div style="display:flex; gap: var(--space-2);">
                    <button type="button" class="btn btn--primary btn--sm" onclick="document.getElementById('edit-faq-<?php echo (int)$row['id']; ?>').style.display='block'">Edit</button>
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this FAQ?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                      <button type="submit" class="btn btn--outline btn--sm">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <tr id="edit-faq-<?php echo (int)$row['id']; ?>" class="expand-row" style="display:none;">
                <td colspan="5">
                  <form method="post" style="max-width: 720px;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                    <div class="form-group">
                      <label>Question</label>
                      <input type="text" name="question" class="form-control" value="<?php echo htmlspecialchars($row['question']); ?>" required>
                    </div>
                    <div class="form-group">
                      <label>Answer</label>
                      <textarea name="answer" class="form-control form-control--textarea" rows="5" required><?php echo htmlspecialchars($row['answer']); ?></textarea>
                    </div>
                    <div class="form-group">
                      <label>Sort order</label>
                      <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$row['sort_order']; ?>" style="max-width: 120px;">
                    </div>
                    <div class="form-group">
                      <label><input type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>> Active</label>
                    </div>
                    <button type="submit" class="btn btn--primary btn--sm">Save Changes</button>
                    <button type="button" class="btn btn--outline btn--sm" onclick="document.getElementById('edit-faq-<?php echo (int)$row['id']; ?>').style.display='none'">Cancel</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

