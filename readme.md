# Ultimate POS - Sysnettechs Customization

## About

Ultimate POS is a POS application by [Ultimate Fosters](http://ultimatefosters.com), a brand of [The Web Fosters](http://thewebfosters.com). This repository contains the Sysnettechs-maintained installation and its documented customizations.

## Documentation

### Start here

- [Customizations index](docs/CUSTOMIZATIONS.md)
- [Operations and deployment](docs/OPERATIONS_AND_DEPLOYMENT.md)
- [Official UltimatePOS documentation](http://ultimatefosters.com/ultimate-pos/)

### Feature documentation

- [M-PESA and payments](docs/features/MPESA_AND_PAYMENTS.md)
- [POS and sales](docs/features/POS_AND_SALES.md)
- [Inventory and reporting](docs/features/INVENTORY_AND_REPORTING.md)
- [Invoices and tax](docs/features/INVOICES_AND_TAX.md)
- [Tenancy and Superadmin](docs/features/TENANCY_AND_SUPERADMIN.md)
- [Windows Print Server](tools/windows-print-server/README.md)

### Plans and specialist reports

- [Restaurant POS implementation plan](docs/RESTAURANT_POS_IMPLEMENTATION_PLAN.md)
- [Visual invoice designer plan](docs/VISUAL_INVOICE_DESIGNER_PLAN.md)
- [M-PESA payment recovery report](docs/MPESA_PAYMENT_RECOVERY_REPORT.md)
- [Database optimization document](docs/DBOptimizationa.docx)

## Common deployment commands

Back up the live database before migrations or major maintenance.

```bash
php artisan migrate
php artisan optimize:clear
```

For production cache rebuilding:

```bash
php artisan optimize
php artisan event:cache
php artisan view:cache
```

See [Operations and Deployment](docs/OPERATIONS_AND_DEPLOYMENT.md) for the complete deployment process, precautions, cache instructions, and migration safety notes.

## Documentation maintenance

- Keep this root file concise and use it as the documentation homepage.
- Add implementation details to the appropriate topic document.
- Add new topic documents to both this page and the [customizations index](docs/CUSTOMIZATIONS.md).
- Document permissions, migrations, changed files, deployment commands, testing, troubleshooting, and rollback precautions.
- Clearly label planned features separately from implemented functionality.

## Security vulnerabilities

If you discover a security vulnerability within Ultimate POS, contact the upstream support team at thewebfosters@gmail.com and notify the system administrator responsible for this deployment.

## License

Ultimate POS is licensed under the [CodeCanyon standard license](https://codecanyon.net/licenses/standard).
