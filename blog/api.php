<?php
/**
 * Tulima Solar - Blog API Endpoints
 * Handles AJAX requests for likes, comments, and comment retrieval
 */

session_start();

// Set JSON content type
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/path.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

// CORS headers for development (restrict in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get database connection
try {
    $pdo = getDb();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get action from request
$action = $_REQUEST['action'] ?? '';

// CSRF token validation for POST requests
function validateCsrfToken(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate new CSRF token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Helper function to send JSON response and exit
 */
function jsonResponse(array $data): void {
    echo json_encode($data);
    exit;
}

/**
 * Helper function to validate and sanitize comment content
 */
function sanitizeCommentContent(string $content): string {
    // Allow basic HTML tags only
    $allowedTags = '<p><br><strong><b><em><i><u><a>';
    $content = strip_tags($content, $allowedTags);
    
    // Remove any potential XSS
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    // Trim whitespace
    $content = trim($content);
    
    return $content;
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Route to appropriate handler based on action
switch ($action) {
    case 'like':
        handleLike();
        break;
    
    case 'comment':
        handleComment();
        break;
    
    case 'comments':
        handleGetComments();
        break;
    
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Handle like/unlike toggle for a post
 * POST /like
 * Input: post_id, csrf_token
 * Output: {success, liked, like_count, message}
 */
function handleLike(): void {
    global $pdo;
    
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed']);
    }
    
    // Validate CSRF token
    if (!validateCsrfToken()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    }
    
    // Check if user is logged in
    $userId = $_SESSION['admin_id'] ?? null;
    if (!$userId) {
        jsonResponse(['success' => false, 'message' => 'You must be logged in to like this post.']);
    }
    
    // Get and validate post_id
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    if (!$postId || $postId < 1) {
        jsonResponse(['success' => false, 'message' => 'Invalid post ID.']);
    }
    
    try {
        // Verify post exists and is published
        $postStmt = $pdo->prepare('SELECT id, title, created_by, slug FROM blog_posts WHERE id = ? AND status = \'published\' AND deleted_at IS NULL');
        $postStmt->execute([$postId]);
        $post = $postStmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found.']);
        }
        
        // Check if user already liked this post
        $likeStmt = $pdo->prepare('SELECT id FROM blog_post_likes WHERE post_id = ? AND user_id = ?');
        $likeStmt->execute([$postId, $userId]);
        $existingLike = $likeStmt->fetch();
        
        if ($existingLike) {
            // Unlike: remove the like
            $deleteStmt = $pdo->prepare('DELETE FROM blog_post_likes WHERE post_id = ? AND user_id = ?');
            $deleteStmt->execute([$postId, $userId]);
            $liked = false;
        } else {
            // Like: add new like
            $insertStmt = $pdo->prepare('INSERT INTO blog_post_likes (post_id, user_id) VALUES (?, ?)');
            $insertStmt->execute([$postId, $userId]);
            $liked = true;
        }
        
        // Get updated like count (from triggers or direct query)
        $countStmt = $pdo->prepare('SELECT like_count FROM blog_posts WHERE id = ?');
        $countStmt->execute([$postId]);
        $likeRow = $countStmt->fetch(PDO::FETCH_ASSOC);
        $likeCount = $likeRow ? (int)$likeRow['like_count'] : 0;

        // Notify post author about new like via email (only on like, not unlike)
        if ($liked && !empty($post['created_by'])) {
            try {
                $authorStmt = $pdo->prepare('SELECT email, username FROM admins WHERE id = ? LIMIT 1');
                $authorStmt->execute([(int)$post['created_by']]);
                $author = $authorStmt->fetch(PDO::FETCH_ASSOC);

                if ($author && !empty($author['email']) && filter_var($author['email'], FILTER_VALIDATE_EMAIL)) {
                    $cfg = getSmtpSettings($pdo);
                    if ($cfg['enabled']) {
                    $subject = 'Your blog post received a new reaction';
                    $postUrl = rtrim(BASE_PATH, '/') . '/blog/post.php?slug=' . urlencode($post['slug']);

                        $htmlBody = '<p>Hi ' . e($author['username'] ?? 'Author') . ',</p>'
                            . '<p>Your post <strong>' . e($post['title']) . '</strong> just received a new reaction.</p>'
                            . '<p>Total reactions: <strong>' . $likeCount . '</strong></p>'
                            . '<p>You can view the post here: <a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

                        $textBody = "Your post '{$post['title']}' received a new reaction.\n"
                            . "Total reactions: {$likeCount}\n"
                            . "View post: {$postUrl}\n";

                        $res = sendSmtpEmail($pdo, $author['email'], $subject, $htmlBody, $textBody);
                        if (!$res['success']) {
                            error_log('Blog like notification email error: ' . $res['error']);
                        }
                    }
                }
            } catch (Throwable $mailEx) {
                error_log('Blog like mailer error: ' . $mailEx->getMessage());
            }
        }
        
        jsonResponse([
            'success' => true,
            'liked' => $liked,
            'like_count' => $likeCount,
            'message' => $liked ? 'Post liked!' : 'Post unliked'
        ]);
        
    } catch (PDOException $e) {
        error_log('Like error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}

/**
 * Handle comment submission
 * POST /comment
 * Input: post_id, parent_id (nullable), content, guest_name, guest_email, csrf_token
 * Output: {success, comment_id, message, moderation}
 */
function handleComment(): void {
    global $pdo;
    
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed']);
    }
    
    // Validate CSRF token
    if (!validateCsrfToken()) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
    }
    
    // Get and validate post_id
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    if (!$postId || $postId < 1) {
        jsonResponse(['success' => false, 'message' => 'Invalid post ID.']);
    }
    
    // Get and validate parent_id (for replies)
    $parentId = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
    if ($parentId === false || $parentId === 0) {
        $parentId = null;
    }
    
    // Validate parent comment exists if provided
    if ($parentId !== null) {
        $parentStmt = $pdo->prepare('SELECT id, post_id FROM blog_comments WHERE id = ? AND status = \'approved\'');
        $parentStmt->execute([$parentId]);
        $parentComment = $parentStmt->fetch();
        
        if (!$parentComment || $parentComment['post_id'] != $postId) {
            jsonResponse(['success' => false, 'message' => 'Invalid parent comment.']);
        }
    }
    
    // Get and validate content
    $content = $_POST['content'] ?? '';
    $content = sanitizeCommentContent($content);
    
    if (strlen($content) < 3) {
        jsonResponse(['success' => false, 'message' => 'Comment must be at least 3 characters.']);
    }
    
    if (strlen($content) > 2000) {
        jsonResponse(['success' => false, 'message' => 'Comment cannot exceed 2000 characters.']);
    }
    
    // Check if user is logged in or get guest info
    $userId = $_SESSION['admin_id'] ?? null;
    $guestName = null;
    $guestEmail = null;
    
    if (!$userId) {
        // Guest comment - require name and email
        $guestName = trim($_POST['guest_name'] ?? '');
        $guestEmail = trim($_POST['guest_email'] ?? '');
        
        if (strlen($guestName) < 2 || strlen($guestName) > 100) {
            jsonResponse(['success' => false, 'message' => 'Name must be between 2 and 100 characters.']);
        }
        
        if (!isValidEmail($guestEmail)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.']);
        }
        
        // Sanitize guest info
        $guestName = htmlspecialchars($guestName, ENT_QUOTES, 'UTF-8');
        $guestEmail = filter_var($guestEmail, FILTER_SANITIZE_EMAIL);
    }
    
    try {
        // Verify post exists and is published
        $postStmt = $pdo->prepare('SELECT id, title, created_by, slug FROM blog_posts WHERE id = ? AND status = \'published\' AND deleted_at IS NULL');
        $postStmt->execute([$postId]);
        $post = $postStmt->fetch(PDO::FETCH_ASSOC);
        if (!$post) {
            jsonResponse(['success' => false, 'message' => 'Post not found.']);
        }
        
        // Get IP address and user agent for spam detection
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Insert comment with 'pending' status for moderation
        $insertStmt = $pdo->prepare('
            INSERT INTO blog_comments 
            (post_id, parent_id, user_id, author_name, author_email, content, status, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, \'pending\', ?, ?)
        ');
        
        $insertStmt->execute([
            $postId,
            $parentId,
            $userId,
            $guestName,
            $guestEmail,
            $content,
            $ipAddress,
            $userAgent
        ]);
        
        $commentId = (int)$pdo->lastInsertId();

        // Email notifications: inform post author (and parent comment author for replies)
        try {
            $cfg = getSmtpSettings($pdo);
            if ($cfg['enabled']) {
                // Notify post author
                if (!empty($post['created_by'])) {
                    $authorStmt = $pdo->prepare('SELECT email, username FROM admins WHERE id = ? LIMIT 1');
                    $authorStmt->execute([(int)$post['created_by']]);
                    $author = $authorStmt->fetch(PDO::FETCH_ASSOC);
                    if ($author && !empty($author['email']) && isValidEmail($author['email'])) {
                        $subject = 'New comment on your blog post';
                        $postUrl = rtrim(BASE_PATH, '/') . '/blog/post.php?slug=' . urlencode($post['slug']);

                        $htmlBody = '<p>Hi ' . e($author['username'] ?? 'Author') . ',</p>'
                            . '<p>A new comment was submitted on your post <strong>' . e($post['title']) . '</strong>.</p>'
                            . '<p><strong>Comment preview:</strong></p>'
                            . '<p>' . nl2br(e(mb_substr($content, 0, 300))) . '</p>'
                            . '<p>You can review and approve it in the admin panel or view the post here: '
                            . '<a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

                        $textBody = "A new comment was submitted on your post '{$post['title']}'.\n\n"
                            . "Comment preview:\n"
                            . mb_substr($content, 0, 300) . "\n\n"
                            . "View post: {$postUrl}\n";

                        $res = sendSmtpEmail($pdo, $author['email'], $subject, $htmlBody, $textBody);
                        if (!$res['success']) {
                            error_log('Blog comment notification email error: ' . $res['error']);
                        }
                    }
                }

                // Notify parent comment author (for replies)
                if ($parentId !== null) {
                    $parentStmt = $pdo->prepare('SELECT author_email, user_id FROM blog_comments WHERE id = ? LIMIT 1');
                    $parentStmt->execute([$parentId]);
                    $parent = $parentStmt->fetch(PDO::FETCH_ASSOC);
                    $replyRecipient = null;
                    $replyName = null;

                    if ($parent) {
                        if (!empty($parent['user_id'])) {
                            $userStmt = $pdo->prepare('SELECT email, username FROM admins WHERE id = ? LIMIT 1');
                            $userStmt->execute([(int)$parent['user_id']]);
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                            if ($user && !empty($user['email']) && isValidEmail($user['email'])) {
                                $replyRecipient = $user['email'];
                                $replyName = $user['username'] ?? 'User';
                            }
                        } elseif (!empty($parent['author_email']) && isValidEmail($parent['author_email'])) {
                            $replyRecipient = $parent['author_email'];
                            $replyName = 'Reader';
                        }
                    }

                    if ($replyRecipient && $replyRecipient !== ($author['email'] ?? null)) {
                        $subject = 'Someone replied to your comment';
                        $postUrl = rtrim(BASE_PATH, '/') . '/blog/post.php?slug=' . urlencode($post['slug']);

                        $htmlBody = '<p>Hi ' . e($replyName) . ',</p>'
                            . '<p>Your comment on the post <strong>' . e($post['title']) . '</strong> has received a reply.</p>'
                            . '<p><strong>Reply preview:</strong></p>'
                            . '<p>' . nl2br(e(mb_substr($content, 0, 300))) . '</p>'
                            . '<p>You can view the discussion here: '
                            . '<a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';

                        $textBody = "Your comment on '{$post['title']}' has a new reply.\n\n"
                            . "Reply preview:\n"
                            . mb_substr($content, 0, 300) . "\n\n"
                            . "View post: {$postUrl}\n";

                        $res = sendSmtpEmail($pdo, $replyRecipient, $subject, $htmlBody, $textBody);
                        if (!$res['success']) {
                            error_log('Blog comment reply notification email error: ' . $res['error']);
                        }
                    }
                }
            }
        } catch (Throwable $mailEx) {
            error_log('Blog comment mailer error: ' . $mailEx->getMessage());
        }

        jsonResponse([
            'success' => true,
            'comment_id' => $commentId,
            'message' => 'Your comment has been submitted successfully.',
            'moderation' => true  // Comments require moderation
        ]);
        
    } catch (PDOException $e) {
        error_log('Comment error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'An error occurred while submitting your comment. Please try again.']);
    }
}

/**
 * Handle get comments for a post
 * GET /comments
 * Input: post_id, page (optional, default 1)
 * Output: {success, comments: [...], total, pages, current_page}
 */
function handleGetComments(): void {
    global $pdo;
    
    // Only accept GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonResponse(['success' => false, 'message' => 'Method not allowed']);
    }
    
    // Get and validate post_id
    $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
    if (!$postId || $postId < 1) {
        jsonResponse(['success' => false, 'message' => 'Invalid post ID.']);
    }
    
    // Get page number
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $page = max(1, $page);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    try {
        // Get total approved comments for pagination
        $countStmt = $pdo->prepare('SELECT COUNT(*) as total FROM blog_comments WHERE post_id = ? AND status = \'approved\'');
        $countStmt->execute([$postId]);
        $total = (int)$countStmt->fetch()['total'];
        $totalPages = max(1, ceil($total / $perPage));
        
        // Get comments with pagination
        $commentsStmt = $pdo->prepare('
            SELECT c.id, c.parent_id, c.content, c.created_at,
                   c.author_name as guest_name, c.author_email as guest_email,
                   a.username as admin_username, a.id as user_id
            FROM blog_comments c
            LEFT JOIN admins a ON c.user_id = a.id
            WHERE c.post_id = ? AND c.status = \'approved\'
            ORDER BY c.created_at ASC
            LIMIT ' . $offset . ', ' . $perPage
        );
        $commentsStmt->execute([$postId]);
        $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format comments for output
        $formattedComments = array_map(function($comment) {
            return [
                'id' => (int)$comment['id'],
                'parent_id' => $comment['parent_id'] ? (int)$comment['parent_id'] : null,
                'content' => $comment['content'],
                'created_at' => $comment['created_at'],
                'author' => [
                    'name' => $comment['admin_username'] ?? $comment['guest_name'] ?? 'Anonymous',
                    'is_staff' => !empty($comment['user_id'])
                ]
            ];
        }, $comments);
        
        jsonResponse([
            'success' => true,
            'comments' => $formattedComments,
            'total' => $total,
            'pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $perPage
        ]);
        
    } catch (PDOException $e) {
        error_log('Get comments error: ' . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'An error occurred while fetching comments.']);
    }
}
