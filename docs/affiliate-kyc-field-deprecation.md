# Affiliate KYC Field Deprecation

Affiliate v1 no longer requires document upload. The application flow now uses only lightweight audit fields on `users`.

## Active Fields

- `affiliate_requested_at`
- `affiliate_requirement_acknowledged_at`
- `affiliate_application_note`
- `affiliate_application_meta`

Recommended `affiliate_application_meta` keys:

- `promotion_channel_url`
- `submitted_via`
- `submitted_ip`
- `submitted_user_agent`
- `review_history`
- `review_last`

## Deprecated Compatibility Fields

These columns are kept temporarily for backward compatibility, but the active affiliate flow must not read, write, display, null, or delete files from them:

- `affiliate_identity_document_path`
- `affiliate_support_document_path`
- `affiliate_ktp_document_path`
- `affiliate_selfie_document_path`
- `affiliate_family_card_document_path`

## Pre-Drop Audit

Run this before the future drop-column batch:

```sql
select
    sum(case when affiliate_identity_document_path is not null and affiliate_identity_document_path <> '' then 1 else 0 end) as identity_document_paths,
    sum(case when affiliate_support_document_path is not null and affiliate_support_document_path <> '' then 1 else 0 end) as support_document_paths,
    sum(case when affiliate_ktp_document_path is not null and affiliate_ktp_document_path <> '' then 1 else 0 end) as ktp_document_paths,
    sum(case when affiliate_selfie_document_path is not null and affiliate_selfie_document_path <> '' then 1 else 0 end) as selfie_document_paths,
    sum(case when affiliate_family_card_document_path is not null and affiliate_family_card_document_path <> '' then 1 else 0 end) as family_card_document_paths
from users;
```

If any count is non-zero, archive or intentionally remove the related files from storage before dropping the columns.

## Future Drop Batch

Only after production audit is clean, drop:

- `affiliate_identity_document_path`
- `affiliate_support_document_path`
- `affiliate_ktp_document_path`
- `affiliate_selfie_document_path`
- `affiliate_family_card_document_path`
