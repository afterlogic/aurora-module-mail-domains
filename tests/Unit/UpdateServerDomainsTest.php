<?php

namespace Aurora\Modules\MailDomains\Tests\Unit;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Mail\Enums\ServerOwnerType;
use Aurora\Modules\Mail\Managers\Servers\Manager;
use Aurora\Modules\Mail\Models\Server;
use Aurora\Modules\MailDomains\Module;
use Aurora\System\Api;
use Aurora\System\Enums\UserRole;
use Aurora\System\Exceptions\ApiException;
use Aurora\System\Notifications;
use PHPUnit\Framework\TestCase;

class UpdateServerDomainsTest extends TestCase
{
    /** @dataProvider updateCases */
    public function testDomainsAreClearedOnlyAfterAuthorizedSuccessfulUpdate(
        bool $result,
        int $role,
        string $ownerType,
        int $serverTenantId,
        bool $accessDenied
    ): void {
        $user = new User();
        $user->Role = $role;
        $user->IdTenant = 1;

        $server = new Server();
        $server->OwnerType = $ownerType;
        $server->TenantId = $serverTenantId;
        $server->Domains = 'example.test';
        $shouldSave = $result && !$accessDenied;

        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServer', 'updateServer'])
            ->getMock();
        $manager->expects($result ? $this->once() : $this->never())->method('getServer')
            ->with(1)->willReturn($server);
        $manager->expects($shouldSave ? $this->once() : $this->never())->method('updateServer')
            ->with($this->identicalTo($server))->willReturn(true);

        $module = $this->getMockBuilder(Module::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServersManager'])
            ->getMock();
        $module->method('getServersManager')->willReturn($manager);

        $authenticatedUser = new \ReflectionProperty(Api::class, 'oAuthenticatedUser');
        $previousUser = $authenticatedUser->isInitialized() ? $authenticatedUser->getValue() : null;
        $authenticatedUser->setValue(null, $user);

        try {
            $module->onAfterUpdateServer(['ServerId' => 1, 'TenantId' => $user->IdTenant], $result);
            $this->assertFalse($accessDenied);
        } catch (ApiException $exception) {
            $this->assertTrue($accessDenied);
            $this->assertSame(Notifications::AccessDenied, $exception->getCode());
        } finally {
            $authenticatedUser->setValue(null, $previousUser);
        }

        $this->assertSame($shouldSave ? '' : 'example.test', $server->Domains);
    }

    public static function updateCases(): array
    {
        return [
            'own tenant server' => [true, UserRole::TenantAdmin, ServerOwnerType::Tenant, 1, false],
            'failed update' => [false, UserRole::TenantAdmin, ServerOwnerType::Tenant, 1, false],
            'another tenant server' => [true, UserRole::TenantAdmin, ServerOwnerType::Tenant, 2, true],
            'tenant admin and global server' => [true, UserRole::TenantAdmin, ServerOwnerType::SuperAdmin, 0, true],
            'normal user' => [true, UserRole::NormalUser, ServerOwnerType::Tenant, 1, true],
            'superadmin and global server' => [true, UserRole::SuperAdmin, ServerOwnerType::SuperAdmin, 0, false],
        ];
    }
}
