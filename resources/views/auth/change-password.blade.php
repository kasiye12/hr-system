@extends('layouts.app')

@section('title', 'Change Password - TNT HR')

@section('content')
    <h1>Change Password</h1>
    <div class="subtitle">Replace your temporary password before continuing.</div>

    <div class="card" style="max-width:500px;">
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <div class="field" style="margin-bottom:16px;">
                <label>Current Password</label>
                <input type="password" name="current" required>
            </div>

            <div class="field" style="margin-bottom:16px;">
                <label>New Password (minimum 8 characters)</label>
                <input type="password" name="new" required>
            </div>

            <div class="field" style="margin-bottom:16px;">
                <label>Confirm New Password</label>
                <input type="password" name="confirm" required>
            </div>

            <button type="submit">Change Password</button>
        </form>
    </div>
@endsection