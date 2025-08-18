<?php
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function loginAdmin($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM admin_users WHERE username = ?",
            [$username]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION[ADMIN_SESSION_NAME] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'login_time' => time()
            ];
            return true;
        }
        return false;
    }

    public function isAdminLoggedIn() {
        if (!isset($_SESSION[ADMIN_SESSION_NAME])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION[ADMIN_SESSION_NAME]['login_time'] > SESSION_TIMEOUT) {
            $this->logoutAdmin();
            return false;
        }

        return true;
    }

    public function logoutAdmin() {
        unset($_SESSION[ADMIN_SESSION_NAME]);
    }

    public function getAdminUser() {
        return $_SESSION[ADMIN_SESSION_NAME] ?? null;
    }

    public function checkSitePassword($password) {
        $settings = $this->getSettings();
        
        if ($settings['site_password_enabled'] === 'false') {
            return true; // No password required
        }

        $hashedPassword = $settings['site_password_hash'];
        return password_verify($password, $hashedPassword);
    }

    public function setSitePassword($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->update(
            'website_settings',
            ['setting_value' => $hash],
            'setting_key = ?',
            ['site_password_hash']
        );
    }

    public function enableSitePassword($enabled = true) {
        $this->db->update(
            'website_settings',
            ['setting_value' => $enabled ? 'true' : 'false'],
            'setting_key = ?',
            ['site_password_enabled']
        );
    }

    public function isSitePasswordEnabled() {
        $setting = $this->db->fetchOne(
            "SELECT setting_value FROM website_settings WHERE setting_key = ?",
            ['site_password_enabled']
        );
        return $setting['setting_value'] === 'true';
    }

    public function isUserLoggedIn() {
        if (!$this->isSitePasswordEnabled()) {
            return true; // No password required
        }

        return isset($_SESSION[USER_SESSION_NAME]) && $_SESSION[USER_SESSION_NAME] === true;
    }

    public function loginUser($password) {
        if ($this->checkSitePassword($password)) {
            $_SESSION[USER_SESSION_NAME] = true;
            return true;
        }
        return false;
    }

    public function logoutUser() {
        unset($_SESSION[USER_SESSION_NAME]);
    }

    private function getSettings() {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM website_settings");
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        return $result;
    }

    public function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}
?>
