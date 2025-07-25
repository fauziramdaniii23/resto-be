<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 30px;">
<div style="max-width: 600px; margin: auto; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h2 style="color: #333;">Hello!</h2>
    <p style="color: #555;">Thank you for registering. To complete your registration, please verify your email by clicking the button below:</p>

    <p style="text-align: center; margin: 30px 0;">
        <a href="{{ $linkUrl }}" style="
                background-color: #4CAF50;
                color: white;
                padding: 14px 24px;
                text-decoration: none;
                font-weight: bold;
                border-radius: 6px;
                display: inline-block;">
            Verify Email
        </a>
    </p>

    <p style="color: #555;">If you did not create an account, no further action is required.</p>

    <p style="color: #999; font-size: 12px;">If you're having trouble clicking the "Verify Email" button, copy and paste the URL below into your web browser:</p>
    <p style="word-break: break-all; color: #999; font-size: 12px;">{{ $verifyUrl }}</p>

    <p style="color: #333; margin-top: 30px;">Regards,<br><strong>YourApp Team</strong></p>
</div>
</body>
</html>
