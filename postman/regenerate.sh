#!/bin/bash
# Run this from the Laravel project ROOT, e.g.:
#   sh ./postman/regenerate.sh
set -e  # stop immediately if any step fails, instead of silently continuing

echo "🔄 Generating OpenAPI specification..."
php artisan l5-swagger:generate

echo "🔄 Converting to Postman format..."
cd postman
openapi2postmanv2 -s ../storage/api-docs/api-docs.json -o collection_raw.json -p -c ./cli-options-config.json

echo "🔄 Refining collection..."
node refine-collection.cjs

echo "🔄 Enhancing collection..."
node enhance-collection.cjs

echo "🔄 Validating collection..."
node validate-collection.cjs

echo "🔄 Spot-checking for known failure patterns..."
node spot-check-collection.cjs

echo "✅ All done!"
