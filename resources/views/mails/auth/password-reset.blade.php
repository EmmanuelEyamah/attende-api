<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Attende</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #112437; /* primary color */
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(17, 36, 55, 0.1); /* primary color with opacity */
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #f4f4f4;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #112437; /* primary color */
            text-decoration: none;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px 20px;
        }
        .reset-button {
            display: inline-block;
            padding: 14px 30px;
            margin: 25px 0;
            background-color: #264E58; /* accent color */
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            transition: background-color 0.3s;
        }
        .reset-button:hover {
            background-color: #2f5f6b; /* dark mode accent color */
        }
        .divider {
            border-top: 1px solid #677489; /* secondary color */
            margin: 20px 0;
        }
        .security-box {
            background-color: #f8f9fa;
            border-left: 4px solid #264E58; /* accent color */
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border-left: 4px solid #264E58; /* accent color */
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #677489; /* secondary color */
            font-size: 12px;
            color: #677489; /* secondary color */
        }
        .alternative-link {
            word-break: break-all;
            color: #264E58; /* accent color */
            font-size: 14px;
        }
        .help-text {
            font-size: 14px;
            color: #677489; /* secondary color */
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Attende</div>
            <p style="margin: 0; color: #677489;">Password Reset Request</p>
        </div>

        <div class="content">
            <h2>Password Reset Request</h2>

            <p>Hello {{ $user->first_name }},</p>

            <p>We received a request to reset the password for your Attende account. If you made this request, please click the button below to reset your password:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">Reset My Password</a>
            </div>

            <div class="security-box">
                <strong>Security Tips:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Choose a strong password that you haven't used before</li>
                    <li>Use a combination of letters, numbers, and symbols</li>
                    <li>Never share your password with anyone</li>
                </ul>
            </div>

            <div class="warning">
                ⚠️ This password reset link will expire in 24 hours. If you didn't request a password reset, please ignore this email or contact our support team if you have concerns.
            </div>

            <p class="help-text">If the button above doesn't work, copy and paste this link into your browser:</p>
            <p class="alternative-link">{{ $resetUrl }}</p>

            <div class="divider"></div>

            <p>Need help? Contact our support team</p>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Attende. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
