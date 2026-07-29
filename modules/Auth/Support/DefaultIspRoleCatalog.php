<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

final class DefaultIspRoleCatalog
{
    /**
     * @return list<array{
     *     name: string,
     *     slug: string,
     *     level: int,
     *     description: string,
     *     permissions: list<string>
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'ISP Manager',
                'slug' => 'isp-manager',
                'level' => 8,
                'description' => 'Runs daily ISP operations without identity, backup, or database administration.',
                'permissions' => self::managerPermissions(),
            ],
            [
                'name' => 'Billing Officer',
                'slug' => 'billing-officer',
                'level' => 6,
                'description' => 'Manages subscriptions, invoices, payments, credits, taxes, and customer ledgers.',
                'permissions' => self::billingPermissions(),
            ],
            [
                'name' => 'Customer Support',
                'slug' => 'customer-support',
                'level' => 5,
                'description' => 'Maintains customers and services while financial records remain read-only.',
                'permissions' => self::supportPermissions(),
            ],
            [
                'name' => 'Network Operator',
                'slug' => 'network-operator',
                'level' => 5,
                'description' => 'Operates PPPoE, NAS, IP pools, RADIUS sessions, and network diagnostics.',
                'permissions' => self::networkPermissions(),
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'level' => 2,
                'description' => 'Read-only access to operational, financial, security, and regulatory records.',
                'permissions' => self::auditorPermissions(),
            ],
            [
                'name' => 'POP Manager',
                'slug' => 'pop-manager',
                'level' => 4,
                'description' => 'Manages only customers and PPPoE accounts owned by their POP and views generated POP bills.',
                'permissions' => self::popManagerPermissions(),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private static function managerPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view', 'notifications.manage', 'activity.view', 'security.view',
            'users.view', 'users.create', 'users.edit',
            'customer_groups.view', 'customer_groups.manage',
            'customers.view', 'customers.manage', 'customers.import', 'customers.export',
            'customers.status.change', 'customers.documents.manage', 'customers.ledger.view',
            'customers.ledger.manage', 'customers.payments.view', 'customers.payments.manage',
            'customers.portal.manage', 'customers.audit.view',
            'customer_addresses.view', 'customer_addresses.manage',
            'customer_documents.view', 'customer_documents.manage',
            'customer_pppoe.view', 'customer_pppoe.manage', 'customer_pppoe.username.update',
            'customer_import_export.manage', 'customer_subscriptions.view', 'customer_subscriptions.manage',
            'service_plans.view', 'service_plans.manage',
            'payments.view', 'payments.manage', 'invoices.view', 'invoices.manage',
            'credit_notes.view', 'credit_notes.manage', 'adjustments.view', 'adjustments.manage',
            'discounts.view', 'discounts.manage', 'tax_rates.view', 'tax_rates.manage',
            'reports.view', 'btrc_dis.export', 'settings.view',
            'nas.view', 'nas.manage', 'ip_pools.view', 'ip_pools.manage',
            'radius_sessions.view', 'radius_sessions.disconnect',
            'radius_logs.view', 'radius_accounting.view',
            'pop_reports.view', 'pop_reports.generate',
        ];
    }

    /**
     * @return list<string>
     */
    private static function billingPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view',
            'customers.view', 'customers.export', 'customers.ledger.view', 'customers.ledger.manage',
            'customers.payments.view', 'customers.payments.manage', 'customers.audit.view',
            'customer_subscriptions.view', 'customer_subscriptions.manage',
            'service_plans.view', 'payments.view', 'payments.manage', 'invoices.view', 'invoices.manage',
            'credit_notes.view', 'credit_notes.manage', 'adjustments.view', 'adjustments.manage',
            'discounts.view', 'discounts.manage', 'tax_rates.view', 'tax_rates.manage',
            'reports.view',
        ];
    }

    /**
     * @return list<string>
     */
    private static function supportPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view',
            'customer_groups.view', 'customers.view', 'customers.manage', 'customers.status.change',
            'customers.documents.manage', 'customers.ledger.view', 'customers.payments.view',
            'customers.portal.manage', 'customers.audit.view',
            'customer_addresses.view', 'customer_addresses.manage',
            'customer_documents.view', 'customer_documents.manage',
            'customer_pppoe.view', 'customer_pppoe.manage', 'customer_pppoe.username.update',
            'customer_subscriptions.view', 'customer_subscriptions.manage', 'service_plans.view',
            'payments.view', 'invoices.view', 'credit_notes.view',
            'nas.view', 'ip_pools.view',
            'radius_sessions.view', 'radius_sessions.disconnect',
            'radius_logs.view', 'radius_accounting.view',
        ];
    }

    /**
     * @return list<string>
     */
    private static function networkPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view',
            'customers.view', 'customer_pppoe.view', 'customer_pppoe.manage',
            'customer_pppoe.username.update', 'customer_subscriptions.view', 'service_plans.view',
            'nas.view', 'nas.manage', 'ip_pools.view', 'ip_pools.manage',
            'radius_sessions.view', 'radius_sessions.disconnect',
            'radius_logs.view', 'radius_accounting.view', 'reports.view',
        ];
    }

    /**
     * @return list<string>
     */
    private static function auditorPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view', 'activity.view', 'security.view',
            'users.view', 'roles.view', 'permissions.view', 'settings.view', 'database_backup.view',
            'customer_groups.view', 'customers.view', 'customers.export',
            'customers.ledger.view', 'customers.payments.view', 'customers.audit.view',
            'customer_addresses.view', 'customer_documents.view', 'customer_pppoe.view',
            'customer_subscriptions.view', 'service_plans.view',
            'payments.view', 'invoices.view', 'credit_notes.view', 'adjustments.view',
            'discounts.view', 'tax_rates.view', 'reports.view', 'btrc_dis.export',
            'nas.view', 'ip_pools.view', 'radius_sessions.view',
            'radius_logs.view', 'radius_accounting.view',
        ];
    }

    /**
     * @return list<string>
     */
    private static function popManagerPermissions(): array
    {
        return [
            'dashboard.view', 'notifications.view',
            'customers.view', 'customers.manage', 'customers.status.change',
            'customer_addresses.view', 'customer_addresses.manage',
            'customer_documents.view', 'customer_documents.manage',
            'customer_pppoe.view', 'customer_pppoe.manage',
            'customer_subscriptions.view', 'customer_subscriptions.manage',
            'service_plans.consume',
            'ip_pools.view', 'pop_reports.view',
        ];
    }
}
