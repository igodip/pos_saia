<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case WAREHOUSE = 'warehouse';
    case ACCOUNTING = 'accounting';
    case VIEWER = 'viewer';
}
