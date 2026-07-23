# 💸 SplitEase – Group Expense Splitter

**👥 Team Members:**  
[**Asmath Abbas**](https://github.com/AsmathAbbas) · [**Harsha Atla**](https://github.com/Harsha-156) · [**Jyothiswar Reddy Dwarasala**](https://github.com/Jyothiswar1019)

---

**SplitEase** is a full-stack web application that helps groups of friends, roommates, or colleagues track shared expenses and automatically calculates the minimum number of payments needed to settle everyone's balances. No more messy spreadsheets or confusing IOU lists – just add expenses, and SplitEase tells you who owes whom.

🔗 **Live Demo:** [Your InfinityFree URL here]

---

## 📸 Screenshots

| Dashboard | Group Page | Settlement Plan |
|-----------|------------|-----------------|
| <img width="1440" height="816" alt="dashboard" src="https://github.com/user-attachments/assets/c5777577-9dbd-4c56-bd7b-fcbdcc3788b6" />
 | <img width="1437" height="815" alt="group_page" src="https://github.com/user-attachments/assets/beead0f8-651c-4b43-a5fc-442760a0cb6d" />
 | <img width="1440" height="814" alt="settlement_plan" src="https://github.com/user-attachments/assets/ac653667-c7a8-4681-9fa0-5ff1b699e0cb" />
 |

---

## ✨ Features

### 🔐 Authentication & Security
- Secure user registration & login with **password hashing** (`password_hash` / `password_verify`)
- **Google reCAPTCHA v2** to block automated bot signups
- **Email OTP verification** – accounts are created only after email ownership is confirmed

### 👥 Group Management
- **Create groups** – start a group for your trip, flat, or event
- **Permission-based joining** – users must accept invites before joining
- **Invite members** – send email invitations to registered users

### 💰 Expense Tracking
- **Three split types**:
  - **Equal** – everyone pays the same share
  - **Percentage** – each member pays a specified percentage
  - **Custom** – enter exact amounts for each member
- **Categories** – organize expenses by Food, Travel, Stay, Shopping, etc.
- **Category chart** – visual breakdown of spending using Chart.js

### 🔄 Settlement & Balance
- **Debt simplification algorithm** – minimizes the number of transactions needed
- **Real-time balances** – see who owes whom
- **Mark as paid** – track settlements that have been completed

### 📱 User Experience
- **AJAX-powered** – no page reloads when adding expenses or accepting invites
- **Responsive design** – works on desktop, tablet, and mobile
- **Profile dashboard** – personal financial summary across all groups

---

## 🛠 Technology Stack

| Layer | Technology | Purpose |
|-------|------------|---------|
| **Frontend** | HTML5, CSS3, JavaScript | User interface & interactivity |
| **Backend** | PHP 7.4+ | Server-side logic |
| **Database** | MySQL | Data storage |
| **Charts** | Chart.js | Visual data representation |
| **Email** | SendGrid | Transactional email (OTP) |
| **Bot Protection** | Google reCAPTCHA v2 | Human verification |
| **Hosting** | InfinityFree | Free PHP + MySQL hosting |

---

## 🧠 How It Works

### 1. User Registration & Verification
```mermaid
sequenceDiagram
    User->>register.php: Submit registration form
    register.php->>Users: Check if email exists
    alt Email available
        register.php->>User: Send OTP via SendGrid
        User->>verify_otp.php: Enter OTP
        verify_otp.php->>Users: Create account
    else Email already exists
        register.php->>User: Show error
    end
