<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Handle message status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $messageId = intval($_POST['message_id']);
    
    try {
        // Toggle the read status
        $query = "UPDATE contact_messages SET is_read = NOT is_read WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        
        // Return updated status
        $statusQuery = "SELECT is_read FROM contact_messages WHERE id = ?";
        $statusStmt = $conn->prepare($statusQuery);
        $statusStmt->bind_param("i", $messageId);
        $statusStmt->execute();
        $result = $statusStmt->get_result();
        $message = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'is_read' => $message['is_read'],
            'new_status' => $message['is_read'] ? 'Read' : 'Unread',
            'status_class' => $message['is_read'] ? 'status-read' : 'status-unread'
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle message view (mark as read)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_message'])) {
    $messageId = intval($_POST['message_id']);
    
    try {
        // Mark message as read
        $query = "UPDATE contact_messages SET is_read = 1 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        
        // Get the full message details
        $messageQuery = "SELECT * FROM contact_messages WHERE id = ?";
        $messageStmt = $conn->prepare($messageQuery);
        $messageStmt->bind_param("i", $messageId);
        $messageStmt->execute();
        $result = $messageStmt->get_result();
        $message = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'status_class' => 'status-read',
            'new_status' => 'Read'
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $messageId = intval($_POST['message_id']);
    
    try {
        $query = "DELETE FROM contact_messages WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $messageId);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Message deleted successfully'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Failed to delete message'
            ];
        }
        header("Location: messages.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ];
        header("Location: messages.php");
        exit;
    }
}

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$filters = [];
$filterParams = [];

// Date filter
if (!empty($_GET['date_from'])) {
    $filters[] = "created_at >= ?";
    $filterParams[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $filters[] = "created_at <= ?";
    $filterParams[] = $_GET['date_to'] . ' 23:59:59';
}

// Status filter
$statusFilter = $_GET['status'] ?? '';
if ($statusFilter === 'read') {
    $filters[] = "is_read = 1";
} elseif ($statusFilter === 'unread') {
    $filters[] = "is_read = 0";
}

// Build WHERE clause
$whereClause = empty($filters) ? '' : 'WHERE ' . implode(' AND ', $filters);

// Fetch total number of messages for pagination
$totalQuery = "SELECT COUNT(*) as total FROM contact_messages $whereClause";
$totalStmt = $conn->prepare($totalQuery);

if (!empty($filterParams)) {
    $types = str_repeat('s', count($filterParams));
    $totalStmt->bind_param($types, ...$filterParams);
}

$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalMessages = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalMessages / $itemsPerPage);

// Fetch messages with pagination
$messagesQuery = "SELECT * FROM contact_messages $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$messagesStmt = $conn->prepare($messagesQuery);

// Combine all parameters for the query
$allParams = [];
if (!empty($filterParams)) {
    $allParams = array_merge($filterParams, [$itemsPerPage, $offset]);
    $types = str_repeat('s', count($filterParams)) . 'ii';
} else {
    $allParams = [$itemsPerPage, $offset];
    $types = 'ii';
}

$messagesStmt->bind_param($types, ...$allParams);
$messagesStmt->execute();
$messagesResult = $messagesStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - Messages Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/messages.css">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h3>Phone Mart Admin</h3>
      </div>
      <nav class="sidebar-menu">
        <ul>
          <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
          <li><a href="products.php"><i class="fas fa-box"></i> <span>Products</span></a></li>
          <li><a href="categories.php"><i class="fas fa-list"></i> <span>Categories</span></a></li>
          <li><a href="brands.php"><i class="fas fa-tags"></i> <span>Brands</span></a></li>
          <li><a href="users.php"><i class="fas fa-users"></i> <span>Users</span></a></li>
          <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>Messages Management</h1>
      </div>
      <div class="content-grid">
        <div class="table-card">
          <div class="card-header">
            <h2>Contact Messages</h2>
            <div class="table-controls">
              <form id="filterForm" method="GET" class="filter-form">
                <div class="filter-row">
                  <div class="date-filters">
                    <div class="filter-group date-filter">
                      <label for="date_from">From:</label>
                      <input type="date" id="date_from" name="date_from" 
                             value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group date-filter">
                      <label for="date_to">To:</label>
                      <input type="date" id="date_to" name="date_to" 
                             value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" class="form-control">
                    </div>
                  </div>
                  
                  <div class="filter-group status-filter">
                    <select name="status" class="form-control">
                      <option value="">All Messages</option>
                      <option value="read" <?= ($_GET['status'] ?? '') === 'read' ? 'selected' : '' ?>>Read</option>
                      <option value="unread" <?= ($_GET['status'] ?? '') === 'unread' ? 'selected' : '' ?>>Unread</option>
                    </select>
                  </div>
                </div>
              </form>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="messages-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Message</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($messagesResult->num_rows > 0): ?>
                  <?php while ($message = $messagesResult->fetch_assoc()): ?>
                    <tr data-id="<?= $message['id'] ?>" class="<?= $message['is_read'] ? '' : 'unread' ?>">
                      <td><?= htmlspecialchars($message['name']) ?></td>
                      <td><?= htmlspecialchars($message['email']) ?></td>
                      <td>
                        <div class="message-preview">
                          <?= htmlspecialchars(substr($message['message'], 0, 50)) ?>
                          <?= strlen($message['message']) > 50 ? '...' : '' ?>
                        </div>
                      </td>
                      <td><?= date('M j, Y H:i', strtotime($message['created_at'])) ?></td>
                      <td>
                        <label class="status-toggle">
                          <input type="checkbox" class="status-checkbox" 
                                 data-id="<?= $message['id'] ?>" 
                                 <?= $message['is_read'] ? 'checked' : '' ?>>
                          <span class="status-badge <?= $message['is_read'] ? 'status-read' : 'status-unread' ?>">
                            <?= $message['is_read'] ? 'Read' : 'Unread' ?>
                          </span>
                        </label>
                      </td>
                      <td>
                        <div class="action-btns">
                          <button class="btn btn-sm btn-primary view-message" data-id="<?= $message['id'] ?>">
                            <i class="fas fa-eye"></i>
                          </button>
                          <button class="btn btn-sm btn-danger delete-message" data-id="<?= $message['id'] ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center">No messages found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          
          <div class="table-footer">
            <div class="pagination">
              <?php if ($currentPage > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="btn btn-sm" title="First Page">
                  <i class="fas fa-angle-double-left"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="btn btn-sm" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </a>
              <?php else: ?>
                <span class="btn btn-sm disabled" title="First Page">
                  <i class="fas fa-angle-double-left"></i>
                </span>
                <span class="btn btn-sm disabled" title="Previous">
                  <i class="fas fa-chevron-left"></i>
                </span>
              <?php endif; ?>

              <?php 
              // Show limited page numbers with ellipsis
              $startPage = max(1, $currentPage - 2);
              $endPage = min($totalPages, $currentPage + 2);
              
              if ($startPage > 1): ?>
                <span class="btn btn-sm disabled">...</span>
              <?php endif;
              
              for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                   class="btn btn-sm <?= $i == $currentPage ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor;
              
              if ($endPage < $totalPages): ?>
                <span class="btn btn-sm disabled">...</span>
              <?php endif; ?>

              <?php if ($currentPage < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="btn btn-sm" title="Next">
                  <i class="fas fa-chevron-right"></i>
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="btn btn-sm" title="Last Page">
                  <i class="fas fa-angle-double-right"></i>
                </a>
              <?php else: ?>
                <span class="btn btn-sm disabled" title="Next">
                  <i class="fas fa-chevron-right"></i>
                </span>
                <span class="btn btn-sm disabled" title="Last Page">
                  <i class="fas fa-angle-double-right"></i>
                </span>
              <?php endif; ?>
            </div>
            
            <div class="pagination-info">
              Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalMessages) ?> of <?= $totalMessages ?> messages
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Message View Modal -->
  <div class="modal" id="messageModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Message Details</h4>
        <button type="button" class="close-modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="message-header">
          <div class="sender-info">
            <h5 id="messageSenderName"></h5>
            <p id="messageSenderEmail"></p>
          </div>
          <div class="message-meta">
            <p id="messageDate"></p>
            <span id="messageStatus" class="status-badge"></span>
          </div>
        </div>
        <div class="message-content">
          <p id="messageText"></p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary close-modal">Close</button>
        <button type="button" class="btn btn-danger" id="deleteMessageBtn">Delete</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content small">
      <div class="modal-header">
        <h4>Confirm Deletion</h4>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this message? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      </div>
    </div>
  </div>

  <script src="assets/js/messages.js"></script>
</body>
</html>