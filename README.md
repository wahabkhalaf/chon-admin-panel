# Chon Admin Panel

<p align="center">
<a href="https://github.com/zeyadsharo/chon-admin-panel/actions"><img src="https://github.com/zeyadsharo/chon-admin-panel/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Laravel Version"></a>
<a href="https://github.com/zeyadsharo/chon-admin-panel/blob/main/LICENSE"><img src="https://img.shields.io/github/license/zeyadsharo/chon-admin-panel" alt="License"></a>
</p>

The **Chon Admin Panel** is a comprehensive management dashboard built with Laravel 11 and Filament 3. This application provides administrators with powerful tools to manage the Chon gaming platform, including user management, game analytics, notifications, and system configuration.

## 🚀 Features

- **User Management**: Complete CRUD operations for users, roles, and permissions
- **Game Analytics**: Real-time statistics and performance metrics
- **Notification System**: Push notifications via Firebase Cloud Messaging (FCM)
- **Multi-language Support**: Including Kurdish language support
- **Competition Management**: Create and manage gaming competitions
- **Payment System**: Handle in-app purchases and transactions
- **Performance Monitoring**: Built-in performance tracking and optimization
- **Real-time Updates**: Live data updates and monitoring

## 🛠 Technology Stack

- **PHP**: 8.2+
- **Laravel**: 11.x
- **Filament**: 3.2+ (Admin Panel Framework)
- **Database**: PostgreSQL (with optimizations)
- **Testing**: Pest PHP 3.7
- **Containerization**: Docker & Laravel Sail
- **Queue System**: Laravel Queues for background processing
- **Push Notifications**: Firebase Cloud Messaging
- **Frontend**: Tailwind CSS, Livewire

## 📋 Prerequisites

- **Docker** and **Docker Compose**
- **PHP** 8.2 or higher (if running without Docker)
- **Composer** 2.x
- **Node.js** 18+ (for asset compilation)

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/zeyadsharo/chon-admin-panel.git
cd chon-admin-panel
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Update the .env file with your configuration
# Key settings:
# - Database credentials
# - Firebase configuration
# - App URL and name
```

### 3. Install Dependencies

```bash
# PHP dependencies
composer install

# Node.js dependencies (if needed)
npm install
```

### 4. Start with Docker (Recommended)

```bash
# Start all services
./vendor/bin/sail up -d

# Run database migrations and seeders
./vendor/bin/sail artisan migrate --seed

# Generate application key
./vendor/bin/sail artisan key:generate

# Optimize the application
./vendor/bin/sail artisan optimize
```

### 5. Access the Application

- **Main Application**: [http://localhost](http://localhost)
- **Admin Panel**: [http://localhost/admin](http://localhost/admin)
- **API Documentation**: [http://localhost/docs](http://localhost/docs)

## 🧪 Testing

This project uses **Pest PHP** for testing with comprehensive test coverage.

```bash
# Run all tests
./vendor/bin/sail test

# Run specific test suite
./vendor/bin/sail test --testsuite=Feature
./vendor/bin/sail test --testsuite=Unit

# Run tests with coverage
./vendor/bin/sail test --coverage

# Run tests in parallel
./vendor/bin/sail test --parallel
```

## 📊 Performance Monitoring

The application includes built-in performance monitoring tools:

```bash
# Monitor performance in real-time
./real_time_monitor.sh

# Check current performance metrics
./check_performance.sh

# Optimize PostgreSQL
./optimize-postgresql.sh

# View application logs
./view_logs.sh
```

## 🔧 Development Commands

```bash
# Database operations
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan db:seed

# Clear caches
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# Queue management
./vendor/bin/sail artisan queue:work
./vendor/bin/sail artisan queue:restart

# Code quality
./vendor/bin/sail composer pint  # PHP CS Fixer
./vendor/bin/sail test            # Run tests
```

## 🏗 Project Structure

```
chon-admin-panel/
├── app/
│   ├── Filament/           # Filament admin resources
│   ├── Models/             # Eloquent models
│   ├── Services/           # Business logic services
│   └── Jobs/              # Queue jobs
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/          # Database seeders
│   └── optimizations/    # Performance optimizations
├── docs/                 # Project documentation
├── scripts/              # Utility scripts
└── tests/                # Pest PHP tests
```

## 📚 Documentation

- [Flutter App Update API Reference](docs/Flutter-App-Update-API-Reference.md)
- [Firebase FCM Implementation Guide](docs/Flutter-FCM-Implementation-Guide.md)
- [Kurdish Language Support](docs/kurdish-language-support.md)
- [Competition Management](docs/competition/)
- [Payment System](docs/payment-fun.md)
- [Performance Analysis](PERFORMANCE_ANALYSIS.md)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation in the `docs/` directory
