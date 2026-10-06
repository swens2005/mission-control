<?php

namespace App\Support\Proofmark;

use RuntimeException;

/**
 * An upload Proofmark won't store. The message is shown to the user.
 */
final class RejectedImage extends RuntimeException {}
