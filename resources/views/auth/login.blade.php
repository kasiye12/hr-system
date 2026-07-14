<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNT HR Reporting Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0e2437, #1b5966);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: #16324f;
            color: #fff;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .login-header h1 {
            font-size: 22px;
            color: #1b2635;
        }
        .login-header p {
            color: #627386;
            font-size: 14px;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #627386;
            margin-bottom: 4px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dce5ee;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1b7f79;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #1b7f79;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #156b66;
        }
        .error {
            background: #ffeded;
            color: #a61b1b;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #efbbbb;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .notes {
            margin-top: 20px;
            padding: 14px;
            background: #f4f7fa;
            border-radius: 8px;
            font-size: 12px;
            color: #697a8d;
        }
        .notes strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">TNT</div>
            <h1>Manpower Reporting Application</h1>
            <p>Secure HR reporting access</p>
        </div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="notes">
            <strong>Initial accounts:</strong>
            Administrator: admin / Admin@123<br>
            HR User: hr_editor / HR@123<br>
            Viewer: viewer / View@123
        </div>
    </div>
</body>
</html>