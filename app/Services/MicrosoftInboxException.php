<?php

namespace App\Services;

/** A safe, actionable inbox error that can be displayed without provider secrets. */
class MicrosoftInboxException extends \RuntimeException {}
