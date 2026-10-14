<?php

namespace App\Modules\Ecommerce\Exceptions;

use RuntimeException;

/** Un code promo existe mais ne peut pas être utilisé (le message est destiné au client). */
class PromoCodeException extends RuntimeException {}
