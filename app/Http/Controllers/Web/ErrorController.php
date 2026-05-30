<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ErrorController extends WebController
{
    public function unauthorized(): Response
    {
        return $this->errorPage(
            'Khong co quyen truy cap',
            'Unauthorized',
            'Tai khoan cua ban khong co quyen truy cap trang nay.',
            Response::HTTP_FORBIDDEN
        );
    }

    public function notFound(): Response
    {
        return $this->errorPage(
            '404 - Khong tim thay',
            'Not Found',
            'Trang ban dang tim kiem khong ton tai hoac da bi go bo.',
            Response::HTTP_NOT_FOUND
        );
    }

    public function fallback(): RedirectResponse
    {
        return redirect()->route('404');
    }

    public function serverError(): Response
    {
        return response()->view('errors.500', [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function errorPage(string $title, string $section, string $description, int $status): Response
    {
        $view = match ($status) {
            Response::HTTP_FORBIDDEN => 'errors.403',
            Response::HTTP_NOT_FOUND => 'errors.404',
            default => 'errors.500',
        };

        return response()->view($view, compact('title', 'section', 'description'), $status);
    }
}
