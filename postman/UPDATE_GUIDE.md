# Postman Collection Update Guide

This guide explains how to regenerate and update the Postman Collection when the API changes.

## Overview

The Postman Collection is generated from the Laravel API's OpenAPI (Swagger) specification using `openapi-to-postmanv2` converter. The generation process is automated using Node.js scripts.

### Generation Pipeline

```
Laravel Implementation
       ↓
PHP Annotations (OpenAPI)
       ↓
php artisan l5-swagger:generate
       ↓
storage/api-docs/api-docs.json
       ↓
openapi2postmanv2
       ↓
postman/collection_raw.json
       ↓
refine-collection.cjs
       ↓
postman/collection.json (organized by role)
       ↓
enhance-collection.cjs
       ↓
postman/collection.json (final with webhooks, etc)
```

## Prerequisites

- **openapi-to-postmanv2**: Must be globally installed
  ```bash
  npm install -g @postmanlabs/openapi-to-postmanv2
  ```

- **Node.js v14+**: For running refinement scripts

- **Laravel Artisan**: For generating OpenAPI spec

## Step 1: Update API Documentation

Ensure your API changes are documented with OpenAPI annotations:

```php
/**
 * @OA\Get(
 *     path="/api/v1/products",
 *     tags={"Products"},
 *     summary="List products",
 *     security={{"api_token":{}}},
 *     @OA\Response(response=200, description="Success"),
 * )
 */
```

See `app/Http/Controllers/Api/` for examples.

## Step 2: Generate OpenAPI Specification

Regenerate the OpenAPI spec from code:

```bash
cd /path/to/laravel-ecommerce-api
php artisan l5-swagger:generate
```

This creates: `storage/api-docs/api-docs.json`

Verify the spec is valid:
```bash
# Check if file was updated
ls -la storage/api-docs/api-docs.json
```

## Step 3: Generate Raw Postman Collection

Use `openapi-to-postmanv2` to convert the OpenAPI spec to Postman format:

```bash
cd postman
openapi2postmanv2 -s ../storage/api-docs/api-docs.json \
  -o collection_raw.json --pretty
```

This creates: `postman/collection_raw.json`

**Expected output:**
```
Conversion successful, collection written to file
```

## Step 4: Refine Collection

Run the refinement script to organize endpoints by role and domain:

```bash
node refine-collection.cjs
```

This:
- Organizes endpoints by user role (public, customer, seller, admin)
- Groups by endpoint domain (auth, products, orders, etc)
- Adds authentication headers per role
- Adds role/verification documentation
- Creates: `postman/collection.json` and `postman/environment.json`

## Step 5: Enhance Collection

Run the enhancement script to add special handling:

```bash
node enhance-collection.cjs
```

This:
- Converts product image endpoints to multipart/form-data
- Configures Midtrans webhook endpoint
- Adds comprehensive documentation
- Adds realistic request examples

## Step 6: Validate Collection

Verify the collection is properly formatted:

```bash
node validate-collection.cjs
```

Expected output:
```
✅ Validation PASSED
📦 Collection Ready for Import
```

## Full Regeneration Script

To regenerate everything at once:

```bash
#!/bin/bash
cd /path/to/laravel-ecommerce-api

# 1. Regenerate OpenAPI spec
echo "🔄 Generating OpenAPI specification..."
php artisan l5-swagger:generate

# 2. Convert to Postman
echo "🔄 Converting to Postman format..."
cd postman
openapi2postmanv2 -s ../storage/api-docs/api-docs.json \
  -o collection_raw.json --pretty

# 3. Refine collection
echo "🔄 Refining collection..."
node refine-collection.cjs

# 4. Enhance collection
echo "🔄 Enhancing collection..."
node enhance-collection.cjs

# 5. Validate
echo "🔄 Validating collection..."
node validate-collection.cjs

echo "✅ All done!"
```

Save as `postman/regenerate.sh` (Linux/Mac) or `postman/regenerate.bat` (Windows PowerShell).

## Windows PowerShell Script

Create `postman/regenerate.ps1`:

```powershell
# Regenerate Postman Collection (Windows PowerShell)

Write-Host "🔄 Generating OpenAPI specification..." -ForegroundColor Green
Set-Location ..
php artisan l5-swagger:generate

Write-Host "🔄 Converting to Postman format..." -ForegroundColor Green
Set-Location postman
openapi2postmanv2 -s ../storage/api-docs/api-docs.json `
  -o collection_raw.json --pretty

Write-Host "🔄 Refining collection..." -ForegroundColor Green
node refine-collection.cjs

Write-Host "🔄 Enhancing collection..." -ForegroundColor Green
node enhance-collection.cjs

Write-Host "🔄 Validating collection..." -ForegroundColor Green
node validate-collection.cjs

Write-Host "✅ Done!" -ForegroundColor Green
```

Run with:
```powershell
.\regenerate.ps1
```

## When to Regenerate

Regenerate the collection when:

✅ **New API endpoints** are added
✅ **Existing endpoints** are modified (methods, parameters, responses)
✅ **Authentication requirements** change
✅ **Response structures** change
✅ **Error responses** are updated
✅ **Validation rules** change

❌ **Do NOT regenerate** for:
- Documentation changes in this README
- Local Postman scripts/tests you've added
- Environment variable values
- Request order or folder organization (if you customized)

## Merging Changes

If you've customized the collection locally:

### Option 1: Full Regenerate (Recommended for major changes)
```bash
# Backup your customizations
cp collection.json collection.backup.json

# Regenerate
./regenerate.sh (or .ps1)

# Review differences
diff collection.backup.json collection.json

# Re-apply customizations if needed
```

### Option 2: Selective Update
If you only added tests/pre-request scripts:
1. Save your scripts (copy them from the old collection)
2. Regenerate the collection
3. Re-add your scripts to the new collection

### Option 3: Sync Mode
If you have advanced customizations, use `openapi2postmanv2` sync mode:

```bash
openapi2postmanv2 -s ../storage/api-docs/api-docs.json \
  --sync collection.json \
  --sync-options syncExamples=true \
  -o collection_synced.json
```

Then review `collection_synced.json` for changes.

## Troubleshooting

### "openapi2postmanv2: command not found"
Install globally:
```bash
npm install -g @postmanlabs/openapi-to-postmanv2
```

### "php artisan not found"
Ensure you're in the Laravel project root:
```bash
cd /path/to/laravel-ecommerce-api
```

### OpenAPI spec has errors
Check the PHP controller annotations:
```bash
cat storage/api-docs/api-docs.json | python -m json.tool | head -50
```

Look for common issues:
- Missing `@OA\` annotations
- Incorrect response schema references
- Missing required fields in `@OA\Parameter`

### Collection has wrong endpoints
1. Verify OpenAPI spec: `php artisan l5-swagger:generate`
2. Check raw collection: `collection_raw.json`
3. Re-run refinement scripts

### Environment variables not working
Ensure variables are named exactly:
- `{{baseUrl}}`
- `{{customer_token}}`
- `{{seller_token}}`
- `{{admin_token}}`

Use double braces: `{{ }}`

## Tips

### Keep OpenAPI Up-to-Date
Review and update OpenAPI annotations in controllers whenever you:
- Add new endpoints
- Change HTTP methods
- Add/remove parameters
- Change response structures

### Test Generated Collection
After regeneration:
1. Import the new collection into Postman
2. Test a few endpoints from each role
3. Verify tokens are correctly positioned
4. Check multipart/form-data endpoints work
5. Verify webhook endpoint is correctly documented

### Version Control
```bash
# Track collection changes
git add postman/collection.json
git add postman/environment.json
git add postman/README.md

# Exclude raw/temporary files (see .gitignore)
git add postman/.gitignore

# Don't commit scripts if they change frequently
# Or commit them with .gitignore for sensitive data
```

### Documentation Updates
When regenerating:
1. Review README.md for any needed updates
2. Update workflows if endpoints changed
3. Update authentication section if auth logic changed
4. Add notes about breaking changes

## Support

For issues:

1. **OpenAPI annotations**: See L5-Swagger docs at `l5-swagger.php` config
2. **Postman importing**: Check Postman documentation
3. **API changes**: Verify endpoints actually exist in Laravel routes
4. **Validation errors**: Run `validate-collection.cjs` for detailed feedback

---

**Last Updated**: 2024-2025
**Collection Version**: 2.1.0
