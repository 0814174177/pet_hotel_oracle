<?php

namespace App\Repositories\Contracts\Ceo;

interface CeoFinanceRepositoryInterface
{
    public function getFinanceData(array $filters = []): array;
}
