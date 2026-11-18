<?php

namespace App\Support\Ai;

use RuntimeException;

/**
 * The assistant can't draft right now. The message is written for the
 * studio and shown as it is.
 */
final class AssistantUnavailable extends RuntimeException {}
