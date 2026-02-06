<p align="center">
  <a href="https://github.com/rostam-sodagari/glide">
    <img src="https://img.shields.io/badge/Laravel-12-green.svg" alt="Laravel 12">
    <img src="https://img.shields.io/badge/PHP-8.2+-blue.svg" alt="PHP 8.2+">
    <img src="https://img.shields.io/badge/Docker-support-orange.svg" alt="Docker">
    <img src="https://img.shields.io/badge/License-MIT-brightgreen.svg" alt="License">
  </a>
</p>

<h1 align="center">Glide 🚀</h1>

<p align="center">
  A self‑hosted deployment and management platform for small and medium‑sized web applications.
</p>


## Overview

**Glide** is a backend‑centric platform built with **Laravel**, designed to help teams deploy, manage, and monitor applications on VPS or cloud platforms such as **AWS** and **GCP**.  
It provides a streamlined developer experience similar in spirit to **Laravel Forge** and **Coolify**, with an emphasis on clean architecture, automation, and operational clarity.


## Objectives

- Provide a sensible default deployment flow for Laravel and containerised apps.  
- Automate repetitive tasks such as installing dependencies and running migrations.  
- Keep configurations and secrets neatly organised by environment.  
- Offer visibility into deployment history and system health.  
- Integrate smoothly into modern workflows with Git, containers, and CI/CD.


## Planned Features

### Projects & Environments
- Manage multiple environments (e.g. development, staging, production).
- Configure unique deployment settings per environment.

### Git Integration
- Connect repositories from **GitHub** or **GitLab**.  
- Trigger deployments via webhooks or from the UI.

### Deployment Pipeline (Laravel‑focused)
- Clone from Git remotes and install Composer/NPM dependencies.  
- Run automated tests before deployment.  
- Apply migrations and optimisation commands.  
- Support rollbacks if deployment fails.

### Containers
- Deploy with **Docker** or **Docker Compose**.  
- Run non‑PHP services as container images alongside your Laravel apps.

### Monitoring & Logs
- Perform basic health checks for deployed services.  
- View deployment history, status, and timestamps.  
- Access recent deployment logs directly from the interface.

### Configuration & Secrets
- Store environment variables and secrets in encrypted form.  
- Keep environment‑specific configurations isolated and updatable.


## Technology Stack

### Backend
- **Laravel** (PHP)  
- **MySQL**, **PostgreSQL**, or **SQL Server**  
- **Redis** for queues and caching  

### Infrastructure & DevOps
- **Docker** & **Docker Compose**  
- **Kubernetes** (planned)  
- **Cloud Integrations:**  
  - AWS (planned)  
  - Google Cloud Platform (planned)  
- **CI/CD Support:**  
  - GitHub Actions  
  - GitLab CI  

### Languages
- **PHP**  
- **JavaScript / TypeScript**  
- **SQL**


## Roadmap

### MVP
- User authentication and session management.  
- Basic project & environment management.  
- Deploy Laravel apps to a single VPS.  
- Display deployment logs and status.

### v1.0
- GitHub & GitLab repository integration.  
- Docker‑based deployment.  
- UI for environment variables & secrets.  
- Health checks and monitoring views.

### v1.x
- Billing and subscriptions via Stripe & PayPal.  
- Enhanced AWS/GCP integration.  
- Advanced metrics and alerts.  
- Kubernetes deployment support.


## Local Development

**Setup (subject to change):**

1. Clone the repository:
   
   ```bash
   git clone https://github.com/rostam-sodagari/glide.git
   cd glide
    ```
2. Copy the environment file:
   
    ```bass
    .example .env
    ```

3. Configure your database and Redis credentials.
4. Install dependencies:
    ```bash
    composer install
    npm install
    ```
5. Run migrations:
    ```bash
    php artisan migrate
    ```

6. Start the stack:
    ```bash
    docker-compose up -d
    ```
    or
    ```bash
    php artisan serve
    ```

## License


MIT © Rostam Sodagari
