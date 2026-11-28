classDiagram
    class User {
        +int id
        +string name
    }

    class Unit {
        +int id
        +string code
        +string block
        +string zone
    }

    class CashLedgerEntry {
        +int id
        +date entry_date
        +string type
        +string category
        +int amount
        +string payment_method
        +string description
        +int unit_id
        +int recorded_by_user_id
    }

    class DuesPayment {
        +int id
        +int unit_id
        +string dues_type
        +string period_month
        +int amount
        +string status
        +string payment_method
        +date paid_at
        +int cash_ledger_entry_id
    }

    class UmkmStore {
        +int id
        +int unit_id
        +int owner_user_id
        +string store_name
        +string category
        +string status
        +bool is_featured
        +datetime featured_until
        +bool is_suspended
        +array catalog_items
        +is_currently_featured bool
    }

    class Aspiration {
        +int id
        +int user_id
        +int unit_id
        +string category
        +string title
        +string status
        +int target_signatures
    }

    class AspirationSignature {
        +int id
        +int aspiration_id
        +int unit_id
        +int user_id
        +string note
    }

    class Announcement {
        +int id
        +string title
        +string category
        +bool is_pinned
        +bool is_published
        +int created_by_user_id
    }

    class EnvironmentAgenda {
        +int id
        +string title
        +string category
        +date event_date
        +string visibility
        +array target_blocks
        +scopeVisibleTo(Builder, User) Builder
    }

    class MeetingMinute {
        +int id
        +string title
        +date meeting_date
        +string category
        +string visibility
        +int created_by_user_id
    }

    class NewsArticle {
        +int id
        +int user_id
        +int unit_id
        +string title
        +string slug
        +string layout
        +string status
        +date published_date
        +generateUniqueSlug() string
    }

    class WhatsappGroupContact {
        +int id
        +string phone
        +string wa_display_name
        +array groups
        +array auto_labels
        +array manual_labels
        +string source_note
        +string detected_unit_code
        +int matched_user_id
        +int matched_unit_id
        +string reconciliation_status
        +string admin_notes
    }

    Unit "1" -- "*" DuesPayment : duesPayments
    Unit "0..1" -- "*" CashLedgerEntry : unit
    CashLedgerEntry "0..1" -- "0..1" DuesPayment : ledgerEntry
    User "1" -- "*" CashLedgerEntry : recorder

    Unit "1" -- "*" UmkmStore : umkmStores
    User "1" -- "0..1" UmkmStore : owner
    User "0..1" -- "*" UmkmStore : curator

    Unit "1" -- "*" Aspiration : unit
    User "1" -- "*" Aspiration : author
    Aspiration "1" -- "*" AspirationSignature : signatures
    Unit "1" -- "*" AspirationSignature : unitVote
    User "1" -- "*" AspirationSignature : signer

    User "1" -- "*" Announcement : author
    User "1" -- "*" EnvironmentAgenda : author
    EnvironmentAgenda "*" -- "*" User : invitedUsers
    User "1" -- "*" MeetingMinute : author
    User "1" -- "*" NewsArticle : author
    Unit "0..1" -- "*" NewsArticle : unit

    User "0..1" -- "*" WhatsappGroupContact : matchedUser
    Unit "0..1" -- "*" WhatsappGroupContact : matchedUnit