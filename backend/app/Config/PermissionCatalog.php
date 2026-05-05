<?php

namespace Config;

use App\Exceptions\InvalidPermissionCodeException;
use App\Exceptions\PermissionNotFoundException;
use CodeIgniter\Config\BaseConfig;

class PermissionCatalog extends BaseConfig
{
    private const CODE_REGEX = '/^[a-z][a-z_]*\.[a-z][a-z_]*(\.[a-z][a-z_]*)*$/';

    /**
     * @var list<array{code:string,label:string,scope:string,description:string,is_active:bool}>
     */
    private array $permissions = [
        [
            'code' => 'auth.me.view',
            'label' => 'Auth Me View',
            'scope' => 'company',
            'description' => 'Authenticated kullanici profilini goruntuleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.logout',
            'label' => 'Auth Logout',
            'scope' => 'company',
            'description' => 'Aktif oturumu sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.list',
            'label' => 'Auth Session List',
            'scope' => 'company',
            'description' => 'Kullanicinin oturumlarini listeleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.revoke',
            'label' => 'Auth Session Revoke',
            'scope' => 'company',
            'description' => 'Belirli bir oturumu sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'auth.session.revoke.all',
            'label' => 'Auth Session Revoke All',
            'scope' => 'company',
            'description' => 'Tum oturumlari sonlandirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.view',
            'label' => 'Profile View',
            'scope' => 'company',
            'description' => 'Kullanici profilini goruntuleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.update',
            'label' => 'Profile Update',
            'scope' => 'company',
            'description' => 'Kullanici profilini guncelleme izni',
            'is_active' => true,
        ],
        [
            'code' => 'profile.password.change',
            'label' => 'Profile Password Change',
            'scope' => 'company',
            'description' => 'Kullanici sifresi degistirme izni',
            'is_active' => true,
        ],
        [
            'code' => 'user.role.assign',
            'label' => 'User Role Assign',
            'scope' => 'company',
            'description' => 'Kullaniciya rol atama izni',
            'is_active' => true,
        ],
        [
            'code' => 'user.role.revoke',
            'label' => 'User Role Revoke',
            'scope' => 'company',
            'description' => 'Kullanicidan rol kaldirma izni',
            'is_active' => true,
        ],
        [
            'code' => 'permission.manage',
            'label' => 'Permission Manage',
            'scope' => 'system',
            'description' => 'Permission envanteri yonetim izni',
            'is_active' => true,
        ],
        ['code' => 'site.list', 'label' => 'Site List', 'scope' => 'company', 'description' => 'Site listeleme izni', 'is_active' => true],
        ['code' => 'site.create', 'label' => 'Site Create', 'scope' => 'company', 'description' => 'Site olusturma izni', 'is_active' => true],
        ['code' => 'site.view', 'label' => 'Site View', 'scope' => 'company', 'description' => 'Site detay goruntuleme izni', 'is_active' => true],
        ['code' => 'site.update', 'label' => 'Site Update', 'scope' => 'company', 'description' => 'Site guncelleme izni', 'is_active' => true],
        ['code' => 'site.delete', 'label' => 'Site Delete', 'scope' => 'company', 'description' => 'Site silme izni', 'is_active' => true],
        ['code' => 'site.export', 'label' => 'Site Export', 'scope' => 'company', 'description' => 'Site excel disa aktarma izni', 'is_active' => true],
        ['code' => 'site.import', 'label' => 'Site Import', 'scope' => 'company', 'description' => 'Site excel ice aktarma izni', 'is_active' => true],
        ['code' => 'block.list', 'label' => 'Block List', 'scope' => 'company', 'description' => 'Blok listeleme izni', 'is_active' => true],
        ['code' => 'block.create', 'label' => 'Block Create', 'scope' => 'company', 'description' => 'Blok olusturma izni', 'is_active' => true],
        ['code' => 'block.view', 'label' => 'Block View', 'scope' => 'company', 'description' => 'Blok detay goruntuleme izni', 'is_active' => true],
        ['code' => 'block.update', 'label' => 'Block Update', 'scope' => 'company', 'description' => 'Blok guncelleme izni', 'is_active' => true],
        ['code' => 'block.delete', 'label' => 'Block Delete', 'scope' => 'company', 'description' => 'Blok silme izni', 'is_active' => true],
        ['code' => 'block.export', 'label' => 'Block Export', 'scope' => 'company', 'description' => 'Blok excel disa aktarma izni', 'is_active' => true],
        ['code' => 'block.import', 'label' => 'Block Import', 'scope' => 'company', 'description' => 'Blok excel ice aktarma izni', 'is_active' => true],
        ['code' => 'floor.list', 'label' => 'Floor List', 'scope' => 'company', 'description' => 'Kat listeleme izni', 'is_active' => true],
        ['code' => 'floor.create', 'label' => 'Floor Create', 'scope' => 'company', 'description' => 'Kat olusturma izni', 'is_active' => true],
        ['code' => 'floor.view', 'label' => 'Floor View', 'scope' => 'company', 'description' => 'Kat detay goruntuleme izni', 'is_active' => true],
        ['code' => 'floor.update', 'label' => 'Floor Update', 'scope' => 'company', 'description' => 'Kat guncelleme izni', 'is_active' => true],
        ['code' => 'floor.delete', 'label' => 'Floor Delete', 'scope' => 'company', 'description' => 'Kat silme izni', 'is_active' => true],
        ['code' => 'floor.export', 'label' => 'Floor Export', 'scope' => 'company', 'description' => 'Kat excel disa aktarma izni', 'is_active' => true],
        ['code' => 'floor.import', 'label' => 'Floor Import', 'scope' => 'company', 'description' => 'Kat excel ice aktarma izni', 'is_active' => true],
        ['code' => 'unit.list', 'label' => 'Unit List', 'scope' => 'company', 'description' => 'Bagimsiz bolum listeleme izni', 'is_active' => true],
        ['code' => 'unit.create', 'label' => 'Unit Create', 'scope' => 'company', 'description' => 'Bagimsiz bolum olusturma izni', 'is_active' => true],
        ['code' => 'unit.view', 'label' => 'Unit View', 'scope' => 'company', 'description' => 'Bagimsiz bolum detay goruntuleme izni', 'is_active' => true],
        ['code' => 'unit.update', 'label' => 'Unit Update', 'scope' => 'company', 'description' => 'Bagimsiz bolum guncelleme izni', 'is_active' => true],
        ['code' => 'unit.delete', 'label' => 'Unit Delete', 'scope' => 'company', 'description' => 'Bagimsiz bolum silme izni', 'is_active' => true],
        ['code' => 'unit.export', 'label' => 'Unit Export', 'scope' => 'company', 'description' => 'Bagimsiz bolum excel disa aktarma izni', 'is_active' => true],
        ['code' => 'unit.import', 'label' => 'Unit Import', 'scope' => 'company', 'description' => 'Bagimsiz bolum excel ice aktarma izni', 'is_active' => true],
        ['code' => 'resident.list', 'label' => 'Resident List', 'scope' => 'company', 'description' => 'Resident profile listeleme izni', 'is_active' => true],
        ['code' => 'resident.create', 'label' => 'Resident Create', 'scope' => 'company', 'description' => 'Resident profile olusturma izni', 'is_active' => true],
        ['code' => 'resident.view', 'label' => 'Resident View', 'scope' => 'company', 'description' => 'Resident profile detay izni', 'is_active' => true],
        ['code' => 'resident.update', 'label' => 'Resident Update', 'scope' => 'company', 'description' => 'Resident profile guncelleme izni', 'is_active' => true],
        ['code' => 'resident.delete', 'label' => 'Resident Delete', 'scope' => 'company', 'description' => 'Resident profile silme izni', 'is_active' => true],
        ['code' => 'unit_occupancy.list', 'label' => 'Unit Occupancy List', 'scope' => 'company', 'description' => 'Unit occupancy listeleme izni', 'is_active' => true],
        ['code' => 'unit_occupancy.create', 'label' => 'Unit Occupancy Create', 'scope' => 'company', 'description' => 'Unit occupancy olusturma izni', 'is_active' => true],
        ['code' => 'unit_occupancy.view', 'label' => 'Unit Occupancy View', 'scope' => 'company', 'description' => 'Unit occupancy detay izni', 'is_active' => true],
        ['code' => 'unit_occupancy.update', 'label' => 'Unit Occupancy Update', 'scope' => 'company', 'description' => 'Unit occupancy guncelleme izni', 'is_active' => true],
        ['code' => 'unit_occupancy.delete', 'label' => 'Unit Occupancy Delete', 'scope' => 'company', 'description' => 'Unit occupancy silme izni', 'is_active' => true],
        ['code' => 'resident_contact.list', 'label' => 'Resident Contact List', 'scope' => 'company', 'description' => 'Resident contact listeleme izni', 'is_active' => true],
        ['code' => 'resident_contact.create', 'label' => 'Resident Contact Create', 'scope' => 'company', 'description' => 'Resident contact olusturma izni', 'is_active' => true],
        ['code' => 'resident_contact.update', 'label' => 'Resident Contact Update', 'scope' => 'company', 'description' => 'Resident contact guncelleme izni', 'is_active' => true],
        ['code' => 'resident_contact.delete', 'label' => 'Resident Contact Delete', 'scope' => 'company', 'description' => 'Resident contact silme izni', 'is_active' => true],
        ['code' => 'resident_vehicle.list', 'label' => 'Resident Vehicle List', 'scope' => 'company', 'description' => 'Resident vehicle listeleme izni', 'is_active' => true],
        ['code' => 'resident_vehicle.create', 'label' => 'Resident Vehicle Create', 'scope' => 'company', 'description' => 'Resident vehicle olusturma izni', 'is_active' => true],
        ['code' => 'resident_vehicle.view', 'label' => 'Resident Vehicle View', 'scope' => 'company', 'description' => 'Resident vehicle detay izni', 'is_active' => true],
        ['code' => 'resident_vehicle.update', 'label' => 'Resident Vehicle Update', 'scope' => 'company', 'description' => 'Resident vehicle guncelleme izni', 'is_active' => true],
        ['code' => 'resident_vehicle.delete', 'label' => 'Resident Vehicle Delete', 'scope' => 'company', 'description' => 'Resident vehicle silme izni', 'is_active' => true],
        ['code' => 'due_definition.list', 'label' => 'Due Definition List', 'scope' => 'company', 'description' => 'Aidat tanimi listeleme izni', 'is_active' => true],
        ['code' => 'due_definition.create', 'label' => 'Due Definition Create', 'scope' => 'company', 'description' => 'Aidat tanimi olusturma izni', 'is_active' => true],
        ['code' => 'due_definition.view', 'label' => 'Due Definition View', 'scope' => 'company', 'description' => 'Aidat tanimi detay izni', 'is_active' => true],
        ['code' => 'due_definition.update', 'label' => 'Due Definition Update', 'scope' => 'company', 'description' => 'Aidat tanimi guncelleme izni', 'is_active' => true],
        ['code' => 'due_definition.delete', 'label' => 'Due Definition Delete', 'scope' => 'company', 'description' => 'Aidat tanimi silme izni', 'is_active' => true],
        ['code' => 'due_period.list', 'label' => 'Due Period List', 'scope' => 'company', 'description' => 'Aidat donem listeleme izni', 'is_active' => true],
        ['code' => 'due_period.create', 'label' => 'Due Period Create', 'scope' => 'company', 'description' => 'Aidat donem olusturma izni', 'is_active' => true],
        ['code' => 'due_period.view', 'label' => 'Due Period View', 'scope' => 'company', 'description' => 'Aidat donem detay izni', 'is_active' => true],
        ['code' => 'due_period.update', 'label' => 'Due Period Update', 'scope' => 'company', 'description' => 'Aidat donem guncelleme izni', 'is_active' => true],
        ['code' => 'due_period.delete', 'label' => 'Due Period Delete', 'scope' => 'company', 'description' => 'Aidat donem silme izni', 'is_active' => true],
        ['code' => 'due_period.close', 'label' => 'Due Period Close', 'scope' => 'company', 'description' => 'Aidat donem kapatma izni', 'is_active' => true],
        ['code' => 'due_period.lock', 'label' => 'Due Period Lock', 'scope' => 'company', 'description' => 'Aidat donem kilitleme izni', 'is_active' => true],
        ['code' => 'due_batch.list', 'label' => 'Due Batch List', 'scope' => 'company', 'description' => 'Tahakkuk batch listeleme izni', 'is_active' => true],
        ['code' => 'due_batch.view', 'label' => 'Due Batch View', 'scope' => 'company', 'description' => 'Tahakkuk batch detay izni', 'is_active' => true],
        ['code' => 'due_batch.create', 'label' => 'Due Batch Create', 'scope' => 'company', 'description' => 'Tahakkuk batch olusturma izni', 'is_active' => true],
        ['code' => 'due_item.list', 'label' => 'Due Item List', 'scope' => 'company', 'description' => 'Borc satiri listeleme izni', 'is_active' => true],
        ['code' => 'due_item.view', 'label' => 'Due Item View', 'scope' => 'company', 'description' => 'Borc satiri detay izni', 'is_active' => true],
        ['code' => 'due_item.update', 'label' => 'Due Item Update', 'scope' => 'company', 'description' => 'Borc satiri guncelleme izni', 'is_active' => true],
        ['code' => 'due_item.cancel', 'label' => 'Due Item Cancel', 'scope' => 'company', 'description' => 'Borc satiri iptal izni', 'is_active' => true],
        ['code' => 'payment.list', 'label' => 'Payment List', 'scope' => 'company', 'description' => 'Odeme listeleme izni', 'is_active' => true],
        ['code' => 'payment.create_manual', 'label' => 'Payment Create Manual', 'scope' => 'company', 'description' => 'Manuel odeme olusturma izni', 'is_active' => true],
        ['code' => 'payment.view', 'label' => 'Payment View', 'scope' => 'company', 'description' => 'Odeme detay izni', 'is_active' => true],
        ['code' => 'payment.cancel', 'label' => 'Payment Cancel', 'scope' => 'company', 'description' => 'Odeme iptal izni', 'is_active' => true],
        ['code' => 'payment_event.list', 'label' => 'Payment Event List', 'scope' => 'company', 'description' => 'Odeme event listeleme izni', 'is_active' => true],
        ['code' => 'payment_event.view', 'label' => 'Payment Event View', 'scope' => 'company', 'description' => 'Odeme event detay izni', 'is_active' => true],
        ['code' => 'deposit.list', 'label' => 'Deposit List', 'scope' => 'company', 'description' => 'Depozito listeleme izni', 'is_active' => true],
        ['code' => 'deposit.create', 'label' => 'Deposit Create', 'scope' => 'company', 'description' => 'Depozito olusturma izni', 'is_active' => true],
        ['code' => 'deposit.view', 'label' => 'Deposit View', 'scope' => 'company', 'description' => 'Depozito detay izni', 'is_active' => true],
        ['code' => 'deposit.update', 'label' => 'Deposit Update', 'scope' => 'company', 'description' => 'Depozito guncelleme izni', 'is_active' => true],
        ['code' => 'deposit.receive', 'label' => 'Deposit Receive', 'scope' => 'company', 'description' => 'Depozito teslim alma izni', 'is_active' => true],
        ['code' => 'deposit.refund', 'label' => 'Deposit Refund', 'scope' => 'company', 'description' => 'Depozito iade izni', 'is_active' => true],
        ['code' => 'deposit.deduct', 'label' => 'Deposit Deduct', 'scope' => 'company', 'description' => 'Depozito kesinti izni', 'is_active' => true],
        ['code' => 'deposit.apply_to_debt', 'label' => 'Deposit Apply To Debt', 'scope' => 'company', 'description' => 'Depozitoyu borca mahsup etme izni', 'is_active' => true],
        ['code' => 'deposit.cancel', 'label' => 'Deposit Cancel', 'scope' => 'company', 'description' => 'Depozito iptal izni', 'is_active' => true],
        ['code' => 'deposit_transaction.list', 'label' => 'Deposit Transaction List', 'scope' => 'company', 'description' => 'Depozito islem listeleme izni', 'is_active' => true],
        ['code' => 'deposit_transaction.view', 'label' => 'Deposit Transaction View', 'scope' => 'company', 'description' => 'Depozito islem detay izni', 'is_active' => true],
        ['code' => 'request_category.list', 'label' => 'Request Category List', 'scope' => 'company', 'description' => 'Talep kategori listeleme izni', 'is_active' => true],
        ['code' => 'request_category.create', 'label' => 'Request Category Create', 'scope' => 'company', 'description' => 'Talep kategori olusturma izni', 'is_active' => true],
        ['code' => 'request_category.view', 'label' => 'Request Category View', 'scope' => 'company', 'description' => 'Talep kategori detay izni', 'is_active' => true],
        ['code' => 'request_category.update', 'label' => 'Request Category Update', 'scope' => 'company', 'description' => 'Talep kategori guncelleme izni', 'is_active' => true],
        ['code' => 'request_category.delete', 'label' => 'Request Category Delete', 'scope' => 'company', 'description' => 'Talep kategori silme izni', 'is_active' => true],
        ['code' => 'service_request.list', 'label' => 'Service Request List', 'scope' => 'company', 'description' => 'Talep listeleme izni', 'is_active' => true],
        ['code' => 'service_request.create', 'label' => 'Service Request Create', 'scope' => 'company', 'description' => 'Talep olusturma izni', 'is_active' => true],
        ['code' => 'service_request.view', 'label' => 'Service Request View', 'scope' => 'company', 'description' => 'Talep detay izni', 'is_active' => true],
        ['code' => 'service_request.update', 'label' => 'Service Request Update', 'scope' => 'company', 'description' => 'Talep guncelleme izni', 'is_active' => true],
        ['code' => 'service_request.assign', 'label' => 'Service Request Assign', 'scope' => 'company', 'description' => 'Talep atama izni', 'is_active' => true],
        ['code' => 'service_request.resolve', 'label' => 'Service Request Resolve', 'scope' => 'company', 'description' => 'Talep resolve izni', 'is_active' => true],
        ['code' => 'service_request.close', 'label' => 'Service Request Close', 'scope' => 'company', 'description' => 'Talep kapatma izni', 'is_active' => true],
        ['code' => 'service_request.cancel', 'label' => 'Service Request Cancel', 'scope' => 'company', 'description' => 'Talep iptal izni', 'is_active' => true],
        ['code' => 'service_request_comment.list', 'label' => 'Service Request Comment List', 'scope' => 'company', 'description' => 'Talep yorum listeleme izni', 'is_active' => true],
        ['code' => 'service_request_comment.create', 'label' => 'Service Request Comment Create', 'scope' => 'company', 'description' => 'Talep yorum olusturma izni', 'is_active' => true],
        ['code' => 'service_request_file.list', 'label' => 'Service Request File List', 'scope' => 'company', 'description' => 'Talep dosya listeleme izni', 'is_active' => true],
        ['code' => 'service_request_file.create', 'label' => 'Service Request File Create', 'scope' => 'company', 'description' => 'Talep dosya ekleme izni', 'is_active' => true],
        ['code' => 'service_request_file.delete', 'label' => 'Service Request File Delete', 'scope' => 'company', 'description' => 'Talep dosya silme izni', 'is_active' => true],
        ['code' => 'work_order.list', 'label' => 'Work Order List', 'scope' => 'company', 'description' => 'Is emri listeleme izni', 'is_active' => true],
        ['code' => 'work_order.create', 'label' => 'Work Order Create', 'scope' => 'company', 'description' => 'Is emri olusturma izni', 'is_active' => true],
        ['code' => 'work_order.view', 'label' => 'Work Order View', 'scope' => 'company', 'description' => 'Is emri detay izni', 'is_active' => true],
        ['code' => 'work_order.update', 'label' => 'Work Order Update', 'scope' => 'company', 'description' => 'Is emri guncelleme izni', 'is_active' => true],
        ['code' => 'work_order.start', 'label' => 'Work Order Start', 'scope' => 'company', 'description' => 'Is emri baslatma izni', 'is_active' => true],
        ['code' => 'work_order.complete', 'label' => 'Work Order Complete', 'scope' => 'company', 'description' => 'Is emri tamamlama izni', 'is_active' => true],
        ['code' => 'work_order.cancel', 'label' => 'Work Order Cancel', 'scope' => 'company', 'description' => 'Is emri iptal izni', 'is_active' => true],
        ['code' => 'notification_template.list', 'label' => 'Notification Template List', 'scope' => 'company', 'description' => 'Bildirim sablon listeleme izni', 'is_active' => true],
        ['code' => 'notification_template.create', 'label' => 'Notification Template Create', 'scope' => 'company', 'description' => 'Bildirim sablon olusturma izni', 'is_active' => true],
        ['code' => 'notification_template.view', 'label' => 'Notification Template View', 'scope' => 'company', 'description' => 'Bildirim sablon detay izni', 'is_active' => true],
        ['code' => 'notification_template.update', 'label' => 'Notification Template Update', 'scope' => 'company', 'description' => 'Bildirim sablon guncelleme izni', 'is_active' => true],
        ['code' => 'notification_template.delete', 'label' => 'Notification Template Delete', 'scope' => 'company', 'description' => 'Bildirim sablon silme izni', 'is_active' => true],
        ['code' => 'notification_message.list', 'label' => 'Notification Message List', 'scope' => 'company', 'description' => 'Bildirim mesaj listeleme izni', 'is_active' => true],
        ['code' => 'notification_message.create', 'label' => 'Notification Message Create', 'scope' => 'company', 'description' => 'Bildirim mesaj olusturma izni', 'is_active' => true],
        ['code' => 'notification_message.view', 'label' => 'Notification Message View', 'scope' => 'company', 'description' => 'Bildirim mesaj detay izni', 'is_active' => true],
        ['code' => 'notification_message.queue', 'label' => 'Notification Message Queue', 'scope' => 'company', 'description' => 'Bildirim queue izni', 'is_active' => true],
        ['code' => 'notification_message.cancel', 'label' => 'Notification Message Cancel', 'scope' => 'company', 'description' => 'Bildirim cancel izni', 'is_active' => true],
        ['code' => 'notification_recipient.list', 'label' => 'Notification Recipient List', 'scope' => 'company', 'description' => 'Bildirim alici listeleme izni', 'is_active' => true],
        ['code' => 'notification_recipient.view', 'label' => 'Notification Recipient View', 'scope' => 'company', 'description' => 'Bildirim alici detay izni', 'is_active' => true],
        ['code' => 'notification_recipient.mark_read', 'label' => 'Notification Recipient Mark Read', 'scope' => 'company', 'description' => 'Bildirim mark-read izni', 'is_active' => true],
        ['code' => 'notification_delivery_log.list', 'label' => 'Notification Delivery Log List', 'scope' => 'company', 'description' => 'Bildirim delivery log listeleme izni', 'is_active' => true],
        ['code' => 'notification_delivery_log.view', 'label' => 'Notification Delivery Log View', 'scope' => 'company', 'description' => 'Bildirim delivery log detay izni', 'is_active' => true],
        ['code' => 'communication_provider.list', 'label' => 'Communication Provider List', 'scope' => 'company', 'description' => 'Iletisim provider listeleme izni', 'is_active' => true],
        ['code' => 'communication_provider.create', 'label' => 'Communication Provider Create', 'scope' => 'company', 'description' => 'Iletisim provider olusturma izni', 'is_active' => true],
        ['code' => 'communication_provider.view', 'label' => 'Communication Provider View', 'scope' => 'company', 'description' => 'Iletisim provider detay izni', 'is_active' => true],
        ['code' => 'communication_provider.update', 'label' => 'Communication Provider Update', 'scope' => 'company', 'description' => 'Iletisim provider guncelleme izni', 'is_active' => true],
        ['code' => 'communication_provider.delete', 'label' => 'Communication Provider Delete', 'scope' => 'company', 'description' => 'Iletisim provider silme izni', 'is_active' => true],
        ['code' => 'communication_provider.set_default', 'label' => 'Communication Provider Set Default', 'scope' => 'company', 'description' => 'Iletisim provider varsayilan yapma izni', 'is_active' => true],
        ['code' => 'announcement.list', 'label' => 'Announcement List', 'scope' => 'company', 'description' => 'Duyuru listeleme izni', 'is_active' => true],
        ['code' => 'announcement.create', 'label' => 'Announcement Create', 'scope' => 'company', 'description' => 'Duyuru olusturma izni', 'is_active' => true],
        ['code' => 'announcement.view', 'label' => 'Announcement View', 'scope' => 'company', 'description' => 'Duyuru detay izni', 'is_active' => true],
        ['code' => 'announcement.update', 'label' => 'Announcement Update', 'scope' => 'company', 'description' => 'Duyuru guncelleme izni', 'is_active' => true],
        ['code' => 'announcement.delete', 'label' => 'Announcement Delete', 'scope' => 'company', 'description' => 'Duyuru silme izni', 'is_active' => true],
        ['code' => 'announcement.publish', 'label' => 'Announcement Publish', 'scope' => 'company', 'description' => 'Duyuru yayinlama izni', 'is_active' => true],
        ['code' => 'announcement.archive', 'label' => 'Announcement Archive', 'scope' => 'company', 'description' => 'Duyuru arsivleme izni', 'is_active' => true],
        ['code' => 'announcement.cancel', 'label' => 'Announcement Cancel', 'scope' => 'company', 'description' => 'Duyuru iptal izni', 'is_active' => true],
        ['code' => 'announcement.mark_read', 'label' => 'Announcement Mark Read', 'scope' => 'company', 'description' => 'Duyuru okundu isaretleme izni', 'is_active' => true],
        ['code' => 'announcement.reads.list', 'label' => 'Announcement Reads List', 'scope' => 'company', 'description' => 'Duyuru okunma listeleme izni', 'is_active' => true],
        ['code' => 'announcement.targets.list', 'label' => 'Announcement Targets List', 'scope' => 'company', 'description' => 'Duyuru hedef listeleme izni', 'is_active' => true],
        ['code' => 'common_area.list', 'label' => 'Common Area List', 'scope' => 'company', 'description' => 'Ortak alan listeleme izni', 'is_active' => true],
        ['code' => 'common_area.create', 'label' => 'Common Area Create', 'scope' => 'company', 'description' => 'Ortak alan olusturma izni', 'is_active' => true],
        ['code' => 'common_area.view', 'label' => 'Common Area View', 'scope' => 'company', 'description' => 'Ortak alan detay izni', 'is_active' => true],
        ['code' => 'common_area.update', 'label' => 'Common Area Update', 'scope' => 'company', 'description' => 'Ortak alan guncelleme izni', 'is_active' => true],
        ['code' => 'common_area.delete', 'label' => 'Common Area Delete', 'scope' => 'company', 'description' => 'Ortak alan silme izni', 'is_active' => true],
        ['code' => 'common_area_reservation.list', 'label' => 'Common Area Reservation List', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon listeleme izni', 'is_active' => true],
        ['code' => 'common_area_reservation.create', 'label' => 'Common Area Reservation Create', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon olusturma izni', 'is_active' => true],
        ['code' => 'common_area_reservation.view', 'label' => 'Common Area Reservation View', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon detay izni', 'is_active' => true],
        ['code' => 'common_area_reservation.update', 'label' => 'Common Area Reservation Update', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon guncelleme izni', 'is_active' => true],
        ['code' => 'common_area_reservation.approve', 'label' => 'Common Area Reservation Approve', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon onay izni', 'is_active' => true],
        ['code' => 'common_area_reservation.reject', 'label' => 'Common Area Reservation Reject', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon red izni', 'is_active' => true],
        ['code' => 'common_area_reservation.cancel', 'label' => 'Common Area Reservation Cancel', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon iptal izni', 'is_active' => true],
        ['code' => 'common_area_reservation.complete', 'label' => 'Common Area Reservation Complete', 'scope' => 'company', 'description' => 'Ortak alan rezervasyon tamamlama izni', 'is_active' => true],
        ['code' => 'meter.list', 'label' => 'Meter List', 'scope' => 'company', 'description' => 'Sayac listeleme izni', 'is_active' => true],
        ['code' => 'meter.create', 'label' => 'Meter Create', 'scope' => 'company', 'description' => 'Sayac olusturma izni', 'is_active' => true],
        ['code' => 'meter.view', 'label' => 'Meter View', 'scope' => 'company', 'description' => 'Sayac detay izni', 'is_active' => true],
        ['code' => 'meter.update', 'label' => 'Meter Update', 'scope' => 'company', 'description' => 'Sayac guncelleme izni', 'is_active' => true],
        ['code' => 'meter.delete', 'label' => 'Meter Delete', 'scope' => 'company', 'description' => 'Sayac silme izni', 'is_active' => true],
        ['code' => 'meter_period.list', 'label' => 'Meter Period List', 'scope' => 'company', 'description' => 'Sayac donem listeleme izni', 'is_active' => true],
        ['code' => 'meter_period.create', 'label' => 'Meter Period Create', 'scope' => 'company', 'description' => 'Sayac donem olusturma izni', 'is_active' => true],
        ['code' => 'meter_period.view', 'label' => 'Meter Period View', 'scope' => 'company', 'description' => 'Sayac donem detay izni', 'is_active' => true],
        ['code' => 'meter_period.update', 'label' => 'Meter Period Update', 'scope' => 'company', 'description' => 'Sayac donem guncelleme izni', 'is_active' => true],
        ['code' => 'meter_period.close', 'label' => 'Meter Period Close', 'scope' => 'company', 'description' => 'Sayac donem kapatma izni', 'is_active' => true],
        ['code' => 'meter_period.lock', 'label' => 'Meter Period Lock', 'scope' => 'company', 'description' => 'Sayac donem kilitleme izni', 'is_active' => true],
        ['code' => 'meter_reading.list', 'label' => 'Meter Reading List', 'scope' => 'company', 'description' => 'Sayac okuma listeleme izni', 'is_active' => true],
        ['code' => 'meter_reading.create', 'label' => 'Meter Reading Create', 'scope' => 'company', 'description' => 'Sayac okuma olusturma izni', 'is_active' => true],
        ['code' => 'meter_reading.view', 'label' => 'Meter Reading View', 'scope' => 'company', 'description' => 'Sayac okuma detay izni', 'is_active' => true],
        ['code' => 'meter_reading.update', 'label' => 'Meter Reading Update', 'scope' => 'company', 'description' => 'Sayac okuma guncelleme izni', 'is_active' => true],
        ['code' => 'meter_reading.approve', 'label' => 'Meter Reading Approve', 'scope' => 'company', 'description' => 'Sayac okuma onay izni', 'is_active' => true],
        ['code' => 'meter_reading.reject', 'label' => 'Meter Reading Reject', 'scope' => 'company', 'description' => 'Sayac okuma red izni', 'is_active' => true],
        ['code' => 'meter_reading.cancel', 'label' => 'Meter Reading Cancel', 'scope' => 'company', 'description' => 'Sayac okuma iptal izni', 'is_active' => true],
        ['code' => 'consumption_report.list', 'label' => 'Consumption Report List', 'scope' => 'company', 'description' => 'Tuketim raporu listeleme izni', 'is_active' => true],
        ['code' => 'consumption_report.view', 'label' => 'Consumption Report View', 'scope' => 'company', 'description' => 'Tuketim raporu detay izni', 'is_active' => true],
        ['code' => 'consumption_report.generate', 'label' => 'Consumption Report Generate', 'scope' => 'company', 'description' => 'Tuketim raporu olusturma izni', 'is_active' => true],
        ['code' => 'consumption_report.cancel', 'label' => 'Consumption Report Cancel', 'scope' => 'company', 'description' => 'Tuketim raporu iptal izni', 'is_active' => true],
        ['code' => 'asset.list', 'label' => 'Asset List', 'scope' => 'company', 'description' => 'Demirbas listeleme izni', 'is_active' => true],
        ['code' => 'asset.create', 'label' => 'Asset Create', 'scope' => 'company', 'description' => 'Demirbas olusturma izni', 'is_active' => true],
        ['code' => 'asset.view', 'label' => 'Asset View', 'scope' => 'company', 'description' => 'Demirbas detay izni', 'is_active' => true],
        ['code' => 'asset.update', 'label' => 'Asset Update', 'scope' => 'company', 'description' => 'Demirbas guncelleme izni', 'is_active' => true],
        ['code' => 'asset.delete', 'label' => 'Asset Delete', 'scope' => 'company', 'description' => 'Demirbas silme izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.list', 'label' => 'Asset Maintenance Plan List', 'scope' => 'company', 'description' => 'Demirbas bakim plani listeleme izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.create', 'label' => 'Asset Maintenance Plan Create', 'scope' => 'company', 'description' => 'Demirbas bakim plani olusturma izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.view', 'label' => 'Asset Maintenance Plan View', 'scope' => 'company', 'description' => 'Demirbas bakim plani detay izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.update', 'label' => 'Asset Maintenance Plan Update', 'scope' => 'company', 'description' => 'Demirbas bakim plani guncelleme izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.pause', 'label' => 'Asset Maintenance Plan Pause', 'scope' => 'company', 'description' => 'Demirbas bakim plani pause izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.resume', 'label' => 'Asset Maintenance Plan Resume', 'scope' => 'company', 'description' => 'Demirbas bakim plani resume izni', 'is_active' => true],
        ['code' => 'asset_maintenance_plan.cancel', 'label' => 'Asset Maintenance Plan Cancel', 'scope' => 'company', 'description' => 'Demirbas bakim plani cancel izni', 'is_active' => true],
        ['code' => 'asset_maintenance_record.list', 'label' => 'Asset Maintenance Record List', 'scope' => 'company', 'description' => 'Demirbas bakim kaydi listeleme izni', 'is_active' => true],
        ['code' => 'asset_maintenance_record.create', 'label' => 'Asset Maintenance Record Create', 'scope' => 'company', 'description' => 'Demirbas bakim kaydi olusturma izni', 'is_active' => true],
        ['code' => 'asset_maintenance_record.view', 'label' => 'Asset Maintenance Record View', 'scope' => 'company', 'description' => 'Demirbas bakim kaydi detay izni', 'is_active' => true],
        ['code' => 'asset_maintenance_record.cancel', 'label' => 'Asset Maintenance Record Cancel', 'scope' => 'company', 'description' => 'Demirbas bakim kaydi cancel izni', 'is_active' => true],
        ['code' => 'visitor_invite.list', 'label' => 'Visitor Invite List', 'scope' => 'company', 'description' => 'Ziyaretci davet listeleme izni', 'is_active' => true],
        ['code' => 'visitor_invite.create', 'label' => 'Visitor Invite Create', 'scope' => 'company', 'description' => 'Ziyaretci davet olusturma izni', 'is_active' => true],
        ['code' => 'visitor_invite.view', 'label' => 'Visitor Invite View', 'scope' => 'company', 'description' => 'Ziyaretci davet detay izni', 'is_active' => true],
        ['code' => 'visitor_invite.cancel', 'label' => 'Visitor Invite Cancel', 'scope' => 'company', 'description' => 'Ziyaretci davet iptal izni', 'is_active' => true],
        ['code' => 'visitor_entry.list', 'label' => 'Visitor Entry List', 'scope' => 'company', 'description' => 'Ziyaretci giris cikis listeleme izni', 'is_active' => true],
        ['code' => 'visitor_entry.check_in', 'label' => 'Visitor Entry Check In', 'scope' => 'company', 'description' => 'Ziyaretci check-in izni', 'is_active' => true],
        ['code' => 'visitor_entry.check_out', 'label' => 'Visitor Entry Check Out', 'scope' => 'company', 'description' => 'Ziyaretci check-out izni', 'is_active' => true],
        ['code' => 'visitor_entry.view', 'label' => 'Visitor Entry View', 'scope' => 'company', 'description' => 'Ziyaretci kaydi detay izni', 'is_active' => true],
        ['code' => 'security_incident.list', 'label' => 'Security Incident List', 'scope' => 'company', 'description' => 'Guvenlik olay listeleme izni', 'is_active' => true],
        ['code' => 'security_incident.create', 'label' => 'Security Incident Create', 'scope' => 'company', 'description' => 'Guvenlik olay olusturma izni', 'is_active' => true],
        ['code' => 'security_incident.view', 'label' => 'Security Incident View', 'scope' => 'company', 'description' => 'Guvenlik olay detay izni', 'is_active' => true],
        ['code' => 'security_incident.update', 'label' => 'Security Incident Update', 'scope' => 'company', 'description' => 'Guvenlik olay guncelleme izni', 'is_active' => true],
        ['code' => 'security_incident.resolve', 'label' => 'Security Incident Resolve', 'scope' => 'company', 'description' => 'Guvenlik olay resolve izni', 'is_active' => true],
        ['code' => 'security_incident.close', 'label' => 'Security Incident Close', 'scope' => 'company', 'description' => 'Guvenlik olay close izni', 'is_active' => true],
        ['code' => 'security_incident.cancel', 'label' => 'Security Incident Cancel', 'scope' => 'company', 'description' => 'Guvenlik olay cancel izni', 'is_active' => true],
        ['code' => 'vehicle_access_list.list', 'label' => 'Vehicle Access List List', 'scope' => 'company', 'description' => 'Arac gecis listesi listeleme izni', 'is_active' => true],
        ['code' => 'vehicle_access_list.create', 'label' => 'Vehicle Access List Create', 'scope' => 'company', 'description' => 'Arac gecis listesi olusturma izni', 'is_active' => true],
        ['code' => 'vehicle_access_list.view', 'label' => 'Vehicle Access List View', 'scope' => 'company', 'description' => 'Arac gecis listesi detay izni', 'is_active' => true],
        ['code' => 'vehicle_access_list.update', 'label' => 'Vehicle Access List Update', 'scope' => 'company', 'description' => 'Arac gecis listesi guncelleme izni', 'is_active' => true],
        ['code' => 'vehicle_access_list.delete', 'label' => 'Vehicle Access List Delete', 'scope' => 'company', 'description' => 'Arac gecis listesi silme izni', 'is_active' => true],
        ['code' => 'staff_profile.list', 'label' => 'Staff Profile List', 'scope' => 'company', 'description' => 'Personel profili listeleme izni', 'is_active' => true],
        ['code' => 'staff_profile.create', 'label' => 'Staff Profile Create', 'scope' => 'company', 'description' => 'Personel profili olusturma izni', 'is_active' => true],
        ['code' => 'staff_profile.view', 'label' => 'Staff Profile View', 'scope' => 'company', 'description' => 'Personel profili detay izni', 'is_active' => true],
        ['code' => 'staff_profile.update', 'label' => 'Staff Profile Update', 'scope' => 'company', 'description' => 'Personel profili guncelleme izni', 'is_active' => true],
        ['code' => 'staff_profile.delete', 'label' => 'Staff Profile Delete', 'scope' => 'company', 'description' => 'Personel profili silme izni', 'is_active' => true],
        ['code' => 'staff_assignment.list', 'label' => 'Staff Assignment List', 'scope' => 'company', 'description' => 'Personel atama listeleme izni', 'is_active' => true],
        ['code' => 'staff_assignment.create', 'label' => 'Staff Assignment Create', 'scope' => 'company', 'description' => 'Personel atama olusturma izni', 'is_active' => true],
        ['code' => 'staff_assignment.view', 'label' => 'Staff Assignment View', 'scope' => 'company', 'description' => 'Personel atama detay izni', 'is_active' => true],
        ['code' => 'staff_assignment.update', 'label' => 'Staff Assignment Update', 'scope' => 'company', 'description' => 'Personel atama guncelleme izni', 'is_active' => true],
        ['code' => 'staff_assignment.delete', 'label' => 'Staff Assignment Delete', 'scope' => 'company', 'description' => 'Personel atama silme izni', 'is_active' => true],
        ['code' => 'staff_shift.list', 'label' => 'Staff Shift List', 'scope' => 'company', 'description' => 'Personel vardiya listeleme izni', 'is_active' => true],
        ['code' => 'staff_shift.create', 'label' => 'Staff Shift Create', 'scope' => 'company', 'description' => 'Personel vardiya olusturma izni', 'is_active' => true],
        ['code' => 'staff_shift.view', 'label' => 'Staff Shift View', 'scope' => 'company', 'description' => 'Personel vardiya detay izni', 'is_active' => true],
        ['code' => 'staff_shift.update', 'label' => 'Staff Shift Update', 'scope' => 'company', 'description' => 'Personel vardiya guncelleme izni', 'is_active' => true],
        ['code' => 'staff_shift.start', 'label' => 'Staff Shift Start', 'scope' => 'company', 'description' => 'Personel vardiya baslatma izni', 'is_active' => true],
        ['code' => 'staff_shift.complete', 'label' => 'Staff Shift Complete', 'scope' => 'company', 'description' => 'Personel vardiya tamamlama izni', 'is_active' => true],
        ['code' => 'staff_shift.cancel', 'label' => 'Staff Shift Cancel', 'scope' => 'company', 'description' => 'Personel vardiya iptal izni', 'is_active' => true],
        ['code' => 'staff_task.list', 'label' => 'Staff Task List', 'scope' => 'company', 'description' => 'Personel gorev listeleme izni', 'is_active' => true],
        ['code' => 'staff_task.create', 'label' => 'Staff Task Create', 'scope' => 'company', 'description' => 'Personel gorev olusturma izni', 'is_active' => true],
        ['code' => 'staff_task.view', 'label' => 'Staff Task View', 'scope' => 'company', 'description' => 'Personel gorev detay izni', 'is_active' => true],
        ['code' => 'staff_task.update', 'label' => 'Staff Task Update', 'scope' => 'company', 'description' => 'Personel gorev guncelleme izni', 'is_active' => true],
        ['code' => 'staff_task.assign', 'label' => 'Staff Task Assign', 'scope' => 'company', 'description' => 'Personel gorev atama izni', 'is_active' => true],
        ['code' => 'staff_task.start', 'label' => 'Staff Task Start', 'scope' => 'company', 'description' => 'Personel gorev baslatma izni', 'is_active' => true],
        ['code' => 'staff_task.complete', 'label' => 'Staff Task Complete', 'scope' => 'company', 'description' => 'Personel gorev tamamlama izni', 'is_active' => true],
        ['code' => 'staff_task.cancel', 'label' => 'Staff Task Cancel', 'scope' => 'company', 'description' => 'Personel gorev iptal izni', 'is_active' => true],
        ['code' => 'document_category.list', 'label' => 'Document Category List', 'scope' => 'company', 'description' => 'Evrak kategori listeleme izni', 'is_active' => true],
        ['code' => 'document_category.create', 'label' => 'Document Category Create', 'scope' => 'company', 'description' => 'Evrak kategori olusturma izni', 'is_active' => true],
        ['code' => 'document_category.view', 'label' => 'Document Category View', 'scope' => 'company', 'description' => 'Evrak kategori detay izni', 'is_active' => true],
        ['code' => 'document_category.update', 'label' => 'Document Category Update', 'scope' => 'company', 'description' => 'Evrak kategori guncelleme izni', 'is_active' => true],
        ['code' => 'document_category.delete', 'label' => 'Document Category Delete', 'scope' => 'company', 'description' => 'Evrak kategori silme izni', 'is_active' => true],
        ['code' => 'document.list', 'label' => 'Document List', 'scope' => 'company', 'description' => 'Evrak listeleme izni', 'is_active' => true],
        ['code' => 'document.create', 'label' => 'Document Create', 'scope' => 'company', 'description' => 'Evrak olusturma izni', 'is_active' => true],
        ['code' => 'document.view', 'label' => 'Document View', 'scope' => 'company', 'description' => 'Evrak detay izni', 'is_active' => true],
        ['code' => 'document.update', 'label' => 'Document Update', 'scope' => 'company', 'description' => 'Evrak guncelleme izni', 'is_active' => true],
        ['code' => 'document.archive', 'label' => 'Document Archive', 'scope' => 'company', 'description' => 'Evrak arsivleme izni', 'is_active' => true],
        ['code' => 'document.restore', 'label' => 'Document Restore', 'scope' => 'company', 'description' => 'Evrak geri yukleme izni', 'is_active' => true],
        ['code' => 'document.delete', 'label' => 'Document Delete', 'scope' => 'company', 'description' => 'Evrak silme izni', 'is_active' => true],
        ['code' => 'document_version.list', 'label' => 'Document Version List', 'scope' => 'company', 'description' => 'Evrak versiyon listeleme izni', 'is_active' => true],
        ['code' => 'document_version.create', 'label' => 'Document Version Create', 'scope' => 'company', 'description' => 'Evrak versiyon olusturma izni', 'is_active' => true],
        ['code' => 'document_version.view', 'label' => 'Document Version View', 'scope' => 'company', 'description' => 'Evrak versiyon detay izni', 'is_active' => true],
        ['code' => 'document_access_rule.list', 'label' => 'Document Access Rule List', 'scope' => 'company', 'description' => 'Evrak erisim kurali listeleme izni', 'is_active' => true],
        ['code' => 'document_access_rule.create', 'label' => 'Document Access Rule Create', 'scope' => 'company', 'description' => 'Evrak erisim kurali olusturma izni', 'is_active' => true],
        ['code' => 'document_access_rule.delete', 'label' => 'Document Access Rule Delete', 'scope' => 'company', 'description' => 'Evrak erisim kurali silme izni', 'is_active' => true],
        ['code' => 'meeting.list', 'label' => 'Meeting List', 'scope' => 'company', 'description' => 'Toplanti listeleme izni', 'is_active' => true],
        ['code' => 'meeting.create', 'label' => 'Meeting Create', 'scope' => 'company', 'description' => 'Toplanti olusturma izni', 'is_active' => true],
        ['code' => 'meeting.view', 'label' => 'Meeting View', 'scope' => 'company', 'description' => 'Toplanti detay izni', 'is_active' => true],
        ['code' => 'meeting.update', 'label' => 'Meeting Update', 'scope' => 'company', 'description' => 'Toplanti guncelleme izni', 'is_active' => true],
        ['code' => 'meeting.publish', 'label' => 'Meeting Publish', 'scope' => 'company', 'description' => 'Toplanti yayinlama izni', 'is_active' => true],
        ['code' => 'meeting.complete', 'label' => 'Meeting Complete', 'scope' => 'company', 'description' => 'Toplanti tamamlama izni', 'is_active' => true],
        ['code' => 'meeting.cancel', 'label' => 'Meeting Cancel', 'scope' => 'company', 'description' => 'Toplanti iptal izni', 'is_active' => true],
        ['code' => 'meeting.lock', 'label' => 'Meeting Lock', 'scope' => 'company', 'description' => 'Toplanti kilitleme izni', 'is_active' => true],
        ['code' => 'meeting_agenda.list', 'label' => 'Meeting Agenda List', 'scope' => 'company', 'description' => 'Toplanti gundem listeleme izni', 'is_active' => true],
        ['code' => 'meeting_agenda.create', 'label' => 'Meeting Agenda Create', 'scope' => 'company', 'description' => 'Toplanti gundem olusturma izni', 'is_active' => true],
        ['code' => 'meeting_agenda.update', 'label' => 'Meeting Agenda Update', 'scope' => 'company', 'description' => 'Toplanti gundem guncelleme izni', 'is_active' => true],
        ['code' => 'meeting_agenda.delete', 'label' => 'Meeting Agenda Delete', 'scope' => 'company', 'description' => 'Toplanti gundem silme izni', 'is_active' => true],
        ['code' => 'meeting_attendee.list', 'label' => 'Meeting Attendee List', 'scope' => 'company', 'description' => 'Toplanti hazirun listeleme izni', 'is_active' => true],
        ['code' => 'meeting_attendee.create', 'label' => 'Meeting Attendee Create', 'scope' => 'company', 'description' => 'Toplanti hazirun olusturma izni', 'is_active' => true],
        ['code' => 'meeting_attendee.update', 'label' => 'Meeting Attendee Update', 'scope' => 'company', 'description' => 'Toplanti hazirun guncelleme izni', 'is_active' => true],
        ['code' => 'meeting_attendee.sign', 'label' => 'Meeting Attendee Sign', 'scope' => 'company', 'description' => 'Toplanti hazirun imza izni', 'is_active' => true],
        ['code' => 'meeting_attendee.delete', 'label' => 'Meeting Attendee Delete', 'scope' => 'company', 'description' => 'Toplanti hazirun silme izni', 'is_active' => true],
        ['code' => 'decision_book.list', 'label' => 'Decision Book List', 'scope' => 'company', 'description' => 'Karar defteri listeleme izni', 'is_active' => true],
        ['code' => 'decision_book.create', 'label' => 'Decision Book Create', 'scope' => 'company', 'description' => 'Karar defteri olusturma izni', 'is_active' => true],
        ['code' => 'decision_book.view', 'label' => 'Decision Book View', 'scope' => 'company', 'description' => 'Karar defteri detay izni', 'is_active' => true],
        ['code' => 'decision_book.update', 'label' => 'Decision Book Update', 'scope' => 'company', 'description' => 'Karar defteri guncelleme izni', 'is_active' => true],
        ['code' => 'decision_book.approve', 'label' => 'Decision Book Approve', 'scope' => 'company', 'description' => 'Karar defteri onay izni', 'is_active' => true],
        ['code' => 'decision_book.lock', 'label' => 'Decision Book Lock', 'scope' => 'company', 'description' => 'Karar defteri kilitleme izni', 'is_active' => true],
        ['code' => 'decision_book.cancel', 'label' => 'Decision Book Cancel', 'scope' => 'company', 'description' => 'Karar defteri iptal izni', 'is_active' => true],
        ['code' => 'legal_case.list', 'label' => 'Legal Case List', 'scope' => 'company', 'description' => 'Hukuki takip dosya listeleme izni', 'is_active' => true],
        ['code' => 'legal_case.create', 'label' => 'Legal Case Create', 'scope' => 'company', 'description' => 'Hukuki takip dosya olusturma izni', 'is_active' => true],
        ['code' => 'legal_case.view', 'label' => 'Legal Case View', 'scope' => 'company', 'description' => 'Hukuki takip dosya detay izni', 'is_active' => true],
        ['code' => 'legal_case.update', 'label' => 'Legal Case Update', 'scope' => 'company', 'description' => 'Hukuki takip dosya guncelleme izni', 'is_active' => true],
        ['code' => 'legal_case.send_to_lawyer', 'label' => 'Legal Case Send To Lawyer', 'scope' => 'company', 'description' => 'Hukuki takip avukata gonderme izni', 'is_active' => true],
        ['code' => 'legal_case.file', 'label' => 'Legal Case File', 'scope' => 'company', 'description' => 'Hukuki takip dosyalama izni', 'is_active' => true],
        ['code' => 'legal_case.mark_paid', 'label' => 'Legal Case Mark Paid', 'scope' => 'company', 'description' => 'Hukuki takip odendi isaretleme izni', 'is_active' => true],
        ['code' => 'legal_case.close', 'label' => 'Legal Case Close', 'scope' => 'company', 'description' => 'Hukuki takip kapatma izni', 'is_active' => true],
        ['code' => 'legal_case.cancel', 'label' => 'Legal Case Cancel', 'scope' => 'company', 'description' => 'Hukuki takip iptal izni', 'is_active' => true],
        ['code' => 'legal_case_debt.list', 'label' => 'Legal Case Debt List', 'scope' => 'company', 'description' => 'Hukuki takip borc listeleme izni', 'is_active' => true],
        ['code' => 'legal_case_debt.create', 'label' => 'Legal Case Debt Create', 'scope' => 'company', 'description' => 'Hukuki takip borc ekleme izni', 'is_active' => true],
        ['code' => 'legal_case_debt.delete', 'label' => 'Legal Case Debt Delete', 'scope' => 'company', 'description' => 'Hukuki takip borc silme izni', 'is_active' => true],
        ['code' => 'legal_case_event.list', 'label' => 'Legal Case Event List', 'scope' => 'company', 'description' => 'Hukuki takip event listeleme izni', 'is_active' => true],
        ['code' => 'legal_case_event.create', 'label' => 'Legal Case Event Create', 'scope' => 'company', 'description' => 'Hukuki takip event olusturma izni', 'is_active' => true],
        ['code' => 'legal_case_document.list', 'label' => 'Legal Case Document List', 'scope' => 'company', 'description' => 'Hukuki takip dokuman listeleme izni', 'is_active' => true],
        ['code' => 'legal_case_document.create', 'label' => 'Legal Case Document Create', 'scope' => 'company', 'description' => 'Hukuki takip dokuman ekleme izni', 'is_active' => true],
        ['code' => 'legal_case_document.delete', 'label' => 'Legal Case Document Delete', 'scope' => 'company', 'description' => 'Hukuki takip dokuman silme izni', 'is_active' => true],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->validateCatalog();
    }

    /**
     * @return list<array{code:string,label:string,scope:string,description:string,is_active:bool}>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_column($this->permissions, 'code');
    }

    public function exists(string $code): bool
    {
        $normalized = strtolower(trim($code));
        foreach ($this->permissions as $permission) {
            if ($permission['code'] === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{code:string,label:string,scope:string,description:string,is_active:bool}
     */
    public function get(string $code): array
    {
        $normalized = strtolower(trim($code));
        foreach ($this->permissions as $permission) {
            if ($permission['code'] === $normalized) {
                return $permission;
            }
        }

        throw new PermissionNotFoundException('Permission bulunamadi: ' . $code);
    }

    public function scopeOf(string $code): string
    {
        return $this->get($code)['scope'];
    }

    public function assertExists(string $code): void
    {
        $normalized = trim($code);
        if (! preg_match(self::CODE_REGEX, $normalized)) {
            throw new InvalidPermissionCodeException('Permission kodu formati gecersiz: ' . $code);
        }

        if ($normalized !== strtolower($normalized)) {
            throw new InvalidPermissionCodeException('Permission kodu lowercase olmali: ' . $code);
        }

        if (! $this->exists($normalized)) {
            throw new PermissionNotFoundException('Permission bulunamadi: ' . $code);
        }
    }

    public function validateCatalog(): void
    {
        $seen = [];

        foreach ($this->permissions as $idx => $permission) {
            $this->validateRequiredKeys($permission, $idx);

            $code = trim((string) $permission['code']);
            if ($code !== strtolower($code)) {
                throw new InvalidPermissionCodeException('Permission kodu lowercase olmali: ' . $code);
            }

            if (! preg_match(self::CODE_REGEX, $code)) {
                throw new InvalidPermissionCodeException('Permission kodu regex formatina uymuyor: ' . $code);
            }

            if (isset($seen[$code])) {
                throw new InvalidPermissionCodeException('Duplicate permission kodu: ' . $code);
            }
            $seen[$code] = true;

            $scope = trim((string) $permission['scope']);
            if (! in_array($scope, ['system', 'company'], true)) {
                throw new InvalidPermissionCodeException('Permission scope gecersiz: ' . $scope);
            }
        }
    }

    /**
     * @param array<string, mixed> $permission
     */
    private function validateRequiredKeys(array $permission, int $idx): void
    {
        foreach (['code', 'label', 'scope', 'description', 'is_active'] as $key) {
            if (! array_key_exists($key, $permission)) {
                throw new InvalidPermissionCodeException('Permission kaydi eksik alan: ' . $key . ' [index=' . $idx . ']');
            }
        }
    }
}

