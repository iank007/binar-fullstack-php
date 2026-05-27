<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Binar!</title>
</head>
<body>
    <h1>Welcome, {{ $user->name }}!</h1>
    <p>Your account has been created successfully.</p>
    <p>You can now log in using your email: <strong>{{ $user->email }}</strong></p>
</body>
</html>
