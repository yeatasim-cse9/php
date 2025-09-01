<?php
// backend/controllers/AuthController.php
// Handles user registration & login

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AuthController
{
    /**
     * Register a new user.
     * POST ?r=auth&a=register
     * Body: { "name":"...", "email":"...", "phone":"...", "password":"..." }
     */
    public static function register(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['name','email','password']);
        if ($missing) error_response("Missing field: $missing", 400);

        $name  = s($data['name']);
        $email = strtolower(s($data['email']));
        $phone = nn($data['phone'] ?? '');
        $pass  = (string) $data['password'];

        if (!is_valid_email($email)) {
            error_response('Invalid email address');
        }
        if (!is_strong_password($pass, 6)) {
            error_response('Password must be at least 6 characters with letters & digits');
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            error_response('Email already registered', 409);
        }
        $stmt->close();

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?, 'user')");
        $stmt->bind_param('ssss', $name, $email, $phone, $hash);
        if (!$stmt->execute()) {
            error_response('DB error while creating user', 500);
        }
        $uid = $stmt->insert_id;
        $stmt->close();

        respond([
            'message' => 'Registered successfully',
            'user' => [
                'user_id' => $uid,
                'name'    => $name,
                'email'   => $email,
                'phone'   => $phone,
                'role'    => 'user'
            ]
        ], 201);
    }

    /**
     * Login a user.
     * POST ?r=auth&a=login
     * Body: { "email":"...", "password":"..." }
     */
    public static function login(mysqli $conn): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_response('Method not allowed', 405);
        }

        $data = read_json();
        $missing = require_fields($data, ['email','password']);
        if ($missing) error_response("Missing field: $missing", 400);

        $email = strtolower(s($data['email']));
        $pass  = (string) $data['password'];

        $stmt = $conn->prepare("SELECT user_id, name, email, phone, password, role FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($pass, $user['password'])) {
            error_response('Invalid email or password', 401);
        }

        // In production: generate JWT / session. Here we return profile only.
        respond([
            'message' => 'Login successful',
            'user' => [
                'user_id' => (int)$user['user_id'],
                'name'    => $user['name'],
                'email'   => $user['email'],
                'phone'   => $user['phone'],
                'role'    => $user['role']
            ]
        ]);
    }
}
