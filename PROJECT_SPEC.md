# 🛒 Online Grocery Delivery App - Project Specification

## Overview
A comprehensive online grocery delivery application for supermarkets with distinct workflows for customers, store managers, deliverers, and administrators.

---

## 🎯 Core Concept

**Customer Journey:**
```
Browse Products → Add to Cart → Checkout → Place Order → 
Track Delivery → Confirm Receipt (OTP)
```

**Operational Flow:**
```
Order Placed → Manager Assigns Picker → Picker Collects Items → 
Manager Assigns Driver → Driver Delivers → Customer Confirms (OTP)
```

---

## 👥 User Roles

### 1. **Customer** (No Login Required)
- Browse and search products
- View real-time stock availability
- Add items to cart
- Place orders with delivery address
- Track order status in real-time
- Confirm delivery with OTP

### 2. **Store Manager** (Branch-Level Access)
- Manage branch inventory (add, edit, delete products)
- Update product prices and stock quantities
- Process incoming orders
- Assign pickers to orders
- Assign deliverers to orders
- View branch-specific reports
- Request additional staff from admin

### 3. **Admin / General Manager** (System-Wide Access)
- Manage all branches
- Hire and manage store managers
- Assign managers to branches
- Switch managers between branches
- Hire and manage deliverers
- Assign deliverers to branches
- Manage master product catalog
- View system-wide analytics

### 4. **Deliverer / Driver**
- View available delivery assignments
- Accept deliveries
- Navigate to delivery location
- Update delivery status
- Collect OTP from customer
- Complete delivery
- View earnings and history

### 5. **Picker** (Optional - can be same as manager)
- View assigned orders
- Pick items from inventory
- Handle out-of-stock situations
- Suggest substitutions
- Pack orders
- Mark orders ready for delivery

---

## 🗂️ Database Structure

### Core Tables
- **users** - All system users with role-based access
- **supermarkets** - Supermarket chains
- **branches** - Physical store locations
- **categories** - Product categories
- **products** - Master product catalog
- **branch_products** - Branch-specific inventory and pricing
- **orders** - Customer orders with full workflow
- **order_items** - Individual items in orders
- **addresses** - Customer delivery addresses
- **driver_trips** - Delivery tracking

### Key Relationships
- Users → Branch (for staff)
- Orders → Branch, Customer, Picker, Driver
- Products → Categories
- Branch_Products → Branch, Product
- Order_Items → Order, Product

---

## 🔐 Authentication & Authorization

### Customer
- **Optional:** Can browse and order without login
- **Registration:** For order history and saved addresses

### Staff (Manager, Driver, Picker)
- **Required:** Must login to access dashboard
- **Branch-Scoped:** Can only access assigned branch data

### Admin
- **Required:** Full system access
- **No Branch Restriction:** Manages all branches

---

## 🚀 Key Features

### For Customers
✅ No registration required to shop  
✅ Real-time product availability  
✅ Multiple delivery options  
✅ Order tracking with live status  
✅ OTP-based delivery confirmation  
✅ Save addresses for future orders  

### For Store Managers
✅ Branch inventory management  
✅ Order processing and assignments  
✅ Staff coordination  
✅ Sales and inventory reports  
✅ Low stock alerts  

### For Admin
✅ Multi-branch oversight  
✅ Staff lifecycle management  
✅ Master catalog management  
✅ System-wide analytics  
✅ Performance monitoring  

### For Deliverers
✅ Delivery queue management  
✅ Navigation assistance  
✅ Earnings tracking  
✅ Performance metrics  
✅ Customer communication  

---

## 📊 Order Status Flow

```
placed 
  ↓
picking (picker assigned)
  ↓
substitutions_review (if needed)
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

## 🎨 Frontend Routes

### Customer App
```
/                          Home / Browse
/products                  Product catalog
/product/:id               Product details
/cart                      Shopping cart
/checkout                  Checkout flow
/order/:number/track       Track order
/order/:number/confirm     Confirm delivery
```

### Manager Dashboard
```
/manager/login             Login
/manager/dashboard         Overview
/manager/inventory         Product management
/manager/orders            Order management
/manager/staff             Staff overview
/manager/reports           Analytics
```

### Admin Dashboard
```
/admin/login               Login
/admin/dashboard           System overview
/admin/branches            Branch management
/admin/managers            Manager management
/admin/deliverers          Deliverer management
/admin/catalog             Product catalog
/admin/reports             System reports
```

### Deliverer App
```
/driver/login              Login
/driver/dashboard          Available deliveries
/driver/delivery/:id       Delivery details
/driver/history            Past deliveries
/driver/earnings           Earnings dashboard
```

---

## 🛠️ Technology Stack

### Backend
- **Framework:** Laravel 10+
- **Authentication:** Laravel Sanctum
- **Database:** MySQL
- **API:** RESTful

### Frontend
- **Framework:** Vue 3
- **State:** Pinia
- **Router:** Vue Router
- **HTTP:** Axios
- **Build:** Vite

---

## 📦 Project Structure

```
online-grocery-app/
├── server/              # Laravel backend
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AdminController.php
│   │   │   │   ├── ManagerController.php
│   │   │   │   ├── DriverController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── CatalogController.php
│   │   ├── Models/
│   │   └── ...
│   ├── database/
│   │   └── migrations/
│   └── routes/
│       └── api.php
│
└── client/              # Vue frontend
    ├── src/
    │   ├── views/
    │   │   ├── customer/
    │   │   ├── manager/
    │   │   ├── admin/
    │   │   └── driver/
    │   ├── components/
    │   ├── stores/
    │   └── router/
    └── ...
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.1+
- Composer
- Node.js 18+
- MySQL 8.0+

### Backend Setup
```bash
cd server
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

### Frontend Setup
```bash
cd client
npm install
npm run dev
```

---

## 📈 Success Metrics

- **Order Completion Rate:** > 95%
- **Delivery On-Time Rate:** > 90%
- **Customer Satisfaction:** > 4.5/5
- **Inventory Accuracy:** > 95%
- **Average Order Value:** Track growth

---

## 🔄 Current Status

✅ Database schema designed  
✅ User roles defined  
✅ Authentication system planned  
✅ API endpoints documented  
🔄 Backend controllers (in progress)  
🔄 Frontend dashboards (in progress)  
⏳ Testing & deployment (upcoming)  

---

## 📚 Documentation

- [System Architecture](./docs/ARCHITECTURE.md)
- [API Documentation](./docs/API.md)
- [Implementation Roadmap](./docs/ROADMAP.md)
- [Database Schema](./docs/DATABASE.md)

---

## 👨‍💻 Development Team

- **Backend:** Laravel API development
- **Frontend:** Vue.js UI/UX
- **Database:** MySQL schema design
- **DevOps:** Deployment & monitoring

---

## 📝 License

Proprietary - All rights reserved

---

## 📞 Contact

For questions or support, contact the development team.
