<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
redirectIfNotLoggedIn();
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    if (isset($_POST['ban_user'])) {
        // Handle ban user request
        $userId = intval($_POST['user_id']);
        $banDays = intval($_POST['ban_days']);
        $banReason = trim($_POST['ban_reason']);

        if ($userId === $_SESSION['user_id']) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'You cannot ban yourself'
            ];
            header('Location: users.php');
            exit;
        }
        
        if ($banDays < 1 || empty($banReason)) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Please provide valid ban duration and reason'
            ];
        } else {
            $unbanDate = date('Y-m-d H:i:s', strtotime("+$banDays days"));
            
            // Check if user already banned
            $checkBan = $conn->prepare("SELECT BanID FROM ban WHERE UserID = ? AND IsActive = 1");
            $checkBan->bind_param("i", $userId);
            $checkBan->execute();
            
            if ($checkBan->get_result()->num_rows > 0) {
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'message' => 'User is already banned'
                ];
            } else {
                // Insert ban record
                $stmt = $conn->prepare("INSERT INTO ban (UserID, BanReason, BanDate, UnbanDate, IsActive) 
                                       VALUES (?, ?, NOW(), ?, 1)");
                $stmt->bind_param("iss", $userId, $banReason, $unbanDate);
                
                if ($stmt->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'User banned successfully'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => 'Failed to ban user'
                    ];
                }
            }
        }
    } 
    elseif (isset($_POST['unban_user'])) {
        // Handle unban user request
        $userId = intval($_POST['user_id']);
        
        $stmt = $conn->prepare("UPDATE ban SET IsActive = 0 WHERE UserID = ? AND IsActive = 1");
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'User unbanned successfully'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Failed to unban user'
            ];
        }
    } 
    elseif (isset($_POST['delete_user'])) {
        // Handle delete user request
        $userId = intval($_POST['user_id']);
        
        // Prevent deleting admin accounts
        $checkAdmin = $conn->prepare("SELECT IsAdmin FROM user WHERE UserID = ?");
        $checkAdmin->bind_param("i", $userId);
        $checkAdmin->execute();
        $result = $checkAdmin->get_result();
        
        if ($result->num_rows > 0 && $result->fetch_assoc()['IsAdmin']) {
            $_SESSION['alert'] = [
                'type' => 'error',
                'message' => 'Cannot delete admin accounts'
            ];
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Delete related records first
                $conn->query("DELETE FROM ban WHERE UserID = $userId");
                $conn->query("DELETE FROM auth_tokens WHERE user_id = $userId");
                
                // Delete the user
                $stmt = $conn->prepare("DELETE FROM user WHERE UserID = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $conn->commit();
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'User deleted successfully'
                    ];
                } else {
                    $conn->rollback();
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => 'User not found'
                    ];
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['alert'] = [
                    'type' => 'error',
                    'message' => 'Error deleting user: ' . $e->getMessage()
                ];
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: users.php');
    exit;
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filters
$filters = [];
$filterParams = [];
$types = '';

// Date filter
if (!empty($_GET['date_from'])) {
    $filters[] = "u.CreatedAt >= ?";
    $filterParams[] = $_GET['date_from'] . ' 00:00:00';
}
if (!empty($_GET['date_to'])) {
    $filters[] = "u.CreatedAt <= ?";
    $filterParams[] = $_GET['date_to'] . ' 23:59:59';
}

// Status filter
$statusFilter = $_GET['status'] ?? '';
if ($statusFilter === 'active') {
    $filters[] = "(b.BanID IS NULL OR b.IsActive = 0)";
} elseif ($statusFilter === 'banned') {
    $filters[] = "b.IsActive = 1";
}

// Build WHERE clause
$whereClause = empty($filters) ? '' : 'WHERE ' . implode(' AND ', $filters);

// Fetch total number of users for pagination
$totalQuery = "SELECT COUNT(*) as total 
              FROM user u
              LEFT JOIN ban b ON u.UserID = b.UserID AND b.IsActive = 1
              $whereClause";
$totalStmt = $conn->prepare($totalQuery);

if (!empty($filterParams)) {
    $types = str_repeat('s', count($filterParams));
    $totalStmt->bind_param($types, ...$filterParams);
}

$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalUsers = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $itemsPerPage);

// Fetch users with pagination
$usersQuery = "SELECT u.*, 
              CASE WHEN b.BanID IS NOT NULL AND b.IsActive = 1 THEN 'Banned' ELSE 'Active' END as status,
              b.BanDate, b.BanReason, b.UnbanDate
              FROM user u
              LEFT JOIN ban b ON u.UserID = b.UserID AND b.IsActive = 1
              $whereClause
              ORDER BY u.CreatedAt DESC 
              LIMIT ? OFFSET ?";
$usersStmt = $conn->prepare($usersQuery);

// Combine all parameters for the query
$allParams = [];
if (!empty($filterParams)) {
    $allParams = array_merge($filterParams, [$itemsPerPage, $offset]);
    $types = str_repeat('s', count($filterParams)) . 'ii';
} else {
    $allParams = [$itemsPerPage, $offset];
    $types = 'ii';
}

$usersStmt->bind_param($types, ...$allParams);
$usersStmt->execute();
$usersResult = $usersStmt->get_result();

// Fetch banned users separately for the modal
$bannedUsersQuery = "SELECT u.*, b.BanID, b.BanReason, b.BanDate, b.UnbanDate
                    FROM ban b
                    JOIN user u ON b.UserID = u.UserID
                    WHERE b.IsActive = 1
                    ORDER BY b.BanDate DESC";
$bannedUsersResult = $conn->query($bannedUsersQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phone Mart - User Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/messages.css">
  <link rel="stylesheet" href="assets/css/users.css">
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
          <li><a href="users.php" class="active"><i class="fas fa-users"></i> <span>Users</span></a></li>
          <li><a href="messages.php"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
          <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>
          <li><a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>User Management</h1>
      </div>

      <!-- Display alert messages -->
      <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type'] ?>">
          <?= htmlspecialchars($_SESSION['alert']['message']) ?>
        </div>
        <?php unset($_SESSION['alert']); ?>
      <?php endif; ?>

      <div class="content-grid">
        <div class="table-card">
          <div class="card-header">
            <h2>All Users</h2>
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
                      <option value="">All Users</option>
                      <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                      <option value="banned" <?= ($_GET['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
                    </select>
                  </div>
                </div>
              </form>
            </div>
          </div>
          
          <div class="table-responsive">
            <table class="users-table">
              <thead>
                <tr>
                  <th class="user-col">User</th>
                  <th class="email-col">Email</th>
                  <th class="phone-col">Phone</th>
                  <th class="joined-col">Joined Date</th>
                  <th class="status-col">Status</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($usersResult->num_rows > 0): ?>
                  <?php while ($user = $usersResult->fetch_assoc()): ?>
                    <tr data-id="<?= $user['UserID'] ?>" data-status="<?= strtolower($user['status']) ?>">
                      <td>
                        <div class="user-cell">
                          <div class="user-info">
                            <h4><?= htmlspecialchars($user['Username']) ?></h4>
                            <p>ID: <?= $user['UserID'] ?></p>
                          </div>
                        </div>
                      </td>
                      <td class="email-col"><?= htmlspecialchars($user['Email']) ?></td>
                      <td class="phone-col"><?= htmlspecialchars($user['PhoneNumber']) ?></td>
                      <td><?= date('M j, Y H:i', strtotime($user['CreatedAt'])) ?></td>
                      <td>
                        <span class="status-badge status-<?= strtolower($user['status']) ?>">
                          <?= $user['status'] ?>
                        </span>
                      </td>
                      <td>
                        <div class="action-btns">
                          <?php if ($user['status'] == 'Active'): ?>
                            <button class="btn btn-warning ban-user-btn" data-user-id="<?= $user['UserID'] ?>">
                              <i class="fas fa-ban"></i> Ban
                            </button>
                          <?php else: ?>
                            <button class="btn btn-success unban-user-btn" data-user-id="<?= $user['UserID'] ?>">
                              <i class="fas fa-check-circle"></i> Unban
                            </button>
                          <?php endif; ?>
                          <button class="btn btn-danger delete-user-btn" data-user-id="<?= $user['UserID'] ?>">
                            <i class="fas fa-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" class="text-center">No users found</td>
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
              Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalUsers) ?> of <?= $totalUsers ?> users
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Banned Users Modal -->
  <div class="modal" id="bannedUsersModal">
    <div class="modal-content large">
      <div class="modal-header">
        <h4>Banned Users</h4>
        <button type="button" class="close-modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <table class="users-table">
            <thead>
              <tr>
                <th class="user-col">User</th>
                <th class="email-col">Email</th>
                <th class="phone-col">Phone</th>
                <th class="joined-col">Joined Date</th>
                <th class="banned-col">Banned Since</th>
                <th class="reason-col">Reason</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($bannedUsersResult->num_rows > 0): ?>
                <?php while ($bannedUser = $bannedUsersResult->fetch_assoc()): ?>
                  <tr>
                    <td>
                      <div class="user-cell">
                        <div class="user-info">
                          <h4><?= htmlspecialchars($bannedUser['Username']) ?></h4>
                          <p>ID: <?= $bannedUser['UserID'] ?></p>
                        </div>
                      </div>
                    </td>
                    <td class="email-col"><?= htmlspecialchars($bannedUser['Email']) ?></td>
                    <td class="phone-col"><?= htmlspecialchars($bannedUser['PhoneNumber']) ?></td>
                    <td><?= date('M j, Y', strtotime($bannedUser['CreatedAt'])) ?></td>
                    <td><?= date('M j, Y', strtotime($bannedUser['BanDate'])) ?></td>
                    <td class="reason-col"><?= htmlspecialchars($bannedUser['BanReason']) ?></td>
                    <td>
                      <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="user_id" value="<?= $bannedUser['UserID'] ?>">
                        <button type="submit" name="unban_user" class="btn btn-success">
                          <i class="fas fa-check-circle"></i> Unban
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center">No banned users found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary close-modal">Close</button>
      </div>
    </div>
  </div>

  <!-- Ban User Modal -->
  <div class="modal" id="banUserModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4>Ban User</h4>
        <button type="button" class="close-modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="user_id" id="banUserId">
          <div class="form-group">
            <label for="banDays">Ban Duration (days)</label>
            <input type="number" id="banDays" name="ban_days" class="form-control" min="1" value="7" required>
          </div>
          <div class="form-group">
            <label for="banReason">Ban Reason</label>
            <textarea id="banReason" name="ban_reason" class="form-control" rows="3" 
                      placeholder="Enter the reason for banning this user" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" name="ban_user" class="btn btn-danger">Ban User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete User Modal -->
  <div class="modal" id="deleteUserModal">
    <div class="modal-content small">
      <div class="modal-header">
        <h4>Confirm User Deletion</h4>
        <button type="button" class="close-modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <p>Are you sure you want to permanently delete this user account? This action cannot be undone.</p>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="user_id" id="deleteUserId">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/users.js"></script>
</body>
</html>