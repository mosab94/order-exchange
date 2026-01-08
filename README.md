# Order Exchange

A high-performance real-time order matching engine built with Laravel 12, Inertia.js (Vue 3), and Tailwind CSS v4. This application simulates a cryptocurrency exchange where users can place limit orders (Buy/Sell) and have them matched automatically.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Vue 3, Inertia.js v2, Tailwind CSS v4
- **Real-time:** Laravel Echo & Pusher
- **Database:** MySQL / PostgreSQL / SQLite
- **Testing:** Pest PHP

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- A database (MySQL, PostgreSQL, or SQLite)

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mosab94/order-exchange.git
   cd order-exchange
   ```

2. **Install dependencies and setup:**
   ```bash
   composer run setup
   ```
   *This command installs dependencies, sets up the `.env`, generates the key, runs migrations, and builds assets.*

3. **Environment Setup:**
   Ensure your Pusher credentials are configured in the `.env` file to see live orderbook updates across different sessions.
   ```env
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=your-id
   PUSHER_APP_KEY=your-key
   PUSHER_APP_SECRET=your-secret
   PUSHER_APP_CLUSTER=your-cluster
   ```

## Database Migration & Seeding

If you didn't use `composer run setup` or want to reset:

1. **Run migrations and seed the database:**
   ```bash
   php artisan migrate:fresh --seed
   ```

### Default Test Users

The `TestDataSeeder` creates two accounts for testing real-time matching:

| Email | Password | Initial Balance | Assets |
|-------|----------|-----------------|--------|
| **u1@example.com** | `1234` | $100,000.00 | 10 BTC, 100 ETH |
| **u2@example.com** | `1234` | $100,000.00 | 10 BTC, 100 ETH |

## Running the Application

1. **Start all development services:**
   ```bash
   composer run dev
   ```
   *This command concurrently runs the Laravel server, Vite, queue listener, and logs.*

2. **Real-time Updates:**
   The application uses Laravel Echo to broadcast orderbook changes to all connected users.

## Testing

Run the test suite using Pest:
```bash
php artisan test
```

## Features

- **Limit Order Engine:** Place Buy or Sell orders at specific prices.
- **Auto-Matching:** Orders are instantly matched if a corresponding counter-order exists at a valid price.
- **Wallet System:** Tracks USD balance and asset holdings (BTC/ETH).
- **Real-time Orderbook:** Uses Laravel Echo to broadcast orderbook changes to all connected users.
- **Order History:** Filterable history of your own trades and active orders.
