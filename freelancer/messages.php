<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_role('freelancer');

$pdo = db();
$uid = current_user_id();

// ── AJAX: Send Message ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
        exit;
    }

    $recipientId = (int)($_POST['recipient_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($recipientId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient.']);
        exit;
    }
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    // Verify recipient is a client (not another freelancer)
    $recipientStmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $recipientStmt->execute([$recipientId]);
    $recipient = $recipientStmt->fetch();

    if (!$recipient || $recipient['role'] !== 'client') {
        echo json_encode(['success' => false, 'message' => 'Invalid recipient.']);
        exit;
    }

    // Verify they have an accepted proposal or existing conversation with this client
    $hasConnectionStmt = $pdo->prepare('
        SELECT 1 FROM proposals p
        JOIN jobs j ON j.id = p.job_id
        WHERE p.freelancer_id = ? AND j.client_id = ? AND p.status = "accepted"
        
        UNION
        
        SELECT 1 FROM messages
        WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
    ');
    $hasConnectionStmt->execute([$uid, $recipientId, $uid, $recipientId, $recipientId, $uid]);
    
    if (!$hasConnectionStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You must have an accepted proposal with this client to message them.']);
        exit;
    }

    // Insert message
    $insertStmt = $pdo->prepare('
        INSERT INTO messages (sender_id, recipient_id, content)
        VALUES (?, ?, ?)
    ');
    $insertStmt->execute([$uid, $recipientId, $content]);

    echo json_encode(['success' => true, 'message' => 'Message sent!']);
    exit;
}

// ── Fetch Conversations + Accepted Proposals ──────────────
// Get existing conversations
$conversationsStmt = $pdo->prepare('
    SELECT DISTINCT
        CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END AS other_user_id,
        u.full_name,
        u.avatar,
        MAX(m.created_at) AS last_message_time
    FROM messages m
    JOIN users u ON u.id = (CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END)
    WHERE sender_id = ? OR recipient_id = ?
    GROUP BY u.id
    ORDER BY last_message_time DESC
');
$conversationsStmt->execute([$uid, $uid, $uid, $uid]);
$conversations = $conversationsStmt->fetchAll();

// Get accepted proposals without existing conversations
$proposalsStmt = $pdo->prepare('
    SELECT DISTINCT j.client_id AS other_user_id, u.full_name, u.avatar, NULL AS last_message_time
    FROM proposals p
    JOIN jobs j ON j.id = p.job_id
    JOIN users u ON u.id = j.client_id
    WHERE p.freelancer_id = ? AND p.status = "accepted"
    AND j.client_id NOT IN (
        SELECT CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END
        FROM messages
        WHERE sender_id = ? OR recipient_id = ?
    )
');
$proposalsStmt->execute([$uid, $uid, $uid, $uid]);
$proposalConversations = $proposalsStmt->fetchAll();

// Merge both lists
$conversations = array_merge($conversations, $proposalConversations);

// Add unread count and last message to each conversation
foreach ($conversations as &$conv) {
    $unreadStmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM messages WHERE sender_id = ? AND recipient_id = ? AND is_read = 0');
    $unreadStmt->execute([$conv['other_user_id'], $uid]);
    $conv['unread_count'] = $unreadStmt->fetchColumn();
    
    $lastMsgStmt = $pdo->prepare('SELECT content FROM messages WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?) ORDER BY created_at DESC LIMIT 1');
    $lastMsgStmt->execute([$uid, $conv['other_user_id'], $conv['other_user_id'], $uid]);
    $conv['last_message'] = $lastMsgStmt->fetchColumn() ?: '(No previous messages)';
}
unset($conv); // Bug 8 fix: clear reference to avoid silent data corruption

// ── Fetch Selected Conversation ───────────────────────────
$selectedUserId = (int)($_GET['user'] ?? 0);
$messages = [];

if ($selectedUserId > 0) {
    $messagesStmt = $pdo->prepare('
        SELECT m.*, u.full_name, u.avatar
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
        ORDER BY m.created_at ASC
    ');
    $messagesStmt->execute([$uid, $selectedUserId, $selectedUserId, $uid]);
    $messages = $messagesStmt->fetchAll();

    // Mark as read
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND recipient_id = ?')
        ->execute([$selectedUserId, $uid]);
}

$pageTitle = 'Messages';
$activePage = 'messages';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar_freelancer.php'; ?>

<div class="page-wrapper">
<div class="page-content" style="padding-top:0; padding-bottom:0;">
  <div class="row g-0" style="min-height: calc(100vh - 120px); height: 100%;">
    <!-- Conversations Sidebar -->
    <div class="col-md-4 border-end" style="overflow-y: auto; background: #f8fafb">
      <div class="p-3 border-bottom">
        <h5 class="mb-0">Messages</h5>
      </div>
      
      <?php if (empty($conversations)): ?>
        <div class="p-4 text-center text-muted">
          <i class="bi bi-chat-left" style="font-size: 2rem; opacity: 0.3"></i>
          <p class="mt-2 small">No conversations yet</p>
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($conversations as $conv): ?>
          <a href="?user=<?= $conv['other_user_id'] ?>" 
             class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-3 <?= $selectedUserId === (int)$conv['other_user_id'] ? 'active' : '' ?>">
            <?php if (!empty($conv['avatar']) && file_exists(UPLOAD_DIR . $conv['avatar'])): ?>
              <img src="<?= UPLOAD_URL . e($conv['avatar']) ?>" class="avatar-sm rounded-circle" alt="">
            <?php else: ?>
              <div class="avatar-placeholder rounded-circle"><?= strtoupper(substr($conv['full_name'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <p class="mb-0 fw-500"><?= e($conv['full_name']) ?></p>
                <?php if ($conv['unread_count'] > 0): ?>
                  <span class="badge bg-primary rounded-pill"><?= $conv['unread_count'] ?></span>
                <?php endif; ?>
              </div>
              <p class="mb-0 small text-muted text-truncate"><?= e(substr($conv['last_message'], 0, 50)) ?></p>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Message Thread -->
    <div class="col-md-8 d-flex flex-column h-100" style="background: #fff">
      <?php if ($selectedUserId > 0): 
        $otherUser = $pdo->prepare('SELECT full_name, avatar FROM users WHERE id = ?');
        $otherUser->execute([$selectedUserId]);
        $other = $otherUser->fetch();
      ?>
        <!-- Header -->
        <div class="p-3 border-bottom d-flex align-items-center gap-2" style="flex-shrink: 0">
          <?php if (!empty($other['avatar']) && file_exists(UPLOAD_DIR . $other['avatar'])): ?>
            <img src="<?= UPLOAD_URL . e($other['avatar']) ?>" class="avatar-sm rounded-circle" alt="">
          <?php else: ?>
            <div class="avatar-placeholder rounded-circle"><?= strtoupper(substr($other['full_name'], 0, 1)) ?></div>
          <?php endif; ?>
          <h6 class="mb-0"><?= e($other['full_name']) ?></h6>
        </div>

        <!-- Messages -->
        <div class="flex-grow-1 overflow-y-auto p-3" id="messagesContainer" style="background: #f8fafb; min-height: 0">
          <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $msg): ?>
            <div class="d-flex mb-3 <?= $msg['sender_id'] === $uid ? 'justify-content-end' : '' ?>">
              <div class="<?= $msg['sender_id'] === $uid ? 'bg-primary text-white' : 'bg-light' ?> p-3 rounded-3" style="max-width: 70%">
                <p class="mb-1"><?= e($msg['content']) ?></p>
                <small class="<?= $msg['sender_id'] === $uid ? 'opacity-75' : 'text-muted' ?>"><?= date('M j, g:i A', strtotime($msg['created_at'])) ?></small>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center text-muted mt-5">
              <p>No messages yet. Start the conversation!</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Input -->
        <div class="p-3 border-top" style="flex-shrink: 0">
          <form id="messageForm" class="d-flex gap-2" data-recipient="<?= $selectedUserId ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="recipient_id" value="<?= $selectedUserId ?>">
            <input type="text" name="content" class="form-control rounded-3" placeholder="Type a message..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" required>
            <button type="submit" class="btn btn-primary rounded-3">
              <i class="bi bi-send"></i>
            </button>
          </form>
        </div>
      <?php else: ?>
        <div class="d-flex align-items-center justify-content-center h-100">
          <div class="text-center">
            <i class="bi bi-chat-left" style="font-size: 3rem; opacity: 0.2"></i>
            <p class="text-muted mt-3">Select a conversation to start messaging</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<script>
// Auto-scroll to bottom
const container = document.getElementById('messagesContainer');
if (container) {
  container.scrollTop = container.scrollHeight;
}

// Auto-refresh messages every 3 seconds
let lastMessageCount = 0;
function refreshMessages() {
  const selectedUserId = new URLSearchParams(window.location.search).get('user');
  if (!selectedUserId) return;
  
  fetch(window.location.href + '&refresh=1', {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(res => res.text())
  .then(html => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const newMessages = doc.getElementById('messagesContainer');
    const currentMessages = document.getElementById('messagesContainer');
    
    if (newMessages && currentMessages && newMessages.innerHTML !== currentMessages.innerHTML) {
      currentMessages.innerHTML = newMessages.innerHTML;
      currentMessages.scrollTop = currentMessages.scrollHeight;
      
      // Update conversation list
      const newConvList = doc.querySelector('.list-group');
      const currentConvList = document.querySelector('.list-group');
      if (newConvList && currentConvList) {
        currentConvList.innerHTML = newConvList.innerHTML;
      }
    }
  })
  .catch(err => console.log('Refresh error:', err));
}

// Start polling if in a conversation
if (window.location.search.includes('user=')) {
  setInterval(refreshMessages, 3000); // Refresh every 3 seconds
}

// Send message
const messageForm = document.getElementById('messageForm');
if (messageForm) {
  messageForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const input = messageForm.querySelector('input[name="content"]');
    const formData = new FormData(messageForm);

    try {
      const res = await fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const data = await res.json();

      if (data.success) {
        input.value = '';
        refreshMessages(); // Refresh immediately after sending
      } else {
        alert(data.message || 'Error sending message');
      }
    } catch (err) {
      alert('Error sending message');
    }
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
