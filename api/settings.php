<?php
require_once 'includes/dbh.inc.php';
require_once 'includes/config_session.inc.php';
require_once 'includes/update_user_profile.inc.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$flash = $_GET['updated'] ?? null;
$error = $_SESSION['profile_error'] ?? null;
unset($_SESSION['profile_error']);

// $user and $states are provided by update_user_profile.inc.php
$avatarSrc = !empty($user['file_name']) && file_exists($user['file_name'])
    ? htmlspecialchars($user['file_name'])
    : 'assets/img/avatar-placeholder.png';

function e($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your Blogar profile, security, notifications and preferences.">
    <title>Blogar | Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.9.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/settings.css" />
</head>

<body>

    <div class="layout">
        <?php require 'sidebar.php'; ?>
        <main class="main">
            <?php
            $pageTitle = "Settings";
            include "navbar.php";
            ?>

            <?php

            $query = "SELECT firstname, middlename, lastname, email, file_name, phone_no, location, bio, state FROM users WHERE id = :user_id";

            $stmt = $pdo->prepare($query);

            $stmt->execute([
                'user_id' => $user_id
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="container">

                <!-- <header class="page-header">
                    <p class="eyebrow"><i class="ri-sparkling-2-fill"></i> Blogar account</p>
                    <h1>Settings</h1>
                    <p class="subtitle">Curate how you appear, how we reach you, and how your account stays safe.</p>
                </header> -->

                <?php if ($flash === 'success'): ?>
                    <div class="alert alert-success" role="status">
                        <i class="ri-checkbox-circle-fill"></i> Your profile has been updated.
                        <button type="button" class="alert-close" aria-label="Dismiss">&times;</button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="ri-error-warning-fill"></i> <?= e($error) ?>
                        <button type="button" class="alert-close" aria-label="Dismiss">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="settings-shell">
                    <!-- Sidebar nav -->
                    <aside class="settings-nav" role="tablist" aria-label="Settings sections">
                        <button class="nav-item active" data-tab="profile" role="tab" aria-selected="true">
                            <span class="nav-icon"><i class="ri-user-3-line"></i></span>
                            <span class="nav-text"><strong>Profile</strong><small>Personal information</small></span>
                        </button>
                        <button class="nav-item" data-tab="security" role="tab" aria-selected="false">
                            <span class="nav-icon"><i class="ri-lock-2-line"></i></span>
                            <span class="nav-text"><strong>Security</strong><small>Password &amp; access</small></span>
                        </button>
                        <button class="nav-item" data-tab="notifications" role="tab" aria-selected="false">
                            <span class="nav-icon"><i class="ri-notification-3-line"></i></span>
                            <span class="nav-text"><strong>Notifications</strong><small>Email &amp; alerts</small></span>
                        </button>
                        <button class="nav-item" data-tab="appearance" role="tab" aria-selected="false">
                            <span class="nav-icon"><i class="ri-palette-line"></i></span>
                            <span class="nav-text"><strong>Appearance</strong><small>Theme &amp; display</small></span>
                        </button>
                        <button class="nav-item" data-tab="danger" role="tab" aria-selected="false">
                            <span class="nav-icon danger"><i class="ri-alert-line"></i></span>
                            <span class="nav-text"><strong>Danger zone</strong><small>Delete &amp; export</small></span>
                        </button>
                    </aside>

                    <div class="settings-content">

                        <!-- PROFILE -->
                        <section class="settings-card tab-panel active" id="tab-profile" role="tabpanel">
                            <form method="POST" action="includes/update_user_profile.inc.php" enctype="multipart/form-data" id="profileForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="form" value="profile">

                                <div class="profile-top">
                                    <div class="profile-left">
                                        <div class="edit-avatar" id="avatarTrigger" title="Change photo">
                                            <img id="previewImage" src="includes/<?= e($user['file_name']) ?>" alt="Profile photo">
                                            <div class="avatar-overlay"><i class="ri-camera-line"></i></div>
                                        </div>
                                        <input type="file" name="profileimg" id="profileUpload" accept="image/png,image/jpeg,image/webp" hidden>
                                        <div class="profile-info">
                                            <h2><?= e(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))) ?: 'Your name' ?></h2>
                                            <p><?= e($user['email'] ?? '') ?></p>
                                            <p class="hint">PNG, JPG or WEBP &middot; max 5MB</p>
                                        </div>
                                    </div>
                                    <button type="button" class="edit-btn" id="changePhotoBtn">
                                        <i class="ri-camera-line"></i> Change photo
                                    </button>
                                </div>

                                <h3 class="section-title">Personal information</h3>

                                <div class="form-grid">
                                    <div class="input-box">
                                        <label for="firstname">First name <span class="req">*</span></label>
                                        <input type="text" id="firstname" name="firstname" required maxlength="50"
                                            value="<?= e($user['firstname'] ?? '') ?>" autocomplete="given-name">
                                    </div>
                                    <div class="input-box">
                                        <label for="middlename">Middle name</label>
                                        <input type="text" id="middlename" name="middlename" maxlength="50"
                                            value="<?= e($user['middlename'] ?? '') ?>" autocomplete="additional-name">
                                    </div>
                                    <div class="input-box">
                                        <label for="lastname">Last name <span class="req">*</span></label>
                                        <input type="text" id="lastname" name="lastname" required maxlength="50"
                                            value="<?= e($user['lastname'] ?? '') ?>" autocomplete="family-name">
                                    </div>
                                    <div class="input-box">
                                        <label for="email">Email <span class="req">*</span></label>
                                        <input type="email" id="email" name="email" required maxlength="255"
                                            value="<?= e($user['email'] ?? '') ?>" autocomplete="email">
                                    </div>
                                    <div class="input-box">
                                        <label for="phone_no">Phone number</label>
                                        <input type="tel" id="phone_no" name="phone_no" maxlength="20"
                                            value="<?= e($user['phone_no'] ?? '') ?>" autocomplete="tel"
                                            pattern="[0-9+\-\s()]{7,20}">
                                    </div>
                                    <div class="input-box">
                                        <label for="state">State</label>
                                        <select id="state" name="state">
                                            <option value="">Select state</option>
                                            <?php foreach ($states as $s): ?>
                                                <option value="<?= e($s) ?>" <?= (($user['state'] ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-box full">
                                        <label for="location">Address</label>
                                        <input type="text" id="location" name="location" maxlength="200"
                                            value="<?= e($user['location'] ?? '') ?>" autocomplete="street-address">
                                    </div>
                                    <div class="input-box full">
                                        <label for="bio">Bio <span class="counter"><span id="bioCount">0</span>/500</span></label>
                                        <textarea id="bio" name="bio" maxlength="500" placeholder="A short introduction…"><?= e($user['bio'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="save-btn">
                                        <i class="ri-save-line"></i> Save changes
                                    </button>
                                </div>
                            </form>
                        </section>

                        <!-- SECURITY -->
                        <section class="settings-card tab-panel" id="tab-security" role="tabpanel" hidden>
                            <form method="POST" action="includes/update_user_profile.inc.php" id="passwordForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="form" value="password">

                                <h3 class="section-title">Change password</h3>
                                <p class="section-sub">Use a strong password you don't reuse elsewhere. Minimum 8 characters with a mix of letters, numbers, and symbols.</p>

                                <div class="form-grid single">
                                    <div class="input-box full">
                                        <label for="current_pwd">Current password</label>
                                        <div class="pwd-wrap">
                                            <input type="password" id="current_pwd" name="current_pwd" autocomplete="current-password" maxlength="128">
                                            <button type="button" class="pwd-toggle" data-target="current_pwd" aria-label="Show password"><i class="ri-eye-line"></i></button>
                                        </div>
                                    </div>
                                    <div class="input-box full">
                                        <label for="new_pwd">New password</label>
                                        <div class="pwd-wrap">
                                            <input type="password" id="new_pwd" name="new_pwd" autocomplete="new-password" maxlength="128" minlength="8">
                                            <button type="button" class="pwd-toggle" data-target="new_pwd" aria-label="Show password"><i class="ri-eye-line"></i></button>
                                        </div>
                                        <div class="pwd-strength" id="pwdStrength" aria-live="polite">
                                            <div class="bars"><span></span><span></span><span></span><span></span></div>
                                            <small class="label">Enter a password</small>
                                        </div>
                                    </div>
                                    <div class="input-box full">
                                        <label for="confirm_pwd">Confirm new password</label>
                                        <div class="pwd-wrap">
                                            <input type="password" id="confirm_pwd" name="confirm_pwd" autocomplete="new-password" maxlength="128">
                                            <button type="button" class="pwd-toggle" data-target="confirm_pwd" aria-label="Show password"><i class="ri-eye-line"></i></button>
                                        </div>
                                        <small class="match-msg" id="matchMsg"></small>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="save-btn">
                                        <i class="ri-lock-2-line"></i> Update password
                                    </button>
                                </div>
                            </form>

                            <hr class="divider">

                            <h3 class="section-title">Two-factor authentication</h3>
                            <p class="section-sub">Add an extra layer of security to your account.</p>
                            <div class="toggle-row">
                                <div>
                                    <strong>Authenticator app</strong>
                                    <small>Use an app like 1Password or Authy to generate codes.</small>
                                </div>
                                <label class="switch"><input type="checkbox" id="twoFAToggle"><span class="slider"></span></label>
                            </div>
                        </section>

                        <!-- NOTIFICATIONS -->
                        <section class="settings-card tab-panel" id="tab-notifications" role="tabpanel" hidden>
                            <h3 class="section-title">Notifications</h3>
                            <p class="section-sub">Choose what you'd like to hear about by email.</p>
                            <div class="toggle-list">
                                <?php
                                $notifs = [
                                    ['product', 'Product updates', 'New features and improvements.', true],
                                    ['comments', 'Comments', 'When someone replies to your posts.', true],
                                    ['mentions', 'Mentions', "When you're @mentioned in a thread.", true],
                                    ['weekly', 'Weekly digest', 'A Monday summary of your activity.', false],
                                    ['marketing', 'Marketing', 'Occasional offers and tips.', false],
                                ];
                                foreach ($notifs as [$k, $t, $d, $on]): ?>
                                    <div class="toggle-row">
                                        <div><strong><?= e($t) ?></strong><small><?= e($d) ?></small></div>
                                        <label class="switch"><input type="checkbox" name="notif_<?= e($k) ?>" <?= $on ? 'checked' : '' ?>><span class="slider"></span></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <!-- APPEARANCE -->
                        <section class="settings-card tab-panel" id="tab-appearance" role="tabpanel" hidden>
                            <h3 class="section-title">Appearance</h3>
                            <p class="section-sub">Tweak how Blogar looks for you.</p>

                            <p class="field-label">Theme</p>
                            <div class="theme-grid">
                                <button type="button" class="theme-card active" data-theme="light"><i class="ri-sun-line"></i> Light</button>
                                <button type="button" class="theme-card" data-theme="dark"><i class="ri-moon-line"></i> Dark</button>
                                <button type="button" class="theme-card" data-theme="system"><i class="ri-computer-line"></i> System</button>
                            </div>

                            <p class="field-label">Accent color</p>
                            <div class="accent-row">
                                <button type="button" class="accent active" style="--c:#eb2020" data-accent="crimson"><span></span>Crimson</button>
                                <button type="button" class="accent" style="--c:#4f46e5" data-accent="indigo"><span></span>Indigo</button>
                                <button type="button" class="accent" style="--c:#10b981" data-accent="emerald"><span></span>Emerald</button>
                                <button type="button" class="accent" style="--c:#f59e0b" data-accent="amber"><span></span>Amber</button>
                            </div>
                        </section>

                        <!-- DANGER -->
                        <section class="settings-card tab-panel" id="tab-danger" role="tabpanel" hidden>
                            <h3 class="section-title">Export data</h3>
                            <p class="section-sub">Download a copy of your account data.</p>
                            <div class="toggle-row">
                                <div><strong>Account export</strong><small>We'll email you a ZIP of your posts and profile.</small></div>
                                <button type="button" class="ghost-btn"><i class="ri-download-2-line"></i> Request export</button>
                            </div>

                            <hr class="divider">

                            <div class="danger-zone">
                                <h3 class="danger-title">Delete account</h3>
                                <p class="section-sub">This permanently deletes your account, posts, and data. This cannot be undone.</p>
                                <form method="POST" action="includes/delete_account.inc.php" id="deleteForm" onsubmit="return confirm('This permanently deletes your account. Continue?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <div class="input-box full">
                                        <label for="confirm_delete">Type <strong>DELETE</strong> to confirm</label>
                                        <input type="text" id="confirm_delete" name="confirm_delete" autocomplete="off" placeholder="DELETE">
                                    </div>
                                    <div class="form-actions">
                                        <button type="submit" class="danger-btn" id="deleteBtn" disabled>
                                            <i class="ri-delete-bin-line"></i> Delete my account
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </section>

                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="assets/js/navbar.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/user_dashboard.js"></script>
</body>

</html>