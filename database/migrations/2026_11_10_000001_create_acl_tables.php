<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * The new ACL (v3), managed under /app/acl. Lives next to the old ACL
     * (role_groups, resources, role_resources, user_roles, special_privileges)
     * until everything has moved over.
     *
     * - acl_permissions:     one thing a user can be allowed to do (key: employees.worker-info.view),
     *                        optionally bound to an app module and an app module route.
     * - acl_roles:           a named set of permissions.
     * - acl_role_permission: which permissions a role holds.
     * - acl_user_role:       which roles a user has.
     * - acl_user_permission: per-user overrides, allow = TRUE grants, allow = FALSE denies
     *                        (a deny wins over every role).
     */
    public function up(): void
    {
        Schema::create('acl_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->uuid('module_id')->nullable()->index();
            $table->uuid('route_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('acl_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('active')->default(TRUE);
            $table->timestamps();
        });

        Schema::create('acl_role_permission', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('acl_roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('acl_permissions')->cascadeOnDelete();
        });

        Schema::create('acl_user_role', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->uuid('role_id');
            $table->primary(['user_id', 'role_id']);
            $table->foreign('role_id')->references('id')->on('acl_roles')->cascadeOnDelete();
        });

        Schema::create('acl_user_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->uuid('permission_id');
            $table->boolean('allow')->default(TRUE);
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
            $table->foreign('permission_id')->references('id')->on('acl_permissions')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acl_user_permission');
        Schema::dropIfExists('acl_user_role');
        Schema::dropIfExists('acl_role_permission');
        Schema::dropIfExists('acl_roles');
        Schema::dropIfExists('acl_permissions');
    }
};
