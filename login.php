<?php
// Simple vulnerable login page for security testing
// Returns 401 on failed login — detectable by Wazuh

$valid_credentials = [
    "admin"         => "admin123",
    "administrator" => "password",
    "root"          => "toor",
    "user"          => "user123"
];

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    if (isset($valid_credentials[$username]) && $valid_credentials[$username] === $password) {
        $success = true;
    } else {
        // Return 401 — Wazuh will detect this
        http_response_code(401);
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CorpNet — Internal Portal</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      background: #f5f5f5;
      font-family: Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      background: #fff;
      border: 1px solid #ddd;
      padding: 40px;
      width: 360px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    h1 { font-size: 22px; color: #222; margin-bottom: 6px; }

    .subtitle { font-size: 12px; color: #999; margin-bottom: 28px; }

    .alert {
      background: #fff3f3;
      border: 1px solid #ffcccc;
      color: #cc0000;
      padding: 10px 12px;
      font-size: 13px;
      margin-bottom: 16px;
    }

    .success {
      background: #f3fff3;
      border: 1px solid #ccffcc;
      color: #008800;
      padding: 10px 12px;
      font-size: 13px;
      margin-bottom: 16px;
    }

    label { display: block; font-size: 12px; color: #555; margin-bottom: 5px; margin-top: 14px; }

    input {
      width: 100%;
      border: 1px solid #ccc;
      padding: 10px 12px;
      font-size: 14px;
      outline: none;
    }

    input:focus { border-color: #0066cc; }

    .btn {
      width: 100%;
      background: #0066cc;
      color: #fff;
      border: none;
      padding: 12px;
      font-size: 14px;
      cursor: pointer;
      margin-top: 20px;
    }

    .btn:hover { background: #0055aa; }

    .links { display: flex; justify-content: space-between; margin-top: 16px; }

    .links a { font-size: 12px; color: #0066cc; text-decoration: none; }
    .links a:hover { text-decoration: underline; }

    .admin { text-align: center; }
    .admin h2 { color: #008800; font-size: 20px; margin-bottom: 8px; }
    .admin p { color: #555; font-size: 13px; margin-bottom: 24px; }

    .data-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #eee;
      font-size: 13px;
    }

    .data-row:last-child { border: none; }
    .key { color: #999; }
    .val { color: #222; font-weight: bold; }
    .val.green { color: #008800; }
    .val.red { color: #cc0000; }

    .footer { text-align: center; margin-top: 24px; font-size: 11px; color: #bbb; }
  </style>
</head>
<body>
  <div class="card">

    <?php if ($success): ?>
      <div class="admin">
        <h2>✓ Access Granted</h2>
        <p>Welcome, <?php echo htmlspecialchars($_POST["username"]); ?></p>
        <div class="data-row">
          <span class="key">Session ID</span>
          <span class="val green"><?php echo strtoupper(bin2hex(random_bytes(8))); ?></span>
        </div>
        <div class="data-row">
          <span class="key">Clearance</span>
          <span class="val green">Level 5 — Full</span>
        </div>
        <div class="data-row">
          <span class="key">Last Login</span>
          <span class="val"><?php echo date("Y-m-d H:i:s"); ?></span>
        </div>
        <div class="data-row">
          <span class="key">Active Users</span>
          <span class="val">3</span>
        </div>
        <div class="data-row">
          <span class="key">Threat Level</span>
          <span class="val red">Elevated</span>
        </div>
      </div>

    <?php else: ?>
      <h1>CorpNet Portal</h1>
      <div class="subtitle">Authorized access only</div>

      <?php if ($error): ?>
        <div class="alert"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <label>Username</label>
        <input type="text" name="username" placeholder="username" autocomplete="off"/>

        <label>Password</label>
        <input type="password" name="password" placeholder="password"/>

        <button type="submit" class="btn">Sign In</button>
      </form>

      <div class="links">
        <a href="/admin">Admin Panel</a>
        <a href="/backup">Backup</a>
        <a href="/.env">Config</a>
        <a href="/phpmyadmin">Database</a>
      </div>
    <?php endif; ?>

    <div class="footer">CorpNet v2.4.1 — Internal use only</div>
  </div>
</body>
</html>
