<?php

namespace App\Modules\Ecommerce\Exceptions;

use RuntimeException;

/** Passage de statut interdit (le message est destiné à l'équipe). */
class InvalidOrderTransition extends RuntimeException {}
