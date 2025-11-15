# RCard - Virtual Financial Membership Platform

![RCard](https://img.shields.io/badge/RCard-Virtual%20Finance-purple?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue?style=for-the-badge&logo=php)
![Security](https://img.shields.io/badge/Security-AES--256-green?style=for-the-badge&logo=security)
![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)

A comprehensive virtual financial membership platform designed for Roblox-integrated ecosystems. RCard handles user accounts, custom credit/debit cards, interest-bearing loans, in-game perks, sponsor partnerships, and fraud protection tracking.

## 🌟 Features

### Core Functionality
- **Custom Card Programs**: Credit, debit, and merchant cards with customizable benefits and branding
- **Smart Loan System**: Interest-bearing loans with daily accrual, early repayment, and smart preview calculations
- **User Dashboard**: Comprehensive wallet management, card tracking, and loan management
- **Sponsor Dashboard**: Create and manage card programs with full customization
- **AES-256 Encryption**: Enterprise-grade security for all sensitive data
- **Fraud Protection**: Real-time monitoring, rate limiting, and comprehensive legal framework

### Technical Features
- **JSON-Based Storage**: Lightweight file-based database with atomic writes and file locking
- **REST-like API**: Clean, well-documented endpoints for all operations
- **TailwindCSS UI**: Modern, responsive, glassmorphic design
- **Session Management**: Secure authentication with CSRF protection
- **Input Sanitization**: Comprehensive security measures for all user inputs
- **Migration-Ready**: Designed for easy migration to SQL or MongoDB

## 📁 Project Structure

```
/
├── api.php                 # Main API endpoint handler
├── index.php              # Public homepage
├── legal.php              # Legal framework and fraud protection
├── /public
│   ├── /css
│   │   └── main.css       # TailwindCSS styles
│   ├── /js
│   │   ├── main.js        # Main JavaScript utilities
│   │   └── loans.js       # Loan management module
│   └── /img               # Image assets
├── /includes
│   ├── auth.php           # Authentication and user management
│   ├── cards.php          # Card catalog and management
│   ├── loans.php          # Loan system with interest calculations
│   ├── security.php       # Security utilities and session management
│   ├── utils.php          # General utility functions
│   └── encryption.php     # AES-256 encryption helpers
├── /jsondata
│   ├── /users             # User account data
│   ├── /cards             # Card program data (encrypted)
│   │   └── /{org_id}/
│   ├── /loans             # Loan records
│   │   └── /{user_id}/
│   ├── /transactions      # Transaction logs
│   └── /settings          # System settings
├── /org
│   ├── dashboard.php      # Sponsor organization dashboard
│   └── create_card.php    # Card creation interface
└── /dashboard
    ├── home.php           # User dashboard home
    ├── cards.php          # Card catalog and management
    └── loans.php          # Loan management interface
```

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- OpenSSL extension for PHP
- Write permissions for `/jsondata` directory

### Quick Start

1. **Clone the repository**:
```bash
git clone https://github.com/FoundationINCCorporateTeam/RCard.git
cd RCard
```

2. **Set up permissions**:
```bash
chmod -R 755 .
chmod -R 777 jsondata/
```

3. **Configure encryption key** (recommended for production):
```bash
export RCARD_ENCRYPTION_KEY="your-secure-32-character-key-here"
```
Or set it in your web server configuration.

4. **Start the development server**:
```bash
php -S localhost:8000
```

5. **Access the application**:
Open your browser to `http://localhost:8000`

### Production Deployment

For production deployment:

1. **Use a proper web server** (Apache or Nginx)
2. **Set environment variables**:
   - `RCARD_ENCRYPTION_KEY`: Strong encryption key (minimum 32 characters)
3. **Configure HTTPS**: All production traffic should use SSL/TLS
4. **Set restrictive file permissions**:
   - Application files: `644`
   - Directories: `755`
   - Data directory: `700` (only web server can access)
5. **Enable PHP error logging** (disable display_errors)
6. **Configure rate limiting** at the web server level
7. **Regular backups** of the `/jsondata` directory

## 🔐 Security

### Encryption
All sensitive card data is encrypted using AES-256-CBC with:
- Random IV per file
- Base64 encoding
- Atomic file writes with locking

### Session Management
- Secure session configuration
- CSRF token protection
- Session timeout (1 hour default)
- Session regeneration on authentication

### Input Validation
- All inputs sanitized and validated
- Path traversal protection
- SQL injection prevention (prepared for SQL migration)
- XSS protection

### Rate Limiting
- Login attempts: 5 per 5 minutes
- Loan creation: 3 per minute
- API calls: Configurable per endpoint

## 📚 API Documentation

### Authentication Endpoints

#### Register User
```
POST /api.php?action=auth_register
Parameters: username, password
Response: { status: "success", data: { user: {...} } }
```

#### Login
```
POST /api.php?action=auth_login
Parameters: username, password
Response: { status: "success", data: { user: {...}, session_token: "..." } }
```

#### Logout
```
GET /api.php?action=auth_logout
Response: { status: "success", data: { message: "Logged out successfully" } }
```

### Card Endpoints

#### List User Cards
```
GET /api.php?action=cards_list
Response: { status: "success", data: { cards: [...] } }
```

#### Get Card Details
```
GET /api.php?action=cards_details&public_id=xxx
Response: { status: "success", data: { card: {...} } }
```

#### Apply for Card
```
POST /api.php?action=cards_apply
Parameters: card_id, card_identifier, card_type
Response: { status: "success", data: { card: {...} } }
```

### Loan Endpoints

#### Bootstrap Loans (Get Initial Data)
```
GET /api.php?action=loans_bootstrap
Response: { status: "success", data: { loans: [...], cards: [...], policies: {...} } }
```

#### Preview Loan
```
GET /api.php?action=loans_preview&card_id=1&principal=500&days=30
Response: { status: "success", data: { preview: {...} } }
```

#### Create Loan
```
POST /api.php?action=loans_create
Parameters: card_id, principal, days
Response: { status: "success", data: { loan: {...} } }
```

#### List Loans
```
GET /api.php?action=loans_list&status=active
Response: { status: "success", data: { loans: [...] } }
```

#### Repay Loan
```
POST /api.php?action=loans_repay
Parameters: loan_id, amount
Response: { status: "success", data: { result: {...} } }
```

## 💰 Loan System

### Interest Calculation

The loan system uses daily interest calculation:

```
daily_rate = monthly_rate / 30 / 100
interest_amount = principal * daily_rate * days
total_due = principal + interest_amount
```

### Features
- Minimum interest days (default: 5)
- Maximum yearly loan limits per card
- Early repayment support
- Automatic interest accrual
- Real-time preview calculations

### Example
```
Principal: R$ 500
Monthly Rate: 12%
Days: 30
Interest: R$ 60 (500 * 0.004 * 30)
Total Due: R$ 560
```

## 🎨 Card Catalog

### Default Card Types

#### Credit Cards
- **Platinum Credit**: 12% monthly, R$ 2,500/year max
- **Gold Credit**: 10% monthly, R$ 2,000/year max
- **Silver Credit**: 8% monthly, R$ 1,500/year max

#### Debit Cards
- **Premium Debit**: 0% interest, R$ 1,000/year max
- **Standard Debit**: 0% interest, R$ 500/year max

#### Merchant Cards
- **Merchant Pro**: 5% monthly, R$ 5,000/year max

## 🛠️ Development

### Key Functions

#### User Management
- `r_user_create($username, $password)` - Create new user
- `r_user_get($user_id)` - Get user data
- `r_user_authenticate($username, $password)` - Authenticate user
- `r_user_add_card($user_id, $card_info)` - Add card to user

#### Card Management
- `r_card_create($org_id, $spec)` - Create new card
- `r_card_load($org_id, $card_id)` - Load card data (decrypted)
- `r_lookup_card_policy($card_id)` - Get card policy
- `r_card_get_by_public_id($public_id)` - Find card by public identifier

#### Loan Management
- `r_loan_create($user_id, $card_id, $principal, $days)` - Create loan
- `r_loan_preview_interest($principal, $rate, $days)` - Preview calculation
- `r_loan_repay($user_id, $loan_id, $amount)` - Process repayment
- `r_loan_calculate_accrued_interest($loan)` - Calculate current interest

### Adding New Features

1. **New API Endpoint**: Add case in `api.php` switch statement
2. **New Data Model**: Create functions in appropriate `/includes` file
3. **New Page**: Create PHP file in `/dashboard` or `/org`
4. **New Styles**: Add to `/public/css/main.css`

## 📊 Database Schema

### User Model (`/jsondata/users/{id}.json`)
```json
{
  "id": 123,
  "username": "example",
  "password_hash": "...",
  "cards": [
    { "id": 1, "card_identifier": "x123", "type": "credit" }
  ],
  "balances": {
    "central_wallet": 0
  },
  "created_at": "2025-01-01T00:00:00+00:00",
  "last_login": "2025-01-01T00:00:00+00:00"
}
```

### Card Model (`/jsondata/cards/{org_id}/{card_id}.json`)
```json
{
  "blob": "base64_encrypted_data",
  "iv": "base64_iv"
}
```

Decrypted:
```json
{
  "public_identifier": "unique",
  "org_id": "abc",
  "spec": {
    "name": "Card Name",
    "annual_fee": 500,
    "interest_rate": 12,
    "max_yearly_loans": 2500,
    "min_interest_days": 5,
    "brand_primary": "#14b8a6",
    "brand_secondary": "#3b82f6"
  }
}
```

### Loan Model (`/jsondata/loans/{user_id}/loan_{id}.json`)
```json
{
  "id": "loan_001",
  "user_id": 123,
  "card_id": 50,
  "principal": 500,
  "interest_rate_monthly": 12,
  "interest_accrued": 0,
  "created_at": "2025-01-01",
  "due_date": "2025-02-01",
  "status": "active",
  "last_interest_calc": "2025-01-01"
}
```

## 🔄 Migration to SQL

The codebase is designed for easy migration to SQL databases:

1. All data access is centralized in `/includes` modules
2. Clean function abstractions (e.g., `r_user_get()`, `r_card_load()`)
3. No mixing of data access logic in endpoint handlers
4. Simple mapping from JSON files to SQL tables

### Migration Steps

1. Create SQL schema based on JSON models
2. Implement new data access functions with same signatures
3. Update constants in `/includes/utils.php` to use database
4. Migrate existing JSON data to SQL
5. Test thoroughly and switch over

## ⚖️ Legal & Compliance

- **Legal Framework**: Comprehensive protection agreement in `/legal.php`
- **Jurisdiction**: Minnesota, United States
- **Fraud Protection**: Real-time monitoring and reporting
- **Criminal Charges**: Wire fraud, identity theft, computer fraud
- **Civil Liability**: Damages, restitution, platform bans

## 🤝 Contributing

This is a proprietary platform. For authorized contributors:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

Proprietary. All rights reserved.

## 📞 Support

For support, please contact the platform administrators or file an issue in the repository.

---

**Built with ❤️ for the Roblox ecosystem**
