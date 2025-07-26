# <img width="50" height="50" alt="logo" src="https://github.com/user-attachments/assets/3fa3a574-46c8-4bb2-a7be-f12fe2a2b1dc"/> CompNest -Online Computer Store


CompNest is a fully functional online computer store built with PHP, MySQL, HTML5, CSS/SCSS, JavaScript, and integrated with PayPal for secure payments. It includes both user and admin modules for managing products, orders, and user accounts.

## Features

- *Product catalog* with categories and details
- *User authentication* (register/login/logout)
- *Shopping cart system*
- *PayPal payment gateway integration*
- *Order management*
- *Admin dashboard* for product and order control
- *Responsive UI* using Bootstrap and custom CSS

## Technologies Used

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Bootstrap 5
- HTML5/CSS3
- JavaScript

## Requirements

- *XAMPP* – Localhost control panel
- *Git* – For repository cloning
- *Browser* – Chrome, Firefox, Safari

## Installation

1. *Start Apache and MySQL services* from the XAMPP Control Panel.
2. *Clone or Download the Repository:*
    bash
    git clone https://github.com/JayPatel-wd/CompNest.git
    
3. *Set up the database:*
    - Import the SQL file from db/ into your MySQL server.
    - Update config.php with your DB credentials.
4. *Configure PayPal:*
    - Set up your PayPal developer account.
    - Add your client ID and secret in paypal_config.php.
5. *Run locally:*
    - Place the project in your local server's root (e.g., htdocs for XAMPP).
    - Access via localhost/CompNest.

## Security Practices

- Passwords hashed using password_hash()
- Uses prepared statements to prevent SQL Injection
- Secure session handling

## License

This project is intended for educational purposes and licensed under the MIT License.

## Developed by CompNest Team

- Shanvitha Thammisetti
- Harsh Patel
- Jay Patel
