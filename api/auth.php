<?php
function getBearerToken() {
    $headers = [];

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    }

    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            if (preg_match('/Bearer\s+(\S+)/i', trim($value), $matches)) {
                return trim($matches[1]);
            }
        }
    }

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/i', trim($_SERVER['HTTP_AUTHORIZATION']), $matches)) {
            return trim($matches[1]);
        }
    }

    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/i', trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']), $matches)) {
            return trim($matches[1]);
        }
    }

    return null;
}

function validateToken($conn) {
    $token = getBearerToken();

    if (!$token) {
        echo json_encode([
            "success" => false,
            "message" => "Authorization token missing"
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id, user_type, token FROM api_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row;
    }

    $stmt->close();

    echo json_encode([
        "success" => false,
        "message" => "Invalid or expired token"
    ]);
    exit();
}

function requireRole($user, $allowedRoles = []) {
    if (!in_array($user['user_type'], $allowedRoles)) {
        echo json_encode([
            "success" => false,
            "message" => "Access denied"
        ]);
        exit();
    }
}
?>