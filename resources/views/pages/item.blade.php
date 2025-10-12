@extends('layouts.sidebar')

@section('title', 'Dashboard')

@section('content')
<div class="bg-white p-6 rounded-xl shadow">
    <h1 class="text-2xl font-semibold text-gray-700 mb-4">Selamat Datang, {{ auth()->user()->name }}</h1>
    <p class="text-gray-500">Anda login sebagai <strong>{{ auth()->user()->role }}</strong>.</p>
</div>
@endsection
