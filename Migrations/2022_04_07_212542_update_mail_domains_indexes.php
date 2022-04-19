<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class UpdateMailDomainsIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::schema()->table('mail_domains', function(Blueprint $table) use ($prefix)
        {
            $sm = Capsule::schema()->getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($prefix . 'mail_domains');

            if (!$doctrineTable->hasIndex('mail_domains_tenantid_index')) {
                $table->index('TenantId');
            }
            if (!$doctrineTable->hasIndex('mail_domains_mailserverid_index')) {
                $table->index('MailserverId');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::schema()->table('mail_domains', function (Blueprint $table) use ($prefix)
        {
            $sm = Capsule::schema()->getConnection()->getDoctrineSchemaManager();
            $doctrineTable = $sm->listTableDetails($prefix . 'mail_domains');

            if ($doctrineTable->hasIndex('mail_domains_tenantid_index')) {
                $table->dropIndex(['TenantId']);
            }
            if ($doctrineTable->hasIndex('mail_domains_mailserverid_index')) {
                $table->dropIndex(['MailserverId']);
            }
        });
    }
}
