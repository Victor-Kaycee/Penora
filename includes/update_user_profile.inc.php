<?php
require_once 'dbh.inc.php';
require_once 'config_session.inc.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ============================================================
   FETCH USER
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT firstname, middlename, lastname, email, file_name,
           phone_no, location, bio, state, pwd
    FROM users
    WHERE id = :user_id
");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$states = [
    "Abia",
    "Adamawa",
    "Akwa Ibom",
    "Anambra",
    "Bauchi",
    "Bayelsa",
    "Benue",
    "Borno",
    "Cross River",
    "Delta",
    "Ebonyi",
    "Edo",
    "Ekiti",
    "Enugu",
    "FCT Abuja",
    "Gombe",
    "Imo",
    "Jigawa",
    "Kaduna",
    "Kano",
    "Katsina",
    "Kebbi",
    "Kogi",
    "Kwara",
    "Lagos",
    "Nasarawa",
    "Niger",
    "Ogun",
    "Ondo",
    "Osun",
    "Oyo",
    "Plateau",
    "Rivers",
    "Sokoto",
    "Taraba",
    "Yobe",
    "Zamfara"
];

/* ============================================================
   HELPERS
   ============================================================ */
function fail_back($msg)
{
    $_SESSION['profile_error'] = $msg;
    header("Location: ../settings.php");
    exit;
}

function verify_csrf()
{
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        fail_back("Invalid request token. Please reload and try again.");
    }
}

/* Strong password policy */
function password_is_strong($pwd)
{
    if (strlen($pwd) < 8 || strlen($pwd) > 128) return false;
    if (!preg_match('/[A-Z]/', $pwd)) return false;
    if (!preg_match('/[a-z]/', $pwd)) return false;
    if (!preg_match('/\d/',   $pwd)) return false;
    if (!preg_match('/[^A-Za-z0-9]/', $pwd)) return false;
    return true;
}

/* ============================================================
   HANDLE POST
   ============================================================ */
if ($_SERVER["REQUEST_METHOD"] !== "POST") return;

verify_csrf();

$form = $_POST['form'] ?? 'profile';

/* -------------------- PASSWORD FORM -------------------- */
if ($form === 'password') {
    $current = $_POST['current_pwd'] ?? '';
    $new     = $_POST['new_pwd']     ?? '';
    $confirm = $_POST['confirm_pwd'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        fail_back("All password fields are required.");
    }
    if (!password_verify($current, $user['pwd'])) {
        fail_back("Your current password is incorrect.");
    }
    if (!password_is_strong($new)) {
        fail_back("Password must be 8+ chars with upper, lower, number, and symbol.");
    }
    if ($new !== $confirm) {
        fail_back("New passwords do not match.");
    }
    if (password_verify($new, $user['pwd'])) {
        fail_back("New password must be different from the current one.");
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET pwd = :pwd WHERE id = :user_id");
    $upd->execute(['pwd' => $hashed, 'user_id' => $user_id]);

    // Optional: rotate session id after password change
    session_regenerate_id(true);

    header("Location: ../user_profile.php?updated=success");
    exit;
}

/* -------------------- PROFILE FORM -------------------- */

$firstname  = trim($_POST['firstname']  ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$lastname   = trim($_POST['lastname']   ?? '');
$email      = trim($_POST['email']      ?? '');
$phone_no   = trim($_POST['phone_no']   ?? '');
$location   = trim($_POST['location']   ?? '');
$bio        = trim($_POST['bio']        ?? '');
$addstate   = trim($_POST['state']      ?? '');

/* ----- Validation ----- */
if ($firstname === '' || $lastname === '' || $email === '') {
    fail_back("First name, last name, and email are required.");
}
if (mb_strlen($firstname) > 50 || mb_strlen($lastname) > 50 || mb_strlen($middlename) > 50) {
    fail_back("Names must be 50 characters or fewer.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    fail_back("Please enter a valid email address.");
}
if ($phone_no !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone_no)) {
    fail_back("Please enter a valid phone number.");
}
if (mb_strlen($location) > 200) fail_back("Address is too long.");
if (mb_strlen($bio) > 500)      fail_back("Bio must be 500 characters or fewer.");
if ($addstate !== '' && !in_array($addstate, $states, true)) {
    fail_back("Please select a valid state.");
}

/* ----- Email uniqueness ----- */
$check = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :user_id LIMIT 1");
$check->execute(['email' => $email, 'user_id' => $user_id]);
if ($check->fetch()) {
    fail_back("That email is already in use.");
}

/* ----- Image upload ----- */
$profileImage = $user['file_name'];

if (isset($_FILES['profileimg']) && $_FILES['profileimg']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profileimg']['error'] !== UPLOAD_ERR_OK) {
        fail_back("Image upload failed. Please try again.");
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($_FILES['profileimg']['size'] > $maxBytes) {
        fail_back("Image must be smaller than 5MB.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['profileimg']['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        fail_back("Only JPG, PNG, or WEBP images are allowed.");
    }

    $ext = $allowed[$mime];
    $safeName = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/./uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $uploadPath = $uploadDir . $safeName;

    if (!move_uploaded_file($_FILES['profileimg']['tmp_name'], $uploadPath)) {
        fail_back("Could not save uploaded image.");
    }

    // delete old image (only if it lives in uploads/)
    if (!empty($user['file_name'])) {
        $oldPath = __DIR__ . '/./' . ltrim($user['file_name'], '/');
        if (strpos(realpath($oldPath) ?: '', realpath($uploadDir)) === 0 && is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $profileImage = 'uploads/' . $safeName;
}

/* ----- Update DB ----- */
$upd = $pdo->prepare("
    UPDATE users SET
        firstname  = :firstname,
        middlename = :middlename,
        lastname   = :lastname,
        email      = :email,
        file_name  = :file_name,
        phone_no   = :phone_no,
        location   = :location,
        bio        = :bio,
        state      = :state
    WHERE id = :user_id
");
$upd->execute([
    'firstname'  => $firstname,
    'middlename' => $middlename,
    'lastname'   => $lastname,
    'email'      => $email,
    'file_name'  => $profileImage,
    'phone_no'   => $phone_no,
    'location'   => $location,
    'bio'        => $bio,
    'state'      => $addstate,
    'user_id'    => $user_id,
]);

// refresh local copy so the form re-renders with new values
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

header("Location: ../user_profile.php?updated=success");
exit;
