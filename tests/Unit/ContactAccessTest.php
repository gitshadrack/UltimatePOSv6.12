<?php

namespace Tests\Unit;

use App\Utils\ContactAccess;
use PHPUnit\Framework\TestCase;

class ContactAccessTest extends TestCase
{
    private function user(array $permissions)
    {
        return new class($permissions) {
            public $id = 5;
            public $business_id = 1;
            public $contactAccess;
            private $permissions;

            public function __construct($permissions)
            {
                $this->permissions = $permissions;
                $this->contactAccess = collect([(object) ['id' => 12]]);
            }

            public function can($permission)
            {
                return in_array($permission, $this->permissions, true);
            }
        };
    }

    public function test_customer_permissions_do_not_authorize_supplier_actions()
    {
        foreach (['view', 'create', 'update', 'delete'] as $action) {
            $user = $this->user(['customer.'.$action]);
            $this->assertTrue(ContactAccess::allows($user, 'customer', $action));
            $this->assertFalse(ContactAccess::allows($user, 'supplier', $action));
        }
    }

    public function test_unrelated_own_permission_does_not_block_full_customer_access()
    {
        $contact = (object) ['id' => 10, 'business_id' => 1, 'created_by' => 99];
        $this->assertTrue(ContactAccess::allows($this->user(['customer.view', 'supplier.view_own']), 'customer', 'view', $contact));
    }

    public function test_own_permissions_check_ownership_or_assignment_and_do_not_grant_mutations()
    {
        $user = $this->user(['customer.view_own']);
        $contact = (object) ['id' => 10, 'business_id' => 1, 'created_by' => 99];
        $this->assertFalse(ContactAccess::allows($user, 'customer', 'view', $contact));
        $contact->created_by = 5;
        $this->assertTrue(ContactAccess::allows($user, 'customer', 'view', $contact));
        $this->assertFalse(ContactAccess::allows($user, 'customer', 'update', $contact));
        $contact->created_by = 99;
        $contact->id = 12;
        $this->assertTrue(ContactAccess::allows($user, 'customer', 'view', $contact));
        $contact->business_id = 2;
        $this->assertFalse(ContactAccess::allows($user, 'customer', 'view', $contact));
    }

    public function test_shared_contacts_require_both_permissions_for_mutations()
    {
        $this->assertTrue(ContactAccess::allows($this->user(['customer.view']), 'both', 'view'));
        foreach (['create', 'update', 'delete'] as $action) {
            $this->assertFalse(ContactAccess::allows($this->user(['customer.'.$action]), 'both', $action));
            $this->assertTrue(ContactAccess::allows($this->user(['customer.'.$action, 'supplier.'.$action]), 'both', $action));
        }
        $this->assertFalse(ContactAccess::allows($this->user(['customer.create']), null, 'create'));
    }
}
