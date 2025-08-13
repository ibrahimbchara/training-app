<?php
session_start();

// Simple authentication configuration
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'training123'; // Change this password!

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Login function
function login($username, $password) {
    global $ADMIN_USERNAME, $ADMIN_PASSWORD;
    
    if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

// Logout function
function logout() {
    session_destroy();
    session_start();
}

// Check if session is expired (24 hours)
function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return true;
    }
    
    $sessionLifetime = 24 * 60 * 60; // 24 hours in seconds
    return (time() - $_SESSION['login_time']) > $sessionLifetime;
}

// Require authentication for write operations
function requireAuth() {
    if (!isLoggedIn() || isSessionExpired()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required', 'login_required' => true]);
        exit;
    }
}

// Handle login/logout requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        if ($input['action'] === 'login') {
            $username = $input['username'] ?? '';
            $password = $input['password'] ?? '';
            
            if (login($username, $password)) {
                echo json_encode(['success' => true, 'message' => 'Login successful']);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
            exit;
        } elseif ($input['action'] === 'logout') {
            logout();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
            exit;
        }
    }
}

// Check login status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    echo json_encode([
        'logged_in' => isLoggedIn() && !isSessionExpired(),
        'username' => $_SESSION['username'] ?? null
    ]);
    exit;
}
?>
