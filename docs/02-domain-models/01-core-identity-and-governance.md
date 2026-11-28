classDiagram
    class User {
        +int id
        +string name
        +string email
        +isPureSystemAdmin() bool
        +isSystemAdmin() bool
        +isGlobalGovernor() bool
        +hasAdminAccess() bool
        +canVouchUnit(Unit) bool
        +canViewOccupancyRegistry() bool
        +canViewPlainNibShm() bool
        +canViewTreasury() bool
        +canManageTreasury() bool
        +canManageAnnouncements() bool
        +canManageMarketplace() bool
        +accessibleUnitsQuery() Builder
        +assignableGovernanceRoles() array
    }

    class ResidentProfile {
        +int user_id
        +string username
        +string gender
        +string display_name
        +string phone
        +string global_role
        +bool is_verified
        +bool is_ownership_doc_verified
        +encrypted nik_encrypted
        +encrypted kk_encrypted
        +array vehicles
        +array privacy_settings
        +reputation_badge array
    }

    class Unit {
        +int id
        +string code
        +string block
        +string number
        +int sort_order
        +string unit_type
        +string carport_side
        +string zone
        +string occupancy_status
        +array map_coordinates
    }

    class UnitMembership {
        +int id
        +int unit_id
        +int user_id
        +int guarantor_user_id
        +bool is_owner
        +string owner_role
        +bool is_occupant
        +string resident_role
        +string ownership_verification_status
        +encrypted nib_shm_encrypted
        +bool is_primary_contact
        +bool is_approved
        +date valid_until
        +has_nib_shm bool
        +endorsed_by_sentence string
    }

    class OwnershipVerification {
        +int id
        +int unit_membership_id
        +int user_id
        +string role_code
        +string role_label
        +datetime verified_at
    }

    class UnitInvite {
        +int id
        +int unit_id
        +int created_by_user_id
        +string token
        +string entity_type
        +string sub_role
        +date member_valid_until
        +datetime expires_at
        +isUsable() bool
    }

    class VerificationVoucher {
        +int id
        +int target_user_id
        +int unit_id
        +int voucher_user_id
        +string method
        +datetime created_at
    }

    class UserGovernanceRole {
        +int id
        +int user_id
        +string role_code
        +string scope_type
        +string scope_value
        +int assigned_by_user_id
        +bool is_active
        +display_label string
        +permissionMatrix() array
    }

    class GovernanceRoleDefinition {
        +int id
        +string role_code
        +string label
        +string domain
        +string scope
        +array permissions
        +array restrictions
        +bool is_core
        +bool is_active
        +allActiveCached() Collection
    }

    class PasswordRecoveryTicket {
        +int id
        +int user_id
        +string unit_code
        +string code_plain
        +int issued_by_user_id
        +datetime expires_at
        +datetime used_at
        +isUsable() bool
    }

    class AccountAuditLog {
        +int id
        +int target_user_id
        +int actor_user_id
        +string action
        +string reason
        +string ip_address
        +datetime created_at
    }

    User "1" -- "1" ResidentProfile : profile
    User "1" -- "*" UnitMembership : unitMemberships
    User "0..1" -- "*" UnitMembership : guarantor
    Unit "1" -- "*" UnitMembership : memberships
    UnitMembership "1" -- "*" OwnershipVerification : ownershipVerifications
    User "1" -- "*" OwnershipVerification : verifier
    Unit "1" -- "*" UnitInvite : invites
    User "1" -- "*" UnitInvite : creator
    Unit "1" -- "*" VerificationVoucher : unit
    User "1" -- "*" VerificationVoucher : target / voucher
    User "1" -- "*" UserGovernanceRole : governanceRoles
    GovernanceRoleDefinition "1" -- "*" UserGovernanceRole : defines
    User "1" -- "*" PasswordRecoveryTicket : recoveryTickets
    User "1" -- "*" AccountAuditLog : auditLogs