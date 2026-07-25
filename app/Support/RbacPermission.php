<?php

declare(strict_types=1);

namespace App\Support;

final class RbacPermission
{
    public const ACCESS_ADMIN_PANEL = 'access_admin_panel';

    public const ASSIGN_STAFF_ROLES = 'assign_staff_roles';

    public const EXPORT_PAYMENTS = 'export_payments';

    public const IMPERSONATE_TENANT = 'impersonate_tenant';

    public const MANAGE_BACKUPS = 'manage_backups';

    public const MANAGE_FAILED_JOBS = 'manage_failed_jobs';

    public const MANAGE_FEATURE_FLAGS = 'manage_feature_flags';

    public const PURGE_TENANT = 'purge_tenant';

    public const REFUND_PAYMENT = 'refund_payment';

    public const REPLAY_WEBHOOKS = 'replay_webhooks';

    public const VIEW_SYSTEM_LOGS = 'view_system_logs';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ACCESS_ADMIN_PANEL => 'Access Admin Panel',
            self::ASSIGN_STAFF_ROLES => 'Assign Roles to Staff',
            self::EXPORT_PAYMENTS => 'Export Payments',
            self::IMPERSONATE_TENANT => 'Impersonate Tenants',
            self::MANAGE_BACKUPS => 'Manage System Backups',
            self::MANAGE_FAILED_JOBS => 'Manage Failed Jobs',
            self::MANAGE_FEATURE_FLAGS => 'Manage Tenant Feature Flags',
            self::PURGE_TENANT => 'Permanently Purge Tenants',
            self::REFUND_PAYMENT => 'Refund Payments',
            self::REPLAY_WEBHOOKS => 'Replay Webhooks',
            self::VIEW_SYSTEM_LOGS => 'View System Logs',
        ];
    }
}
