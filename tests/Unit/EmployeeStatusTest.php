<?php

namespace Tests\Unit;

use App\Models\Employee;
use Carbon\Carbon;
use Tests\TestCase;

class EmployeeStatusTest extends TestCase
{
    public function test_employee_is_working_by_default(): void
    {
        $employee = new Employee();

        $this->assertSame(Employee::STATUS_WORKING, $employee->status);
        $this->assertTrue($employee->isWorking());
        $this->assertNull($employee->terminated_at);
    }

    public function test_terminated_at_is_inferred_from_latest_update_for_resigned_employee(): void
    {
        $terminatedAt = Carbon::parse('2026-06-02 10:30:00');
        $employee = new Employee([
            'status' => Employee::STATUS_RESIGNED,
            'updated_at' => $terminatedAt,
        ]);

        $this->assertFalse($employee->isWorking());
        $this->assertTrue($terminatedAt->equalTo($employee->terminated_at));
    }
}
