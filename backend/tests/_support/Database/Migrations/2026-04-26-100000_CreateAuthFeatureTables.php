<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthFeatureTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'owner_user_id' => ['type' => 'INTEGER', 'null' => true],
            'public_id' => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug', 'uq_companies_slug');
        $this->forge->createTable('companies', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'public_id' => ['type' => 'VARCHAR', 'constraint' => 36, 'null' => false],
            'email' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => false],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'password_changed_at' => ['type' => 'DATETIME', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
            'failed_login_count' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'locked_until' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('roles', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'scope' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'company'],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'deprecated_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('permissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'role_id' => ['type' => 'INTEGER', 'null' => false],
            'permission_id' => ['type' => 'INTEGER', 'null' => false],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('role_permissions', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'user_id' => ['type' => 'INTEGER', 'null' => false],
            'role_id' => ['type' => 'INTEGER', 'null' => false],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'role_version' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('user_roles', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'user_id' => ['type' => 'INTEGER', 'null' => false],
            'family_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'token_jti' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'issued_at' => ['type' => 'DATETIME', 'null' => false],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'revoked_reason' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'revoked_by' => ['type' => 'INTEGER', 'null' => true],
            'replaced_by_token_id' => ['type' => 'INTEGER', 'null' => true],
            'created_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'device_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('user_refresh_tokens', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id' => ['type' => 'INTEGER', 'null' => false],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'used_at' => ['type' => 'DATETIME', 'null' => true],
            'requested_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'requested_user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('password_reset_tokens', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => true],
            'user_id' => ['type' => 'INTEGER', 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'event' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'actor_user_id' => ['type' => 'INTEGER', 'null' => true],
            'target_user_id' => ['type' => 'INTEGER', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'entity_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'old_data' => ['type' => 'TEXT', 'null' => true],
            'new_data' => ['type' => 'TEXT', 'null' => true],
            'meta' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('audit_logs', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'settings' => ['type' => 'TEXT', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('company_id', 'uq_company_settings_company_id');
        $this->forge->createTable('company_settings', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'parent_id' => ['type' => 'INTEGER', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('departments', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'department_id' => ['type' => 'INTEGER', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('branches', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'user_id' => ['type' => 'INTEGER', 'null' => false],
            'department_id' => ['type' => 'INTEGER', 'null' => true],
            'branch_id' => ['type' => 'INTEGER', 'null' => true],
            'employee_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'last_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'active'],
            'hired_at' => ['type' => 'DATETIME', 'null' => true],
            'left_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('user_id', 'uq_employees_user_id');
        $this->forge->addUniqueKey(['company_id', 'employee_code'], 'uq_employees_company_employee_code');
        $this->forge->createTable('employees', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'actor_user_id' => ['type' => 'INTEGER', 'null' => false],
            'original_filename' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'total_rows' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'success_rows' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'failed_rows' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'processing'],
            'duplicate_strategy' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'fail'],
            'update_existing' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'error_report' => ['type' => 'TEXT', 'null' => true],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'failed_at' => ['type' => 'DATETIME', 'null' => true],
            'failure_reason' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('employee_imports', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'slug'], 'uq_course_categories_company_slug');
        $this->forge->createTable('course_categories', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'category_id' => ['type' => 'INTEGER', 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'slug' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'draft'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INTEGER', 'null' => true],
            'updated_by' => ['type' => 'INTEGER', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'slug'], 'uq_courses_company_slug');
        $this->forge->createTable('courses', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'course_id' => ['type' => 'INTEGER', 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'description' => ['type' => 'TEXT', 'null' => true],
            'sort_order' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'course_id', 'sort_order'], 'uq_course_modules_company_course_sort');
        $this->forge->createTable('course_modules', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'course_id' => ['type' => 'INTEGER', 'null' => false],
            'module_id' => ['type' => 'INTEGER', 'null' => false],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false, 'default' => 'text'],
            'content' => ['type' => 'TEXT', 'null' => true],
            'external_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'duration_seconds' => ['type' => 'INTEGER', 'null' => true],
            'sort_order' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'is_preview' => ['type' => 'INTEGER', 'null' => false, 'default' => 0],
            'is_active' => ['type' => 'INTEGER', 'null' => false, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'module_id', 'sort_order'], 'uq_lessons_company_module_sort');
        $this->forge->createTable('lessons', true);

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'company_id' => ['type' => 'INTEGER', 'null' => false],
            'lesson_id' => ['type' => 'INTEGER', 'null' => false],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'stored_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'path' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => false],
            'mime_type' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'size_bytes' => ['type' => 'INTEGER', 'null' => false],
            'disk' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false, 'default' => 'local'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'active'],
            'uploaded_by' => ['type' => 'INTEGER', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('lesson_media', true);
    }

    public function down()
    {
        $this->forge->dropTable('lesson_media', true);
        $this->forge->dropTable('lessons', true);
        $this->forge->dropTable('course_modules', true);
        $this->forge->dropTable('courses', true);
        $this->forge->dropTable('course_categories', true);
        $this->forge->dropTable('employee_imports', true);
        $this->forge->dropTable('employees', true);
        $this->forge->dropTable('branches', true);
        $this->forge->dropTable('departments', true);
        $this->forge->dropTable('company_settings', true);
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('password_reset_tokens', true);
        $this->forge->dropTable('user_refresh_tokens', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('companies', true);
    }
}
