<!DOCTYPE html>
<html>
<head>
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Hello {{ $user->name }},</h2>
    <p>Your account password has been reset by the Administrator.</p>
    
    <div style="background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin: 20px 0; font-size: 18px;">
        <strong>New Password:</strong> {{ $newPassword }}
    </div>
    
    <p>Please login using this new password. We highly recommend you to change this password immediately after logging in.</p>
    
    <p>Thank you.</p>
</body>
</html>
