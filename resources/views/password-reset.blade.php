<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Reset Kata Sandi</title>
</head>
<body>
    <main>
        <h1>Buat Kata Sandi Baru</h1>
        <p>Gunakan kata sandi baru dengan minimal 12 karakter.</p>

        @if($invalidLink || $errors->any())
            <p role="alert">{{ $errors->first('email', 'Tautan reset tidak valid atau telah kedaluwarsa.') }}</p>
        @endif

        @if(! $invalidLink)
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <p>
                    <label for="password">Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" autocomplete="new-password" minlength="12" required>
                </p>
                <p>
                    <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="12" required>
                </p>
                <button type="submit">Simpan Kata Sandi Baru</button>
            </form>
        @endif
    </main>
</body>
</html>
