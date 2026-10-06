<?php

namespace App\Support\Launch\Checks;

use RuntimeException;

/**
 * The check run ran out of its time budget before this check could finish.
 */
final class BudgetExceeded extends RuntimeException {}
