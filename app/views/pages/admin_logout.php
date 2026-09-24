<?php
declare(strict_types=1);

admin_logout();
header('Location: ' . path('admin/login'));
exit;
