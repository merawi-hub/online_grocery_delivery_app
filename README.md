# 🛒 Online Grocery Delivery App

> A comprehensive online grocery delivery platform for supermarkets with role-based workflows for customers, managers, deliverers, and administrators.

[![Laravel](https://img.shields.io/badge/Laravel-10+-red.svg)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3+-green.svg)](https://vuejs.org)
[![License](https://img.shields.io/badge/license-Proprietary-blue.svg)](LICENSE)

---

## 🌟 Features

### For Customers
- ✅ **No Login Required** - Browse and order without registration
- 🔍 **Smart Search** - Find products easily with filters and categories
- 📦 **Real-time Stock** - View live inventory availability
- 🛒 **Easy Checkout** - Simple ordering process
- 📍 **Order Tracking** - Real-time delivery status updates
- 🔐 **OTP Confirmation** - Secure delivery handover

### For Store Managers
- 📊 **Inventory Management** - Add, edit, and manage branch products
- 💰 **Price Control** - Set branch-specific pricing
- 📋 **Order Processing** - Assign pickers and deliverers
- 👥 **Staff Coordination** - Monitor team performance
- 📈 **Reports** - Sales and inventory analytics

### For Administrators
- 🏢 **Branch Management** - Oversee all store locations
- 👨‍💼 **Staff Management** - Hire, assign, and manage employees
- 📦 **Master Catalog** - Control global product listings
- 📊 **System Analytics** - Comprehensive performance metrics
- 🔄 **Flexible Operations** - Switch managers and assign staff

### For Deliverers
- 🚚 **Delivery Queue** - View and accept available deliveries
- 🗺️ **Navigation** - Integrated mapping for routes
- 💵 **Earnings Tracker** - Monitor income and performance
- ⭐ **Ratings** - Build reputation through good service
- 📱 **Mobile Optimized** - Easy to use on the go

---

## 🎯 User Workflow

### Customer Journey
```
1. Browse products by category or search
2. Add items to shopping cart
3. Enter delivery address
4. Place order (receive order number & OTP)
5. Track order status in real-time
6. Receive delivery and confirm with OTP
```

### Operational Flow
```
1. Order placed by customer
2. Manager assigns picker
3. Picker collects items from inventory
4. Picker handles substitutions if needed
5. Manager assigns deliverer
6. Deliverer picks up order
7. Deliverer navigates to customer
8. Customer confirms with OTP
9. Order completed
```

---

## 🏗️ Architecture

### Tech Stack

**Backend:**
- Laravel 10+ (PHP 8.1+)
- MySQL 8.0+
- Laravel Sanctum (Authentication)
- RESTful API

**Frontend:**
- Vue.js 3
- Vite
- Pinia (State Management)
- Vue Router
- Axios

**Development:**
- Composer
- npm/Node.js
- Git

---

## 🚀 Quick Start

### Prerequisites

Ensure you have the following installed:
- PHP 8.1 or higher
- Composer
- Node.js 18 or higher
- MySQL 8.0 or higher

### Backend Setup

```bash
# Navigate to server directory
cd server

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database in .env
# DB_DATABASE=your_database_name
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run migrations and seed data
php artisan migrate:fresh --seed

# Start development server
php artisan serve
```

Backend will be available at `http://localhost:8000`

### Frontend Setup

```bash
# Navigate to client directory
cd client

# Install dependencies
npm install

# Start development server
npm run dev
```

Frontend will be available at `http://localhost:5173`

---

## 📁 Project Structure

```
online-grocery-app/
│
├── server/                    # Laravel Backend
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   │           ├── AdminController.php
│   │   │           ├── ManagerController.php
│   │   │           ├── DriverController.php
│   │   │           ├── OrderController.php
│   │   │           └── CatalogController.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Branch.php
│   │   │   ├── Product.php
│   │   │   └── Order.php
│   │   └── ...
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   │   └── api.php
│   └── ...
│
├── client/                    # Vue Frontend
│   ├── src/
│   │   ├── views/
│   │   │   ├── customer/     # Customer browsing & ordering
│   │   │   ├── manager/      # Store manager dashboard
│   │   │   ├── admin/        # Admin control panel
│   │   │   └── driver/       # Deliverer interface
│   │   ├── components/       # Reusable components
│   │   ├── stores/           # Pinia state management
│   │   ├── router/           # Vue Router config
│   │   └── services/         # API services
│   └── ...
│
├── PROJECT_SPEC.md           # Detailed specifications
├── README.md                 # This file
└── LICENSE
```

---

## 👥 User Roles

| Role | Access Level | Key Responsibilities |
|------|-------------|---------------------|
| **Customer** | Public | Browse, order, track deliveries |
| **Store Manager** | Branch-level | Manage inventory, process orders, assign staff |
| **Admin** | System-wide | Manage branches, staff, master catalog |
| **Deliverer** | Assigned deliveries | Accept and complete deliveries |
| **Picker** | Assigned orders | Collect and pack items |

---

## 🔐 Authentication

### Customer
- **Optional** - Can browse and order without login
- Can register for order history and saved addresses

### Staff (Manager, Deliverer, Picker)
- **Required** - Must authenticate to access dashboard
- Access scoped to assigned branch/deliveries

### Admin
- **Required** - Full system access
- Can manage all branches and staff

---

## 📊 Order Status Flow

```
placed
  ↓
picking (picker assigned)
  ↓
substitutions_review (if items unavailable)
  ↓
packed (ready for delivery)
  ↓
driver_assigned
  ↓
out_for_delivery
  ↓
delivered (OTP confirmed)
```

---

## 🔗 API Endpoints

### Public Endpoints (No Auth)
- `GET /api/catalog/products` - Browse products
- `GET /api/catalog/categories` - List categories
- `POST /api/orders` - Place order
- `GET /api/orders/{number}/track` - Track order

### Manager Endpoints
- `GET /api/manager/dashboard` - Dashboard stats
- `POST /api/manager/inventory/add-product` - Add product to branch
- `PUT /api/manager/orders/{id}/assign-picker` - Assign picker
- `PUT /api/manager/orders/{id}/assign-driver` - Assign deliverer

### Admin Endpoints
- `POST /api/admin/managers` - Create manager
- `POST /api/admin/drivers` - Hire deliverer
- `PUT /api/admin/managers/{id}/assign-branch` - Assign manager to branch
- `GET /api/admin/reports/overview` - System reports

### Deliverer Endpoints
- `GET /api/driver/deliveries/available` - Available deliveries
- `POST /api/driver/deliveries/{id}/accept` - Accept delivery
- `POST /api/driver/deliveries/{id}/complete` - Complete delivery

**Full API documentation:** See [API.md](./docs/API.md)

---

## 📱 Responsive Design

The application is fully responsive and optimized for:
- 📱 Mobile devices (customers and deliverers)
- 💻 Tablets (managers and pickers)
- 🖥️ Desktop (admins and managers)

---

## 🧪 Testing

### Backend Tests
```bash
cd server
php artisan test
```

### Frontend Tests
```bash
cd client
npm run test
```

---

## 🚀 Deployment

### Production Setup

#### Backend (Laravel)
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Set up queue worker
php artisan queue:work --daemon
```

#### Frontend (Vue)
```bash
# Build for production
npm run build

# Output will be in dist/ directory
```

### Environment Variables

#### Backend (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=grocery_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
```

#### Frontend (.env)
```env
VITE_API_URL=https://api.your-domain.com
```

---

## 📈 Performance

- ⚡ **Fast Loading** - Optimized bundle sizes
- 🔄 **Real-time Updates** - Live order status
- 💾 **Efficient Caching** - Reduced server load
- 📊 **Scalable** - Handles high traffic

---

## 🤝 Contributing

This is a proprietary project. For internal development:

1. Create a feature branch
2. Make your changes
3. Test thoroughly
4. Submit a pull request
5. Wait for code review

---

## 📝 Documentation

- [Project Specification](./PROJECT_SPEC.md) - Detailed requirements
- [API Documentation](./docs/API.md) - Complete API reference
- [Database Schema](./docs/DATABASE.md) - Database structure
- [Implementation Roadmap](./docs/ROADMAP.md) - Development timeline

---

## 🐛 Troubleshooting

### Common Issues

**Database connection failed:**
```bash
# Check .env configuration
# Ensure MySQL is running
# Verify credentials
```

**Frontend can't connect to backend:**
```bash
# Check VITE_API_URL in client/.env
# Verify CORS settings in server/config/cors.php
# Ensure backend is running
```

**Migration errors:**
```bash
# Reset and re-run migrations
php artisan migrate:fresh --seed
```

---

## 📞 Support

For technical support or questions:
- Email: dev@yourcompany.com
- Slack: #grocery-app-dev
- Issues: Create a GitHub issue

---

## 🎯 Roadmap

- [x] Database schema design
- [x] Authentication system
- [x] API endpoints structure
- [ ] Backend controllers
- [ ] Frontend dashboards
- [ ] Real-time tracking
- [ ] Payment integration
- [ ] Mobile app
- [ ] Analytics dashboard
- [ ] Performance optimization

---

## 📄 License

Proprietary - All rights reserved © 2024

---

## 👨‍💻 Development Team

- **Backend Lead:** Laravel API development
- **Frontend Lead:** Vue.js interface
- **Database Architect:** Schema design
- **DevOps Engineer:** Deployment & CI/CD
- **QA Engineer:** Testing & quality assurance

---

## 🙏 Acknowledgments

- Laravel framework
- Vue.js team
- All contributors and testers

---

**Happy Coding! 🚀**
