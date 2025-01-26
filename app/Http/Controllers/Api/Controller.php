<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use lbuchs\WebAuthn\WebAuthn;

abstract class Controller
{
    public function __construct(
        protected WebAuthn $webAuthnClient,
        protected \Illuminate\Log\LogManager $logger,
    ) {}
}
