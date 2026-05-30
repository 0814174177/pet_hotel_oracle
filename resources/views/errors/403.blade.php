<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không có quyền truy cập</title>

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

        .forbidden-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
        }

        .forbidden-card {
            width: 100%;
            max-width: 720px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .forbidden-code {
            margin: 0;
            font-size: 96px;
            line-height: 1;
            font-weight: 800;
            color: #dc2626;
        }

        .forbidden-title {
            margin: 20px 0 10px;
            font-size: 30px;
            font-weight: 800;
            color: #111827;
        }

        .forbidden-message {
            margin: 0 auto 28px;
            max-width: 520px;
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
        }

        .forbidden-actions {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .forbidden-btn {
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

        .forbidden-btn--primary {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }

        .forbidden-btn--primary:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        .forbidden-btn--secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .forbidden-btn--secondary:hover {
            background: #f1f5f9;
        }

        @media (max-width: 576px) {
            .forbidden-card {
                padding: 36px 22px;
            }

            .forbidden-code {
                font-size: 72px;
            }

            .forbidden-title {
                font-size: 24px;
            }

            .forbidden-actions {
                flex-direction: column;
            }

            .forbidden-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="forbidden-page">
    <div class="forbidden-card">
        <h1 class="forbidden-code">403</h1>

        <h2 class="forbidden-title">Không có quyền truy cập</h2>

        <p class="forbidden-message">
            Bạn không có quyền truy cập vào trang hoặc chức năng này. Vui lòng kiểm tra lại tài khoản,
            quyền hạn hoặc quay về trang phù hợp.
        </p>

        <div class="forbidden-actions">
            <a href="{{ url('/') }}" class="forbidden-btn forbidden-btn--primary">
                Về trang chủ
            </a>

            <a href="javascript:history.back()" class="forbidden-btn forbidden-btn--secondary">
                Quay lại
            </a>
        </div>
    </div>
</div>

</body>
</html>