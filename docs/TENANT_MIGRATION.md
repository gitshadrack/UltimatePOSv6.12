# Single-business migration

Superadmin now has a visible **Tenant migration** menu. It offers encrypted Export and Import buttons, plus Artisan commands for large archives or servers with low web-upload limits. The buttons work only in maintenance mode; an HTTP export made while POS/offline sync is active is not a lossless migration.

## Supported path

- MySQL source and destination with identical table definitions, application version, and installed modules.
- An otherwise empty destination database. Existing tenant records are never merged or renumbered; all primary keys and transaction references remain unchanged.
- Matching shared records (for example currencies, permissions and subscription packages) must already exist with the same IDs and contents. The importer rejects missing/different references or any non-empty imported table.
- A private AES-256-encrypted ZIP. The passphrase is prompted interactively and is not stored in the archive or command history.

## Cutover

1. Take a normal full backup of the source. Allow every offline POS device to synchronize, and check there are no queued sales or purchase writes. Stop schedulers, queue workers and other writers. Put the source application in maintenance mode with `php artisan down --secret=YOUR_RANDOM_BYPASS_SECRET` if using the Superadmin buttons, or `php artisan down` for CLI-only operation. Visit `https://source-domain/YOUR_RANDOM_BYPASS_SECRET` in the Superadmin browser to receive the maintenance bypass cookie.
2. On the source, open **Superadmin → Tenant migration → Export** for the business, enter and confirm a passphrase, and download the archive. Alternatively run `php artisan pos:tenant-export BUSINESS_ID`. Both write under `storage/app/tenant-migrations`. Keep the source offline.
3. Copy the encrypted archive to the destination through a secure channel. Provision a fresh database with the same schema and matching shared reference rows. Put the destination in maintenance mode with its own `php artisan down --secret=ANOTHER_RANDOM_BYPASS_SECRET` if using the browser controls.
4. On the destination, enter through its maintenance bypass URL and open **Superadmin → Tenant migration → Import**. Upload the encrypted archive and enter its passphrase to run a dry-run check. Alternatively run `php artisan pos:tenant-import ARCHIVE_PATH`. Resolve any schema, shared-reference, ID, file or data errors before continuing.
5. Type the business ID shown by the dry run, enter the passphrase again, and click **Import business now**. CLI alternative: `php artisan pos:tenant-import ARCHIVE_PATH --commit`. Compare transaction, payment, product, user and media counts with the source, then reconcile sales and payments and open representative invoices and attachments before changing DNS.
6. Bring the destination online only after approval. Keep the source read-only/offline until the new site is accepted. Never write to both sites after the cutover.

## Failure and security notes

Some legacy module tables use MyISAM, which cannot roll back. A failed or interrupted import taints the destination: discard that **disposable destination database** and retry on a new empty one. Never retry on a partially imported database.

The exporter follows declared foreign keys plus explicit links for legacy tables without foreign keys. It includes referenced product images, business logos, transaction/payment documents and media. A missing referenced file stops export. Runtime artifacts such as sessions, OAuth tokens and password reset tokens are not migrated. Tenant-specific credentials or integrations should be reviewed and reconfigured on the new domain.

The archive contains sensitive customer and financial data. Store it outside the web root, transfer it securely, keep the passphrase separate and remove the archive only after the migration and backup retention policy permit it.

If the app stores Laravel-encrypted values in tenant records, the new installation may need the original `APP_KEY` to decrypt them. Review this with the operator before cutover; the migration archive intentionally does **not** contain `.env` or server secrets.
