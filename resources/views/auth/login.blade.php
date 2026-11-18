<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Praktikum</title>
</head>
<body>
    <h1>Login Praktikum</h1>

    <form method="POST" action="{{ route('login.proses') }}">
        @csrf

        <p>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </p>

        <p>
            <label for="password">Kata sandi</label>
            <input id="password" type="password" name="password" required>
        </p>

        @error('email')
            <p>{{ $message }}</p>
        @enderror

        <button type="submit">Masuk</button>
    </form>
</body>
</html>
