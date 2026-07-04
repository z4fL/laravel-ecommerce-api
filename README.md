<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Laravel E Commerce REST API

A production inspired RESTful API built with Laravel for learning, backend portfolio, and software engineering best practices.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-Database-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-Authentication-000000?style=for-the-badge&logo=jsonwebtokens&logoColor=white)
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

* JWT Authentication
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
|------------|-------------|
| Laravel 13 | PHP Framework |
| PHP | Programming Language |
| PostgreSQL | Primary Database |
| JWT | Authentication |
| Swagger / OpenAPI | API Documentation |
| Redis | Cache & Queue |
| Pest | Automated Testing |
| GitHub Actions | Continuous Integration |


## Installation

Clone the repository.

```bash
git clone https://github.com/z4fL/laravel-ecommerce-api.git
cd laravel-ecommerce-api
````

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

Configure your PostgreSQL database inside `.env`.

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_ecommerce
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Run database migrations.

```bash
php artisan migrate
```

Generate the JWT secret.

```bash
php artisan jwt:secret
```

## Running the Application

Start the development server.

```bash
php artisan serve
```

The application will be available at:

```
http://localhost:8000
```


## API Documentation

Swagger documentation is available after generating the API documentation.

Generate Swagger documentation.

```bash
php artisan l5-swagger:generate
```

Open the documentation in your browser.

```
http://localhost:8000/api/documentation
```


## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a feature branch.

```bash
git checkout -b feature/your-feature
```

3. Commit your changes.

```bash
git commit -m "feat: add new feature"
```

4. Push the branch.

```bash
git push origin feature/your-feature
```

5. Open a Pull Request for review.


## License

This project is licensed under the MIT License.
