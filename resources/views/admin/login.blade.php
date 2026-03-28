@extends('admin.layout', ['title' => 'Login Admin'])

@section('body')
    <div class="login-wrap">
        <div class="login-card">
            <div class="badge">Backend Control Room</div>
            <h1 style="margin: 16px 0 8px; font-size: 2rem;">Accesso /admin</h1>
            <p class="muted" style="margin-top: 0;">Pannello operativo per stato DB, magazzino e fatture passive.</p>

            @if ($errors->any())
                <div class="errors" style="margin: 18px 0;">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="content">
                @csrf
                <label>
                    Username
                    <input type="text" name="username" value="{{ old('username', 'admin') }}" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" value="admin123" required>
                </label>
                <button class="btn" type="submit">Entra nel pannello</button>
            </form>
        </div>
    </div>
@endsection
