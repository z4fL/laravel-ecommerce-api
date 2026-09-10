<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Laravel E Commerce REST API

A production inspired RESTful API built with Laravel for learning, backend portfolio, and software engineering best practices.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Database-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-Authentication-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
![Swagger](https://img.shields.io/badge/Swagger-OpenAPI-85EA2D?style=for-the-badge&logo=swagger&logoColor=black)
![Redis](https://img.shields.io/badge/Redis-Cache-DC382D?style=for-the-badge&logo=redis&logoColor=white)
![Pest](https://img.shields.io/badge/Pest-Testing-366488?style=for-the-badge&logo=phpunit&logoColor=white)
![License](https://img.shields.io/badge/MIT-green?style=for-the-badge)

## About

This project is a production inspired RESTful API for an E Commerce application built using Laravel.

The primary goal is to practice backend software engineering by applying RESTful API design, authentication, authorization, clean architecture, testing, documentation, and development workflows commonly used in real world projects.

This project is developed incrementally using GitHub Issues and Milestones, where each issue represents a single unit of work.

## Features

Current and planned features include:

* Sanctum API Token Authentication
* Role Based Access Control
* User Profile Management
* Product Management
* Shopping Cart
* Order & Checkout
* Payment Integration
* Inventory Management
* Global API Response
* Global Exception Handling
* API Versioning
* Swagger Documentation
* Structured Logging
* Automated Testing

## Tech Stack

| Technology | Description |
| ------------ | ------------- |
| Laravel 13 | PHP Framework |
| PHP | Programming Language |
| PostgreSQL | Primary Database |
| Sanctum | Authentication |
| Swagger / OpenAPI | API Documentation |
| Redis | Cache & Queue |
| Pest | Automated Testing |
| GitHub Actions | Continuous Integration |

## Requirements

The versions and services below are required by the repository configuration:

| Requirement | Version / configuration | Tested with |
| ------------- | ------------------------- | -------------- |
| PHP | `^8.5` | 8.5.x |
| Composer | Not pinned by repo | 2.10 |
| PostgreSQL | Required by `.env.example` & test config, not pinned | 18.3 |
| Redis | Required by `QUEUE_CONNECTION=redis` & `CACHE_STORE=redis`, not pinned | 8.10 |
| Node.js | Required to run openapi-to-postmanv2 for regenerating the Postman collection | 24.15 |

## Installation

Clone the repository.

```bash
git clone https://github.com/z4fL/laravel-ecommerce-api.git
cd laravel-ecommerce-api
```

Install dependencies.

```bash
composer install
```

Create the environment file.

```bash
cp .env.example .env
```

Generate the application key.

```bash
php artisan key:generate
```

Configure the environment inside `.env`.

`DB_DATABASE` must refer to an existing PostgreSQL database. The migrations create the application tables, including users, cache, jobs, personal access tokens, categories, tags, stores, products, carts, orders, payments, inventory histories, and audit logs.

Redis is used for both the default cache store and the queue. Make a Redis service available at the `REDIS_HOST` and `REDIS_PORT` values above, then adjust `REDIS_PASSWORD` when authentication is enabled. Install Redis locally following the official guide for your platform (Linux/macOS: native package manager; Windows: via WSL, since Redis does not provide official native Windows binaries). This repository does not contain a Docker Compose file, so containerized setup is not documented here.

Run database migrations.

```bash
php artisan migrate
```

To process the Redis-backed queue, run the queue worker in another terminal:

```bash
php artisan queue:work redis
```

## Running the Application

Start the development server.

```bash
php artisan serve
```

The application will be available at:

```url
http://localhost:8000
```

## API Documentation

Swagger documentation is available after generating the API documentation.

Generate Swagger documentation.

```bash
php artisan l5-swagger:generate
```

Open the documentation in your browser.

```url
http://localhost:8000/api/documentation
```

### Postman Collection

The OpenAPI specification can be converted into a Postman collection using `openapi-to-postmanv2`.

Install the Node dependencies.

```​bash
npm install
```

Generate the Postman collection.

```bash
sh ./postman/regenerate.sh
```

This creates `collection.json` and `environment.json` in the postman folder for import into Postman.

## Testing

The project uses Pest through Laravel's test runner. The configured test suites are `Unit`, `Feature`, and `Integration`.

Create the PostgreSQL database named by `phpunit.xml` (`ecommerce_api_testing`) before running the tests. The test configuration uses PostgreSQL on `127.0.0.1:5432`, while the cache store is changed to `array` and the queue connection remains `redis`.

Run all tests with either command:

```bash
php artisan test
composer test
```

The CI workflow also checks code style with:

```bash
./vendor/bin/pint --test
```

## API Usage

All API v1 routes use the `/api/v1` prefix. The following public endpoints are implemented in `routes/api/v1/public.php`.

Register a customer:

```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
 -H "Content-Type: application/json" \
 -d '{
  "name": "John Doe",
  "username": "john-doe",
  "email": "john@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!",
  "phone": "085222555111"
 }'
```

The successful response has HTTP `201` and returns the token in `data.access_token`:

```json
{
 "success": true,
 "message": "User created successfully.",
 "data": {
  "user": {
   "id": 1,
   "name": "John Doe",
   "username": "john-doe",
   "email": "john@example.com",
   "created_at": "..."
  },
  "access_token": "...",
  "token_type": "Bearer"
 }
}
```

List published products without authentication:

```bash
curl "http://localhost:8000/api/v1/products?per_page=1"
```

The response contains product data and pagination metadata:

```json
{
 "success": true,
 "message": "Request completed successfully.",
 "data": [
  {
   "id": 1,
   "sku": "...",
   "name": "...",
   "slug": "...",
   "description": "...",
   "price": "...",
   "status": "published",
   "stock": 1,
   "store": {},
   "category": {},
   "tags": [],
   "images": []
  }
 ],
 "meta": {
  "total": 1,
  "per_page": 1,
  "current_page": 1,
  "last_page": 1,
  "count": 1,
  "from": 1,
  "to": 1
 },
 "links": {
  "first": "...",
  "last": "...",
  "prev": null,
  "next": null,
  "path": "http://localhost:8000/api/v1/products"
 }
}
```

## Contributing

This project is developed incrementally using GitHub Issues and Milestones. Treat one issue as one unit of work:

1. Choose or create an issue from the relevant milestone.
2. Implement the scope described by that issue.
3. Run the automated tests and the CI code-style check locally.
4. Open a Pull Request that references the issue and describes the verification performed.

```bash
php artisan test
./vendor/bin/pint --test
```

## License

This project is licensed under the MIT License.
