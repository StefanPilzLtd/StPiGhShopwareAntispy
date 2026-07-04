# StPiGh Shopware Antispy

Shopware-6.7-Plugin zum Blockieren von Shopware-Services, Usage Data,
Store-/Tracking-Clients, Product Analytics, GMV/Analytics-Apps und
Third-Party-Aufrufen aus der Administration.

Nach Installation/Aktivierung:

```bash
bin/console plugin:refresh
bin/console plugin:install --activate StPiGhShopwareAntispy
bin/console cache:clear
bin/build-administration.sh
```
