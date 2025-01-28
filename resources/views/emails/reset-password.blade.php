<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            background-color: #0070f3;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .code {
            text-align: center;
            font-size: 24px;
            color: #0070f3;
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            letter-spacing: 5px;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Reset Password</h2>
        </div>
        <p>Hai,</p>
        <p>Anda telah meminta untuk mengatur ulang password Anda. Gunakan kode verifikasi di bawah ini untuk melanjutkan proses reset password:</p>
        <div class="code">
            <strong>{{ $code }}</strong>
        </div>
        <p>Kode ini hanya berlaku selama 15 menit. Jangan bagikan kode ini dengan siapapun.</p>
        <div class="footer">
            <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
            <p>© 2024 Kominfo Dokumen Kontrak Kerja Apps</p>
        </div>
    </div>
</body>
</html>