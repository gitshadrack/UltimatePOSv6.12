<?php

namespace App\Utils;

class ContactAccess
{
    public static function allows($user, $type, $action, $contact = null): bool
    {
        if (! in_array($type, ['customer', 'supplier', 'both'], true)) {
            return false;
        }
        if ($contact && (string) $contact->business_id !== (string) $user->business_id) {
            return false;
        }
        $types = $type === 'both' ? ['customer', 'supplier'] : [$type];
        $allowed = [];
        foreach ($types as $role) {
            $can = $user->can($role.'.'.$action);
            if (! $can && $action === 'view' && $contact && $user->can($role.'.view_own')) {
                $can = (string) $contact->created_by === (string) $user->id
                    || $user->contactAccess->contains('id', $contact->id);
            }
            $allowed[] = $can;
        }

        // Shared contacts can be viewed from either list, but changing them affects both roles.
        return $action === 'view' ? in_array(true, $allowed, true) : ! in_array(false, $allowed, true);
    }
}
