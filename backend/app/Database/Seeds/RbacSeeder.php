<?php

namespace App\Database\Seeds;

use Config\PermissionCatalog;
use Throwable;

class RbacSeeder extends BaseAppSeeder
{
    /**
     * @var array<string,list<string>>
     */
    private array $rolePermissionMap = [
        'super_admin' => ['*'],
        'company_admin' => [
            'auth.me.view',
            'auth.logout',
            'auth.session.list',
            'auth.session.revoke',
            'auth.session.revoke.all',
            'profile.view',
            'profile.update',
            'profile.password.change',
            'user.role.assign',
            'user.role.revoke',
            'site.list',
            'site.create',
            'site.view',
            'site.update',
            'site.delete',
            'block.list',
            'block.create',
            'block.view',
            'block.update',
            'block.delete',
            'floor.list',
            'floor.create',
            'floor.view',
            'floor.update',
            'floor.delete',
            'unit.list',
            'unit.create',
            'unit.view',
            'unit.update',
            'unit.delete',
            'resident.list',
            'resident.create',
            'resident.view',
            'resident.update',
            'resident.delete',
            'unit_occupancy.list',
            'unit_occupancy.create',
            'unit_occupancy.view',
            'unit_occupancy.update',
            'unit_occupancy.delete',
            'resident_contact.list',
            'resident_contact.create',
            'resident_contact.update',
            'resident_contact.delete',
            'resident_vehicle.list',
            'resident_vehicle.create',
            'resident_vehicle.view',
            'resident_vehicle.update',
            'resident_vehicle.delete',
            'due_definition.list',
            'due_definition.create',
            'due_definition.view',
            'due_definition.update',
            'due_definition.delete',
            'due_period.list',
            'due_period.create',
            'due_period.view',
            'due_period.update',
            'due_period.delete',
            'due_period.close',
            'due_period.lock',
            'due_batch.list',
            'due_batch.view',
            'due_batch.create',
            'due_item.list',
            'due_item.view',
            'due_item.update',
            'due_item.cancel',
            'payment.list',
            'payment.create_manual',
            'payment.view',
            'payment.cancel',
            'payment_event.list',
            'payment_event.view',
            'deposit.list',
            'deposit.create',
            'deposit.view',
            'deposit.update',
            'deposit.receive',
            'deposit.refund',
            'deposit.deduct',
            'deposit.apply_to_debt',
            'deposit.cancel',
            'deposit_transaction.list',
            'deposit_transaction.view',
            'request_category.list',
            'request_category.create',
            'request_category.view',
            'request_category.update',
            'request_category.delete',
            'service_request.list',
            'service_request.create',
            'service_request.view',
            'service_request.update',
            'service_request.assign',
            'service_request.resolve',
            'service_request.close',
            'service_request.cancel',
            'service_request_comment.list',
            'service_request_comment.create',
            'service_request_file.list',
            'service_request_file.create',
            'service_request_file.delete',
            'work_order.list',
            'work_order.create',
            'work_order.view',
            'work_order.update',
            'work_order.start',
            'work_order.complete',
            'work_order.cancel',
            'notification_template.list',
            'notification_template.create',
            'notification_template.view',
            'notification_template.update',
            'notification_template.delete',
            'notification_message.list',
            'notification_message.create',
            'notification_message.view',
            'notification_message.queue',
            'notification_message.cancel',
            'notification_recipient.list',
            'notification_recipient.view',
            'notification_recipient.mark_read',
            'notification_delivery_log.list',
            'notification_delivery_log.view',
            'communication_provider.list',
            'communication_provider.create',
            'communication_provider.view',
            'communication_provider.update',
            'communication_provider.delete',
            'communication_provider.set_default',
            'announcement.list',
            'announcement.create',
            'announcement.view',
            'announcement.update',
            'announcement.delete',
            'announcement.publish',
            'announcement.archive',
            'announcement.cancel',
            'announcement.mark_read',
            'announcement.reads.list',
            'announcement.targets.list',
            'common_area.list',
            'common_area.create',
            'common_area.view',
            'common_area.update',
            'common_area.delete',
            'common_area_reservation.list',
            'common_area_reservation.create',
            'common_area_reservation.view',
            'common_area_reservation.update',
            'common_area_reservation.approve',
            'common_area_reservation.reject',
            'common_area_reservation.cancel',
            'common_area_reservation.complete',
            'meter.list',
            'meter.create',
            'meter.view',
            'meter.update',
            'meter.delete',
            'meter_period.list',
            'meter_period.create',
            'meter_period.view',
            'meter_period.update',
            'meter_period.close',
            'meter_period.lock',
            'meter_reading.list',
            'meter_reading.create',
            'meter_reading.view',
            'meter_reading.update',
            'meter_reading.approve',
            'meter_reading.reject',
            'meter_reading.cancel',
            'consumption_report.list',
            'consumption_report.view',
            'consumption_report.generate',
            'consumption_report.cancel',
            'asset.list',
            'asset.create',
            'asset.view',
            'asset.update',
            'asset.delete',
            'asset_maintenance_plan.list',
            'asset_maintenance_plan.create',
            'asset_maintenance_plan.view',
            'asset_maintenance_plan.update',
            'asset_maintenance_plan.pause',
            'asset_maintenance_plan.resume',
            'asset_maintenance_plan.cancel',
            'asset_maintenance_record.list',
            'asset_maintenance_record.create',
            'asset_maintenance_record.view',
            'asset_maintenance_record.cancel',
            'visitor_invite.list',
            'visitor_invite.create',
            'visitor_invite.view',
            'visitor_invite.cancel',
            'visitor_entry.list',
            'visitor_entry.check_in',
            'visitor_entry.check_out',
            'visitor_entry.view',
            'security_incident.list',
            'security_incident.create',
            'security_incident.view',
            'security_incident.update',
            'security_incident.resolve',
            'security_incident.close',
            'security_incident.cancel',
            'vehicle_access_list.list',
            'vehicle_access_list.create',
            'vehicle_access_list.view',
            'vehicle_access_list.update',
            'vehicle_access_list.delete',
            'staff_profile.list',
            'staff_profile.create',
            'staff_profile.view',
            'staff_profile.update',
            'staff_profile.delete',
            'staff_assignment.list',
            'staff_assignment.create',
            'staff_assignment.view',
            'staff_assignment.update',
            'staff_assignment.delete',
            'staff_shift.list',
            'staff_shift.create',
            'staff_shift.view',
            'staff_shift.update',
            'staff_shift.start',
            'staff_shift.complete',
            'staff_shift.cancel',
            'staff_task.list',
            'staff_task.create',
            'staff_task.view',
            'staff_task.update',
            'staff_task.assign',
            'staff_task.start',
            'staff_task.complete',
            'staff_task.cancel',
            'document_category.list',
            'document_category.create',
            'document_category.view',
            'document_category.update',
            'document_category.delete',
            'document.list',
            'document.create',
            'document.view',
            'document.update',
            'document.archive',
            'document.restore',
            'document.delete',
            'document_version.list',
            'document_version.create',
            'document_version.view',
            'document_access_rule.list',
            'document_access_rule.create',
            'document_access_rule.delete',
            'meeting.list',
            'meeting.create',
            'meeting.view',
            'meeting.update',
            'meeting.publish',
            'meeting.complete',
            'meeting.cancel',
            'meeting.lock',
            'meeting_agenda.list',
            'meeting_agenda.create',
            'meeting_agenda.update',
            'meeting_agenda.delete',
            'meeting_attendee.list',
            'meeting_attendee.create',
            'meeting_attendee.update',
            'meeting_attendee.sign',
            'meeting_attendee.delete',
            'decision_book.list',
            'decision_book.create',
            'decision_book.view',
            'decision_book.update',
            'decision_book.approve',
            'decision_book.lock',
            'decision_book.cancel',
            'legal_case.list',
            'legal_case.create',
            'legal_case.view',
            'legal_case.update',
            'legal_case.send_to_lawyer',
            'legal_case.file',
            'legal_case.mark_paid',
            'legal_case.close',
            'legal_case.cancel',
            'legal_case_debt.list',
            'legal_case_debt.create',
            'legal_case_debt.delete',
            'legal_case_event.list',
            'legal_case_event.create',
            'legal_case_document.list',
            'legal_case_document.create',
            'legal_case_document.delete',
        ],
        'employee' => [
            'auth.me.view',
            'auth.logout',
            'profile.view',
            'profile.update',
            'profile.password.change',
            'site.list',
            'site.view',
            'block.list',
            'block.view',
            'floor.list',
            'floor.view',
            'unit.list',
            'unit.view',
            'resident.list',
            'resident.view',
            'unit_occupancy.list',
            'unit_occupancy.view',
            'resident_contact.list',
            'resident_vehicle.list',
            'resident_vehicle.view',
            'due_definition.list',
            'due_definition.view',
            'due_period.list',
            'due_period.view',
            'due_batch.list',
            'due_batch.view',
            'due_item.list',
            'due_item.view',
            'payment.list',
            'payment.view',
            'payment_event.list',
            'payment_event.view',
            'deposit.list',
            'deposit.view',
            'deposit_transaction.list',
            'deposit_transaction.view',
            'request_category.list',
            'request_category.view',
            'service_request.list',
            'service_request.create',
            'service_request.view',
            'service_request_comment.list',
            'service_request_comment.create',
            'service_request_file.list',
            'service_request_file.create',
            'work_order.list',
            'work_order.view',
            'notification_template.list',
            'notification_template.view',
            'notification_message.list',
            'notification_message.create',
            'notification_message.view',
            'notification_message.queue',
            'notification_message.cancel',
            'notification_recipient.list',
            'notification_recipient.view',
            'notification_recipient.mark_read',
            'notification_delivery_log.list',
            'notification_delivery_log.view',
            'communication_provider.list',
            'communication_provider.view',
            'announcement.list',
            'announcement.view',
            'announcement.mark_read',
            'announcement.reads.list',
            'announcement.targets.list',
            'common_area.list',
            'common_area.view',
            'common_area_reservation.list',
            'common_area_reservation.create',
            'common_area_reservation.view',
            'common_area_reservation.cancel',
            'meter.list',
            'meter.view',
            'meter_reading.list',
            'meter_reading.create',
            'meter_reading.view',
            'consumption_report.list',
            'consumption_report.view',
            'asset.list',
            'asset.view',
            'asset_maintenance_plan.list',
            'asset_maintenance_plan.view',
            'asset_maintenance_record.list',
            'asset_maintenance_record.create',
            'asset_maintenance_record.view',
            'visitor_invite.list',
            'visitor_invite.create',
            'visitor_invite.view',
            'visitor_entry.list',
            'visitor_entry.check_in',
            'visitor_entry.check_out',
            'visitor_entry.view',
            'security_incident.list',
            'security_incident.create',
            'security_incident.view',
            'vehicle_access_list.list',
            'vehicle_access_list.view',
            'staff_profile.list',
            'staff_profile.view',
            'staff_assignment.list',
            'staff_assignment.view',
            'staff_shift.list',
            'staff_shift.view',
            'staff_shift.start',
            'staff_shift.complete',
            'staff_shift.cancel',
            'staff_task.list',
            'staff_task.create',
            'staff_task.view',
            'staff_task.update',
            'staff_task.assign',
            'staff_task.start',
            'staff_task.complete',
            'staff_task.cancel',
            'document_category.list',
            'document_category.view',
            'document.list',
            'document.create',
            'document.view',
            'document_version.list',
            'document_version.create',
            'document_version.view',
            'document_access_rule.list',
            'meeting.list',
            'meeting.view',
            'meeting_agenda.list',
            'meeting_attendee.list',
            'meeting_attendee.sign',
            'decision_book.list',
            'decision_book.view',
            'legal_case.list',
            'legal_case.view',
            'legal_case_debt.list',
            'legal_case_event.list',
            'legal_case_document.list',
        ],
    ];

    /**
     * @return array{roles:int,permissions:int,role_permissions:int}
     */
    public function run(): array
    {
        $name = static::class;
        $this->logStart($name);

        try {
            $roleCodes = [
                'super_admin'  => 'Super Admin',
                'company_admin' => 'Company Admin',
                'employee'     => 'Employee',
            ];

            $catalog = new PermissionCatalog();
            $catalogRows = $catalog->all();

            $roleIds = [];
            foreach ($roleCodes as $code => $label) {
                $roleIds[$code] = $this->upsertRole($code, $label);
            }

            $permissionIds = [];
            foreach ($catalogRows as $permission) {
                $code = (string) $permission['code'];
                $permissionIds[$code] = $this->upsertPermission($permission);
            }

            $this->deactivateCatalogExternalPermissions(array_keys($permissionIds));
            $resolvedRolePermissionMap = $this->resolveRolePermissionMap(array_keys($permissionIds));

            $linked = 0;
            foreach ($resolvedRolePermissionMap as $roleCode => $permissionList) {
                $roleId = $roleIds[$roleCode];

                foreach ($permissionList as $permissionCode) {
                    if (! isset($permissionIds[$permissionCode])) {
                        continue;
                    }

                    $permissionId = $permissionIds[$permissionCode];
                    $linked += $this->upsertRolePermission($roleId, $permissionId);
                }
            }

            $result = [
                'roles'            => count($roleIds),
                'permissions'      => count($permissionIds),
                'role_permissions' => $linked,
            ];

            $this->logSuccess($name, json_encode($result, JSON_UNESCAPED_SLASHES));

            return $result;
        } catch (Throwable $e) {
            $this->logFailure($name, $e->getMessage());
            throw $e;
        }
    }

    private function upsertRole(string $code, string $name): int
    {
        $builder = $this->db->table('roles');
        $existing = $builder
            ->where('company_id', null)
            ->where('code', $code)
            ->get()
            ->getRowArray();

        $now = $this->now();

        if ($existing !== null) {
            $builder->where('id', $existing['id'])->update([
                'name'       => $name,
                'updated_at' => $now,
            ]);

            return (int) $existing['id'];
        }

        $builder->insert([
            'company_id'  => null,
            'code'        => $code,
            'name'        => $name,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array{code:string,label:string,scope:string,description:string,is_active:bool} $permission
     */
    private function upsertPermission(array $permission): int
    {
        $builder = $this->db->table('permissions');
        $code = (string) $permission['code'];
        $existing = $builder->where('code', $code)->get()->getRowArray();
        $now = $this->now();

        if ($existing !== null) {
            $builder->where('id', $existing['id'])->update([
                'name'       => (string) $permission['label'],
                'scope'      => (string) $permission['scope'],
                'is_active'  => (bool) $permission['is_active'] ? 1 : 0,
                'deprecated_at' => null,
                'updated_at' => $now,
            ]);

            return (int) $existing['id'];
        }

        $builder->insert([
            'code'       => $code,
            'name'       => (string) $permission['label'],
            'scope'      => (string) $permission['scope'],
            'is_active'  => (bool) $permission['is_active'] ? 1 : 0,
            'deprecated_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param list<string> $catalogCodes
     */
    private function deactivateCatalogExternalPermissions(array $catalogCodes): void
    {
        $rows = $this->db->table('permissions')
            ->select('id, code')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        $catalogLookup = array_fill_keys($catalogCodes, true);
        $now = $this->now();

        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            if ($code === '' || isset($catalogLookup[$code])) {
                continue;
            }

            $this->db->table('permissions')
                ->where('id', (int) $row['id'])
                ->update([
                    'is_active' => 0,
                    'deprecated_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    /**
     * @param list<string> $catalogCodes
     * @return array<string,list<string>>
     */
    private function resolveRolePermissionMap(array $catalogCodes): array
    {
        $allCompanyScopeCodes = [];
        $catalog = new PermissionCatalog();
        foreach ($catalog->all() as $permission) {
            if (($permission['scope'] ?? 'company') !== 'company') {
                continue;
            }
            $allCompanyScopeCodes[] = (string) $permission['code'];
        }

        $map = $this->rolePermissionMap;
        if (($map['super_admin'] ?? []) === ['*']) {
            $map['super_admin'] = $catalogCodes;
        }

        // Company admin must never receive system-scoped permissions.
        $map['company_admin'] = array_values(array_intersect($map['company_admin'] ?? [], $allCompanyScopeCodes));

        return $map;
    }

    private function upsertRolePermission(int $roleId, int $permissionId): int
    {
        $builder = $this->db->table('role_permissions');
        $existing = $builder
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            $builder->where('id', (int) $existing['id'])->update([
                'is_active' => 1,
                'deleted_at' => null,
                'updated_at' => $this->now(),
            ]);
            return 0;
        }

        $now = $this->now();
        $builder->insert([
            'role_id'      => $roleId,
            'permission_id'=> $permissionId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return 1;
    }
}
