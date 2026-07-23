<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TNT HR | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-wrapper {
            display: flex;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.12);
            max-width: 900px;
            width: 100%;
            min-height: 500px;
        }
        
        .login-left {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: #fff;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            flex: 1;
        }
        .login-left .brand-logo {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,0.15);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        .login-left h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .login-left p {
            font-size: 14px;
            opacity: 0.8;
            line-height: 1.6;
        }
        .login-left .features {
            margin-top: 30px;
            text-align: left;
            width: 100%;
            max-width: 280px;
        }
        .login-left .features .feat {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .login-left .features .feat .dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .login-right {
            padding: 50px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right h3 {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }
        .login-right .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 28px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group .input-wrap {
            position: relative;
        }
        .form-group .input-wrap .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: #f9fafb;
        }
        .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
            background: #fff;
        }
        
        .btn-login {
            width: 100%;
            padding: 13px;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            margin-top: 8px;
        }
        .btn-login:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79,70,229,0.3);
        }
        
        .error-msg {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #fecaca;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #9ca3af;
        }
        
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 420px; }
            .login-left { padding: 30px 24px; }
            .login-left .features { display: none; }
            .login-right { padding: 30px 24px; }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Brand -->
        <div class="login-left">
            <div class="brand-logo">TNT</div>
            <h2>TNT Construction</h2>
            <p>HR Manpower Reporting System</p>
            <div class="features">
                <div class="feat"><span class="dot"></span> Daily Manpower Tracking</div>
                <div class="feat"><span class="dot"></span> Employee Leave Management</div>
                <div class="feat"><span class="dot"></span> Applicant Registration</div>
                <div class="feat"><span class="dot"></span> Professional Excel Reports</div>
                <div class="feat"><span class="dot"></span> Multi-Project Management</div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-right">
            <h3>Welcome Back 👋</h3>
            <p class="subtitle">Sign in to access your dashboard</p>
            
            @if($errors->any())
                <div class="error-msg">⚠️ {{ $errors->first() }}</div>
            @endif
            
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <span class="icon">👤</span>
                        <input type="text" name="username" value="{{ old('username') }}" placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <span class="icon">🔒</span>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <div class="login-footer">
                TNT HR Manpower Reporting System &copy; {{ date('Y') }}
            </div>
        </div>
    </div>
</body>
</html>
