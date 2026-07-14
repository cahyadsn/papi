# PAPI Kostick

**Personality and Preference Inventory (PAPI) Kostick** — a web-based psychological assessment application built with PHP and MySQL.

Live demo: [https://psycho.cahyadsn.com/papi](https://psycho.cahyadsn.com/papi) *(v0.4 — Indonesian)*

> ⚠️ **Disclaimer:** This application is intended for educational and personal development purposes only. It must **not** be used as a formal psychometric reference or for any commercial purpose.

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](https://raw.githubusercontent.com/cahyadsn/papi/master/LICENSE)
[![Forks](https://img.shields.io/github/forks/cahyadsn/papi.svg)](https://github.com/cahyadsn/papi/network)
[![Stars](https://img.shields.io/github/stars/cahyadsn/papi.svg)](https://github.com/cahyadsn/papi/stargazers)
[![Issues](https://img.shields.io/github/issues/cahyadsn/papi.svg)](https://github.com/cahyadsn/papi/issues)
[![Donate](https://img.shields.io/badge/$-support-ff69b4.svg?style=flat)](https://paypal.me/cahyadwiana)

---

## Table of Contents

- [About PAPI Kostick](#about-papi-kostick)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Usage](#usage)
- [Database Schema](#database-schema)
- [Changelog](#changelog)
- [Roadmap](#roadmap)
- [Donation](#donation)
- [Contact](#contact)

---

## About PAPI Kostick

**PAPI (Personality and Preference Inventory)** is a leading personality assessment tool used by HR professionals and managers to evaluate individual behavior and work styles at all organizational levels.

PAPI was developed by **Dr. Max Martin Kostick**, Professor of Industrial Psychology at Massachusetts, USA, in the early 1960s. The Swedish version was introduced in the early 1980s, followed by the 1997 release with two variants:

| Variant | Code | Purpose |
|---------|------|---------|
| Ipsative | PAPI-I | Personal development |
| Normative | PAPI-N | Comparison and selection |

The theoretical foundation of PAPI is based on Murray's (1938) **"needs-press"** personality theory, which examines the interaction between a person's internal needs and external environmental pressures.

The test consists of **90 forced-choice question pairs**. For each pair, respondents choose the statement that best describes themselves, even if both seem equally applicable or inapplicable.

---

## Features

- 90-question PAPI Kostick assessment (ipsative format)
- Paginated question view (5 questions per page)
- Client-side validation — prevents unanswered questions before submission
- Automated result interpretation from database rules
- Results grouped by personality aspect and role
- Modern **glassmorphism UI** — no external CSS framework dependency
- Responsive design for desktop and mobile
- XSS-safe output via `htmlspecialchars`
- Session-based question loading (no redundant DB queries)
- `.env`-based credential management via native PHP — no Composer required

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 7.4+ |
| Database | MySQL 5.7+ / MariaDB |
| Frontend | Vanilla HTML, CSS (glassmorphism), Vanilla JS |
| Styling | Custom `css/glass.css` (replaces W3.CSS) |

---

## Project Structure

```
papi/
├── index.php            # Main test page (intro, instructions, questions)
├── papi_process.php     # Result processing and interpretation page
├── .env                 # Environment variables — credentials (DO NOT commit)
├── .env.example         # Safe-to-commit template for .env
├── .gitignore           # Excludes .env and other local files from git
├── css/
│   └── glass.css        # Glassmorphism UI stylesheet
├── js/
│   └── util.js          # Client-side JS (pagination, validation, progress bar)
├── inc/
│   ├── db.php           # Database connection (reads from .env)
│   └── env.php          # Native PHP .env loader — no Composer required
├── db/
│   └── papi.sql         # Database schema and seed data (dummy)
└── README.md
```

---

## Installation

1. **Clone** the repository:
   ```bash
   git clone https://github.com/cahyadsn/papi.git
   ```

2. **Copy** the project folder to your web server's document root (e.g., `htdocs/` for XAMPP or `www/` for Laragon).

3. **Create the database:**
   ```sql
   CREATE DATABASE psycho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

4. **Import the schema:**
   ```bash
   mysql -u root -p psycho < db/papi.sql
   ```

5. **Set up environment variables** — copy the example file and fill in your values:
   ```bash
   cp .env.example .env
   ```
   Then edit `.env`:
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=psycho
   DB_PORT=3306
   DB_CHARSET=utf8mb4

   APP_ENV=development
   APP_DEBUG=true
   ```
   > ⚠️ Never commit `.env` to version control — it is already excluded by `.gitignore`.

6. Open your browser and navigate to `http://localhost/papi`.

> **Note:** The repository contains **dummy data** only. Real psychometric data is not included.

---

## Usage

1. The home page presents a brief introduction to PAPI Kostick.
2. Click **Lanjut** to read the instructions.
3. Click **Mulai Tes** to begin the 90-question assessment.
4. Navigate between pages using **Prev** and **Next** buttons.
5. The progress bar tracks which section you are on.
6. Once all questions are answered, click **Submit** to see your results.
7. The results page displays each personality aspect with its role and interpretation.
8. Click **Ulangi Tes** to retake the test.

---

## Database Schema

The application relies on four main tables:

| Table            | Description |
|------------------|-------------|
| `papi_questions` | The 90 question pairs (`value1`, `question1`, `value2`, `question2`) |
| `papi_aspects` | Personality aspect categories |
| `papi_roles` | Roles within each aspect |
| `papi_rules` | Scoring rules: maps score ranges (`low_value`–`high_value`) to interpretations |

---

## Changelog

| Date       | Change |
|------------|--------|
| 2026-07-14 | Performance optimization: Migrated dynamic `js/util.php` to static `js/util.js` to enable browser caching, indexed database schema keys, and optimized result matching logic to O(1) in `papi_process.php` |
| 2026-07-12 | Added `.env`-based credential management via native PHP loader (`inc/env.php`); credentials moved out of `inc/db.php`; added `.gitignore` and `.env.example` |
| 2026-07-12 | UI refactored — replaced W3.CSS with custom glassmorphism CSS (`css/glass.css`); rewrote `js/util.php` as clean readable JS; added XSS protection and progress bar |
| 2025-11-26 | Bug fix in `papi_process.php` |
| 2021-03-06 | `index.php` updated |
| 2017-04-09 | Initial release |

---

## Roadmap

- [ ] Add English language support
- [ ] PAPI-N (normative) variant
- [ ] Printable / exportable PDF result report
- [ ] User authentication and result history
- [ ] Admin panel for managing questions and scoring rules
- [ ] Chart/radar visualization of personality aspects

---

## Donation

If you find this project useful, consider supporting the author:

**Bank Transfer (Indonesia)**
| Bank | Account Number |
|------|---------------|
| Bank Jago (542) | 5003 5796 1022 |
| Bank BCA Digital / Blu (501) | 000 576 776 186 |
| Bank Sinarmas (153) | 005 462 4719 |
| Bank Syariah Indonesia / BSI | 821-342-5550 |

**PayPal:** [https://paypal.me/cahyadwiana](https://paypal.me/cahyadwiana)

**QRIS:** CAHYADSN ID1022183125288

![QRIS](https://github.com/cahyadsn/wilayah/blob/master/docs/qr_code.cahyadsn.png?raw=true 'Donate via QRIS CAHYADSN')

---

## Contact

| Channel | Link |
|---------|------|
| Email | [cahyadsn@gmail.com](mailto:cahyadsn@gmail.com) |
| Facebook | [https://m.facebook.com/cahya.dsn](https://m.facebook.com/cahya.dsn) |
| Demo site | [https://psycho.cahyadsn.com/papi](https://psycho.cahyadsn.com/papi) |
| Source code | [https://github.com/cahyadsn/papi](https://github.com/cahyadsn/papi) |

---

*copyright © 2017–2026 by [cahyadsn](mailto:cahyadsn@gmail.com) — released under the [MIT License](LICENSE)*
