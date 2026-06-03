@extends('layouts.client')

@section('title', $title ?? 'Pet Hotel')

@section('content')

<section style="min-height: 60vh; padding: 72px 20px; background: #f8fafc;">
    <div style="max-width: 860px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 36px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);">
        <span style="display: inline-flex; color: #f59e0b; font-weight: 800; margin-bottom: 12px;">
            {{ $section ?? 'Pet Hotel' }}
        </span>

        <h1 style="margin: 0 0 12px; color: #0f172a; font-size: 32px;">
            {{ $title ?? 'Đang cập nhật' }}
        </h1>

        <p style="margin: 0; color: #64748b; font-size: 16px; line-height: 1.7;">
            {{ $description ?? 'Trang này đang được hoàn thiện giao diện.' }}
        </p>
    </div>
</section>

@endsection
