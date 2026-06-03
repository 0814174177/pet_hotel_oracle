<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    public const STATUS_RESIGNED = 0;

    public const STATUS_WORKING = 1;

    protected $table = 'employee';
    protected $primaryKey = 'employee_id';

    protected $guarded = [];

    protected $attributes = [
        'status' => self::STATUS_WORKING,
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'birthday' => 'date',
            'status' => 'integer',
        ];
    }

    public function scopeWorking(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WORKING);
    }

    public function isWorking(): bool
    {
        return $this->status === self::STATUS_WORKING;
    }

    public function isManagerPosition(): bool
    {
        return strtoupper((string) $this->position) === 'MANAGER';
    }

    public function getTerminatedAtAttribute(): ?Carbon
    {
        return $this->isWorking() ? null : $this->updated_at;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'employee_id', 'employee_id');
    }

    public function bookingServicePets()
    {
        return $this->hasMany(BookingServicePet::class, 'employee_id', 'employee_id');
    }

    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by_emp', 'employee_id');
    }
}
