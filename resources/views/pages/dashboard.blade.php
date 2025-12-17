@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Selamat Datang, {{ auth()->user()->name }}</h1>
        <p class="text-text-secondary">Anda login sebagai <strong>{{ auth()->user()->role }}</strong>.</p>
    </div>
@endsection
