<?php

namespace App\Support\Auth;

enum LoginCodeCheck
{
    case Valid;
    case Wrong;
    case Exhausted;
    case Expired;
}
