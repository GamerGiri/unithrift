<?php
// admin.php - Administrator Control Panel & Moderation Center
$pageTitle = "Admin Control Panel";
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

// Strict Admin Route Guard
require_admin();

$csrfToken = get_csrf_token();
$activeTab = $_GET['tab'] ?? 'listings';
$searchQuery = trim($_GET['q'] ?? '');

// User Tab Filters & Search
$userSearch = trim($_GET['user_search'] ?? '');
if (empty($userSearch) && ($activeTab === 'users') && !empty($searchQuery)) {
    $userSearch = $searchQuery;
}
$userType = trim($_GET['user_type'] ?? 'all'); // 'all', 'seu', 'outsiders', 'admins'
if (!in_array($userType, ['all', 'seu', 'outsiders', 'admins'])) {
    $userType = 'all';
}

// Report Tab Filters & Search
$reportStatus = strtolower(trim($_GET['report_status'] ?? 'all')); // 'all', 'pending', 'reviewed', 'ignored'
if (!in_array($reportStatus, ['all', 'pending', 'reviewed', 'ignored'])) {
    $reportStatus = 'all';
}
$reportSearch = trim($_GET['report_search'] ?? '');
if (empty($reportSearch) && ($activeTab === 'reports') && !empty($searchQuery)) {
    $reportSearch = $searchQuery;
}

// Message Tab Filters & Search
$messageStatus = strtolower(trim($_GET['msg_status'] ?? 'all')); // 'all', 'unread', 'read'
if (!in_array($messageStatus, ['all', 'unread', 'read'])) {
    $messageStatus = 'all';
}
$messageSearch = trim($_GET['msg_search'] ?? '');
if (empty($messageSearch) && ($activeTab === 'messages') && !empty($searchQuery)) {
    $messageSearch = $searchQuery;
}

// =========================================================================
// 1. POST Actions: Moderation, Status Updates, User Management
// =========================================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        set_flash('error', 'CSRF verification failed.');
        header("Location: admin.php?tab={$activeTab}");
        exit();
    }

    // A. Admin Change Listing Status
    if ($action === 'change_item_status') {
        $itemId    = (int)($_POST['item_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'Available');
        if ($itemId > 0 && in_array($newStatus, ['Available', 'Reserved', 'Sold'])) {
            try {
                $stmt = $pdo->prepare("UPDATE items SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $itemId]);
                set_flash('success', "Listing #{$itemId} status updated to {$newStatus}.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to update status: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=listings");
        exit();
    }

    // B. Admin Delete Listing (Moderation)
    elseif ($action === 'admin_delete_item') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($itemId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
                $stmt->execute([':id' => $itemId]);
                set_flash('success', "Listing #{$itemId} permanently removed by administrator.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to delete listing: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=listings");
        exit();
    }

    // B2. Admin Edit Listing (Full Moderation Update)
    elseif ($action === 'admin_update_item') {
        $itemId         = (int)($_POST['item_id'] ?? 0);
        $title          = trim($_POST['title'] ?? '');
        $category       = trim($_POST['category'] ?? 'Textbooks');
        $courseCode     = strtoupper(trim($_POST['course_code'] ?? ''));
        $condition      = trim($_POST['item_condition'] ?? 'Gently Used');
        $origPrice      = (float)($_POST['original_price'] ?? 0);
        $sellPrice      = (float)($_POST['selling_price'] ?? 0);
        $description    = trim($_POST['description'] ?? '');
        $meetupLocation = trim($_POST['meetup_location'] ?? 'SEU Main Cafeteria');
        $status         = trim($_POST['status'] ?? 'Available');
        $icon           = trim($_POST['image_icon'] ?? '📦');
        $newImageUrl    = save_item_image($_FILES['item_image'] ?? null);

        if (empty($title) || empty($description) || empty($meetupLocation) || $itemId <= 0) {
            set_flash('error', 'Invalid input data for update.');
        } elseif ($sellPrice <= 0) {
            set_flash('error', 'Selling price must be greater than zero.');
        } else {
            try {
                if ($newImageUrl) {
                    $stmt = $pdo->prepare("
                        UPDATE items SET 
                            title = :title,
                            category = :category,
                            course_code = :course,
                            item_condition = :condition,
                            original_price = :orig,
                            selling_price = :sell,
                            description = :desc,
                            meetup_location = :meetup,
                            status = :status,
                            image_icon = :icon,
                            image_url = :img
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':title'     => $title,
                        ':category'  => $category,
                        ':course'    => $courseCode ?: null,
                        ':condition' => $condition,
                        ':orig'      => ($origPrice > 0 ? $origPrice : $sellPrice),
                        ':sell'      => $sellPrice,
                        ':desc'      => $description,
                        ':meetup'    => $meetupLocation,
                        ':status'    => $status,
                        ':icon'      => $icon,
                        ':img'       => $newImageUrl,
                        ':id'        => $itemId
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE items SET 
                            title = :title,
                            category = :category,
                            course_code = :course,
                            item_condition = :condition,
                            original_price = :orig,
                            selling_price = :sell,
                            description = :desc,
                            meetup_location = :meetup,
                            status = :status,
                            image_icon = :icon
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':title'     => $title,
                        ':category'  => $category,
                        ':course'    => $courseCode ?: null,
                        ':condition' => $condition,
                        ':orig'      => ($origPrice > 0 ? $origPrice : $sellPrice),
                        ':sell'      => $sellPrice,
                        ':desc'      => $description,
                        ':meetup'    => $meetupLocation,
                        ':status'    => $status,
                        ':icon'      => $icon,
                        ':id'        => $itemId
                    ]);
                }

                set_flash('success', "Listing #{$itemId} successfully updated by Administrator.");
            } catch (PDOException $e) {
                set_flash('error', 'Admin update failed: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=listings");
        exit();
    }

    // C. Admin Toggle User Role (Student <-> Admin)
    elseif ($action === 'toggle_user_role') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newRole      = trim($_POST['role'] ?? 'student');

        // Prevent self-demotion
        if ($targetUserId === (int)$_SESSION['user_id']) {
            set_flash('error', 'You cannot change your own administrator role.');
        } elseif ($targetUserId > 0 && in_array($newRole, ['student', 'admin'])) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
                $stmt->execute([':role' => $newRole, ':id' => $targetUserId]);
                set_flash('success', "User #{$targetUserId} role updated to {$newRole}.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to update user role: ' . $e->getMessage());
            }
        }
        $userTypeParam = trim($_POST['user_type'] ?? '');
        $userSearchParam = trim($_POST['user_search'] ?? '');
        $uUrl = "admin.php?tab=users";
        if (!empty($userTypeParam) && $userTypeParam !== 'all') $uUrl .= "&user_type=" . urlencode($userTypeParam);
        if (!empty($userSearchParam)) $uUrl .= "&user_search=" . urlencode($userSearchParam);
        header("Location: {$uUrl}");
        exit();
    }

    // D. Admin Delete User Account
    elseif ($action === 'admin_delete_user') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);

        if ($targetUserId === (int)$_SESSION['user_id']) {
            set_flash('error', 'You cannot delete your own administrator account.');
        } elseif ($targetUserId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $targetUserId]);
                set_flash('success', "User account and all associated listings removed.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to delete user: ' . $e->getMessage());
            }
        }
        $userTypeParam = trim($_POST['user_type'] ?? '');
        $userSearchParam = trim($_POST['user_search'] ?? '');
        $uUrl = "admin.php?tab=users";
        if (!empty($userTypeParam) && $userTypeParam !== 'all') $uUrl .= "&user_type=" . urlencode($userTypeParam);
        if (!empty($userSearchParam)) $uUrl .= "&user_search=" . urlencode($userSearchParam);
        header("Location: {$uUrl}");
        exit();
    }

    // D2. Admin Provision User (Allows Any Email, including non-seu.edu.bd)
    elseif ($action === 'admin_create_user') {
        $studentId  = trim($_POST['student_id'] ?? '');
        $fullName   = trim($_POST['full_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? 'General');
        $role       = in_array($_POST['role'] ?? '', ['student', 'admin']) ? $_POST['role'] : 'student';
        $password   = $_POST['password'] ?? '';

        if (empty($studentId) || empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            set_flash('error', 'All fields are required to provision an account.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address.');
        } elseif (strlen($password) < 6) {
            set_flash('error', 'Password must be at least 6 characters long.');
        } else {
            try {
                $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email OR student_id = :sid LIMIT 1");
                $chk->execute([':email' => $email, ':sid' => $studentId]);
                if ($chk->fetch()) {
                    set_flash('error', "A user with Student ID '{$studentId}' or email '{$email}' already exists.");
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (student_id, full_name, email, phone, department, role, password_hash)
                        VALUES (:sid, :name, :email, :phone, :dept, :role, :hash)
                    ");
                    $ins->execute([
                        ':sid'   => $studentId,
                        ':name'  => $fullName,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':dept'  => $department,
                        ':role'  => $role,
                        ':hash'  => $hashed
                    ]);

                    $newUid = $pdo->lastInsertId();

                    // Optional welcome notification
                    $welcomeStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message)
                        VALUES (:uid, 'Account Provisioned by Administrator', 'Welcome to UniThrift. Your verified account was provisioned with special access by campus administration.')
                    ");
                    $welcomeStmt->execute([':uid' => $newUid]);

                    set_flash('success', "User account for '{$fullName}' ({$email}) successfully created with {$role} privileges.");
                }
            } catch (PDOException $e) {
                set_flash('error', 'Failed to create user account: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=users");
        exit();
    }

    // E. Moderation: Remove Reported Listing
    elseif ($action === 'remove_reported_listing') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $itemId   = (int)($_POST['item_id'] ?? 0);
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $adminMsg = trim($_POST['admin_message'] ?? 'This listing was removed by campus administration following a safety report.');

        if ($itemId > 0) {
            try {
                // Fetch item title before deleting
                $itemTitleStmt = $pdo->prepare("SELECT title FROM items WHERE id = :id");
                $itemTitleStmt->execute([':id' => $itemId]);
                $itemTitle = $itemTitleStmt->fetchColumn() ?: "Listing #{$itemId}";

                // Delete the listing (cascades related references)
                $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
                $stmt->execute([':id' => $itemId]);

                // Delete the safety report along with the taken-down listing
                if ($reportId > 0) {
                    $pdo->prepare("DELETE FROM reports WHERE id = :id")->execute([':id' => $reportId]);
                }

                // If seller exists, send direct administrative notification
                if ($sellerId > 0) {
                    $notifStmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message)
                        VALUES (:uid, :title, :msg)
                    ");
                    $notifStmt->execute([
                        ':uid'   => $sellerId,
                        ':title' => "Listing Removed by Admin: {$itemTitle}",
                        ':msg'   => "Your listing \"{$itemTitle}\" was taken down by campus administration. Administrative Note: {$adminMsg}"
                    ]);
                }

                set_flash('success', "Listing permanently removed and safety report closed.");
            } catch (PDOException $e) {
                set_flash('error', 'Action failed: ' . $e->getMessage());
            }
        }
        $repStatusParam = trim($_POST['redirect_status'] ?? '');
        $repSearchParam = trim($_POST['redirect_search'] ?? '');
        $rUrl = "admin.php?tab=reports";
        if (!empty($repStatusParam) && $repStatusParam !== 'all') $rUrl .= "&report_status=" . urlencode($repStatusParam);
        if (!empty($repSearchParam)) $rUrl .= "&report_search=" . urlencode($repSearchParam);
        header("Location: {$rUrl}");
        exit();
    }

    // F. Moderation: Ignore Report
    elseif ($action === 'ignore_report') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        if ($reportId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE reports SET status = 'Ignored', admin_notes = 'Reviewed and dismissed by administrator.' WHERE id = :id");
                $stmt->execute([':id' => $reportId]);
                set_flash('info', "Report #{$reportId} marked as Ignored.");
            } catch (PDOException $e) {
                set_flash('error', 'Action failed: ' . $e->getMessage());
            }
        }
        $repStatusParam = trim($_POST['redirect_status'] ?? '');
        $repSearchParam = trim($_POST['redirect_search'] ?? '');
        $rUrl = "admin.php?tab=reports";
        if (!empty($repStatusParam) && $repStatusParam !== 'all') $rUrl .= "&report_status=" . urlencode($repStatusParam);
        if (!empty($repSearchParam)) $rUrl .= "&report_search=" . urlencode($repSearchParam);
        header("Location: {$rUrl}");
        exit();
    }

    // G. Moderation: Notify Seller with Custom Message
    elseif ($action === 'notify_seller') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $sellerId = (int)($_POST['seller_id'] ?? 0);
        $itemId   = (int)($_POST['item_id'] ?? 0);
        $title    = trim($_POST['notice_title'] ?? 'Administrative Notice Regarding Your Listing');
        $message  = trim($_POST['custom_message'] ?? '');

        if ($sellerId <= 0 || empty($message)) {
            set_flash('error', 'Please provide a valid seller and message content.');
        } else {
            try {
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, item_id, title, message)
                    VALUES (:uid, :item_id, :title, :msg)
                ");
                $notifStmt->execute([
                    ':uid'     => $sellerId,
                    ':item_id' => ($itemId > 0 ? $itemId : null),
                    ':title'   => $title,
                    ':msg'     => $message
                ]);

                if ($reportId > 0) {
                    $uRep = $pdo->prepare("UPDATE reports SET status = 'Reviewed', admin_notes = :notes WHERE id = :id");
                    $uRep->execute([
                        ':notes' => "Official notice sent to seller: \"{$message}\"",
                        ':id'    => $reportId
                    ]);
                }

                set_flash('success', "Administrative message successfully delivered to student seller.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to send notification: ' . $e->getMessage());
            }
        }
        $repStatusParam = trim($_POST['redirect_status'] ?? '');
        $repSearchParam = trim($_POST['redirect_search'] ?? '');
        $rUrl = "admin.php?tab=reports";
        if (!empty($repStatusParam) && $repStatusParam !== 'all') $rUrl .= "&report_status=" . urlencode($repStatusParam);
        if (!empty($repSearchParam)) $rUrl .= "&report_search=" . urlencode($repSearchParam);
        header("Location: {$rUrl}");
        exit();
    }

    // H. Push Broadcast Notification (To all users and/or guests on index.php)
    elseif ($action === 'admin_push_broadcast') {
        $title    = trim($_POST['notice_title'] ?? '');
        $message  = trim($_POST['notice_message'] ?? '');
        $target   = trim($_POST['target_audience'] ?? 'everyone');
        $type     = in_array($_POST['alert_type'] ?? '', ['info', 'warning', 'success']) ? $_POST['alert_type'] : 'info';

        if (empty($title) || empty($message)) {
            set_flash('error', 'Notification title and message are required.');
        } else {
            try {
                // If targeting guests / homepage or everyone
                if ($target === 'guests_index' || $target === 'everyone') {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, item_id, title, message, target_audience, alert_type)
                        VALUES (NULL, NULL, :title, :msg, :target, :type)
                    ");
                    $stmt->execute([
                        ':title'  => $title,
                        ':msg'    => $message,
                        ':target' => $target,
                        ':type'   => $type
                    ]);
                }

                // If targeting all registered users or everyone
                if ($target === 'all_users' || $target === 'everyone') {
                    $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($users)) {
                        $uStmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, item_id, title, message, target_audience, alert_type)
                            VALUES (:uid, NULL, :title, :msg, :target, :type)
                        ");
                        foreach ($users as $uid) {
                            $uStmt->execute([
                                ':uid'    => $uid,
                                ':title'  => $title,
                                ':msg'    => $message,
                                ':target' => $target,
                                ':type'   => $type
                            ]);
                        }
                    }
                }

                $reachDesc = ($target === 'everyone') ? 'all registered students & homepage guests' : (($target === 'guests_index') ? 'homepage guests (index.php)' : 'all registered students');
                set_flash('success', "Broadcast notification successfully pushed to {$reachDesc}.");
            } catch (PDOException $e) {
                set_flash('error', 'Broadcast failed: ' . $e->getMessage());
            }
        }
        header("Location: admin.php");
        exit();
    }

    // I. Clear All Platform Notifications
    elseif ($action === 'admin_clear_all_notifications') {
        try {
            $pdo->exec("DELETE FROM notifications");
            set_flash('success', 'All system notifications and campus announcements have been permanently cleared.');
        } catch (PDOException $e) {
            set_flash('error', 'Failed to clear notifications: ' . $e->getMessage());
        }
        header("Location: admin.php");
        exit();
    }

    // J. Clear All Contact Inquiries & Messages
    elseif ($action === 'admin_clear_all_messages') {
        try {
            $pdo->exec("DELETE FROM contact_messages");
            set_flash('success', 'All student and visitor contact inquiries have been permanently cleared.');
        } catch (PDOException $e) {
            set_flash('error', 'Failed to clear messages: ' . $e->getMessage());
        }
        header("Location: admin.php?tab=messages");
        exit();
    }

    // K. Delete Single Contact Message
    elseif ($action === 'admin_delete_message') {
        $msgId = (int)($_POST['message_id'] ?? 0);
        if ($msgId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
                $stmt->execute([':id' => $msgId]);
                set_flash('success', "Message #{$msgId} deleted successfully.");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to delete message: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=messages");
        exit();
    }

    // L. Toggle Message Read Status
    elseif ($action === 'admin_toggle_message_status') {
        $msgId = (int)($_POST['message_id'] ?? 0);
        $newStatus = (($_POST['status'] ?? '') === 'read') ? 'read' : 'unread';
        if ($msgId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $msgId]);
                set_flash('success', "Message #{$msgId} marked as " . ucfirst($newStatus) . ".");
            } catch (PDOException $e) {
                set_flash('error', 'Failed to update message status: ' . $e->getMessage());
            }
        }
        header("Location: admin.php?tab=messages");
        exit();
    }
}

// =========================================================================
// 2. Fetch System Metrics & Analytics
// =========================================================================
$totalUsers = 0;
$totalSeuStudents = 0;
$totalOutsiders = 0;
$totalAdminUsers = 0;

$totalListings = 0;
$totalAvailable = 0;
$totalSold = 0;
$totalMoneySold = 0;
$totalSavingsOnSold = 0;
$totalActiveValue = 0;

$totalReports = 0;
$pendingReports = 0;
$reviewedReports = 0;
$ignoredReports = 0;

$totalMessages = 0;
$unreadMessages = 0;

$totalNotifications = 0;
$categoryCounts = [];

try {
    $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalSeuStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND (email LIKE '%@seu.edu.bd' OR email LIKE '%.seu.edu.bd')")->fetchColumn();
    $totalOutsiders = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE email NOT LIKE '%@seu.edu.bd' AND email NOT LIKE '%.seu.edu.bd'")->fetchColumn();
    $totalAdminUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

    $totalListings = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
    $totalAvailable = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Available'")->fetchColumn();
    $totalSold = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE status = 'Sold'")->fetchColumn();
    
    $totalReports = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    $pendingReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Pending'")->fetchColumn();
    $reviewedReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Reviewed'")->fetchColumn();
    $ignoredReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Ignored'")->fetchColumn();
    
    $totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $unreadMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

    $totalNotifications = (int)$pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    
    // Total money exchanged for items actually sold
    $totalMoneySold = (float)$pdo->query("SELECT COALESCE(SUM(selling_price), 0) FROM items WHERE status = 'Sold'")->fetchColumn();
    
    // Realized money saved by students on items actually sold
    $totalSavingsOnSold = (float)$pdo->query("SELECT COALESCE(SUM(original_price - selling_price), 0) FROM items WHERE status = 'Sold' AND original_price > selling_price")->fetchColumn();
    
    // Total inventory value of currently active listings
    $totalActiveValue = (float)$pdo->query("SELECT COALESCE(SUM(selling_price), 0) FROM items WHERE status = 'Available'")->fetchColumn();

    // Category distribution
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM items GROUP BY category");
    $categoryCounts = $stmt->fetchAll();

    // Department participation
    $stmt = $pdo->query("SELECT department, COUNT(*) as count FROM users GROUP BY department ORDER BY count DESC");
    $deptCounts = $stmt->fetchAll();

} catch (PDOException $e) {
    // Graceful error handle
}

// =========================================================================
// 3. Fetch Tab Data
// =========================================================================
$allListings = [];
$allUsers = [];
$allReports = [];
$allMessages = [];

try {
    if ($activeTab === 'listings') {
        if (!empty($searchQuery)) {
            $stmt = $pdo->prepare("
                SELECT i.*, u.full_name as seller_name, u.student_id as seller_sid, u.phone as seller_phone
                FROM items i
                JOIN users u ON i.user_id = u.id
                WHERE i.title LIKE :q OR i.course_code LIKE :q2 OR u.full_name LIKE :q3 OR u.student_id LIKE :q4
                ORDER BY i.id DESC
            ");
            $searchTerm = "%{$searchQuery}%";
            $stmt->execute([':q' => $searchTerm, ':q2' => $searchTerm, ':q3' => $searchTerm, ':q4' => $searchTerm]);
        } else {
            $stmt = $pdo->query("
                SELECT i.*, u.full_name as seller_name, u.student_id as seller_sid, u.phone as seller_phone
                FROM items i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.id DESC
            ");
        }
        $allListings = $stmt->fetchAll();
    } elseif ($activeTab === 'users') {
        $userWhere = [];
        $userParams = [];

        if ($userType === 'outsiders') {
            $userWhere[] = "(u.email NOT LIKE '%@seu.edu.bd' AND u.email NOT LIKE '%.seu.edu.bd')";
        } elseif ($userType === 'admins') {
            $userWhere[] = "u.role = 'admin'";
        } elseif ($userType === 'seu') {
            $userWhere[] = "((u.email LIKE '%@seu.edu.bd' OR u.email LIKE '%.seu.edu.bd') AND u.role = 'student')";
        }

        if (!empty($userSearch)) {
            $userWhere[] = "(u.full_name LIKE :usearch OR u.student_id LIKE :usearch OR u.email LIKE :usearch OR u.phone LIKE :usearch OR u.department LIKE :usearch)";
            $userParams[':usearch'] = "%{$userSearch}%";
        }

        $userWhereSql = !empty($userWhere) ? "WHERE " . implode(" AND ", $userWhere) : "";

        $stmt = $pdo->prepare("
            SELECT u.*, COUNT(i.id) as item_count 
            FROM users u
            LEFT JOIN items i ON u.id = i.user_id
            {$userWhereSql}
            GROUP BY u.id
            ORDER BY u.id DESC
        ");
        $stmt->execute($userParams);
        $allUsers = $stmt->fetchAll();
    } elseif ($activeTab === 'reports') {
        $repWhere = [];
        $repParams = [];

        if (in_array($reportStatus, ['pending', 'reviewed', 'ignored'])) {
            $repWhere[] = "r.status = :rstatus";
            $repParams[':rstatus'] = ucfirst($reportStatus);
        }

        if (!empty($reportSearch)) {
            $repWhere[] = "(i.title LIKE :rsearch OR seller.full_name LIKE :rsearch OR reporter.full_name LIKE :rsearch OR r.reason LIKE :rsearch OR r.details LIKE :rsearch OR seller.student_id LIKE :rsearch)";
            $repParams[':rsearch'] = "%{$reportSearch}%";
        }

        $repWhereSql = !empty($repWhere) ? "WHERE " . implode(" AND ", $repWhere) : "";

        $stmt = $pdo->prepare("
            SELECT r.*, 
                   i.title as item_title, i.selling_price as item_price, i.status as item_status, i.image_url as item_image, i.image_icon as item_icon,
                   seller.id as seller_id, seller.full_name as seller_name, seller.email as seller_email, seller.phone as seller_phone, seller.student_id as seller_sid, seller.department as seller_dept,
                   reporter.full_name as reporter_name, reporter.student_id as reporter_sid, reporter.email as reporter_email
            FROM reports r
            LEFT JOIN items i ON r.item_id = i.id
            LEFT JOIN users seller ON i.user_id = seller.id
            LEFT JOIN users reporter ON r.reporter_id = reporter.id
            {$repWhereSql}
            ORDER BY r.id DESC
        ");
        $stmt->execute($repParams);
        $allReports = $stmt->fetchAll();
    } elseif ($activeTab === 'messages') {
        $msgWhere = [];
        $msgParams = [];

        if ($messageStatus === 'unread' || $messageStatus === 'read') {
            $msgWhere[] = "status = :mstatus";
            $msgParams[':mstatus'] = $messageStatus;
        }

        if (!empty($messageSearch)) {
            $msgWhere[] = "(name LIKE :msearch OR email LIKE :msearch OR phone LIKE :msearch OR subject LIKE :msearch OR message LIKE :msearch)";
            $msgParams[':msearch'] = "%{$messageSearch}%";
        }

        $msgWhereSql = !empty($msgWhere) ? "WHERE " . implode(" AND ", $msgWhere) : "";

        $stmt = $pdo->prepare("SELECT * FROM contact_messages {$msgWhereSql} ORDER BY id DESC");
        $stmt->execute($msgParams);
        $allMessages = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Graceful error handle
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 3.5rem;">

    <!-- Admin Command Banner Header -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem 1.75rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: var(--warning-light); color: var(--warning); padding: 3px 10px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 800; margin-bottom: 0.5rem;">
                🛡️ System Administrator Mode
            </div>
            <h1 style="font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 0.25rem;">Administrator Command Center</h1>
            <p style="color: var(--text-secondary); font-size: 0.92rem; margin: 0;">Moderate campus listings, manage student accounts, and review marketplace cashflow</p>
        </div>
        <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('adminPushModal')">
                📢 Push Notification
            </button>
            <form method="POST" action="admin.php" style="display: inline;" onsubmit="return confirm('⚠️ Are you sure you want to permanently clear ALL notifications and campus announcements across the entire platform?');">
                <input type="hidden" name="action" value="admin_clear_all_notifications">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" title="Clear all active notifications">
                    🗑️ Clear All Notifications (<?= $totalNotifications ?>)
                </button>
            </form>
            <a href="marketplace.php" class="btn btn-outline btn-sm">
                🏪 Marketplace
            </a>
            <a href="my_listings.php" class="btn btn-outline btn-sm">
                ➕ Post Item
            </a>
        </div>
    </div>

    <!-- Platform KPI Overview Cards (Sold Money First, Then Item-Basis Savings) -->
    <div class="stats-grid" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?= format_price($totalMoneySold) ?></div>
            <div class="stat-label">Total Money Sold</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><?= $totalSold ?> items successfully sold</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎉</div>
            <div class="stat-value"><?= format_price($totalSavingsOnSold) ?></div>
            <div class="stat-label">Realized Savings (Sold)</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Money saved by buyers vs new retail</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏷️</div>
            <div class="stat-value"><?= format_price($totalActiveValue) ?></div>
            <div class="stat-label">Active Market Inventory</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><?= $totalAvailable ?> listings ready for pickup</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $totalListings ?></div>
            <div class="stat-label">Total Listings Posted</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Across all academic categories</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $totalUsers ?></div>
            <div class="stat-label">Registered Students</div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Verified university accounts</div>
        </div>
    </div>

    <!-- Navigation Tabs for Admin Panel -->
    <div style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 2rem; overflow-x: auto; padding-bottom: 2px;">
        <a href="admin.php?tab=listings" class="btn <?= $activeTab === 'listings' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            📋 Listings Moderation (<?= $totalListings ?>)
        </a>
        <a href="admin.php?tab=users" class="btn <?= $activeTab === 'users' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            👥 Student Accounts (<?= $totalUsers ?>)
        </a>
        <a href="admin.php?tab=analytics" class="btn <?= $activeTab === 'analytics' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
            📊 Category Analytics
        </a>
        <a href="admin.php?tab=reports" class="btn <?= $activeTab === 'reports' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0; position: relative;">
            🚩 Safety Reports (<?= $totalReports ?>)
            <?php if ($pendingReports > 0): ?>
                <span style="background: var(--danger); color: #fff; font-size: 0.7rem; padding: 2px 7px; border-radius: var(--radius-full); margin-left: 6px; font-weight: 800;"><?= $pendingReports ?> PENDING</span>
            <?php endif; ?>
        </a>
        <a href="admin.php?tab=messages" class="btn <?= $activeTab === 'messages' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: var(--radius-sm); border-bottom-left-radius: 0; border-bottom-right-radius: 0; position: relative;">
            ✉️ Inquiries &amp; Messages (<?= $totalMessages ?>)
            <?php if ($unreadMessages > 0): ?>
                <span style="background: var(--danger); color: #fff; font-size: 0.7rem; padding: 2px 7px; border-radius: var(--radius-full); margin-left: 6px; font-weight: 800;"><?= $unreadMessages ?> NEW</span>
            <?php endif; ?>
        </a>
    </div>

    <!-- =====================================================================
         TAB 1: LISTINGS MODERATION
         ===================================================================== -->
    <?php if ($activeTab === 'listings'): ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <h2 style="font-size: 1.3rem; font-weight: 800;">All Campus Listings (<?= count($allListings) ?>)</h2>
            
            <!-- Admin Search Filter Form -->
            <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem;">
                <input type="hidden" name="tab" value="listings">
                <input type="text" name="q" class="form-control" placeholder="Search by title, student ID, seller..." value="<?= htmlspecialchars($searchQuery) ?>" style="width: 280px; padding: 0.5rem 0.85rem;">
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="admin.php?tab=listings" class="btn btn-outline btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Listing Details</th>
                        <th>Seller / Student</th>
                        <th>Category</th>
                        <th>Pricing &amp; Savings</th>
                        <th>Status</th>
                        <th>Date Posted</th>
                        <th style="text-align: right;">Moderation Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allListings)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                                No listings found matching your search.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allListings as $item): ?>
                            <tr>
                                <td data-label="Listing ID" style="font-weight: 700; color: var(--text-muted);">#<?= (int)$item['id'] ?></td>
                                <td data-label="Listing Details">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php $imgSrc = get_item_image($item['image_url'] ?? null); ?>
                                        <?php if ($imgSrc): ?>
                                            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Photo" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;">
                                        <?php else: ?>
                                            <span style="font-size: 1.6rem; flex-shrink: 0;"><?= htmlspecialchars($item['image_icon'] ?? '📦') ?></span>
                                        <?php endif; ?>
                                        <div>
                                            <a href="item_details.php?id=<?= (int)$item['id'] ?>" target="_blank" style="font-weight: 700; color: var(--text-primary);">
                                                <?= htmlspecialchars($item['title']) ?> 🔗
                                            </a>
                                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                                Course: <?= htmlspecialchars($item['course_code'] ?: 'N/A') ?> &bull; Condition: <?= htmlspecialchars($item['item_condition']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Student Seller">
                                    <div style="font-weight: 600; font-size: 0.88rem;"><?= htmlspecialchars($item['seller_name']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">ID: <?= htmlspecialchars($item['seller_sid']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--accent);"><?= htmlspecialchars($item['seller_phone']) ?></div>
                                </td>
                                <td data-label="Category">
                                    <span class="card-badge-category" style="position: static; font-size: 0.72rem;"><?= htmlspecialchars($item['category']) ?></span>
                                </td>
                                <td data-label="Pricing &amp; Savings">
                                    <div style="font-weight: 800; color: var(--accent); font-size: 0.95rem;">
                                        <?= format_price($item['selling_price']) ?>
                                    </div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">
                                        New: <span style="text-decoration: line-through;"><?= format_price($item['original_price']) ?></span>
                                    </div>
                                    <?php 
                                        $savedAmount = max(0, (float)$item['original_price'] - (float)$item['selling_price']);
                                        $savedPct = calc_discount_pct($item['original_price'], $item['selling_price']);
                                    ?>
                                    <?php if ($savedAmount > 0): ?>
                                        <div style="font-size: 0.74rem; font-weight: 700; color: var(--success); margin-top: 2px;">
                                            💰 Saves <?= format_price($savedAmount) ?> (<?= $savedPct ?>%)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status">
                                    <form method="POST" action="admin.php" style="display: inline-block;">
                                        <input type="hidden" name="action" value="change_item_status">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="custom-select" style="font-size: 0.78rem; padding: 2px 6px;">
                                            <option value="Available" <?= $item['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Reserved" <?= $item['status'] === 'Reserved' ? 'selected' : '' ?>>Reserved</option>
                                            <option value="Sold" <?= $item['status'] === 'Sold' ? 'selected' : '' ?>>Sold</option>
                                        </select>
                                    </form>
                                </td>
                                <td data-label="Date Posted" style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                </td>
                                <td data-label="Actions" style="text-align: right;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                        <button type="button" class="btn btn-outline btn-sm" onclick='openAdminEditModal(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="admin.php" onsubmit="return confirm('Are you sure you want to permanently delete this listing from the marketplace as administrator?');" style="display: inline-block;">
                                            <input type="hidden" name="action" value="admin_delete_item">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Remove Prohibited/Spam Listing">
                                                🗑️ Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- =====================================================================
         TAB 2: USER MANAGEMENT
         ===================================================================== -->
    <?php elseif ($activeTab === 'users'): ?>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.25rem;">
                    Student Accounts &amp; Users (<?= count($allUsers) ?>)
                </h2>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 0;">
                    Search and inspect registered SEU students, external non-SEU outsiders, and system administrators.
                </p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('adminAddUserModal')">
                ➕ Add User (Any Email)
            </button>
        </div>

        <!-- Filter & Search Controls for Student Accounts -->
        <div style="background: var(--bg-surface); padding: 1.15rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.9rem;">
            
            <!-- Row 1: Segmented Filter Pills -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div class="filter-pills">
                    <a href="admin.php?tab=users&user_type=all<?= !empty($userSearch) ? '&user_search=' . urlencode($userSearch) : '' ?>" 
                       class="filter-pill <?= $userType === 'all' ? 'active' : '' ?>">
                        👥 All Accounts (<?= $totalUsers ?>)
                    </a>
                    <a href="admin.php?tab=users&user_type=seu<?= !empty($userSearch) ? '&user_search=' . urlencode($userSearch) : '' ?>" 
                       class="filter-pill <?= $userType === 'seu' ? 'active' : '' ?>">
                        🎓 SEU Students (<?= $totalSeuStudents ?>)
                    </a>
                    <a href="admin.php?tab=users&user_type=outsiders<?= !empty($userSearch) ? '&user_search=' . urlencode($userSearch) : '' ?>" 
                       class="filter-pill <?= $userType === 'outsiders' ? 'active' : '' ?>">
                        🌐 Outsiders / Non-SEU (<?= $totalOutsiders ?>)
                    </a>
                    <a href="admin.php?tab=users&user_type=admins<?= !empty($userSearch) ? '&user_search=' . urlencode($userSearch) : '' ?>" 
                       class="filter-pill <?= $userType === 'admins' ? 'active' : '' ?>">
                        🛡️ Administrators (<?= $totalAdminUsers ?>)
                    </a>
                </div>

                <?php if (!empty($userSearch) || $userType !== 'all'): ?>
                    <a href="admin.php?tab=users" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        ↺ Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Row 2: Live Search Input Form -->
            <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="users">
                <input type="hidden" name="user_type" value="<?= htmlspecialchars($userType) ?>">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="user_search" class="form-control" placeholder="Search by student ID, name, email address, phone, or department..." value="<?= htmlspecialchars($userSearch) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;">
                    🔍 Search
                </button>
                <?php if (!empty($userSearch)): ?>
                    <a href="admin.php?tab=users&user_type=<?= htmlspecialchars($userType) ?>" class="btn btn-outline btn-sm" style="white-space: nowrap;">
                        Clear
                    </a>
                <?php endif; ?>
            </form>

            <?php if (!empty($userSearch) || $userType !== 'all'): ?>
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Showing <strong><?= count($allUsers) ?></strong> matching account<?= count($allUsers) === 1 ? '' : 's' ?> 
                    <?php if ($userType !== 'all'): ?>
                        in <strong><?= $userType === 'outsiders' ? 'Outsiders (Non-SEU)' : ($userType === 'admins' ? 'Administrators' : 'SEU Students') ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($userSearch)): ?>
                        for search &ldquo;<em><?= htmlspecialchars($userSearch) ?></em>&rdquo;
                    <?php endif; ?>.
                </div>
            <?php endif; ?>

        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name &amp; Dept</th>
                        <th>Account Type</th>
                        <th>Email Address</th>
                        <th>Mobile / WhatsApp</th>
                        <th>Listings</th>
                        <th>Current Role</th>
                        <th>Joined Date</th>
                        <th style="text-align: right;">Admin Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allUsers)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔍</div>
                                <strong>No accounts found matching your filter criteria.</strong>
                                <div style="font-size: 0.85rem; margin-top: 4px;">Try searching for another keyword or switch category pill.</div>
                                <div style="margin-top: 1rem;">
                                    <a href="admin.php?tab=users" class="btn btn-outline btn-sm">View All Accounts</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allUsers as $u): ?>
                            <?php 
                                $uEmailLower = strtolower($u['email']);
                                $isSeu = str_ends_with($uEmailLower, '@seu.edu.bd') || str_ends_with($uEmailLower, '.seu.edu.bd');
                                $isAdmin = ($u['role'] === 'admin');
                            ?>
                            <tr>
                                <td data-label="Student ID">
                                    <strong><?= htmlspecialchars($u['student_id']) ?></strong>
                                </td>
                                <td data-label="Name &amp; Dept">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div class="user-avatar-sm" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700;"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($u['department']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Account Type">
                                    <?php if ($isAdmin): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(245, 158, 11, 0.15); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.35); font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-full); font-weight: 800;">
                                            🛡️ Admin
                                        </span>
                                    <?php elseif (!$isSeu): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(234, 88, 12, 0.12); color: #ea580c; border: 1px solid rgba(234, 88, 12, 0.35); font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-full); font-weight: 800;" title="External email authorized by Administrator">
                                            🌐 Outsider
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(59, 130, 246, 0.12); color: var(--primary); border: 1px solid rgba(59, 130, 246, 0.3); font-size: 0.72rem; padding: 2px 8px; border-radius: var(--radius-full); font-weight: 800;">
                                            🎓 SEU Student
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Email">
                                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>"><?= htmlspecialchars($u['email']) ?></a>
                                </td>
                                <td data-label="Mobile / WhatsApp">
                                    <strong><?= htmlspecialchars($u['phone']) ?></strong>
                                </td>
                                <td data-label="Listings">
                                    <span style="background: var(--bg-subtle); padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.82rem; font-weight: 700;">
                                        <?= (int)$u['item_count'] ?> items
                                    </span>
                                </td>
                                <td data-label="Current Role">
                                    <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span style="font-size: 0.8rem; background: var(--warning); color: #000; padding: 2px 8px; border-radius: var(--radius-sm); font-weight: 800;">
                                            ADMIN (You)
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="admin.php?tab=users" style="display: inline-block;">
                                            <input type="hidden" name="action" value="toggle_user_role">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="user_type" value="<?= htmlspecialchars($userType) ?>">
                                            <input type="hidden" name="user_search" value="<?= htmlspecialchars($userSearch) ?>">
                                            <select name="role" onchange="this.form.submit()" class="custom-select" style="font-size: 0.78rem; padding: 2px 6px;">
                                                <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Joined Date" style="font-size: 0.8rem; color: var(--text-muted);">
                                    <?= date('M d, Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td data-label="Actions" style="text-align: right;">
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" action="admin.php?tab=users" onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['full_name'])) ?>? This will delete all items posted by this student.');" style="display: inline-block;">
                                            <input type="hidden" name="action" value="admin_delete_user">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                            <input type="hidden" name="user_type" value="<?= htmlspecialchars($userType) ?>">
                                            <input type="hidden" name="user_search" value="<?= htmlspecialchars($userSearch) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                🗑️ Remove User
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">Active Session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- =====================================================================
         TAB 3: ANALYTICS & CATEGORY BREAKDOWN
         ===================================================================== -->
    <?php elseif ($activeTab === 'analytics'): ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem;">
            
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;">Marketplace Category Distribution</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($categoryCounts as $cat): ?>
                        <?php 
                            $pct = $totalListings > 0 ? round(($cat['count'] / $totalListings) * 100) : 0; 
                        ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.92rem; font-weight: 600; margin-bottom: 0.35rem;">
                                <span><?= htmlspecialchars($cat['category']) ?></span>
                                <span><?= (int)$cat['count'] ?> items (<?= $pct ?>%)</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-subtle); border-radius: var(--radius-full); overflow: hidden;">
                                <div style="width: <?= $pct ?>%; height: 100%; background: var(--primary); border-radius: var(--radius-full);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 1.25rem;">Student Department Participation</h3>
                
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($deptCounts as $dept): ?>
                        <?php 
                            $pct = $totalUsers > 0 ? round(($dept['count'] / $totalUsers) * 100) : 0; 
                        ?>
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.92rem; font-weight: 600; margin-bottom: 0.35rem;">
                                <span>Department of <?= htmlspecialchars($dept['department']) ?></span>
                                <span><?= (int)$dept['count'] ?> students (<?= $pct ?>%)</span>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--bg-subtle); border-radius: var(--radius-full); overflow: hidden;">
                                <div style="width: <?= $pct ?>%; height: 100%; background: var(--accent); border-radius: var(--radius-full);"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    <!-- =====================================================================
         TAB 4: SAFETY & LISTING REPORTS
         ===================================================================== -->
    <?php elseif ($activeTab === 'reports'): ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.25rem;">Campus Safety &amp; Listing Reports (<?= count($allReports) ?>)</h2>
                <p style="color: var(--text-secondary); font-size: 0.88rem; margin: 0;">Review flagged listings separately by Pending, Reviewed, and Ignored status, dismiss invalid claims, or warn student sellers directly.</p>
            </div>
            
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <span class="badge" style="background: var(--warning-light); color: var(--warning); padding: 5px 12px; font-size: 0.85rem; font-weight: 700; border-radius: var(--radius-full);">
                    ⏳ <?= $pendingReports ?> Pending Review
                </span>
                <span class="badge" style="background: var(--bg-surface); border: 1px solid var(--border-color); color: var(--text-muted); padding: 5px 12px; font-size: 0.85rem; font-weight: 700; border-radius: var(--radius-full);">
                    Total: <?= $totalReports ?>
                </span>
            </div>
        </div>

        <!-- Filter & Search Controls for Safety Reports -->
        <div style="background: var(--bg-surface); padding: 1.15rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.9rem;">
            
            <!-- Row 1: Separate Status Pills -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div class="filter-pills">
                    <a href="admin.php?tab=reports&report_status=all<?= !empty($reportSearch) ? '&report_search=' . urlencode($reportSearch) : '' ?>" 
                       class="filter-pill <?= $reportStatus === 'all' ? 'active' : '' ?>">
                        📋 All Reports (<?= $totalReports ?>)
                    </a>
                    <a href="admin.php?tab=reports&report_status=pending<?= !empty($reportSearch) ? '&report_search=' . urlencode($reportSearch) : '' ?>" 
                       class="filter-pill <?= $reportStatus === 'pending' ? 'active' : '' ?>" style="<?= $pendingReports > 0 ? 'font-weight: 800;' : '' ?>">
                        ⏳ Pending (<?= $pendingReports ?>)
                    </a>
                    <a href="admin.php?tab=reports&report_status=reviewed<?= !empty($reportSearch) ? '&report_search=' . urlencode($reportSearch) : '' ?>" 
                       class="filter-pill <?= $reportStatus === 'reviewed' ? 'active' : '' ?>">
                        👀 Reviewed (<?= $reviewedReports ?>)
                    </a>
                    <a href="admin.php?tab=reports&report_status=ignored<?= !empty($reportSearch) ? '&report_search=' . urlencode($reportSearch) : '' ?>" 
                       class="filter-pill <?= $reportStatus === 'ignored' ? 'active' : '' ?>">
                        ✓ Ignored (<?= $ignoredReports ?>)
                    </a>
                </div>

                <?php if (!empty($reportSearch) || $reportStatus !== 'all'): ?>
                    <a href="admin.php?tab=reports" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        ↺ Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Row 2: Live Search Form -->
            <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="report_status" value="<?= htmlspecialchars($reportStatus) ?>">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="report_search" class="form-control" placeholder="Search reported listing title, seller name, reporter, or reason..." value="<?= htmlspecialchars($reportSearch) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;">
                    🔍 Search Reports
                </button>
                <?php if (!empty($reportSearch)): ?>
                    <a href="admin.php?tab=reports&report_status=<?= htmlspecialchars($reportStatus) ?>" class="btn btn-outline btn-sm" style="white-space: nowrap;">
                        Clear
                    </a>
                <?php endif; ?>
            </form>

            <?php if (!empty($reportSearch) || $reportStatus !== 'all'): ?>
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Showing <strong><?= count($allReports) ?></strong> report<?= count($allReports) === 1 ? '' : 's' ?> 
                    <?php if ($reportStatus !== 'all'): ?>
                        filtered by status <strong><?= ucfirst($reportStatus) ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($reportSearch)): ?>
                        for keyword &ldquo;<em><?= htmlspecialchars($reportSearch) ?></em>&rdquo;
                    <?php endif; ?>.
                </div>
            <?php endif; ?>

        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Target Listing</th>
                        <th>Seller Details</th>
                        <th>Reported By</th>
                        <th>Reason &amp; Details</th>
                        <th>Status</th>
                        <th style="text-align: right;">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allReports)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">
                                    <?= $reportStatus === 'pending' ? '🎉' : ($reportStatus === 'ignored' ? '👁️' : ($reportStatus === 'reviewed' ? '👀' : '📋')) ?>
                                </div>
                                <strong>
                                    <?php if ($reportStatus === 'pending'): ?>
                                        All caught up! No pending reports awaiting moderation.
                                    <?php elseif ($reportStatus === 'reviewed'): ?>
                                        No reviewed reports on file.
                                    <?php elseif ($reportStatus === 'ignored'): ?>
                                        No ignored reports on file.
                                    <?php elseif ($reportStatus === 'resolved'): ?>
                                        No resolved reports on file.
                                    <?php else: ?>
                                        No safety reports found matching your criteria.
                                    <?php endif; ?>
                                </strong>
                                <div style="font-size: 0.85rem; margin-top: 4px;">
                                    <?= !empty($reportSearch) ? 'Try searching with another keyword or reset the filter.' : 'Campus marketplace listings are currently clean and compliant.' ?>
                                </div>
                                <?php if (!empty($reportSearch) || $reportStatus !== 'all'): ?>
                                    <div style="margin-top: 1rem;">
                                        <a href="admin.php?tab=reports" class="btn btn-outline btn-sm">View All Reports</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allReports as $rep): ?>
                            <tr>
                                <td data-label="Report ID">
                                    <div style="font-weight: 800; color: var(--text-primary);">#REP-<?= (int)$rep['id'] ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('M d, Y h:i A', strtotime($rep['created_at'])) ?></div>
                                </td>

                                <td data-label="Target Listing">
                                    <?php if (!empty($rep['item_title'])): ?>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php $repImg = get_item_image($rep['item_image'] ?? null); ?>
                                            <?php if ($repImg): ?>
                                                <img src="<?= htmlspecialchars($repImg) ?>" alt="Photo" style="width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; border: 1px solid var(--border-color); flex-shrink: 0;">
                                            <?php else: ?>
                                                <span style="font-size: 1.6rem; flex-shrink: 0;"><?= htmlspecialchars($rep['item_icon'] ?? '📦') ?></span>
                                            <?php endif; ?>
                                            <div>
                                                <a href="item_details.php?id=<?= (int)$rep['item_id'] ?>" target="_blank" style="font-weight: 700; color: var(--text-primary);">
                                                    <?= htmlspecialchars($rep['item_title']) ?> 🔗
                                                </a>
                                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                                    Price: <?= format_price($rep['item_price']) ?> &bull; 
                                                    <span class="badge" style="font-size: 0.7rem; padding: 1px 6px;"><?= htmlspecialchars($rep['item_status']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: var(--danger); font-style: italic; font-size: 0.9rem;">
                                            🗑️ Listing Removed (ID: #<?= (int)$rep['item_id'] ?>)
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Seller Details">
                                    <?php if (!empty($rep['seller_name'])): ?>
                                        <div style="font-weight: 700;"><?= htmlspecialchars($rep['seller_name']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">ID: <?= htmlspecialchars($rep['seller_sid'] ?? 'N/A') ?> &bull; <?= htmlspecialchars($rep['seller_dept'] ?? 'SEU') ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">📞 <?= htmlspecialchars($rep['seller_phone'] ?? 'N/A') ?></div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">Unknown / Removed</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Reported By">
                                    <?php if (!empty($rep['reporter_name'])): ?>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($rep['reporter_name']) ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);">ID: <?= htmlspecialchars($rep['reporter_sid'] ?? 'N/A') ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($rep['reporter_email'] ?? '') ?></div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">Anonymous / Guest</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Reason &amp; Details" style="max-width: 280px;">
                                    <div style="margin-bottom: 0.35rem;">
                                        <span style="display: inline-block; background: var(--danger-light); color: var(--danger); font-size: 0.75rem; font-weight: 800; padding: 2px 8px; border-radius: var(--radius-full);">
                                            <?= htmlspecialchars($rep['reason']) ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($rep['details'])): ?>
                                        <div style="font-size: 0.82rem; background: var(--bg-surface); padding: 0.5rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--text-secondary); line-height: 1.35; margin-bottom: 0.35rem;">
                                            &ldquo;<?= htmlspecialchars($rep['details']) ?>&rdquo;
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($rep['admin_notes'])): ?>
                                        <div style="font-size: 0.75rem; color: var(--primary); font-weight: 600;">
                                            🛡️ <em><?= htmlspecialchars($rep['admin_notes']) ?></em>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Status">
                                    <?php
                                        $st = $rep['status'];
                                        $badgeBg = 'var(--warning-light)';
                                        $badgeColor = 'var(--warning)';
                                        if ($st === 'Resolved') {
                                            $badgeBg = 'var(--success-light)';
                                            $badgeColor = 'var(--success)';
                                        } elseif ($st === 'Ignored') {
                                            $badgeBg = 'var(--bg-subtle)';
                                            $badgeColor = 'var(--text-muted)';
                                        } elseif ($st === 'Reviewed') {
                                            $badgeBg = 'var(--primary-light)';
                                            $badgeColor = 'var(--primary)';
                                        }
                                    ?>
                                    <span style="display: inline-block; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; font-size: 0.75rem; font-weight: 800; padding: 3px 10px; border-radius: var(--radius-full); text-transform: uppercase;">
                                        <?= htmlspecialchars($st) ?>
                                    </span>
                                </td>

                                <td data-label="Moderation Actions" style="text-align: right;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap: wrap;">
                                        
                                        <!-- Action 1: Notify Seller -->
                                        <?php if (!empty($rep['seller_id'])): ?>
                                            <button type="button" 
                                                    class="btn btn-outline btn-sm" 
                                                    title="Send Direct Administrative Warning to Seller"
                                                    onclick="openNotifySellerModal(<?= (int)$rep['id'] ?>, <?= (int)$rep['seller_id'] ?>, '<?= htmlspecialchars(addslashes($rep['seller_name']), ENT_QUOTES) ?>', <?= (int)$rep['item_id'] ?>, '<?= htmlspecialchars(addslashes($rep['item_title'] ?? 'Listing #' . $rep['item_id']), ENT_QUOTES) ?>')">
                                                ✉️ Notify
                                            </button>
                                        <?php endif; ?>

                                        <!-- Action 2: Remove Listing -->
                                        <?php if (!empty($rep['item_title'])): ?>
                                            <form method="POST" action="admin.php?tab=reports" style="display: inline;" onsubmit="return confirm('Take down and permanently delete this reported listing?');">
                                                <input type="hidden" name="action" value="remove_reported_listing">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="report_id" value="<?= (int)$rep['id'] ?>">
                                                <input type="hidden" name="item_id" value="<?= (int)$rep['item_id'] ?>">
                                                <input type="hidden" name="seller_id" value="<?= (int)($rep['seller_id'] ?? 0) ?>">
                                                <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($reportStatus) ?>">
                                                <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($reportSearch) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: var(--danger);" title="Remove listing permanently">
                                                    🗑️ Remove
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Action 3: Ignore Report -->
                                        <?php if ($rep['status'] !== 'Ignored'): ?>
                                            <form method="POST" action="admin.php?tab=reports" style="display: inline;">
                                                <input type="hidden" name="action" value="ignore_report">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="report_id" value="<?= (int)$rep['id'] ?>">
                                                <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($reportStatus) ?>">
                                                <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($reportSearch) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" style="color: var(--text-muted); border-color: var(--border-color);" title="Dismiss and Ignore report">
                                                    👁️ Ignore
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); padding: 4px 8px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm);">
                                                ✓ Ignored
                                            </span>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- =====================================================================
         TAB 5: CONTACT MESSAGES & INQUIRIES
         ===================================================================== -->
    <?php elseif ($activeTab === 'messages'): ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 0.2rem;">Contact Inquiries &amp; Support Messages (<?= count($allMessages) ?>)</h2>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin: 0;">Messages and queries submitted through the campus Contact Us page</p>
            </div>
            
            <?php if ($totalMessages > 0): ?>
                <form method="POST" action="admin.php?tab=messages" style="display: inline;" onsubmit="return confirm('⚠️ Are you sure you want to permanently clear ALL contact messages? This action cannot be reversed.');">
                    <input type="hidden" name="action" value="admin_clear_all_messages">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger); font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;" title="Permanently delete all contact messages">
                        🗑️ Clear All Messages (<?= $totalMessages ?>)
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Controls for Messages -->
        <div style="background: var(--bg-surface); padding: 1.15rem 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.9rem;">
            
            <!-- Row 1: Separate Status Pills -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div class="filter-pills">
                    <a href="admin.php?tab=messages&msg_status=all<?= !empty($messageSearch) ? '&msg_search=' . urlencode($messageSearch) : '' ?>" 
                       class="filter-pill <?= $messageStatus === 'all' ? 'active' : '' ?>">
                        📬 All Messages (<?= $totalMessages ?>)
                    </a>
                    <a href="admin.php?tab=messages&msg_status=unread<?= !empty($messageSearch) ? '&msg_search=' . urlencode($messageSearch) : '' ?>" 
                       class="filter-pill <?= $messageStatus === 'unread' ? 'active' : '' ?>" style="<?= $unreadMessages > 0 ? 'font-weight: 800;' : '' ?>">
                        📩 Unread (<?= $unreadMessages ?>)
                    </a>
                    <a href="admin.php?tab=messages&msg_status=read<?= !empty($messageSearch) ? '&msg_search=' . urlencode($messageSearch) : '' ?>" 
                       class="filter-pill <?= $messageStatus === 'read' ? 'active' : '' ?>">
                        📖 Read (<?= max(0, $totalMessages - $unreadMessages) ?>)
                    </a>
                </div>

                <?php if (!empty($messageSearch) || $messageStatus !== 'all'): ?>
                    <a href="admin.php?tab=messages" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">
                        ↺ Reset Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Row 2: Live Search Form -->
            <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="messages">
                <input type="hidden" name="msg_status" value="<?= htmlspecialchars($messageStatus) ?>">
                <div style="flex: 1; min-width: 260px;">
                    <input type="text" name="msg_search" class="form-control" placeholder="Search by name, email, phone, subject, or message content..." value="<?= htmlspecialchars($messageSearch) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; white-space: nowrap;">
                    🔍 Search Messages
                </button>
                <?php if (!empty($messageSearch)): ?>
                    <a href="admin.php?tab=messages&msg_status=<?= htmlspecialchars($messageStatus) ?>" class="btn btn-outline btn-sm" style="white-space: nowrap;">
                        Clear
                    </a>
                <?php endif; ?>
            </form>

            <?php if (!empty($messageSearch) || $messageStatus !== 'all'): ?>
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Showing <strong><?= count($allMessages) ?></strong> message<?= count($allMessages) === 1 ? '' : 's' ?> 
                    <?php if ($messageStatus !== 'all'): ?>
                        filtered by status <strong><?= ucfirst($messageStatus) ?></strong>
                    <?php endif; ?>
                    <?php if (!empty($messageSearch)): ?>
                        for keyword &ldquo;<em><?= htmlspecialchars($messageSearch) ?></em>&rdquo;
                    <?php endif; ?>.
                </div>
            <?php endif; ?>

        </div>

        <div class="table-responsive">
            <table class="custom-table responsive-card-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 110px;">Status</th>
                        <th style="width: 200px;">Sender</th>
                        <th>Subject &amp; Message</th>
                        <th style="width: 140px;">Received</th>
                        <th style="text-align: right; width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allMessages)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📭</div>
                                <div style="font-weight: 700; font-size: 1.1rem; color: var(--text-primary); margin-bottom: 0.25rem;">
                                    No contact messages found
                                </div>
                                <div style="font-size: 0.88rem;">
                                    <?php if (!empty($messageSearch) || $messageStatus !== 'all'): ?>
                                        Try adjusting your search query or status filter.
                                    <?php else: ?>
                                        Users and visitors haven't submitted any inquiries via the contact page yet.
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($allMessages as $msg): ?>
                            <tr style="<?= $msg['status'] === 'unread' ? 'background: rgba(37, 99, 235, 0.04);' : '' ?>">
                                <td data-label="ID">
                                    <span style="font-weight: 700; color: var(--text-muted);">#<?= (int)$msg['id'] ?></span>
                                </td>

                                <td data-label="Status">
                                    <?php if ($msg['status'] === 'unread'): ?>
                                        <span style="display: inline-block; background: var(--danger-light, #fee2e2); color: var(--danger, #dc2626); font-size: 0.72rem; font-weight: 800; padding: 3px 8px; border-radius: var(--radius-full); text-transform: uppercase; letter-spacing: 0.5px;">
                                            📩 UNREAD
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-block; background: var(--bg-subtle); color: var(--text-muted); font-size: 0.72rem; font-weight: 700; padding: 3px 8px; border-radius: var(--radius-full); text-transform: uppercase;">
                                            ✓ READ
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Sender">
                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.2rem;">
                                        <?= htmlspecialchars($msg['name']) ?>
                                    </div>
                                    <div style="font-size: 0.82rem; margin-bottom: 0.2rem;">
                                        <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color: var(--primary); text-decoration: none;">
                                            ✉️ <?= htmlspecialchars($msg['email']) ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($msg['phone'])): ?>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                                            📞 <?= htmlspecialchars($msg['phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Subject &amp; Message">
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 0.4rem;">
                                        <?= htmlspecialchars($msg['subject']) ?>
                                    </div>
                                    <div style="font-size: 0.85rem; background: var(--bg-surface); padding: 0.7rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--text-secondary); line-height: 1.45; white-space: pre-wrap; word-break: break-word;">
                                        <?= htmlspecialchars($msg['message']) ?>
                                    </div>
                                </td>

                                <td data-label="Received">
                                    <span style="font-size: 0.82rem; color: var(--text-secondary); font-weight: 600; display: block;">
                                        <?= date('M d, Y', strtotime($msg['created_at'])) ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                    </span>
                                </td>

                                <td data-label="Actions" style="text-align: right;">
                                    <div style="display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap: wrap;">
                                        
                                        <!-- Reply via Email -->
                                        <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=<?= urlencode('Re: ' . $msg['subject'] . ' - UniThrift Helpdesk') ?>" 
                                           class="btn btn-outline btn-sm" 
                                           title="Reply directly via Email">
                                            ✉️ Reply
                                        </a>

                                        <!-- Toggle Read/Unread -->
                                        <form method="POST" action="admin.php?tab=messages" style="display: inline;">
                                            <input type="hidden" name="action" value="admin_toggle_message_status">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                            <input type="hidden" name="status" value="<?= $msg['status'] === 'unread' ? 'read' : 'unread' ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" title="<?= $msg['status'] === 'unread' ? 'Mark message as read' : 'Mark message as unread' ?>">
                                                <?= $msg['status'] === 'unread' ? '✓ Mark Read' : '↺ Mark Unread' ?>
                                            </button>
                                        </form>

                                        <!-- Delete Message -->
                                        <form method="POST" action="admin.php?tab=messages" style="display: inline;" onsubmit="return confirm('Delete this message permanently?');">
                                            <input type="hidden" name="action" value="admin_delete_message">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: var(--danger);" title="Delete message">
                                                🗑️
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</div>

<!-- Admin Edit Listing Modal -->
<div class="modal-overlay" id="adminEditModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">✏️ Admin Edit Listing</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="admin.php?tab=listings" id="adminEditForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="admin_update_item">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="item_id" id="adminEditItemId" value="">

            <div class="form-group">
                <label class="form-label">Item Title *</label>
                <input type="text" name="title" id="adminEditTitle" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Product Photo (Optional, leave empty to keep current)</label>
                <div id="adminEditImagePreview" style="margin-bottom: 0.5rem; display: none;"></div>
                <input type="file" name="item_image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" id="adminEditCategory" class="form-control" required>
                        <option value="Textbooks">📚 Textbooks</option>
                        <option value="Lab Gear & Kits">🔬 Lab Gear &amp; Kits</option>
                        <option value="Drawing & Tools">📐 Drawing &amp; Tools</option>
                        <option value="Electronics & Calculators">🔢 Electronics &amp; Calculators</option>
                        <option value="Other">📦 Other Academic Supplies</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="course_code" id="adminEditCourseCode" class="form-control">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Condition *</label>
                    <select name="item_condition" id="adminEditCondition" class="form-control" required>
                        <option value="Like New">Like New</option>
                        <option value="Gently Used">Gently Used</option>
                        <option value="Fair">Fair</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" id="adminEditStatus" class="form-control" required>
                        <option value="Available">Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Display Icon</label>
                    <select name="image_icon" id="adminEditIcon" class="form-control">
                        <option value="📚">📚 Textbook / Notes</option>
                        <option value="🔬">🔬 Lab Equipment</option>
                        <option value="🔢">🔢 Calculator / Hardware</option>
                        <option value="📐">📐 Drawing / Architecture</option>
                        <option value="⚡">⚡ Circuit / Electronics</option>
                        <option value="💻">💻 Computer / Adapter</option>
                        <option value="💡">💡 IC / Components</option>
                        <option value="📦">📦 General Academic Gear</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Campus Meetup Location *</label>
                    <input type="text" name="meetup_location" id="adminEditMeetup" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Original Retail Price (BDT) *</label>
                    <input type="number" step="1" name="original_price" id="adminEditOrigPrice" class="form-control calc-orig-price" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Resale Price (BDT) *</label>
                    <input type="number" step="1" name="selling_price" id="adminEditSellPrice" class="form-control calc-sell-price" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <span class="calc-discount-badge" style="display: none; padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; background: var(--accent-light); color: var(--accent);"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Description *</label>
                <textarea name="description" id="adminEditDesc" class="form-control" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                💾 Save Administrative Changes
            </button>
        </form>
    </div>
</div>

<script>
function openAdminEditModal(item) {
    document.getElementById('adminEditItemId').value = item.id;
    document.getElementById('adminEditTitle').value = item.title;
    document.getElementById('adminEditCategory').value = item.category;
    document.getElementById('adminEditCourseCode').value = item.course_code || '';
    document.getElementById('adminEditCondition').value = item.item_condition;
    document.getElementById('adminEditStatus').value = item.status;
    document.getElementById('adminEditIcon').value = item.image_icon || '📦';
    document.getElementById('adminEditMeetup').value = item.meetup_location;
    document.getElementById('adminEditOrigPrice').value = item.original_price;
    document.getElementById('adminEditSellPrice').value = item.selling_price;
    document.getElementById('adminEditDesc').value = item.description;

    const previewContainer = document.getElementById('adminEditImagePreview');
    if (item.image_url) {
        previewContainer.innerHTML = `<div style="display: flex; align-items: center; gap: 0.75rem;"><img src="${item.image_url}" alt="Current Photo" style="width: 50px; height: 50px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); object-fit: cover;"> <span style="font-size: 0.8rem; color: var(--text-muted);">Current attached photo</span></div>`;
        previewContainer.style.display = 'block';
    } else {
        previewContainer.innerHTML = '';
        previewContainer.style.display = 'none';
    }

    // Trigger price calculator badge
    const editForm = document.getElementById('adminEditForm');
    const orig = editForm.querySelector('.calc-orig-price');
    if (orig) {
        orig.dispatchEvent(new Event('input'));
    }

    openModal('adminEditModal');
}

function openNotifySellerModal(reportId, sellerId, sellerName, itemId, itemTitle) {
    document.getElementById('notifyReportId').value = reportId || 0;
    document.getElementById('notifySellerId').value = sellerId || 0;
    document.getElementById('notifyItemId').value = itemId || 0;
    document.getElementById('notifySellerName').textContent = sellerName || 'Student';
    document.getElementById('notifyItemTitle').textContent = itemTitle || 'General Listing';
    document.getElementById('notifyNoticeTitle').value = 'Administrative Notice: ' + (itemTitle || '');
    document.getElementById('notifyCustomMessage').value = '';

    openModal('notifySellerModal');
}
</script>

<!-- Admin Notify Seller Modal -->
<div class="modal-overlay" id="notifySellerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">✉️ Notify Student Seller</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="admin.php?tab=reports" id="notifySellerForm">
            <input type="hidden" name="action" value="notify_seller">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="report_id" id="notifyReportId" value="0">
            <input type="hidden" name="seller_id" id="notifySellerId" value="0">
            <input type="hidden" name="item_id" id="notifyItemId" value="0">
            <input type="hidden" name="redirect_status" value="<?= htmlspecialchars($reportStatus) ?>">
            <input type="hidden" name="redirect_search" value="<?= htmlspecialchars($reportSearch) ?>">

            <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 1rem; font-size: 0.88rem;">
                <div><strong>Recipient:</strong> <span id="notifySellerName">-</span></div>
                <div style="margin-top: 4px;"><strong>Listing Context:</strong> <span id="notifyItemTitle">-</span></div>
            </div>

            <div class="form-group">
                <label class="form-label">Notice Subject *</label>
                <input type="text" name="notice_title" id="notifyNoticeTitle" class="form-control" value="Administrative Notice Regarding Your Listing" required>
            </div>

            <div class="form-group">
                <label class="form-label">Custom Warning / Message *</label>
                <textarea name="custom_message" id="notifyCustomMessage" class="form-control" rows="4" placeholder="Enter administrative warning or clarification instructions..." required></textarea>
                <small style="color: var(--text-muted); font-size: 0.78rem;">This message will appear directly on the student seller's dashboard and notification bell.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                📨 Deliver Administrative Notice
            </button>
        </form>
    </div>
</div>

<!-- Admin Provision User Modal (Allows Any Email) -->
<div class="modal-overlay" id="adminAddUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">➕ Add User Account (Admin Authorization)</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="admin.php?tab=users" id="adminAddUserForm">
            <input type="hidden" name="action" value="admin_create_user">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                🛡️ <strong>Admin Privilege:</strong> While standard student self-registration is strictly restricted to official <code>@seu.edu.bd</code> emails, administrators can provision verified accounts with <strong>any email domain</strong> (e.g., Gmail, Yahoo, guest faculty, inter-university partners).
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Student / University ID *</label>
                    <input type="text" name="student_id" class="form-control" placeholder="e.g. 2021100000888 or EXT-101" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. John Doe or Jane Smith" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address (Any Domain Permitted) *</label>
                <input type="email" name="email" class="form-control" placeholder="e.g. scholar@gmail.com or student@seu.edu.bd" required>
                <small style="color: var(--text-muted); font-size: 0.78rem;">You may enter non-SEU emails (e.g. Gmail) or standard SEU emails here.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control" required>
                        <option value="CSE">CSE</option>
                        <option value="EEE">EEE</option>
                        <option value="Architecture">Architecture</option>
                        <option value="BBA">BBA</option>
                        <option value="Pharmacy">Pharmacy</option>
                        <option value="English">English</option>
                        <option value="Law">Law</option>
                        <option value="General">General / External</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" class="form-control" required>
                        <option value="student">Student / Regular User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone / WhatsApp Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="01700000000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Initial Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.75rem;">
                💾 Provision &amp; Activate Account
            </button>
        </form>
    </div>
</div>

<!-- Admin Push Notification Modal -->
<div class="modal-overlay" id="adminPushModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">📢 Push Broadcast Notification</h2>
            <button type="button" class="modal-close-btn" data-close-modal>&times;</button>
        </div>

        <form method="POST" action="admin.php" id="adminPushForm">
            <input type="hidden" name="action" value="admin_push_broadcast">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 1.25rem; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                📣 <strong>Broadcast Reach:</strong> Send official notifications to all registered students (appears in notification bell &amp; studio) and/or broadcast live announcements to guests and campus visitors on the <strong>index.php</strong> homepage.
            </div>

            <div class="form-group">
                <label class="form-label">Target Audience *</label>
                <select name="target_audience" class="form-control" required>
                    <option value="everyone">🌐 Everywhere (All Students + Guests on Homepage)</option>
                    <option value="guests_index">🏠 Guests &amp; Visitors on Homepage (index.php)</option>
                    <option value="all_users">👥 All Registered Students (In-App Bells &amp; Studio)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Notification Title / Headline *</label>
                <input type="text" name="notice_title" class="form-control" placeholder="e.g. End of Semester Book Exchange Fair: Next Tuesday" required>
            </div>

            <div class="form-group">
                <label class="form-label">Notice Message *</label>
                <textarea name="notice_message" class="form-control" rows="4" placeholder="Enter announcement details, date, location, or safety instructions..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Notice Style</label>
                <select name="alert_type" class="form-control">
                    <option value="info">🔵 Informational (Blue)</option>
                    <option value="warning">🟠 Urgent / Notice (Orange)</option>
                    <option value="success">🟢 Special Event / Good News (Green)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                🚀 Broadcast Notification Now
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
