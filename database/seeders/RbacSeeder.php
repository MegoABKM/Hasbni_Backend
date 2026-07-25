<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Support\RbacPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (array_keys(RbacPermission::labels()) as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            $superAdmin = Role::findOrCreate('super_admin', 'web');
            $financeAdmin = Role::findOrCreate('finance_admin', 'web');
            $supportAdmin = Role::findOrCreate('support_admin', 'web');

            $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
            $financeAdmin->syncPermissions($this->existingPermissions($this->financePermissions()));
            $supportAdmin->syncPermissions($this->existingPermissions($this->supportPermissions()));

            User::query()
                ->whereIn('role', ['super_admin', 'support_admin', 'finance_admin'])
                ->eachById(function (User $user): void {
                    $legacyRole = (string) $user->getRawOriginal('role');

                    $user->forceFill(['account_type' => User::ACCOUNT_TYPE_STAFF])->saveQuietly();
                    $user->syncRoles([$legacyRole]);
                });

            User::query()
                ->where('role', 'tenant')
                ->update(['account_type' => User::ACCOUNT_TYPE_TENANT]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * @param  array<int, string>  $names
     */
    private function existingPermissions(array $names)
    {
        return Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private function financePermissions(): array
    {
        return [
            RbacPermission::ACCESS_ADMIN_PANEL,
            RbacPermission::EXPORT_PAYMENTS,
            RbacPermission::REFUND_PAYMENT,
            'ViewAny:PaymentResource',
            'View:PaymentResource',
            'Create:PaymentResource',
            'Update:PaymentResource',
            'ViewAny:PlanResource',
            'View:PlanResource',
            'Create:PlanResource',
            'Update:PlanResource',
            'ViewAny:PromoCodeResource',
            'View:PromoCodeResource',
            'Create:PromoCodeResource',
            'Update:PromoCodeResource',
            'Delete:PromoCodeResource',
            'ViewAny:SubscriptionResource',
            'View:SubscriptionResource',
            'Create:SubscriptionResource',
            'Update:SubscriptionResource',
            'Delete:SubscriptionResource',
            'ViewAny:UserResource',
            'View:UserResource',
            'View:SaasAnalytics',
            'View:ExecutiveDecisionSupportWidget',
            'View:SaaSCountryAnalyticsWidget',
            'View:KpiHomeMrrTrendChart',
            'View:KpiHomeOverviewWidget',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function supportPermissions(): array
    {
        return [
            RbacPermission::ACCESS_ADMIN_PANEL,
            'ViewAny:AnnouncementResource',
            'View:AnnouncementResource',
            'Create:AnnouncementResource',
            'Update:AnnouncementResource',
            'Delete:AnnouncementResource',
            'ViewAny:FaqResource',
            'View:FaqResource',
            'Create:FaqResource',
            'Update:FaqResource',
            'Delete:FaqResource',
            'ViewAny:InstructionResource',
            'View:InstructionResource',
            'Create:InstructionResource',
            'Update:InstructionResource',
            'Delete:InstructionResource',
            'ViewAny:SupportTicketResource',
            'View:SupportTicketResource',
            'Update:SupportTicketResource',
            'ViewAny:UserResource',
            'View:UserResource',
            'View:EmailCampaigns',
            'View:PushNotifications',
        ];
    }
}
