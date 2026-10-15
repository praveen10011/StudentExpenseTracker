<?php
session_start();
require __DIR__.'/config/database.php';
if(isset($_SESSION['user_id'])){ header('Location: dashboard.php'); exit; }

$msg = '';
$msg_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if(empty($full_name) || empty($username) || empty($email) || empty($password)){
        $msg = "⚠️ Please fill all fields";
        $msg_type = 'error';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $msg = "⚠️ Invalid Email Format";
        $msg_type = 'error';
    } elseif(strlen($password) < 6){
        $msg = "⚠️ Password must be 6+ characters";
        $msg_type = 'error';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
            $stmt->execute(['email' => $email, 'username' => $username]);
            
            if($stmt->fetch()){
                $msg = "⚠️ Email or Username already taken";
                $msg_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert to users table
                $sql = "INSERT INTO users (username, full_name, email, password) VALUES (:username, :full_name, :email, :password)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'username' => $username,
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);

                // Also insert to expense_users
                $pdo->prepare("INSERT INTO expense_users (username, full_name, email, password) VALUES (?, ?, ?, ?)")
                    ->execute([$username, $full_name, $email, $hashed_password]);

                $msg = "✅ Account Created Successfully! Redirecting to Login...";
                $msg_type = 'success';
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e){
            $msg = "Database Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register | Student Expense Tracker</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#0a0a0a;--ink:#f5f5f5;--muted:#8a8a8a;
  --green:#10b981;--green-light:#34d399;--green-dark:#059669;--line:#2a2a2a;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Outfit',system-ui,sans-serif}
body{background:var(--bg);color:var(--ink);min-height:100vh;display:flex;}
.left{flex:1.1;background:linear-gradient(135deg, #0a0a0a 0%, #0f1a14 100%);padding:60px 50px;display:flex;flex-direction:column;justify-content:center;position:relative;}
.left::before{content:'';position:absolute;top:-50%;left:-20%;width:600px;height:600px;background:radial-gradient(circle, rgba(16,185,129,0.2) 0%, transparent 70%);}
.logo-icon{width:60px;height:60px;background:linear-gradient(135deg,var(--green),var(--green-light));border-radius:16px;display:grid;place-items:center;font-weight:900;color:#fff;font-size:26px;box-shadow:0 10px 40px rgba(16,185,129,0.4);margin-bottom:24px;z-index:2}
.left h1{font-size:44px;font-weight:900;line-height:1.1;margin-bottom:14px;z-index:2}
.left h1 span{background:linear-gradient(90deg,var(--green),var(--green-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.left p{font-size:16px;color:var(--muted);line-height:1.6;margin-bottom:28px;z-index:2}
.stats{display:flex;gap:24px;z-index:2}.stat{font-size:12px;color:var(--muted)}.stat b{display:block;font-size:20px;color:var(--green-light);font-weight:800}

.right{flex:0.9;display:flex;align-items:center;justify-content:center;padding:40px 20px;background:rgba(17,17,17,0.5);}
.wrap{width:100%;max-width:400px}
.card{background:#111;border:1px solid var(--line);border-radius:20px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,0.5)}
.card h2{font-size:26px;font-weight:800;margin-bottom:6px;text-align:center}
.sub{font-size:13px;color:var(--muted);margin-bottom:22px;text-align:center}
.lab{display:block;font-size:11px;font-weight:600;color:var(--muted);margin:14px 0 6px;text-transform:uppercase;letter-spacing:0.5px}
.inp{width:100%;padding:13px 14px;background:#1a1a1a;border:1.5px solid var(--line);border-radius:10px;color:var(--ink);font-size:14px;outline:none;transition:0.2s}
.inp:focus{border-color:var(--green);background:#222;box-shadow:0 0 0 3px rgba(16,185,129,0.12)}
.btn{width:100%;margin-top:18px;padding:14px;background:linear-gradient(180deg,var(--green),var(--green-dark));color:white;border:none;border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;transition:0.2s;box-shadow:0 6px 18px rgba(16,185,129,0.3)}
.btn:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,185,129,0.5)}
.divider{margin:18px 0;display:flex;align-items:center;gap:10px;color:var(--muted);font-size:11px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line)}
.footer{text-align:center;font-size:13px;color:var(--muted)}
.footer a{color:var(--green-light);text-decoration:none;font-weight:700}
.alert{padding:11px 12px;border-radius:9px;margin-bottom:16px;font-size:12px;font-weight:500;text-align:center}
.alert.error{background:rgba(239,68,68,0.15);border:1px solid #ef4444;color:#f87171}
.alert.success{background:rgba(16,185,129,0.15);border:1px solid var(--green);color:#34d399}
@media(max-width:900px){body{flex-direction:column}.left{display:none}.right{padding:20px}}
</style>
</head>
<body>

<div class="left">
  <div class="logo-icon">S</div>
  <h1>Create Your <span>Account</span></h1>
  <p>Join thousands of students tracking expenses smartly. 100% Free, Always.</p>
  <div class="stats">
    <div class="stat"><b>Secure</b>Data</div>
    <div class="stat"><b>Fast</b>Reports</div>
    <div class="stat"><b>Free</b>Forever</div>
  </div>
</div>

<div class="right">
  <div class="wrap">
    <div class="card">
      <h2>Get Started</h2>
      <div class="sub">Create your free account in 30 seconds</div>

      <?php if($msg): ?>
          <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
          <label class="lab">Full Name</label>
          <input type="text" name="full_name" class="inp" placeholder="praveen" required>

          <label class="lab">Username</label>
          <input type="text" name="username" class="inp" placeholder="praveen@123" required>

          <label class="lab">Email Address</label>
          <input type="email" name="email" class="inp" placeholder="you@gmail.com" required>

          <label class="lab">Password</label>
          <input type="password" name="password" class="inp" placeholder="Minimum 6 characters" required>

          <button type="submit" class="btn">Create Account →</button>
      </form>

      <div class="divider">or</div>
      <div class="footer">
          Already have an account? <a href="login.php">Login here</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>