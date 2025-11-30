# 📱 PhoneMart – The Ultimate E-Commerce Solution for Tech Retail ✨

**PhoneMart** is a comprehensive, open-source e-commerce platform built in PHP, designed for modern retail of tech gadgets such as smartphones, laptops, tablets, and accessories.  
It features a responsive customer storefront and a powerful admin backend for streamlined business operations.

---

## 🌐 Live Demo
🔗 **Try PhoneMart Live Here**  
*(Click Here — Add your deployed link)*

---

## 🎯 Core Features (Customer Storefront)

The customer-facing application is optimized for a smooth, engaging shopping experience with robust product discovery tools.

---

### 🛒 Shopping Experience
A modern, intuitive interface designed for optimal customer flow:

- **Product Catalog:** Browse smartphones, laptops, tablets, headphones, and more.  
- **Search & Filtering:** Dynamic search (`search.php`) with category/brand filtering (e.g., Smartphones, Apple, Samsung).  
- **Product Pages:** Rich product detail pages (`product.php`) with specs, pricing, and images.

**Includes:** Dynamic category menu, price display, product thumbnails

---

### 🔐 User & Purchase Management
Dedicated tools for registered customers:

- **User Authentication:** Secure login (`login.php`) and profile management (`profile.php`).  
- **Shopping Cart:** Add, review, and modify items (`cart.php`).  
- **Wishlist:** Save products (`wishlist.php`).  
- **Checkout:** Clean multi-step checkout system (`checkout.php`).

**Includes:** User profiles, cart totals, wishlist tracking

---

### 📢 Promotions & Content
Engaging marketing and informational sections:

- **Banners & Deals:** Showcases new arrivals, trending items, and monthly deals.  
- **Service Highlights:** Icons displaying services like Islandwide Delivery, Warranty, etc.

**Includes:** Banner sliders, service icons

---

## ⚙️ Administrative Backend (/admin)

The admin dashboard is the command center for managing products, sales, users, and site content.

---

### 📦 Product Management
Full inventory control:

- CRUD operations for products  
- Stock level monitoring  
- Image uploads (via `uploads/`)

**Key Files (Inferred):**  
`manage_products.php`, `add_product.php`

---

### 🧾 Order & Sales Control
Tools for operational management:

- View incoming, pending, and completed orders  
- Update order statuses (Processing → Shipped → Delivered)  
- Basic sales analytics and dashboard metrics

**Key Files (Inferred):**  
`manage_orders.php`, `dashboard.php`

---

### 🗂️ Content & Settings
Maintain the store structure and presentation:

- Manage categories/brands (Smartphones, Laptops, Apple, Samsung)  
- Manage user/customer accounts  
- Enable or disable maintenance mode (`maintenance.php`)

**Key Files (Inferred):**  
`manage_categories.php`, `manage_users.php`, `settings.php`

---

## ✨ Technology Stack

| Component       | Tech Used                         |
|----------------|-----------------------------------|
| 💻 Backend      | PHP                                |
| 🗃️ Database      | MySQL  |
| 🎨 Styling       | CSS (51.6% of codebase)           |
| 🌐 Interactivity | JavaScript (11.5% of codebase)     |
| 🏗️ Architecture  | Standard LAMP/WAMP Stack           |

---

## 🛠️ Project Details

- Developed by **KING-UPE**  
- Full-featured e-commerce system  
- Includes **SampleDB** folder for quick database setup  
- Built for real-world use with admin + storefront separation

---

## 🚀 Why PhoneMart?

### 🔄 1. Complete E-Commerce Cycle
From registration → product browsing (`product.php`) → carting (`cart.php`) → checkout (`checkout.php`),  
the entire workflow is covered.

### 📈 2. Dedicated Administration
The `/admin` panel empowers business owners with real inventory, order, and content management tools.

### 📱 3. Product-Focused Design
Tailored for tech retail with support for major categories and brands.

---
