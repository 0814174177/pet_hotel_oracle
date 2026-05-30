<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không tìm thấy trang</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .not-found-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .not-found-card {
            width: 100%;
            max-width: 720px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .not-found-code {
            margin: 0;
            font-size: 96px;
            line-height: 1;
            font-weight: 800;
            color: #2563eb;
        }

        .not-found-title {
            margin: 20px 0 10px;
            font-size: 30px;
            font-weight: 800;
            color: #111827;
        }

        .not-found-message {
            margin: 0 auto 28px;
            max-width: 500px;
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
        }

        .not-found-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .not-found-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 150px;
            height: 44px;
            padding: 0 18px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid transparent;
            transition: 0.2s ease;
        }

        .not-found-btn--primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .not-found-btn--primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .not-found-btn--secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .not-found-btn--secondary:hover {
            background: #f1f5f9;
        }

        @media (max-width: 576px) {
            .not-found-card {
                padding: 36px 22px;
            }

            .not-found-code {
                font-size: 72px;
            }

            .not-found-title {
                font-size: 24px;
            }

            .not-found-actions {
                flex-direction: column;
            }

            .not-found-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="not-found-page">
    <div class="not-found-card">
        <h1 class="not-found-code">404</h1>

        <h2 class="not-found-title">Không tìm thấy trang</h2>

        <p class="not-found-message">
            Trang bạn đang tìm kiếm có thể đã bị xoá, đổi đường dẫn hoặc hiện không tồn tại trong hệ thống.
        </p>

        <div class="not-found-actions">
            <a href="{{ url('/') }}" class="not-found-btn not-found-btn--primary">
                Về trang chủ
            </a>

            <a href="javascript:history.back()" class="not-found-btn not-found-btn--secondary">
                Quay lại
            </a>
        </div>
    </div>
</div>

</body>
</html>