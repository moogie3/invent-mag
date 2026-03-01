<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a tenant attempts to exceed a plan-imposed resource limit.
 * Used as defense-in-depth in service layer (UserService, WarehouseService).
 * The middleware layer (CheckPlanLimit) handles the HTTP-level blocking.
 */
class PlanLimitExceededException extends RuntimeException
{
    //
}
