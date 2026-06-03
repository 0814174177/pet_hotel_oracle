<?php

namespace App\Http\Middleware;

use App\Services\Manager\ManagerBranchScopeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private const ROLE_ALIASES = [
        'customer' => 'CUSTOMER',
        'receptionist' => 'RECEPTIONIST',
        'groomer' => 'GROOMER',
        'manager' => 'MANAGER',
        'admin' => 'ADMIN',
        'ceo' => 'ADMIN',
    ];

    public function __construct(
        private ManagerBranchScopeService $managerBranchScope
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {

        $user = $request->user();

        $expectsJson = $request->expectsJson() || $request->is('api/*');

        if (! $user || ! $user->role) {
            if (! $expectsJson) {
                return redirect()->route('login');
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Không xác định được danh tính người dùng.',
            ], 401);
        }

        $normalizedAllowedRoles = array_map(
            fn(string $role): string => self::ROLE_ALIASES[strtolower($role)] ?? $role,
            $allowedRoles
        );
        $normalizedUserRole = self::ROLE_ALIASES[strtolower((string) $user->role)] ?? (string) $user->role;

        if (! in_array($normalizedUserRole, $normalizedAllowedRoles, true)) {
            if (! $expectsJson) {
                return redirect()->route('unauthorized');
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Bạn không có quyền thực hiện hành động này.',
            ], 403);
        }

        if ($normalizedUserRole === 'MANAGER' && in_array('MANAGER', $normalizedAllowedRoles, true)) {
            $this->managerBranchScope->currentBranchId();
        } elseif (! $user->is_active || ($user->isStaff() && $user->employee && ! $user->employee->isWorking())) {
            if (! $expectsJson) {
                return redirect()->route('login');
            }

            return response()->json([
                'status' => 'error',
                'message' => 'KhÃ´ng xÃ¡c Ä‘á»‹nh Ä‘Æ°á»£c danh tÃ­nh ngÆ°á»i dÃ¹ng.',
            ], 401);
        }

        return $next($request);
    }
}
