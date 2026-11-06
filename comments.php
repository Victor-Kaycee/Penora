<?php
require_once 'includes/dbh.inc.php';
require_once 'includes/config_session.inc.php';

header('Content-Type: application/json; charset=utf-8');

/* ============================
   AUTH
   ============================ */
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Please log in to comment."]);
    exit;
}

/* ============================
   INPUT
   ============================ */
$user_id   = (int) $_SESSION['user_id'];
$post_id   = filter_input(INPUT_POST, 'post_id',   FILTER_VALIDATE_INT);
$parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT); // optional
$comment   = trim($_POST['comment'] ?? '');

/* ============================
   VALIDATE
   ============================ */
if (!$post_id) {
    echo json_encode(["error" => "No post selected."]);
    exit;
}
if ($comment === '') {
    echo json_encode(["error" => "Comment cannot be empty."]);
    exit;
}
if (mb_strlen($comment) > 1000) {
    echo json_encode(["error" => "Comment is too long (max 1000)."]);
    exit;
}

/* Rate limit (basic — 1 message / 4s) */
if (isset($_SESSION['last_comment_at']) && (time() - $_SESSION['last_comment_at']) < 4) {
    echo json_encode(["error" => "You're posting too fast. Please slow down."]);
    exit;
}

try {
    /* Ensure post exists and get owner */
    $ownerStmt = $pdo->prepare("SELECT users_id FROM posts WHERE id = :id");
    $ownerStmt->execute(['id' => $post_id]);
    $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$owner) {
        echo json_encode(["error" => "Post not found."]);
        exit;
    }

    /* If replying, ensure parent exists and belongs to the same post */
    $parentAuthor = null;
    if ($parent_id) {
        $ps = $pdo->prepare("SELECT user_id FROM comments WHERE id = :id AND post_id = :p");
        $ps->execute(['id' => $parent_id, 'p' => $post_id]);
        $row = $ps->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo json_encode(["error" => "Reply target not found."]);
            exit;
        }
        $parentAuthor = (int) $row['user_id'];
    } else {
        $parent_id = null;
    }

    /* Insert */
    $insert = $pdo->prepare("
        INSERT INTO comments (post_id, parent_id, user_id, comment, created_at)
        VALUES (:post_id, :parent_id, :user_id, :comment, NOW())
    ");
    $insert->execute([
        'post_id'   => $post_id,
        'parent_id' => $parent_id,
        'user_id'   => $user_id,
        'comment'   => $comment,
    ]);
    $newId = (int) $pdo->lastInsertId();

    /* Notify */
    $ownerId = (int) $owner['users_id'];
    $notifyTarget = $parentAuthor ?: $ownerId;
    if ($notifyTarget !== $user_id) {
        $type    = $parent_id ? 'reply'   : 'comment';
        $message = $parent_id ? 'replied to your comment' : 'commented on your post';
        $notify = $pdo->prepare("
            INSERT INTO notifications (user_id, sender_id, post_id, type, message, created_at)
            VALUES (:user_id, :sender_id, :post_id, :type, :message, NOW())
        ");
        $notify->execute([
            'user_id'   => $notifyTarget,
            'sender_id' => $user_id,
            'post_id'   => $post_id,
            'type'      => $type,
            'message'   => $message,
        ]);
    }

    /* User info for render */
    $u = $pdo->prepare("SELECT firstname, lastname, file_name FROM users WHERE id = :id");
    $u->execute(['id' => $user_id]);
    $user = $u->fetch(PDO::FETCH_ASSOC);

    $_SESSION['last_comment_at'] = time();

    echo json_encode([
        "success"   => true,
        "id"        => $newId,
        "parent_id" => $parent_id,
        "firstname" => $user['firstname'] ?? '',
        "lastname"  => $user['lastname']  ?? '',
        "file_name" => $user['file_name'] ?: 'default.png',
        "comment"   => $comment,
        "time"      => "Just now",
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => "Something went wrong. Please try again."]);
}
