#!/bin/bash
cd /opt/keycloak/bin || exit

./kcadm.sh config credentials --server $KC_FRONTEND_URL --realm master --user $KEYCLOAK_ADMIN --password $KEYCLOAK_ADMIN_PASSWORD
echo "Creating service account ${IDP_MANAGEMENT_USER}"
./kcadm.sh create clients -r master -s enabled=true -f - << EOF
{
  "clientId": "${IDP_MANAGEMENT_USER}",
  "name": "Service Account for master realm",
  "secret": "${IDP_MANAGEMENT_SECRET}",
  "enabled": true,
  "alwaysDisplayInConsole": false,
  "clientAuthenticatorType": "client-secret",
  "serviceAccountsEnabled": true,
  "publicClient": false,
  "protocol": "openid-connect",
  "access": {
    "view": true,
    "configure": true,
    "manage": true
  }
}
EOF

echo "Assign Role admin to ${IDP_MANAGEMENT_USER}"
./kcadm.sh add-roles -r master --uusername service-account-"${IDP_MANAGEMENT_USER}" --rolename admin
