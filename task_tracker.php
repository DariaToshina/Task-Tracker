<?php
session_start();

if (!isset($_SESSION['users'])) $_SESSION['users'] = [];
if (!isset($_SESSION['tokens'])) $_SESSION['tokens'] = [];

function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if ($action === 'register_api' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        if (!$email || !$password) json_response(['error' => 'Email and password required']);
        if (isset($_SESSION['users'][$email])) json_response(['error' => 'User exists']);
        $_SESSION['users'][$email] = password_hash($password, PASSWORD_BCRYPT);
        json_response(['message' => 'Registration successful']);
    }

    if ($action === 'login_api' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!isset($_SESSION['users'][$email]) || !password_verify($password, $_SESSION['users'][$email])) {
            json_response(['error' => 'Invalid credentials']);
        }

        $token = md5(uniqid($email, true));
        $_SESSION['tokens'][$token] = $email;
        json_response(['token' => $token]);
    }

    if ($action === 'me_api' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $token = $_GET['token'] ?? '';

        if (!$token || !isset($_SESSION['tokens'][$token])) json_response(['error' => 'Unauthorized']);
        $email = $_SESSION['tokens'][$token];
        json_response(['email' => $email]);
    }

    if ($action === 'logout_api' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_GET['token'] ?? '';

        if ($token && isset($_SESSION['tokens'][$token])) unset($_SESSION['tokens'][$token]);
        json_response(['message' => 'Logged out']);
    }

    json_response(['error' => 'Invalid action or method']);
}

$page = $_GET['page'] ?? 'register';
$token = $_SESSION['current_token'] ?? null;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Task Tracker API Demo</title>
</head>
<body>
<?php if ($page === 'register'): ?>
    <h2>Registration</h2>
    <form method="post" action="?page=login" onsubmit="return registerUser(event)">
        <input type="email" id="regEmail" placeholder="Email" required>
        <!-- <input type="email" placeholder="Email" required> -->
        <input type="password" id="regPassword" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <pre id="regResult"></pre>

    <script>
    async function registerUser(event){
        event.preventDefault();
        const email = document.getElementById('regEmail').value;
        const password = document.getElementById('regPassword').value;
        const res = await fetch('?action=register_api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({email, password})
        });
        const data = await res.json();
        document.getElementById('regResult').textContent = JSON.stringify(data, null, 2);
        if(!data.error) window.location='?page=login';
        return false;
    }
    </script>

<?php elseif ($page === 'login'): ?>
    <h2>Login</h2>
    <form method="post" action="?page=me" onsubmit="return loginUser(event)">
        <input type="email" id="loginEmail" placeholder="Email" required>
        <!-- <input type="email" placeholder="Email" required> -->
        <input type="password" id="loginPassword" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <pre id="loginResult"></pre>

    <script>
    async function loginUser(event){
        event.preventDefault();
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        const res = await fetch('?action=login_api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({email, password})
        });

        const data = await res.json();
        document.getElementById('loginResult').textContent = JSON.stringify(data, null, 2);

        if(data.token){
            localStorage.setItem('token', data.token);
            window.location='?page=me';
        }
        return false;
    }
    </script>

<?php elseif ($page === 'me'): ?>
    <h2>Current user</h2>
    <button onclick="getMe()">Show /api/me</button>
    <pre id="meResult"></pre>
    <a href="?page=logout">Logout</a>

    <script>
    async function getMe(){
        const token = localStorage.getItem('token');
        const res = await fetch(`?action=me_api&token=${token}`);
        const data = await res.json();
        document.getElementById('meResult').textContent = JSON.stringify(data, null, 2);
    }
    </script>

<?php elseif ($page === 'logout'): ?>
    <h2>Logout</h2>
    <button onclick="logout()">Logout</button>
    <pre id="logoutResult"></pre>
    <a href="?page=register">Return to registration page</a>

    <script>
    async function logout(){
        const token = localStorage.getItem('token');
        const res = await fetch(`?action=logout_api&token=${token}`, {method:'POST'});
        const data = await res.json();

        document.getElementById('logoutResult').textContent = JSON.stringify(data, null, 2);

        localStorage.removeItem('token');
    }
    </script>

<?php endif; ?>
</body>
</html>
